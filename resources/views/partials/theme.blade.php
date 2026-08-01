{{--
    Link Space Panel — shared front-end theme (single source of truth).

    Loads Tailwind (CDN) and configures the brand palette. Included in the <head>
    of every layout so colors stay consistent system-wide.

    Palette (60-30-10):
        #163c85  primary  (60%)  → brand-600 : buttons, links, active nav, sidebars
        #6f96d1  secondary(30%)  → brand-400 : soft accents, highlights on dark
        #e6e7e8  neutral  (10%)  → surface-200 : borders, muted surfaces

    The default `blue / indigo / violet / purple` scales are remapped onto the
    brand ramp, so existing utility classes across all 44 views adopt the brand
    automatically — no per-view edits required. Semantic status colors
    (green/yellow/red/orange) and neutral text scales (gray/slate) are left
    untouched. See docs/DESIGN_SYSTEM.md for the full token reference.
--}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
    (function () {
        const brand = {
            50:  '#eef3fb',
            100: '#d7e3f4',
            200: '#b0c6e6',
            300: '#88a6d8',
            400: '#6f96d1', // secondary (30%)
            500: '#3f68af',
            600: '#163c85', // primary (60%)
            700: '#123068',
            800: '#0d244e',
            900: '#081833',
        };
        const surface = {
            50:  '#f7f8fa',
            100: '#eef0f3',
            200: '#e6e7e8', // neutral (10%)
            300: '#d3d6da',
            400: '#a6abb2',
            500: '#787f88',
            600: '#565d66',
            700: '#3f454d',
            800: '#282c33',
            900: '#171a1f',
        };
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Instrument Sans', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand,
                        surface,
                        // Remap the whole cool-accent family onto the brand ramp.
                        blue: brand,
                        indigo: brand,
                        violet: brand,
                        purple: brand,
                    },
                },
            },
        };
    })();
</script>
