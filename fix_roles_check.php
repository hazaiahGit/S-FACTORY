<?php
$file = "resources/js/Pages/Stock/Movements.vue";
$content = file_get_contents($file);

$content = str_replace(
    'v-if="$page.props.auth.user.roles?.includes(\'Super Admin\')"',
    'v-if="$page.props.auth.roles?.includes(\'Super Admin\')"',
    $content
);

file_put_contents($file, $content);
?>
