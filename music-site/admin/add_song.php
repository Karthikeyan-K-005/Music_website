<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $duration = intval($_POST['duration_seconds'] ?? 0);
    $genre = $_POST['genre'] ?? '';
    $audio_url = trim($_POST['audio_url'] ?? '');
    $cover_url = trim($_POST['cover_url'] ?? '');

    if ($title === '' || $artist === '' || $duration <= 0 || $audio_url === '' || $cover_url === '') {
        $errors[] = "All fields are required and duration must be > 0.";
    }
    if (!in_array($genre, ['Melody','Beat','Phonk'], true)) $errors[] = "Invalid genre.";

    if (empty($errors)) {
        $stmt = $mysqli->prepare("INSERT INTO songs (title, artist, duration_seconds, genre, audio_url, cover_url) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssisss", $title, $artist, $duration, $genre, $audio_url, $cover_url);
        if ($stmt->execute()) $success = true; else $errors[] = "DB error: " . $mysqli->error;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Song • Admin</title>
<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<header class="topbar">
  <div class="brand">Music Admin</div>
  <nav>
    <a href="../index.php">Site</a>
    <a href="../logout.php">Logout</a>
  </nav>
</header>
<main class="container">
  <h2>Add New Song</h2>
  <?php if ($success): ?><div class="success">Song added successfully.</div><?php endif; ?>
  <?php if ($errors): ?><div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div><?php endif; ?>
  <form method="post" class="card" novalidate>
    <div class="form-grid">
      <label>
        Title
        <input type="text" name="title" required>
      </label>
      <label>
        Artist
        <input type="text" name="artist" required>
      </label>
      <label>
        Duration (seconds)
        <input type="number" name="duration_seconds" min="1" required>
      </label>
      <label>
        Genre
        <select name="genre" required>
          <option value="Melody">Melody</option>
          <option value="Beat">Beat</option>
          <option value="Phonk">Phonk</option>
        </select>
      </label>

      <label class="full">
        Audio URL (cloud)
        <input type="url" name="audio_url" required>
      </label>
      <label class="full">
        Cover Image URL (cloud)
        <input type="url" name="cover_url" required>
      </label>
    </div>

    <div style="margin-top:14px;">
      <button type="submit">Add Song</button>
    </div>
  </form>
</main>
</body>
</html>
