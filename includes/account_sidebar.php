<?php
// Account Sidebar Component
// This file contains the sidebar navigation for the account page
// Make sure $first_name is available in the scope where this is included
?>

<!-- Account Sidebar -->
<aside class="account-sidebar">
    <div class="sidebar-header">
        <h2 class="sidebar-title">My Account</h2>
    </div>
    <ul class="sidebar-nav">
        <li><a href="account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
        <li><a href="my_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_orders.php' ? 'active' : ''; ?>"><i class="fas fa-box"></i>My Orders</a></li>
        <li><a href="wishlist.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active' : ''; ?>"><i class="fas fa-heart"></i>My Wishlist</a></li>
        <li><a href="compare.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'compare.php' ? 'active' : ''; ?>"><i class="fas fa-balance-scale"></i>Compare</a></li>
        <li><a href="help_center.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'help_center.php' ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i>Help Center</a></li>
        <li><a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
</aside>

