<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$buttons_inject = <<<'EOT'
                        <div class="flex gap-2">
                            <button type="button" @click="addCustomMaterial" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition-colors">
                                <Plus class="w-3.5 h-3.5 mr-1" />
                                Add Custom Cost
                            </button>
                            <button type="button" @click="addMaterial" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                                <Plus class="w-3.5 h-3.5 mr-1" />
                                Add Product
                            </button>
                        </div>
EOT;

$content = preg_replace('/<button type="button" @click="addMaterial"[^>]*>.*?<\/button>/s', $buttons_inject, $content);

$method_inject = <<<'EOT'
const addMaterial = () => {
    form.items.push({
        product_id: '',
        description: '',
        quantity: 1,
        unit_cost: 0,
        total_cost: 0,
        item_type: 'material'
    });
};

const addCustomMaterial = () => {
    form.items.push({
        product_id: null,
        description: 'New Custom Cost',
        quantity: 1,
        unit_cost: 0,
        total_cost: 0,
        item_type: 'material'
    });
};
EOT;

$content = preg_replace('/const addMaterial = \(\) => {.*?};/s', $method_inject, $content);
file_put_contents($file, $content);
echo "Patched Add Material buttons.\n";
?>
