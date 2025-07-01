<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Total SMTP Accounts
$stmt1 = $conn->prepare("SELECT COUNT(*) FROM smtp_accounts WHERE user_id = ?");
$stmt1->execute([$user_id]);
$totalSMTP = $stmt1->fetchColumn();

// 2. Total Recipients
$stmt2 = $conn->prepare("SELECT COUNT(*) FROM emails WHERE user_id = ?");
$stmt2->execute([$user_id]);
$totalRecipients = $stmt2->fetchColumn();

// 3. Emails Sent in Last 7 Days
$stmt3 = $conn->prepare("SELECT COUNT(*) FROM sent_emails WHERE user_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt3->execute([$user_id]);
$totalSent7Days = $stmt3->fetchColumn();

// 4. Email Open Tracking - from sent_emails.status directly
$stmt4 = $conn->prepare("
    SELECT id, recipient_email, subject, sent_at, status
    FROM sent_emails
    WHERE user_id = ?
    ORDER BY sent_at DESC
    LIMIT 100
");
$stmt4->execute([$user_id]);
$emails = $stmt4->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>📊 Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📊 Dashboard</h2>

    <!-- 🔢 Summary Cards -->
    <div class="row text-center mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5>📡 Total SMTP Accounts</h5>
                <h3><?= $totalSMTP ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5>📧 Total Recipients</h5>
                <h3><?= $totalRecipients ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5>📤 Emails Sent (7 Days)</h5>
                <h3><?= $totalSent7Days ?></h3>
            </div>
        </div>
    </div>

    <!-- 📬 Email Open Tracking -->
    <h4 class="mt-5">📬 Email Open Tracking</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Sent At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($emails) > 0): ?>
                <?php foreach ($emails as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['recipient_email']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= $row['sent_at'] ?></td>
                        <td>
                            <?php if (strtolower($row['status']) === 'opened'): ?>
                                <span class="badge bg-success">✅ Opened</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">❌ Not Opened</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">No emails sent yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 🔗 Navigation Buttons -->
    <div class="mt-4">
        <a href="upload_emails.php" class="btn btn-outline-primary">📁 Upload Emails</a>
        <a href="smtp_settings.php" class="btn btn-outline-secondary">📡 SMTP Settings</a>
        <a href="compose_email.php" class="btn btn-outline-success">✉️ Compose Email</a>
        <a href="../auth/logout.php" class="btn btn-outline-danger float-end">🚪 Logout</a>
    </div>
</div>
</body>
</html>
