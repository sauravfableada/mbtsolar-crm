<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$passedEstimate = \App\Models\Estimate::orderBy('estimate_id', 'desc')->first();
$html = view('pdfbuilder.qt-000150-pdf', ['estimate' => $passedEstimate, 'doc' => $passedEstimate, 'companySettings' => [], 'quotation_html' => ''])->render();
file_put_contents('test_pdf.html', $html);
echo "Done\n";
