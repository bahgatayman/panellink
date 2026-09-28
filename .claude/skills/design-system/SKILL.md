---
name: design-system
description: Link Space Panel design system ("Premium Business Operations — Refined") with Light/Dark themes. Use whenever building, editing, or restyling any Owner Blade view, layout, or component so tokens, components, dark mode, RTL and spacing stay consistent.
---

# Link Space Panel — Design System

Approved direction: **Premium Business Operations — Refined** (visual reference:
`resources/design-playground/`, "Refined" concept). Stripe-level clarity, friendly voice.

## Source of truth (Owner app)
- **Tokens + components CSS:** `public/css/panel.css` (static, no build). Semantic tokens only:
  `--color-bg, --color-surface, --color-surface-subtle, --color-surface-elevated, --color-border(-subtle/-strong),
  --color-text, --color-text-secondary, --color-text-muted, --color-primary(-hover), --color-accent(-soft),
  --color-success|warning|danger|info(-soft)`, `--shadow-*`. Light values on `:root`, dark on `html[data-theme="dark"]`.
- **Runtime:** `public/js/panel.js` → `LS.toast`, `LS.open/close` (modals, drawers; focus trap + Esc + focus return),
  `LS.busy`, `LS.theme`, live timers (`data-ls-since/until`), client search (`data-ls-search`).
- **Blade components:** `resources/views/components/ui/*` — `x-ui.button`, `badge`, `avatar`, `card`, `page-header`,
  `empty-state`, `illustration`, `modal` (`drawer` prop), `flash`, `banner`, `money`, `search`, `input`, `icon`.
- **Layout:** `resources/views/layouts/app.blade.php` (sidebar groups, top bar with theme menu + language, sheet).
- Legacy Tailwind CDN config still lives in `resources/views/partials/theme.blade.php` for unmigrated views.

## Rules
1. **Compose components, don't restyle per page.** New screens use `x-ui.*` + `ls-*` classes.
2. **Never hard-code colours** in views. Use tokens (`var(--color-…)`) so Light and Dark both work.
3. **Soft navy `#2e4f8f` = primary action** (`#436ec9` in dark) — a calmer evolution of brand `#163c85`. One primary action per screen;
   label it with the outcome ("Collect EGP 1,948", "Create booking"), never "Submit/Confirm".
   Repeated per-card/per-row main actions use `variant="tonal"` (soft fill) so a grid never becomes a wall of solid blue;
   secondary = neutral bordered, ghost/icon = minimal.
4. **Status colours carry meaning only:** success · warning · danger · info · neutral (`x-ui.badge tone=…`).
   Normal states (e.g. "Live") use the quiet `.ls-status` dot + text; badges are for states that need attention.
   Group with spacing (`--space-*` scale) and hairlines before reaching for borders, fills or shadows.
5. **Money:** `x-ui.money`, tabular figures, right/end-aligned in tables (`.is-money`).
6. **Destructive actions** use `danger-quiet`/`danger` and sit apart from the primary action.
7. **RTL:** logical properties only (`margin-inline-start`, `inset-inline-end`…); isolate LTR values
   (times, phone numbers) with `<bdi dir="ltr">`. Every string via `__('app.*')` in en + ar.
8. **Dark mode = Link Space at night, not "everything grey".** Automatic if you use tokens. Three blue-grey levels
   (`--color-bg` canvas → `--color-sheet` content → `--color-surface` cards → `--color-surface-elevated` overlays;
   inputs use the recessed `--color-field`). Identity comes from the brand blues: primary actions, active nav
   (indicator bar), selection, links, focus, key metrics. Test both themes; nothing renders bright white.
   Colours that must stay visible get *lighter* in dark (primary `#436ec9`, text-on-blue `#aac6f8`), never darker.
11. **Semantic colour roles (both themes):** `--color-surface-brand` (bill summaries/selected areas),
    `--color-revenue` (money earned — one headline figure, not every amount), `.ls-strip-value.is-brand|is-revenue|is-warn`
    (accent a metric only when it should be read first), `--color-teal`/`--color-violet` (+`-soft`) for
    **categorisation only** (room kinds, types — never status), `--color-*-solid` + `--color-on-solid` for filled
    status buttons (AA with white text), `--color-chart` for single-series bars, `--color-meter` for progress.
    Avatars get a stable identity tint (blue/teal/violet) from `x-ui.avatar` automatically.
12. **Unmigrated views:** section 11 of `panel.css` bridges legacy Tailwind colours onto these tokens
    (tints, categorical hues, solids, gradients, focus rings, sub-AA grey/status text). Prefer tokens in new code.
9. **Touch:** controls grow to 44px on `pointer: coarse`; keep that when adding custom controls.
10. **Feedback:** success → toast (`x-ui.flash` or `LS.toast`), errors/warnings → `x-ui.banner`,
    empty → `x-ui.empty-state` with the next step, loading → `.ls-skel` / `LS.busy()`.
