<?php
// user/order.php
// Handles placing a new order (multiple items, quantities, total calculation).
$pageClass = "udashboard-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireUserLogin();
require __DIR__ . '/../includes/header.php';

// Fetch all menu items for selection
$stmt = $pdo->query("SELECT id, name, price, image, quantity FROM menu_items ORDER BY name ASC");
$items = $stmt->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $selectedItems = $_POST['item'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (empty($selectedItems)) {
        $errors[] = 'Please select at least one item.';
    } else {
        $total = 0;
        $orderLines = [];
        $menuLookup = [];

        foreach ($items as $m) {
            $menuLookup[$m['id']] = $m;
        }

        foreach ($selectedItems as $menuId) {
            $menuId = (int)$menuId;
            $qty = (int)($quantities[$menuId] ?? 0);

            if ($qty <= 0 || $qty > 50 || !isset($menuLookup[$menuId])) continue;

            $available = $menuLookup[$menuId]['quantity'];
            if ($qty > $available) {
                $errors[] = 'Not enough quantity available for ' . htmlspecialchars($menuLookup[$menuId]['name']) . '. Available: ' . $available;
                continue;
            }

            $price = $menuLookup[$menuId]['price'];
            $total += $price * $qty;

            $orderLines[] = [
                'menu_item_id' => $menuId,
                'quantity' => $qty,
                'price' => $price
            ];
        }

        if ($total <= 0) {
            $errors[] = 'Please provide valid quantities.';
        }

        if (empty($errors) && !empty($orderLines)) {
            try {
                $pdo->beginTransaction(); // ✅ START TRANSACTION

                // Insert order
                $stmt = $pdo->prepare("
                    INSERT INTO orders 
                    (user_id, total_price, status, payment_method, payment_status, payment_reference, created_at)
                    VALUES (:uid, :total, 'Pending', 'Cash', 'Unpaid', NULL, NOW())
                ");
                $stmt->execute([
                    ':uid' => $_SESSION['user_id'],
                    ':total' => $total
                ]);

                $orderId = $pdo->lastInsertId();

                // Insert order items
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, menu_item_id, quantity, price) 
                    VALUES (:oid, :mid, :qty, :price)
                ");
                foreach ($orderLines as $line) {
                    $stmtItem->execute([
                        ':oid' => $orderId,
                        ':mid' => $line['menu_item_id'],
                        ':qty' => $line['quantity'],
                        ':price' => $line['price']
                    ]);
                }

                // NOTE: Inventory is decremented only after payment is confirmed (in payment.php)

                $pdo->commit(); // ✅ COMMIT

                // Redirect to payment page
                header("Location: payment.php?order_id=" . $orderId);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to place order. Please try again.';
            }
        }
    }
}
?>

<div class="order-page">

    <div class="dashboard-header">
        <h1>Place Your Order</h1>
        <div class="dashboard-divider"></div>
        <p>Select your favorite dishes and enjoy luxury dining</p>
    </div>
    <?php if ($success): ?>
    <div class="alert-success">
        <p>Your order has been placed successfully. Payment method:
            <?php echo htmlspecialchars($payment_method ?? 'Cash'); ?>.</p>
    </div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="alert-danger">
        <?php foreach ($errors as $e): ?>
        <p><?php echo htmlspecialchars($e); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>



    <form action="" method="post" class="order-card">
        <?php csrf_input(); ?>

        <table class="order-table">

            <thead>
                <tr>
                    <th>Select</th>
                    <th></th>
                    <th>Item</th>
                    <th>Price (Rs)</th>
                    <th>Available</th>
                    <th>Quantity</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($items as $item): ?>

                <tr>

                    <td>
                        <input type="checkbox" class="order-check" name="item[]" value="<?php echo (int)$item['id']; ?>"
                            <?php echo $item['quantity'] <= 0 ? 'disabled' : ''; ?>>
                    </td>

                    <td>
                        <img src="/Restaurant-System/assets/images/<?php echo htmlspecialchars($item['image']); ?>"
                            class="menu-thumb">
                    </td>

                    <td class="item-name">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </td>

                    <td class="price" data-price="<?php echo $item['price']; ?>">
                        Rs <?php echo number_format($item['price'], 2); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($item['quantity']); ?>
                    </td>

                    <td>
                        <input type="number" class="qty-input" min="1" max="<?php echo max(1, $item['quantity']); ?>"
                            name="quantity[<?php echo (int)$item['id']; ?>]" value="1" disabled>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
        <div class="order-total">
            Total: Rs 0.00
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const totalDiv = document.querySelector('.order-total');

            function updateTotal() {
                let total = 0;
                document.querySelectorAll('.order-table tbody tr').forEach(row => {
                    const checkbox = row.querySelector('.order-check');
                    const qtyInput = row.querySelector('.qty-input');
                    const price = parseFloat(row.querySelector('.price').dataset.price);

                    if (checkbox.checked) {
                        const qty = parseInt(qtyInput.value) || 0;
                        total += price * qty;
                    }
                });
                totalDiv.innerText = `Total: Rs ${total.toFixed(2)}`;
            }

            // Enable/disable quantity input & update total
            document.querySelectorAll('.order-check').forEach(check => {
                const row = check.closest('tr');
                const qtyInput = row.querySelector('.qty-input');
                qtyInput.disabled = !check.checked;

                check.addEventListener('change', () => {
                    qtyInput.disabled = !check.checked;
                    if (check.checked && qtyInput.value == 0) qtyInput.value = 1;
                    updateTotal();
                });
            });

            // Update total when quantity changes
            document.querySelectorAll('.qty-input').forEach(qtyInput => {
                qtyInput.addEventListener('input', updateTotal);
            });

            // Initial total calculation
            updateTotal();
        });
        </script>

        <div class="order-action">
            <button type="submit" class="order-btn">Place Order</button>
        </div>

    </form>

</div>

<?php
require __DIR__ . '/../includes/footer.php';