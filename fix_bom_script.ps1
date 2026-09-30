$file = "resources/js/Pages/Manufacturing/BOM/Create.vue"
$content = Get-Content $file -Raw

$newMethod = @"
const addCustomMaterial = () => {
    if (!customItemName.value) {
        alert("Please provide a name for the material.");
        return;
    }
    
    form.items.push({
        item_type: 'material',
        product_id: null,
        name: customItemName.value,
        description: customItemDesc.value,
        quantity: 1,
        unit_cost: Number(customItemCost.value || 0),
        total_cost: Number(customItemCost.value || 0),
    });
    
    customItemName.value = '';
    customItemDesc.value = '';
    customItemCost.value = 0;
};
"@

$content = $content -replace "const addMaterial = \(\) => \{[\s\S]*?customItemCost.value = 0;`r?`n\};", $newMethod

Set-Content -Path $file -Value $content
