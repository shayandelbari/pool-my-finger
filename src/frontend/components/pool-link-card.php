<?php

if (!function_exists('renderPoolLinkCard')) {
    /**
     * @param array<string, mixed> $pool
     */
    function renderPoolLinkCard(array $pool): string
    {
        $poolId = isset($pool['id']) ? (int) $pool['id'] : 0;
        $name = isset($pool['name']) ? (string) $pool['name'] : 'Unknown pool';
        $address = isset($pool['address']) && trim((string) $pool['address']) !== ''
            ? (string) $pool['address']
            : 'Address unavailable';
        $imageUrl = isset($pool['imageUrl']) && trim((string) $pool['imageUrl']) !== ''
            ? (string) $pool['imageUrl']
            : ASSETS_URL . '/no-image-icon.png';
        $href = BASE_URL . '/pool/' . rawurlencode((string) $poolId);

        return '
            <article class="pool-card">
                <a class="pool-card-link" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">
                    <div class="pool-card-media-wrap">
                        <img
                            class="pool-card-media"
                            src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '"
                            alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"
                            loading="lazy"
                            onerror="this.src=\'' . htmlspecialchars(ASSETS_URL . '/no-image-icon.png', ENT_QUOTES, 'UTF-8') . '\';"
                        >
                    </div>

                    <div class="pool-card-content">
                        <h3 class="pool-card-title">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h3>
                        <p class="pool-card-address">' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '</p>

                        <div class="pool-card-chip-row">
                            <span class="pool-chip pool-chip-open">Open now: TBD</span>
                            <span class="pool-chip pool-chip-distance">Distance: TBD</span>
                        </div>
                    </div>
                </a>
            </article>
        ';
    }
}