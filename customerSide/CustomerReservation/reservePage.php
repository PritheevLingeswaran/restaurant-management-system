<?php
require_once '../config.php';
session_start();

$reservationStatus = $_GET['reservation'] ?? null;
$reservationId = $_GET['reservation_id'] ?? null;
$reservationError = $_GET['message'] ?? '';
$headCount = (int) ($_GET['head_count'] ?? 1);
$defaultReservationDate = $_GET['reservation_date'] ?? date('Y-m-d');
$defaultReservationTime = $_GET['reservation_time'] ?? '13:00:00';
$reservedTableIdList = $_GET['reserved_table_id'] ?? '0';

$availableTimes = [];
for ($hour = 10; $hour <= 20; $hour++) {
    $availableTimes[] = sprintf('%02d:00:00', $hour);
}

$availableTables = [];
if ($reservedTableIdList !== '') {
    $reservedTableIds = array_filter(array_map('intval', explode(',', $reservedTableIdList)));
    $tableQuery = "SELECT * FROM restaurant_tables WHERE capacity >= ?";
    $types = "i";
    $params = [$headCount];

    if (!empty($reservedTableIds) && $reservedTableIdList !== '0') {
        $placeholders = implode(',', array_fill(0, count($reservedTableIds), '?'));
        $tableQuery .= " AND table_id NOT IN ($placeholders)";
        $types .= str_repeat('i', count($reservedTableIds));
        $params = array_merge($params, $reservedTableIds);
    }

    $tableStmt = mysqli_prepare($link, $tableQuery);
    mysqli_stmt_bind_param($tableStmt, $types, ...$params);
    mysqli_stmt_execute($tableStmt);
    $tableResult = mysqli_stmt_get_result($tableStmt);
    $availableTables = mysqli_fetch_all($tableResult, MYSQLI_ASSOC);
    mysqli_stmt_close($tableStmt);
}

include_once('../components/header.php');
?>

<?php if ($reservationStatus === 'success' && $reservationId): ?>
    <script>
        alert("Table successfully reserved. Click OK to view your reservation receipt.");
        window.location.href = "reservationReceipt.php?reservation_id=<?php echo urlencode((string) $reservationId); ?>";
    </script>
<?php endif; ?>

<section class="reservation-hero">
    <div class="reservation-hero-copy">
        <p class="eyebrow">Table Booking</p>
        <h1>Reserve your seat with confidence.</h1>
        <p>
            Select your preferred date and time, review available tables, and complete the reservation in one polished flow.
        </p>
    </div>
</section>

<section class="reservation-shell">
    <div class="reservation-grid">
        <div class="reservation-panel search-panel">
            <div class="reservation-panel-header">
                <span>Step 1</span>
                <h2>Search For Time</h2>
                <p>Pick the day and timeslot to see which tables are available.</p>
            </div>

            <form method="GET" action="availability.php" class="reservation-form-card">
                <div class="form-group">
                    <label for="search_reservation_date">Select Date</label>
                    <input class="form-control" type="date" id="search_reservation_date" name="reservation_date" required>
                </div>

                <div class="form-group">
                    <label for="search_reservation_time">Available Reservation Times</label>
                    <select name="reservation_time" id="search_reservation_time" class="form-control" required>
                        <option value="" selected disabled>Select a Time</option>
                        <?php foreach ($availableTimes as $time): ?>
                            <option value="<?php echo htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo date('h:i A', strtotime($time)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="number" name="head_count" value="1" hidden required>

                <button type="submit" class="btn reservation-btn-dark">Search Availability</button>
            </form>
        </div>

        <div class="reservation-panel book-panel">
            <div class="reservation-panel-header">
                <span>Step 2</span>
                <h2>Make Reservation</h2>
                <p>Fill in the guest details and confirm the best available table.</p>
            </div>

            <?php if ($reservationError !== ''): ?>
                <div class="reservation-alert">
                    <?php echo htmlspecialchars($reservationError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="insertReservation.php" class="reservation-form-card">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input class="form-control" type="text" id="customer_name" name="customer_name" required>
                </div>

                <div class="reservation-inline-fields">
                    <div class="form-group">
                        <label for="reservation_date_display">Reservation Date</label>
                        <input class="form-control" type="date" id="reservation_date_display" name="reservation_date" value="<?php echo htmlspecialchars($defaultReservationDate, ENT_QUOTES, 'UTF-8'); ?>" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="reservation_time_display">Reservation Time</label>
                        <input class="form-control" type="time" id="reservation_time_display" name="reservation_time" value="<?php echo htmlspecialchars($defaultReservationTime, ENT_QUOTES, 'UTF-8'); ?>" readonly required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="table_id_reserve">Available Tables</label>
                    <select class="form-control" name="table_id" id="table_id_reserve" required>
                        <option value="" selected disabled>Select a Table</option>
                        <?php if (!empty($availableTables)): ?>
                            <?php foreach ($availableTables as $table): ?>
                                <option value="<?php echo (int) $table['table_id']; ?>">
                                    Table <?php echo (int) $table['table_id']; ?> · For <?php echo (int) $table['capacity']; ?> guests
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option disabled>No tables available yet. Search a date and time first.</option>
                        <?php endif; ?>
                    </select>
                    <input type="number" name="head_count" value="<?php echo $headCount; ?>" hidden required>
                </div>

                <div class="form-group">
                    <label for="special_request">Special Request</label>
                    <textarea class="form-control" id="special_request" name="special_request" rows="4" placeholder="Anniversary setup, quiet corner, allergies, or any dining preference"></textarea>
                </div>

                <button type="submit" class="btn reservation-btn-gold">Confirm Reservation</button>
            </form>
        </div>
    </div>
</section>

<?php include_once('../components/footer.php'); ?>
