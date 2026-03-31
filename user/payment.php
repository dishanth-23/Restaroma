<?php
$pageClass = "payment-page";

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireUserLogin();
require __DIR__ . '/../includes/header.php';

$errors = [];

function promoIsActiveNow(array $promo, DateTimeImmutable $now): bool {
    $scheduleType = $promo['schedule_type'] ?? 'range';
    if ($scheduleType === 'weekly' && !empty($promo['valid_from']) && !empty($promo['valid_to'])) {
        $todayDate = $now->format('Y-m-d');
        if ($todayDate < $promo['valid_from'] || $todayDate > $promo['valid_to']) return false;
        $dow = (int)$now->format('N'); // 1..7
        $days = array_filter(array_map('trim', explode(',', (string)($promo['weekly_days'] ?? ''))));
        if (!in_array((string)$dow, $days, true)) return false;
        $ds = $promo['daily_start_time'] ?: '00:00:00';
        $de = $promo['daily_end_time'] ?: '23:59:59';
        $startToday = new DateTimeImmutable($todayDate . ' ' . $ds);
        $endToday = new DateTimeImmutable($todayDate . ' ' . $de);
        return ($now >= $startToday && $now <= $endToday);
    }

    $start = strtotime((string)($promo['start_date'] ?? ''));
    $end = strtotime((string)($promo['end_date'] ?? ''));
    if (!$start || !$end) return false;
    $ts = $now->getTimestamp();
    return ($ts >= $start && $ts <= $end);
}

$orderId = (int)($_GET['order_id'] ?? 0);

// Fetch order
$stmt = $pdo->prepare("SELECT id, total_price, payment_status, promo_code, subtotal_amount, discount_amount FROM orders WHERE id=:id AND user_id=:uid");
$stmt->execute([':id'=>$orderId, ':uid'=>$_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo "Order not found"; exit;
}

if ($order['payment_status'] === 'Paid') {
    echo "This order has already been paid."; exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    verify_csrf_or_die();

    $method = $_POST['payment_method'] ?? '';
    $promoCodeInput = strtoupper(trim($_POST['promo_code'] ?? ''));

    $errors = [];

    if (!in_array($method, ['Cash','Card'])) {
        $errors[] = "Please select a payment method.";
    }

    // Recompute subtotal from order_items
    $stmtItems = $pdo->prepare("SELECT quantity, price, menu_item_id FROM order_items WHERE order_id=:id");
    $stmtItems->execute([':id' => $orderId]);
    $orderItems = $stmtItems->fetchAll();
    $subtotal = 0;
    foreach ($orderItems as $it) {
        $subtotal += (float)$it['price'] * (int)$it['quantity'];
    }

    $discountAmount = 0.0;
    $appliedPromoCode = null;

    // Promo code
    if ($promoCodeInput !== '' && empty($errors)) {
        $stmtPromo = $pdo->prepare("SELECT * FROM promos WHERE UPPER(promo_code)=:code LIMIT 1");
        $stmtPromo->execute([':code' => $promoCodeInput]);
        $promo = $stmtPromo->fetch();

        if (!$promo) {
            $errors[] = "Invalid promo code.";
        } else {
            $nowDt = new DateTimeImmutable('now');
            if (!promoIsActiveNow($promo, $nowDt)) {
                $errors[] = "Promo code is not active right now.";
            } else {
                $percent = (int)($promo['discount_percent'] ?? 0);
                if ($percent < 1 || $percent > 100) {
                    $errors[] = "Promo code is invalid.";
                } else {
                    $discountAmount = round($subtotal * ($percent / 100), 2);
                    $appliedPromoCode = $promoCodeInput;
                }
            }
        }
    }

    $finalTotal = max(0, round($subtotal - $discountAmount, 2));

    // Card payment validation
    if ($method === "Card") {
        $card_number = preg_replace('/\s+/', '', trim($_POST['card_number'] ?? ''));
        $card_name   = trim($_POST['card_name'] ?? '');
        $expiry      = trim($_POST['expiry'] ?? '');
        $cvv         = trim($_POST['cvv'] ?? '');

        if (!$card_number || !$card_name || !$expiry || !$cvv) {
            $errors[] = "Please fill in all card details.";
        } elseif (!preg_match('/^\d{16}$/', $card_number)) {
            $errors[] = "Card number must be 16 digits.";
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = "Expiry must be in MM/YY format.";
        } else {
            [$month, $year] = explode('/', $expiry);
            $month = (int)$month;
            $year = (int)$year + 2000;
            $lastDay = new DateTime("{$year}-{$month}-01");
            $lastDay->modify('last day of this month');
            if ($lastDay < new DateTime()) {
                $errors[] = "Card has expired.";
            }
        }

        if (!preg_match('/^\d{3}$/', $cvv)) {
            $errors[] = "CVV must be 3 digits.";
        }
    }

    // Only process payment if no errors
    if (empty($errors)) {
        if ($method === 'Cash' && $order['subtotal_amount'] !== null) {
            header("Location: order_receipt.php?id=" . $orderId);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $whereSql = $method === 'Card'
                ? "id=:id AND payment_status='Unpaid'"
                : "id=:id AND payment_status='Unpaid' AND subtotal_amount IS NULL";

            // 1️⃣ Update order
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_method=:method,
                    payment_status=:payment_status,
                    promo_code=:pc,
                    subtotal_amount=:sub,
                    discount_amount=:disc,
                    total_price=:total
                WHERE $whereSql
            ");

            $stmt->bindValue(':method', $method);
            $stmt->bindValue(':payment_status', $method === 'Card' ? 'Paid' : 'Unpaid');
            $stmt->bindValue(':pc', $appliedPromoCode ?? null, $appliedPromoCode ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':sub', $subtotal);
            $stmt->bindValue(':disc', $discountAmount);
            $stmt->bindValue(':total', $finalTotal);
            $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);

            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("Order already processed or not found.");
            }

            // 2️⃣ Update stock only for card payments
            if ($method === 'Card') {
                $stmtQty = $pdo->prepare("
                    UPDATE menu_items
                    SET quantity = quantity - :qty
                    WHERE id=:id AND quantity >= :qty_check
                ");

                foreach ($orderItems as $item) {
                    $stmtQty->execute([
                        ':qty' => (int)$item['quantity'],
                        ':qty_check' => (int)$item['quantity'],
                        ':id'  => (int)$item['menu_item_id']
                    ]);

                    if ($stmtQty->rowCount() === 0) {
                        throw new RuntimeException("Insufficient stock for item ID: " . $item['menu_item_id']);
                    }
                }
            }

            $pdo->commit();

            // Redirect to receipt
            header("Location: order_receipt.php?id=" . $orderId);
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : "Payment processing failed. Please try again.";
        }
    }
}

