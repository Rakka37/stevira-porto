<?php
require_once __DIR__ . '/db.php';

// ambil biodata (satu row)
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$defaults = [
  'nama' => 'Stevira Rachel Gabriella',
  'email' => 'steviragabriella@gmail.com',
  'telepon' => '08883049119',
  'instagram' => '@stevirachel',
  'linkedin' => 'linkedin.com/in/stevira',
  'youtube' => 'LSMI UPN JATIM',
  'github' => '@SimmySim',
  'tiktok' => '@LSMI_UPNVJT',
  'foto' => null
];

$data = $row ? array_merge($defaults, $row) : $defaults;

$uploadDir = __DIR__ . '/uploads/';
$uploadEducationDir = $uploadDir . 'education/';

// pastikan folder edukasi ada (bukan membuat file di produksi, cuma aman-aman)
if (!is_dir($uploadEducationDir)) {
    // tidak wajib membuat di produksi, tapi aman jika belum ada
    @mkdir($uploadEducationDir, 0755, true);
}

// foto profil (navbar & biodata)
$foto = (!empty($data['foto']) && file_exists($uploadDir . $data['foto']))
        ? 'uploads/' . rawurlencode($data['foto'])
        : 'stevira.jpg';

$nama = htmlspecialchars($data['nama'], ENT_QUOTES);

// ambil daftar pendidikan dari tabel education (urut berdasarkan sort asc, id desc fallback)
try {
    $eduStmt = $pdo->query("SELECT * FROM education ORDER BY COALESCE(`sort`,0) ASC, id ASC");
    $educations = $eduStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // jika tabel belum ada atau error, kosongkan
    $educations = [];
}

// helper untuk path icon pendidikan
function edu_icon_path($fname) {
    $baseLocal = 'uploads/education/' . $fname;
    if ($fname && file_exists(__DIR__ . '/' . $baseLocal)) return $baseLocal;
    // fallback: check uploads/
    $alt = 'uploads/' . $fname;
    if ($fname && file_exists(__DIR__ . '/' . $alt)) return $alt;
    return '';
}

// small helper for safe output
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biodata - <?= h($nama) ?></title>

