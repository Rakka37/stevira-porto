<?php
require_once __DIR__ . '/db.php';

// ambil biodata
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$row = $stmt->fetch();

$defaults = [
  'nama' => 'Stevira Rachel Gabriella',
  'email' => '',
  'telepon' => '',
  'instagram' => '',
  'linkedin' => '',
  'youtube' => '',
  'github' => '',
  'tiktok' => '',
  'foto' => null
];

$data = $row ? array_merge($defaults, $row) : $defaults;

$uploadDir = __DIR__ . '/uploads/';
$foto = (!empty($data['foto']) && file_exists($uploadDir . $data['foto']))
        ? 'uploads/' . rawurlencode($data['foto'])
        : 'stevira.jpg';

$nama = htmlspecialchars($data['nama']);
$nama_depan = htmlspecialchars(explode(" ", $data['nama'])[0]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portofolio <?= $nama ?></title>

<style>
/* CSS ORIGINAL – tidak diubah */
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}
body{background:linear-gradient(135deg,#1b1d26,#2d2a35);color:#eee;text-align:center}
a{text-decoration:none;color:inherit}

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
.menu a{font-weight:500;transition:.2s}
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

.hero{padding-top:120px;min-height:48vh;display:flex;flex-direction:column;justify-content:center;color:#c49826}
.hero p{color:#ccc;margin-top:8px}

.about-section{
  background:#000;padding:100px 20px 60px;margin-top:80px;
  border-top:4px solid #f8c146;border-bottom:4px solid #f8c146
}
.section-title{font-size:2.2rem;font-weight:800;margin-bottom:34px;color:#f8c146}
.about-container{
  max-width:1100px;margin:auto;background:#111;border-radius:18px;
  padding:40px;display:flex;flex-wrap:wrap;gap:30px;
  box-shadow:0 0 15px #0004;text-align:left;justify-content:center
}
.about-photo img{
  width:220px;height:220px;border-radius:50%;border:8px solid #f8c146;
  object-fit:cover;background:#fff
}
.about-text{max-width:600px}
.about-text h2{font-size:1.7rem;margin-bottom:6px}
.about-text span{color:#f8c146}
.about-text h3{color:#ffb100;font-weight:600;margin-bottom:12px}
.about-text p{color:#ccc;line-height:1.6;font-size:.95rem}

footer{
  background:rgba(66,66,151,.7);color:#aaa;text-align:center;
  padding:20px 0;border-top:1px solid #ffffff22
}
.footer-social img{
  width:30px;height:30px;margin:0 8px;border-radius:8px;
  filter:brightness(.95);transition:.2s
}
.footer-social img:hover{transform:scale(1.12);filter:brightness(1.2)}

.login-admin{
  position:fixed;left:6px;bottom:8px;font-size:7px;
  background:#0007;color:#d3d3d3;padding:8px 10px;border-radius:8px;
  box-shadow:0 6px 18px #0007;font-weight:600;transition:.2s
}
.login-admin:hover{background:#0009;color:#fff;transform:translateY(-3px)}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-container">
    <div class="logo">
      <img src="<?= htmlspecialchars($foto) ?>" alt="">
      <?= $nama ?>
    </div>

    <span class="menu-icon" id="menu-icon">&#9776;</span>

    <ul class="menu" id="menu-list">
      <li><a href="index.php" class="active">Home</a></li>
      <li><a href="biodata.php">Biodata</a></li>
      <li><a href="projects.php">Project</a></li>
      <li><a href="certificates.php">Certificate</a></li>
      <li><a href="sharing.php">Sharing</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <h1>Portofolio <?= $nama ?></h1>
  <p>Mahasiswa Sistem Informasi UPN Veteran Jawa Timur</p>
</section>

<!-- ABOUT -->
<section class="about-section" id="about">
  <h1 class="section-title">About Me</h1>

  <div class="about-container">
    <div class="about-photo">
      <img src="<?= htmlspecialchars($foto) ?>" alt="">
    </div>

    <div class="about-text">
      <h2>Hello, I'm <span><?= $nama ?></span></h2>
      <h3>Mahasiswa Sistem Informasi</h3>
      <p>
        Saya <?= $nama ?>, mahasiswa Sistem Informasi Universitas Pembangunan Nasional "Veteran" Jawa Timur.  
        Saya memiliki minat besar terhadap teknologi informasi, desain antarmuka, dan pengembangan sistem berbasis IoT.  
        Selain itu, saya juga aktif dalam berbagai kegiatan akademik dan organisasi.  
        <br><br>
        Semangat untuk terus belajar dan berkembang adalah prinsip utama saya! 🚀
      </p>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-social">
   <a href="https://www.linkedin.com/in/stevira-rachel-gabriella-a8b7a7290?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"><img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn"></a>
      <a href="https://www.instagram.com/stevirachel?igsh=dDUxYjRzeDVueWYw"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram"></a>
      <a href="https://youtube.com/@stevirarachel1212?si=BjQBgvoQEJe-2xVg"><img src="https://cdn-icons-png.flaticon.com/512/1384/1384060.png" alt="YouTube"></a>
      <a href="https://github.com/steviragabriella-code"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111425.png" alt="GitHub"></a>
      <a href="https://www.tiktok.com/@steviraa?_r=1&_t=ZS-91VNpeh0ocf"><img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok"></a>
  </div>

  <div>© <?= date("Y") ?> <?= $nama ?> | Portofolio UPNVJT</div>
</footer>

<a class="login-admin" href="admin.php">Login Admin</a>

<script>
  const menuIcon = document.getElementById('menu-icon');
  const menuList = document.getElementById('menu-list');
  menuIcon.onclick = () => menuList.classList.toggle('show');
</script>

</body>
</html>
