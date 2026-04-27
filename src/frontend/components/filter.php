<?php

$state = $_POST['state'] ?? 'closed';
// the app is for MTL, so I will hard set the timezone to EST, but this will need ot be changed to dynamic if this is for other cities in the future
$defaultDateTime = (new DateTime('now', new DateTimeZone('America/Montreal')))->format('Y-m-d\TH:i');

if ($state === 'open'): ?>

<div class="filter-menu">
    <label class="filter-option">
        <input class="filter-checkbox peer" type="checkbox" name="filter[]" value="indoor">
        <span class="filter-label">Indoor</span>
    </label>

    <label class="filter-option">
        <input class="filter-checkbox peer" type="checkbox" name="filter[]" value="outdoor">
        <span class="filter-label">Outdoor</span>
    </label>

    <label class="filter-option">
        <input class="filter-checkbox peer" type="checkbox" name="filter[]" value="splash-pad">
        <span class="filter-label">Splash Pad</span>
    </label>

    <label class="filter-option">
        <input class="filter-checkbox peer" type="checkbox" name="filter[]" value="wading-pool">
        <span class="filter-label">Wading Pool</span>
    </label>
</div>

<div id="dateTimeContainer">
    <input type="datetime-local" name="date_time_picker" id="date_time_picker"
        value="<?= htmlspecialchars($defaultDateTime, ENT_QUOTES, 'UTF-8') ?>">
</div>

<div id="distance-slider">
    // Distance slider will go here
</div>


<?php endif;