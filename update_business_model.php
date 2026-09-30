<?php
$file = "app/Models/Business.php";
$content = file_get_contents($file);

$content = str_replace(
    "'is_active',",
    "'is_active',\n        'subscription_package_id', 'subscription_ends_at', 'subscription_status',",
    $content
);
$content = str_replace(
    "'settings' => 'array',",
    "'settings' => 'array',\n        'subscription_ends_at' => 'date',",
    $content
);

$rel = <<<'EOT'
    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function mainBranch()
EOT;
$content = str_replace("    public function mainBranch()", $rel, $content);
file_put_contents($file, $content);
echo "Updated Business model.\n";
?>
