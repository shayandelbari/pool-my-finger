<!doctype html>
<html class="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="assets/css/styles.css" rel="stylesheet" />

    <title>Home</title>
</head>


<?php
include COMPONENTS_PATH . '/header.php';

$filterMenu = false;
?>

<script>
    let mainFilterOpen = false;


    function toggleMainFilter() {
        mainFilterOpen = !mainFilterOpen;
        fetch('<?php echo BASE_URL; ?>/filter', {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "state=" + (mainFilterOpen ? "open" : "closed")
        })
            .then(res => res.text())
            .then(html => {
                document.getElementById("menuContainer").innerHTML = html;
            });
    }


    function setPlaceholder(value) {
        document.querySelector('input[name="search_bar"]').placeholder = value;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('button[name="filter"]').addEventListener('click', toggleMainFilter);
    });
</script>




<div>
    <input type="text" placeholder="Postal Code or Pool Name" name="search_bar">

    <button type="button" name="filter">FILTER</button>

    <div id="menuContainer"> </div>
</div>

<script>
    const now = new Date();

    const minutes = Math.round(now.getMinutes() / 15) * 15;
    now.setMinutes(minutes);
    now.setSeconds(0);
    now.setMilliseconds(0);
</script>





<!--
<br>
<footer>
    <div>
        <?php include COMPONENTS_PATH . '/admin-link.php'; ?>
    </div>
</footer>
-->

</html>