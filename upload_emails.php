<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['email_csv'])) {
    $file = $_FILES['email_csv']['tmp_name'];

    if (($handle = fopen($file, 'r')) !== false) {
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $email = trim($data[0]);

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $conn->prepare("INSERT INTO emails (user_id, email) VALUES (?, ?)");
                $stmt->execute([$user_id, $email]);
            }
        }
        fclose($handle);
        $success = "Emails uploaded successfully!";
    } else {
        $error = "Unable to read the file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Upload Emails</title></head>
<body>
<h2>Upload Emails (CSV)</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="email_csv" accept=".csv" required>
    <button type="submit">Upload</button>
</form>

<?php
if ($success) echo "<p style='color:green;'>$success</p>";
if ($error) echo "<p style='color:red;'>$error</p>";
?>
</body>
</html>
