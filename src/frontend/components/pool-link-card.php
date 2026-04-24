<!-- I will take an instance of class pool and use the attributes to fill this template component -->
<?php include "../models/Pool.php" ?>

<div>
    <h2> <?php echo getName($pool) ?> </h2>
    <div>
        <img src=<?php echo getImageUrl($pool) ?> onerror="this.src='../assets/no-image-icon.png';">
    </div>
    <div>
        <h3> <?php echo getAddress($pool) ?> </h3>

        <!--  TODO - look into how addresses are being stored.
        <h3> <?php echo getCity($pool) ?>, <?php echo getProvince($pool) ?> </h3>
        <h3>
            <?php echo getPostalCode($pool) ?>
        </h3>
-->
        <h3> <?php echo getPhone($pool) ?> </h3>
        <h3> <?php echo getWebsite($pool) ?> </h3>
    </div>
</div>