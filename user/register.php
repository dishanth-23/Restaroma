<?php
// user/register.php
// Handles user registration with password hashing and input validation.
$pageClass = "user-page";
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/header.php';

$name = $email = $password = $confirm_password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    // Basic server-side validation
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already registered.';
        } else {
            // Hash the password and insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'user')");
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hash
            ]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<div class="user-login-wrapper">
    <form action="" method="post" class="form-card" novalidate>
        <?php csrf_input(); ?>
        <h1 class="page-title"><i class="fas fa-user"></i> Create Account</h1>
        <p style="text-align:center; color:#d1d5db; margin-bottom:1.5rem; letter-spacing:2px;">Join us and enjoy our
            premium dining experience</p>
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
            <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="form-group-register">
            <label for="name"><i class="fas fa-user"></i> Full Name</label>
            <input required type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
        </div>
        <div class="form-group-register">
            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
            <input required type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="form-group-register">
            <label for="password"><i class="fas fa-lock"></i> Password</label>
            <input required type="password" id="password" name="password" minlength="6">
        </div>
        <div class="form-group-register">
            <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
            <input required type="password" id="confirm_password" name="confirm_password" minlength="6">
        </div>
        <br>
        <button type="submit" class="form-btn"><i class="fas fa-sign-in-alt"></i> Register</button>
        <p class="register-link">Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>