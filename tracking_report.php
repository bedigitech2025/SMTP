<?php
echo "<h3>Open Tracking Log</h3>";
echo nl2br(file_get_contents(__DIR__ . '/tracking/open_log.txt'));

echo "<h3>Click Tracking Log</h3>";
echo nl2br(file_get_contents(__DIR__ . '/tracking/click_log.txt'));
?>
