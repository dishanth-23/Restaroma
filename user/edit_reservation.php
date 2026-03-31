<?php
$pageClass = "udashboard-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$errors = [];

// Fetch existing reservation safely
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE id = :id AND user_id = :uid
");
$stmt->execute([
    ':id' => $id,
    ':uid' => $userId
]);

$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    die("Reservation not found.");
}

// Null-safe assignment
$date = $reservation['reservation_date'] ?? '';
$time = $reservation['reservation_time'] ?? '';
$guests = (int)($reservation['guests'] ?? 1);
$message = $reservation['message'] ?? '';

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $date = $_POST['reservation_date'] ?? '';
    $time = $_POST['reservation_time'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (!$date) $errors[] = "Reservation date is required.";
    if (!$time) $errors[] = "Reservation time is required.";
    if ($guests < 1 || $guests > 10) $errors[] = "Number of guests must be between 1 and 10.";
    if (strlen($message) > 300) $errors[] = "Message must be under 300 characters.";

    // Optional: Check duplicate time for same user
    if (empty($errors)) {
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM reservations
            WHERE user_id = :uid AND reservation_date = :d AND reservation_time = :t AND id != :id
        ");
        $check->execute([
            ':uid' => $userId,
            ':d' => $date,
            ':t' => $time,
            ':id' => $id
        ]);

        if ($check->fetchColumn() > 0) {
            $errors[] = "You already have a reservation for this date and time.";
        }
    }

    // Update reservation if no errors
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE reservations
            SET reservation_date = :d,
                reservation_time = :t,
                guests = :g,
                message = :m
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':d' => $date,
            ':t' => $time,
            ':g' => $guests,
            ':m' => $message,
            ':id' => $id,
            ':uid' => $userId
        ]);

        header("Location: udashboard.php?updated=1");
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="reservation-wrapper">
    <h1 class="page-title">Edit Reservation</h1>

    <!-- Errors -->
    <?php if ($errors): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-card">
        <?php csrf_input(); ?>
        <div class="form-group">
            <label for="reservation_date">Reservation Date</label>
            <input type="date" id="reservation_date" name="reservation_date"
                value="<?php echo htmlspecialchars($date); ?>"
                min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label for="reservation_time">Reservation Time</label>
            <input type="time" id="reservation_time" name="reservation_time"
                value="<?php echo htmlspecialchars($time); ?>" required>
        </div>

        <div class="form-group">
            <label for="guests">Number of Guests</label><br>
            <small style="color:#c6a75e;">Maximum 10 guests per Table</small>
            <input type="number" id="guests" name="guests" min="1" max="10"
                value="<?php echo htmlspecialchars($guests); ?>" required>
        </div>

        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4"
                placeholder="Write your Message here..."><?php echo htmlspecialchars($message); ?></textarea>
        </div>

        <button type="submit" class="form-btn"
                onclick="this.disabled=true; this.form.submit();">
            Update Reservation
        </button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>