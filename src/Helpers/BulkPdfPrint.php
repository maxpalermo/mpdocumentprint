<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpDocumentPrint\Helpers;

use setasign\Fpdi\Fpdi;

class BulkPdfPrint
{
    public function run($streams, $pageFormat = ['width' => 210, 'height' => 297], $orientation = 'P')
    {
        // Specifica unità 'mm' direttamente
        $pdf = new Fpdi($orientation, 'mm', $pageFormat);
        foreach ($streams as $stream) {
            // Controlla se $stream è in formato base64
            if (!$pdf = base64_decode($stream, true)) {
                $pdf = $stream;
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'pdf');
            file_put_contents($tmpFile, $pdf);

            $pageCount = $pdf->setSourceFile($tmpFile);
            for ($pageNo = 1; $pageNo <= $pageCount; ++$pageNo) {
                $tplIdx = $pdf->importPage($pageNo);
                $pdf->AddPage($orientation, [$pageFormat['width'], $pageFormat['height']]);
                $pdf->useTemplate($tplIdx);
            }

            unlink($tmpFile);
        }

        return $pdf->Output('S');
    }
}
