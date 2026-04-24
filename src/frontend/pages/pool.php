<!doctype html>
<html class="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="assets/css/styles.css" rel="stylesheet" />
    <title>Pool Details</title>
</head>

<?php
include_once COMPONENTS_PATH . '/header.php';
include_once COMPONENTS_PATH . '/returnHome-link.php';

$allSchedules = null; // this will be the array of all schedules for the pool

//TODO - see ln 20 
?>

<!-- TODO: show all the pool info, then below a list of all of it's scheduled -->







<?php
if ($allSchedules === null) {
    echo ('<p>"Sorry, I couldn\'t find any schedules for this pool"</p>');
} else {
    foreach ($allSchedules as $schedule) {

        echo ('<div>');
        include_once COMPONENTS_PATH . '/pool-schedule-card.php';
        echo ('</div>');
    }
}
?>