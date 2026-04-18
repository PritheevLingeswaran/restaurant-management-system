<?php
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $selectedDate = $_GET["reservation_date"] ?? '';
    $headCount = max(1, (int) ($_GET["head_count"] ?? 1));
    $selectedTime = date("H:i:s", strtotime($_GET["reservation_time"] ?? ''));

    if ($selectedDate === '' || $selectedTime === '00:00:00') {
        header("Location: reservePage.php?availability_status=error&availability_message=" . urlencode("Please choose a valid reservation date and time."));
        exit();
    }

    $reservedTableIds = [];
    $reservedStmt = mysqli_prepare(
        $link,
        "SELECT table_id FROM reservations WHERE reservation_date = ? AND reservation_time = ?"
    );
    mysqli_stmt_bind_param($reservedStmt, "ss", $selectedDate, $selectedTime);
    mysqli_stmt_execute($reservedStmt);
    $reservedResult = mysqli_stmt_get_result($reservedStmt);

    while ($row = mysqli_fetch_assoc($reservedResult)) {
        $reservedTableIds[] = (int) $row["table_id"];
    }

    mysqli_stmt_close($reservedStmt);

    $tableQuery = "SELECT table_id, capacity FROM restaurant_tables WHERE capacity >= ?";
    $types = "i";
    $params = [$headCount];

    if (!empty($reservedTableIds)) {
        $placeholders = implode(',', array_fill(0, count($reservedTableIds), '?'));
        $tableQuery .= " AND table_id NOT IN ($placeholders)";
        $types .= str_repeat('i', count($reservedTableIds));
        $params = array_merge($params, $reservedTableIds);
    }

    $availableStmt = mysqli_prepare($link, $tableQuery);
    mysqli_stmt_bind_param($availableStmt, $types, ...$params);
    mysqli_stmt_execute($availableStmt);
    $availableResult = mysqli_stmt_get_result($availableStmt);

    $availableTables = [];
    while ($row = mysqli_fetch_assoc($availableResult)) {
        $availableTables[] = $row;
    }

    mysqli_stmt_close($availableStmt);

    $redirectParams = [
        'reservation_date' => $selectedDate,
        'head_count' => $headCount,
        'reservation_time' => $selectedTime,
        'reserved_table_id' => empty($reservedTableIds) ? '0' : implode(',', $reservedTableIds),
    ];

    if (!empty($availableTables)) {
        $redirectParams['availability_status'] = 'success';
        $redirectParams['availability_message'] = count($availableTables) . ' table(s) available for the selected time.';
    } else {
        $redirectParams['availability_status'] = 'empty';
        $redirectParams['availability_message'] = 'No tables are available for the selected time.';
    }

    header('Location: reservePage.php?' . http_build_query($redirectParams));
    exit();
}
?>
