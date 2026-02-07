<?php
session_start();
require_once '../config/database.php';

// proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];

        header("Location: index.php");
        exit;

    } else {
        $error = "Username atau Password salah.";
    }
}

include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-box">

        <h2 class="login-title">Healventory</h2>
        <p class="login-subtitle"><i class="bi bi-person"></i> Masuk Ke Akun Anda</p>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <!-- LINK LUPA PASSWORD -->
        <a href="forgot_password.php" class="forgot-password">Lupa Sandi ?</a>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
