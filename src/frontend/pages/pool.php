<!doctype html>
<html class="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="<?php echo ASSETS_URL; ?>/css/styles.css" rel="stylesheet" />
    <title>Pool Details</title>
</head>

<?php
include_once COMPONENTS_PATH . '/header.php';
include_once COMPONENTS_PATH . '/returnHome-link.php';

$poolId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>


<body>
    <main class="pool-detail-shell">
        <section class="pool-detail-hero" aria-label="Pool overview">
            <div id="poolDetailStatus" class="pool-list-status is-loading" aria-live="polite">Loading pool details...</div>

            <div id="poolDetailContent" class="pool-detail-content is-hidden">
                <div class="pool-detail-top-row">
                    <div>
                        <h1 id="poolDetailName" class="pool-detail-title">Pool name</h1>
                        <p id="poolDetailAddress" class="pool-detail-address">Address unavailable</p>
                    </div>
                    <div id="poolDetailTypeBadges" class="pool-type-badges-row"></div>
                </div>

                <div class="pool-detail-media-wrap">
                    <img id="poolDetailImage" class="pool-detail-media" src="<?php echo ASSETS_URL; ?>/no-image-icon.png" alt="Pool image" loading="lazy">
                </div>

                <div class="pool-detail-meta-grid">
                    <p><span class="pool-detail-meta-label">Phone:</span> <span id="poolDetailPhone">Unavailable</span></p>
                    <p><span class="pool-detail-meta-label">Website:</span> <a id="poolDetailWebsite" href="#" target="_blank" rel="noopener noreferrer">Unavailable</a></p>
                    <p><span class="pool-detail-meta-label">Map:</span> <a id="poolDetailMap" href="#" target="_blank" rel="noopener noreferrer">Unavailable</a></p>
                    <p><span class="pool-detail-meta-label">Coordinates:</span> <span id="poolDetailCoordinates">Unavailable</span></p>
                </div>

                <div class="pool-card-chip-row">
                    <span class="pool-chip pool-chip-open">Time match: future feature</span>
                    <span class="pool-chip pool-chip-distance">Distance match: future feature</span>
                    <span class="pool-chip pool-chip-distance">Name match: future feature</span>
                </div>
            </div>
        </section>

        <section class="pool-schedules-shell" aria-label="Schedules preview">
            <div class="pool-list-header-row">
                <h2 class="pool-list-title">Schedule Preview</h2>
                <p class="pool-list-meta">Mock layout only for now</p>
            </div>

            <div class="pool-schedule-grid">
                <article class="pool-schedule-mock-card">
                    <h3 class="pool-card-title">Lane Swim</h3>
                    <p class="pool-card-address">Weekdays 06:00 - 09:00</p>
                    <p class="pool-list-meta">Placeholder card. Schedule API integration comes next.</p>
                </article>

                <article class="pool-schedule-mock-card">
                    <h3 class="pool-card-title">Open Swim</h3>
                    <p class="pool-card-address">Weekdays 16:00 - 20:00</p>
                    <p class="pool-list-meta">Placeholder card. Schedule API integration comes next.</p>
                </article>

                <article class="pool-schedule-mock-card">
                    <h3 class="pool-card-title">Family Session</h3>
                    <p class="pool-card-address">Weekends 10:00 - 13:00</p>
                    <p class="pool-list-meta">Placeholder card. Schedule API integration comes next.</p>
                </article>
            </div>
        </section>
    </main>
</body>

<script>
    const poolId = <?php echo $poolId; ?>;

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

    function setDetailStatus(message, stateClass) {
        const statusNode = document.getElementById('poolDetailStatus');
        statusNode.className = `pool-list-status ${stateClass}`;
        statusNode.textContent = message;
    }

    function renderDetail(pool) {
        const baseUrl = '<?php echo BASE_URL; ?>';
        const fallbackImage = `${baseUrl}/assets/no-image-icon.png`;

        document.getElementById('poolDetailName').textContent = pool.name || 'Unknown pool';
        document.getElementById('poolDetailAddress').textContent = pool.address || 'Address unavailable';
        document.getElementById('poolDetailPhone').textContent = pool.phone || 'Unavailable';

        const poolImageNode = document.getElementById('poolDetailImage');
        const imageUrl = pool.imageUrl && String(pool.imageUrl).trim() !== '' ? pool.imageUrl : fallbackImage;
        poolImageNode.src = imageUrl;
        poolImageNode.alt = `${pool.name || 'Pool'} image`;
        poolImageNode.onerror = function () {
            this.src = fallbackImage;
        };

        const websiteNode = document.getElementById('poolDetailWebsite');
        if (pool.website) {
            websiteNode.href = pool.website;
            websiteNode.textContent = pool.website;
        } else {
            websiteNode.removeAttribute('href');
            websiteNode.textContent = 'Unavailable';
        }

        const mapNode = document.getElementById('poolDetailMap');
        if (pool.map) {
            mapNode.href = pool.map;
            mapNode.textContent = 'Open map';
        } else {
            mapNode.removeAttribute('href');
            mapNode.textContent = 'Unavailable';
        }

        const coordinatesNode = document.getElementById('poolDetailCoordinates');
        if (typeof pool.latitude === 'number' && typeof pool.longitude === 'number') {
            coordinatesNode.textContent = `${pool.latitude.toFixed(5)}, ${pool.longitude.toFixed(5)}`;
        } else {
            coordinatesNode.textContent = 'Unavailable';
        }

        const badgeContainer = document.getElementById('poolDetailTypeBadges');
        if (!Array.isArray(pool.types) || pool.types.length === 0) {
            badgeContainer.innerHTML = '<span class="pool-type-badge">Type unavailable</span>';
        } else {
            badgeContainer.innerHTML = pool.types
                .map((type) => `<span class="pool-type-badge">${escapeHtml(type.name || type.description || 'Pool')}</span>`)
                .join('');
        }

        document.getElementById('poolDetailContent').classList.remove('is-hidden');
        setDetailStatus('', 'is-hidden');
    }

    async function loadPoolDetail() {
        if (!Number.isInteger(poolId) || poolId <= 0) {
            setDetailStatus('Invalid pool id. Please return to the home page and choose a pool again.', 'is-error');
            return;
        }

        try {
            const response = await fetch('<?php echo API_URL; ?>/pools/' + encodeURIComponent(poolId));
            if (!response.ok) {
                throw new Error('Pool detail request failed.');
            }

            const payload = await response.json();
            renderDetail(payload);
        } catch (error) {
            setDetailStatus('Could not load this pool right now. Please go back and try again.', 'is-error');
        }
    }

    document.addEventListener('DOMContentLoaded', loadPoolDetail);
</script>

</html>