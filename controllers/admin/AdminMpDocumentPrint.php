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

use MpSoft\MpDocumentPrint\Helpers\FetchHandler;
use MpSoft\MpDocumentPrint\Pdf\PdfOrder;
use MpSoft\MpDocumentPrint\Pdf\PdfOrders;

class AdminMpDocumentPrintController extends ModuleAdminController
{
    private $fetchHandler;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->fetchHandler = new FetchHandler($this->module, $this);
        $this->fetchHandler->run();
    }

    public function ajaxProcessDocumentPrintOrderNote($data)
    {
        $orderId = $data['order_id'];
        $pdfOrder = new PdfOrder($orderId);
        $document = $pdfOrder->create()->getDocument();
        $stream = $pdfOrder->render()->getStream();

        return [
            'order_id' => $orderId,
            'document' => $document,
            'stream' => base64_encode($stream),
        ];
    }

    public function ajaxProcessDocumentPrintOrderNoteBulk($data)
    {
        $orderIds = $data['order_ids'];
        $stream = PdfOrders::render($orderIds);

        return [
            'order_ids' => $orderIds,
            'stream' => base64_encode($stream),
        ];
    }
}
