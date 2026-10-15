<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die('Missing reset token.');
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT id, name FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die('Invalid or expired reset link.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($new) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        $upd->bind_param("si", $hash, $user['id']);
        $upd->execute();
        $success = "Password reset successful. <a href='login.php'>Login now</a>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - MotoRepair Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f4f4f4; padding-top:80px;">
<div class="container" style="max-width:480px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-3">Reset Password</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button class="btn btn-warning w-100">Reset Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>