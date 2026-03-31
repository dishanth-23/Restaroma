<?php
// user/login.php
// Handles user login using password_verify and secure sessions.
$pageClass = "user-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/header.php';

$email = '';
$errors = [];
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        // Fetch user by email
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            secure_session_regenerate();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            header('Location: udashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>

<div class="user-login-wrapper">
    <form action="" method="post" class="form-card" novalidate>
        <?php csrf_input(); ?>
        <h1 class="page-title"><i class="fas fa-user-circle"></i> Welcome Back</h1>
        <p style="text-align:center; color:#d1d5db; margin-bottom:1.5rem; letter-spacing:2px;">Sign in to continue your
            dining experience</p>
        <?php if ($registered): ?>
        <div class="alert alert-success">
            <p>Registration successful. Please login.</p>
        </div>
        <?php endif; ?>
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
            <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="email"><i class="fas fa-envelope"></i> Email</label><br>
            <input required type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password</label><br>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="form-btn"><i class="fas fa-sign-in-alt"></i> Login</button>
        <p class="register-link">New User? <a href="register.php"> Register here</a></p>
    </form>
</div>
<?php
require __DIR__ . '/../includes/footer.php';
?>