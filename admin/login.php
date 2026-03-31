<?php
// admin/login.php
// Separate admin login using admin table.
$pageClass = "admin-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/header.php';

$username = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admin WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            secure_session_regenerate();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: adashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}
?>
<div class="admin-login-wrapper">
    <form action="" method="post" class="form-card">
        <?php csrf_input(); ?>
        <h1><i class="fas fa-user-shield"></i><br><br>ADMIN LOGIN</h1>
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
            <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Username</label>
            <input required type="text" id="username" name="username"
                value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password</label>
            <input required type="password" id="password" name="password">
        </div>
        <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>