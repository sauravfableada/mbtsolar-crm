<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = \App\Models\Estimate::find(25);
if (!$e) { echo "Estimate 25 not found\n"; exit; }

$gd = is_array($e->generation_data) ? $e->generation_data : json_decode((string)$e->generation_data, true);
echo "generation_data for estimate 25:\n";
echo json_encode($gd, JSON_PRETTY_PRINT) . "\n";

$customLogo = $gd['custom_logo'] ?? null;
if ($customLogo) {
    $diskPath = storage_path('app/public/' . $customLogo);
    echo "\nLogo path: $diskPath\n";
    echo "File exists: " . (file_exists($diskPath) ? "YES" : "NO") . "\n";
} else {
    echo "\nNo custom_logo in generation_data\n";
}
