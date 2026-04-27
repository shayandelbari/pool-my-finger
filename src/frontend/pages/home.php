<!doctype html>
<html class="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="<?php echo ASSETS_URL; ?>/css/styles.css" rel="stylesheet" />
    <title>Home</title>
</head>

<?php
include COMPONENTS_PATH . '/header.php';

$filterMenu = false;
?>

<body>
    <main class="home-shell">
        <section class="home-hero">
            <img class="home-icon" src="<?php echo ASSETS_URL; ?>/PMF_header_accent1.png"
                alt="Pool My Finger Logo - Full">
        </section>

        <section class="home-controls-shell" aria-label="Search and filters">
            <div class="home-controls-row">
                <input type="text" placeholder="Postal Code or Pool Name" name="search_bar">

                <button type="button" name="filter" class="filter-btn">
                    <img src="<?php echo ASSETS_URL; ?>/filter.png" alt="FILTER" class="filter-btn-icon">
                </button>
            </div>
            <div id="menuContainer"></div>
        </section>

        <section class="pool-list-section" aria-label="Pools list">
            <div class="pool-list-header-row">
                <h2 class="pool-list-title">Pools</h2>
                <p id="poolListMeta" class="pool-list-meta">Loading pools...</p>
            </div>

            <div id="poolListStatus" class="pool-list-status" aria-live="polite">Loading pools...</div>
            <div id="poolCardsGrid" class="pool-card-grid" aria-live="polite"></div>
            <div id="poolPagination" class="pool-pagination" aria-label="Pool pagination"></div>
        </section>
    </main>
</body>

<script>
let mainFilterOpen = false;
const CARDS_PER_PAGE = 10;
let allPools = [];
let currentPage = 1;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (char) => {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return map[char] || char;
    });
}

function poolTypeBadges(types) {
    if (!Array.isArray(types) || types.length === 0) {
        return '<span class="pool-type-badge">Type unavailable</span>';
    }

    return types
        .map((type) => `<span class="pool-type-badge">${escapeHtml(type.name || type.description || 'Pool')}</span>`)
        .join('');
}

function poolCardTemplate(pool) {
    const baseUrl = '<?php echo BASE_URL; ?>';
    const fallbackImage = `${baseUrl}/assets/no-image-icon.png`;
    const imageUrl = (pool.imageUrl && pool.imageUrl.trim() !== '') ?
        pool.imageUrl :
        fallbackImage;

    return `
            <article class="pool-card">
                <a class="pool-card-link" href="${baseUrl}/pool/${encodeURIComponent(pool.id)}" aria-label="Open ${escapeHtml(pool.name)} details">
                    <div class="pool-card-media-wrap">
                        <img class="pool-card-media" src="${escapeHtml(imageUrl)}" alt="${escapeHtml(pool.name)}" loading="lazy" onerror="this.src='${escapeHtml(fallbackImage)}'" />
                    </div>

                    <div class="pool-card-content">
                        <h3 class="pool-card-title">${escapeHtml(pool.name || 'Unknown pool')}</h3>
                        <p class="pool-card-address">${escapeHtml(pool.address || 'Address unavailable')}</p>

                        <div class="pool-card-chip-row">
                            <span class="pool-chip pool-chip-open">Open now: TBD</span>
                            <span class="pool-chip pool-chip-distance">Distance: TBD</span>
                        </div>

                        <div class="pool-type-badges-row">
                            ${poolTypeBadges(pool.types)}
                        </div>
                    </div>
                </a>
            </article>
        `;
}

function setPoolListStatus(message, stateClass) {
    const statusNode = document.getElementById('poolListStatus');
    statusNode.className = `pool-list-status ${stateClass}`;
    statusNode.textContent = message;
}

