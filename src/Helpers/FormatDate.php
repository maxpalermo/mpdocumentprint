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

class FormatDate
{
    private $date;
    private $locale;
    private $locale_iso_code;
    private $localFormats = [
        'en' => 'Y-m-d H:i:s',
        'it' => 'd/m/Y H:i:s',
    ];

    public function __construct($date)
    {
        $this->date = $date;
        $this->locale = \Tools::getContextLocale(\Context::getContext());
        $this->locale_iso_code = \Context::getContext()->language->iso_code;
    }

    public function toLocalDate($iso_code = '')
    {
        if (!$iso_code) {
            $iso_code = \Tools::strtolower($this->locale_iso_code);
        }
        // Formatta la data da ISO a formato locale
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->date);

        return $date->format($this->localFormats[$iso_code]);
    }
}
