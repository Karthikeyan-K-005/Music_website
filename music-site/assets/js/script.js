// assets/js/script.js
(function(){
  const audio = document.getElementById('audio');
  const cover = document.getElementById('player-cover');
  const titleEl = document.getElementById('player-title');
  const artistEl = document.getElementById('player-artist');
  const playPauseBtn = document.getElementById('playPauseBtn');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const rewindBtn = document.getElementById('rewindBtn');
  const forwardBtn = document.getElementById('forwardBtn');
  const seekBar = document.getElementById('seekBar');
  const currentTimeEl = document.getElementById('currentTime');
  const totalTimeEl = document.getElementById('totalTime');
  const volume = document.getElementById('volume');
  const contentEl = document.getElementById('content');

  // playlist is a global var; use window.playlist for safety
  window.playlist = window.initialPlaylist || [];
  let currentIndex = -1;
  let isSeeking = false;

  function fmtTime(sec) {
    sec = Math.max(0, Math.floor(sec || 0));
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  }

  function clamp(v, lo, hi){ return Math.max(lo, Math.min(hi, v)); }

  // set playlist from server data (AJAX)
  function setPlaylist(newPlaylist){
    const prevSrc = audio.src || '';
    window.playlist = Array.isArray(newPlaylist) ? newPlaylist : [];
    // try to preserve currently playing track: find index matching audio.src
    if (prevSrc) {
      const idx = window.playlist.findIndex(p => {
        // compare normalized URLs
        return p.audio_url === prevSrc || p.audio_url === (new URL(prevSrc)).href;
      });
      currentIndex = idx >= 0 ? idx : -1;
    } else {
      currentIndex = -1;
    }
  }

  function loadTrack(i) {
    if (!window.playlist || window.playlist.length === 0) return;
    currentIndex = (i + window.playlist.length) % window.playlist.length;
    const t = window.playlist[currentIndex];
    audio.src = t.audio_url;
    titleEl.textContent = t.title;
    artistEl.textContent = `${t.artist} • ${t.genre}`;
    cover.src = t.cover_url;
    seekBar.max = t.duration || 0;
    seekBar.value = 0;
    currentTimeEl.textContent = '0:00';
    totalTimeEl.textContent = fmtTime(t.duration || 0);
    const pm = document.querySelector('.player-main');
    if (pm) pm.classList.remove('empty-state');
  }

  function playCurrent() {
    if (currentIndex === -1) loadTrack(0);
    audio.play().then(()=>{ 
      playPauseBtn.innerHTML = '<span class="icon">⏸</span>';
      playPauseBtn.setAttribute('aria-pressed','true');
    }).catch(()=>{});
  }

  function pauseCurrent() { 
    audio.pause(); 
    playPauseBtn.innerHTML = '<span class="icon">▶</span>';
    playPauseBtn.setAttribute('aria-pressed','false');
  }

  // bind player controls
  playPauseBtn.addEventListener('click', () => {
    if (!audio.src) loadTrack(0);
    if (audio.paused) playCurrent(); else pauseCurrent();
  });
  prevBtn.addEventListener('click', () => {
    if (!window.playlist || window.playlist.length === 0) return;
    loadTrack(currentIndex <= 0 ? window.playlist.length - 1 : currentIndex - 1);
    playCurrent();
  });
  nextBtn.addEventListener('click', () => {
    if (!window.playlist || window.playlist.length === 0) return;
    loadTrack(currentIndex + 1);
    playCurrent();
  });

  // 10s rewind / forward
  rewindBtn && rewindBtn.addEventListener('click', () => {
    if (!audio.src) return;
    const target = clamp((audio.currentTime || 0) - 10, 0, audio.duration || 0);
    audio.currentTime = Math.floor(target);
    seekBar.value = Math.floor(audio.currentTime || 0);
    currentTimeEl.textContent = fmtTime(audio.currentTime || 0);
  });

  forwardBtn && forwardBtn.addEventListener('click', () => {
    if (!audio.src) return;
    const target = clamp((audio.currentTime || 0) + 10, 0, audio.duration || 0);
    audio.currentTime = Math.floor(target);
    seekBar.value = Math.floor(audio.currentTime || 0);
    currentTimeEl.textContent = fmtTime(audio.currentTime || 0);
  });

  audio.addEventListener('timeupdate', () => {
    if (isSeeking) return;
    seekBar.value = Math.floor(audio.currentTime || 0);
    currentTimeEl.textContent = fmtTime(audio.currentTime || 0);
  });

  seekBar.addEventListener('input', () => { isSeeking = true; });
  seekBar.addEventListener('change', () => {
    audio.currentTime = Number(seekBar.value || 0);
    isSeeking = false;
  });

  volume.addEventListener('input', () => { audio.volume = Number(volume.value); });

  audio.addEventListener('ended', () => {
    if (!window.playlist || window.playlist.length === 0) return;
    loadTrack(currentIndex + 1);
    playCurrent();
  });

  // function to bind play buttons & favorite buttons (call after AJAX loads)
  function bindListButtons() {
    // play buttons
    document.querySelectorAll('.play-btn').forEach(btn => {
      // remove previous handler to avoid duplicates
      btn.replaceWith(btn.cloneNode(true));
    });
    document.querySelectorAll('.play-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = Number(btn.dataset.index);
        loadTrack(idx);
        playCurrent();
      });
    });

    // fav buttons
    document.querySelectorAll('.fav-btn').forEach(btn => {
      btn.replaceWith(btn.cloneNode(true));
    });
    document.querySelectorAll('.fav-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const songId = Number(btn.dataset.songId);
        const isFav = btn.classList.contains('fav');
        const action = isFav ? 'remove' : 'add';
        try {
          const res = await fetch('favorite.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `song_id=${encodeURIComponent(songId)}&action=${encodeURIComponent(action)}`
          });
          const text = await res.text();
          if (text === 'added') { btn.classList.add('fav'); btn.textContent = '♥'; }
          else if (text === 'removed') { btn.classList.remove('fav'); btn.textContent = '♡'; }
        } catch(e) { console.error(e); }
      });
    });
  }

  // AJAX navigation: fetch ajax_songs.php and replace #content
  async function ajaxLoad(url, pushState = true) {
    try {
      // transform index.php?genre=... into ajax_songs.php?genre=...
      const u = new URL(url, window.location.origin);
      const params = new URLSearchParams(u.search);
      const target = 'ajax_songs.php?' + params.toString();
      const res = await fetch(target, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      contentEl.innerHTML = data.html;
      setPlaylist(data.playlist);
      bindListButtons();
      // update nav active states
      document.querySelectorAll('.topbar nav a').forEach(a => a.classList.remove('active'));
      // set active on clicked link if it matches the requested URL pathname+search
      history.replaceState(null, '', url);
      if (pushState) history.pushState({url: url}, '', url);
    } catch (e) {
      console.error('Failed to load content', e);
    }
  }

  // intercept clicks on links with .ajax-link
  document.addEventListener('click', function(e){
    const a = e.target.closest && e.target.closest('a.ajax-link');
    if (!a) return;
    e.preventDefault();
    ajaxLoad(a.href, true);
  });

  // handle back/forward browser buttons
  window.addEventListener('popstate', (ev) => {
    const url = location.href;
    ajaxLoad(url, false);
  });

  // keyboard accessibility for play button
  playPauseBtn.addEventListener('keydown', (e) => {
    if (e.key === ' ' || e.key === 'Enter') {
      e.preventDefault();
      playPauseBtn.click();
    }
  });

  // initial binding on page load
  document.addEventListener('DOMContentLoaded', () => {
    // set initial playlist from server-rendered window.initialPlaylist
    if (window.initialPlaylist) setPlaylist(window.initialPlaylist);
    bindListButtons();
  });

  // expose a couple helpers in case you want them in console
  window.appPlayer = {
    setPlaylist,
    bindListButtons,
    ajaxLoad
  };
})();
