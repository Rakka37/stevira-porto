<?php
// create_admin.php - JALANKAN 1x lalu HAPUS
require_once __DIR__ . '/db.php'; // pastikan db.php ada dan terkonfigurasi (root + kosong jika XAMPP)

if (php_sapi_name() === 'cli') {
    echo "Jalankan lewat browser untuk convenience.\n";
    // continue anyway
}

// ---- UBAH SESUAI KEBUTUHAN ----
$name = 'Administrator';
$email = 'ramdhanirakkalevine37@gmail.com';
$password_plain = '12112004'; // ganti dengan password yang kamu inginkan
// -------------------------------

if (empty($email) || empty($password_plain)) {
    die('Isi $email dan $password_plain di file create_admin.php terlebih dahulu.');
}

$hashed = password_hash($password_plain, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'admin')");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashed
    ]);
    echo "Admin berhasil dibuat.<br>";
    echo "Email: <strong>" . htmlspecialchars($email) . "</strong><br>";
    echo "Password (plain): <strong>" . htmlspecialchars($password_plain) . "</strong><br>";
    echo "<b>CATAT:</b> Hapus file create_admin.php setelah ini untuk keamanan.";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "Gagal: pengguna dengan email ini sudah ada.<br>";
        echo "Coba gunakan reset_admin_pass.php jika ingin mengganti password.";
    } else {
        echo "Gagal membuat admin: " . htmlspecialchars($e->getMessage());
    }
}
