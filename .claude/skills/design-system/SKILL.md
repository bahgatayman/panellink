---
name: design-system
description: Link Space Panel brand palette and UI conventions. Use whenever building, editing, or restyling any Blade view, layout, or component so colors, buttons, cards, badges, and spacing stay consistent system-wide.
---

# Link Space Panel — Design System

The full reference lives in **`docs/DESIGN_SYSTEM.md`** — read it when you need component
recipes or the complete ramps. The essentials:

## Source of truth
The theme is defined once in **`resources/views/partials/theme.blade.php`** (Tailwind CDN config)
and `@include`d in every layout `<head>`. To change colors, edit that partial. **Never re-hardcode
hex values in a view** — use token classes.

## Brand palette (60-30-10)
- **Primary `#163c85` → `brand-600`** (60%): buttons, links, active nav, sidebars.
- **Secondary `#6f96d1` → `brand-400`** (30%): soft accents, highlights on dark, gradient tails.
- **Neutral `#e6e7e8` → `surface-200`** (10%): borders, dividers, muted backgrounds.

`brand-*` ramp: 50 `#eef3fb` · 100 `#d7e3f4` · 200 `#b0c6e6` · 300 `#88a6d8` · 400 `#6f96d1`
· 500 `#3f68af` · 600 `#163c85` · 700 `#123068` · 800 `#0d244e` · 900 `#081833`.

## Rules
1. **New markup uses `brand-*` / `surface-*` tokens.** (Legacy `blue/indigo/violet/purple`
   classes are auto-remapped to the brand ramp, so old views already look correct — but write new
   code with `brand-*` for clarity.)
2. **Semantic status colors stay literal:** `green` = success, `yellow/amber` = warning,
   `red` = danger. Never remap or brand these.
3. **Red is the admin role marker.** In the admin back-office keep the red "Admin" pill, red
   active-nav border, and red logout. Everywhere else, red means danger only.
4. **Buttons:** `bg-brand-600 hover:bg-brand-700 text-white rounded-lg px-4 py-2.5 font-medium shadow-sm`.
5. **Cards:** `bg-white rounded-xl shadow-sm border border-gray-100 p-6`.
6. **Inputs focus:** `focus:ring-2 focus:ring-brand-500`.
7. **Radius:** `rounded-lg` controls, `rounded-xl` cards, `rounded-2xl` modals/hero.
8. **RTL + i18n:** branch on `$isRtl` for directional classes; wrap all strings in `__('app.*')`
   (en + ar).

After changing shared theme or layouts, run `php artisan view:clear` (CDN needs no build step).
