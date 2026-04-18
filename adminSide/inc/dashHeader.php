<?php
require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['logged_account_id'])) {
    $logged_account_id = (int) $_SESSION['logged_account_id'];
    $staff_name_stmt = $link->prepare("SELECT staff_name FROM Staffs WHERE account_id = ?");

    if ($staff_name_stmt) {
        $staff_name_stmt->bind_param("i", $logged_account_id);
        $staff_name_stmt->execute();
        $staff_name_stmt->bind_result($current_staff_name);

        if ($staff_name_stmt->fetch()) {
            $_SESSION['logged_staff_name'] = $current_staff_name;
        }

        $staff_name_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Boundless Staff Panel</title>
        <link href="../css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> 
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="../panel/pos-panel.php">Boundless Staff Panel</a>
            
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Main</div>
                            <a class="nav-link" href="../panel/pos-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                                Point of Sale 
                            </a>
                            <a class="nav-link" href="../panel/bill-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-receipt"></i></div>
                                Bills
                            </a>
                            <a class="nav-link" href="../panel/table-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-table-cells"></i></div>
                                Table
                            </a>
                            <a class="nav-link" href="../panel/menu-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                                Menu
                            </a>
                            <a class="nav-link" href="../panel/reservation-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-book"></i></div>
                                Reservations
                            </a>
                            <a class="nav-link" href="../panel/customer-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-person-shelter"></i></div>
                                Members
                            </a>
                            <a class="nav-link" href="../panel/staff-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-people-group"></i></div>
                                Staff
                            </a>
                            <a class="nav-link" href="../panel/account-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-eye"></i></div>
                                View All Accounts
                            </a>
                            <a class="nav-link" href="../panel/kitchen-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-kitchen-set"></i></div>
                                Kitchen
                            </a>
                            <a class="nav-link" href="../panel/inventory-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-boxes-stacked"></i></div>
                                Inventory
                            </a>
                            <a class="nav-link" href="../panel/suppliers-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-truck-field"></i></div>
                                Suppliers
                            </a>
                            <a class="nav-link" href="../panel/purchase-orders-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                Purchase Orders
                            </a>
                            <a class="nav-link" href="../panel/waste-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-recycle"></i></div>
                                Waste & Spoilage
                            </a>
                            <a class="nav-link" href="../panel/recipe-costing-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-scale-balanced"></i></div>
                                Recipe Costing
                            </a>
                            <div class="sb-sidenav-menu-heading">Report & Analytics</div>
                            <a class="nav-link" href="../panel/sales-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-fire"></i></div>
                                Items Sales
                            </a>
                            <a class="nav-link" href="../panel/statistics-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Revenue Statistics
                            </a>
                            <a class="nav-link" href="../panel/profiles-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                Member Profiles
                            </a>
                            <a class="nav-link" href="../StaffLogin/logout.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-key"></i></div>
                                Log out
                            </a>
                            
                            
                            
                        </div>
                    </div>
                        <div class="sb-sidenav-footer">
                            <div class="small">Logged in as:</div>
                                <?php
                                // Check if the session variables are set
                                if (isset($_SESSION['logged_account_id']) && isset($_SESSION['logged_staff_name'])) {
                                    // Display the logged-in staff ID and name
                                    echo "Staff ID: " . $_SESSION['logged_account_id'] . "<br>";
                                    echo "Staff Name: " . $_SESSION['logged_staff_name'];
                                    
                                } else {
                                    // If session variables are not set, display a default message or handle as needed
                                    echo "Not logged in";
                                }
                                ?>
                        </div>
                </nav>
            </div>
        </div>
        <script src="../js/scripts.js" type="text/javascript"></script>
