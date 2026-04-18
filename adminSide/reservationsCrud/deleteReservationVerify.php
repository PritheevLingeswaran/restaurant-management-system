<?php
require_once "../config.php";
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $table_id = intval($_GET['id']);
} else {
    header("Location: ../panel/reservation-panel.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $provided_account_id = $_POST['admin_id'];
    $provided_password = $_POST['password'];
    if (($provided_account_id . $provided_password) == "9999912345") {
        header("Location: ../reservationsCrud/deleteReservation.php?id=".$table_id);
        exit();
    } else {
        echo '<script>alert("Incorrect ID or Password!")</script>';
    }
}
$deleteVerifyMessage = 'Admin credentials are needed to delete this reservation.';
$deleteVerifySubmitLabel = 'Delete Reservation';
$deleteVerifyCancelHref = '../panel/reservation-panel.php';
include '../inc/adminDeleteVerifyLayout.php';
