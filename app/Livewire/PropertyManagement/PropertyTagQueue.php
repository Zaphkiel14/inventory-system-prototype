<?php

namespace App\Livewire\PropertyManagement;

use App\Enums\PropertyDocumentType;
use App\Enums\PropertyTagStatus;
use App\Enums\TagPrintKind;
use App\Http\Controllers\Property\PropertyTagPdfController;
use App\Models\PropertyTagPrints;
use App\Models\PropertyTags;
use App\Models\StickerSheetProfiles;
use App\Services\Property\PropertyTagContent;
use App\Services\Property\PropertyTagSheet;
use App\Services\Property\TagSheetLayout;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The property tag print queue. Pick tags, preview them on a sticker sheet
 * (a signed PDF link opened in a new tab), then confirm which ones actually
 * printed: only confirmed tags are marked Printed and logged. Reprints need
 * a reason. Blank sticker sheets and the calibration page are logged when
 * opened.
 *
 * Each row of `tags` carries `content` (PropertyTagContent::for()).
 *
 * @property-read LengthAwarePaginator<int, PropertyTags> $tags
 * @property-read array<int, string> $profiles
 * @property-read int $slotsPerSheet
 * @property-read array{count: int, printed: int, sheets: int} $selectedSummary
 * @property-read array{columns: int, rows: int} $profileGrid
 * @property-read Collection<int, PropertyTags> $awaitingTags
 */
class PropertyTagQueue extends Component
{
    use WithPagination;

    /** Most tags one preview (or "select all matching") takes. */
    public const MAX_SELECTION = 200;

    public string $search = '';

    /** '' | 'pending' | 'printed' */
    public string $status = 'pending';

    /** '' | 'ics' | 'par' */
    public string $documentType = '';

    public string $fundCluster = '';

    /** Matched against the source requisition's office. */
    public string $office = '';

    /** Document date from, Y-m-d */
    public string $from = '';

    /** Document date to, Y-m-d */
    public string $to = '';

    public int $perPage = 25;

    /** @var array<int, int> selected tag ids, kept across pages */
    public array $selected = [];

    public ?int $profileId = null;

    public int $startSlot = 1;

    public string $reprintReason = '';

    public int $blankCount = 10;

    /**
     * Tag ids in the last preview, waiting for "which printed?". Locked: only
     * preview() sets it, so the browser can't confirm tags it never previewed.
     *
     * @var array<int, int>
     */
    #[Locked]
    public array $awaitingConfirmation = [];

