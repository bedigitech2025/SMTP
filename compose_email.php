<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require '../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$preview = "";
$allDatabaseEmails = [];

$stmt = $conn->prepare("SELECT email FROM emails WHERE user_id = ?");
$stmt->execute([$user_id]);
$allDatabaseEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'];
    $key = rand(1000000000000000, 9999999999999999) ;
    $body = $_POST['body'];
    $cc = $_POST['cc'] ?? '';
    $bcc = $_POST['bcc'] ?? '';
    $use_db_emails = isset($_POST['use_db_emails']);

    $manualEmails = [];
    if (!empty($to)) {
        $manualEmails = array_map('trim', explode(',', $to));
    }

    $finalRecipients = $manualEmails;
    if ($use_db_emails && !empty($allDatabaseEmails)) {
        $finalRecipients = array_merge($finalRecipients, $allDatabaseEmails);
        $finalRecipients = array_unique($finalRecipients);
    }

    if (isset($_POST['preview'])) {
        $preview = "<h4>Email Preview:</h4>
            <p><strong>To:</strong> " . implode(', ', $finalRecipients) . "</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Body:</strong><br>{$body}</p>
            <p><strong>CC:</strong> {$cc}</p>
            <p><strong>BCC:</strong> {$bcc}</p>";
    } else {
        $smtp_stmt = $conn->prepare("SELECT * FROM smtp_accounts WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $smtp_stmt->execute([$user_id]);
        $smtp = $smtp_stmt->fetch();

        if ($smtp && !empty($finalRecipients)) {
            try {
                foreach ($finalRecipients as $recipientEmail) {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $smtp['smtp_host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtp['smtp_user'];
                    $mail->Password   = $smtp['smtp_pass'];
                    $mail->SMTPSecure = $smtp['encryption'];
                    $mail->Port       = $smtp['smtp_port'];

                    $mail->setFrom($smtp['smtp_user'], 'Mailer');
                    $mail->addAddress($recipientEmail);

                    if (!empty($cc)) {
                        foreach (explode(',', $cc) as $cc_email) {
                            $mail->addCC(trim($cc_email));
                        }
                    }

                    if (!empty($bcc)) {
                        foreach (explode(',', $bcc) as $bcc_email) {
                            $mail->addBCC(trim($bcc_email));
                        }
                    }

                    $mail->isHTML(true);
                    $mail->Subject = $subject;

                    $trackingPixel = "<img src='http://localhost/php_email_dashboard/track/open.php?email=" . urlencode($recipientEmail) . "&key=" . urlencode($key) . "' width='1' height='1'>";

                    $bodyWithTracking = preg_replace_callback('/href=\"([^\"]+)\"/i', function($matches) use ($recipientEmail, $user_id) {
                        $url = urlencode($matches[1]);
                        return 'href="http://localhost/php_email_dashboard/track/click.php?url=' . $url . '&email=' . urlencode($recipientEmail) . '&user_id=' . $user_id . '"';
                    }, $body);

                    $mail->Body = $bodyWithTracking . $trackingPixel;

                    $mail->send();

                    $log = $conn->prepare("
                    INSERT INTO sent_emails (user_id, recipient_email, subject, body, email_key)
                     VALUES (?, ?, ?, ?, ?)
                     ");
                    $log->execute([$user_id, $recipientEmail, $subject, $body, $key]);

                    $mail->clearAddresses();
                    $mail->clearCCs();
                    $mail->clearBCCs();
                }

                $message = "<div class='alert alert-success mt-3'>✅ Emails sent successfully (with tracking)!</div>";
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger mt-3'>❌ Mailer Error: {$mail->ErrorInfo}</div>";
            }
        } else {
            $message = "<div class='alert alert-warning mt-3'>⚠️ No recipient email or SMTP config found.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Compose Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="mb-4">✉️ Compose Email</h2>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">To:</label>
                <input type="text" name="to" class="form-control" placeholder="Manual recipient emails (comma-separated)" value="<?= isset($_POST['to']) ? htmlspecialchars($_POST['to']) : '' ?>">
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="use_db_emails" id="use_db_emails" <?= isset($_POST['use_db_emails']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="use_db_emails">Use Emails from Database (<?= count($allDatabaseEmails) ?> found)</label>
            </div>

            <div class="mb-3">
                <label class="form-label">Subject:</label>
                <input type="text" name="subject" class="form-control" placeholder="Subject" required value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Body:</label>
                <textarea name="body" class="form-control" placeholder="Email body here..." rows="10" required><?= isset($_POST['body']) ? htmlspecialchars($_POST['body']) : '' ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">CC:</label>
                <input type="text" name="cc" class="form-control" placeholder="CC (comma-separated)" value="<?= isset($_POST['cc']) ? htmlspecialchars($_POST['cc']) : '' ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">BCC:</label>
                <input type="text" name="bcc" class="form-control" placeholder="BCC (comma-separated)" value="<?= isset($_POST['bcc']) ? htmlspecialchars($_POST['bcc']) : '' ?>">
            </div>

            <button type="submit" name="preview" class="btn btn-secondary">👁️ Preview</button>
            <button type="submit" name="send" class="btn btn-primary">📤 Send Email</button>
        </form>

        <?php
        if (!empty($preview)) {
            echo "<div class='card mt-4 p-3 bg-light'>{$preview}</div>";
        }

        if (!empty($message)) {
            echo $message;
        }
        ?>
    </div>
</div>
</body>
</html>
