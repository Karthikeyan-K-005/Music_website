<?php
require_once __DIR__ . '/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // ✅ Hardcoded admin login
    if ($username === "admin" && $password === "admin123") {
        $_SESSION['user'] = ['id' => 0, 'username' => 'admin', 'role' => 'admin'];
        header('Location: index.php');
        exit;
    }

    // ✅ Otherwise check database
    $stmt = $mysqli->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $uname, $hash, $role);
    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user'] = ['id' => $id, 'username' => $uname, 'role' => $role];
        header('Location: index.php');
        exit;
    } else {
        $errors[] = "Invalid credentials.";
    }
    $stmt->close();
}
$registered = isset($_GET['registered']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login • Music</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="auth-card">
  <h2>Welcome back</h2>
  <?php if ($registered): ?><div class="success">Registration successful. Please log in.</div><?php endif; ?>
  <?php if ($errors): ?><div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div><?php endif; ?>
  <form method="post">
    <label>Username
      <input type="text" name="username" required>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button type="submit">Login</button>
  </form>
  <p class="muted">No account? <a href="register.php">Register</a></p>
</div>
</body>
</html>
