<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$fallbackPath = 'public/assets/img/footer.png';
ob_start();
include 'resources/views/pdfbuilder/pdf.blade.php';
ob_end_clean();
$result = resolve_pdf_image_with_fallback('', $fallbackPath);
echo $result ? "Works! length=" . strlen($result) : "Fails! result=" . json_encode($result);
