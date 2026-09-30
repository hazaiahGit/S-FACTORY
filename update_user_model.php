<?php
$file = "app/Models/User.php";
$content = file_get_contents($file);
$content = str_replace(
    "'business_id',",
    "'business_id',\n        'is_system_admin',",
    $content
);
$content = str_replace(
    "'is_active' => 'boolean',",
    "'is_active' => 'boolean',\n            'is_system_admin' => 'boolean',",
    $content
);
file_put_contents($file, $content);
echo "Updated User model.\n";
?>
