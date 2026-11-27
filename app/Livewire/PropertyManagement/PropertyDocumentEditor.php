<?php

namespace App\Livewire\PropertyManagement;

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Enums\PropertyDocumentType;
use App\Livewire\Forms\PropertyDocumentForm;
use App\Models\PropertyDocuments;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The ICS/PAR editor modal ("property-document-editor"): create a
 * hand-encoded document, edit a draft, finalize it, correct a finalized one
 * with a reason, or change its status.
 *
 * @property-read bool $isFinalized
 * @property-read array<string, string> $statusOptions
 * @property-read array<int, string|null> $lineClasses
 * @property-read PropertyDocuments|null $document
 */
class PropertyDocumentEditor extends Component
{
    public const MODAL = 'property-document-editor';

    public PropertyDocumentForm $form;

    #[On('create-property-document')]
    public function create(string $type, ?string $icsClass = null): void
    {
        $this->form->initialize(PropertyDocumentType::from($type), PropertyClass::tryFrom((string) $icsClass));
        $this->forgetDocument();

        Flux::modal(self::MODAL)->show();
    }

    #[On('edit-property-document')]
    public function edit(int $id): void
    {
        $this->form->load(PropertyDocuments::query()->findOrFail($id));
        $this->forgetDocument();

        Flux::modal(self::MODAL)->show();
    }

    public function save(): void
    {
        $wasFinalized = $this->isFinalized;
        $document = $this->form->store();

        $this->done($document, $wasFinalized ? 'Changes saved and logged.' : 'Draft saved.');
    }

    public function finalize(): void
    {
        $document = $this->form->finalize();

        $this->done($document, 'Finalized. The document is now active.');
    }

    public function changeStatus(string $status, string $reason): void
    {
        $target = PropertyDocumentStatus::tryFrom($status);

        if ($target === null) {
            $this->addError('form.status', 'Choose a valid status.');

            return;
        }

        $document = $this->form->changeStatus($target, $reason);

        $this->done($document, "Marked {$target->label()}.");
    }

    /**
     * Delete the draft being edited (finalized documents can't be deleted).
     */
    public function deleteDraft(): void
    {
        $document = $this->document;

        if ($document === null) {
            $this->close();
            Flux::modal(self::MODAL)->close();

            return;
        }

        $this->form->deleteDraft();

        Flux::modal(self::MODAL)->close();
        Flux::toast(text: 'Draft deleted.', heading: strtoupper($document->type->value), variant: 'success');

        $this->dispatch('property-document-saved', id: $document->id);
        $this->close();
    }

    public function addLine(): void
    {
        $this->form->addLine();
    }

    public function removeLine(int $index): void
    {
        $this->form->removeLine($index);
    }

    /**
     * The stored document being edited (null for a new one).
     */
    #[Computed]
    public function document(): ?PropertyDocuments
    {
        return $this->form->document();
    }

    #[Computed]
    public function isFinalized(): bool
    {
        return (bool) $this->document?->isFinalized();
    }

    /**
     * Statuses this document can move to now (value => label); empty unless Active.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        if ($this->document?->status !== PropertyDocumentStatus::Active) {
            return [];
        }

        return collect(PropertyDocumentForm::STATUS_TARGETS)
            ->mapWithKeys(fn (PropertyDocumentStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * Cost class per line for the live badge (index => splv/sphv/ppe, or null
     * while the cost isn't a valid amount).
     *
     * @return array<int, string|null>
     */
    #[Computed]
    public function lineClasses(): array
    {
        return collect($this->form->lines)
            ->keys()
            ->mapWithKeys(fn (int $index) => [$index => $this->form->lineClass($index)?->value])
            ->all();
    }

    /**
     * The modal was closed without saving.
     */
    public function close(): void
    {
        $this->form->reset();
        $this->form->resetValidation();
        $this->forgetDocument();
    }

    protected function done(PropertyDocuments $document, string $message): void
    {
        Flux::modal(self::MODAL)->close();

        Flux::toast(text: $message, heading: strtoupper($document->type->value), variant: 'success');

        $this->dispatch('property-document-saved', id: $document->id);
        $this->close();
    }

    protected function forgetDocument(): void
    {
        unset($this->document, $this->isFinalized, $this->statusOptions, $this->lineClasses);
    }

    public function render(): View
    {
        return view('livewire.property-management.property-document-editor');
    }
}
