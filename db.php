<?php
$host = 'localhost';
$dbname = 'email_dashboard';  // your database name
$username = 'root';           // default for XAMPP
$password = '';               // leave empty for XAMPP

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