    public function mount(): void
    {
        $this->profileId = StickerSheetProfiles::default()->id;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'documentType', 'fundCluster', 'office', 'from', 'to', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    /**
     * An unknown profile id set from the browser falls back to the default.
     * A profile deleted after it was chosen fires no update: render then
     * shows the default's grid and the actions refuse with a profileId error.
     */
    public function updatedProfileId(): void
    {
        if (! StickerSheetProfiles::query()->whereKey($this->profileId)->exists()) {
            $this->profileId = StickerSheetProfiles::default()->id;
        }

        unset($this->slotsPerSheet, $this->selectedSummary, $this->profileGrid);
    }

    #[On('sticker-profile-saved')]
    public function refreshProfiles(): void
    {
        unset($this->profiles, $this->slotsPerSheet, $this->selectedSummary, $this->profileGrid);

        if (! StickerSheetProfiles::query()->whereKey($this->profileId)->exists()) {
            $this->profileId = StickerSheetProfiles::default()->id;
        }
    }

    /*
     * Selection
     */

    public function selectPage(): void
    {
        $this->select(array_map(fn (PropertyTags $tag) => $tag->id, $this->tags->items()));
    }

    public function selectAllMatching(): void
    {
        $ids = $this->query()->limit(self::MAX_SELECTION + 1)->pluck('property_tags.id')->all();

        if (count($ids) > self::MAX_SELECTION) {
            $ids = array_slice($ids, 0, self::MAX_SELECTION);

            Flux::toast(
                text: 'Selected the first '.self::MAX_SELECTION.' matching tags. Print these, then select the rest.',
                heading: 'Selection limit',
                variant: 'warning'
            );
        }

        $this->select($ids);
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        unset($this->selectedSummary);
    }

    /*
     * Printing
     */

    /**
     * Check the selection, remember it for confirmation, and open the sheet PDF.
     */
    public function preview(): void
    {
        $profile = $this->profile();
        $this->validateStartSlot($profile);

        $ids = $this->selectedIds();

        if ($ids === []) {
            throw ValidationException::withMessages(['selected' => 'Select at least one tag to print.']);
        }

        if (count($ids) > self::MAX_SELECTION) {
            throw ValidationException::withMessages(['selected' => 'Print at most '.self::MAX_SELECTION.' tags at a time.']);
        }

        $reprints = PropertyTags::query()->whereKey($ids)->where('print_count', '>', 0)->count();

        if ($reprints > 0) {
            $this->validate(
                ['reprintReason' => ['required', 'string', 'min:5', 'max:1000']],
                ['reprintReason.required' => "{$reprints} of the selected tags were printed before. Give a reason for reprinting them."],
                ['reprintReason' => 'reprint reason'],
            );
        }

        $this->awaitingConfirmation = $ids;
        unset($this->awaitingTags);

        $this->dispatch('open-pdf', url: PropertyTagPdfController::sheetUrl($profile, $ids, $this->startSlot));
    }

    /**
     * Mark the tags that came out right as printed and log each print. Only
     * tags from the last preview count; unticked ones stay as they were.
     *
     * @param  array<int, int|string>  $printedIds
     */
    public function confirmPrinted(array $printedIds): void
    {
        $ids = array_values(array_intersect(array_map('intval', $printedIds), $this->awaitingConfirmation));

        if ($ids === []) {
            throw ValidationException::withMessages(['awaitingConfirmation' => 'Tick the tags that printed correctly.']);
        }

        $profile = $this->profile();

        $marked = DB::transaction(function () use ($ids, $profile): int {
            $tags = PropertyTags::query()->whereKey($ids)->lockForUpdate()->get();

            if ($tags->contains(fn (PropertyTags $tag) => $tag->print_count > 0) && mb_strlen(trim($this->reprintReason)) < 5) {
                throw ValidationException::withMessages(['reprintReason' => 'Give a reason for reprinting the tags that were printed before.']);
            }

            foreach ($tags as $tag) {
                $reprint = $tag->print_count > 0;

                PropertyTagPrints::create([
                    'property_tag_id' => $tag->id,
                    'user_id' => Auth::id(),
                    'kind' => $reprint ? TagPrintKind::Reprint : TagPrintKind::First,
                    'reason' => $reprint ? trim($this->reprintReason) : null,
                    'sticker_sheet_profile_id' => $profile->id,
                    'printed_at' => now(),
                ]);

                $tag->update([
                    'status' => PropertyTagStatus::Printed,
                    'print_count' => $tag->print_count + 1,
                    'last_printed_at' => now(),
                    'last_printed_by' => Auth::id(),
                ]);
            }

            return $tags->count();
        });

        $this->awaitingConfirmation = [];
        $this->selected = [];
        $this->reprintReason = '';
        unset($this->tags, $this->selectedSummary, $this->awaitingTags);

        Flux::toast(text: "{$marked} ".($marked === 1 ? 'tag' : 'tags').' marked printed.', heading: 'Printed', variant: 'success');
    }

    public function cancelConfirmation(): void
    {
        $this->awaitingConfirmation = [];
        unset($this->awaitingTags);
    }

    public function printBlank(): void
    {
        $profile = $this->profile();

        $this->validate(['blankCount' => ['required', 'integer', 'min:1', 'max:'.PropertyTagPdfController::MAX_BLANK]], attributes: ['blankCount' => 'number of blank stickers']);
        $this->validateStartSlot($profile);

        $this->logSheet(TagPrintKind::Blank, $profile, "{$this->blankCount} blank ".($this->blankCount === 1 ? 'sticker' : 'stickers'));

        $this->dispatch('open-pdf', url: PropertyTagPdfController::blankUrl($profile, $this->blankCount, $this->startSlot));
    }

    public function printCalibration(): void
    {
        $profile = $this->profile();

        $this->logSheet(TagPrintKind::Calibration, $profile, null);

        $this->dispatch('open-pdf', url: PropertyTagPdfController::calibrationUrl($profile));
    }

    /*
     * Data
     */

    /**
     * @return LengthAwarePaginator<int, PropertyTags>
     */
    #[Computed]
    public function tags(): LengthAwarePaginator
    {
        $page = $this->query()
            ->with('line.document.requisition:id,office,ris_number,request_number')
            ->paginate($this->perPage);

        $page->getCollection()->each(fn (PropertyTags $tag) => $tag->setAttribute('content', PropertyTagContent::for($tag)));

        return $page;
    }

    /**
     * @return array<int, string> id => name, default first
     */
    #[Computed]
    public function profiles(): array
    {
        return StickerSheetProfiles::query()->orderByDesc('is_default')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Grid of the chosen profile (or the default) for the start-slot picker.
     *
     * @return array{columns: int, rows: int}
     */
    #[Computed]
    public function profileGrid(): array
    {
        $profile = $this->displayProfile();

        return ['columns' => $profile->columns, 'rows' => $profile->rows];
    }

    /**
     * The tags in the last preview, in print order, each with `content`
     * (PropertyTagContent::for()), for the "which printed?" modal. One joined
     * query plus the eager-loaded lines and documents; empty when nothing is
     * awaiting confirmation.
     *
     * @return Collection<int, PropertyTags>
     */
    #[Computed]
    public function awaitingTags(): Collection
    {
        if ($this->awaitingConfirmation === []) {
            return new Collection;
        }

        return PropertyTagSheet::orderedTags($this->awaitingConfirmation)
            ->each(fn (PropertyTags $tag) => $tag->setAttribute('content', PropertyTagContent::for($tag)));
    }

    #[Computed]
    public function slotsPerSheet(): int
    {
        return TagSheetLayout::slotsPerSheet($this->displayProfile());
    }

    /**
     * @return array{count: int, printed: int, sheets: int}
     */
    #[Computed]
    public function selectedSummary(): array
    {
        $ids = $this->selectedIds();
        $count = count($ids);
        $perSheet = max(1, $this->slotsPerSheet);
        $start = min(max(1, $this->startSlot), $perSheet);

        return [
            'count' => $count,
            'printed' => $count === 0 ? 0 : PropertyTags::query()->whereKey($ids)->where('print_count', '>', 0)->count(),
            'sheets' => $count === 0 ? 0 : (int) ceil(($count + $start - 1) / $perSheet),
        ];
    }

    /**
     * The filtered queue, ordered for printing (document date, document, line).
     *
     * @return Builder<PropertyTags>
     */
    protected function query(): Builder
    {
        $term = '%'.trim($this->search).'%';

        return PropertyTags::query()
            ->select('property_tags.*')
            ->join('property_document_lines as lines', 'lines.id', '=', 'property_tags.property_document_line_id')
            ->join('property_documents as documents', 'documents.id', '=', 'lines.property_document_id')
            ->leftJoin('requisitions', 'requisitions.id', '=', 'documents.requisition_id')
            ->when(PropertyTagStatus::tryFrom($this->status), fn (Builder $query, PropertyTagStatus $status) => $query->where('property_tags.status', $status))
            ->when(PropertyDocumentType::tryFrom($this->documentType), fn (Builder $query, PropertyDocumentType $type) => $query->where('documents.type', $type))
            ->when(trim($this->fundCluster) !== '', fn (Builder $query) => $query->where('documents.fund_cluster', 'like', '%'.trim($this->fundCluster).'%'))
            ->when(trim($this->office) !== '', fn (Builder $query) => $query->where('requisitions.office', 'like', '%'.trim($this->office).'%'))
            ->when($this->validDate($this->from), fn (Builder $query) => $query->whereDate('documents.document_date', '>=', $this->from))
            ->when($this->validDate($this->to), fn (Builder $query) => $query->whereDate('documents.document_date', '<=', $this->to))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('lines.property_number', 'like', $term)
                ->orWhere('lines.item_name', 'like', $term)
                ->orWhere('lines.serial_number', 'like', $term)
                ->orWhere('documents.to_name', 'like', $term)
                ->orWhere('documents.document_number', 'like', $term)))
            ->orderBy('documents.document_date')
            ->orderBy('documents.id')
            ->orderBy('lines.id');
    }

