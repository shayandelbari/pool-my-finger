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
    let adultFilterOpen = false;
    let allAgesFilterOpen = false;


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

    function toggleAdultFilter() {
        adultFilterOpen = !adultFilterOpen;
        fetch('<?php echo BASE_URL; ?>/filter', {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "state=" + (adultFilterOpen ? "open" : "closed")
        })
            .then(res => res.text())
            .then(html => {
                document.getElementById("menuContainerAdult").innerHTML = html;
            });
    }

    function toggleAllAgesFilter() {
        allAgesFilterOpen = !allAgesFilterOpen;
        fetch('<?php echo BASE_URL; ?>/filter', {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "state=" + (allAgesFilterOpen ? "open" : "closed")
        })
            .then(res => res.text())
            .then(html => {
                document.getElementById("menuContainerAllAges").innerHTML = html;
            });
    }

    function setPlaceholder(value) {
        document.querySelector('input[name="search_bar"]').placeholder = value;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('button[name="filter"]').addEventListener('click', toggleMainFilter);
        document.querySelector('input[name="filter_adult"]').addEventListener('change', toggleAdultFilter);
        document.querySelector('input[name="filter_all_ages"]').addEventListener('change', toggleAllAgesFilter);
    });
</script>


<!-- search bar -->
<?php
if (isset($_POST['allPools_btn'])) {

    $isAllPoolsPage = true;
    $placeholder = "Pool Name";
} else {
    $isAllPoolsPage = false;
    $placeholder = "Postal Code";
}
?>



<div>
    <input type="text" placeholder="<?php echo $placeholder; ?>" name="search_bar">

    <button type="button" name="search_btn">GO</button>

    <!--
  <button type="button" onclick="setPlaceholder('Pool Name')">All Pools</button>
  <button type="button" onclick="setPlaceholder('Postal Code')">By Location</button>
-->

    <button type="button" name="filter">FILTER</button>
    <!-- TODO: Change the filter button to display an icon not the word-->

    <div id="dateTimeContainer"></div>

    <div id="menuContainer"> </div>

    <div id="menuContainerAdult"></div>

    <div id="menuContainerAllAges"></div>


</div>





<br>
<footer>
    <div>
        <?php include COMPONENTS_PATH . '/admin-link.php'; ?>
    </div>
</footer>


</html>