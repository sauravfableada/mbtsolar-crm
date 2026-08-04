<?php
$path = 'resources/views/pdfbuilder/pdf.blade.php';
$content = file_get_contents($path);

// Replace any occurrence of the logo image with max-height constraint
$content = preg_replace(
    '/(<img[^>]*src="<\?= \\$logoBase64 \?>"[\s]*alt="Company Logo"[\s]*style="max-width[\s]*:[\s]*160px;)\s*height\s*:\s*auto;?([^"]*")/',
    '$1 max-height: 55px; height: auto; object-fit: contain;$2',
    $content
);

$content = preg_replace(
    '/(<img[^>]*src="<\?= \\$logoBase64 \?>"[\s]*style="max-width[\s]*:[\s]*160px;)\s*height\s*:\s*auto;?([^"]*")/',
    '$1 max-height: 55px; height: auto; object-fit: contain;$2',
    $content
);

$content = preg_replace(
    '/(<img[^>]*src="<\?= \\$logoBase64 \?>"[\s]*style="max-width[\s]*:[\s]*160px;)\s*margin-bottom:5px;([^"]*")/',
    '$1 max-height: 55px; height: auto; object-fit: contain; margin-bottom:5px;$2',
    $content
);

$content = preg_replace(
    '/(<img[^>]*src="<\?= \\$logoBase64 \?>"[\s]*style="max-width:200px;)\s*height:auto;\s*object-fit:contain;([^"]*")/',
    '$1 height:auto; max-height: 80px; object-fit:contain;$2',
    $content
);

file_put_contents($path, $content);
echo "Done";
