<?php
// ajax_songs.php
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

// generate the HTML fragment for the playlist (same markup as index.php uses)
ob_start();
?>
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
        <div class="duration"><?php $m=floor($song['duration_seconds']/60); $s=$song['duration_seconds']%60; echo sprintf('%d:%02d',$m,$s); ?></div>
        <button class="btn play-btn" data-index="<?php echo $i; ?>">Play</button>
        <button class="btn fav-btn <?php echo $song['is_fav'] ? 'fav' : ''; ?>" data-song-id="<?php echo (int)$song['id']; ?>">
          <?php echo $song['is_fav'] ? '♥' : '♡'; ?>
        </button>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php
$html = ob_get_clean();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['html'=>$html, 'playlist'=>$playlist], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
