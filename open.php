<?php
// track/open.php

require_once '../config/db.php';

if (isset($_GET['email'], $_GET['user_id'])) {
    $email = urldecode($_GET['email']);
    $user_id = intval($_GET['user_id']);
    $opened_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO email_opens (user_id, email, opened_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $email, $opened_at]);
}

// return a 1x1 transparent image
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
exit;
