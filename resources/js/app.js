import { registerChart } from './chart'
import { registerDatetimePicker } from './datetime-picker'

const register = (Alpine) => {
    registerChart(Alpine)
    registerDatetimePicker(Alpine)
}

// Livewire starts Alpine on DOMContentLoaded; this module runs before that.
if (window.Alpine) {
    register(window.Alpine)
} else {
    document.addEventListener('alpine:init', () => register(window.Alpine))
}
