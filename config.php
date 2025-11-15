<?php
$host = 'localhost';
$dbname = 'midterm_itel304';
$username = 'root';  // Palitan kung iba ang username mo
$password = '';      // Palitan kung may password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>