<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$sqlmainDishes = "SELECT * FROM Menu WHERE item_category = 'Main Dishes' ORDER BY item_type; ";
$resultmainDishes = mysqli_query($link, $sqlmainDishes);
$mainDishes = mysqli_fetch_all($resultmainDishes, MYSQLI_ASSOC);

$sqldrinks = "SELECT * FROM Menu WHERE item_category = 'Drinks' ORDER BY item_type; ";
$resultdrinks = mysqli_query($link, $sqldrinks);
$drinks = mysqli_fetch_all($resultdrinks, MYSQLI_ASSOC);

$sqlsides = "SELECT * FROM Menu WHERE item_category = 'Side Snacks' ORDER BY item_type; ";
$resultsides = mysqli_query($link, $sqlsides);
$sides = mysqli_fetch_all($resultsides, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.theme.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <title>Home</title>
</head>

<body>
    <!-- Header -->
<?php
$isHomePage = basename($_SERVER['PHP_SELF']) === 'home.php';
$homeLink = $isHomePage ? '#hero' : '../home/home.php';
$menuLink = $isHomePage ? '#projects' : '../home/home.php#projects';
$aboutLink = $isHomePage ? '#about' : '../home/home.php#about';
$contactLink = $isHomePage ? '#contact' : '../home/home.php#contact';
?>

    <section id="header">
        <div class="header container">
            <div class="nav-bar">
                <div class="brand">
                    <a class="nav-link" href="<?php echo $homeLink; ?>">
                        <h1 class="brand-mark">Boundless</h1><span class="sr-only"></span>
                    </a>
                </div>
                <div class="nav-list">
                    <div class="hamburger">
                        <div class="bar"></div>
                    </div>
                    <div class="navbar-container">

                        <div class="navbar">
                            <ul>
                                <li><a href="<?php echo $homeLink; ?>" data-after="Home">Home</a></li>
                                <li><a href="<?php echo $menuLink; ?>" data-after="Menu">Menu</a></li>
                                <li><a href="../CustomerReservation/reservePage.php" data-after="Reservation">Reservation</a></li>
                                <li><a href="<?php echo $aboutLink; ?>" data-after="About">About</a></li>
                                <li><a href="<?php echo $contactLink; ?>" data-after="Contact">Contact</a></li>
                                <li><a href="../../adminSide/StaffLogin/login.php" data-after="Staff">Staff</a></li>

                                <div class="dropdown">
                                    <button class="dropbtn">ACCOUNT <i class="fa fa-caret-down" aria-hidden="true"></i>
                                    </button>
                                    <div class="dropdown-content">

<?php

// Get the member_id from the query parameters
$account_id = $_SESSION['account_id'] ?? null; // Change this to the way you obtain the member ID

// Create a query to retrieve the member's information
//$query = "SELECT member_name, points FROM memberships WHERE account_id = $account_id";

// Execute the query
//$result = mysqli_query($link, $query);

// Check if the user is logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $account_id != null) {
    $query = "SELECT member_name, points FROM memberships WHERE account_id = $account_id";

// Execute the query
$result = mysqli_query($link, $query);
    // If logged in, show "Logout" link
    // Check if the query was successful
    if ($result) {
        // Fetch the member's information
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            $member_name = $row['member_name'];
            $points = $row['points'];
            
            // Calculate VIP status
            $vip_status = ($points >= 1000) ? 'VIP' : 'Regular';
            
            // Define the VIP tooltip text
            $vip_tooltip = ($vip_status === 'Regular') ? ($points < 1000 ? (1000 - $points) . ' points to VIP ' : 'You are eligible for VIP') : '';
            
            // Output the member's information
            echo "<p class='logout-link' style='font-size:1.3em; margin-left:15px; padding:5px; color:white; '>$member_name</p>";
            echo "<p class='logout-link' style='font-size:1.3em; margin-left:15px;padding:5px;color:white; '>$points Points </p>";
            echo "<p class='logout-link' style='font-size:1.3em; margin-left:15px;padding:5px; color:white; '>$vip_status";
            
            // Add the tooltip only for Regular status
            if ($vip_status === 'Regular') {
                echo " <span class='tooltip'>$vip_tooltip</span>";
            }
            
            echo "</p>";
        } else {
            echo "Member not found.";
        }
    } else {
        echo "Error: " . mysqli_error($link);
    }

    echo '<a class="logout-link" style="color: white; font-size:1.3em;" href="../customerLogin/logout.php">Logout</a>';
} else {
    // If not logged in, show "Login" link
    echo '<a class="signin-link" style="color: white; font-size:15px;" href="../customerLogin/register.php">Sign Up </a> ';
    echo '<a class="login-link" style="color: white; font-size:15px; " href="../customerLogin/login.php">Log In</a>';
}

?>

                                    </div>
                                </div>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Header -->
