<?php
$pageClass = "udashboard-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireUserLogin();
require __DIR__ . '/../includes/header.php';

$errors = [];

$date = '';
$time = '';
$guests = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $date = $_POST['reservation_date'] ?? '';
    $time = $_POST['reservation_time'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (!$date) $errors[] = 'Reservation date is required.';
    if (!$time) $errors[] = 'Reservation time is required.';
    if ($guests < 1) $errors[] = 'Number of guests must be at least 1.';
    if ($guests > 10) $errors[] = 'Maximum reservation limit is 10 guests.';
    if (strlen($message) > 300) $errors[] = "Message must be under 300 characters.";

    // Duplicate check
    if (empty($errors)) {
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM reservations 
            WHERE user_id = :uid 
            AND reservation_date = :d 
            AND reservation_time = :t
        ");
        $check->execute([
            ':uid' => $_SESSION['user_id'],
            ':d' => $date,
            ':t' => $time
        ]);

        if ($check->fetchColumn() > 0) {
            $errors[] = "You already have a reservation for this time.";
        }
    }

    // Insert
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO reservations 
            (user_id, reservation_date, reservation_time, guests, message, status) 
            VALUES (:uid, :d, :t, :g, :m, 'Pending')
        ");

        $stmt->execute([
            ':uid' => $_SESSION['user_id'],
            ':d'   => $date,
            ':t'   => $time,
            ':g'   => $guests,
            ':m'   => $message
        ]);

        // Redirect (VERY IMPORTANT)
        header("Location: reservation.php?success=1");
        exit;
    }
}
?>

<div class="reservation-wrapper">
    <h1 class="page-title">Table Reservation</h1>

    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <p>Your table reservation has been submitted successfully.<br>
            Our restaurant Admin will confirm your booking shortly.</p>
        </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if ($errors): ?>
        <div class="alert-danger">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" class="form-card">
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
            Confirm Reservation
        </button>

    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>