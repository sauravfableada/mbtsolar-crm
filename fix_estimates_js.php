<?php
$file = 'public/js/estimates.js';
$content = file_get_contents($file);

// Replace id references to solar_meter_charges with solar_meter_charges_amount
$content = str_replace(
    "document.getElementById('solar_meter_charges')?.value",
    "document.getElementById('solar_meter_charges_amount')?.value",
    $content
);

// In the formData.set: we need to ALSO set solar_meter_charges_amount!
// Let's replace the formData.set('solar_meter_charges', ...)
// Actually, it was:
// formData.set('solar_meter_charges', document.getElementById('solar_meter_charges_check')?.checked ? (document.getElementById('solar_meter_charges_amount')?.value || '0') : '0');
// We need to change that to set solar_meter_charges_amount AND NOT OVERWRITE solar_meter_charges (which is handled by the normal form data from the select).
// But wait, the standard form serialize handles the select. The formData.set overwrites it. Let's just remove the overwriting of solar_meter_charges, and instead set solar_meter_charges_amount.
$content = preg_replace(
    "/formData\.set\('solar_meter_charges', document\.getElementById\('solar_meter_charges_check'\)\?\.checked \? \(document\.getElementById\('solar_meter_charges_amount'\)\?\.value \|\| '0'\) : '0'\);/",
    "formData.set('solar_meter_charges_amount', document.getElementById('solar_meter_charges_check')?.checked ? (document.getElementById('solar_meter_charges_amount')?.value || '0') : '0');",
    $content
);

// We should also replace the formData.set in quick add:
$content = preg_replace(
    "/formData\.set\('solar_meter_charges', '0'\);/",
    "formData.set('solar_meter_charges_amount', '0');\n                formData.set('solar_meter_charges', 'No');",
    $content
);

// Update inputs array for event listeners
$content = str_replace(
    "const inputs = ['price', 'solar_structure_charges', 'solar_meter_charges', 'discount', 'subsidy_amount'];",
    "const inputs = ['price', 'solar_structure_charges', 'solar_meter_charges_amount', 'discount', 'subsidy_amount'];",
    $content
);

file_put_contents($file, $content);
echo "Updated estimates.js\n";
