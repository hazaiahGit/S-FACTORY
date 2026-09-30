<?php
$file = "resources/js/Layouts/AppLayout.vue";
$content = file_get_contents($file);
$content = str_replace(
    "    AlertTriangle\n} from '@lucide/vue';",
    "    AlertTriangle,\n    Building2,\n    ServerCrash\n} from '@lucide/vue';",
    $content
);
file_put_contents($file, $content);
echo "Done.\n";
echo strpos($content, "Building2") !== false ? "Building2 found in file.\n" : "Building2 NOT found in file.\n";
?>
