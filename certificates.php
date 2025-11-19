<?php
require_once __DIR__ . '/db.php';

// ambil biodata singkat untuk navbar
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$b = $stmt->fetch(PDO::FETCH_ASSOC);

$defaults_b = ['nama' => 'Stevira Rachel Gabriella', 'foto' => null];
$b = $b ? array_merge($defaults_b, $b) : $defaults_b;

$uploadDir = __DIR__ . '/uploads/';
$nama_nav = htmlspecialchars($b['nama']);

// foto navbar
$b_foto = (!empty($b['foto']) && file_exists($uploadDir . $b['foto']))
           ? 'uploads/' . rawurlencode($b['foto'])
           : 'stevira.jpg';

// ambil certificates
$certificates = $pdo->query("SELECT * FROM certificates ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

function cert_image_path($fname) {
    if (!$fname) return '';
    $local = 'uploads/certificates/' . $fname;
    if (file_exists(__DIR__ . '/' . $local)) return $local;
    $alt = __DIR__ . '/' . ltrim($fname, '/');
    if (file_exists($alt)) return $fname;
    return '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificates - <?= $nama_nav ?></title>

<style>
/* ========================================================= */
/* ===============  GLOBAL & NAVBAR FIX ==================== */
/* ========================================================= */

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:Arial,Helvetica,sans-serif;
}

body{
  background:linear-gradient(135deg,#1b1d26,#2d2a35);
  color:#eee;
  text-align:center;
}

/* HILANGKAN GARIS PUTIH / UNDERLINE */
.navbar, .menu, .menu li, .menu li a{
  border:none !important;
  outline:none !important;
  text-decoration:none !important;
}

/* NAVBAR */
.navbar{
  position:fixed;
  top:0;left:0;
  width:100%;
  background:rgba(66,66,151,.7);
  backdrop-filter:blur(8px);
  box-shadow:0 3px 8px rgba(0,0,0,.4);
  z-index:1000;
}
.nav-container{
  max-width:1300px;
  margin:auto;
  padding:10px 20px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.logo{
  display:flex;
  align-items:center;
  gap:10px;
  color:#f8c146;
  font-weight:700;
}
.logo img{
  width:40px;
  height:40px;
  border-radius:50%;
  border:3px solid #f8c146;
  object-fit:cover;
  background:#fff;
}

.menu{
  display:flex;
  gap:25px;
  list-style:none;
}
.menu a{
  font-weight:500;
  color:#eee;
  transition:.2s;
  text-decoration:none !important;
}
.menu a:hover{
  color:#f8c146;
  transform:scale(1.05);
}
.menu a.active{
  position:relative;
  color:#fff;
}
.menu a.active::after{
  content:"";
  position:absolute;
  left:0;
  right:0;
  bottom:-8px;
  height:4px;
  background:#f8c146;
  border-radius:4px;
}

.menu-icon{
  display:none;
  font-size:1.8rem;
  color:#f8c146;
  cursor:pointer;
}

@media(max-width:768px){
  .menu{
    display:none;
    position:absolute;
    top:60px;
    left:0;
    width:100%;
    flex-direction:column;
    background:#191923f2;
    padding:20px 0;
    border:none !important;
  }
  .menu.show{display:flex;}
  .menu-icon{display:block;}
}

/* ========================================================= */
/* =======================  CONTENT  ======================== */
/* ========================================================= */

main{margin-top:100px;}

.about-section{
  background:#000;
  padding:80px 20px 60px;
  border-bottom:4px solid #f8c146;
}
.section-title{
  color:#f8c146;
  font-weight:800;
  font-size:2.1rem;
  margin-bottom:34px;
}

/* grid certificate */
.cert-grid{
  max-width:1180px;
  margin:0 auto;
  display:flex;
  flex-wrap:wrap;
  gap:34px;
  justify-content:center;
  padding:10px;
}

.cert-card{
  width:190px;
  background:#3b3b70;
  border-radius:6px;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  box-shadow:0 4px 15px rgba(0,0,0,.4);
  transition:.2s;
}
.cert-card:hover{transform:translateY(-6px);}

.cert-title{
  padding:10px;
  background:#3b3b70;
  font-weight:600;
  font-size:.85rem;
  min-height:42px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
}
.cert-card img{
  width:100%;
  height:110px;
  object-fit:cover;
  background:#222;
}
.cert-detail{
  margin-top:auto;
  background:#f8c146;
  padding:8px 0;
  color:#000;
  display:block;
  text-decoration:none !important;
}
.cert-detail:hover{
  background:#ebe098;
  font-weight:700;
}

@media(max-width:900px){
  .cert-card{width:160px;}
  .cert-card img{height:95px;}
}
@media(max-width:420px){
  .cert-card{width:140px;}
  .cert-card img{height:80px;}
  .cert-title{font-size:.9rem;}
}

/* MODAL */
.modal-backdrop{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.6);
  display:none;
  align-items:center;
  justify-content:center;
  padding:20px;
  z-index:2000;
}
.modal-backdrop.show{display:flex;}

.modal{
  width:100%;
  max-width:900px;
  background:#111;
  border-radius:10px;
  display:flex;
  gap:18px;
  overflow:hidden;
  box-shadow:0 20px 60px rgba(0,0,0,.7);
}
.modal-left{
  flex:1;padding:12px;background:#000;
  display:flex;align-items:center;justify-content:center;
}
.modal-left img{
  max-width:100%;
  max-height:70vh;
  object-fit:contain;
}
.modal-right{
  flex:1;padding:18px;
  color:#eee;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
}
.modal-title{
  color:#f8c146;
  font-weight:800;
  font-size:1.2rem;
  margin-bottom:8px;
  text-align:left;
}
.modal-meta{text-align:left;color:#ccc;margin-bottom:8px}
.modal-desc{text-align:left;color:#ddd;line-height:1.5}

.modal-close{
  align-self:flex-end;
  padding:8px 12px;
  border-radius:8px;
  background:#f8c146;
  color:#000;
  cursor:pointer;
}
.modal-close:hover{
  background:#ebe098;
  font-weight:700;
}

/* FOOTER */
footer{
  background:rgba(66,66,151,.7);
  padding:20px 0 30px;
  color:#aaa;
  border-top:1px solid rgba(255,255,255,.1);
}
.footer-social img{
  width:30px;height:30px;margin:0 8px;border-radius:8px;
  filter:brightness(.95);
}
.login-admin{
  position:fixed;
  left:6px;
  bottom:8px;
  background:#0007;
  color:#d3d3d3;
  font-size:7px;
  padding:8px 10px;
  border-radius:8px;
  font-weight:600;
  box-shadow:0 6px 18px #0007;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-container">

    <div class="logo">
      <img src="<?= htmlspecialchars($b_foto) ?>">
      <?= $nama_nav ?>
    </div>

    <span class="menu-icon" id="menu-icon">&#9776;</span>

    <ul class="menu" id="menu-list">
      <li><a href="index.php">Home</a></li>
      <li><a href="biodata.php">Biodata</a></li>
      <li><a href="projects.php">Project</a></li>
      <li><a href="certificates.php" class="active">Certificate</a></li>
      <li><a href="sharing.php">Sharing</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>

  </div>
</nav>

<!-- CONTENT -->
<main>
<section class="about-section">
  <h1 class="section-title">Certificate</h1>

  <div class="cert-grid" id="certGrid">
    <?php if(empty($certificates)): ?>
      <div style="color:#ddd">Belum ada sertifikat.</div>
    <?php else: ?>
      <?php foreach($certificates as $c):
        $img = cert_image_path($c['image']);
      ?>
      <div class="cert-card"
           data-img="<?= htmlspecialchars($img) ?>"
           data-title="<?= htmlspecialchars($c['title']) ?>"
           data-date="<?= htmlspecialchars($c['date_text']) ?>"
           data-desc="<?= htmlspecialchars($c['description']) ?>">

        <div class="cert-title"><?= htmlspecialchars($c['title']) ?></div>
        <img src="<?= htmlspecialchars($img ?: 'p-placeholder.png') ?>">

        <a href="#" class="cert-detail">Details</a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
</main>

<!-- MODAL -->
<div id="modalBackdrop" class="modal-backdrop">
  <div class="modal">
    <div class="modal-left">
      <img id="modalImage">
    </div>
    <div class="modal-right">
      <div>
        <div id="modalTitle" class="modal-title"></div>
        <div id="modalMeta" class="modal-meta"></div>
        <div id="modalDesc" class="modal-desc"></div>
      </div>
      <div><a href="#" id="modalClose" class="modal-close">Close</a></div>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-social">
    <img src="https://cdn-icons-png.flaticon.com/512/174/174857.png">
    <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png">
    <img src="https://cdn-icons-png.flaticon.com/512/1384/1384060.png">
    <img src="https://cdn-icons-png.flaticon.com/512/2111/2111425.png">
  </div>
  <div>© <?= date("Y") ?> <?= $nama_nav ?> | Portofolio UPNVJT</div>
</footer>

<a class="login-admin" href="admin.php">Login Admin</a>

<script>
const menuIcon = document.getElementById('menu-icon');
const menuList = document.getElementById('menu-list');
menuIcon.onclick = ()=> menuList.classList.toggle('show');

const certGrid = document.getElementById('certGrid');
const backdrop = document.getElementById('modalBackdrop');
const modalImg = document.getElementById('modalImage');
const modalTitle = document.getElementById('modalTitle');
const modalMeta = document.getElementById('modalMeta');
const modalDesc = document.getElementById('modalDesc');
const modalClose = document.getElementById('modalClose');

certGrid.addEventListener('click', e=>{
  const btn = e.target.closest('.cert-detail');
  if(!btn) return;
  e.preventDefault();

  const c = btn.closest('.cert-card');
  modalImg.src = c.dataset.img;
  modalTitle.textContent = c.dataset.title;
  modalMeta.textContent = "Tgl Perolehan: " + c.dataset.date;
  modalDesc.textContent = c.dataset.desc;

  backdrop.classList.add('show');
  document.body.style.overflow = 'hidden';
});

modalClose.onclick = e=>{
  e.preventDefault();
  backdrop.classList.remove('show');
  document.body.style.overflow = '';
};
backdrop.onclick = e=>{
  if(e.target === backdrop){
    backdrop.classList.remove('show');
    document.body.style.overflow = '';
  }
};
</script>

</body>
</html>
