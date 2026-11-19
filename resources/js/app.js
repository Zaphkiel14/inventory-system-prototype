import { registerChart } from './chart'

// Livewire starts Alpine on DOMContentLoaded; this module runs before that.
if (window.Alpine) {
    registerChart(window.Alpine)
} else {
    document.addEventListener('alpine:init', () => registerChart(window.Alpine))
}
