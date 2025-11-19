<?php
// admin-sharing.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

// proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: admin.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_sharing'])) {
    $_SESSION['csrf_sharing'] = bin2hex(random_bytes(16));
}

// shared uploads (biodata uses uploads/ too)
$uploadDir = __DIR__ . '/uploads/';
$uploadUrlBase = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// biodata untuk sidebar (sama persis dg admin-biodata.php)
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$b = $stmt->fetch(PDO::FETCH_ASSOC);
$defaults = [
    'nama' => 'Stevira Rachel Gabriella',
    'telepon' => '08883049119',
    'email' => 'steviragabriella@gmail.com',
    'linkedin' => 'linkedin.com/in/stevira',
    'instagram' => '@stevirachel',
    'youtube' => 'LSMI UPN JATIM',
    'github' => '@SimmySim',
    'tiktok' => '@LSMI UPN JATIM',
    'foto' => null,
];
$data = $b ? array_merge($defaults, $b) : $defaults;
$avatar_url = (!empty($data['foto']) && file_exists($uploadDir . $data['foto'])) ? $uploadUrlBase . rawurlencode($data['foto']) : 'stevira.jpg';

// sharing uploads dir
$shareRel = 'uploads/';
$shareSub = 'sharing/';
$shareFsDir = __DIR__ . '/' . $shareRel . $shareSub;
if (!is_dir(__DIR__ . '/' . $shareRel)) mkdir(__DIR__ . '/' . $shareRel, 0755, true);
if (!is_dir($shareFsDir)) mkdir($shareFsDir, 0755, true);

$maxFileSize = 3 * 1024 * 1024; // 3MB
$allowedExt = ['jpg','jpeg','png','gif','webp'];

// create table if not exists
$create_sql = "
CREATE TABLE IF NOT EXISTS sharing (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  link VARCHAR(1024) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$pdo->exec($create_sql);

// flash helpers
function flash_set(string $k, string $v) { $_SESSION['_fl_'.$k] = $v; }
function flash_get(string $k) { $key = '_fl_'.$k; if(isset($_SESSION[$key])){ $v = $_SESSION[$key]; unset($_SESSION[$key]); return $v;} return null; }

// upload helper
function handle_upload(array $file, string $destDir, array $allowedExt, int $maxFileSize): ?string {
    if (empty($file) || !isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload error code: ' . $file['error']);
    if ($file['size'] > $maxFileSize) throw new RuntimeException('Ukuran file terlalu besar (max ' . round($maxFileSize/1024/1024) . ' MB).');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) throw new RuntimeException('Format file tidak didukung.');
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) throw new RuntimeException('Gagal membuat folder upload.');
    $safe = uniqid('share_', true) . '.' . $ext;
    $dst = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe;
    if (!move_uploaded_file($file['tmp_name'], $dst)) throw new RuntimeException('Gagal menyimpan file upload.');
    return $safe;
}

function resolve_share_image(array $r, string $shareFsDir, string $shareRel, string $shareSub): string {
    $img = trim((string)($r['image'] ?? ''));
    if ($img === '') return '';
    $base = basename($img);
    $fs = $shareFsDir . $base;
    if (file_exists($fs)) return $shareRel . $shareSub . rawurlencode($base);
    $fs2 = __DIR__ . '/' . ltrim($img, '/');
    if (file_exists($fs2)) return $img;
    return '';
}

