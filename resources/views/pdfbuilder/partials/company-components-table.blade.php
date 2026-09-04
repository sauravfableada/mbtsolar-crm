<?php
    $genData = [];
    $_src = $estimate ?? $passedEstimate ?? $estdata ?? $invoice ?? null;
    if (isset($_src) && $_src) {
        $rawGenData = is_array($_src) ? ($_src['generation_data'] ?? '[]') : ($_src->generation_data ?? '[]');
        $genData = is_array($rawGenData) ? $rawGenData : json_decode((string)$rawGenData, true);
    }
    $genData = $genData ?: [];
    
    $customName = $genData['custom_name'] ?? null;
    $customLogoPath = $genData['custom_logo'] ?? null;
    $customLogoBase64 = $custom_logo_base64 ?? null;
    if (!$customLogoBase64 && $customLogoPath) {
        $diskPath = storage_path('app/public/' . $customLogoPath);
        if (file_exists($diskPath)) {
            $customLogoBase64 = 'data:image/' . pathinfo($diskPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($diskPath));
        }
    }

    // Attempt to grab admin/user phone and email if not already defined
    $adminPhone = $companyPhone ?? data_get($user, 'phone') ?? data_get($user, 'mobile') ?? data_get($profileUser, 'phone') ?? data_get($profileUser, 'mobile') ?? '';
    $adminEmail = $companyEmail ?? data_get($companySettings, 'company_email') ?? data_get($companySettings, 'email') ?? data_get($user, 'company_email') ?? data_get($user, 'email') ?? data_get($profileUser, 'email') ?? '';
    $adminName = $customName ?: ($globalCompanyName ?? 'MBT SOLAR');
    
