<?php
// Run only from an SSH/hosting terminal, after importing schema.sql.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}
$configFile = __DIR__ . '/../config/db.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Missing config/db.php. Create it from config/db.example.php first.\n");
    exit(1);
}
require_once $configFile;
if (!isset($conn) || !($conn instanceof PDO)) {
    fwrite(STDERR, "Database connection unavailable. Check config/db.php.\n");
    exit(1);
}
try {
    // Do not change existing users or reveal a new password if the account exists.
    if ((int) $conn->query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) {
        fwrite(STDERR, "An account already exists. No account was changed.\n");
        exit(1);
    }
    $password = bin2hex(random_bytes(16));
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
        throw new RuntimeException('Password hashing failed.');
    }
    $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->execute(['admin', $hash]);
    echo "Administrator created.\nUsername: admin\nTemporary password: ", $password, "\n";
    echo "Save it securely, log in over HTTPS, and change it in Account.\n";
} catch (Throwable $e) {
    error_log('DataPanel admin setup failed: ' . $e->getMessage());
    fwrite(STDERR, "Could not create the administrator. Verify the database and schema, then try again.\n");
    exit(1);
}
