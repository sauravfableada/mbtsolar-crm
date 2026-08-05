<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 0px;">
    <tr>
        <td width="50%" align="left" valign="middle">
            <div style="font-size: 18px; font-family: 'Montserrat', sans-serif;">
                <?= $quantity ?>kW Ongrid <?= $pdfTypeLabelMixed ?>
            </div>
        </td>
        <td width="50%" align="right" valign="middle">
            <?php if (!empty($logoBase64)): ?>
                <img src="<?= $logoBase64 ?>" alt="Company Logo"
                    style="max-width: 160px; max-height: 55px; height: auto; margin-bottom: 5px;">
            <?php endif; ?>
        </td>
    </tr>
</table>
