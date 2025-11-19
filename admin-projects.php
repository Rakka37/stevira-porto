<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

// require login
if (!isset($_SESSION['user_id'])) {
    header('Location: admin.php');
    exit;
}

// CSRF tokens
if (empty($_SESSION['csrf_projects'])) {
    $_SESSION['csrf_projects'] = bin2hex(random_bytes(16));
}

// --- biodata lookup (to make sidebar identical to admin-biodata.php) ---
$uploadDir = __DIR__ . '/uploads/';
$uploadUrlBase = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// get single biodata row
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$b = $stmt->fetch(PDO::FETCH_ASSOC);

// defaults for biodata (same fallback used in admin-biodata.php)
$defaults = [
    'nama' => 'Stevira Rachel Gabriella',
    'telepon' => '08883049119',
    'email' => 'steviragabriella@gmail.com',
    'linkedin' => 'linkedin.com/in/steviragabriella',
    'instagram' => '@stevirachel',
    'youtube' => 'LSMI UPN JATIM',
    'github' => '@SimmySim',
    'tiktok' => '@LSMI UPN JATIM',
    'foto' => null,
];

$data = $b ? array_merge($defaults, $b) : $defaults;

$avatar_url = (!empty($data['foto']) && file_exists($uploadDir . $data['foto'])) ? $uploadUrlBase . rawurlencode($data['foto']) : 'stevira.jpg';

// ---------------- projects config ----------------
$projUploadRel = 'uploads/';
$projSub = 'projects/';
$projFsDir = __DIR__ . '/' . $projUploadRel . $projSub;
if (!is_dir(__DIR__ . '/' . $projUploadRel)) mkdir(__DIR__ . '/' . $projUploadRel, 0755, true);
if (!is_dir($projFsDir)) mkdir($projFsDir, 0755, true);

$maxFileSize = 4 * 1024 * 1024;
$allowedExt = ['jpg','jpeg','png','webp','gif'];

// ensure projects table exists (same schema as projects.php)
$create_sql = "
CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) DEFAULT NULL,
  thumb VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  description TEXT,
  date_text VARCHAR(100) DEFAULT NULL,
  duration VARCHAR(100) DEFAULT NULL,
  doc_link VARCHAR(1024) DEFAULT NULL,
  team VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$pdo->exec($create_sql);

// flash helpers
function flash_set(string $k, string $v) { $_SESSION['_fl_'.$k] = $v; }
function flash_get(string $k) { $key = '_fl_'.$k; if(isset($_SESSION[$key])){ $v = $_SESSION[$key]; unset($_SESSION[$key]); return $v;} return null; }

function slugify(string $s): string {
    $s = preg_replace('/[^A-Za-z0-9\- ]+/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    return strtolower(trim($s,'-'));
}

// upload
function handle_upload(array $file, string $destDir, array $allowedExt, int $maxFileSize): ?string {
    if (empty($file) || !isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] > $maxFileSize) {
        throw new RuntimeException('Ukuran file terlalu besar (max ' . round($maxFileSize/1024/1024) . ' MB).');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        throw new RuntimeException('Format file tidak didukung. Gunakan: ' . implode(', ', $allowedExt));
    }
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        throw new RuntimeException('Gagal membuat folder upload.');
    }
    $safe = uniqid('proj_', true) . '.' . $ext;
    $dst = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe;
    if (!move_uploaded_file($file['tmp_name'], $dst)) {
        throw new RuntimeException('Gagal menyimpan file upload.');
    }
    return $safe;
}

function resolve_project_image(array $p, string $projFsDir, string $projUploadRel, string $projSub): string {
    $imageVal = trim((string)($p['image'] ?? ''));
    $thumbVal = trim((string)($p['thumb'] ?? ''));
    $baseImage = $imageVal !== '' ? basename($imageVal) : '';
    $baseThumb = $thumbVal !== '' ? basename($thumbVal) : '';

    if ($baseImage !== '') {
        $fs = $projFsDir . $baseImage;
        if (file_exists($fs)) return $projUploadRel . $projSub . rawurlencode($baseImage);
    }
    if ($baseThumb !== '') {
        $fs = $projFsDir . $baseThumb;
        if (file_exists($fs)) return $projUploadRel . $projSub . rawurlencode($baseThumb);
    }
    if ($imageVal !== '') {
        $fs = __DIR__ . '/' . ltrim($imageVal, '/');
        if (file_exists($fs)) return $imageVal;
    }
    if ($thumbVal !== '') {
        $fs = __DIR__ . '/' . ltrim($thumbVal, '/');
        if (file_exists($fs)) return $thumbVal;
    }
    if ($imageVal !== '') {
        $fs = $projFsDir . $imageVal;
        if (file_exists($fs)) return $projUploadRel . $projSub . rawurlencode($imageVal);
    }
    return '';
}