?>

<h1 class="payment-title">Secure Payment</h1>

<div class="payment-box">
    <table style="width:87%; margin-bottom:20px;">
        <tr>
            <th style="text-align: right;">Order ID :</th>
            <td style="text-align: left;">#<?php echo $order['id']; ?></td>
        </tr>
        <tr>
            <th style="text-align: right;">Total Amount :</th>
            <td style="text-align: left;">Rs <?php echo number_format((float)$order['total_price'], 2); ?></td>
        </tr>
    </table>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" class="payment-form">
        <?php csrf_input(); ?>

        <h3 class="section-title">Please select your payment method and provide the necessary details.</h3>

        <div style="margin: 10px 0 18px;">
            <label for="promo_code" style="display:block;margin-bottom:6px;">Promo Code (optional)</label>
            <input type="text" id="promo_code" name="promo_code" placeholder="e.g. NEW10" maxlength="30"
                value="<?php echo isset($_POST['promo_code']) ? htmlspecialchars($_POST['promo_code']) : ''; ?>"
                style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid rgba(255,227,190,.25);background:#1c1c1c;color:#ffe3be;">
        </div>

        <div class="payment-methods">
            <label class="method-btn">
                <input type="radio" name="payment_method" value="Cash" checked onclick="showCash()">
                <span><i class="fas fa-money-bill"></i> Cash</span>
            </label>

            <label class="method-btn">
                <input type="radio" name="payment_method" value="Card" onclick="showCard()">
                <span><i class="fa-solid fa-credit-card"></i> Card</span>
            </label>
        </div>

        <div id="card-section" class="card-section">
            <!-- Card preview -->
            <div class="card-preview">
                <div class="card-front">
                    <div class="card-number" id="preview-number">#### #### #### ####</div>
                    <div class="card-holder"><span>Card Holder</span>
                        <div id="preview-name">YOUR NAME</div>
                    </div>
                    <div class="card-expiry"><span>Expires</span>
                        <div id="preview-expiry">MM/YY</div>
                    </div>
                </div>
            </div>

            <!-- Card inputs -->
            <div class="card-input-wrapper">
                <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19"
                    disabled>
                <img id="card-logo" src="" alt="Card Logo" class="card-logo">
            </div>

            <input type="text" id="card_name" name="card_name" placeholder="Card Holder Name" disabled>

            <div class="card-row">
                <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5" disabled>
                <input type="password" name="cvv" id="cvv" placeholder="CVV" maxlength="3" disabled>
            </div>
        </div>

        <button type="submit" class="pay-btn">Confirm Payment</button>
        <p class="order-info">We accept cash payments at the restaurant or card payments onlinely.</p>
    </form>
