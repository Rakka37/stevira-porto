<?php
// admin-biodata.php (full) - Biodata + CRUD Education (modal edit with field summary)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

// --- auth ---
if (!isset($_SESSION['user_id'])) {
    header('Location: admin.php');
    exit;
}

// --- CSRF tokens ---
if (empty($_SESSION['csrf_biodata'])) $_SESSION['csrf_biodata'] = bin2hex(random_bytes(16));
if (empty($_SESSION['csrf_edu'])) $_SESSION['csrf_edu'] = bin2hex(random_bytes(16));

// --- upload dirs ---
$uploadDir = __DIR__ . '/uploads/';
$uploadEducationDir = $uploadDir . 'education/';
$uploadUrlBase = 'uploads'; // used to build browser URLs
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
if (!is_dir($uploadEducationDir)) @mkdir($uploadEducationDir, 0755, true);

// --- defaults & load biodata ---
$defaults = [
    'nama' => 'Stevira Rachel Gabriella',
    'telepon' => '08883049119',
    'email' => 'steviragabriella@gmail.com',
    'linkedin' => 'linkedin.com/in/stevira',
    'instagram' => '@stevirachel',
    'youtube' => 'LSMI UPN JATIM',
    'github' => '@SimmySim',
    'tiktok' => '@LSMI_UPNVJT',
    'foto' => null,
];
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$b = $stmt->fetch(PDO::FETCH_ASSOC);
$data = $b ? array_merge($defaults, $b) : $defaults;

// --- ensure education table exists ---
$createEduSql = "
CREATE TABLE IF NOT EXISTS education (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level VARCHAR(200) NOT NULL,
  institution VARCHAR(255) NOT NULL,
  year_or_period VARCHAR(100) DEFAULT NULL,
  icon VARCHAR(255) DEFAULT NULL,
  `sort` INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$pdo->exec($createEduSql);

// --- helpers ---
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }

function save_uploaded_icon(array $file, string $destDir, array $allowed = ['jpg','jpeg','png','gif','webp'], int $max=3*1024*1024): string|false {
    if (empty($file) || !isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $max) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    $safe = 'edu_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dst = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe;
    if (!move_uploaded_file($file['tmp_name'], $dst)) return false;
    return $safe;
}

