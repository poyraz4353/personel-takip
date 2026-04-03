<?php
require_once __DIR__ . '/config/session_manager.php';
SessionManager::start();

// Örnek: daha önceki aramaları session'dan al
$recentSearches = $_SESSION['recent_searches'] ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Arama</title>
<style>
    #suggestions { margin-top: 10px; }
</style>
</head>
<body>

<h2>Arama</h2>
<input type="text" id="searchInput" placeholder="Ara...">
<button id="clearSearches">Temizle</button>

<div id="suggestions">
    <?php foreach($recentSearches as $search): ?>
        <div><?php echo htmlspecialchars($search); ?></div>
    <?php endforeach; ?>
</div>

<script>
// Arama inputunu dinleyip recent searches session'a kaydetme
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        let term = searchInput.value.trim();
        if (!term) return;

        // Ajax ile aramayı kaydet
        fetch('api/save_recent_search.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ search: term })
        });

        // Görüntüle
        const div = document.createElement('div');
        div.textContent = term;
        document.getElementById('suggestions').prepend(div);

        searchInput.value = '';
    }
});

// Temizle butonu
document.getElementById('clearSearches').addEventListener('click', function() {
    fetch('api/clear_recent_searches.php', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('suggestions').innerHTML = '';
            console.log('Son aramalar temizlendi');
        } else {
            console.error('Hata:', data.error);
        }
    })
    .catch(err => console.error('Fetch hatası:', err));
});
</script>

</body>
</html>
