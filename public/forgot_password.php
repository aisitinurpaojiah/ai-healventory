<?php
require_once "../config/database.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {

        $token = bin2hex(random_bytes(32));

        $update = $pdo->prepare("
            UPDATE users 
            SET reset_token = ?, reset_expired = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
            WHERE username=?
        ");
        $update->execute([$token, $username]);

        header("Location: reset_password.php?token=" . $token);
        exit;
    } else {
        $error = "Username tidak ditemukan!";
    }
}
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Lupa Kata Sandi</title>



</head>
<body>

<div class="auth-container">
    <div class="auth-box">

        <h2 class="auth-title">Reset Password</h2>
        <p class="auth-subtitle"><i class="bi bi-key"></i> Lupa Kata Sandi</p>

        <?php if ($error): ?>
            <div class="auth-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" class="auth-input" placeholder="Masukkan Username" required>
            <button type="submit" class="auth-btn">Kirim Link Reset</button>
        </form>

        <a href="login.php" class="auth-link">Kembali ke Login</a>

    </div>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>