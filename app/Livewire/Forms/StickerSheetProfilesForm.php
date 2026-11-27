<?php

namespace App\Livewire\Forms;

use App\Models\StickerSheetProfiles;
use App\Services\Property\TagSheetLayout;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/**
 * Creates or edits a sticker sheet profile. All sizes are millimetres with
 * up to two decimals; calibration offsets may be negative (−20..20). The
 * grid must fit the page (TagSheetLayout::fits()).
 */
class StickerSheetProfilesForm extends Form
{
    /** The mm fields that must be ≥ 0 (offsets are checked separately). */
    public const SIZE_FIELDS = [
        'page_width_mm', 'page_height_mm', 'tag_width_mm', 'tag_height_mm',
        'margin_top_mm', 'margin_left_mm', 'gap_x_mm', 'gap_y_mm',
    ];

    public ?StickerSheetProfiles $profile = null;

    public string $name = '';

    public string $page_width_mm = '';

    public string $page_height_mm = '';

    public int $columns = 1;

    public int $rows = 1;

    public string $tag_width_mm = '';

    public string $tag_height_mm = '';

    public string $margin_top_mm = '0';

    public string $margin_left_mm = '0';

    public string $gap_x_mm = '0';

    public string $gap_y_mm = '0';

    public string $offset_x_mm = '0';

    public string $offset_y_mm = '0';

    /**
     * A new profile, pre-filled with the standard A4 2 × 5 layout.
     */
    public function initialize(): void
    {
        $this->reset();
        $this->resetValidation();

        $this->fill([...StickerSheetProfiles::DEFAULT_PROFILE, 'name' => '']);
    }

    public function load(StickerSheetProfiles $profile): void
    {
        $this->reset();
        $this->resetValidation();

        $this->profile = $profile;
        $this->name = $profile->name;
        $this->columns = $profile->columns;
        $this->rows = $profile->rows;

        foreach ([...self::SIZE_FIELDS, 'offset_x_mm', 'offset_y_mm'] as $field) {
            $this->{$field} = (string) $profile->{$field};
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'columns' => ['required', 'integer', 'min:1', 'max:20'],
            'rows' => ['required', 'integer', 'min:1', 'max:20'],
            'offset_x_mm' => ['required', 'numeric', 'decimal:0,2', 'min:-20', 'max:20'],
            'offset_y_mm' => ['required', 'numeric', 'decimal:0,2', 'min:-20', 'max:20'],
        ];

        foreach (self::SIZE_FIELDS as $field) {
            $rules[$field] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'page_width_mm' => 'page width', 'page_height_mm' => 'page height',
            'tag_width_mm' => 'tag width', 'tag_height_mm' => 'tag height',
            'margin_top_mm' => 'top margin', 'margin_left_mm' => 'left margin',
            'gap_x_mm' => 'horizontal gap', 'gap_y_mm' => 'vertical gap',
            'offset_x_mm' => 'horizontal offset', 'offset_y_mm' => 'vertical offset',
        ];
    }

    /**
     * Validate the fields and that the grid fits the page. The layout error
     * is on "form.layout".
     */
    public function validateProfile(): void
    {
        $this->validate();

        if (! TagSheetLayout::fits($this->unsaved())) {
            throw ValidationException::withMessages([
                'form.layout' => 'The tags don\'t fit on the page: the left margin plus '.$this->columns.' columns and their gaps must fit the page width, and the top margin plus '.$this->rows.' rows and their gaps must fit the page height. Make the tags, margins or gaps smaller, or use fewer columns or rows.',
            ]);
        }
    }

    public function store(): StickerSheetProfiles
    {
        $this->validateProfile();

        $values = $this->unsaved()->only([
            'name', 'columns', 'rows', ...self::SIZE_FIELDS, 'offset_x_mm', 'offset_y_mm',
        ]);

        if ($this->profile !== null) {
            $this->profile->update($values);

            return $this->profile;
        }

        return StickerSheetProfiles::create([
            ...$values,
            // The first profile ever saved becomes the default.
            'is_default' => ! StickerSheetProfiles::query()->exists(),
        ]);
    }

    /**
     * The form's values as an unsaved profile, for the fit check and the
     * live grid preview. Non-numeric sizes count as 0.
     */
    public function unsaved(): StickerSheetProfiles
    {
        $profile = new StickerSheetProfiles(['name' => trim($this->name), 'columns' => $this->columns, 'rows' => $this->rows]);

        foreach ([...self::SIZE_FIELDS, 'offset_x_mm', 'offset_y_mm'] as $field) {
            $profile->{$field} = number_format(is_numeric($this->{$field}) ? (float) $this->{$field} : 0, 2, '.', '');
        }

        return $profile;
    }
}
