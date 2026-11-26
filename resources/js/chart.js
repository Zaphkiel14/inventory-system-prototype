/**
 * Chart engine behind <x-chart> (free alternative to Flux Pro's charts).
 *
 * The Blade child components (<x-chart.line>, <x-chart.bar>, <x-chart.axis>, ...)
 * render hidden marker elements. This engine reads them, takes each series'
 * color from the marker's computed `color` (so Tailwind `text-*` and `dark:`
 * classes just work), and draws an SVG sized to the container.
 */

const esc = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])

const num = (v) => {
    if (v === null || v === undefined || v === '') return null
    const n = Number(v)
    return Number.isFinite(n) ? n : null
}

const r2 = (n) => Math.round(n * 100) / 100

/** Rows may be objects, plain numbers, or a {label: value} map. */
function normalize(value) {
    if (value === null || value === undefined) return []
    if (typeof value === 'string') {
        try {
            value = JSON.parse(value)
        } catch {
            return []
        }
    }
    if (!Array.isArray(value)) {
        return Object.entries(value).map(([label, v]) =>
            v !== null && typeof v === 'object' ? { label, ...v } : { label, value: v },
        )
    }
    return value.map((row, index) => (row !== null && typeof row === 'object' ? row : { value: row, index }))
}

const DATE_KEYS = ['year', 'month', 'day', 'weekday', 'hour', 'minute', 'second', 'dateStyle', 'timeStyle']

/** Build a formatter from Intl options, like Flux's :format prop. */
function formatter(format) {
    if (!format || (typeof format === 'object' && !Object.keys(format).length)) {
        const nf = new Intl.NumberFormat()
        return (v) => (typeof v === 'number' ? nf.format(v) : String(v ?? ''))
    }

    if (Object.keys(format).some((k) => DATE_KEYS.includes(k))) {
        const df = new Intl.DateTimeFormat(undefined, { timeZone: 'UTC', ...format })
        return (v) => {
            const d = new Date(typeof v === 'string' ? v.replace(' ', 'T') : v)
            return Number.isNaN(d.getTime()) ? String(v ?? '') : df.format(d)
        }
    }

    const nf = new Intl.NumberFormat(undefined, format)
    return (v) => (num(v) === null ? String(v ?? '') : nf.format(Number(v)))
}

/** "Nice" round tick values covering [min, max]. */
function niceTicks(min, max, count) {
    if (min === max) {
        max = min === 0 ? 1 : min + Math.abs(min)
        if (min > 0) min = 0
    }
    const raw = (max - min) / Math.max(1, count)
    const mag = 10 ** Math.floor(Math.log10(raw))
    const err = raw / mag
    const step = (err >= 7.5 ? 10 : err >= 3.5 ? 5 : err >= 1.5 ? 2 : 1) * mag
    const lo = Math.floor(min / step) * step
    const hi = Math.ceil(max / step) * step
    const ticks = []
    for (let v = lo; v <= hi + step / 2; v += step) ticks.push(Number(v.toFixed(10)))
    return ticks
}

/** Monotone cubic path through points (no overshoot), or straight segments. */
function linePath(pts, curve) {
    if (!pts.length) return ''
    if (pts.length === 1) return `M${r2(pts[0][0])},${r2(pts[0][1])}`
    if (curve !== 'smooth' || pts.length < 3) {
        return pts.map(([x, y], i) => `${i ? 'L' : 'M'}${r2(x)},${r2(y)}`).join('')
    }

    const n = pts.length
    const dx = [], dy = [], m = [], t = []
    for (let i = 0; i < n - 1; i++) {
        dx[i] = pts[i + 1][0] - pts[i][0]
        dy[i] = pts[i + 1][1] - pts[i][1]
        m[i] = dx[i] ? dy[i] / dx[i] : 0
    }
    t[0] = m[0]
    t[n - 1] = m[n - 2]
    for (let i = 1; i < n - 1; i++) t[i] = m[i - 1] * m[i] <= 0 ? 0 : (m[i - 1] + m[i]) / 2
    for (let i = 0; i < n - 1; i++) {
        if (m[i] === 0) {
            t[i] = t[i + 1] = 0
            continue
        }
        const a = t[i] / m[i], b = t[i + 1] / m[i], s = a * a + b * b
        if (s > 9) {
            const k = 3 / Math.sqrt(s)
            t[i] = k * a * m[i]
            t[i + 1] = k * b * m[i]
        }
    }

    let d = `M${r2(pts[0][0])},${r2(pts[0][1])}`
    for (let i = 0; i < n - 1; i++) {
        const h = dx[i] / 3
        d += `C${r2(pts[i][0] + h)},${r2(pts[i][1] + t[i] * h)} ${r2(pts[i + 1][0] - h)},${r2(pts[i + 1][1] - t[i + 1] * h)} ${r2(pts[i + 1][0])},${r2(pts[i + 1][1])}`
    }
    return d
}

