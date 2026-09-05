<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 0px;">
    <tr>
        <td width="50%" align="left" valign="middle">
            <?php if (!empty($_customLogoBase64ForPages)): ?>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="175" align="left" valign="middle" style="padding-right:15px;">
                                    <img src="<?= $_customLogoBase64ForPages ?>" alt="Custom Logo" style="max-width:160px; max-height:55px; height:auto; object-fit:contain;">
                                </td>
                                <td align="left" valign="middle" style="white-space:nowrap;">
                        <?php endif; ?>
                        <div style="font-size: 18px; font-family: 'Montserrat', sans-serif;">
                <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
            </div>
                        <?php if (!empty($_customLogoBase64ForPages)): ?>
                                </td>
                            </tr>
                        </table>
                        <?php endif; ?>
        </td>
        <td width="50%" align="right" valign="middle">
            <?php if (!empty($logoBase64)): ?>
                <img src="<?= $logoBase64 ?>" alt="Company Logo"
                    style="max-width: 160px; max-height: 55px; height: auto; margin-bottom: 5px;">
            <?php endif; ?>
        </td>
    </tr>
</table>