// POST actions
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_projects'], $_POST['csrf'])) {
            throw new RuntimeException('Permintaan tidak valid (CSRF).');
        }
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new RuntimeException('Judul wajib diisi.');
            $date_text = trim($_POST['date_text'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            $doc_link = trim($_POST['doc_link'] ?? '');
            $team = trim($_POST['team'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $saved = handle_upload($_FILES['file'] ?? [], $projFsDir, $allowedExt, $maxFileSize);
            $imageDb = $saved ?: null;
            $thumbDb = $saved ?: null;

            $slug = slugify($title) . '-' . substr(uniqid(),0,6);
            $stmt = $pdo->prepare("INSERT INTO projects (title,slug,thumb,image,description,date_text,duration,doc_link,team) VALUES (:title,:slug,:thumb,:image,:description,:date_text,:duration,:doc_link,:team)");
            $stmt->execute([
                ':title'=>$title, ':slug'=>$slug, ':thumb'=>$thumbDb, ':image'=>$imageDb,
                ':description'=>$description, ':date_text'=>$date_text, ':duration'=>$duration,
                ':doc_link'=>$doc_link, ':team'=>$team
            ]);
            flash_set('success','Project ditambahkan.');
            $_SESSION['csrf_projects'] = bin2hex(random_bytes(16));
            header('Location: admin-projects.php');
            exit;
        }

        if ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID tidak valid.');
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new RuntimeException('Judul wajib diisi.');
            $date_text = trim($_POST['date_text'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            $doc_link = trim($_POST['doc_link'] ?? '');
            $team = trim($_POST['team'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $cur = $pdo->prepare("SELECT * FROM projects WHERE id=:id");
            $cur->execute([':id'=>$id]);
            $curRow = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$curRow) throw new RuntimeException('Project tidak ditemukan.');

            $saved = handle_upload($_FILES['file'] ?? [], $projFsDir, $allowedExt, $maxFileSize);
            $imageDb = $saved ? $saved : $curRow['image'];
            $thumbDb = $saved ? $saved : $curRow['thumb'];

            if ($saved && !empty($curRow['image'])) {
                $oldFs = $projFsDir . $curRow['image'];
                if (file_exists($oldFs)) @unlink($oldFs);
            }

            $slug = slugify($title) . '-' . substr(uniqid(),0,6);
            $upd = $pdo->prepare("UPDATE projects SET title=:title,slug=:slug,thumb=:thumb,image=:image,description=:description,date_text=:date_text,duration=:duration,doc_link=:doc_link,team=:team WHERE id=:id");
            $upd->execute([
                ':title'=>$title, ':slug'=>$slug, ':thumb'=>$thumbDb, ':image'=>$imageDb,
                ':description'=>$description, ':date_text'=>$date_text, ':duration'=>$duration,
                ':doc_link'=>$doc_link, ':team'=>$team, ':id'=>$id
            ]);
            flash_set('success','Perubahan disimpan.');
            $_SESSION['csrf_projects'] = bin2hex(random_bytes(16));
            header('Location: admin-projects.php');
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID tidak valid.');
            $cur = $pdo->prepare("SELECT image,thumb FROM projects WHERE id=:id");
            $cur->execute([':id'=>$id]);
            $curRow = $cur->fetch(PDO::FETCH_ASSOC);
            if ($curRow) {
                if (!empty($curRow['image'])) {
                    $f = $projFsDir . $curRow['image'];
                    if (file_exists($f)) @unlink($f);
                }
                if (!empty($curRow['thumb']) && $curRow['thumb'] !== $curRow['image']) {
                    $f2 = $projFsDir . $curRow['thumb'];
                    if (file_exists($f2)) @unlink($f2);
                }
            }
            $pdo->prepare("DELETE FROM projects WHERE id=:id")->execute([':id'=>$id]);
            flash_set('success','Project dihapus.');
            $_SESSION['csrf_projects'] = bin2hex(random_bytes(16));
            header('Location: admin-projects.php');
            exit;
        }
    }
} catch (Throwable $e) {
    flash_set('error', $e->getMessage());
    if (!empty($_POST['action']) && $_POST['action'] === 'edit' && !empty($_POST['id'])) {
        header('Location: admin-projects.php?edit=' . (int)$_POST['id']);
    } else {
        header('Location: admin-projects.php');
    }
    exit;
}

// read projects
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// edit item if requested
$editItem = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id=:id");
        $stmt->execute([':id'=>$eid]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// flash
$success = flash_get('success');
$error = flash_get('error');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin - Projects</title>
<style>
 :root{
  --bg:#f2e79a;--panel:#3b3656;--accent:#ffeb3b;--card:#3c3763;
  --thead:#342c4a;--muted:#e8e8e8;--sb-w:300px;--maxw:1200px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:Inter,Arial,sans-serif}
body{min-height:100vh;background:var(--bg);display:flex;justify-content:center}
.page{width:100%;max-width:var(--maxw);padding:18px}

/* SIDEBAR (IDENTICAL to admin-biodata.php) */
.sidebar{
  width:var(--sb-w);background:var(--panel);color:#fff;padding:28px 22px;
  display:flex;flex-direction:column;align-items:center;gap:12px;
  border-radius:0 12px 12px 0;box-shadow:0 10px 30px #0002;
}
.avatar{width:140px;height:140px;border-radius:50%;overflow:hidden;border:6px solid var(--accent)}
.avatar img{width:100%;height:100%;object-fit:cover}
.user-short{font-weight:700;margin-top:10px}
.user-full{text-align:center;font-weight:600;margin-top:12px;padding-bottom:8px;border-bottom:3px solid var(--accent);width:100%}
nav ul{list-style:none;margin-top:18px}
nav li{padding:12px 8px}
nav a{color:#fff;text-decoration:none;position:relative}
nav a.active::after{content:"";position:absolute;left:0;bottom:-6px;width:100%;height:4px;background:var(--accent);border-radius:4px}

/* MAIN */
.main{padding-left:8px}
.title{font-size:1.5rem;font-weight:700}

/* CARD & FORM */
.card{
  background:var(--card);padding:18px;border-radius:12px;
  box-shadow:0 12px 40px #0002;margin-top:12px;
}
.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.field{background:#eee;padding:10px;border-radius:10px;color:#111}
.field input,.field textarea{width:100%;border:0;background:transparent;outline:none;font-size:.95rem;margin-bottom:6px}
.file-wrap{display:flex;gap:12px;align-items:center}
.btn-save{
  background:var(--accent);padding:10px 18px;border:0;font-weight:700;
  border-radius:10px;cursor:pointer;
}
.btn-cancel{background:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;color:#111;font-weight:700;display:inline-block;margin-left:12px}

/* TABLE-LIKE LIST (perubahan: daftar menyamping) */
.projects-table{
  width:100%;
  border-collapse:collapse;
  background:transparent;
  margin-top:10px;
  color:#111;
}
.projects-table thead th{
  background:var(--thead);
  color:#fff;
  padding:12px 14px;
  text-align:left;
  font-weight:800;
}
.projects-table tbody td{
  background:#fff;
  padding:14px;
  border-top:1px solid #eee;
  vertical-align:middle;
}
.proj-thumb{width:140px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #f0f0f0}
.proj-title{font-weight:800;font-size:1.05rem;color:#111;margin-bottom:6px}
.proj-desc{color:#444;font-size:0.95rem}
.proj-meta{font-size:0.92rem;color:#666;margin-top:6px}
.action-row{display:flex;gap:8px;align-items:center;justify-content:flex-end}
.btn-edit{background:#f8c146;border:0;padding:8px 12px;border-radius:8px;cursor:pointer}
.btn-del{background:#ff6b6b;border:0;padding:8px 12px;border-radius:8px;cursor:pointer;color:#fff}

/* small helper */
.small-muted{font-size:12px;color:#ddd;margin-top:6px}
.help{font-size:12px;color:#ddd;background:rgba(0,0,0,.08);padding:6px;border-radius:6px;margin-bottom:8px}

/* responsive */
.hamburger{display:none;font-size:22px;background:none;border:0;margin-bottom:10px;cursor:pointer}
.overlay{display:none;position:fixed;inset:0;background:#0006;opacity:0;transition:.2s;z-index:40}
.msg-success{background:#e6ffde;color:#0a6b00;padding:10px;border-radius:8px;margin-bottom:12px}
.msg-error{background:#ffecec;color:#b00000;padding:10px;border-radius:8px;margin-bottom:12px}

@media(min-width:761px){
  .sidebar{position:fixed;left:0;top:0;height:100vh}
  .page{margin-left:var(--sb-w)}
}
@media(max-width:760px){
  .sidebar{position:fixed;left:0;top:0;height:100vh;width:75vw;max-width:320px;transform:translateX(-110%);transition:.3s;border-radius:0}
  .sidebar.open{transform:translateX(0)}
  .hamburger{display:block}
  .overlay.show{display:block;opacity:1}
  #editCard{margin:0 auto;width:100%}
  .page{margin-left:0;padding:12px}
  .projects-table thead{display:none}
  .projects-table, .projects-table tbody, .projects-table tr, .projects-table td{display:block;width:100%}
  .projects-table tbody td{padding:10px}
  .proj-thumb{width:100%;height:200px}
}
</style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<div class="page">

  <!-- SIDEBAR (identical markup/content as admin-biodata.php) -->
  <aside class="sidebar" id="sidebar">
    <div class="avatar"><img src="<?=htmlspecialchars($avatar_url)?>" alt="avatar"></div>
    <div class="user-short" id="shortName"><?=htmlspecialchars(explode(' ', $data['nama'])[0] ?: 'Stevira')?></div>
    <div class="user-full" id="fullName"><?=htmlspecialchars($data['nama'])?></div>

    <nav>
      <ul>
        <li><a href="admin-biodata.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin-biodata.php' ? 'active' : '' ?>">Biodata</a></li>
        <li><a href="admin-projects.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin-projects.php' ? 'active' : '' ?>">Projects</a></li>
        <li><a href="admin-certificates.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin-certificates.php' ? 'active' : '' ?>">Certificates</a></li>
        <li><a href="admin-sharing.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin-sharing.php' ? 'active' : '' ?>">Sharing</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <button class="hamburger" id="hambMenu">☰</button>
    <div class="title">Edit Data Project</div>

    <?php if ($success): ?><div class="msg-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg-error"><?=htmlspecialchars($error)?></div><?php endif; ?>

    <div class="card" id="editCard">
      <form id="editForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_projects'])?>">
        <input type="hidden" name="action" value="<?= $editItem ? 'edit' : 'add' ?>">
        <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>

        <div class="help">Upload gambar (jpg/png/webp/gif). Maks 4MB. Edit tanpa upload baru akan mempertahankan gambar lama.</div>

        <div class="form-grid">
          <div class="field file-wrap">
            <label style="width:100%;display:block">
              <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none" id="fileInput">
              <button type="button" onclick="document.getElementById('fileInput').click()" style="background:#fff;border:0;padding:8px 12px;border-radius:12px;cursor:pointer">Pilih File</button>
              <span id="fileName" style="margin-left:12px;color:#333"><?= $editItem && $editItem['thumb'] ? htmlspecialchars(basename($editItem['thumb'])) : 'Tidak ada file yang dipilih' ?></span>
            </label>
          </div>

          <div class="field">
            <label style="display:block;color:#fff;margin-bottom:6px">Judul</label>
            <input id="fTitle" name="title" type="text" value="<?=htmlspecialchars($editItem['title'] ?? '')?>" placeholder="Judul Project">
          </div>

          <div class="field">
            <label style="display:block;color:#fff;margin-bottom:6px">Tanggal</label>
            <input id="fDate" name="date_text" type="text" value="<?=htmlspecialchars($editItem['date_text'] ?? '')?>" placeholder="dd mm yyyy">
          </div>

          <div class="field">
            <label style="display:block;color:#fff;margin-bottom:6px">Durasi</label>
            <input id="fDuration" name="duration" type="text" value="<?=htmlspecialchars($editItem['duration'] ?? '')?>" placeholder="Durasi (mis. 2 Bulan)">
          </div>

          <div class="field">
            <label style="display:block;color:#fff;margin-bottom:6px">Link</label>
            <input id="fDoc" name="doc_link" type="text" value="<?=htmlspecialchars($editItem['doc_link'] ?? '')?>" placeholder="Link Project (opsional)">
          </div>

          <div class="field">
            <label style="display:block;color:#fff;margin-bottom:6px">Tim</label>
            <input id="fTeam" name="team" type="text" value="<?=htmlspecialchars($editItem['team'] ?? '')?>" placeholder="Kolaborasi/Tim/Mandiri">
          </div>

          <div style="grid-column:1/ -1">
            <label style="display:block;color:#fff;margin-bottom:6px">Deskripsi</label>
            <div class="field" style="min-height:80px">
              <textarea id="fDesc" name="description" placeholder="Deskripsi Project" style="min-height:72px"><?=htmlspecialchars($editItem['description'] ?? '')?></textarea>
            </div>
          </div>
        </div>

        <div class="btn-wrap" style="text-align:center;margin-top:12px">
          <button type="submit" class="btn-save"><?= $editItem ? 'Simpan Perubahan' : 'Tambah Project' ?></button>
          <?php if ($editItem): ?><a class="btn-cancel" href="admin-projects.php">Batal</a><?php endif; ?>
        </div>

      </form>
    </div>

    <!-- PERBAIKAN: DAFTAR PROJECT MENJADI TABEL BARIS (MENYAMPING) -->
    <div class="card" style="margin-top:14px">
      <div class="small-muted">Daftar Project</div>

      <table class="projects-table" role="table" aria-label="Daftar project">
        <thead>
          <tr>
            <th style="width:180px">Gambar</th>
            <th>Judul</th>
            <th>Deskripsi</th>
            <th style="width:140px">Tanggal</th>
            <th style="width:120px">Durasi</th>
            <th style="width:140px">Tim</th>
            <th style="width:170px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($projects)): ?>
            <tr><td colspan="7" style="padding:18px;background:#fff;color:#666">Belum ada project.</td></tr>
          <?php else: foreach($projects as $p):
            $imgRel = resolve_project_image($p, $projFsDir, $projUploadRel, $projSub);
          ?>
          <tr>
            <td><img class="proj-thumb" src="<?=htmlspecialchars($imgRel ?: 'p-placeholder.png')?>" alt="<?=htmlspecialchars($p['title'])?>"></td>
            <td>
              <div class="proj-title"><?=htmlspecialchars($p['title'])?></div>
              <div class="proj-meta"><?=htmlspecialchars($p['team'] ?? '')?></div>
            </td>
            <td><div class="proj-desc"><?=nl2br(htmlspecialchars((string)$p['description']))?></div></td>
            <td><?=htmlspecialchars($p['date_text'] ?? '-')?></td>
            <td><?=htmlspecialchars($p['duration'] ?? '-')?></td>
            <td><?=htmlspecialchars($p['team'] ?? '-')?></td>
            <td>
              <div class="action-row">
                <a class="btn-edit" href="admin-projects.php?edit=<?= (int)$p['id'] ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Hapus project ini?');">
                  <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_projects'])?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn-del">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    </div>

  </main>
</div>

<script>
// file input UI
const fileInput = document.getElementById('fileInput');
const fileName = document.getElementById('fileName');
if(fileInput){
  fileInput.addEventListener('change', (e)=>{
    const f = e.target.files[0];
    fileName.textContent = f ? f.name : 'Tidak ada file yang dipilih';
  });
}

// Sidebar hamburger & overlay
const hamb = document.getElementById("hambMenu");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
if(hamb){
  hamb.onclick = () => {
    sidebar.classList.add("open");
    overlay.classList.add("show");
  };
}
if(overlay){
  overlay.onclick = () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  };
}
</script>
</body>
</html>