/** Split [x, y|null] points into runs without gaps. */
function segments(pts) {
    const out = []
    let run = []
    for (const p of pts) {
        if (p[1] === null) {
            if (run.length) out.push(run)
            run = []
        } else run.push(p)
    }
    if (run.length) out.push(run)
    return out
}

/** Rectangle with only the "far" end rounded (the end away from the baseline). */
function barPath(x, y, w, h, r, end) {
    if (w <= 0 || h <= 0) return ''
    r = Math.max(0, Math.min(r, w / 2, h / 2))
    const R = (n) => r2(n)
    if (!r) return `M${R(x)},${R(y)}h${R(w)}v${R(h)}h${R(-w)}Z`
    switch (end) {
        case 'top':
            return `M${R(x)},${R(y + h)}V${R(y + r)}Q${R(x)},${R(y)} ${R(x + r)},${R(y)}H${R(x + w - r)}Q${R(x + w)},${R(y)} ${R(x + w)},${R(y + r)}V${R(y + h)}Z`
        case 'bottom':
            return `M${R(x)},${R(y)}V${R(y + h - r)}Q${R(x)},${R(y + h)} ${R(x + r)},${R(y + h)}H${R(x + w - r)}Q${R(x + w)},${R(y + h)} ${R(x + w)},${R(y + h - r)}V${R(y)}Z`
        case 'right':
            return `M${R(x)},${R(y)}H${R(x + w - r)}Q${R(x + w)},${R(y)} ${R(x + w)},${R(y + r)}V${R(y + h - r)}Q${R(x + w)},${R(y + h)} ${R(x + w - r)},${R(y + h)}H${R(x)}Z`
        case 'left':
            return `M${R(x + w)},${R(y)}H${R(x + r)}Q${R(x)},${R(y)} ${R(x)},${R(y + r)}V${R(y + h - r)}Q${R(x)},${R(y + h)} ${R(x + r)},${R(y + h)}H${R(x + w)}Z`
        default:
            return `M${R(x)},${R(y)}h${R(w)}v${R(h)}h${R(-w)}Z`
    }
}

/** Annular sector; angles in radians, 0 = 12 o'clock, clockwise. `gap` in px. */
function arcPath(cx, cy, r0, r1, a0, a1, gap) {
    const pt = (r, a) => `${r2(cx + r * Math.sin(a))},${r2(cy - r * Math.cos(a))}`
    const full = a1 - a0 >= Math.PI * 2 - 1e-6

    if (full) {
        const outer = `M${pt(r1, 0)}A${r1},${r1} 0 1 1 ${pt(r1, Math.PI)}A${r1},${r1} 0 1 1 ${pt(r1, 0)}Z`
        return r0 > 0
            ? `${outer}M${pt(r0, 0)}A${r0},${r0} 0 1 0 ${pt(r0, Math.PI)}A${r0},${r0} 0 1 0 ${pt(r0, 0)}Z`
            : outer
    }

    const po = gap ? Math.min(gap / 2 / r1, (a1 - a0) / 2) : 0
    const pi = gap && r0 > 0 ? Math.min(gap / 2 / r0, (a1 - a0) / 2) : 0
    const o0 = a0 + po, o1 = a1 - po
    const large = (o1 - o0) > Math.PI ? 1 : 0

    let d = `M${pt(r1, o0)}A${r1},${r1} 0 ${large} 1 ${pt(r1, o1)}`
    if (r0 > 0) {
        const i0 = a0 + pi, i1 = a1 - pi
        const largeIn = (i1 - i0) > Math.PI ? 1 : 0
        d += `L${pt(r0, i1)}A${r0},${r0} 0 ${largeIn} 0 ${pt(r0, i0)}Z`
    } else {
        d += `L${r2(cx)},${r2(cy)}Z`
    }
    return d
}

let uid = 0

