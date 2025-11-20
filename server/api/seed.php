<?php
require_once 'config/db.php';

// Security check: Only allow in development mode or with a secret key
// For this demo, we'll just check if it's localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied');
}

try {
    $sql = file_get_contents('../../database/seed.sql');

    // Split SQL by semicolon to execute multiple statements
    // Note: This is a simple splitter and might fail with complex SQL containing semicolons in strings
    // But for our seed file it should be fine
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "Database seeded successfully!<br>";
    echo "Added 5 teachers, 2 parents, and sample reviews.";

} catch (PDOException $e) {
    echo "Seeding failed: " . $e->getMessage();
}
