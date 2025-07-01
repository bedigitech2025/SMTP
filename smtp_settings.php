<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";

// Save SMTPs if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['smtp'])) {
    foreach ($_POST['smtp'] as $smtp) {
        $stmt = $conn->prepare("INSERT INTO smtp_accounts (user_id, smtp_host, smtp_port, smtp_user, smtp_pass, encryption) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $smtp['host'],
            $smtp['port'],
            $smtp['user'],
            $smtp['pass'],
            $smtp['encryption']
        ]);
    }
    $success = "✅ SMTP details saved!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SMTP Settings</title>
    <style>
        .smtp-field {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .auth-result {
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
            color: blue;
        }
        input {
            display: block;
            margin: 5px 0;
            width: 300px;
        }
    </style>
</head>
<body>
<h2>📡 SMTP Settings</h2>

<form method="POST">
    <div id="smtp-container">
        <div class="smtp-field">
            <input type="text" name="smtp[0][host]" placeholder="SMTP Host" required>
            <input type="number" name="smtp[0][port]" placeholder="Port" required>
            <input type="text" name="smtp[0][user]" placeholder="Username" required>
            <input type="password" name="smtp[0][pass]" placeholder="Password" required>
            <input type="text" name="smtp[0][encryption]" placeholder="tls/ssl" required>
            <button type="button" onclick="authenticateSMTP(this)">Authenticate</button>
            <span class="auth-result"></span>
        </div>
    </div>
    <button type="button" onclick="addSMTPField()">+ Add Another SMTP</button><br><br>
    <button type="submit">💾 Save</button>
</form>

<?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>

<script>
let smtpIndex = 1;

function addSMTPField() {
    const container = document.getElementById('smtp-container');
    const firstField = document.querySelector('.smtp-field');
    const newField = firstField.cloneNode(true);

    const inputs = newField.querySelectorAll('input');
    inputs.forEach(input => {
        input.value = "";
        input.name = input.name.replace(/\[0\]/, `[${smtpIndex}]`);
    });

    newField.querySelector('.auth-result').innerText = "";
    container.appendChild(newField);
    smtpIndex++;
}

function authenticateSMTP(button) {
    const field = button.closest('.smtp-field');
    const host = field.querySelector('input[name*="[host]"]').value;
    const port = field.querySelector('input[name*="[port]"]').value;
    const user = field.querySelector('input[name*="[user]"]').value;
    const pass = field.querySelector('input[name*="[pass]"]').value;
    const encryption = field.querySelector('input[name*="[encryption]"]').value;
    const result = field.querySelector('.auth-result');

    result.innerText = '🔄 Authenticating...';

    fetch('/php_email_dashboard/test_smtp.php', {

        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ host, port, user, pass, encryption })
    })
    .then(res => res.text())
    .then(text => {
        result.innerText = text;
        result.style.color = text.includes("✅") ? "green" : "red";
    })
    .catch(err => {
        console.error(err);
        result.innerText = '❌ Error testing SMTP.';
        result.style.color = "red";
    });
}
</script>
</body>
</html>
