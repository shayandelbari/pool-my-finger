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

  <button type="submit" name="search_btn">GO</button>

  <button type="button" onclick="setPlaceholder('Pool Name')">All Pools</button>
  <button type="button" onclick="setPlaceholder('Postal Code')">By Location</button>

  <button type="submit" name="filter" onclick="toggleMainFilter()">FILTER</button>
  <!-- TODO: Change the filter button to display an icon not the word-->


  <div id="menuContainer1"></div>

  <div id="menuContainer2"></div>

</div>

<!-- toggle on/of the filter -->
<script>
  let openMainFilter = false;

  function toggleMainFilter() {
    fetch("filter.php", {
      method: POST,
    })
      .then(res => res.text())
      .then(html => document.getElementById("menuContainer1").innerHTML = html;)
  }

  function setPlaceholder(value) {
    document.querySelector('input[name="search_bar"]').placeholder = value;
  }
</script>






<!-- The body below is test and will be eventually deleted -->
<!-- 
<body>
  <h1>Home</h1>
  <p class="p-4 text-white bg-blue-500">testing tailwind</p>
  <p class="p-4 bg-background text-foreground">Testing new theming system</p>
  <p class="p-4 bg-primary text-primary-foreground">Primary style</p>
</body>
-->

<br>
<footer>
  <div>

    <?php include COMPONENTS_PATH . '/admin-link.php'; ?>
  </div>
</footer>

</html>