// One observer on <html> for light / dark switches, shared by every mounted chart.
// It is created with the first chart and disconnected once the last one is destroyed.
const themeListeners = new Set()
let themeObserver = null

function onThemeChange(listener) {
    themeListeners.add(listener)

    if (!themeObserver) {
        themeObserver = new MutationObserver(() => themeListeners.forEach((fn) => fn()))
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
    }

    return () => {
        themeListeners.delete(listener)

        if (themeListeners.size === 0 && themeObserver) {
            themeObserver.disconnect()
            themeObserver = null
        }
    }
}

export default function chart(config = {}) {
    return {
        rows: [],
        layers: [],
        palette: [],
        width: 0,
        height: 0,
        hover: null,
        geo: null,
        id: `fc${++uid}`,

        init() {
            this.readValue()
            this.readLayers()

            if (config.model && this.$wire) {
                const apply = (v) => {
                    this.rows = normalize(v)
                    this.schedule()
                }
                apply(this.$wire.get(config.model))
                this.$wire.$watch(config.model, apply)
            }

            // Server re-renders update data-value / the child markers.
            this._mo = new MutationObserver(() => {
                this.readValue()
                this.readLayers()
                this.schedule()
            })
            this._mo.observe(this.$el, { attributes: true, attributeFilter: ['data-value'] })
            this._mo.observe(this.$refs.slot, { attributes: true, childList: true, subtree: true })

            // Light / dark switches change the computed colors.
            this._offTheme = onThemeChange(() => {
                this.readLayers()
                this.schedule()
            })

            this._ro = new ResizeObserver(() => this.schedule())
            this._ro.observe(this.$refs.plot)

            this.schedule()
        },

        destroy() {
            this._mo?.disconnect()
            this._offTheme?.()
            this._ro?.disconnect()
            // A render queued just before teardown (e.g. a wire:navigate away) would draw into a detached node.
            cancelAnimationFrame(this._raf)
        },

        readValue() {
            if (config.model) return
            this.rows = normalize(this.$el.dataset.value)
        },

        readLayers() {
            this.palette = [...this.$el.querySelectorAll(':scope > [data-chart-palette] > span')].map(
                (s) => getComputedStyle(s).color,
            )

            let colorIndex = 0
            this.layers = [...this.$refs.slot.querySelectorAll('[data-chart-layer]')].map((el) => {
                const opts = JSON.parse(el.dataset.options || '{}')
                const layer = { type: el.dataset.chartLayer, ...opts }

                if (['line', 'area', 'point', 'bar'].includes(layer.type)) {
                    layer.color = opts.colored ? getComputedStyle(el).color : null
                }
                if (layer.type === 'pie') {
                    layer.colors = [...el.querySelectorAll('[data-swatch]')].map((s) => getComputedStyle(s).color)
                }
                return layer
            })

            // A label on any layer names the field everywhere (legend + tooltip),
            // so an unlabeled area under a labeled line still reads "Received".
            const labels = {}
            for (const l of this.layers) if (l.field !== undefined && l.label) labels[l.field] ??= l.label
            for (const l of this.layers) if (l.field !== undefined && !l.label && labels[l.field]) l.label = labels[l.field]

            // Series without their own class share the palette, keyed by field so a
            // line + area + point on the same field all get the same color.
            const byField = {}
            for (const l of this.layers) {
                if (!['line', 'area', 'point', 'bar'].includes(l.type) || l.color) continue
                byField[l.field] ??= this.palette[colorIndex++ % this.palette.length] || 'currentColor'
                l.color = byField[l.field]
            }
        },

        schedule() {
            cancelAnimationFrame(this._raf)
            this._raf = requestAnimationFrame(() => this.render())
        },

        layer(type) {
            return this.layers.find((l) => l.type === type)
        },

        measure(text) {
            this._ctx ??= document.createElement('canvas').getContext('2d')
            this._ctx.font = `12px ${getComputedStyle(this.$el).fontFamily}`
            return this._ctx.measureText(String(text)).width
        },

        render() {
            const plot = this.$refs.plot
            this.width = plot.clientWidth
            this.height = plot.clientHeight
            this.hover = null
            this.$refs.overlay.innerHTML = ''
            this.$refs.overlayUnder.innerHTML = ''
            this.$refs.tooltip.style.display = 'none'

            if (!this.width || !this.height) return

            const svg = this.$refs.svg
            svg.setAttribute('viewBox', `0 0 ${this.width} ${this.height}`)
            this.$refs.overlay.setAttribute('viewBox', `0 0 ${this.width} ${this.height}`)
            this.$refs.overlayUnder.setAttribute('viewBox', `0 0 ${this.width} ${this.height}`)

            const pie = this.layer('pie')
            svg.innerHTML = pie ? this.renderPie(pie) : this.renderCartesian()
            this.renderLegend(pie)
        },

        /* ------------------------------------------------------------------ */
        /* Line / area / point / bar                                           */
        /* ------------------------------------------------------------------ */

        renderCartesian() {
            const W = this.width, H = this.height
            const rows = this.rows
            const n = rows.length
            const horizontal = !!config.horizontal
            const series = this.layers.filter((l) => ['line', 'area', 'point', 'bar'].includes(l.type))
            if (!n || !series.length) return ''

            const xAxis = this.layers.find((l) => l.type === 'axis' && l.axis === 'x')
            const yAxis = this.layers.find((l) => l.type === 'axis' && l.axis === 'y')
            const catAxis = horizontal ? yAxis : xAxis
            const valAxis = horizontal ? xAxis : yAxis
            const catField = catAxis?.field ?? xAxis?.field ?? yAxis?.field ?? null
            const fmtCat = formatter(catAxis?.format)
            const fmtVal = formatter(valAxis?.format)
            const bars = series.filter((s) => s.type === 'bar')
            const hasBars = bars.length > 0

            // Values + stacking (bars and areas stack separately).
            for (const s of series) s.vals = rows.map((r) => num(r[s.field]))
            const stack = (group) => {
                const pos = new Array(n).fill(0), neg = new Array(n).fill(0)
                for (const s of group) {
                    s.lo = []; s.hi = []
                    s.vals.forEach((v, i) => {
                        if (v === null) { s.lo[i] = s.hi[i] = null; return }
                        const base = v >= 0 ? pos : neg
                        s.lo[i] = base[i]
                        base[i] += v
                        s.hi[i] = base[i]
                    })
                }
            }
            if (config.stacked) {
                stack(bars)
                stack(series.filter((s) => s.type === 'area'))
            }
            for (const s of series) {
                if (s.hi) continue
                s.lo = s.vals.map((v) => (v === null ? null : 0))
                s.hi = s.vals
            }

            const all = series.flatMap((s) => [...s.hi, ...(s.type === 'line' || s.type === 'point' ? [] : s.lo)]).filter((v) => v !== null)
            let min = Math.min(...all), max = Math.max(...all)
            if (hasBars || series.some((s) => s.type === 'area')) {
                min = Math.min(0, min)
                max = Math.max(0, max)
            }
            if (valAxis?.min !== undefined && valAxis?.min !== null) min = Number(valAxis.min)
            if (valAxis?.max !== undefined && valAxis?.max !== null) max = Number(valAxis.max)

            // Margins.
            const hasAxes = !!(xAxis || yAxis)
            const showTicks = (a) => a && a.ticks !== false
            const catLabels = rows.map((r, i) => (catField ? r[catField] : i + 1))

            const valLen = horizontal ? W : H
            let ticks = niceTicks(min, max, Math.max(2, Math.floor(valLen / (horizontal ? 90 : 45))))
            if (valAxis?.min === undefined || valAxis?.min === null) min = ticks[0]
            if (valAxis?.max === undefined || valAxis?.max === null) max = ticks[ticks.length - 1]
            ticks = ticks.filter((t) => t >= min - 1e-9 && t <= max + 1e-9)

            const pointR = Math.max(0, ...series.filter((s) => s.type === 'point').map((s) => s.size ?? 4))
            const strokeW = Math.max(0, ...series.filter((s) => s.type === 'line').map((s) => s.width ?? 2))
            const edge = Math.max(pointR + 1, strokeW / 2 + 1)

            let left = edge, right = edge, top = edge, bottom = edge
            if (hasAxes) {
                top = Math.max(8, edge)
                right = Math.max(8, edge)
                if (showTicks(yAxis)) {
                    const labels = horizontal ? catLabels.map(fmtCat) : ticks.map(fmtVal)
                    left = Math.ceil(Math.max(...labels.map((l) => this.measure(l)))) + 12
                }
                if (showTicks(xAxis)) bottom = 26
            }

            const iw = Math.max(1, W - left - right)
            const ih = Math.max(1, H - top - bottom)
            const catLen = horizontal ? ih : iw
            const catStart = horizontal ? top : left
            const band = catLen / n

            // Category position (center of band for bars; spread for points).
            const catPos = (i) => (hasBars
                ? catStart + band * (i + 0.5)
                : n === 1 ? catStart + catLen / 2 : catStart + (i * catLen) / (n - 1))
            const scale = (v) => (max === min ? 0.5 : (v - min) / (max - min))
            const valPos = (v) => (horizontal ? left + scale(v) * iw : top + ih - scale(v) * ih)
            const xy = (i, v) => (horizontal ? [valPos(v), catPos(i)] : [catPos(i), valPos(v)])

            let out = `<defs>`
            series.forEach((s, k) => {
                if (s.type === 'area' && s.gradient !== false) {
                    out += `<linearGradient id="${this.id}-g${k}" x1="0" y1="0" x2="${horizontal ? 1 : 0}" y2="${horizontal ? 0 : 1}">`
                        + `<stop offset="0" stop-color="${s.color}" stop-opacity="${horizontal ? 0.05 : s.opacity ?? 0.3}"/>`
                        + `<stop offset="1" stop-color="${s.color}" stop-opacity="${horizontal ? s.opacity ?? 0.3 : 0.05}"/></linearGradient>`
                }
            })
            out += `</defs>`

            // Grid lines (perpendicular to each axis that asks for them).
            const valGrid = valAxis ? valAxis.grid !== false : false
            if (valGrid) {
                for (const t of ticks) {
                    const p = r2(valPos(t)) + 0.5
                    out += horizontal
                        ? `<line class="fc-grid" x1="${p}" x2="${p}" y1="${top}" y2="${top + ih}"/>`
                        : `<line class="fc-grid" x1="${left}" x2="${left + iw}" y1="${p}" y2="${p}"/>`
                }
            }
            if (catAxis?.grid === true) {
                for (let i = 0; i < n; i++) {
                    const p = r2(catPos(i)) + 0.5
                    out += horizontal
                        ? `<line class="fc-grid" x1="${left}" x2="${left + iw}" y1="${p}" y2="${p}"/>`
                        : `<line class="fc-grid" x1="${p}" x2="${p}" y1="${top}" y2="${top + ih}"/>`
                }
            }

            // Areas.
            series.forEach((s, k) => {
                if (s.type !== 'area') return
                const fill = s.gradient === false ? s.color : `url(#${this.id}-g${k})`
                const pts = rows.map((_, i) => [i, s.hi[i]])
                for (const run of segments(pts)) {
                    const upper = run.map(([i, v]) => xy(i, v))
                    const lower = run.map(([i]) => xy(i, s.lo[i] ?? Math.max(min, Math.min(0, max)))).reverse()
                    const upperPath = linePath(upper, s.curve ?? 'smooth')
                    const lowerPath = linePath(lower, s.curve ?? 'smooth').replace(/^M/, 'L')
                    out += `<path d="${upperPath}${lowerPath}Z" fill="${fill}" stroke="none"/>`
                }
            })

            // Bars: grouped side by side, or stacked in one column.
            if (hasBars) {
                const groupGap = band * (config.stacked ? 0.3 : 0.2)
                const slots = config.stacked ? 1 : bars.length
                const inner = slots > 1 ? Math.min(4, band * 0.05) : 0
                const bw = Math.max(1, (band - groupGap - inner * (slots - 1)) / slots)

                bars.forEach((s, k) => {
                    const slot = config.stacked ? 0 : k
                    const isTop = (i) => !config.stacked || !bars.slice(k + 1).some((o) => o.vals[i] !== null && Math.sign(o.vals[i] || 0) === Math.sign(s.vals[i] || 0) && o.vals[i] !== 0)
                    s.vals.forEach((v, i) => {
                        if (v === null || v === 0) return
                        const c0 = catStart + band * i + groupGap / 2 + slot * (bw + inner)
                        const a = valPos(s.lo[i]), b = valPos(s.hi[i])
                        const lo = Math.min(a, b), len = Math.abs(b - a)
                        const radius = isTop(i) ? (s.radius ?? 4) : 0
                        const end = horizontal ? (v >= 0 ? 'right' : 'left') : (v >= 0 ? 'top' : 'bottom')
                        const gapEdge = config.stacked && s.lo[i] !== 0 ? 1 : 0
                        out += horizontal
                            ? `<path d="${barPath(lo + (v >= 0 ? gapEdge : 0), c0, Math.max(0, len - gapEdge), bw, radius, end)}" fill="${s.color}"/>`
                            : `<path d="${barPath(c0, lo + (v < 0 ? gapEdge : 0), bw, Math.max(0, len - gapEdge), radius, end)}" fill="${s.color}"/>`
                    })
                })
            }

            // Lines.
            for (const s of series) {
                if (s.type !== 'line') continue
                const pts = rows.map((_, i) => [i, s.hi[i]])
                for (const run of segments(pts)) {
                    const d = linePath(run.map(([i, v]) => xy(i, v)), s.curve ?? 'smooth')
                    out += `<path d="${d}" fill="none" stroke="${s.color}" stroke-width="${s.width ?? 2}" stroke-linejoin="round" stroke-linecap="round"${s.dashed ? ' stroke-dasharray="4 4"' : ''}/>`
                }
            }

            // Points.
            for (const s of series) {
                if (s.type !== 'point') continue
                s.hi.forEach((v, i) => {
                    if (v === null) return
                    const [x, y] = xy(i, v)
                    out += `<circle cx="${r2(x)}" cy="${r2(y)}" r="${s.size ?? 4}" fill="${s.color}"/>`
                })
            }

            // Axis lines.
            if (catAxis && catAxis.line !== false) {
                const p = r2(valPos(Math.max(min, Math.min(0, max)))) + 0.5
                out += horizontal
                    ? `<line class="fc-axis" x1="${p}" x2="${p}" y1="${top}" y2="${top + ih}"/>`
                    : `<line class="fc-axis" x1="${left}" x2="${left + iw}" y1="${p}" y2="${p}"/>`
            }
            if (valAxis?.line === true) {
                out += horizontal
                    ? `<line class="fc-axis" x1="${left}" x2="${left + iw}" y1="${top + ih + 0.5}" y2="${top + ih + 0.5}"/>`
                    : `<line class="fc-axis" x1="${left + 0.5}" x2="${left + 0.5}" y1="${top}" y2="${top + ih}"/>`
            }

            // Value tick labels.
            if (showTicks(valAxis)) {
                for (const t of ticks) {
                    const p = valPos(t)
                    out += horizontal
                        ? `<text class="fc-tick" x="${r2(p)}" y="${H - 8}" text-anchor="middle">${esc(fmtVal(t))}</text>`
                        : `<text class="fc-tick" x="${left - 8}" y="${r2(p)}" dy="0.32em" text-anchor="end">${esc(fmtVal(t))}</text>`
                }
            }

            // Category tick labels, thinned out so they never overlap.
            if (showTicks(catAxis)) {
                const labels = catLabels.map(fmtCat)
                const size = horizontal ? 16 : Math.max(...labels.map((l) => this.measure(l))) + 16
                const every = Math.max(1, Math.ceil(n / Math.max(1, Math.floor(catLen / size))))
                labels.forEach((label, i) => {
                    if (i % every !== 0) return
                    const p = catPos(i)
                    if (horizontal) {
                        out += `<text class="fc-tick" x="${left - 8}" y="${r2(p)}" dy="0.32em" text-anchor="end">${esc(label)}</text>`
                    } else {
                        const w = this.measure(label)
                        const anchor = p - w / 2 < 0 ? 'start' : p + w / 2 > W ? 'end' : 'middle'
                        const x = anchor === 'start' ? Math.max(0, p - 4) : anchor === 'end' ? Math.min(W, p + 4) : p
                        out += `<text class="fc-tick" x="${r2(x)}" y="${H - 8}" text-anchor="${anchor}">${esc(label)}</text>`
                    }
                })
            }

            this.geo = { kind: 'cartesian', n, horizontal, hasBars, band, catStart, catLen, catPos, xy, left, top, iw, ih, series, catField, fmtCat, fmtVal }
            return out
        },

        /* ------------------------------------------------------------------ */
        /* Pie / donut / ring                                                   */
        /* ------------------------------------------------------------------ */

        renderPie(p) {
            const W = this.width, H = this.height
            const labelField = p.labelField ?? 'label'
            const values = this.rows.map((r) => Math.max(0, num(r[p.field ?? 'value']) ?? 0))
            const total = values.reduce((a, b) => a + b, 0)
            const colors = p.colors?.length ? p.colors : this.palette
            const r1 = Math.max(1, Math.min(W, H) / 2 - 4)
            const r0 = r1 * (p.inner ?? 0)
            const cx = W / 2, cy = H / 2
            const gap = p.gap ?? (p.inner > 0 ? 3 : 2)

            let out = ''
            if (p.track || !total) {
                out += `<path class="fc-track" d="${arcPath(cx, cy, r0, r1, 0, Math.PI * 2, 0)}" fill-rule="evenodd"/>`
            }

            const slices = []
            let a = 0
            const sweep = Math.PI * 2 * (p.max ? Math.min(1, total / p.max) : 1)
            values.forEach((v, i) => {
                if (!v) return
                const span = (v / (p.max || total)) * Math.PI * 2
                const s = { i, a0: a, a1: Math.min(a + span, sweep), color: colors[i % colors.length] || 'currentColor' }
                slices.push(s)
                a += span
            })
            const gapPx = slices.length > 1 ? gap : 0

            for (const s of slices) {
                out += `<path data-slice="${s.i}" d="${arcPath(cx, cy, r0, r1, s.a0, s.a1, gapPx)}" fill="${s.color}" fill-rule="evenodd"/>`
            }

            this.geo = {
                kind: 'pie', cx, cy, r0, r1, slices, total: p.max || total, values,
                label: (i) => this.rows[i]?.[labelField] ?? `#${i + 1}`,
                fmt: formatter(p.format), gapPx,
            }
            return out
        },

        /* ------------------------------------------------------------------ */
        /* Hover: cursor, highlight, tooltip                                    */
        /* ------------------------------------------------------------------ */

        onMove(e) {
            const g = this.geo
            if (!g) return
            const rect = this.$refs.plot.getBoundingClientRect()
            const mx = e.clientX - rect.left, my = e.clientY - rect.top

            if (g.kind === 'pie') return this.hoverPie(mx, my)

            const along = g.horizontal ? my : mx
            let i
            if (g.hasBars) i = Math.floor((along - g.catStart) / g.band)
            else i = g.n === 1 ? 0 : Math.round(((along - g.catStart) / g.catLen) * (g.n - 1))
            if (i < 0 || i >= g.n) return this.onLeave()
            if (i === this.hover) return this.placeTooltip(mx, my)
            this.hover = i

            const cursor = this.layer('cursor')
            let out = '', under = ''
            if (cursor && g.hasBars) {
                under += g.horizontal
                    ? `<rect class="fc-band" x="${g.left}" y="${r2(g.catStart + g.band * i)}" width="${g.iw}" height="${r2(g.band)}"/>`
                    : `<rect class="fc-band" x="${r2(g.catStart + g.band * i)}" y="${g.top}" width="${r2(g.band)}" height="${g.ih}"/>`
            } else if (cursor) {
                const p = r2(g.catPos(i)) + 0.5
                out += g.horizontal
                    ? `<line class="fc-cursor" x1="${g.left}" x2="${g.left + g.iw}" y1="${p}" y2="${p}"/>`
                    : `<line class="fc-cursor" x1="${p}" x2="${p}" y1="${g.top}" y2="${g.top + g.ih}"/>`
            }
            for (const s of g.series) {
                if (!['line', 'area', 'point'].includes(s.type) || s.hi[i] === null) continue
                if (s.type === 'area' && g.series.some((o) => o.type === 'line' && o.field === s.field)) continue
                const [x, y] = g.xy(i, s.hi[i])
                out += `<circle class="fc-dot" cx="${r2(x)}" cy="${r2(y)}" r="${(s.size ?? 4) + 0.5}" fill="${s.color}"/>`
            }
            // The band sits behind the bars so it reads as a highlight, not a veil.
            this.$refs.overlayUnder.innerHTML = under
            this.$refs.overlay.innerHTML = out

            const tip = this.layer('tooltip')
            if (!tip) return
            const row = this.rows[i]
            const heading = tip.field
                ? formatter(tip.format ?? null)(row?.[tip.field])
                : tip.format ? formatter(tip.format)(row?.[g.catField]) : g.fmtCat(g.catField ? row?.[g.catField] : i + 1)
            const fmtVal = tip.valueFormat ? formatter(tip.valueFormat) : g.fmtVal

            const seen = new Set()
            const items = g.series.filter((s) => {
                if (seen.has(s.field)) return false
                seen.add(s.field)
                return true
            })
            this.showTooltip(heading, items.map((s) => ({ color: s.color, label: s.label ?? s.field, value: s.vals[i] === null ? '—' : fmtVal(s.vals[i]) })), mx, my)
        },

        hoverPie(mx, my) {
            const g = this.geo
            const dx = mx - g.cx, dy = my - g.cy
            const dist = Math.hypot(dx, dy)
            let ang = Math.atan2(dx, -dy)
            if (ang < 0) ang += Math.PI * 2
            const slice = dist <= g.r1 + 4 && dist >= g.r0 - 2 ? g.slices.find((s) => ang >= s.a0 && ang < s.a1) : null
            if (!slice) return this.onLeave()
            if (slice.i === this.hover) return this.placeTooltip(mx, my)
            this.hover = slice.i

            this.$refs.svg.querySelectorAll('[data-slice]').forEach((el) => {
                el.style.opacity = el.dataset.slice === String(slice.i) ? '1' : '0.45'
            })
            this.$refs.overlay.innerHTML = `<path d="${arcPath(g.cx, g.cy, g.r0, g.r1 + 4, slice.a0, slice.a1, g.gapPx)}" fill="${slice.color}" fill-rule="evenodd"/>`

            if (!this.layer('tooltip')) return
            const v = g.values[slice.i]
            const pct = g.total ? `${Math.round((v / g.total) * 1000) / 10}%` : ''
            this.showTooltip(null, [{ color: slice.color, label: g.label(slice.i), value: `${g.fmt(v)} <span class="fc-tip-muted">(${pct})</span>`, raw: true }], mx, my)
        },

        showTooltip(heading, items, mx, my) {
            const tip = this.$refs.tooltip
            tip.innerHTML = (heading !== null && heading !== undefined ? `<div class="fc-tip-heading">${esc(heading)}</div>` : '')
                + items.map((it) => `<div class="fc-tip-row"><span class="fc-tip-dot" style="background:${it.color}"></span>`
                    + `<span class="fc-tip-label">${esc(it.label)}</span><span class="fc-tip-value">${it.raw ? it.value : esc(it.value)}</span></div>`).join('')
            tip.style.display = 'block'
            this.placeTooltip(mx, my)
        },

        placeTooltip(mx, my) {
            const tip = this.$refs.tooltip
            if (tip.style.display === 'none') return
            const pad = 12
            let x = mx + pad, y = my + pad
            if (x + tip.offsetWidth > this.width) x = mx - tip.offsetWidth - pad
            if (y + tip.offsetHeight > this.height) y = Math.max(0, my - tip.offsetHeight - pad)
            tip.style.transform = `translate(${Math.max(0, x)}px, ${y}px)`
        },

        onLeave() {
            this.hover = null
            this.$refs.overlay.innerHTML = ''
            this.$refs.overlayUnder.innerHTML = ''
            this.$refs.tooltip.style.display = 'none'
            this.$refs.svg.querySelectorAll('[data-slice]').forEach((el) => (el.style.opacity = ''))
        },

        /* ------------------------------------------------------------------ */
        /* Legend                                                               */
        /* ------------------------------------------------------------------ */

        renderLegend(pie) {
            const legend = this.layer('legend')
            const el = this.$refs.legend
            if (!legend) {
                el.innerHTML = ''
                el.style.display = 'none'
                return
            }

            let items
            if (pie) {
                const g = this.geo
                items = this.rows.map((_, i) => ({
                    color: (pie.colors?.length ? pie.colors : this.palette)[i % (pie.colors?.length || this.palette.length)],
                    label: g.label(i),
                    value: legend.values ? g.fmt(g.values[i]) : null,
                }))
            } else {
                const seen = new Set()
                items = this.layers
                    .filter((l) => ['line', 'area', 'point', 'bar'].includes(l.type) && !seen.has(l.field) && seen.add(l.field))
                    .map((l) => ({ color: l.color, label: l.label ?? l.field }))
            }

            el.style.display = 'flex'
            el.innerHTML = items.map((it) => `<span class="fc-legend-item"><span class="fc-tip-dot" style="background:${it.color}"></span>`
                + `<span>${esc(it.label)}</span>${it.value !== null && it.value !== undefined ? `<span class="fc-legend-value">${esc(it.value)}</span>` : ''}</span>`).join('')
        },
    }
}

export function registerChart(Alpine) {
    Alpine.data('fluxChart', chart)
}

