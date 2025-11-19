<?php
// logout.php
declare(strict_types=1);
session_start();

// ambil biodata singkat (jika ada) untuk tampilan
require_once __DIR__ . '/db.php';
$stmt = null;
try {
    $stmt = $pdo->query("SELECT * FROM biodata ORDER BY id ASC LIMIT 1");
} catch (Throwable $e) {
    // abaikan jika db belum siap — tetap tampilkan halaman logout
}
$b = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
$defaults = ['nama'=>'Stevira Rachel Gabriella','foto'=>null];
$data = $b ? array_merge($defaults, $b) : $defaults;
$uploadDir = __DIR__ . '/uploads/';
$avatar = (!empty($data['foto']) && file_exists($uploadDir . $data['foto'])) ? 'uploads/' . rawurlencode($data['foto']) : 'stevira.jpg';
$nama = htmlspecialchars($data['nama'], ENT_QUOTES);

// CSRF token simple untuk form
if (empty($_SESSION['csrf_logout'])) {
    $_SESSION['csrf_logout'] = bin2hex(random_bytes(16));
}

// proses logout bila form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_logout'], (string)$token)) {
        // token tidak valid -> tetap tampilkan pesan
        $error = "Permintaan tidak valid.";
    } else {
        // destroy session dan redirect ke halaman login (admin.php)
        $_SESSION = [];
        // hapus cookie session jika ada
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        // regen token agar aman (untuk kasus rare)
        header('Location: admin.php?logged_out=1');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Logout - <?= $nama ?></title>
<style>
  body{
    margin:0;
    padding:0;
    background:#f2e79a;
    font-family: Arial, sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
  }

  .card{
    width:90%;
    max-width:420px;
    background:#3c3763;
    padding:30px 25px 35px;
    border-radius:18px;
    box-shadow:0 8px 25px rgba(0,0,0,0.25);
    text-align:center;
    color:#fff;
  }

  .photo{
    width:110px; height:110px;
    margin:-10px auto 15px;
    border-radius:50%;
    border:5px solid #ffeb3b;
    overflow:hidden;
  }
  .photo img{width:100%;height:100%;object-fit:cover;}

  h2{margin:8px 0 5px; font-weight:600;}
  p{color:#ddd; font-size:15px; margin-bottom:22px;}

  .btn{
    display:block;
    width:100%;
    padding:12px 0;
    border-radius:12px;
    font-weight:bold;
    cursor:pointer;
    border:none;
    font-size:15px;
    margin-bottom:12px;
    transition:0.2s;
  }
  .logout-btn{
    background:#ff6b6b;
    color:#fff;
  }
  .logout-btn:hover{transform:translateY(-2px);}

  .cancel-btn{
    background:#ffeb3b;
    color:#000;
  }
  .cancel-btn:hover{transform:translateY(-2px);}

  .back{
    display:block;
    margin-top:10px;
    font-size:13px;
    color:#ccc;
    text-decoration:none;
  }

  .msg-error{background:#ffecec;color:#b00000;padding:10px;border-radius:8px;margin:12px 0}
  .msg-info{color:#ddd;font-size:13px;margin-bottom:8px}
</style>
</head>
<body>

<div class="card">

  <div class="photo">
    <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= $nama ?>">
  </div>

  <h2><?= $nama ?></h2>
  <p>Apakah Anda yakin ingin keluar dari halaman admin?</p>

  <?php if (!empty($error)): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" style="margin:0">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_logout']) ?>">
    <button class="btn logout-btn" type="submit" name="confirm" value="1">Ya, Logout</button>
  </form>

  <button class="btn cancel-btn" onclick="window.location.href='admin-biodata.php'">
    Batal
  </button>

  <a href="admin-biodata.php" class="back">Kembali ke Dashboard</a>

</div>

</body>
</html>
