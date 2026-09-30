<?php
$file = "resources/js/app.js";
$content = file_get_contents($file);

$inject = <<<'EOT'
import FormattedNumberInput from '@/Components/FormattedNumberInput.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
EOT;

$content = str_replace("const appName = import.meta.env.VITE_APP_NAME || 'Laravel';", $inject, $content);

$inject2 = <<<'EOT'
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('FormattedNumberInput', FormattedNumberInput);
EOT;

$content = str_replace(
    "    setup({ el, App, props, plugin }) {\n        const app = createApp({ render: () => h(App, props) })\n            .use(plugin)\n            .use(ZiggyVue);",
    $inject2,
    $content
);

file_put_contents($file, $content);
echo "Patched app.js to register FormattedNumberInput globally.\n";
?>