function resolve_icon_url(string $icon, string $eduFsDir, string $urlBase): string {
    if (!$icon) return '';
    $base = basename($icon);
    $fs = rtrim($eduFsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $base;
    if (file_exists($fs)) return rtrim($urlBase, '/') . '/education/' . rawurlencode($base);
    $fs2 = __DIR__ . '/' . ltrim($icon, '/');
    if (file_exists($fs2)) return ltrim($icon, '/');
    return '';
}

// --- flash helpers ---
function flash_set(string $k, string $v){ $_SESSION['_fl_'.$k] = $v; }
function flash_get(string $k){ $key='_fl_'.$k; if(isset($_SESSION[$key])){ $v=$_SESSION[$key]; unset($_SESSION[$key]); return $v; } return null; }

// --- handle POST actions (biodata update + education CRUD) ---
$errors = [];
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form = $_POST['form'] ?? '';

        if ($form === 'biodata') {
            if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_biodata'], $_POST['csrf'])) throw new RuntimeException('CSRF invalid.');
            $nama = trim($_POST['nama'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $linkedin = trim($_POST['linkedin'] ?? '');
            $instagram = trim($_POST['instagram'] ?? '');
            $youtube = trim($_POST['youtube'] ?? '');
            $github = trim($_POST['github'] ?? '');
            $tiktok = trim($_POST['tiktok'] ?? '');
            if ($nama === '') throw new RuntimeException('Nama wajib diisi.');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');

            $foto_filename = $data['foto'] ?? null;
            if (!empty($_FILES['foto']['name'])) {
                $saved = save_uploaded_icon($_FILES['foto'], $uploadDir, ['jpg','jpeg','png','gif','webp'], 3*1024*1024);
                if ($saved === false) throw new RuntimeException('Upload foto gagal atau format tidak diperbolehkan.');
                if (!empty($foto_filename) && file_exists($uploadDir . $foto_filename)) @unlink($uploadDir . $foto_filename);
                $foto_filename = $saved;
            }

            if ($b) {
                $upd = $pdo->prepare("UPDATE biodata SET nama=:nama, telepon=:telepon, email=:email, linkedin=:linkedin, instagram=:instagram, youtube=:youtube, github=:github, tiktok=:tiktok, foto=:foto, updated_at=NOW() WHERE id=:id");
                $upd->execute([
                    ':nama'=>$nama, ':telepon'=>$telepon, ':email'=>$email, ':linkedin'=>$linkedin,
                    ':instagram'=>$instagram, ':youtube'=>$youtube, ':github'=>$github, ':tiktok'=>$tiktok,
                    ':foto'=>$foto_filename, ':id'=>$b['id']
                ]);
            } else {
                $ins = $pdo->prepare("INSERT INTO biodata (nama,telepon,email,linkedin,instagram,youtube,github,tiktok,foto) VALUES (:nama,:telepon,:email,:linkedin,:instagram,:youtube,:github,:tiktok,:foto)");
                $ins->execute([
                    ':nama'=>$nama, ':telepon'=>$telepon, ':email'=>$email, ':linkedin'=>$linkedin,
                    ':instagram'=>$instagram, ':youtube'=>$youtube, ':github'=>$github, ':tiktok'=>$tiktok, ':foto'=>$foto_filename
                ]);
            }

            $stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
            $b = $stmt->fetch(PDO::FETCH_ASSOC);
            $data = $b ? array_merge($defaults, $b) : $defaults;
            flash_set('success','Biodata tersimpan.');
            $_SESSION['csrf_biodata'] = bin2hex(random_bytes(16));
            header('Location: admin-biodata.php');
            exit;
        }

        if ($form === 'edu_add') {
            if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_edu'], $_POST['csrf'])) throw new RuntimeException('CSRF invalid.');
            $level = trim($_POST['level'] ?? '');
            $institution = trim($_POST['institution'] ?? '');
            $year_or_period = trim($_POST['year_or_period'] ?? '');
            $sort = (int)($_POST['sort'] ?? 0);
            if ($level === '' || $institution === '') throw new RuntimeException('Level dan Institusi wajib diisi.');

            $iconSaved = '';
            if (!empty($_FILES['icon']['name'])) {
                $saved = save_uploaded_icon($_FILES['icon'], $uploadEducationDir, ['jpg','jpeg','png','gif','webp'], 3*1024*1024);
                if ($saved === false) throw new RuntimeException('Upload icon gagal atau tidak diperbolehkan.');
                $iconSaved = 'education/' . $saved;
            }

            $ins = $pdo->prepare("INSERT INTO education (level,institution,year_or_period,icon,`sort`) VALUES (:level,:institution,:yop,:icon,:sort)");
            $ins->execute([':level'=>$level, ':institution'=>$institution, ':yop'=>$year_or_period, ':icon'=>$iconSaved, ':sort'=>$sort]);
            flash_set('success','Pendidikan ditambahkan.');
            $_SESSION['csrf_edu'] = bin2hex(random_bytes(16));
            header('Location: admin-biodata.php');
            exit;
        }

        if ($form === 'edu_edit') {
            if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_edu'], $_POST['csrf'])) throw new RuntimeException('CSRF invalid.');
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID tidak valid.');
            $level = trim($_POST['level'] ?? '');
            $institution = trim($_POST['institution'] ?? '');
            $year_or_period = trim($_POST['year_or_period'] ?? '');
            $sort = (int)($_POST['sort'] ?? 0);
            if ($level === '' || $institution === '') throw new RuntimeException('Level dan Institusi wajib diisi.');

            $cur = $pdo->prepare("SELECT * FROM education WHERE id=:id");
            $cur->execute([':id'=>$id]);
            $row = $cur->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Data pendidikan tidak ditemukan.');

            $iconVal = $row['icon'] ?? '';
            if (!empty($_FILES['icon']['name'])) {
                $saved = save_uploaded_icon($_FILES['icon'], $uploadEducationDir, ['jpg','jpeg','png','gif','webp'], 3*1024*1024);
                if ($saved === false) throw new RuntimeException('Upload icon gagal atau tidak diperbolehkan.');
                $oldBase = basename((string)$row['icon']);
                $oldFs = $uploadEducationDir . $oldBase;
                if (file_exists($oldFs)) @unlink($oldFs);
                $iconVal = 'education/' . $saved;
            }

            $upd = $pdo->prepare("UPDATE education SET level=:level, institution=:institution, year_or_period=:yop, icon=:icon, `sort`=:sort WHERE id=:id");
            $upd->execute([':level'=>$level, ':institution'=>$institution, ':yop'=>$year_or_period, ':icon'=>$iconVal, ':sort'=>$sort, ':id'=>$id]);
            flash_set('success','Pendidikan diperbarui.');
            $_SESSION['csrf_edu'] = bin2hex(random_bytes(16));
            header('Location: admin-biodata.php');
            exit;
        }

        if ($form === 'edu_delete') {
            if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_edu'], $_POST['csrf'])) throw new RuntimeException('CSRF invalid.');
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID tidak valid.');
            $cur = $pdo->prepare("SELECT icon FROM education WHERE id=:id");
            $cur->execute([':id'=>$id]);
            $r = $cur->fetch(PDO::FETCH_ASSOC);
            if ($r && !empty($r['icon'])) {
                $oldBase = basename((string)$r['icon']);
                $oldFs = $uploadEducationDir . $oldBase;
                if (file_exists($oldFs)) @unlink($oldFs);
            }
            $pdo->prepare("DELETE FROM education WHERE id=:id")->execute([':id'=>$id]);
            flash_set('success','Pendidikan dihapus.');
            $_SESSION['csrf_edu'] = bin2hex(random_bytes(16));
            header('Location: admin-biodata.php');
            exit;
        }
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

// --- read educations for display ---
$educations = $pdo->query("SELECT * FROM education ORDER BY COALESCE(`sort`,0) ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// avatar url
$avatar_url = (!empty($data['foto']) && file_exists($uploadDir . $data['foto'])) ? $uploadUrlBase . '/' . rawurlencode($data['foto']) : 'stevira.jpg';

// flash messages
$success = flash_get('success');
$errorFlash = flash_get('error');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin - Biodata (Edu)</title>
<style>
 :root{
  --bg:#f2e79a;--panel:#3b3656;--accent:#ffeb3b;--card:#3c3763;
  --thead:#342c4a;--muted:#e8e8e8;--sb-w:300px;--maxw:1100px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:Inter,Arial,sans-serif}
body{min-height:100vh;background:var(--bg);display:flex;justify-content:center}
.page{width:100%;max-width:var(--maxw);padding:18px}

/* SIDEBAR */
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
nav a{color:#fff;text-decoration:none;position:relative;display:block;padding:6px 4px}
nav a.active::after{content:"";position:absolute;left:0;bottom:-6px;width:100%;height:4px;background:var(--accent);border-radius:4px}

/* MAIN */
.main{padding-left:8px}
.title{font-size:1.5rem;font-weight:700;margin-bottom:8px}

/* containers */
.card{background:var(--card);padding:18px;border-radius:12px;box-shadow:0 12px 40px #0002;margin-top:12px}
.section{margin-top:12px}

/* biodata form */
.field{margin-bottom:10px}
.field label{display:block;color:#fff;margin-bottom:6px}
.field input[type="text"], .field input[type="email"], .field textarea{width:100%;padding:8px;border-radius:8px;border:0;background:#eee}
.btn{background:var(--accent);border:0;padding:10px 14px;border-radius:8px;cursor:pointer;font-weight:700}
.btn-muted{background:#fff;color:#111;padding:8px 12px;border-radius:8px;border:0;cursor:pointer}

/* education list */
.edu-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.edu-list-admin{display:flex;flex-direction:column;gap:8px}
.edu-item-admin{display:flex;align-items:center;gap:12px;background:#fff;padding:8px;border-radius:8px}
.edu-item-admin img{width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #ddd}
.edu-item-admin .meta{flex:1;text-align:left}
.small{font-size:13px;color:#666}

/* edit modal with right-side field summary */
#editEduModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:1000}
#editEduModal .box{width:880px;max-width:96%;background:#fff;padding:18px;border-radius:12px;display:flex;gap:12px}
#editEduModal .form-wrap{flex:1}
#editEduModal .summary{width:260px;background:#f8f8f8;border-radius:8px;padding:12px;box-shadow:0 4px 18px rgba(0,0,0,.06)}
#editEduModal .summary h4{margin:0 0 8px 0;font-size:16px}
#editEduModal .summary .row{margin-bottom:8px;font-size:14px;color:#333}
#editEduModal label{display:block;margin-bottom:6px;font-weight:700}
#editEduModal input[type="text"], #editEduModal input[type="number"]{width:100%;padding:8px;border-radius:8px;border:1px solid #ddd}

/* messages */
.msg-success{background:#e6ffed;color:#086c2f;padding:10px;border-radius:8px;margin:10px 0}
.msg-error{background:#ffeded;color:#900;padding:10px;border-radius:8px;margin:10px 0}

/* responsive */
@media(min-width:961px){
  .sidebar{position:fixed;left:0;top:0;height:100vh}
  .page{margin-left:var(--sb-w)}
}
@media(max-width:960px){
  .page{margin-left:0;padding:12px}
  .sidebar{position:fixed;left:0;top:0;height:100vh;width:75vw;transform:translateX(-110%);transition:.3s}
  .sidebar.open{transform:translateX(0)}
  #editEduModal .box{flex-direction:column;padding:12px}
  #editEduModal .summary{width:auto;order:2}
}
</style>
</head>
<body>

<div class="page">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="avatar"><img src="<?= h($avatar_url) ?>" alt="avatar"></div>
    <div class="user-short"><?= h(explode(' ', $data['nama'])[0] ?: 'Stevira') ?></div>
    <div class="user-full"><?= h($data['nama']) ?></div>

    <nav>
      <ul>
        <li><a href="admin-biodata.php" class="active">Biodata</a></li>
        <li><a href="admin-projects.php">Projects</a></li>
        <li><a href="admin-certificates.php">Certificates</a></li>
        <li><a href="admin-sharing.php">Sharing</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <div class="title">Edit Data Diri</div>

    <?php if ($success): ?><div class="msg-success"><?= h($success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="msg-error"><ul style="padding-left:18px;margin:0"><?php foreach($errors as $er) echo '<li>'.h($er).'</li>'; ?></ul></div><?php endif; ?>
    <?php if ($errorFlash): ?><div class="msg-error"><?= h($errorFlash) ?></div><?php endif; ?>

    <!-- BIODATA CARD -->
    <section class="card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="form" value="biodata">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_biodata']) ?>">

        <div class="field">
          <label>Nama</label>
          <input type="text" name="nama" value="<?= h($data['nama']) ?>" required>
        </div>

        <div class="field">
          <label>Telepon</label>
          <input type="text" name="telepon" value="<?= h($data['telepon'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?= h($data['email'] ?? '') ?>">
        </div>

        <div class="field">
          <label>LinkedIn</label>
          <input type="text" name="linkedin" value="<?= h($data['linkedin'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Instagram</label>
          <input type="text" name="instagram" value="<?= h($data['instagram'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Youtube</label>
          <input type="text" name="youtube" value="<?= h($data['youtube'] ?? '') ?>">
        </div>

        <div class="field">
          <label>GitHub</label>
          <input type="text" name="github" value="<?= h($data['github'] ?? '') ?>">
        </div>

        <div class="field">
          <label>TikTok</label>
          <input type="text" name="tiktok" value="<?= h($data['tiktok'] ?? '') ?>">
        </div>

        <div class="field">
          <label>Foto profil (opsional, jpg/png/gif/webp max 3MB)</label>
          <input type="file" name="foto" accept="image/*">
        </div>

        <div style="text-align:right"><button class="btn" type="submit">Simpan Biodata</button></div>
      </form>
    </section>

    <!-- EDUCATION SECTION (UNDER BIODATA) -->
    <section class="section">
      <div class="card">
        <div class="edu-header">
          <div style="font-weight:700">Riwayat Pendidikan</div>
          <div>
            <button id="btnToggleAdd" class="btn-muted" onclick="toggleAddForm(event)">Tambah Data</button>
          </div>
        </div>

        <!-- ADD FORM (hidden by default) -->
        <div id="addFormWrap" style="display:none;margin-bottom:12px">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="form" value="edu_add">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_edu']) ?>">
            <div class="field">
              <label>Level (mis. SD / SMP / SMA / S1)</label>
              <input type="text" name="level" required>
            </div>
            <div class="field">
              <label>Institusi</label>
              <input type="text" name="institution" required>
            </div>
            <div class="field">
              <label>Tahun / Periode</label>
              <input type="text" name="year_or_period">
            </div>
            <div class="field">
              <label>Urutan (sort) - angka kecil tampil dulu</label>
              <input type="number" name="sort" value="0" min="0">
            </div>
            <div class="field">
              <label>Icon (opsional, jpg/png/gif/webp)</label>
              <input type="file" name="icon" accept="image/*">
            </div>
            <div style="text-align:right"><button class="btn" type="submit">Tambah Pendidikan</button></div>
          </form>
        </div>

        <!-- education list -->
        <div class="edu-list-admin">
          <?php if (empty($educations)): ?>
            <div class="small">Belum ada data pendidikan.</div>
          <?php else: foreach ($educations as $ed): 
            $iconUrl = resolve_icon_url((string)$ed['icon'], $uploadEducationDir, $uploadUrlBase);
            $iconSrc = $iconUrl ?: 'p-placeholder.png';
          ?>
          <div class="edu-item-admin">
            <img src="<?= h($iconSrc) ?>" alt="<?= h($ed['level']) ?>">
            <div class="meta">
              <div style="font-weight:700"><?= h($ed['level']) ?> — <?= h($ed['institution']) ?></div>
              <?php if (!empty($ed['year_or_period'])): ?><div class="small"><?= h($ed['year_or_period']) ?></div><?php endif; ?>
            </div>

            <!-- actions: edit (dialog) + delete -->
            <div style="display:flex;flex-direction:column;gap:6px">
              <button class="btn-muted" onclick="openEditEdu(<?= (int)$ed['id'] ?>)">Edit</button>

              <form method="post" onsubmit="return confirm('Hapus pendidikan ini?');">
                <input type="hidden" name="form" value="edu_delete">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_edu']) ?>">
                <input type="hidden" name="id" value="<?= (int)$ed['id'] ?>">
                <button class="btn" type="submit" style="background:#ff6b6b">Hapus</button>
              </form>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </section>

    <!-- EDIT EDU MODAL (hidden) -->
    <div id="editEduModal">
      <div class="box">
        <div class="form-wrap">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="font-weight:700">Edit Pendidikan</div>
            <button onclick="closeEditEdu()" style="background:#eee;border:0;padding:6px 8px;border-radius:6px;cursor:pointer">X</button>
          </div>
          <form id="frmEditEdu" method="post" enctype="multipart/form-data">
            <input type="hidden" name="form" value="edu_edit">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_edu']) ?>">
            <input type="hidden" name="id" id="edu_id">
            <div class="field">
              <label for="edu_level">Level</label>
              <input type="text" name="level" id="edu_level" required>
            </div>
            <div class="field">
              <label for="edu_institution">Institusi</label>
              <input type="text" name="institution" id="edu_institution" required>
            </div>
            <div class="field">
              <label for="edu_yop">Tahun / Periode</label>
              <input type="text" name="year_or_period" id="edu_yop">
            </div>
            <div class="field" style="max-width:160px">
              <label for="edu_sort">Urutan (sort)</label>
              <input type="number" name="sort" id="edu_sort" value="0" min="0">
            </div>
            <div class="field">
              <label>Ganti Icon (opsional)</label>
              <input type="file" name="icon" accept="image/*">
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
              <div></div>
              <div><button class="btn" type="submit">Simpan</button></div>
            </div>
          </form>
        </div>

        <div class="summary" aria-hidden="false">
          <h4>Ringkasan field (yang diedit)</h4>
          <div class="row"><strong>Level:</strong> <span id="sum_level"></span></div>
          <div class="row"><strong>Institusi:</strong> <span id="sum_institution"></span></div>
          <div class="row"><strong>Tahun / Periode:</strong> <span id="sum_yop"></span></div>
          <div class="row"><strong>Urutan (sort):</strong> <span id="sum_sort"></span></div>
          <div class="row"><strong>Icon saat ini:</strong> <span id="sum_icon"></span></div>
          <div style="margin-top:8px;font-size:13px;color:#666">Perubahan akan tersimpan setelah klik <strong>Simpan</strong>.</div>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
// data for client-side editing
const educations = <?= json_encode($educations, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

function openEditEdu(id){
  const ed = educations.find(e=> e.id == id);
  if(!ed) return alert('Data tidak ditemukan.');
  document.getElementById('edu_id').value = ed.id;
  document.getElementById('edu_level').value = ed.level || '';
  document.getElementById('edu_institution').value = ed.institution || '';
  document.getElementById('edu_yop').value = ed.year_or_period || '';
  document.getElementById('edu_sort').value = ed.sort ?? 0;

  // populate summary
  updateSummaryFromInputs();

  // live update when inputs change
  ['edu_level','edu_institution','edu_yop','edu_sort'].forEach(idName=>{
    const el = document.getElementById(idName);
    if(!el._bound){
      el.addEventListener('input', updateSummaryFromInputs);
      el._bound = true;
    }
  });

  // icon current: show filename or preview if available
  const iconVal = ed.icon ? ed.icon.split('/').pop() : '(tidak ada)';
  document.getElementById('sum_icon').textContent = iconVal;

  document.getElementById('editEduModal').style.display = 'flex';
}

function closeEditEdu(){
  document.getElementById('frmEditEdu').reset();
  document.getElementById('editEduModal').style.display = 'none';
}

function toggleAddForm(e){
  e.preventDefault();
  const wrap = document.getElementById('addFormWrap');
  if(!wrap) return;
  wrap.style.display = (wrap.style.display === 'none' || wrap.style.display === '') ? 'block' : 'none';
}

function updateSummaryFromInputs(){
  document.getElementById('sum_level').textContent = document.getElementById('edu_level').value || '(kosong)';
  document.getElementById('sum_institution').textContent = document.getElementById('edu_institution').value || '(kosong)';
  document.getElementById('sum_yop').textContent = document.getElementById('edu_yop').value || '(kosong)';
  document.getElementById('sum_sort').textContent = document.getElementById('edu_sort').value ?? '0';
}
</script>

</body>
</html>
