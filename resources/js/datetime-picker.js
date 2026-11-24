/**
 * Date and time picker behind <x-datetime-picker> (free alternative to Flux
 * Pro's date and time pickers).
 *
 * The value is a wall-clock string, "YYYY-MM-DDTHH:mm" (or "YYYY-MM-DD" when
 * the time is turned off). That is the same format <input type="datetime-local">
 * sends, so Livewire forms parse it exactly as before. "Today" and "Now" are
 * read in the app's timezone, not the browser's.
 */

const pad = (n) => String(n).padStart(2, '0')

export const dateKey = (year, month, day) => `${year}-${pad(month)}-${pad(day)}`

/** "2026-11-24T14:35" → { year, month, day, hour, minute }, or null. */
export function parseValue(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/.exec(String(value ?? ''))

    if (!match) return null

    return {
        year: +match[1],
        month: +match[2],
        day: +match[3],
        hour: match[4] === undefined ? 0 : +match[4],
        minute: match[5] === undefined ? 0 : +match[5],
    }
}

export function formatValue(parts, withTime = true) {
    const date = dateKey(parts.year, parts.month, parts.day)

    return withTime ? `${date}T${pad(parts.hour)}:${pad(parts.minute)}` : date
}

/** The current wall-clock time in a timezone, falling back to the browser's. */
export function nowIn(timeZone) {
    try {
        const parts = Object.fromEntries(
            new Intl.DateTimeFormat('en-CA', {
                timeZone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23',
            })
                .formatToParts(new Date())
                .map((part) => [part.type, part.value]),
        )

        return { year: +parts.year, month: +parts.month, day: +parts.day, hour: +parts.hour % 24, minute: +parts.minute }
    } catch {
        const d = new Date()

        return { year: d.getFullYear(), month: d.getMonth() + 1, day: d.getDate(), hour: d.getHours(), minute: d.getMinutes() }
    }
}

export function addMonths(year, month, delta) {
    const index = year * 12 + (month - 1) + delta

    return { year: Math.floor(index / 12), month: (((index % 12) + 12) % 12) + 1 }
}

