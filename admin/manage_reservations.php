<?php
// admin/manage_reservations.php
// Admin can view reservations and update status.
$pageClass = "admindash-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
requireAdminLogin();
require __DIR__ . '/../includes/header.php';

$statuses = ['Pending', 'Approved', 'Cancelled'];
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['status'])) {
    verify_csrf_or_die();
    $resId = (int)$_POST['reservation_id'];
    $status = $_POST['status'];

    if (in_array($status, $statuses, true)) {
        $stmt = $pdo->prepare("UPDATE reservations SET status = :s WHERE id = :id");
        $stmt->execute([
            ':s' => $status,
            ':id' => $resId
        ]);
        $success = 'Reservation status updated.';
    }
}

// Fetch reservations with user info
$sql = "SELECT r.id, r.reservation_date, r.reservation_time, r.guests, r.status, u.name AS user_name
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.reservation_date DESC, r.reservation_time DESC";
$reservations = $pdo->query($sql)->fetchAll();
?>
<h1>Manage Reservations</h1>

<?php if ($success): ?>
<div class="alert alert-success">
    <p><?php echo htmlspecialchars($success); ?></p>
</div>
<?php endif; ?>

<table class="rtable">
    <thead>
        <tr>
            <th>#</th>
            <th>User</th>
            <th>Date</th>
            <th>Time</th>
            <th>Guests</th>
            <th>Status</th>
            <th>Update Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reservations as $r): ?>

        <?php
        $isUpcoming = strtotime($r['reservation_date']) >= strtotime(date('Y-m-d'));
        $status = strtolower($r['status']);
        $statusClass = "status-" . $status;
        ?>

        <tr class="<?php echo $isUpcoming ? 'upcoming-reservation' : ''; ?>">

            <td data-label="#"> <?php echo (int)$r['id']; ?> </td>

            <td data-label="User">
                <?php echo htmlspecialchars($r['user_name']); ?>
            </td>

            <td data-label="Date">
                <?php echo htmlspecialchars($r['reservation_date']); ?>
            </td>

            <td data-label="Time">
                <?php echo htmlspecialchars($r['reservation_time']); ?>
            </td>

            <td data-label="Guests">
                <?php echo (int)$r['guests']; ?>
            </td>

            <td data-label="Status">
                <span class="status-badge <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($r['status']); ?>
                </span>
            </td>

            <td data-label="Update Status">
                <form method="post" class="status-update">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="reservation_id" value="<?php echo (int)$r['id']; ?>">

                    <select name="status">
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php if ($r['status'] === $s) echo 'selected'; ?>>
                            <?php echo $s; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="save-btn">Save</button>
                </form>
            </td>

        </tr>

        <?php endforeach; ?>
    </tbody>
</table>

<?php
require __DIR__ . '/../includes/footer.php';
?>