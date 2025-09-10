@props(['variables', 'framework' => 'bootstrap'])

@if($framework === 'tailwind')
    <h2 class="text-lg font-bold mb-3">Variables</h2>
    <p class="text-gray-600 text-sm mb-4">Click or drag variables into the email content, or add manually.</p>

    <form wire:submit.prevent="addVariable" class="mb-4">
        <div class="flex">
            <input type="text" wire:model="newVariable"
                class="{{ mass_mailer_get_form_classes('input', $framework) }} flex-1 rounded-l-md"
                placeholder="Variable Name" />
            <button type="submit"
                class="{{ mass_mailer_get_color_classes('primary', $framework) }} px-4 py-2 rounded-r-md transition-colors">
                <i class="fas fa-code mr-2"></i> Add
            </button>
        </div>
    </form>

    @foreach ($variables as $index => $variable)
        <div class="flex justify-between items-center bg-gray-100 rounded shadow-sm p-3 mb-2 cursor-move"
            draggable="true" ondragstart="handleDragStart(event, '{{ $variable }}')">
            <span class="font-medium cursor-pointer hover:text-blue-600 transition-colors"
                onclick="handleVariableClick('{{ $variable }}')">{{ $variable }}</span>
            <button type="button" class="text-red-500 hover:text-red-700 p-1"
                wire:click="deleteVariable({{ $index }})" wire:loading.attr="disabled">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endforeach
@else
    <h2 class="fs-5 fw-bold mb-3 ">Variables</h2>
    <p class="text-muted small">Click or drag variables into the email content, or add manually.</p>

    <form wire:submit.prevent="addVariable" class="mb-3">
        <div class="input-group">
            <input type="text" wire:model="newVariable" class="{{ mass_mailer_get_form_classes('input', $framework) }}"
                placeholder="Variable Name" />
            <button type="submit" class="btn {{ mass_mailer_get_color_classes('primary', $framework) }}">
                <i class="fas fa-code me-2 fs-5"></i>
                Add</button>
        </div>
    </form>

    @foreach($variables as $index => $variable)
        <div class="d-flex justify-content-between align-items-center bg-light rounded shadow-sm p-2 mb-2"
            draggable="true" ondragstart="handleDragStart(event, '{{ $variable }}')">
            <span class="cursor-pointer text-primary-hover" onclick="handleVariableClick('{{ $variable }}')">{{ $variable }}</span>
            <button type="button" class="btn {{ mass_mailer_get_button_size_classes('sm', $framework) }} {{ mass_mailer_get_color_classes('danger', $framework) }}"
                wire:click="deleteVariable({{ $index }})" wire:loading.attr="disabled">
                &times;
            </button>
        </div>
    @endforeach
@endif
