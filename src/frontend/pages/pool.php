<!doctype html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="<?php echo ASSETS_URL; ?>/css/styles.css" rel="stylesheet" />
    <title>Pool Details</title>
    <script>
        (() => {
            try {
                const storedTheme = localStorage.getItem('pmf-theme');
                const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                const theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : preferredTheme;
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.dataset.theme = theme;
            } catch (error) {
                document.documentElement.classList.add('dark');
                document.documentElement.dataset.theme = 'dark';
            }
        })();
    </script>
</head>

<?php
include_once COMPONENTS_PATH . '/header.php';

$poolId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>


<body>
    <main class="pool-detail-shell">
        <div class="page-tools-row">
            <?php include_once COMPONENTS_PATH . '/returnHome-link.php'; ?>
            <?php include COMPONENTS_PATH . '/theme-toggle.php'; ?>
        </div>

        <section class="pool-detail-hero" aria-label="Pool overview">
            <div id="poolDetailStatus" class="pool-list-status is-loading" aria-live="polite">Loading pool details...</div>

            <div id="poolDetailContent" class="pool-detail-content is-hidden">
                <div class="pool-detail-media-wrap">
                    <img id="poolDetailImage" class="pool-detail-media" src="<?php echo ASSETS_URL; ?>/no-image-icon.png" alt="Pool image" loading="lazy">
                </div>

                <div class="pool-detail-body">
                    <div class="pool-detail-top-row">
                        <div>
                            <h1 id="poolDetailName" class="pool-detail-title">Pool name</h1>
                            <p id="poolDetailAddress" class="pool-detail-address">Address unavailable</p>
                        </div>
                        <div id="poolDetailTypeBadges" class="pool-type-badges-row"></div>
                    </div>

                    <div class="pool-detail-meta-grid">
                        <p class="pool-detail-meta-item"><span class="pool-detail-meta-label">Phone</span><span id="poolDetailPhone">Unavailable</span></p>
                        <p class="pool-detail-meta-item"><span class="pool-detail-meta-label">Website</span><a id="poolDetailWebsite" href="#" target="_blank" rel="noopener noreferrer">Unavailable</a></p>
                    </div>

                    <div class="pool-detail-map-shell">
                        <div class="pool-detail-map-frame">
                            <iframe
                                id="poolDetailMapEmbed"
                                class="pool-detail-map-embed"
                                src=""
                                title="Pool location map"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                            <div id="poolDetailMapFallback" class="pool-detail-map-fallback hidden">Map unavailable for this pool.</div>
                        </div>

                        <div class="pool-detail-action-row">
                            <a id="poolDetailDirections" class="pool-detail-action-btn" href="#" target="_blank" rel="noopener noreferrer">Get Directions</a>
                            <a id="poolDetailMap" class="pool-detail-action-btn is-secondary" href="#" target="_blank" rel="noopener noreferrer">Open Full Map</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pool-schedules-shell" aria-label="Schedules preview">
            <div class="pool-list-header-row">
                <h2 class="pool-list-title">Schedule Preview</h2>
                <p id="poolScheduleMeta" class="pool-list-meta">Loading schedules...</p>
            </div>

            <div id="poolScheduleStatus" class="pool-list-status is-loading" aria-live="polite">Loading schedules...</div>
            <div id="poolScheduleGrid" class="pool-schedule-grid" aria-live="polite"></div>
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

    function setScheduleStatus(message, stateClass) {
        const statusNode = document.getElementById('poolScheduleStatus');
        statusNode.className = `pool-list-status ${stateClass}`;
        statusNode.textContent = message;
    }

    function applyTheme(theme) {
        const isDark = theme === 'dark';
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', String(isDark));
            button.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            button.setAttribute('title', isDark ? 'Switch to light theme' : 'Switch to dark theme');
        });
    }

    function initializeThemeToggle() {
        const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        applyTheme(currentTheme);

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
                applyTheme(nextTheme);

                try {
                    localStorage.setItem('pmf-theme', nextTheme);
                } catch (error) {
                    // Ignore storage errors and keep the visual theme change.
                }
            });
        });
    }

    function normalizeExternalUrl(value) {
        const trimmed = String(value ?? '').trim();
        if (trimmed === '') {
            return null;
        }

        if (/^https?:\/\//i.test(trimmed)) {
            return trimmed;
        }

        return `https://${trimmed.replace(/^\/+/, '')}`;
    }

    function setExternalLink(node, url, label, unavailableLabel = 'Unavailable') {
        if (url) {
            node.href = url;
            node.textContent = label;
            node.setAttribute('target', '_blank');
            node.setAttribute('rel', 'noopener noreferrer');
            node.classList.remove('is-disabled');
            node.removeAttribute('aria-disabled');
            return;
        }

        node.removeAttribute('href');
        node.removeAttribute('target');
        node.removeAttribute('rel');
        node.textContent = unavailableLabel;
        node.classList.add('is-disabled');
        node.setAttribute('aria-disabled', 'true');
    }

    function buildMapQuery(pool) {
        if (pool.address && String(pool.address).trim() !== '') {
            return String(pool.address).trim();
        }

        if (typeof pool.latitude === 'number' && typeof pool.longitude === 'number') {
            return `${pool.latitude},${pool.longitude}`;
        }

        return '';
    }

    function buildMapEmbedUrl(pool) {
        const query = buildMapQuery(pool);
        if (query !== '') {
            return `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed`;
        }

        if (pool.map && String(pool.map).trim() !== '') {
            return `${String(pool.map).trim()}${String(pool.map).includes('?') ? '&' : '?'}output=embed`;
        }

        return null;
    }

    function buildDirectionsUrl(pool) {
        const query = buildMapQuery(pool);
        if (query === '') {
            return null;
        }

        return `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(query)}`;
    }

    function buildFullMapUrl(pool) {
        const query = buildMapQuery(pool);
        if (query !== '') {
            return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
        }

        if (pool.map && String(pool.map).trim() !== '') {
            return String(pool.map).trim();
        }

        return null;
    }

    function formatDate(dateValue) {
        const parsed = new Date(`${dateValue}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return dateValue || 'Date unavailable';
        }

        return parsed.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function formatTime(timeValue) {
        const parsed = new Date(`1970-01-01T${timeValue}`);
        if (Number.isNaN(parsed.getTime())) {
            return timeValue || 'Time unavailable';
        }

        return parsed.toLocaleTimeString(undefined, {
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function groupTimeBlocksByDay(timeBlocks) {
        return timeBlocks.reduce((groups, block) => {
            const day = block && block.day ? block.day : 'Day unavailable';
            const existingGroup = groups.find((group) => group.day === day);

            if (existingGroup) {
                existingGroup.blocks.push(block);
                return groups;
            }

            groups.push({
                day,
                blocks: [block]
            });
            return groups;
        }, []);
    }

    function scheduleCardTemplate(schedule) {
        const timeBlocks = Array.isArray(schedule.timeBlocks) ? schedule.timeBlocks : [];
        const groupedBlocks = groupTimeBlocksByDay(timeBlocks);
        const blocksMarkup = groupedBlocks.length > 0
            ? groupedBlocks.map((group) => `
                <li class="pool-schedule-block-item">
                    <span class="pool-schedule-block-day">${escapeHtml(group.day)}</span>
                    <div class="pool-schedule-time-slot-list">
                        ${group.blocks.map((block) => `
                            <div class="pool-schedule-time-slot">
                                <span class="pool-schedule-block-time">${escapeHtml(formatTime(block.start))} - ${escapeHtml(formatTime(block.end))}</span>
                                ${block.label ? `<span class="pool-schedule-block-label">${escapeHtml(block.label)}</span>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </li>
            `).join('')
            : '<li class="pool-schedule-block-item is-empty">No time blocks available.</li>';

        return `
            <article class="pool-schedule-card">
                <div class="pool-schedule-card-header">
                    <h3 class="pool-card-title">${escapeHtml(schedule.type?.name || 'Schedule')}</h3>
                    <p class="pool-list-meta">${escapeHtml(formatDate(schedule.effectiveDate))} - ${escapeHtml(formatDate(schedule.endDate))}</p>
                </div>
                <ul class="pool-schedule-block-list">
                    ${blocksMarkup}
                </ul>
            </article>
        `;
    }

    function renderSchedules(schedules) {
        const gridNode = document.getElementById('poolScheduleGrid');
        const metaNode = document.getElementById('poolScheduleMeta');

        if (!Array.isArray(schedules) || schedules.length === 0) {
            gridNode.innerHTML = '';
            metaNode.textContent = 'No schedules available';
            setScheduleStatus('No schedule data is available for this pool right now.', 'is-empty');
            return;
        }

        metaNode.textContent = `${schedules.length} schedule${schedules.length === 1 ? '' : 's'} available`;
        gridNode.innerHTML = schedules.map(scheduleCardTemplate).join('');
        setScheduleStatus('', 'is-hidden');
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
        setExternalLink(
            websiteNode,
            normalizeExternalUrl(pool.website),
            pool.website && String(pool.website).trim() !== '' ? String(pool.website).trim() : 'Visit Website'
        );

        const mapNode = document.getElementById('poolDetailMap');
        setExternalLink(mapNode, buildFullMapUrl(pool), 'Open Full Map');

        const directionsNode = document.getElementById('poolDetailDirections');
        setExternalLink(directionsNode, buildDirectionsUrl(pool), 'Get Directions');

        const mapEmbedNode = document.getElementById('poolDetailMapEmbed');
        const mapFallbackNode = document.getElementById('poolDetailMapFallback');
        const mapEmbedUrl = buildMapEmbedUrl(pool);
        if (mapEmbedUrl) {
            mapEmbedNode.src = mapEmbedUrl;
            mapEmbedNode.classList.remove('hidden');
            mapFallbackNode.classList.add('hidden');
        } else {
            mapEmbedNode.removeAttribute('src');
            mapEmbedNode.classList.add('hidden');
            mapFallbackNode.classList.remove('hidden');
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

    async function loadSchedules() {
        if (!Number.isInteger(poolId) || poolId <= 0) {
            setScheduleStatus('Invalid pool id. Unable to load schedules.', 'is-error');
            document.getElementById('poolScheduleMeta').textContent = 'Invalid pool';
            return;
        }

        try {
            const response = await fetch('<?php echo API_URL; ?>/pools/' + encodeURIComponent(poolId) + '/schedules');
            if (!response.ok) {
                throw new Error('Pool schedule request failed.');
            }

            const payload = await response.json();
            renderSchedules(Array.isArray(payload.schedules) ? payload.schedules : []);
        } catch (error) {
            document.getElementById('poolScheduleGrid').innerHTML = '';
            document.getElementById('poolScheduleMeta').textContent = 'Unable to load schedules';
            setScheduleStatus('Could not load schedule data right now. Please refresh and try again.', 'is-error');
        }
    }

    async function loadPoolDetail() {
        if (!Number.isInteger(poolId) || poolId <= 0) {
            setDetailStatus('Invalid pool id. Please return to the home page and choose a pool again.', 'is-error');
            setScheduleStatus('Invalid pool id. Unable to load schedules.', 'is-error');
            document.getElementById('poolScheduleMeta').textContent = 'Invalid pool';
            return;
        }

        try {
            const [poolResponse] = await Promise.all([
                fetch('<?php echo API_URL; ?>/pools/' + encodeURIComponent(poolId)),
                loadSchedules()
            ]);

            if (!poolResponse.ok) {
                throw new Error('Pool detail request failed.');
            }

            const payload = await poolResponse.json();
            renderDetail(payload);
        } catch (error) {
            setDetailStatus('Could not load this pool right now. Please go back and try again.', 'is-error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initializeThemeToggle();
        loadPoolDetail();
    });
</script>

</html>
