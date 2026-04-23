<?php

$state = $_POST['state'] ?? 'closed';

// If currently closed → open it
if ($state === "closed") {
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
    // If open → return empty (hide menu)
    echo '';
}