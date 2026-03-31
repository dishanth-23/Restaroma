<?php
$pageClass = "order_receipt-page";

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireUserLogin();
require __DIR__ . '/../includes/header.php';

    $orderId = (int)($_GET['id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT o.*, u.name 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id AND o.user_id = :uid
        ");

        $stmt->execute([
            ':id'=>$orderId,
            ':uid'=>$_SESSION['user_id']
        ]);

        $order = $stmt->fetch();

            if(!$order){
            echo "<p>Order not found.</p>";
            require __DIR__.'/../includes/footer.php';
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT oi.quantity, oi.price, m.name
            FROM order_items oi
            JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE oi.order_id = :id
        ");

        $stmt->execute([':id'=>$orderId]);
        $items = $stmt->fetchAll();

        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += ((float)$it['price']) * ((int)$it['quantity']);
        }

        $discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0.0;
        $promoCode = isset($order['promo_code']) ? trim((string)$order['promo_code']) : '';

        // If older orders don't have stored fields, treat total_price as subtotal.
        if (!isset($order['subtotal_amount']) || $order['subtotal_amount'] === null) {
            $orderSubtotal = $subtotal;
        } else {
            $orderSubtotal = (float)$order['subtotal_amount'];
        }

        if ($orderSubtotal <= 0) $orderSubtotal = $subtotal;

        $finalTotal = (float)$order['total_price'];
        if ($finalTotal <= 0) {
            $finalTotal = max(0, round($orderSubtotal - $discountAmount, 2));
        }
?>



<div class="receipt-box">
    <h1>R E S T A R O M A</h2>
        <hr>
        <h3><i class="fa-solid fa-receipt"></i> Order Receipt
    </h1>
    <table class="receipt-table">
        <tr>
            <th style="text-align:right;"><strong>Order ID :</strong></th>
            <td style="text-align:left;">#<?php echo $order['id']; ?></td>
        </tr>
        <tr>
            <th style="text-align:right;"><strong>Date :</strong></th>
            <td style="text-align:left;"><?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></td>
        </tr>
        <tr>
            <th style="text-align:right;"><strong>Payment Method :</strong></th>
            <td style="text-align:left;"><?php echo htmlspecialchars($order['payment_method']); ?></td>
        </tr>
        <?php
            $displayStatus = $order['payment_status'];

            if ($order['payment_method'] === 'Cash' && $order['payment_status'] === 'Unpaid') {
                $displayStatus = 'Cash on Payment';
            }
        ?>
        <tr>
            <th style="text-align:right;"><strong>Payment Status :</strong></th>
            <td style="text-align:left;"><?php echo htmlspecialchars($displayStatus); ?></td>
        </tr>
        <tr>
            <th style="text-align:right;"><strong>Status :</strong></th>
            <td style="text-align:left;"><?php echo htmlspecialchars($order['status']); ?></td>
        </tr>
    </table>
    <h3><i class="fa-solid fa-clipboard-list"></i> Order Items</h3>
    <table class="receipt-table">
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
        <?php foreach($items as $it): ?>
        <tr>
            <td style="text-align:left;"><?php echo htmlspecialchars($it['name']); ?></td>
            <td style="text-align:center;"><?php echo $it['quantity']; ?></td>
            <td style="text-align:left;">Rs <?php echo number_format($it['price'],2); ?></td>
            <td style="text-align:left;">Rs <?php echo number_format($it['price']*$it['quantity'],2); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <table class="receipt-table">
        <tr>
            <th style="text-align:right;"><strong>Subtotal :</strong></th>
            <td style="text-align:left;">Rs <?php echo number_format($orderSubtotal, 2); ?></td>
        </tr>
        <tr>
            <th style="text-align:right;"><strong>Promo<?php echo $promoCode ? ' (' . htmlspecialchars($promoCode) . ')' : ''; ?> :</strong></th>
            <td style="text-align:left;">- <?php echo 'Rs ' . number_format($discountAmount, 2); ?></td>
        </tr>
        <tr>
            <th style="text-align:right;"><strong>Total :</strong></th>
            <td style="text-align:left;">Rs <?php echo number_format($finalTotal, 2); ?></td>
        </tr>
    </table>
    <button onclick="window.print()" class="print-btn"><i class="fa-solid fa-print"></i> Print Receipt</button>
    <div class="receipt-footer">
        <p>Thank you for dining with us! <i class="fa-regular fa-handshake"></i></p>
        <p>We hope to serve you again soon. <i class="fa-regular fa-face-grin-beam"></i></p>
        <hr>
        <p class="receipt-contact">Kaithady west, Kaithady, Jaffna | +94 766 125 494</p>
    </div>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>