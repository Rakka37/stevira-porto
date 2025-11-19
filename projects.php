<?php
require_once __DIR__ . '/db.php';

// ---------- pastikan tabel projects ada ----------
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

// ---------- jika tabel kosong, masukkan contoh data ----------
$count = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
if ($count === 0) {
    $sample = [
        [
          'title'=>'RUMAH HEMAT ENERGI','slug'=>'rumah-hemat-energi','thumb'=>'p1.jpg','image'=>'p1.jpg',
          'description'=>'Rumah Hemat Energi dirancang dengan konsep modern yang memadukan teknologi ramah lingkungan dan efisiensi energi.',
          'date_text'=>'26 Desember 2023','duration'=>'2 Bulan','doc_link'=>'','team'=>'Individu'
        ],
        [
          'title'=>'PEDULI LINGKUNGAN SEJAK DINI','slug'=>'peduli-lingkungan-sejak-dini','thumb'=>'p2.jpg','image'=>'p2.jpg',
          'description'=>'Proyek relawan untuk mengajarkan anak mencintai alam melalui kegiatan fun game, menanam, dan merawat tanaman.',
          'date_text'=>'28 Juni 2024','duration'=>'1 Bulan','doc_link'=>'','team'=>'Tim'
        ],
        [
          'title'=>'DESAIN RUANG OLAHRAGA','slug'=>'desain-ruang-olahraga','thumb'=>'p3.jpg','image'=>'p3.jpg',
          'description'=>'Tugas perancangan ruang olahraga yang melatih ketelitian dan visualisasi desain teknik.',
          'date_text'=>'4 Desember 2023','duration'=>'3 Minggu','doc_link'=>'','team'=>'Tim'
        ],
        [
          'title'=>'SOIL MOISTURE DETECTOR','slug'=>'soil-moisture-detector','thumb'=>'p4.jpg','image'=>'p4.jpg',
          'description'=>'Sistem penyiram tanaman otomatis berbasis Arduino Uno dengan sensor kelembaban tanah.',
          'date_text'=>'4 Mei 2024','duration'=>'1 Bulan','doc_link'=>'','team'=>'Tim'
        ],
        [
          'title'=>'PROJECT ANALISIS MENGGUNAKAN FLEXSIM','slug'=>'project-analisis-flexsim','thumb'=>'p5.jpg','image'=>'p5.jpg',
          'description'=>'Analisis simulasi sistem diskrit menggunakan FlexSim untuk evaluasi antrian dan optimasi sumber daya.',
          'date_text'=>'12 Maret 2025','duration'=>'2 Minggu','doc_link'=>'','team'=>'Individu'
        ],
    ];

    $ins = $pdo->prepare("INSERT INTO projects (title,slug,thumb,image,description,date_text,duration,doc_link,team) VALUES (:title,:slug,:thumb,:image,:description,:date_text,:duration,:doc_link,:team)");
    foreach ($sample as $s) {
        $ins->execute([
            ':title'=>$s['title'], ':slug'=>$s['slug'], ':thumb'=>$s['thumb'], ':image'=>$s['image'],
            ':description'=>$s['description'], ':date_text'=>$s['date_text'], ':duration'=>$s['duration'],
            ':doc_link'=>$s['doc_link'], ':team'=>$s['team']
        ]);
    }
}

// ---------- ambil biodata singkat untuk navbar ----------
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$b = $stmt->fetch();
$defaults_b = ['nama'=>'Stevira Rachel Gabriella','foto'=>null];
$b = $b ? array_merge($defaults_b, $b) : $defaults_b;
$uploadDir = __DIR__ . '/uploads/';
$b_foto = (!empty($b['foto']) && file_exists($uploadDir . $b['foto'])) ? 'uploads/' . rawurlencode($b['foto']) : 'stevira.jpg';
$nama_nav = htmlspecialchars($b['nama']);

// ---------- ambil semua project ----------
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();

