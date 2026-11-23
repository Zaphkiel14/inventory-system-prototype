{{-- Who sent a requisition and why. Expects $requisition. --}}
<dl class="grid gap-4 sm:grid-cols-3">
    <div>
        <flux:text size="sm">Immediate supervisor</flux:text>
        <flux:text variant="strong">{{ $requisition->supervisor_name ?: '—' }}</flux:text>
    </div>
    <div class="sm:col-span-2">
        <flux:text size="sm">Designation</flux:text>
        <flux:text variant="strong">{{ $requisition->designation ?: '—' }}</flux:text>
    </div>
    <div class="sm:col-span-3">
        <flux:text size="sm">Purpose</flux:text>
        <flux:text variant="strong">{{ $requisition->purpose }}</flux:text>
    </div>
</dl>
