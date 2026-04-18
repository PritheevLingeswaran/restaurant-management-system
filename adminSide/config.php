<?php // Rememeber to change the username,password and database name to acutal values
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurantDB');

//Create Connection
$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$link->set_charset('utf8mb4');
$link->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

//Check COnnection
if ($link->connect_error) { //if not Connection
    die('Connection Failed' . $link->connect_error);//kills the Connection OR terminate execution
}

if (!function_exists('db_begin_transaction_with_isolation')) {
    function db_begin_transaction_with_isolation(mysqli $connection, string $isolation = 'READ COMMITTED'): void
    {
        $allowed = ['READ COMMITTED', 'REPEATABLE READ', 'SERIALIZABLE'];
        if (!in_array($isolation, $allowed, true)) {
            $isolation = 'READ COMMITTED';
        }

        $connection->query("SET TRANSACTION ISOLATION LEVEL {$isolation}");
        $connection->begin_transaction();
    }
}

if (!function_exists('db_acquire_named_lock')) {
    function db_acquire_named_lock(mysqli $connection, string $lockName, int $timeoutSeconds = 10): bool
    {
        $stmt = $connection->prepare("SELECT GET_LOCK(?, ?)");
        $stmt->bind_param("si", $lockName, $timeoutSeconds);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        $stmt->close();

        return (int) $result === 1;
    }
}

