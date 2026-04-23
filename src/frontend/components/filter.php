<?php

$state = $_POST['state'] ?? 'closed';

// If open then show menu
if ($state === "open") {
    echo '<div>
            <label>
                <input type="checkbox" name="menu_choice" value="adult">
                Adult
            </label><br>

            <label>
                <input type="checkbox" name="menu_choice" value="all">
                All Ages
            </label>
        </div>'
    ;
} else {
    // If closed → return empty (hide menu)
    echo '';
}