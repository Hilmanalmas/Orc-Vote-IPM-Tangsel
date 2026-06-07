<?php
require_once 'config.php';

try {
    // Check if theme_color exists
    $stmt = $pdo->query("SHOW COLUMNS FROM settings LIKE 'theme_color'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN theme_color VARCHAR(255) DEFAULT '#00984B,#E86729,#00743F'");
        echo "Kolom 'theme_color' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'theme_color' sudah ada.<br>";
    }

    // Check if logo_path exists
    $stmt = $pdo->query("SHOW COLUMNS FROM settings LIKE 'logo_path'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN logo_path VARCHAR(255) DEFAULT 'media/Logo_PD_IPM.png'");
        echo "Kolom 'logo_path' berhasil ditambahkan!<br>";
    } else {
        echo "Kolom 'logo_path' sudah ada.<br>";
    }

    // Try to create the uploads directory explicitly
    $uploadDir = __DIR__ . '/uploads/logos/';
    if (!is_dir($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            echo "Direktori $uploadDir berhasil dibuat!<br>";
        } else {
            echo "<span style='color:red;'>Gagal membuat direktori $uploadDir. Pastikan PHP/www-data memiliki izin tulis (write permission) di folder utama (Orc-Vote-IPM-Tangsel). Anda bisa menjalankan <code>chmod -R 777 uploads</code> jika foldernya dibuat manual.</span><br>";
        }
    } else {
        echo "Direktori $uploadDir sudah ada dan siap digunakan.<br>";
    }

    echo "<h3>Migrasi Selesai. Silakan coba kembali upload logo di halaman admin.</h3>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
