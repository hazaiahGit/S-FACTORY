<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

// Add InputError import if not exists
if (strpos($content, 'InputError') === false) {
    $content = str_replace(
        "import { ref, computed } from 'vue';",
        "import { ref, computed } from 'vue';\nimport InputError from '@/Components/InputError.vue';",
        $content
    );
}

// Add error display above form
$error_display = <<<'EOT'
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Validation Errors -->
                <div v-if="Object.keys(form.errors).length > 0" class="mb-4 bg-rose-50 p-4 rounded-lg border border-rose-200">
                    <h3 class="text-sm font-bold text-rose-800 mb-2">Please fix the following errors:</h3>
                    <ul class="list-disc pl-5 text-sm text-rose-600">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>
                
                <!-- details -->
EOT;

$content = str_replace(
    "<form @submit.prevent=\"submit\" class=\"space-y-6\">\n                <!-- details -->",
    $error_display,
    $content
);

file_put_contents($file, $content);
?>
