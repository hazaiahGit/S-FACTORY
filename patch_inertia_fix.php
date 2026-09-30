<?php
$file = "app/Http/Middleware/HandleInertiaRequests.php";
$content = file_get_contents($file);
$content = str_replace("clone \$user->active_branch_id", "\$user->active_branch_id", $content);
file_put_contents($file, $content);
echo "Fixed HandleInertiaRequests.\n";
?>
