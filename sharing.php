<?php
// sharing.php (terhubung ke admin-sharing.php / uploads yang sama)
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// --- shared uploads (biodata uses uploads/ too) ---
$uploadDir = __DIR__ . '/uploads/';
$uploadUrlBase = 'uploads/'; // relatif ke root file
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// ambil biodata singkat untuk navbar (sama logic dg admin)
$stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
$b = $stmt->fetch(PDO::FETCH_ASSOC);
$defaults_b = [
    'nama' => 'Stevira Rachel Gabriella',
    'foto' => null
];
$b = $b ? array_merge($defaults_b, $b) : $defaults_b;

// avatar url identik dgn admin-* files
$avatar_url = (!empty($b['foto']) && file_exists($uploadDir . $b['foto'])) ? $uploadUrlBase . rawurlencode($b['foto']) : 'stevira.jpg';
$nama_nav = htmlspecialchars($b['nama'] ?? $defaults_b['nama'], ENT_QUOTES);

// --- sharing uploads dir (consistent with admin-sharing.php) ---
$shareRel = 'uploads/';
$shareSub = 'sharing/';
$shareFsDir = __DIR__ . '/' . $shareRel . $shareSub;
if (!is_dir(__DIR__ . '/' . $shareRel)) mkdir(__DIR__ . '/' . $shareRel, 0755, true);
if (!is_dir($shareFsDir)) mkdir($shareFsDir, 0755, true);

