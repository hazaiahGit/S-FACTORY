<?php
$file = "bootstrap/app.php";
$content = file_get_contents($file);
$content = str_replace(
    "\$middleware->web(append: [",
    "\$middleware->alias(['system.admin' => \App\Http\Middleware\SystemAdminMiddleware::class]);\n\n        \$middleware->web(append: [",
    $content
);
file_put_contents($file, $content);
echo "Registered middleware in bootstrap/app.php\n";
?>
