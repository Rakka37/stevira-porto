<?php
// admin.php
session_start();
require_once __DIR__ . '/db.php'; // pastikan db.php sesuai: root + password kosong

// CSRF token sederhana
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(16));
}

// jika sudah login, langsung ke admin-biodata
if (isset($_SESSION['user_id'])) {
    header('Location: admin-biodata.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // cek CSRF
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_login'], $_POST['csrf'])) {
        $errors[] = "Permintaan tidak valid.";
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Masukkan email yang valid.";
        }
        if ($password === '') {
            $errors[] = "Password tidak boleh kosong.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                unset($_SESSION['csrf_login']);
                header('Location: admin-biodata.php');
                exit;
            } else {
                $errors[] = "Email atau password salah.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Stevira</title>
<style>
body{margin:0;padding:0;font-family:Arial;background:#f2e79a;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{width:90%;max-width:450px;background:#3c3763;padding:30px;border-radius:18px;color:#fff;text-align:center;box-shadow:0 5px 18px rgba(0,0,0,.25)}
.photo{width:110px;height:110px;border-radius:50%;border:5px solid #ffeb3b;margin:-10px auto 15px;overflow:hidden}
.photo img{width:100%;height:100%;object-fit:cover}
.line{width:160px;height:4px;background:#ffeb3b;margin:8px auto 25px;border-radius:3px}
.field{background:#6e6e6e;border-radius:12px;display:flex;align-items:center;padding:10px 12px;margin-bottom:16px}
.field input{border:none;background:none;width:100%;outline:none;color:#fff;font-size:14px}
.btn{background:#ffeb3b;color:#000;padding:10px 28px;border:none;border-radius:14px;cursor:pointer;font-weight:bold}
.errors{background:#ffeded;color:#900;padding:10px;margin-bottom:12px;border-radius:8px;text-align:left}
.back{position:fixed;left:12px;bottom:12px;color:#333;text-decoration:none}
</style>
</head>
<body>

<a href="index.php" class="back">Back</a>

<div class="card">
  <div class="photo"><img src="stevira.jpg" alt="Stevira"></div>
  <h2>Stevira Rachel Gabriella</h2>
  <div class="line"></div>

  <?php if (!empty($errors)): ?>
    <div class="errors"><ul style="margin:0;padding-left:18px"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_login'])?>">
    <label style="display:block;text-align:left;margin-bottom:6px;color:#fff">Email</label>
    <div class="field"><input type="email" name="email" placeholder="masukkan email anda..." required value="<?=htmlspecialchars($email)?>"></div>

    <label style="display:block;text-align:left;margin-bottom:6px;color:#fff">Password</label>
    <div class="field"><input type="password" name="password" placeholder="masukkan password anda..." required></div>

    <button class="btn" type="submit">Login</button>
  </form>

  <div style="margin-top:12px;color:#ccc">Belum punya akun? hubungi admin.</div>
</div>

</body>
</html>
