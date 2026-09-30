<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$content = str_replace(
    "import { ref } from 'vue';",
    "import { ref, computed } from 'vue';",
    $content
);

file_put_contents($file, $content);
?>