?>
<div style="page-break-inside: avoid;">
    <div style="text-align: center; margin-bottom: 15px; margin-top: 10px;">
        <?php if ($adminName): ?>
        <h3 style="color: #2b4c8c; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; font-family:'Montserrat', sans-serif;">
            <?= esc($adminName) ?>
        </h3>
        <?php endif; ?>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; font-weight: bold; font-family:'Montserrat', sans-serif; margin-bottom: 5px;">
            <tr>
                <td align="left">MOBILE NO-<?= esc($adminPhone) ?></td>
                <td align="right">E-MAIL ID- <?= esc($adminEmail) ?></td>
            </tr>
        </table>
    </div>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin:10px 0 18px; border:1px solid #000; font-family:'Montserrat', sans-serif; page-break-inside: avoid;">
    <thead>
        <tr>
            <th width="10%" style="background-color:#ffff00; padding:12px 10px; font-weight:bold; font-size:14px; color:#ff0000; text-align:center; border: 1px solid #000;">
                NO.
            </th>
            <th width="90%" style="background-color:#ffff00; padding:12px 16px; font-weight:bold; font-size:14px; color:#ff0000; text-align:center; border: 1px solid #000;">
                DETAILS
            </th>
        </tr>
    </thead>
    <tbody>
    <?php $componentRowIndex = 0; ?>
    <?php foreach ($componentsTableRows ?? $componentsData as $componentKey => $component):
        $specs = $component['specifications'] ?? [];
        $make = trim((string) ($component['category'] ?? ''));
        $qty = trim((string) ($component['quantity'] ?? ''));

        $productImage = $component['image'] ?? $component['product_image'] ?? $component['photo'] ?? null;
        $productImagePath = null;
        if (!empty($productImage)) {
            $productImage = trim((string) $productImage);
            if ($productImage !== '') {
                $resolved = normalize_pdf_image($productImage);
                if ($resolved && strpos($resolved, 'data:image') === 0) {
                    $productImagePath = $resolved;
                }
            }
        }

        $techSpecs = [];
        if (is_array($specs)) {
            foreach ($specs as $row) {
                if (!is_array($row) || count($row) < 2) {
                    continue;
                }
                $k = trim((string) ($row[0] ?? ''));
                $v = trim((string) ($row[1] ?? ''));
                if ($k === '' || $v === '') {
                    continue;
                }
                if (strtolower($k) === 'make') {
                    if (empty($make)) {
                        $make = $v;
                    }
                } elseif (strtolower($k) === 'description') {
                    continue;
                } elseif (strtolower($k) === 'qty') {
                    continue;
                } elseif (strtolower($k) === 'warranty') {
                    continue;
                } else {
                    $techSpecs[] = $v;
                }
            }
        } else {
            $legacy = trim((string) $specs);
            if ($legacy !== '') {
                $techSpecs[] = $legacy;
            }
        }

        $techSpecsHtml = !empty($techSpecs) ? implode(' / ', $techSpecs) : '—';
        $makeWithSpecs = $make !== ''
            ? ($techSpecsHtml !== '—' ? $make . ' (' . $techSpecsHtml . ')' : $make)
            : ($techSpecsHtml !== '—' ? '(' . $techSpecsHtml . ')' : '—');
        $rowBg = '#ffffff';
        $componentRowIndex++;
    ?>
    <tr style="page-break-inside:avoid; background:<?= $rowBg ?>;">
        <td style="padding:12px 10px; font-size:14px; font-weight:bold; color:#ff0000; border:1px solid #000; text-align:center; vertical-align:middle;">
            <?= $componentRowIndex ?>
        </td>
        <td style="padding:12px 14px; font-size:14px; font-weight:bold; color:#ff0000; border:1px solid #000; text-align:center; vertical-align:middle;">
            <?= esc($component['name'] ?? '--') ?> <?= $makeWithSpecs !== '—' && $makeWithSpecs !== '' ? ' - ' . esc($makeWithSpecs) : '' ?>
        </td>
    </tr>
    <?php endforeach; ?>

    <?php
    $additionalChargesBreakdown = [];
    if (isset($estdata) && !empty($estdata->generation_data)) {
        $decodedAdditionalCharges = is_array($estdata->generation_data)
            ? $estdata->generation_data
            : json_decode((string) $estdata->generation_data, true);

        $rawAdditionalCharges = [];
        if (is_array($decodedAdditionalCharges) && !empty($decodedAdditionalCharges['additional_charges'])) {
            $rawAdditionalCharges = (array) $decodedAdditionalCharges['additional_charges'];
        }

        if (!empty($rawAdditionalCharges)) {
            $additionalChargesBreakdown = array_values(array_filter(array_map(function ($charge) {
                if (!is_array($charge)) {
                    return null;
                }
                $name = trim((string) ($charge['name'] ?? ''));
                $price = (float) ($charge['price'] ?? 0);
                if ($name === '' || $price <= 0) {
                    return null;
                }
                return ['name' => $name];
            }, $rawAdditionalCharges)));
        }
    }
    ?>

    <?php foreach ($additionalChargesBreakdown as $additionalCharge): 
        $rowBg = '#ffffff';
        $componentRowIndex++;
    ?>
    <tr style="page-break-inside:avoid; background:<?= $rowBg ?>;">
        <td style="padding:12px 10px; font-size:14px; font-weight:bold; color:#ff0000; border:1px solid #000; text-align:center; vertical-align:middle;">
            <?= $componentRowIndex ?>
        </td>
        <td style="padding:12px 14px; font-size:14px; font-weight:bold; color:#ff0000; border:1px solid #000; text-align:center; vertical-align:middle;">
            <?= esc($additionalCharge['name']) ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

    <div style="font-weight: bold; font-size: 14px; text-transform: uppercase; margin-top: 15px; font-family:'Montserrat', sans-serif;">
        <div style="background-color: #00ff00; padding: 4px 8px; display: inline-block; margin-bottom: 8px;">
            * MAINTENANCE 6 YEARS FOR GUARANTEE/WARRANTY FREE SERVICE WITHOUT WASHING FROM MBT SOLAR.
        </div>
        <br>
        <div style="background-color: #00ff00; padding: 4px 8px; display: inline-block; margin-bottom: 8px;">
            * PANEL PERFORMANCE GUARANTEE 30 YEARS.
        </div>
        <br>
        <div style="background-color: #00ff00; padding: 4px 8px; display: inline-block; margin-bottom: 8px;">
            * INVERTER GUARANTEE/WARRANTY 10 YEARS.
        </div>
    </div>
</div>