<style>
/* ==== COPIED EXACTLY FROM INDEX (BIAR SAMA PERSIS) ==== */
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}
body{background:linear-gradient(135deg,#1b1d26,#2d2a35);color:#eee;text-align:center}
a{text-decoration:none;color:inherit}

/* NAVBAR */
.navbar{
  position:fixed;top:0;left:0;width:100%;
  background:rgba(66,66,151,.7);backdrop-filter:blur(8px);
  box-shadow:0 3px 8px rgba(0,0,0,.4);z-index:10
}
.nav-container{
  max-width:1300px;margin:auto;padding:10px 20px;
  display:flex;justify-content:space-between;align-items:center
}
.logo{display:flex;align-items:center;gap:10px;color:#f8c146;font-weight:700}
.logo img{width:40px;height:40px;border-radius:50%;border:3px solid #f8c146;object-fit:cover;background:#fff}

.menu{display:flex;gap:25px;list-style:none}
.menu a{font-weight:500;transition:.2s;color:#eee}
.menu a:hover{color:#f8c146;transform:scale(1.05)}
.menu-icon{display:none;font-size:1.8rem;color:#f8c146;cursor:pointer}
@media(max-width:768px){
  .menu{display:none;position:absolute;top:60px;left:0;width:100%;flex-direction:column;background:#191923f2;padding:20px 0}
  .menu.show{display:flex}
  .menu-icon{display:block}
}

.menu a.active{position:relative;color:#fff}
.menu a.active::after{
  content:"";position:absolute;left:0;right:0;bottom:-8px;
  height:4px;background:#f8c146;border-radius:4px;
}

/* ==== BIODATA STYLE (DISAMAKAN DESAINNYA) ==== */

.section-title{
  font-size:2.2rem;font-weight:800;margin-top:140px;margin-bottom:34px;color:#f8c146
}

.bio-card{
  max-width:900px;
  margin: auto;
  background:#111;
  padding:35px;
  border-radius:18px;
  box-shadow:0 0 15px #0006;
  text-align:left;
  margin-bottom:40px;
  border:3px solid #f8c146;
}

.bio-grid{
  display:grid;
  grid-template-columns:160px auto;
  gap:8px;
}

.label-col div{
  padding:6px 0;
  font-weight:bold;
  color:#f8c146;
}

.value-col div{
  padding:6px 0;
  color:#ddd;
}

/* FOTO */
.bio-photo{
  display:flex;justify-content:center;margin-bottom:25px;
}
.bio-photo img{
  width:200px;height:200px;border-radius:50%;
  border:8px solid #f8c146;background:#fff;object-fit:cover;
}

/* EDUCATION */
.edu-card{
  max-width:900px;
  margin: auto;
  background:#111;
  padding:25px;
  border-radius:18px;
  border:3px solid #f8c146;
  box-shadow:0 0 15px #0006;
}
.edu-title{
  font-size:1.4rem;
  color:#f8c146;
  margin-bottom:14px;
  font-weight:bold;
}
.edu-list{display:flex;flex-direction:column;gap:12px;}
.edu-item{
  background:#e5e3c6;
  padding:12px;
  border-radius:12px;
  display:flex;
  align-items:center;
  gap:12px;
  color:#222;
}
.edu-item img{
  width:40px;height:40px;border-radius:50%;object-fit:cover;
  border:2px solid #0003;
}

/* separator kuning di atas footer (DITAMBAHKAN) */
.section-sep{
  height:6px;
  background:#f8c146;
  width:100%;
  box-shadow:0 2px 0 rgba(0,0,0,.12);
}

/* FOOTER (SAMA DENGAN INDEX) */
footer{
  background:rgba(66,66,151,.7);color:#aaa;text-align:center;
  padding:20px 0;border-top:1px solid #ffffff22;margin-top:0;
}
.footer-social img{
  width:30px;height:30px;margin:0 8px;border-radius:8px;
  filter:brightness(.95);transition:.2s
}
.footer-social img:hover{transform:scale(1.12);filter:brightness(1.2)}

.login-admin{
  position:fixed;left:6px;bottom:8px;font-size:7px;
  background:#0007;color:#d3d3d3;padding:8px 10px;border-radius:8px;
  box-shadow:0 6px 18px #0007;font-weight:600;transition:.2s;
  text-decoration:none;
}
.login-admin:hover{background:#0009;color:#fff;transform:translateY(-3px)}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-container">

    <div class="logo">
      <img src="<?= h($foto) ?>" alt="">
      <?= h($nama) ?>
    </div>

    <span class="menu-icon" id="menu-icon">&#9776;</span>

    <ul class="menu" id="menu-list">
      <li><a href="index.php">Home</a></li>
      <li><a href="biodata.php" class="active">Biodata</a></li>
      <li><a href="projects.php">Project</a></li>
      <li><a href="certificates.php">Certificate</a></li>
      <li><a href="sharing.php">Sharing</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </div>
</nav>

<!-- JUDUL -->
<h1 class="section-title">Biodata</h1>

<!-- FOTO -->
<div class="bio-photo">
  <img src="<?= h($foto) ?>" alt="">
</div>

<!-- BIODATA CARD -->
<div class="bio-card">
  <div class="bio-grid">

    <div class="label-col">
      <div>Nama</div>
      <div>Email</div>
      <div>Telepon</div>
      <div>Instagram</div>
      <div>LinkedIn</div>
      <div>Youtube</div>
      <div>Github</div>
      <div>TikTok</div>
    </div>

    <div class="value-col">
      <div>: <?= h($data['nama']) ?></div>
      <div>: <?= h($data['email']) ?></div>
      <div>: <?= h($data['telepon']) ?></div>
      <div>: <?= h($data['instagram']) ?></div>
      <div>: <?= h($data['linkedin']) ?></div>
      <div>: <?= h($data['youtube']) ?></div>
      <div>: <?= h($data['github']) ?></div>
      <div>: <?= h($data['tiktok']) ?></div>
    </div>

  </div>
</div>

<!-- RIWAYAT PENDIDIKAN -->
<div class="edu-card">
  <div class="edu-title">Riwayat Pendidikan</div>

  <div class="edu-list">
    <?php if (empty($educations)): ?>
      <!-- fallback statis jika tabel kosong -->
      <div class="edu-item">
        <img src="sd.png" alt="SD">
        <div>SD Negeri 1 Lidah Kulon, Surabaya</div>
      </div>

      <div class="edu-item">
        <img src="smp.png" alt="SMP">
        <div>SMP Negeri 28 Surabaya</div>
      </div>

      <div class="edu-item">
        <img src="sma.png" alt="SMA">
        <div>SMA Negeri 1 Driyorejo</div>
      </div>

      <div class="edu-item">
        <img src="univ.png" alt="Universitas">
        <div>UPN “Veteran” Jawa Timur</div>
      </div>
    <?php else: ?>
      <?php foreach ($educations as $ed):
        // default icons (if icon missing, try sensible fallback by level)
        $icon = '';
        if (!empty($ed['icon'])) {
            $icon = edu_icon_path($ed['icon']);
        }
        if (!$icon) {
            // try level-based fallback filenames
            $lev = strtolower(trim((string)$ed['level']));
            if (strpos($lev, 'sd') !== false) $icon = file_exists('sd.png') ? 'sd.png' : '';
            elseif (strpos($lev, 'smp') !== false) $icon = file_exists('smp.png') ? 'smp.png' : '';
            elseif (strpos($lev, 'sma') !== false || strpos($lev, 'smk') !== false) $icon = file_exists('sma.png') ? 'sma.png' : '';
            else $icon = file_exists('univ.png') ? 'univ.png' : '';
        }
      ?>
      <div class="edu-item">
        <img src="<?= h($icon ?: 'p-placeholder.png') ?>" alt="<?= h($ed['level'] ?? '') ?>">
        <div>
          <div style="font-weight:700;"><?= h($ed['level'] ?? '') ?> — <?= h($ed['institution'] ?? '') ?></div>
          <?php if (!empty($ed['year_or_period'])): ?>
            <div style="font-size:0.95rem;color:#444;margin-top:6px"><?= h($ed['year_or_period']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- GARIS KUNING PEMBATAS (DITAMBAHKAN) -->
<div class="section-sep" aria-hidden="true"></div>

<!-- FOOTER -->
<footer>
  <div class="footer-social">
    <a href="https://www.linkedin.com/in/stevirarachelgabriella" target="_blank" rel="noopener"><img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn"></a>
    <a href="https://www.instagram.com/stevirachel" target="_blank" rel="noopener"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram"></a>
    <a href="https://youtube.com" target="_blank" rel="noopener"><img src="https://cdn-icons-png.flaticon.com/512/1384/1384060.png" alt="YouTube"></a>
    <a href="https://github.com" target="_blank" rel="noopener"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111425.png" alt="GitHub"></a>
    <a href="https://tiktok.com" target="_blank" rel="noopener"><img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok"></a>
  </div>
  <div>© <?= date("Y") ?> <?= h($nama) ?> | Portofolio UPNVJT</div>
</footer>

<a class="login-admin" href="admin-biodata.php">Login Admin</a>

<script>
  const menuIcon = document.getElementById('menu-icon');
  const menuList = document.getElementById('menu-list');
  if (menuIcon && menuList) menuIcon.onclick = () => menuList.classList.toggle('show');
</script>

</body>
</html>