// handle POST (add/edit/delete)
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_sharing'], $_POST['csrf'])) {
            throw new RuntimeException('Permintaan tidak valid (CSRF).');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new RuntimeException('Judul wajib diisi.');
            $link = trim($_POST['link'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            $saved = handle_upload($_FILES['image'] ?? [], $shareFsDir, $allowedExt, $maxFileSize);
            $imgDb = $saved ?: null;

            $ins = $pdo->prepare("INSERT INTO sharing (title,image,description,link) VALUES (:title,:image,:description,:link)");
            $ins->execute([':title'=>$title, ':image'=>$imgDb, ':description'=>$desc, ':link'=>$link]);

            flash_set('success','Konten berhasil ditambahkan.');
            $_SESSION['csrf_sharing'] = bin2hex(random_bytes(16));
            header('Location: admin-sharing.php');
            exit;
        }

        if ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID tidak valid.');
            $title = trim($_POST['title'] ?? '');
            if ($title === '') throw new RuntimeException('Judul wajib diisi.');
            $link = trim($_POST['link'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            $cur = $pdo->prepare("SELECT * FROM sharing WHERE id=:id");
            $cur->execute([':id'=>$id]);
            $row = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Konten tidak ditemukan.');

            $saved = handle_upload($_FILES['image'] ?? [], $shareFsDir, $allowedExt, $maxFileSize);
            $imgDb = $saved ? $saved : $row['image'];

            if ($saved && !empty($row['image'])) {
                $old = $shareFsDir . $row['image'];
                if (file_exists($old)) @unlink($old);
            }

            $upd = $pdo->prepare("UPDATE sharing SET title=:title,image=:image,description=:description,link=:link WHERE id=:id");
            $upd->execute([':title'=>$title, ':image'=>$imgDb, ':description'=>$desc, ':link'=>$link, ':id'=>$id]);

            flash_set('success','Perubahan disimpan.');
            $_SESSION['csrf_sharing'] = bin2hex(random_bytes(16));
            header('Location: admin-sharing.php');
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID tidak valid.');
            $cur = $pdo->prepare("SELECT image FROM sharing WHERE id=:id");
            $cur->execute([':id'=>$id]);
            $row = $cur->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['image'])) {
                $f = $shareFsDir . $row['image'];
                if (file_exists($f)) @unlink($f);
            }
            $pdo->prepare("DELETE FROM sharing WHERE id=:id")->execute([':id'=>$id]);
            flash_set('success','Konten dihapus.');
            $_SESSION['csrf_sharing'] = bin2hex(random_bytes(16));
            header('Location: admin-sharing.php');
            exit;
        }
    }
} catch (Throwable $e) {
    flash_set('error', $e->getMessage());
    header('Location: admin-sharing.php');
    exit;
}

