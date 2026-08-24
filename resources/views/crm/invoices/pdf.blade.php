<?php
if (!function_exists('normalize_pdf_image')) {
    function normalize_pdf_image($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        // Helper closure to optimize and convert progressive images to Baseline using GD
        $optimizeImage = function($candidate) {
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (empty($ext)) $ext = 'png';
            elseif ($ext === 'jpg') $ext = 'jpeg';
            
            if (extension_loaded('gd') && ($ext === 'jpg' || $ext === 'jpeg')) {
                try {
                    // Detect Progressive JPEG format using header inspection
                    $handle = @fopen($candidate, 'rb');
                    $isProgressive = false;
                    if ($handle) {
                        $header = @fread($handle, 131072); // Read initial segment to check for SOF2 markers
                        @fclose($handle);
                        if (strpos($header, "\xFF\xC2") !== false) {
                            $isProgressive = true;
                        }
                    }

                    // Run GD ONLY for broken Progressive JPEGs. Baseline JPEGs are left untouched!
                    if ($isProgressive) {
                        $srcImg = @imagecreatefromjpeg($candidate);
                        if ($srcImg) {
                            $width = imagesx($srcImg);
                            $height = imagesy($srcImg);
                            
                            // Keep 100% of original dimensions to prevent tampering with document layout sizes!
                            $newW = $width;
                            $newH = $height;
                            
                            $dstImg = imagecreatetruecolor($newW, $newH);
                            $white = imagecolorallocate($dstImg, 255, 255, 255);
                            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $white);
                            
                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);
                            
                            ob_start();
                            imageinterlace($dstImg, 0); // Enforce non-progressive baseline format
                            imagejpeg($dstImg, null, 90); // High quality 90 to preserve crispness
                            $binData = ob_get_clean();
                            
                            imagedestroy($srcImg);
                            imagedestroy($dstImg);
                            
                            if ($binData !== false && strlen($binData) > 0) {
                                return 'data:image/jpeg;base64,' . base64_encode($binData);
                            }
                        }
                    }
                } catch (\Throwable $t) {}
            }
            
            // Fast pathway for all other formats (Baseline JPEG & PNG) to preserve exact original bytes
            $imgData = @file_get_contents($candidate);
            if ($imgData !== false) {
                $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
            return null;
        };

        // If it's already a base64 data URI, return as-is
        if (strpos($path, 'data:image') === 0) {
            return $path;
        }

        // Parse URL paths and filenames
        $cleanPath = $path;
        if (preg_match('/^https?:\/\//i', $path)) {
            $urlParts = parse_url($path);
            $cleanPath = isset($urlParts['path']) ? ltrim($urlParts['path'], '/\\') : $path;
        }

        // Clean prefix components (public, storage, public_html, etc)
        $cleanPath = preg_replace('#^(?:public|public_html|storage|app/public|storage/app/public)(?:/|\\\\)+#i', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/\\');

        // Generate robust list of potential disk locations
        $candidates = [
            // 0. Raw path itself (may already be an absolute filesystem path)
            $path,
            
            // 1. Standard Storage paths
            storage_path('app/public/' . $cleanPath),
            storage_path('app/' . $cleanPath),
            
            // 2. Standard Public and Public/Storage paths
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
            
            // 3. Raw filesystem path mappings in standard web-serving folders
            base_path('public_html/storage/' . $cleanPath),
            base_path('public_html/' . $cleanPath),
            base_path('public/storage/' . $cleanPath),
            
            // 4. Deeply nested common project assets & uploads
            public_path('uploads/' . $cleanPath),
            public_path('uploads/products/' . $cleanPath),
            public_path('uploads/img/product/' . $cleanPath),
            public_path('assets/' . $cleanPath),
            public_path('assets/img/profile/' . $cleanPath),
        ];

        // Always try matching by filename for bom-products & products folders
        $filename = basename($cleanPath);
        if ($filename !== '') {
            $candidates[] = storage_path('app/public/bom-products/' . $filename);
            $candidates[] = storage_path('app/public/products/' . $filename);
            $candidates[] = public_path('storage/bom-products/' . $filename);
            $candidates[] = public_path('storage/products/' . $filename);
            $candidates[] = base_path('public_html/storage/bom-products/' . $filename);
            $candidates[] = base_path('public_html/storage/products/' . $filename);
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate && @file_exists($candidate) && @is_file($candidate)) {
                $result = $optimizeImage($candidate);
                if ($result) return $result;
                // Fallback if optimize failed but file is accessible
                $imgData = @file_get_contents($candidate);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                    $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                    return 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }
        }

        // HTTP fallback: try to fetch the image via URL and convert to base64
        // (Dompdf often cannot fetch URLs from the same server due to loopback issues)
        $urlToTry = (preg_match('/^https?:\/\//i', $path)) ? $path : asset('storage/' . $cleanPath);
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $imgData = @file_get_contents($urlToTry, false, $ctx);
            if ($imgData !== false && strlen($imgData) > 0) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($imgData) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($imgData);
            }
        } catch (\Throwable $e) {}

        // Last resort: return the URL directly (may not render in Dompdf)
        return $urlToTry;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        if (empty($path)) {
            return rtrim(url('/'), '/') . '/';
        }
        return normalize_pdf_image($path);
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        .quotation-box,
        .quotation-box * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.4;
        }

        .quotation-box {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
        }

        .quotation-header table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .quotation-header td {
            vertical-align: top;
            padding: 5px 0;
        }

        .company-logo img {
            max-width: 300px;
            width: 50%;
            height: auto;
            display: block;
        }

        .quotation-title {
            font-size: 16px;
            text-align: right;
            color: #686868;
        }

        .info-table,
        .quotation-table,
        .extra-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-table th,
        .info-table td,
        .quotation-table th,
        .quotation-table td,
        .extra-info th,
        .extra-info td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        .info-table th,
        .quotation-table th,
        .extra-info th {
            background-color: #3d7a3b;
            color: #fff;
        }

        .quotation-table tfoot td {
            font-weight: bold;
            text-align: right;
            padding: 8px 12px;
        }

        .quotation-table tbody td {
            padding: 10px 12px;
        }

        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-direction: row;
        }

        .center-text {
            text-align: center;
            font-weight: 700;
            text-decoration: underline;
            flex: 1;
        }

        .page-break {
            page-break-before: always;
            margin-top: 40px;
        }

        .bom-section h2 {
            text-align: center;
            color: #19547B;
            margin-bottom: 30px;
            text-decoration: underline;
            font-size: 20px;
        }

        .highlight-bg {
            background-color: #3d7a3b;
            color: #fff;
        }

        .note-text {
            font-size: 15px;
            margin-top: 2px;
            color: #555;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }

        .qr-code-img {
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="quotation-box">
        <!-- Header -->
        <div class="quotation-header">
            <table>
                <tr>
                    <td class="company-logo" style="width: 50%;">
                        @if (isset($settings['company_logo_path']))
                            <img src="{{ base_url('storage/' . $settings['company_logo_path']) }}" alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        @elseif ($user && $user->company_logo)
                            <img src="{{ base_url('storage/' . $user->company_logo) }}" alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        @else
                            <img src="{{ base_url('assets/img/logo.jpg') }}" alt="Company Logo" style="max-width: 300px; width: 50%; height: auto;">
                        @endif
                    </td>
                    <td class="quotation-title" style="width: 50%;">
                        <div style="line-height:22px;color:#000; text-align: right;">
                            <strong
                                style="font-size:18px;color:#000">{{ $settings['company_name'] ?? ($user->company_name ?? 'MBT SOLAR') }}</strong><br>
                            <div style="max-width: 250px; display: inline-block; text-align: right; white-space: normal;">
                                {{ $settings['company_address'] ?? ($user->address ?? '--') }}
                            </div><br>
                            {{ $settings['phone'] ?? ($user->contact ?? '--') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <hr>

        <!-- Invoice Info -->
        <div class="flex-between">
            <div style="font-weight:700; font-size:15px;">Invoice no.: #{{ $invoice->invoice_no }}</div>
            <div class="center-text" style="font-size:16px;">INVOICE</div>
            <div style="font-weight:700; font-size:15px;">Date: {{ optional($invoice->invoice_date)->format('Y-m-d') }}
            </div>
        </div>

        <!-- Customer Info Table -->
        <table class="info-table">
            <thead>
                <tr>
                    <th colspan="4">Customer Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Customer Name</strong></td>
                    <td>{{ $invoice->customer->name ?? '--' }}</td>
                    <td><strong>Email</strong></td>
                    <td>{{ $invoice->customer->email ?? '--' }}</td>
                </tr>
                <tr>
                    <td><strong>Address</strong></td>
                    <td>{{ $invoice->customer->address ?? '--' }}</td>
                    <td><strong>Contact</strong></td>
                    <td>{{ $invoice->customer->contact ?? '--' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Invoice Details Table -->
        <table class="quotation-table" style="border: 1px solid #333; border-collapse: collapse; width: 100%; font-family: sans-serif; margin-bottom: 20px;">
            <thead style="background-color: #3d7a3b; color: #fff;">
                <tr>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #3d7a3b !important; color: #ffffff !important;">Invoice Name</th>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #3d7a3b !important; color: #ffffff !important;">Quantity (kW)</th>
                    <th style="padding: 10px 8px; font-weight: bold; font-size: 13px; border: 1px solid #333; text-align: left; background-color: #3d7a3b !important; color: #ffffff !important;">Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->invoice_name ?? '--' }}</td>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ $invoice->quantity ?? '0' }}</td>
                    <td style="padding: 10px 8px; border: 1px solid #333; color: #333; font-family: sans-serif;">{{ number_format((float) ($invoice->price ?? 0), 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                @php
                    $subtotal = (float) ($invoice->price ?? 0);
                    $gstRate = (float) ($invoice->gst ?? 0);
                    $discount = (float) ($invoice->discount ?? 0);
                    $subsidy = (float) ($invoice->subsidy_amount ?? 0);
                    $solarStructureCharges = (float) ($invoice->solar_structure_charges ?? 0);
                    $gstBreakdown = is_array($invoice->gst_breakdown ?? null)
                        ? $invoice->gst_breakdown
                        : (json_decode((string) ($invoice->gst_breakdown ?? ''), true) ?: []);
                    $usesGlobalTax = collect($gstBreakdown['groups'] ?? [])->contains(fn ($group) => ($group['tax_type'] ?? '') === 'global_tax');
                    $gstAmount = isset($invoice->gst_amount)
                        ? (float) $invoice->gst_amount
                        : $subtotal * ($gstRate / 100);
                    $totalPayable = $subtotal + $solarStructureCharges + $gstAmount - $discount;
                    $lendingCost = $totalPayable - $subsidy;
                @endphp
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Base Price</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if($solarStructureCharges > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Solar Structure Charges</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($solarStructureCharges, 2) }}</td>
                </tr>
                @endif
                @if($gstRate > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ $usesGlobalTax ? 'Global Tax' : 'BOM Tax' }} ({{ $gstRate }}%)</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">{{ number_format($gstAmount, 2) }}</td>
                </tr>
                @endif
                @if($discount > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Discount</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">-{{ number_format($discount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: bold; padding: 8px 12px; color: #333; font-family: sans-serif;">Customer Payable Amount</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; background-color: #3d7a3b !important; color: #ffffff !important; font-weight: bold; font-family: sans-serif;">{{ number_format($totalPayable, 2) }}</td>
                </tr>
                @if($subsidy > 0)
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: normal; padding: 8px 12px; color: #333; font-family: sans-serif;">Subsidy</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; color: #333; font-family: sans-serif;">-{{ number_format($subsidy, 2) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; background-color: #fff;"></td>
                    <td style="text-align: right; border: 1px solid #333; font-weight: bold; padding: 8px 12px; color: #333; font-family: sans-serif;">Lending Cost Of Customer</td>
                    <td style="text-align: right; border: 1px solid #333; padding: 8px 12px; background-color: #3d7a3b !important; color: #ffffff !important; font-weight: bold; font-family: sans-serif;">{{ number_format($lendingCost, 2) }}</td>
                </tr>
                @endif
            </tfoot>
        </table>

        @if($subsidy > 0)
            <p class="note-text"><strong>Note:</strong> Subsidy Amount to be credited in clients account.</p>
        @endif

        <!-- Extra Info -->
        <div class="extra-info">
            <table>
                <tr>
                    <th style="width: 40%;">System Capacity</th>
                    <td>{{ $invoice->quantity ?? '0' }} kW</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ ucfirst($invoice->type ?? '') }}</td>
                </tr>
                @if(!empty($invoice->solar_meter_charges))
                    <tr>
                        <th>Solar Meter Charges</th>
                        <td>{{ ucwords(str_replace('_', ' ', $invoice->solar_meter_charges)) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @php
            $invoiceHasBankDetails = !empty($settings['bank_name']) || !empty($settings['account_name']) || !empty($settings['account_number']) || !empty($settings['ifsc_code']) || !empty($settings['branch_name']);
            $qrCodePath = $settings['company_qr_code_path'] ?? \App\Models\Setting::where('key', 'company_qr_code_path')->value('value');
            $invoiceHasQrCode = !empty($qrCodePath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($qrCodePath);
        @endphp

        <!-- Stacked Comment / Bank Details / QR Code Table -->
        <table class="info-table" style="margin-top:20px;border:1px solid #eee;">
            <tbody>
                <tr><th style="background-color:#f8f9fa;color:#333;border:1px solid #ddd;">Comment</th></tr>
                <tr><td style="vertical-align:top;padding:15px;background:#fff;border:1px solid #eee;"><div style="font-size:13px;color:#555;">{!! nl2br(e($invoice->comment ?? '--')) !!}</div></td></tr>

                @if($invoiceHasBankDetails)
                    <tr><th style="background-color:#f8f9fa;color:#333;border:1px solid #ddd;">Bank Details</th></tr>
                    <tr>
                        <td style="vertical-align:top;padding:15px;background:#fff;border:1px solid #eee;">
                            <div style="font-size:13px;line-height:1.6;">
                                <strong>Bank:</strong> {{ $settings['bank_name'] ?? '--' }}<br>
                                <strong>A/c Name:</strong> {{ $settings['account_name'] ?? '--' }}<br>
                                <strong>A/c No.:</strong> {{ $settings['account_number'] ?? '--' }}<br>
                                <strong>IFSC:</strong> {{ $settings['ifsc_code'] ?? '--' }}<br>
                                <strong>Branch:</strong> {{ $settings['branch_name'] ?? '--' }}
                            </div>
                        </td>
                    </tr>
                @endif

                @if($invoiceHasQrCode)
                    <tr><th style="background-color:#f8f9fa;color:#333;border:1px solid #ddd;">QR Code</th></tr>
                    <tr><td style="vertical-align:top;padding:15px;background:#fff;border:1px solid #eee;text-align:center;"><img src="{{ base_url('storage/' . $qrCodePath) }}" alt="QR Code" style="max-width:100px;height:auto;"></td></tr>
                @endif
            </tbody>
        </table>

    </div>
</body>

</html>
