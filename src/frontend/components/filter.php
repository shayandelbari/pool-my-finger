<?php

$state = $_POST['state'] ?? 'closed';

// If open then show menu
if ($state === "open") {
    echo '<div>
            <label>
                <input type="checkbox" name="filter_adult" value="adult">
                Adult
            </label><br>

            <label>
                <input type="checkbox" name="filter_all_ages" value="all">
                All Ages
            </label>
        </div>'
    ;
} else {
    // If closed then return empty which should hide menue
    echo '';
}