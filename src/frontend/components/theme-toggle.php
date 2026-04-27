<?php

echo <<<HTML
<button
    type="button"
    class="theme-toggle"
    data-theme-toggle
    aria-label="Toggle theme"
    aria-pressed="false"
    title="Toggle theme">
    <span class="theme-toggle-icon theme-toggle-icon-sun" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 2.5v2.5"></path>
            <path d="M12 19v2.5"></path>
            <path d="m4.93 4.93 1.77 1.77"></path>
            <path d="m17.3 17.3 1.77 1.77"></path>
            <path d="M2.5 12H5"></path>
            <path d="M19 12h2.5"></path>
            <path d="m4.93 19.07 1.77-1.77"></path>
            <path d="m17.3 6.7 1.77-1.77"></path>
        </svg>
    </span>
    <span class="theme-toggle-icon theme-toggle-icon-moon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"></path>
        </svg>
    </span>
</button>
HTML;
