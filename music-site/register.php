<?php
require_once __DIR__ . '/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = "Username and password are required.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Username already taken.";
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $role = 'user';
        $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, role) VALUES (?,?,?)");
        $stmt->bind_param("sss", $username, $hash, $role);
        if ($stmt->execute()) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors[] = "Registration failed.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register • Music</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="auth-card">
  <h2>Create account</h2>
  <?php if ($errors): ?><div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div><?php endif; ?>
  <form method="post">
    <label>Username
      <input type="text" name="username" required>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button type="submit">Register</button>
  </form>
  <p class="muted">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