function renderPools(pools) {
    const cardsGrid = document.getElementById('poolCardsGrid');
    const metaNode = document.getElementById('poolListMeta');

    if (pools.length === 0) {
        cardsGrid.innerHTML = '';
        metaNode.textContent = '0 pools available';
        setPoolListStatus('No pools are available right now.', 'is-empty');
        renderPagination(0, 0, 0);
        return;
    }

    const totalPages = Math.max(1, Math.ceil(pools.length / CARDS_PER_PAGE));
    const safePage = Math.min(Math.max(currentPage, 1), totalPages);
    const startIndex = (safePage - 1) * CARDS_PER_PAGE;
    const endIndex = Math.min(startIndex + CARDS_PER_PAGE, pools.length);
    const visiblePools = pools.slice(startIndex, endIndex);

    currentPage = safePage;
    cardsGrid.innerHTML = visiblePools.map(poolCardTemplate).join('');
    metaNode.textContent = `Showing ${startIndex + 1}-${endIndex} of ${pools.length} pools`;
    setPoolListStatus('', 'is-hidden');
    renderPagination(totalPages, startIndex + 1, endIndex);
}

function renderPagination(totalPages, fromCount, toCount) {
    const paginationNode = document.getElementById('poolPagination');

    if (totalPages <= 1) {
        paginationNode.innerHTML = '';
        paginationNode.className = 'pool-pagination is-hidden';
        return;
    }

    const pageItems = buildPaginationItems(totalPages, currentPage);
    const pageButtons = pageItems
        .map((item) => {
            if (item === 'ellipsis') {
                return '<span class="pool-pagination-ellipsis" aria-hidden="true">...</span>';
            }

            const activeClass = item === currentPage ? 'is-active' : '';
            const currentAttr = item === currentPage ? 'aria-current="page"' : '';
            return `<button type="button" class="pool-pagination-btn ${activeClass}" data-page="${item}" ${currentAttr}>${item}</button>`;
        })
        .join('');

    paginationNode.className = 'pool-pagination';
    paginationNode.innerHTML = `
            <p class="pool-pagination-meta">Showing ${fromCount}-${toCount} on page ${currentPage} of ${totalPages}</p>
            <div class="pool-pagination-controls">
                <button type="button" class="pool-pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>Previous</button>
                ${pageButtons}
                <button type="button" class="pool-pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
            </div>
        `;

    paginationNode.querySelectorAll('button[data-page]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextPage = Number(button.getAttribute('data-page'));
            if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage > totalPages || nextPage ===
                currentPage) {
                return;
            }

            currentPage = nextPage;
            renderPools(allPools);
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
}

function buildPaginationItems(totalPages, page) {
    if (totalPages <= 7) {
        return Array.from({
            length: totalPages
        }, (_, index) => index + 1);
    }

    if (page <= 4) {
        return [1, 2, 3, 4, 5, 'ellipsis', totalPages];
    }

    if (page >= totalPages - 3) {
        return [1, 'ellipsis', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
    }

    return [1, 'ellipsis', page - 1, page, page + 1, 'ellipsis', totalPages];
}

async function fetchPools() {
    setPoolListStatus('Loading pools...', 'is-loading');

    const response = await fetch('<?php echo API_URL; ?>/pools');

    if (!response.ok) {
        throw new Error(`Pool request failed with status ${response.status}`);
    }

    const payload = await response.json();
    return Array.isArray(payload.pools) ? payload.pools : [];
}

async function loadPools() {
    try {
        allPools = await fetchPools();
        currentPage = 1;
        renderPools(allPools);
    } catch (error) {
        document.getElementById('poolListMeta').textContent = 'Unable to load pools';
        document.getElementById('poolPagination').className = 'pool-pagination is-hidden';
        document.getElementById('poolPagination').innerHTML = '';
        setPoolListStatus('Could not fetch pools right now. Please refresh and try again.', 'is-error');
    }
}

async function toggleMainFilter() {
    mainFilterOpen = !mainFilterOpen;

    const response = await fetch('<?php echo BASE_URL; ?>/filter', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'state=' + (mainFilterOpen ? 'open' : 'closed')
    });

    const html = await response.text();
    document.getElementById('menuContainer').innerHTML = html;
}

function setPlaceholder(value) {
    document.querySelector('input[name="search_bar"]').placeholder = value;
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('button[name="filter"]').addEventListener('click', toggleMainFilter);
    loadPools();
});

document.addEventListener('input', function(e) {
    if (e.target.id === 'distance_range') {
        document.getElementById('distanceValue').textContent = e.target.value;
    }
});
</script>

</html>