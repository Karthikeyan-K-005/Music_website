<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

$user = current_user();
$user_id = $user['id'];
$genre = $_GET['genre'] ?? 'All';
$showFav = isset($_GET['fav']);
if ($showFav) {
    $sql = "SELECT s.*, 1 AS is_fav
            FROM songs s
            JOIN favorites f ON f.song_id = s.id AND f.user_id = ?
            ORDER BY s.created_at DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
} elseif (in_array($genre, ['Melody','Beat','Phonk'], true)) {
    $sql = "SELECT s.*, IF(f.user_id IS NULL, 0, 1) AS is_fav
            FROM songs s
            LEFT JOIN favorites f ON f.song_id = s.id AND f.user_id = ?
            WHERE s.genre = ?
            ORDER BY s.created_at DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $user_id, $genre);
} else {
    $sql = "SELECT s.*, IF(f.user_id IS NULL, 0, 1) AS is_fav
            FROM songs s
            LEFT JOIN favorites f ON f.song_id = s.id AND f.user_id = ?
            ORDER BY s.created_at DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$res = $stmt->get_result();
$songs = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$playlist = array_map(function($s){
  return [
    'id'=>(int)$s['id'],
    'title'=>$s['title'],
    'artist'=>$s['artist'],
    'duration'=>(int)$s['duration_seconds'],
    'genre'=>$s['genre'],
    'audio_url'=>$s['audio_url'],
    'cover_url'=>$s['cover_url'],
    'is_fav'=>(int)$s['is_fav']
  ];
}, $songs);

function fmt($sec){ $m=floor($sec/60); $s=$sec%60; return sprintf('%d:%02d',$m,$s); }
function active($cond){ return $cond ? ' class="active"' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Music Player</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="topbar">
  <div class="brand">Ketufy</div>
  <nav>
    <a href="index.php" class="ajax-link"<?php echo active(!$showFav && $genre==='All'); ?>>All</a>
    <a href="index.php?genre=Melody" class="ajax-link"<?php echo active(!$showFav && $genre==='Melody'); ?>>Melody</a>
    <a href="index.php?genre=Beat" class="ajax-link"<?php echo active(!$showFav && $genre==='Beat'); ?>>Beat</a>
    <a href="index.php?genre=Phonk" class="ajax-link"<?php echo active(!$showFav && $genre==='Phonk'); ?>>Phonk</a>
    <a href="index.php?fav=1" class="ajax-link"<?php echo active($showFav); ?>>Favorites</a>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
      <a href="admin/add_song.php">Add Song</a>
    <?php endif; ?>
    <span class="spacer"></span>
    <span class="user">Hi, <?php echo htmlspecialchars($user['username']); ?></span>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main class="container grid">
  <div id="content" class="playlist">
    <h2>Playlist <?php echo ($genre!=='All' && !$showFav) ? '• '.htmlspecialchars($genre) : ($showFav ? '• Favorites' : ''); ?></h2>

    <?php if (empty($songs)): ?>
      <p class="muted">No songs found.</p>
    <?php else: ?>
      <?php foreach ($songs as $i => $song): ?>
        <div class="song-row" data-index="<?php echo $i; ?>">
          <img src="<?php echo htmlspecialchars($song['cover_url']); ?>" alt="cover" class="cover">
          <div class="meta">
            <div class="title"><?php echo htmlspecialchars($song['title']); ?></div>
            <div class="sub"><?php echo htmlspecialchars($song['artist']); ?> • <?php echo htmlspecialchars($song['genre']); ?></div>
          </div>
          <div class="duration"><?php echo fmt((int)$song['duration_seconds']); ?></div>
          <button class="btn play-btn" data-index="<?php echo $i; ?>">Play</button>
          <button class="btn fav-btn <?php echo $song['is_fav'] ? 'fav' : ''; ?>" data-song-id="<?php echo (int)$song['id']; ?>">
            <?php echo $song['is_fav'] ? '♥' : '♡'; ?>
          </button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <section class="player card" id="player-section">
    <div class="player-main empty-state">
      <img id="player-cover" src="assets/default-cover.jpg" alt="cover" class="player-cover default">
      <div class="player-info">
        <div id="player-title" class="player-title">No song selected</div>
        <div id="player-artist" class="player-artist"></div>
      </div>
    </div>

    <audio id="audio" preload="none"></audio>

    <div class="controls">
      <button id="prevBtn" class="btn icon-btn">⏮</button>
      <button id="rewindBtn" class="btn icon-btn" title="Back 10s">⏮ 10s</button>
      <button id="playPauseBtn" class="btn icon-btn primary round" aria-label="Play/Pause">▶</button>
      <button id="forwardBtn" class="btn icon-btn" title="Forward 10s">10s ⏭</button>
      <button id="nextBtn" class="btn icon-btn">⏭</button>
    </div>

    <div class="progress-wrap">
      <span id="currentTime">0:00</span>
      <input type="range" id="seekBar" min="0" value="0" step="1">
      <span id="totalTime">0:00</span>
    </div>

    <div class="volume-wrap">
      <label for="volume">Volume</label>
      <input type="range" id="volume" min="0" max="1" step="0.01" value="1">
    </div>
  </section>
</main>

<script>
window.initialPlaylist = <?php echo json_encode($playlist, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="assets/js/script.js"></script>
</body>
</html>
