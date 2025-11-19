<?php
// paste this into check_files.php and open it from your browser
$cwd = __DIR__;
$files = scandir($cwd);
echo "<h3>Current folder: {$cwd}</h3>";
echo "<ul>";
foreach ($files as $f) {
    echo "<li>" . htmlspecialchars($f) . "</li>";
}
echo "</ul>";
