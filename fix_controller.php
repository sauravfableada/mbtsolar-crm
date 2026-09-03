<?php
$file = 'app/Http/Controllers/Api/EstimateController.php';
$content = file_get_contents($file);

// Replace input casting
$content = preg_replace(
    '/\$solarMeterCharges = \(float\) \(\$request->input\(\'solar_meter_charges\'\) \?\? 0\);/',
    "\$solarMeterCharges = \$request->input('solar_meter_charges');\n            \$solarMeterChargesAmount = (float) (\$request->input('solar_meter_charges_amount') ?? 0);",
    $content
);

// Replace insert/update arrays
$content = preg_replace(
    '/\'solar_meter_charges\' => \$solarMeterCharges,/',
    "'solar_meter_charges' => \$solarMeterCharges,\n                'solar_meter_charges_amount' => \$solarMeterChargesAmount,",
    $content
);

// Replace quote to estimate conversion array
$content = preg_replace(
    '/\'solar_meter_charges\' => \$estimate->solar_meter_charges,/',
    "'solar_meter_charges' => \$estimate->solar_meter_charges,\n                    'solar_meter_charges_amount' => \$estimate->solar_meter_charges_amount,",
    $content
);

file_put_contents($file, $content);
echo "Updated EstimateController.\n";
