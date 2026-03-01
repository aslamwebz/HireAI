<x-layouts::app.sidebar :title="__('Edit Resume')">
    <livewire:resume-builder :resume-id="$resume->id" />
</x-layouts::app.sidebar>
