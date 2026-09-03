<?php
$file = 'app/Http/Controllers/Api/InvoiceController.php';
$content = file_get_contents($file);

// 1. Add $solarMeterChargesAmount retrieval in store()
$content = preg_replace(
    '/\$solarMeterCharges = \$request->input\(\'solar_meter_charges\'\);/',
    "\$solarMeterCharges = \$request->input('solar_meter_charges');\n            \$solarMeterChargesAmount = (float) (\$request->input('solar_meter_charges_amount') ?? 0);",
    $content
);

// 2. Add $solarMeterChargesAmount to subtotal calculation in store()
$content = preg_replace(
    '/\$subtotal = \$basePrice \+ \$solarStructureCharges;/',
    "\$subtotal = \$basePrice + \$solarStructureCharges + \$solarMeterChargesAmount;",
    $content
);

// 3. Add to Invoice::create array
$content = preg_replace(
    '/\'solar_meter_charges\' => \$solarMeterCharges,/',
    "'solar_meter_charges' => \$solarMeterCharges,\n                'solar_meter_charges_amount' => \$solarMeterChargesAmount,",
    $content
);

// 4. Update() method same changes
// But regex might catch both store and update if they are identical. Let's check.
file_put_contents($file, $content);
echo "Updated InvoiceController.php\n";