// helper untuk gambar (cek uploads/projects/)
function project_image_path($fname) {
    $local = 'uploads/projects/' . $fname;
    if ($fname && file_exists(__DIR__ . '/' . $local)) return $local;
    return $fname ?: '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Projects - <?= $nama_nav ?></title>
  <style>
    :root{
      --bg1:#1b1d26; --bg2:#2d2a35; --accent:#f8c146; --accent2:#ffd66a;
      --card:#3b3b70; --panel:#2d2a35;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:Arial,Helvetica,sans-serif}
    html,body{height:100%}
    body{
      display:flex;
      flex-direction:column;
      min-height:100vh;
      background:linear-gradient(135deg,var(--bg1),var(--bg2));
      color:#eee;
      text-align:center;
      line-height:1.45;
    }
    a{color:inherit;text-decoration:none}

    /* NAVBAR (sama behavior dgn index.php) */
    .navbar{position:fixed;top:0;left:0;width:100%;background:rgba(66,66,151,.7);backdrop-filter:blur(8px);box-shadow:0 3px 8px rgba(0,0,0,.4);z-index:50}
    .nav-container{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:10px 20px}
    .logo{display:flex;align-items:center;gap:10px;color:var(--accent);font-weight:700}
    .logo img{width:40px;height:40px;border-radius:50%;border:3px solid var(--accent);object-fit:cover}
    .menu{display:flex;gap:25px;list-style:none}
    .menu li a{color:#eee;font-weight:500}
    .menu-icon{display:none;color:var(--accent);font-size:1.8rem;cursor:pointer}
    @media(max-width:768px){
      .menu{display:none;position:absolute;top:60px;left:0;width:100%;flex-direction:column;background:rgba(25,25,35,.95);padding:18px 0}
      .menu.show{display:flex}
      .menu-icon{display:block}
    }
    .menu a.active{position:relative;color:#fff}
    .menu a.active::after{content:"";position:absolute;left:0;right:0;bottom:-8px;height:4px;background:var(--accent);border-radius:4px}

    
    .about-section{
      background:#000;
      margin-top:60px;
      padding:56px 16px 28px;
      flex:1;
      display:flex;
      flex-direction:column;
      align-items:center;

      /* garis kuning pembatas seperti index.php */
      border-top:0px solid var(--accent);
      border-bottom:1px solid var(--accent);
    }
    .section-title{color:var(--accent);font-weight:800;font-size:2rem;margin-bottom:22px}

    /* GRID / CARDS: responsive like index */
    .proj-grid{
      width:100%;
      max-width:1180px;
      margin:0 auto;
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap:22px;
      padding:10px;
      justify-content:center;
    }
    .proj-card{
      background:var(--card);
      border-radius:10px;
      overflow:hidden;
      display:flex;
      flex-direction:column;
      box-shadow:0 6px 20px rgba(0,0,0,.45);
      transition:transform .18s;
    }
    .proj-card:hover{transform:translateY(-6px)}
    .proj-title{padding:12px;background:var(--card);font-weight:800;color:#fff;text-align:center;min-height:52px;display:flex;align-items:center;justify-content:center;font-size:.82rem}
    .proj-thumb{width:100%;height:110px;object-fit:cover;display:block;background:#222}
    .proj-actions{margin-top:auto;background:var(--panel);padding:0}
    .btn-details{display:block;width:100%;padding:10px;border:0;background:var(--accent);color:#000;font-weight:800;cursor:pointer;border-radius:0 0 6px 6px;transition:background .12s,transform .12s}
    .btn-details:hover{background:var(--accent2);transform:translateY(-2px)}

    /* MODAL (tetap responsif) */
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.72);display:none;align-items:center;justify-content:center;padding:18px;z-index:2000}
    .modal-backdrop.show{display:flex}
    .modal{width:100%;max-width:1100px;background:#111;border-radius:8px;overflow:hidden;display:flex;box-shadow:0 30px 80px rgba(0,0,0,.7)}
    .modal-left{flex:0 0 55%;background:#000;display:flex;align-items:center;justify-content:center;padding:6px}
    .modal-left img{width:100%;height:auto;max-height:78vh;object-fit:contain}
    .modal-right{flex:0 0 45%;background:var(--card);padding:22px 18px;color:#fff;display:flex;flex-direction:column;justify-content:space-between;align-items:flex-start;min-width:0}
    .modal-top{display:flex;gap:16px;align-items:flex-start;width:100%;text-align:left}
    .duration-block{flex:0 0 auto;background:var(--accent);color:#000;border-radius:10px;padding:8px 12px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:72px}
    .modal-title{color:var(--accent);font-weight:800;font-size:1.2rem;margin-bottom:8px;text-align:left}
    .modal-desc{margin-top:8px;color:#e6e6e6;font-size:.95rem;text-align:left;white-space:pre-wrap;margin-bottom:10px}
    .meta{color:#e6e6e6;font-size:.95rem;text-align:left;margin-bottom:10px;word-break:break-word}
    .modal-footer{display:flex;justify-content:center;width:100%;margin-top:6px}
    .modal-close{background:var(--accent);color:#000;border:0;padding:8px 14px;border-radius:18px;font-weight:700;cursor:pointer}

    /* separator (tetap boleh ada, tidak menggangu) */
    .section-sep{height:6px;background:var(--accent);width:100%;box-shadow:0 2px 0 rgba(0,0,0,.12)}

    /* ---- Footer style agar SAMA dengan biodata.php / index.php ---- */
    footer{
      background:rgba(66,66,151,.7);
      color:#aaa;
      text-align:center;
      padding:20px 0;
      border-top:1px solid #ffffff22;
      margin-top:0;
    }
    .footer-social img{
      width:30px;
      height:30px;
      margin:0 8px;
      border-radius:8px;
      filter:brightness(.95);
      transition:.2s;
    }
    .footer-social img:hover{transform:scale(1.12);filter:brightness(1.2)}
    .login-admin{
      position:fixed;
      left:6px;
      bottom:8px;
      font-size:7px;
      background:#0007;
      color:#d3d3d3;
      padding:8px 10px;
      border-radius:8px;
      box-shadow:0 6px 18px #0007;
      font-weight:600;
      transition:.2s;
      text-decoration:none;
    }
    .login-admin:hover{background:#0009;color:#fff;transform:translateY(-3px)}

    /* responsive tweaks */
    @media(max-width:1100px){
      .section-title{font-size:1.8rem}
      .proj-grid{gap:18px}
    }
    @media(max-width:820px){
      .about-section{padding:48px 12px 18px}
      .proj-thumb{height:100px}
    }
    @media(max-width:620px){
      .nav-container{padding:8px 12px}
      .logo img{width:36px;height:36px}
      .section-title{font-size:1.4rem;margin-bottom:18px}
      .proj-grid{grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px}
      .proj-thumb{height:84px}
      .modal{flex-direction:column}
      .modal-left{flex:auto}
      .modal-right{flex:auto;padding:16px}
    }
    @media(max-width:420px){
      .proj-grid{grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px}
      .proj-thumb{height:72px}
      .login-admin{font-size:10px}
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="nav-container">
      <div class="logo">
        <img src="<?= htmlspecialchars($b_foto) ?>" alt="<?= $nama_nav ?>">
        <?= $nama_nav ?>
      </div>

      <span class="menu-icon" id="menu-icon">&#9776;</span>

      <ul class="menu" id="menu-list">
        <li><a href="index.php">Home</a></li>
        <li><a href="biodata.php">Biodata</a></li>
        <li><a href="projects.php" class="active">Project</a></li>
        <li><a href="certificates.php">Certificate</a></li>
        <li><a href="sharing.php">Sharing</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </div>
  </nav>

  <!-- PROJECT SECTION (flex filler) -->
  <section class="about-section" id="projects" role="region" aria-labelledby="projectsTitle">
    <h1 id="projectsTitle" class="section-title">Projects</h1>

    <div class="proj-grid" id="projGrid">
      <?php foreach($projects as $p):
        $thumb = project_image_path($p['thumb']);
        $img = project_image_path($p['image']);
        $title = htmlspecialchars($p['title']);
        $desc = htmlspecialchars($p['description']);
        $date_text = htmlspecialchars($p['date_text']);
        $duration = htmlspecialchars($p['duration']);
        $doc_link = htmlspecialchars($p['doc_link']);
        $team = htmlspecialchars($p['team']);
      ?>
      <article class="proj-card"
               data-img="<?= htmlspecialchars($img) ?>"
               data-title="<?= $title ?>"
               data-desc="<?= $desc ?>"
               data-date="<?= $date_text ?>"
               data-duration="<?= $duration ?>"
               data-doc="<?= $doc_link ?>"
               data-team="<?= $team ?>">
        <div class="proj-title"><?= $title ?></div>
        <img class="proj-thumb" src="<?= htmlspecialchars($thumb ?: $img ?: 'p-placeholder.png') ?>" alt="<?= $title ?>">
        <div class="proj-actions"><button class="btn-details" type="button">Details</button></div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- optional separator (keamanan visual) -->
  <div class="section-sep" aria-hidden="true"></div>

  <!-- FOOTER -->
  <footer>
    <div class="footer-social" role="navigation" aria-label="social links">
     <a href="https://www.linkedin.com/in/stevira-rachel-gabriella-a8b7a7290?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"><img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn"></a>
      <a href="https://www.instagram.com/stevirachel?igsh=dDUxYjRzeDVueWYw"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram"></a>
      <a href="https://youtube.com/@stevirarachel1212?si=BjQBgvoQEJe-2xVg"><img src="https://cdn-icons-png.flaticon.com/512/1384/1384060.png" alt="YouTube"></a>
      <a href="https://github.com/steviragabriella-code"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111425.png" alt="GitHub"></a>
      <a href="https://www.tiktok.com/@steviraa?_r=1&_t=ZS-91VNpeh0ocf"><img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok"></a>
    </div>

    <div>© <?= date("Y") ?> <?= $nama_nav ?> | Portofolio UPNVJT</div>

    <a class="login-admin" href="admin.php">Login Admin</a>
  </footer>

  <!-- MODAL (unchanged) -->
  <div id="modalBackdrop" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modal-left">
        <img id="modalImage" src="" alt="">
      </div>
      <div class="modal-right">
        <div class="content-wrap">
          <div class="modal-top">
            <div class="duration-block" id="modalDurationBlock" style="display:none">
              <div class="duration-value" id="modalDurationValue"></div>
              <div class="duration-label">durasi pengerjaan</div>
            </div>
            <div class="text-block">
              <div id="modalTitle" class="modal-title"></div>
            </div>
          </div>

          <div id="modalDesc" class="modal-desc"></div>
          <div id="modalDate" class="meta"><b>Tanggal :</b> -</div>
          <div id="modalDoc" class="meta"><b>Dokumentasi :</b> <a id="modalDocLink" href="#" target="_blank" rel="noopener noreferrer"></a></div>
          <div id="modalTeam" class="meta"><b>Pengerjaan :</b> -</div>
        </div>

        <div class="modal-footer">
          <button id="modalClose" class="modal-close" aria-label="Tutup modal">Close</button>
        </div>
      </div>
    </div>
  </div>

<script>
  // mobile menu toggle (sama seperti index.php)
  const menuIcon = document.getElementById('menu-icon');
  const menuList = document.getElementById('menu-list');
  if (menuIcon && menuList) {
    menuIcon.addEventListener('click', ()=> menuList.classList.toggle('show'));
  }

  // modal delegation
  const projGrid = document.getElementById('projGrid');
  const backdrop = document.getElementById('modalBackdrop');
  const modalImg = document.getElementById('modalImage');
  const modalTitle = document.getElementById('modalTitle');
  const modalDesc = document.getElementById('modalDesc');
  const modalDate = document.getElementById('modalDate');
  const modalDocLink = document.getElementById('modalDocLink');
  const modalTeam = document.getElementById('modalTeam');
  const modalDurationBlock = document.getElementById('modalDurationBlock');
  const modalDurationValue = document.getElementById('modalDurationValue');
  const modalClose = document.getElementById('modalClose');

  projGrid.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-details');
    if(!btn) return;
    const card = btn.closest('.proj-card');
    if(!card) return;

    const img = card.getAttribute('data-img') || (card.querySelector('img') && card.querySelector('img').src) || '';
    const title = card.getAttribute('data-title') || (card.querySelector('.proj-title') && card.querySelector('.proj-title').innerText) || '';
    const desc = card.getAttribute('data-desc') || '';
    const date = card.getAttribute('data-date') || '-';
    const doc = card.getAttribute('data-doc') || '';
    const team = card.getAttribute('data-team') || '-';
    const duration = card.getAttribute('data-duration') || '';

    modalImg.src = img;
    modalImg.alt = title;
    modalTitle.textContent = title;
    modalDesc.textContent = desc;
    modalDate.innerHTML = '<b>Tanggal :</b> ' + date;
    if(doc){
      modalDocLink.href = doc;
      modalDocLink.textContent = doc;
    } else {
      modalDocLink.href = '#';
      modalDocLink.textContent = '-';
    }
    modalTeam.innerHTML = '<b>Pengerjaan :</b> ' + team;

    if(duration){
      modalDurationBlock.style.display = 'flex';
      modalDurationValue.textContent = duration;
    } else {
      modalDurationBlock.style.display = 'none';
      modalDurationValue.textContent = '';
    }

    backdrop.classList.add('show');
    backdrop.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
  });

  function closeModal(){
    backdrop.classList.remove('show');
    backdrop.setAttribute('aria-hidden','true');
    modalImg.src = '';
    document.body.style.overflow = '';
  }

  modalClose.addEventListener('click', (e)=>{ e.preventDefault(); closeModal(); });
  backdrop.addEventListener('click', (e)=>{ if(e.target === backdrop) closeModal(); });
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape' && backdrop.classList.contains('show')) closeModal(); });
</script>

</body>
</html>