// ambil semua sharing dari DB
$shares = $pdo->query("SELECT * FROM sharing ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// helper untuk resolve gambar sharing (cari di uploads/sharing/)
function resolve_share_image(array $r, string $shareFsDir, string $shareRel, string $shareSub): string {
    $img = trim((string)($r['image'] ?? ''));
    if ($img === '') return '';
    $base = basename($img);
    $fs = $shareFsDir . $base;
    if (file_exists($fs)) return $shareRel . $shareSub . rawurlencode($base);
    $fs2 = __DIR__ . '/' . ltrim($img, '/');
    if (file_exists($fs2)) return $img;
    // last-ditch: maybe DB stored relative under uploads/sharing already
    $maybe = __DIR__ . '/' . ltrim($shareRel . $shareSub . $img, '/');
    if (file_exists($maybe)) return $shareRel . $shareSub . rawurlencode($img);
    return '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sharing - <?= $nama_nav ?></title>
  <style>
    /* ringkas & konsisten dg biodata/certificate */
    *{box-sizing:border-box;margin:0;padding:0;font-family:Arial,Helvetica,sans-serif}
    html,body{height:100%}
    body{background:linear-gradient(135deg,#1b1d26,#2d2a35);color:#eee;text-align:center}
    a{color:inherit;text-decoration:none}

    /* navbar (sama style dg admin/public lain) */
    .navbar{position:fixed;top:0;left:0;width:100%;background:rgba(66,66,151,.7);backdrop-filter:blur(8px);box-shadow:0 3px 8px rgba(0,0,0,.4);z-index:10}
    .nav-container{max-width:1300px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding:10px 20px}
    .logo{display:flex;align-items:center;gap:10px;color:#f8c146;font-weight:750;font-size: 1rem}
    .logo img{width:40px;height:40px;border-radius:50%;border:3px solid #f8c146;object-fit:cover;background:#fff}
    .menu{display:flex;gap:25px;list-style:none}
    .menu li a{color:#eee;font-weight:500;}
    .menu li a:hover{color:#f8c146;transform:translateY(-2px);transition:.18s}
    .menu-icon{display:none;color:#f8c146;font-size:1.8rem;cursor:pointer}
    @media(max-width:768px){.menu{display:none;position:absolute;top:60px;left:0;width:100%;flex-direction:column;background:rgba(25,25,35,.95);padding:18px 0}.menu.show{display:flex}.menu-icon{display:block}}
    .menu a.active{position:relative;color:#fff}
    .menu a.active::after{content:"";position:absolute;left:0;right:0;bottom:-8px;height:4px;background:#f8c146;border-radius:4px;}

    /* section */
    .about-section{background:#000;margin-top:60px;padding:100px 20px 60px;border-bottom:4px solid #f8c146}
    .section-title{color:#f8c146;font-weight:800;font-size:2.2rem;margin-bottom:40px}

    /* grid & card (sama ukuran dg certificate) */
    .share-grid{max-width:1180px;margin:0 auto;display:flex;flex-wrap:wrap;gap:34px;justify-content:center;padding:10px}
    .share-card{width:190px;background:#3b3b70;border-radius:6px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 4px 15px rgba(0,0,0,.4);transition:transform .18s}
    .share-card:hover{transform:translateY(-6px)}
    .share-title{padding:10px;background:#3b3b70;color:#fff;font-weight:700;font-size:.75rem;text-align:center;min-height:56px;display:flex;align-items:center;justify-content:center}
    .share-thumb{width:100%;height:110px;object-fit:cover;background:#222;display:block}
    .share-actions{margin-top:auto;background:#2d2a35;padding:0}

    /* BUTTON DETAILS: default KUNING, hover berubah (lebih cerah) */
    .btn-details{display:block;width:100%;padding:10px 12px;border:0;background:#f8c146;color:#000;font-weight:800;cursor:pointer;text-align:center;transition:background .12s,transform .12s,box-shadow .12s}
    .btn-details:hover,.btn-details:focus{background:#ffd66a;transform:translateY(-2px);box-shadow:0 6px 18px rgba(248,193,70,.12);outline:none}

    /* modal */
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.66);display:none;align-items:center;justify-content:center;padding:18px;z-index:2000}
    .modal-backdrop.show{display:flex}
    .modal{width:100%;max-width:1100px;background:#111;border-radius:10px;overflow:hidden;display:flex;gap:18px;box-shadow:0 20px 60px rgba(0,0,0,.7)}
    .modal-left{flex:1;background:#000;display:flex;align-items:center;justify-content:center;padding:12px;min-width:320px}
    .modal-left img{width:100%;height:auto;max-height:82vh;object-fit:contain;display:block}
    .modal-right{flex:1;padding:28px 30px;color:#eee;display:flex;flex-direction:column;justify-content:space-between;background:#3b3b70;min-width:320px}
    .modal-content{overflow:auto;max-height:72vh;padding-right:6px}
    .modal-title{font-size:1.5rem;color:#f8c146;font-weight:800;margin-bottom:14px;text-align:left;line-height:1.1}
    .modal-desc{color:#e6e6e6;line-height:1.7;font-size:1rem;text-align:left;white-space:pre-wrap;margin-bottom:14px}
    .modal-file{color:#bcd1ff;font-size:.95rem;word-break:break-all;text-align:left;margin-bottom:18px}
    .modal-file a{color:#bcd1ff}
    .modal-footer{display:flex;justify-content:center;align-items:center;padding-top:8px;padding-bottom:6px}
    .modal-close{background:#f8c146;color:#000;border:0;padding:10px 18px;border-radius:22px;font-weight:700;cursor:pointer;box-shadow:0 6px 18px rgba(248,193,70,.12)}
    .modal-close:hover{background:#ffd66a}

    /* small screens: stack vertical */
    @media(max-width:980px){.modal{flex-direction:column}.modal-left{min-width:auto}.modal-right{min-width:auto;padding:20px}.modal-content{max-height:50vh}}

    /* footer + login */
    footer{background:rgba(66,66,151,.7);padding:20px 0 30px;color:#aaa;position:relative;border-top:1px solid rgba(255,255,255,.1)}
    .footer-social{margin-bottom:12px}
    .footer-social a{margin:0 8px;display:inline-block}
    .footer-social img{width:30px;height:30px;border-radius:8px;filter:brightness(.95)}
    .login-admin{position:fixed;left:6px;bottom:8px;background:rgba(0,0,0,.45);color:#d3d3d3;font-size: 7px;padding:8px 10px;border-radius:8px;font-weight:600;box-shadow:0 6px 18px rgba(0,0,0,.45)}
    .login-admin:hover{color:#fff;background:rgba(0,0,0,.6)}
    @media (max-width: 420px) {.login-admin { left: 8px; bottom: 8px; padding: 6px 8px; font-size: 13px; }}
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="nav-container">
      <div class="logo">
        <img src="<?= htmlspecialchars($avatar_url, ENT_QUOTES) ?>" alt="Stevira">
        <?= $nama_nav ?>
      </div>

      <span class="menu-icon" id="menu-icon">&#9776;</span>

      <ul class="menu" id="menu-list">
        <li><a href="index.php">Home</a></li>
        <li><a href="biodata.php">Biodata</a></li>
        <li><a href="projects.php">Project</a></li>
        <li><a href="certificates.php">Certificate</a></li>
        <li><a href="sharing.php" class="active">Sharing</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </div>
  </nav>

  <!-- SHARING -->
  <section class="about-section" id="sharing">
    <h1 class="section-title">Sharing</h1>

    <div class="share-grid" id="shareGrid">
      <?php if (empty($shares)): ?>
        <div style="color:#ddd">Belum ada konten untuk ditampilkan.</div>
      <?php else: ?>
        <?php foreach ($shares as $s):
          $img = resolve_share_image($s, $shareFsDir, $shareRel, $shareSub);
          $imgOutput = $img ?: 'p-placeholder.png';
          $titleAttr = htmlspecialchars($s['title'] ?? '', ENT_QUOTES);
          $descAttr = htmlspecialchars($s['description'] ?? '', ENT_QUOTES);
          $fileAttr = htmlspecialchars($s['link'] ?? '', ENT_QUOTES);
        ?>
        <article class="share-card"
                 data-img="<?= $imgOutput ?>"
                 data-title="<?= $titleAttr ?>"
                 data-desc="<?= $descAttr ?>"
                 data-file="<?= $fileAttr ?>">
          <div class="share-title"><?= htmlspecialchars($s['title'] ?? '') ?></div>
          <img class="share-thumb" src="<?= $imgOutput ?>" alt="<?= htmlspecialchars($s['title'] ?? '') ?>">
          <div class="share-actions"><button class="btn-details">Details</button></div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- MODAL -->
  <div id="modalBackdrop" class="modal-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modal-left"><img id="modalImage" src="" alt=""></div>
      <div class="modal-right">
        <div class="modal-content">
          <div id="modalTitle" class="modal-title">Judul</div>
          <div id="modalDesc" class="modal-desc"></div>
          <div id="modalFile" class="modal-file"></div>
        </div>

        <div class="modal-footer">
          <button id="modalClose" class="modal-close" aria-label="Tutup modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    <div class="footer-social">
      <a href="https://www.linkedin.com/in/stevira-rachel-gabriella-a8b7a7290?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"><img src="https://cdn-icons-png.flaticon.com/512/174/174857.png" alt="LinkedIn"></a>
      <a href="https://www.instagram.com/stevirachel?igsh=dDUxYjRzeDVueWYw"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram"></a>
      <a href="https://youtube.com/@stevirarachel1212?si=BjQBgvoQEJe-2xVg"><img src="https://cdn-icons-png.flaticon.com/512/1384/1384060.png" alt="YouTube"></a>
      <a href="https://github.com/steviragabriella-code"><img src="https://cdn-icons-png.flaticon.com/512/2111/2111425.png" alt="GitHub"></a>
      <a href="https://www.tiktok.com/@steviraa?_r=1&_t=ZS-91VNpeh0ocf"><img src="https://cdn-icons-png.flaticon.com/512/3046/3046121.png" alt="TikTok"></a>
    </div>
    <div>© <?= date("Y") ?> <?= $nama_nav ?> | Portofolio UPNVJT</div>
    <a class="login-admin" href="admin-sharing.php">Login Admin</a>
  </footer>

  <script>
    // mobile menu toggle
    const menuIcon = document.getElementById('menu-icon');
    const menuList = document.getElementById('menu-list');
    if(menuIcon && menuList) menuIcon.addEventListener('click', ()=> menuList.classList.toggle('show'));

    // modal handling
    const shareGrid = document.getElementById('shareGrid');
    const backdrop = document.getElementById('modalBackdrop');
    const modalImg = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalFile = document.getElementById('modalFile');
    const modalClose = document.getElementById('modalClose');

    shareGrid.addEventListener('click', function(e){
      const btn = e.target.closest('.btn-details');
      if(!btn) return;
      e.preventDefault();
      const card = btn.closest('.share-card');
      if(!card) return;
      const img = card.getAttribute('data-img') || (card.querySelector('img') && card.querySelector('img').src) || '';
      const title = card.getAttribute('data-title') || (card.querySelector('.share-title') && card.querySelector('.share-title').innerText) || '';
      const desc = card.getAttribute('data-desc') || '';
      const file = card.getAttribute('data-file') || '';

      modalImg.src = img; modalImg.alt = title;
      modalTitle.textContent = title;
      modalDesc.textContent = desc || 'Deskripsi belum ditambahkan.';
      modalFile.innerHTML = file ? ('File : <a href="'+file+'" target="_blank" rel="noopener noreferrer">'+file+'</a>') : '';

      backdrop.classList.add('show'); backdrop.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden';
    });

    function closeModal(){ backdrop.classList.remove('show'); backdrop.setAttribute('aria-hidden','true'); modalImg.src = ''; document.body.style.overflow = ''; }
    modalClose.addEventListener('click', function(e){ e.preventDefault(); closeModal(); });
    backdrop.addEventListener('click', function(e){ if(e.target === backdrop) closeModal(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && backdrop.classList.contains('show')) closeModal(); });
  </script>
</body>
</html>
