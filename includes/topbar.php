<link rel="stylesheet" href="../assets/css/topbar.css">
<div class="topbar">
    <div class="d-flex align-items-center">
        <img src="../assets/imgs/dyci-logo.png" alt="DYCI Logo" class="topbar-logo">
    </div>
    <div class="d-flex flex-direction-column align-items-center">
        <a href="index.php" class="d-flex flex-direction-column justify-content-center align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none gap-3">
            <span class="fs-4 fw-bold text-white">EatsDYCI</span>
            <span class="text-white">Easy and Timely Service</span>
        </a>
    </div>
    <div class="d-flex align-items-center">
        <span class="topbar-user">
<<<<<<< HEAD
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?> (<?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?>)
=======
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
>>>>>>> master
        </span>
        <a href="login.php?logout=1" class="topbar-logout">Logout</a>
    </div>
</div> 