// baca data untuk tampilan
$shares = $pdo->query("SELECT * FROM sharing ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// edit item jika ada ?edit=
$editItem = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $stmt = $pdo->prepare("SELECT * FROM sharing WHERE id=:id");
        $stmt->execute([':id'=>$eid]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$success = flash_get('success');
$error = flash_get('error');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin - Sharing</title>
<style>
 :root{
  --bg:#f2e79a;--panel:#3b3656;--accent:#ffeb3b;--card:#3c3763;
  --thead:#342c4a;--muted:#e8e8e8;--sb-w:300px;--maxw:1200px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:Inter,Arial,sans-serif}
body{min-height:100vh;background:var(--bg);display:flex;justify-content:center}
.page{width:100%;max-width:var(--maxw);padding:18px}

/* SIDEBAR (identical to admin-biodata.php) */
.sidebar{
  width:var(--sb-w);background:var(--panel);color:#fff;padding:28px 22px;
  display:flex;flex-direction:column;align-items:center;gap:12px;
  border-radius:0 12px 12px 0;box-shadow:0 10px 30px #0002;
}
.avatar{width:140px;height:140px;border-radius:50%;overflow:hidden;border:6px solid var(--accent)}
.avatar img{width:100%;height:100%;object-fit:cover}
.user-short{font-weight:700;margin-top:10px}
.user-full{text-align:center;font-weight:600;margin-top:12px;padding-bottom:8px;border-bottom:3px solid var(--accent);width:100%}
nav ul{list-style:none;margin-top:18px;width:100%}
nav li{padding:12px 8px}
nav a{color:#fff;text-decoration:none;position:relative}
nav a.active::after{content:"";position:absolute;left:0;bottom:-6px;width:100%;height:4px;background:var(--accent);border-radius:4px}

/* MAIN */
.main{padding-left:8px}
.title{font-size:1.5rem;font-weight:700}

/* Card/form */
.card{
  background:var(--card);padding:18px;border-radius:12px;
  box-shadow:0 12px 40px #0002;margin-top:12px;
}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.field{background:#eee;padding:10px;border-radius:10px;color:#111}
.field input,.field textarea{width:100%;border:0;background:transparent;outline:none;font-size:.95rem}
.file-wrap{display:flex;gap:12px;align-items:center}
.btn-save{background:var(--accent);padding:12px;border:0;border-radius:8px;font-weight:700;cursor:pointer;width:100%}
.btn-cancel{background:#fff;padding:10px;border-radius:8px;text-decoration:none;color:#111;display:inline-block}

/* table */
.table-wrap{margin-top:18px;overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{background:var(--thead);color:#fff;padding:12px;text-align:left}
tbody td{background:#fff;padding:12px;border-bottom:1px solid #eee;vertical-align:top}
.thumb{width:120px;height:80px;object-fit:cover;border-radius:6px;background:#ddd}

/* responsive */
.hamburger{display:none;font-size:22px;background:none;border:0;margin-bottom:10px}
.overlay{display:none;position:fixed;inset:0;background:#0006;opacity:0;transition:.2s;z-index:40}
@media(min-width:761px){
  .sidebar{position:fixed;left:0;top:0;height:100vh}
  .page{margin-left:var(--sb-w)}
}
@media(max-width:760px){
  .sidebar{position:fixed;left:0;top:0;height:100vh;width:75vw;max-width:320px;transform:translateX(-110%);transition:.3s;border-radius:0}
  .sidebar.open{transform:translateX(0)}
  .hamburger{display:block}
  .overlay.show{display:block;opacity:1}
  .page{margin-left:0;padding:12px}
}

/* MESSAGES */
.msg-success{background:#e6ffed;color:#086c2f;padding:10px;border-radius:8px;margin:10px 0}
.msg-error{background:#ffeded;color:#900;padding:10px;border-radius:8px;margin:10px 0}

</style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<div class="page">

  <!-- SIDEBAR -->
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
    <div class="title">Sharing</div>

    <?php if ($success): ?>
      <div class="msg-success"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="msg-error"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_sharing'])?>">
        <input type="hidden" name="action" value="<?= $editItem ? 'edit' : 'add' ?>">
        <?php if ($editItem): ?><input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>"><?php endif; ?>

        <div class="form-grid">
          <div class="field file-wrap">
            <label style="width:100%;display:block">
              <input type="file" name="image" id="fileInput" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none">
              <button type="button" onclick="document.getElementById('fileInput').click()" style="background:#fff;border:0;padding:10px;border-radius:12px;cursor:pointer">Pilih File</button>
              <span id="fileName" style="margin-left:12px;color:#333"><?= $editItem && $editItem['image'] ? htmlspecialchars(basename($editItem['image'])) : 'Tidak ada file yang dipilih' ?></span>
            </label>
          </div>

          <div class="field"><input type="text" name="title" placeholder="Judul Konten" value="<?=htmlspecialchars($editItem['title'] ?? '')?>"></div>
          <div class="field"><input type="text" name="link" placeholder="Link (opsional)" value="<?=htmlspecialchars($editItem['link'] ?? '')?>"></div>

          <div style="grid-column:1/ -1">
            <div class="field" style="min-height:72px">
              <textarea name="description" placeholder="Deskripsi (opsional)"><?=htmlspecialchars($editItem['description'] ?? '')?></textarea>
            </div>
          </div>
        </div>

        <div style="margin-top:12px;text-align:center">
          <button type="submit" class="btn-save"><?= $editItem ? 'Simpan Perubahan' : 'Tambah Konten' ?></button>
          <?php if ($editItem): ?><a class="btn-cancel" href="admin-sharing.php">Batal</a><?php endif; ?>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Gambar</th>
            <th>Judul</th>
            <th>Deskripsi</th>
            <th>Link</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($shares)): ?>
            <tr><td colspan="5" style="padding:18px;background:#fff">Belum ada konten.</td></tr>
          <?php else: ?>
            <?php foreach($shares as $s):
              $img = resolve_share_image($s, $shareFsDir, $shareRel, $shareSub);
            ?>
            <tr>
              <td><img src="<?=htmlspecialchars($img ?: 'p-placeholder.png')?>" alt="" class="thumb"></td>
              <td><?=htmlspecialchars($s['title'])?></td>
              <td><?=nl2br(htmlspecialchars($s['description']))?></td>
              <td>
                <?php if (!empty($s['link'])): ?>
                  <a href="<?=htmlspecialchars($s['link'])?>" target="_blank" rel="noopener noreferrer"><?=htmlspecialchars($s['link'])?></a>
                <?php endif; ?>
              </td>
              <td>
                <a href="admin-sharing.php?edit=<?= (int)$s['id'] ?>" style="display:inline-block;padding:6px 10px;background:#f6d86c;border-radius:6px;margin-right:6px;text-decoration:none;color:#111;font-weight:700">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Hapus konten ini?');">
                  <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_sharing'])?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" style="background:#ff6b6b;color:#fff;border:0;padding:6px 10px;border-radius:6px;cursor:pointer">Hapus</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script>
const fileInput = document.getElementById('fileInput');
const fileName = document.getElementById('fileName');
if (fileInput) {
  fileInput.addEventListener('change', (e) => {
    const f = e.target.files[0];
    fileName.textContent = f ? f.name : 'Tidak ada file yang dipilih';
  });
}

// Sidebar hamburger & overlay (same behavior as admin-biodata.php)
const hamb = document.getElementById("hambMenu");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

if (hamb) {
  hamb.onclick = () => {
    sidebar.classList.add("open");
    overlay.classList.add("show");
  };
}
if (overlay) {
  overlay.onclick = () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  };
}
</script>

</body>
</html>