export function addDays(key, delta) {
    const p = parseValue(key)
    const d = new Date(p.year, p.month - 1, p.day + delta)

    return dateKey(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

export const daysInMonth = (year, month) => new Date(year, month, 0).getDate()

/** Six full weeks covering the month, starting on `weekStart` (0 = Sunday). */
export function monthGrid(year, month, weekStart = 0) {
    const offset = (new Date(year, month - 1, 1).getDay() - weekStart + 7) % 7

    return Array.from({ length: 42 }, (_, i) => {
        const d = new Date(year, month - 1, 1 - offset + i)
        const cell = { year: d.getFullYear(), month: d.getMonth() + 1, day: d.getDate() }

        return { ...cell, key: dateKey(cell.year, cell.month, cell.day), inMonth: cell.month === month }
    })
}

/** Keep a value inside min/max. All three share one format, so strings compare in order. */
export function clamp(value, min, max) {
    if (min && value < min) return min
    if (max && value > max) return max

    return value
}

/** Widen a date-only bound to a full date-time so it compares against date-time values. */
function bound(value, withTime, endOfDay) {
    if (!value) return null
    const p = parseValue(value)
    if (!p) return null
    if (!withTime) return formatValue(p, false)

    return String(value).length > 10 ? formatValue(p) : `${formatValue(p, false)}T${endOfDay ? '23:59' : '00:00'}`
}

export function datetimePicker(config = {}) {
    const withTime = config.time !== false
    const step = Math.max(1, Math.min(60, Number(config.minuteStep) || 1))
    const locale = config.locale || undefined

    return {
        value: null,
        open: false,
        withTime,
        step,
        timeZone: config.timeZone,
        weekStart: Number(config.weekStart) || 0,
        min: bound(config.min, withTime, false),
        max: bound(config.max, withTime, true),
        view: { year: 2000, month: 1 },
        /** 'days' (calendar), 'months' (pick a month of view.year) or 'years' (pick a year). */
        mode: 'days',
        focused: null,

        init() {
            this.showMonthOf(this.parts ?? this.now())

            // Follow changes made from the server while the panel is closed.
            this.$watch('value', () => {
                if (!this.open && this.parts) this.showMonthOf(this.parts)
            })
        },

        now() {
            return nowIn(this.timeZone)
        },

        get parts() {
            return parseValue(this.value)
        },

        get todayKey() {
            const n = this.now()

            return dateKey(n.year, n.month, n.day)
        },

        get selectedKey() {
            const p = this.parts

            return p ? dateKey(p.year, p.month, p.day) : null
        },

        get minDate() {
            return this.min ? this.min.slice(0, 10) : null
        },

        get maxDate() {
            return this.max ? this.max.slice(0, 10) : null
        },

        get display() {
            const p = this.parts
            if (!p) return ''

            const options = withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' }

            return new Intl.DateTimeFormat(locale, options).format(new Date(p.year, p.month - 1, p.day, p.hour, p.minute))
        },

        get monthLabel() {
            return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(
                new Date(this.view.year, this.view.month - 1, 1),
            )
        },

        /** First year of the twelve-year page shown in the years view. */
        get yearPageStart() {
            return Math.floor(this.view.year / 12) * 12
        },

        /** The clickable heading: "November 2026", "2026" or "2016 – 2027". */
        get heading() {
            if (this.mode === 'months') return String(this.view.year)
            if (this.mode === 'years') return `${this.yearPageStart} – ${this.yearPageStart + 11}`

            return this.monthLabel
        },

        get headingHint() {
            return this.mode === 'days' ? 'Choose month and year' : this.mode === 'months' ? 'Choose year' : 'Back to calendar'
        },

        get prevLabel() {
            return { days: 'Previous month', months: 'Previous year', years: 'Previous years' }[this.mode]
        },

        get nextLabel() {
            return { days: 'Next month', months: 'Next year', years: 'Next years' }[this.mode]
        },

        get months() {
            const label = new Intl.DateTimeFormat(locale, { month: 'short' })
            const long = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' })
            const p = this.parts
            const n = this.now()

            return Array.from({ length: 12 }, (_, i) => {
                const month = i + 1
                const first = dateKey(this.view.year, month, 1)
                const last = dateKey(this.view.year, month, daysInMonth(this.view.year, month))

                return {
                    month,
                    label: label.format(new Date(this.view.year, i, 1)),
                    longLabel: long.format(new Date(this.view.year, i, 1)),
                    isSelected: Boolean(p && p.year === this.view.year && p.month === month),
                    isCurrent: n.year === this.view.year && n.month === month,
                    isDisabled: Boolean((this.maxDate && first > this.maxDate) || (this.minDate && last < this.minDate)),
                }
            })
        },

        get years() {
            const p = this.parts
            const n = this.now()

            return Array.from({ length: 12 }, (_, i) => {
                const year = this.yearPageStart + i

                return {
                    year,
                    isSelected: Boolean(p && p.year === year),
                    isCurrent: n.year === year,
                    isDisabled: Boolean(
                        (this.maxDate && `${year}-01-01` > this.maxDate) || (this.minDate && `${year}-12-31` < this.minDate),
                    ),
                }
            })
        },

        get weekdays() {
            // 4 Jan 1970 was a Sunday.
            return Array.from({ length: 7 }, (_, i) => {
                const d = new Date(1970, 0, 4 + ((this.weekStart + i) % 7))

                return {
                    short: new Intl.DateTimeFormat(locale, { weekday: 'narrow' }).format(d),
                    long: new Intl.DateTimeFormat(locale, { weekday: 'long' }).format(d),
                }
            })
        },

        get cells() {
            const label = new Intl.DateTimeFormat(locale, { dateStyle: 'full' })

            return monthGrid(this.view.year, this.view.month, this.weekStart).map((cell) => ({
                ...cell,
                label: label.format(new Date(cell.year, cell.month - 1, cell.day)),
                isToday: cell.key === this.todayKey,
                isSelected: cell.key === this.selectedKey,
                isFocused: cell.key === this.focused,
                isDisabled: this.isDisabledDate(cell.key),
            }))
        },

        get hour12() {
            const p = this.parts

            return p ? String(((p.hour + 11) % 12) + 1) : ''
        },

        get minuteValue() {
            const p = this.parts

            return p ? pad(p.minute) : ''
        },

        get meridiem() {
            const p = this.parts

            return p ? (p.hour >= 12 ? 'PM' : 'AM') : ''
        },

        isDisabledDate(key) {
            return Boolean((this.minDate && key < this.minDate) || (this.maxDate && key > this.maxDate))
        },

        showMonthOf(p) {
            this.view = { year: p.year, month: p.month }
        },

        show() {
            // Read the live attribute: Livewire can enable or disable the field after it loads.
            if (this.$refs.trigger?.disabled) return

            const p = this.parts ?? this.now()
            this.showMonthOf(p)
            this.focused = this.selectedKey ?? this.todayKey
            this.mode = 'days'
            this.open = true
            this.focusCell()
        },

        close(returnFocus = false) {
            this.open = false
            if (returnFocus) this.$nextTick(() => this.$refs.trigger?.focus())
        },

        toggle() {
            this.open ? this.close() : this.show()
        },

        /** The time a newly picked day gets when nothing is selected yet: now, on the step. */
        defaultTime() {
            const n = this.now()
            const minute = Math.floor(n.minute / step) * step

            return { hour: n.hour, minute }
        },

        commit(parts) {
            this.value = clamp(formatValue(parts, withTime), this.min, this.max)
        },

        pick(cell) {
            if (cell.isDisabled) return

            const time = this.parts ?? this.defaultTime()
            this.commit({ year: cell.year, month: cell.month, day: cell.day, hour: time.hour, minute: time.minute })
            this.focused = cell.key

            if (!cell.inMonth) this.showMonthOf(cell)
            if (!withTime) this.close(true)
        },

        setTime(changes) {
            const base = this.parts ?? { ...this.now(), ...this.defaultTime() }
            const hour12 = Number(changes.hour12 ?? this.hour12 ?? 12) || 12
            const meridiem = changes.meridiem ?? (this.meridiem || (base.hour >= 12 ? 'PM' : 'AM'))
            const minute = Number(changes.minute ?? base.minute)

            this.commit({ ...base, hour: (hour12 % 12) + (meridiem === 'PM' ? 12 : 0), minute })
        },

        setNow() {
            const n = this.now()
            this.commit({ ...n, ...(withTime ? this.defaultTime() : {}) })

            const p = this.parts
            this.showMonthOf(p)
            this.focused = this.selectedKey

            if (!withTime) this.close(true)
        },

        clear() {
            this.value = null
            this.close(true)
        },

        /** The heading steps out: calendar → months → years → back to the calendar. */
        toggleMode() {
            this.mode = { days: 'months', months: 'years', years: 'days' }[this.mode]
            this.mode === 'days' ? this.focusCell() : this.focusOption()
        },

        /** Escape steps back to the calendar first, then closes. */
        back() {
            if (this.mode === 'days') return this.close(true)

            this.mode = 'days'
            this.focusCell()
        },

        prev() {
            this.step(-1)
        },

        next() {
            this.step(1)
        },

        step(delta) {
            if (this.mode === 'months') return (this.view = { ...this.view, year: this.view.year + delta })
            if (this.mode === 'years') return (this.view = { ...this.view, year: this.view.year + delta * 12 })

            this.moveMonth(delta)
        },

        pickMonth(option) {
            if (option.isDisabled) return

            this.view = { ...this.view, month: option.month }
            this.mode = 'days'
            this.keepFocusedDayInView()
            this.focusCell()
        },

        pickYear(option) {
            if (option.isDisabled) return

            this.view = { ...this.view, year: option.year }
            this.mode = 'months'
            this.focusOption()
        },

        /** Move the keyboard focus to the same day number in the month on screen. */
        keepFocusedDayInView() {
            const day = parseValue(this.focused ?? this.todayKey).day
            this.focused = dateKey(this.view.year, this.view.month, Math.min(day, daysInMonth(this.view.year, this.view.month)))
        },

        focusOption() {
            this.$nextTick(() => {
                const panel = this.$refs[this.mode]
                ;(panel?.querySelector('[data-selected="true"]:not(:disabled)')
                    ?? panel?.querySelector('[data-current="true"]:not(:disabled)')
                    ?? panel?.querySelector('button:not(:disabled)'))?.focus()
            })
        },

        moveMonth(delta) {
            this.view = addMonths(this.view.year, this.view.month, delta)
            this.keepFocusedDayInView()
        },

        /** Arrow keys, Page Up/Down and Home/End move around the calendar grid. */
        onGridKey(event) {
            const moves = {
                ArrowLeft: () => addDays(this.focused, -1),
                ArrowRight: () => addDays(this.focused, 1),
                ArrowUp: () => addDays(this.focused, -7),
                ArrowDown: () => addDays(this.focused, 7),
                Home: () => addDays(this.focused, -((new Date(...this.ymd(this.focused)).getDay() - this.weekStart + 7) % 7)),
                End: () => addDays(this.focused, 6 - ((new Date(...this.ymd(this.focused)).getDay() - this.weekStart + 7) % 7)),
            }

            if (event.key === 'PageUp' || event.key === 'PageDown') {
                event.preventDefault()
                this.moveMonth(event.key === 'PageUp' ? -1 : 1)

                return this.focusCell()
            }

            if (!moves[event.key] || !this.focused) return

            event.preventDefault()
            this.focused = moves[event.key]()

            const p = parseValue(this.focused)
            if (p.year !== this.view.year || p.month !== this.view.month) this.showMonthOf(p)

            this.focusCell()
        },

        ymd(key) {
            const p = parseValue(key)

            return [p.year, p.month - 1, p.day]
        },

        focusCell() {
            this.$nextTick(() => this.$refs.grid?.querySelector(`[data-key="${this.focused}"]`)?.focus())
        },
    }
}

export function registerDatetimePicker(Alpine) {
    Alpine.data('datetimePicker', datetimePicker)
}