</div>

<script>
const cardNumberInput = document.getElementById("card_number");
const nameInput = document.getElementById("card_name");
const expiryInput = document.getElementById("expiry");
const cvvInput = document.getElementById("cvv");
const cardLogo = document.getElementById("card-logo");

// Show/hide card section
function showCard() {
    const cardSection = document.getElementById("card-section");
    cardSection.style.display = "flex";
    cardSection.classList.add("active");
    cardNumberInput.disabled = false;
    nameInput.disabled = false;
    expiryInput.disabled = false;
    cvvInput.disabled = false;
}

function showCash() {
    const cardSection = document.getElementById("card-section");
    cardSection.style.display = "none";
    cardSection.classList.remove("active");
    cardNumberInput.disabled = true;
    nameInput.disabled = true;
    expiryInput.disabled = true;
    cvvInput.disabled = true;
}

// --- CARD NUMBER ---
cardNumberInput.addEventListener("input", function() {
    let value = this.value.replace(/\D/g, "").substring(0, 16);
    this.value = value.match(/.{1,4}/g)?.join(" ") || "";

    // Card logo detection
    if (value.startsWith("4")) {
        cardLogo.src = "../assets/cards/vc.png";
        cardLogo.style.display = "block";
    } else if (value.startsWith("5")) {
        cardLogo.src = "../assets/cards/mc.png";
        cardLogo.style.display = "block";
    } else {
        cardLogo.style.display = "none";
    }

    document.getElementById("preview-number").innerText = this.value || "#### #### #### ####";

    // Real-time validation
    this.classList.toggle("invalid", value.length !== 16);
});

// --- CARD HOLDER NAME ---
nameInput.addEventListener("input", function() {
    this.value = this.value.replace(/[^a-zA-Z\s'-]/g, "").toUpperCase();
    document.getElementById("preview-name").innerText = this.value || "YOUR NAME";
    this.classList.toggle("invalid", this.value.trim().length < 2);
});

// --- EXPIRY MM/YY ---
expiryInput.addEventListener("input", function() {
    let value = this.value.replace(/\D/g, "").substring(0, 4);
    if (value.length > 2) {
        value = value.substring(0, 2) + "/" + value.substring(2);
    }
    this.value = value;
    document.getElementById("preview-expiry").innerText = this.value || "MM/YY";

    // Real-time validation
    this.classList.toggle("invalid", !/^(0[1-9]|1[0-2])\/\d{2}$/.test(this.value));
});

// --- CVV ---
cvvInput.addEventListener("input", function() {
    this.value = this.value.replace(/\D/g, "").substring(0, 3);
    this.classList.toggle("invalid", this.value.length !== 3);
});

// --- FORM VALIDATION ---
document.querySelector(".payment-form").addEventListener("submit", function(e) {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        e.preventDefault();
        alert("Please select a payment method.");
        return;
    }
    const paymentMethod = selectedMethod.value;

    // Skip card validation if Cash
    if (paymentMethod === "Cash") {
        return; // allow form to submit
    }

    // Card validation
    const cardNumber = cardNumberInput.value.replace(/\s/g, "");
    const cardName = nameInput.value.trim();
    const expiry = expiryInput.value;
    const cvv = cvvInput.value;

    let valid = true;

    // Card number
    if (cardNumber.length !== 16) {
        cardNumberInput.classList.add("invalid");
        valid = false;
    } else {
        cardNumberInput.classList.remove("invalid");
    }

    // Card holder name
    if (cardName.length < 2) {
        nameInput.classList.add("invalid");
        valid = false;
    } else {
        nameInput.classList.remove("invalid");
    }

    // Expiry MM/YY
    const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
    const [month, year] = expiry.split("/").map(x => parseInt(x));
    const now = new Date();
    const expiryDate = new Date(`20${year}`, month - 1, 1);
    expiryDate.setMonth(expiryDate.getMonth() + 1);
    expiryDate.setDate(expiryDate.getDate() - 1);

    if (!expiryRegex.test(expiry) || expiryDate < now) {
        expiryInput.classList.add("invalid");
        valid = false;
    } else {
        expiryInput.classList.remove("invalid");
    }

    // CVV
    if (cvv.length !== 3) {
        cvvInput.classList.add("invalid");
        valid = false;
    } else {
        cvvInput.classList.remove("invalid");
    }

    if (!valid) {
        e.preventDefault();
        alert("Please fix the highlighted fields.");
    }
});

showCash();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>