    /**
     * @param  array<int, int>  $ids
     */
    protected function select(array $ids): void
    {
        $this->selected = array_values(array_unique([...$this->selectedIds(), ...array_map('intval', $ids)]));
        unset($this->selectedSummary);
    }

    /**
     * @return array<int, int>
     */
    protected function selectedIds(): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $this->selected), fn (int $id) => $id > 0)));
    }

    /**
     * The profile to show at render: the chosen one, or the default when it
     * is missing. Never throws (actions use profile(), which does).
     */
    protected function displayProfile(): StickerSheetProfiles
    {
        return ($this->profileId ? StickerSheetProfiles::query()->find($this->profileId) : null)
            ?? StickerSheetProfiles::default();
    }

    /**
     * The chosen profile for an action; a missing one is a validation error
     * on profileId.
     */
    protected function profile(): StickerSheetProfiles
    {
        $profile = $this->profileId ? StickerSheetProfiles::query()->find($this->profileId) : null;

        if ($profile === null) {
            throw ValidationException::withMessages(['profileId' => 'Choose a sticker sheet profile.']);
        }

        return $profile;
    }

    protected function validateStartSlot(StickerSheetProfiles $profile): void
    {
        $this->validate(
            ['startSlot' => ['required', 'integer', 'min:1', 'max:'.TagSheetLayout::slotsPerSheet($profile)]],
            attributes: ['startSlot' => 'starting slot'],
        );
    }

    protected function logSheet(TagPrintKind $kind, StickerSheetProfiles $profile, ?string $reason): void
    {
        PropertyTagPrints::create([
            'property_tag_id' => null,
            'user_id' => Auth::id(),
            'kind' => $kind,
            'reason' => $reason,
            'sticker_sheet_profile_id' => $profile->id,
            'printed_at' => now(),
        ]);
    }

    private function validDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    public function render(): View
    {
        return view('livewire.property-management.property-tag-queue');
    }
}
