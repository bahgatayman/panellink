# Link Space Panel — Design System

Single source of truth for the visual language across the whole product (owner panel,
admin back-office, auth, landing, subscription pages). Both English (LTR) and Arabic (RTL).

The theme is defined once in **`resources/views/partials/theme.blade.php`** and included in the
`<head>` of every layout. Change colors there — never re-hardcode hex in a view.

---

## 1. Brand palette (60-30-10)

| Role | Hex | Token | Usage share |
|------|-----|-------|-------------|
| **Primary** | `#163c85` | `brand-600` | **60%** — buttons, links, active nav, sidebars, key headings |
| **Secondary** | `#6f96d1` | `brand-400` | **30%** — soft accents, highlights on dark, gradient tails, charts |
| **Neutral** | `#e6e7e8` | `surface-200` | **10%** — borders, dividers, muted backgrounds |

### Brand ramp (`brand-*`, anchored on the two brand blues)

```
50  #eef3fb   100 #d7e3f4   200 #b0c6e6   300 #88a6d8   400 #6f96d1 ←secondary
500 #3f68af   600 #163c85 ←primary   700 #123068   800 #0d244e   900 #081833
```

### Surface / neutral ramp (`surface-*`, anchored on the neutral gray)

```
50 #f7f8fa  100 #eef0f3  200 #e6e7e8 ←neutral  300 #d3d6da  400 #a6abb2
500 #787f88  600 #565d66  700 #3f454d  800 #282c33  900 #171a1f
```

### Semantic status colors (Tailwind defaults — do **not** remap)

| Meaning | Class family | Typical use |
|---------|-------------|-------------|
| Success | `green-*`  | active status, confirmed bookings, positive alerts |
| Warning | `yellow-* / amber-*` | expiring soon, near plan limit |
| Danger  | `red-*`   | expired, errors, destructive actions, **admin role marker** |
| Info    | `blue-*`  | (remapped to brand) informational notices |

> **Automatic re-skin:** the default `blue`, `indigo`, `violet`, and `purple` scales are
> remapped onto the brand ramp in the theme partial. Existing `blue-600`, `indigo-600`,
> `text-purple-600`, etc. across all views render as brand blue with no per-view edits.
> Prefer writing new markup with `brand-*` directly for clarity.

---

## 2. Panel identities

- **Owner panel** — brand navy sidebar (`from-brand-900 to-brand-700`), `brand-600` accents,
  `brand-500` active-nav border, page background `#f8fafc`.
- **Admin back-office** — same brand navy chrome for a unified system, **kept distinguishable by
  a red role marker**: the red "Admin" pill badge, `red-500` active-nav border, and red logout.
  Red here is intentional role signalling, not decoration.
- **Auth / landing / subscription** — brand-blue gradients (`#0d244e → #163c85 → #3f68af`),
  `brand-600` primary buttons, `surface-*` neutrals.

---

## 3. Component recipes

Copy these; they encode the agreed spacing, radius, and color tokens.

**Primary button**
```html
<button class="bg-brand-600 text-white px-4 py-2.5 rounded-lg hover:bg-brand-700 transition font-medium shadow-sm">…</button>
```

**Secondary / neutral button**
```html
<button class="bg-surface-200 text-surface-700 px-4 py-2 rounded-lg hover:bg-surface-300 transition text-sm">…</button>
```

**Card / panel**
```html
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">…</div>
```

**Text input**
```html
<input class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
```

**Status badge**
```html
<span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Active</span>
<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">Expiring</span>
<span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">Expired</span>
```

**Alert banners**
```html
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">…</div>  {{-- success --}}
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">…</div> {{-- warning --}}
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">…</div>       {{-- error --}}
```

**Active nav item (sidebar)**
```html
<a class="... {{ request()->is('x*') ? 'bg-white/10 border-l-4 border-brand-500' : '' }}">…</a>
```

---

## 4. Conventions

- **Radius:** `rounded-lg` (controls/inputs/badges), `rounded-xl` (cards), `rounded-2xl` (modals/hero).
- **Shadows:** `shadow-sm` for resting cards; deeper shadows only for overlays/hover.
- **Spacing:** page padding `p-4 lg:p-6`; card padding `p-6`; form field gap `mb-4`.
- **Typography:** `Inter` (loaded on auth/landing), system fallback elsewhere; RTL uses `Cairo`.
- **RTL:** never hardcode `left/right`; branch on `$isRtl` as existing layouts do.
- **i18n:** all user-facing strings go through `__('app.*')` (en + ar).

## 5. Known follow-ups
- Tailwind is still loaded from the **CDN** (go-live blocker). When migrating to the Vite build,
  port this same palette into `resources/css/app.css` via `@theme` and drop the CDN `<script>`.