if (!function_exists('db_release_named_lock')) {
    function db_release_named_lock(mysqli $connection, string $lockName): void
    {
        $stmt = $connection->prepare("SELECT RELEASE_LOCK(?)");
        $stmt->bind_param("s", $lockName);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('inventory_get_current_stock')) {
    function inventory_get_current_stock(mysqli $connection, string $ingredientId): float
    {
        $stmt = $connection->prepare("SELECT current_stock FROM Inventory_Ingredients WHERE ingredient_id = ?");
        $stmt->bind_param("s", $ingredientId);
        $stmt->execute();
        $stmt->bind_result($currentStock);
        $found = $stmt->fetch();
        $stmt->close();

        return $found ? (float) $currentStock : 0.0;
    }
}

if (!function_exists('inventory_sync_low_stock_alert')) {
    function inventory_sync_low_stock_alert(mysqli $connection, string $ingredientId): void
    {
        $ingredientStmt = $connection->prepare(
            "SELECT ingredient_name, current_stock, reorder_level
             FROM Inventory_Ingredients
             WHERE ingredient_id = ?"
        );
        $ingredientStmt->bind_param("s", $ingredientId);
        $ingredientStmt->execute();
        $ingredientStmt->bind_result($ingredientName, $currentStock, $reorderLevel);
        $found = $ingredientStmt->fetch();
        $ingredientStmt->close();

        if (!$found) {
            return;
        }

        if ((float) $currentStock <= (float) $reorderLevel) {
            $message = sprintf(
                "%s is below reorder level (%.3f remaining, reorder at %.3f).",
                $ingredientName,
                (float) $currentStock,
                (float) $reorderLevel
            );
            $insertStmt = $connection->prepare(
                "INSERT INTO Inventory_Alerts (ingredient_id, alert_type, alert_message, status, created_at)
                 VALUES (?, 'low_stock', ?, 'active', NOW())
                 ON DUPLICATE KEY UPDATE
                    alert_message = VALUES(alert_message),
                    status = 'active',
                    resolved_at = NULL"
            );
            $insertStmt->bind_param("ss", $ingredientId, $message);
            $insertStmt->execute();
            $insertStmt->close();
            return;
        }

        $resolveStmt = $connection->prepare(
            "UPDATE Inventory_Alerts
             SET status = 'resolved', resolved_at = NOW()
             WHERE ingredient_id = ? AND alert_type = 'low_stock' AND status = 'active'"
        );
        $resolveStmt->bind_param("s", $ingredientId);
        $resolveStmt->execute();
        $resolveStmt->close();
    }
}

if (!function_exists('inventory_record_stock_movement')) {
    function inventory_record_stock_movement(
        mysqli $connection,
        string $ingredientId,
        string $movementType,
        float $quantity,
        float $unitCost = 0.0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $staffId = null,
        string $notes = ''
    ): void {
        $allowedTypes = [
            'purchase_in',
            'stock_in',
            'stock_out',
            'kitchen_usage',
            'waste',
            'spoilage',
            'adjustment_in',
            'adjustment_out',
            'return_out',
            'reverse_usage',
        ];

        if (!in_array($movementType, $allowedTypes, true)) {
            throw new InvalidArgumentException('Invalid stock movement type.');
        }

        $currentStock = inventory_get_current_stock($connection, $ingredientId);
        $signedQuantity = $quantity;
        if (in_array($movementType, ['stock_out', 'kitchen_usage', 'waste', 'spoilage', 'adjustment_out', 'return_out'], true)) {
            $signedQuantity = -abs($quantity);
        } else {
            $signedQuantity = abs($quantity);
        }

        $newStock = $currentStock + $signedQuantity;
        if ($newStock < -0.0001) {
            throw new RuntimeException("Insufficient stock for ingredient {$ingredientId}.");
        }

        $movementStmt = $connection->prepare(
            "INSERT INTO Inventory_Stock_Movements
                (ingredient_id, movement_type, quantity, unit_cost, reference_type, reference_id, staff_id, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $movementStmt->bind_param(
            "ssddsiis",
            $ingredientId,
            $movementType,
            $signedQuantity,
            $unitCost,
            $referenceType,
            $referenceId,
            $staffId,
            $notes
        );
        $movementStmt->execute();
        $movementStmt->close();

        $updateStmt = $connection->prepare(
            "UPDATE Inventory_Ingredients
             SET current_stock = ?, last_stocked_at = CASE WHEN ? > 0 THEN NOW() ELSE last_stocked_at END
             WHERE ingredient_id = ?"
        );
        $updateStmt->bind_param("dds", $newStock, $signedQuantity, $ingredientId);
        $updateStmt->execute();
        $updateStmt->close();

        inventory_sync_low_stock_alert($connection, $ingredientId);
    }
}

if (!function_exists('inventory_receive_purchase_order')) {
    function inventory_receive_purchase_order(mysqli $connection, int $purchaseOrderId, ?int $staffId = null): void
    {
        $poStmt = $connection->prepare(
            "SELECT po_id, status
             FROM Purchase_Orders
             WHERE po_id = ?
             FOR UPDATE"
        );
        $poStmt->bind_param("i", $purchaseOrderId);
        $poStmt->execute();
        $poResult = $poStmt->get_result();
        $purchaseOrder = $poResult->fetch_assoc();
        $poStmt->close();

        if (!$purchaseOrder) {
            throw new RuntimeException('Purchase order not found.');
        }

        if ($purchaseOrder['status'] === 'received') {
            return;
        }

        $itemStmt = $connection->prepare(
            "SELECT po_item_id, ingredient_id, ordered_qty, received_qty, unit_cost
             FROM Purchase_Order_Items
             WHERE po_id = ?"
        );
        $itemStmt->bind_param("i", $purchaseOrderId);
        $itemStmt->execute();
        $itemsResult = $itemStmt->get_result();

        while ($item = $itemsResult->fetch_assoc()) {
            $receivableQty = (float) $item['ordered_qty'] - (float) $item['received_qty'];
            if ($receivableQty <= 0) {
                continue;
            }

            inventory_record_stock_movement(
                $connection,
                $item['ingredient_id'],
                'purchase_in',
                $receivableQty,
                (float) $item['unit_cost'],
                'purchase_order',
                $purchaseOrderId,
                $staffId,
                'Purchase order received'
            );

            $receivedQty = (float) $item['ordered_qty'];
            $updateItemStmt = $connection->prepare(
                "UPDATE Purchase_Order_Items
                 SET received_qty = ?
                 WHERE po_item_id = ?"
            );
            $updateItemStmt->bind_param("di", $receivedQty, $item['po_item_id']);
            $updateItemStmt->execute();
            $updateItemStmt->close();

            $avgStmt = $connection->prepare(
                "UPDATE Inventory_Ingredients
                 SET average_unit_cost = CASE
                    WHEN average_unit_cost = 0 THEN ?
                    ELSE ROUND(((average_unit_cost * 3) + ?) / 4, 2)
                 END
                 WHERE ingredient_id = ?"
            );
            $avgStmt->bind_param("dds", $item['unit_cost'], $item['unit_cost'], $item['ingredient_id']);
            $avgStmt->execute();
            $avgStmt->close();
        }
        $itemStmt->close();

        $updatePoStmt = $connection->prepare(
            "UPDATE Purchase_Orders
             SET status = 'received', received_at = NOW()
             WHERE po_id = ?"
        );
        $updatePoStmt->bind_param("i", $purchaseOrderId);
        $updatePoStmt->execute();
        $updatePoStmt->close();
    }
}

if (!function_exists('inventory_consume_menu_item')) {
    function inventory_consume_menu_item(mysqli $connection, string $menuItemId, int $menuQuantity, int $kitchenId, ?int $staffId = null): array
    {
        $recipeStmt = $connection->prepare(
            "SELECT mi.ingredient_id, mi.quantity_required, mi.waste_percentage, ii.ingredient_name, ii.average_unit_cost, ii.current_stock
             FROM Menu_Ingredients mi
             INNER JOIN Inventory_Ingredients ii ON ii.ingredient_id = mi.ingredient_id
             WHERE mi.item_id = ?"
        );
        $recipeStmt->bind_param("s", $menuItemId);
        $recipeStmt->execute();
        $recipeResult = $recipeStmt->get_result();

        $consumed = [];
        while ($ingredient = $recipeResult->fetch_assoc()) {
            $requiredQty = ((float) $ingredient['quantity_required']) * $menuQuantity;
            $requiredQty += $requiredQty * (((float) $ingredient['waste_percentage']) / 100);

            if ((float) $ingredient['current_stock'] < $requiredQty) {
                throw new RuntimeException("Insufficient stock for {$ingredient['ingredient_name']}.");
            }

            inventory_record_stock_movement(
                $connection,
                $ingredient['ingredient_id'],
                'kitchen_usage',
                $requiredQty,
                (float) $ingredient['average_unit_cost'],
                'kitchen',
                $kitchenId,
                $staffId,
                "Consumed for menu item {$menuItemId}"
            );

            $consumed[] = [
                'ingredient_name' => $ingredient['ingredient_name'],
                'ingredient_id' => $ingredient['ingredient_id'],
                'quantity_used' => round($requiredQty, 3),
                'estimated_cost' => round($requiredQty * (float) $ingredient['average_unit_cost'], 2),
            ];
        }

        $recipeStmt->close();

        return $consumed;
    }
}

if (!function_exists('inventory_reverse_kitchen_consumption')) {
    function inventory_reverse_kitchen_consumption(mysqli $connection, int $kitchenId, ?int $staffId = null): void
    {
        $movementStmt = $connection->prepare(
            "SELECT ingredient_id, ABS(quantity) AS quantity, unit_cost
             FROM Inventory_Stock_Movements
             WHERE reference_type = 'kitchen' AND reference_id = ? AND movement_type = 'kitchen_usage'"
        );
        $movementStmt->bind_param("i", $kitchenId);
        $movementStmt->execute();
        $movementResult = $movementStmt->get_result();

        while ($movement = $movementResult->fetch_assoc()) {
            inventory_record_stock_movement(
                $connection,
                $movement['ingredient_id'],
                'reverse_usage',
                (float) $movement['quantity'],
                (float) $movement['unit_cost'],
                'kitchen_reversal',
                $kitchenId,
                $staffId,
                'Kitchen completion reversed'
            );
        }
        $movementStmt->close();

        $deleteStmt = $connection->prepare(
            "DELETE FROM Inventory_Stock_Movements
             WHERE reference_type = 'kitchen' AND reference_id = ? AND movement_type = 'kitchen_usage'"
        );
        $deleteStmt->bind_param("i", $kitchenId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
}

if (!function_exists('db_get_logged_staff_id')) {
    function db_get_logged_staff_id(mysqli $connection): ?int
    {
        if (!isset($_SESSION['logged_account_id'])) {
            return null;
        }

        $accountId = (int) $_SESSION['logged_account_id'];
        $stmt = $connection->prepare("SELECT staff_id FROM Staffs WHERE account_id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $stmt->bind_result($staffId);
        $found = $stmt->fetch();
        $stmt->close();

        return $found ? (int) $staffId : null;
    }
}
?>
