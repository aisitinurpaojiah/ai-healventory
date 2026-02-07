<?php
require_once "../config/database.php";

$token = $_GET['token'] ?? '';
$error = "";

// Cek validitas token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expired > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("<h2>Token tidak valid atau sudah kedaluwarsa.</h2>");
}

// Jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm'];

    if ($pass1 !== $pass2) {
        $error = "Password tidak sama!";
    } else {

        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        // Update password dan hapus token
        $update = $pdo->prepare("
            UPDATE users SET password=?, reset_token=NULL, reset_expired=NULL
            WHERE id=?
        ");
        $update->execute([$hash, $user['id']]);

        // Redirect langsung ke login
        header("Location: login.php?reset=success");
        exit;
    }
}
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ganti Password</title>

</head>
<body>

<div class="auth-container">
    <div class="auth-box">

        <h2 class="auth-title">Ganti Password</h2>
        <p class="auth-subtitle"><i class="bi bi-lock"></i> Masukkan Password Baru</p>

        <?php if ($error): ?>
            <div class="auth-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="password" class="auth-input" placeholder="Password Baru" required>
            <input type="password" name="confirm" class="auth-input" placeholder="Konfirmasi Password" required>
            <button type="submit" class="auth-btn">Simpan Password Baru</button>
        </form>

        <a href="login.php" class="auth-link">Kembali ke Login</a>

    </div>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>