<?php

$state = $_POST['state'] ?? 'closed';
// the app is for MTL, so I will hard set the timezone to EST, but this will need ot be changed to dynamic if this is for other cities in the future
$defaultDateTime = (new DateTime('now', new DateTimeZone('America/Montreal')))->format('Y-m-d\TH:i');

if ($state === 'open'): ?>

    <div>
        <label>
            <input type="checkbox" name="filter" value="indoor">
            Indoor
        </label><br>

        <label>
            <input type="checkbox" name="filter" value="outdoor">
            Outdoor
        </label><br>

        <label>
            <input type="checkbox" name="filter" value="splash-pad">
            Splash Pad
        </label>

        <label>
            <input type="checkbox" name="filter" value="wading-pool">
            Wading Pool
        </label>
    </div>
    <div id="dateTimeContainer">
        <input type="datetime-local" name="date_time_picker" id="date_time_picker"
            value="<?= htmlspecialchars($defaultDateTime, ENT_QUOTES, 'UTF-8') ?>">
    </div>

<?php endif;