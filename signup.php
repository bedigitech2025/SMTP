<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    try {
        $stmt->execute([$email, $password]);
        $_SESSION['user_id'] = $conn->lastInsertId();
        header('Location: ../dashboard/index.php');
        exit();
    } catch (PDOException $e) {
        $error = "Email already registered.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup</title>
</head>
<body>
<h2>Signup</h2>
<form method="POST">
    <input type="email" name="email" required placeholder="Email"><br>
    <input type="password" name="password" required placeholder="Password"><br>
    <button type="submit">Sign Up</button>
</form>
<?php if (!empty($error)) echo "<p>$error</p>"; ?>
</body>
</html>
