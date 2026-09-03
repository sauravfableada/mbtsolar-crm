<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = \App\Models\Estimate::find(25);
echo "Estimate 25 template_id: " . ($e->template_id ?? 'NULL') . "\n";
echo "Estimate 25 type: " . ($e->type ?? 'NULL') . "\n";

$template = \App\Models\PdfBuilderForm::find($e->template_id);
if ($template) {
    echo "Template name: " . $template->template_name . "\n";
} else {
    echo "No template found\n";
    $first = \App\Models\PdfBuilderForm::first();
    echo "First template: " . ($first ? $first->template_name : 'none') . "\n";
}

// Check what pdf view would be used
$templateName = strtolower(trim((string)($template->template_name ?? '')));
echo "Template name lower: '$templateName'\n";
$pdfView = 'pdfbuilder.pdf';
if ($templateName === 'basic template') {
    $pdfView = 'pdfbuilder.basic-template-pdf';
} elseif (in_array($templateName, ['solar proposal', 'ux template']) || strtolower(trim((string)$e->type)) === 'ux template') {
    $pdfView = 'pdfbuilder.qt-000150-pdf';
}
echo "PDF view being used: $pdfView\n";
