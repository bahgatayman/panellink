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

{{--
    Scrollbars — brand-tinted so they read as part of the panel, not OS chrome.

    Default rules cover the light surfaces (main content, table scrollers, the
    notification dropdown): a brand-200 thumb that deepens through brand-400 on
    hover to brand-500 while dragging, over a transparent track.

    `.nav-scroll` is the inverse, for the dark brand-900 sidebars — brand-400 is
    the palette's designated highlight-on-dark token, so the thumb stays visible
    against the gradient without going stark white.
--}}
<style>
    * {
        scrollbar-width: thin;
        scrollbar-color: #b0c6e6 transparent; /* brand-200 */
    }

    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track,
    ::-webkit-scrollbar-corner { background: transparent; }

    /* The transparent border + background-clip give the thumb breathing room
       inside the track instead of filling the full 10px gutter. */
    ::-webkit-scrollbar-thumb {
        background-color: #b0c6e6;            /* brand-200 */
        border-radius: 9999px;
        border: 2px solid transparent;
        background-clip: content-box;
    }
    ::-webkit-scrollbar-thumb:hover  { background-color: #6f96d1; } /* brand-400 */
    ::-webkit-scrollbar-thumb:active { background-color: #3f68af; } /* brand-500 */

    .nav-scroll { scrollbar-color: #6f96d1 transparent; }
    .nav-scroll::-webkit-scrollbar-thumb       { background-color: #6f96d1; } /* brand-400 */
    .nav-scroll::-webkit-scrollbar-thumb:hover { background-color: #88a6d8; } /* brand-300 */
</style>
