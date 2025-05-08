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

namespace MpSoft\MpDocumentPrint\Pdf;

use MpSoft\MpDocumentPrint\Helpers\FormatDate;
use MpSoft\MpDocumentPrint\Helpers\IsNewCustomer;

class PdfOrder
{
    private $document;
    private $stream;
    private $orderId;
    private $context;
    private $id_lang;
    private $locale;
    private $currencyIsoCode;
    private $customizedDatas;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $this->context = \Context::getContext();
        $this->id_lang = $this->context->language->id;
        $this->locale = \Tools::getContextLocale($this->context);
        $this->currencyIsoCode = $this->context->currency->iso_code;
    }

    public function create()
    {
        $order = new \Order($this->orderId, $this->id_lang);
        if (!\Validate::isLoadedObject($order)) {
            return false;
        }

        $customer = new \Customer($order->id_customer);
        if (!\Validate::isLoadedObject($customer)) {
            $customer = false;
        }

        $deliveryAddress = new \Address($order->id_address_delivery);
        if (!\Validate::isLoadedObject($deliveryAddress)) {
            $deliveryAddress = false;
        }

        $invoiceAddress = new \Address($order->id_address_invoice);
        if (!\Validate::isLoadedObject($invoiceAddress)) {
            $invoiceAddress = false;
        }

        $deliveryCountry = new \Country($deliveryAddress->id_country);
        if (!\Validate::isLoadedObject($deliveryCountry)) {
            $deliveryCountry = false;
        }

        $invoiceCountry = new \Country($invoiceAddress->id_country);
        if (!\Validate::isLoadedObject($invoiceCountry)) {
            $invoiceCountry = false;
        }

        $deliveryState = new \State($deliveryAddress->id_state);
        if (!\Validate::isLoadedObject($deliveryState)) {
            $deliveryState = false;
        }

        $invoiceState = new \State($invoiceAddress->id_state);
        if (!\Validate::isLoadedObject($invoiceState)) {
            $invoiceState = false;
        }

        $this->document = [
            'shop_logo' => $this->getShopLogo(),
            'order' => $this->getOrderFields($order),
            'customer' => $customer ? $customer->getFields() : [],
            'delivery_address' => $deliveryAddress ? $deliveryAddress->getFields() : [],
            'invoice_address' => $invoiceAddress ? $invoiceAddress->getFields() : [],
            'delivery_country' => $deliveryCountry ? $deliveryCountry->getFields() : [],
            'invoice_country' => $invoiceCountry ? $invoiceCountry->getFields() : [],
            'delivery_state' => $deliveryState ? $deliveryState->getFields() : [],
            'invoice_state' => $invoiceState ? $invoiceState->getFields() : [],
            'products' => $this->getProducts($order),
            'customized_products' => $this->getProductCustomizations($order->id_cart),
            'current_state' => $this->getCurrentState($order),
            'is_new_customer' => IsNewCustomer::check($order->id_customer),
            'messages' => $this->getMessages($order),
        ];

        return $this;
    }

    public function render()
    {
        $document = $this->document;
        $id_order = (int) $document['order']['id_order'];

        // Usa una classe anonima per TCPDF con footer personalizzato
        $pdf = new class extends \TCPDF {
            public function Footer()
            {
                $this->SetY(-15);
                $this->SetFont('helvetica', '', 9);
                $this->SetTextColor(120, 120, 120);
                $footerText = '- '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().' -';
                $this->Cell(0, 8, $footerText, 0, 0, 'C');
            }

            public function getTextColor()
            {
                return $this->fgcolor;
            }
        };
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Massimiliano Palermo');
        $pdf->SetTitle('Ordine n.'.$id_order);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        // HEADER
        $this->writeHeader($pdf, $document);
        $pdf->Ln(2);

        // PRODUCTS
        $this->writeProducts($pdf, $document['products']);
        $pdf->Ln(2);

        // PERSONALIZZAZIONI
        $this->writeCustomizations($pdf, $document['customized_products']);
        $pdf->Ln(2);

        // MESSAGGI
        $this->writeMessages($pdf, $id_order);
        $pdf->Ln(2);

        // MESSAGES (se esistono)
        if (!empty($document['messages'])) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetFillColor(255, 255, 204);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(0, 8, 'Messaggi ordine', 0, 1, 'L', 1);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetFillColor(255, 255, 255);
            foreach ($document['messages'] as $msg) {
                $pdf->MultiCell(0, 6, $msg, 1, 'L', 1);
            }
            $pdf->Ln(2);
        }

        // Output finale in una stringa
        $this->stream = $pdf->Output("order_{$id_order}.pdf", 'S');

        return $this;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function getStream()
    {
        return $this->stream;
    }

    protected function getOrderFields($order)
    {
        $fields = $order->getFields();
        $fields['total_order_currency'] = $this->locale->formatPrice($fields['total_paid_tax_incl'], $this->currencyIsoCode);

        return $fields;
    }

    protected function isStockService($id_product, $id_product_attribute)
    {
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        // Controllo se è stock service
        $sql->select('is_stock_service')
            ->from('product_stock_service_check')
            ->where('id_product = '.(int) $id_product);
        $is_stock_service = (int) $db->getValue($sql);
        if (!$is_stock_service) {
            return [
                'is_stock_service' => false,
                'quantity' => 0,
            ];
        }

        $sql = new \DbQuery();
        $sql->select('quantity')
            ->from('product_stock_service')
            ->where('id_product = '.(int) $id_product)
            ->where('id_product_attribute = '.(int) $id_product_attribute);
        $result = $db->getRow($sql);
        if ($result) {
            return [
                'is_stock_service' => true,
                'quantity' => $result['quantity'],
            ];
        }

        return [
            'is_stock_service' => true,
            'quantity' => 0,
        ];
    }

    protected function getCheckDate($id_product)
    {
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql->select('date_upd')
            ->from('product_stock_service_check')
            ->where('id_product = '.(int) $id_product);
        $result = $db->getRow($sql);
        if ($result) {
            return $result['date_upd'];
        }

        return null;
    }

    protected function getShopLogo()
    {
        $logo = \Configuration::get('PS_LOGO');
        if (!$logo) {
            return '';
        }

        return '/img/'.$logo;
    }

    protected function getProducts($order)
    {
        $this->customizedDatas = [];
        $products = $order->getProducts();
        foreach ($products as $key => &$product_item) {
            $product = new \Product($product_item['product_id'], false, $this->id_lang);
            $product_item['product_name_full'] = $product_item['product_name'];
            $product_item['product_name'] = $product->name;
            $product_item['combination'] = $this->getCombination($product, $product_item['product_attribute_id']);
            $product_item['image_url'] = $this->getImage($product_item['product_id']);
            $product_item['price_currency'] = $this->locale->formatPrice($product_item['unit_price_tax_incl'], $this->currencyIsoCode);
            $product_item['stock_service'] = $this->isStockService($product_item['product_id'], $product_item['product_attribute_id']);
            $product_item['check_date'] = $this->getCheckDate($product_item['product_id']);

            if (0 != $product_item['customizable']) {
                $this->customizedDatas[] = $product_item;
                unset($products[$key]);
                continue;
            }
        }

        return $products;
    }

    /**
     * Ottiene tutti i prodotti personalizzati di un ordine.
     *
     * @return array Array strutturato con i prodotti personalizzati
     */
    protected function getProductCustomizations($id_cart)
    {
        $customized_products = $this->customizedDatas;

        foreach ($customized_products as &$product) {
            $id_product = $product['product_id'];
            $id_product_attribute = $product['product_attribute_id'];
            // $customizedData = \Product::getAllCustomizedDatas($id_cart, $this->id_lang);
            $customizedData = $product['customizedDatas'] ?? [];
            if (!$customizedData) {
                continue;
            }

            foreach ($customizedData as $customization_item) {
                $customization_data = reset($customization_item);
                $datas = $customization_data['datas'];
                $productCustomizationData = [];

                foreach ($datas as $data) {
                    $data = reset($data);
                    $hasFile = 0 == $data['type'];
                    $hasText = 1 == $data['type'];
                    $label = $data['name'];
                    $value = $data['value'];
                    $productCustomizationData[] = [
                        'label' => $label,
                        'value' => $value,
                        'hasFile' => $hasFile,
                        'hasText' => $hasText,
                    ];
                }

                $product['customizations'][] = [
                    'id_product' => $id_product,
                    'id_product_attribute' => $id_product_attribute,
                    'quantity' => $customization_data['quantity'],
                    'data' => $productCustomizationData,
                ];
            }
        }

        return $customized_products;
    }

    protected function getMessages(\Order $order)
    {
        return [];
    }

    protected function getImage($idProduct)
    {
        /** @var array $cover */
        $cover = \Image::getCover($idProduct);
        if (!isset($cover['id_image']) || !$cover['id_image']) {
            return $this->context->shop->getBaseURL().'img/404.gif';
        }

        /** @var \Image $image */
        $image = new \Image($cover['id_image']);
        if (!\Validate::isLoadedObject($image)) {
            return $this->context->shop->getBaseURL().'img/404.gif';
        }
        /** @var string $imageType */
        $imageType = ".{$image->image_format}";
        /** @var string $imagePath */
        $imageFolders = \Image::getImgFolderStatic((int) $cover['id_image']);
        /** @var string $imagePath */
        $imagePath = "/img/p/{$imageFolders}{$cover['id_image']}-medium{$imageType}";
        /** @var string $root */
        $root = $this->context->shop->getBaseURL();

        return $imagePath;
    }

    protected function getCombination($product, $idProductAttribute)
    {
        $combination = $product->getAttributesGroups($this->id_lang, $idProductAttribute);

        if ($combination) {
            $out = [];
            foreach ($combination as $group) {
                $out[] = $group['attribute_name'];
            }

            return implode(' - ', $out);
        }

        return '';
    }

    protected function getCurrentState($order)
    {
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql->select('id_order_state')
            ->select('date_add')
            ->from('order_history')
            ->where('id_order = '.(int) $order->id)
            ->orderBy('id_order_state DESC');
        $lastOrderHistory = $db->getRow($sql);
        if (!$lastOrderHistory) {
            return '!ERRORE!';
        }
        $orderState = new \OrderState($lastOrderHistory['id_order_state'], $this->id_lang);
        if (!\Validate::isLoadedObject($orderState)) {
            return '!ERRORE!';
        }
        $orderStateDateIta = (new FormatDate($lastOrderHistory['date_add']))->toLocalDate();

        return [
            'date' => $lastOrderHistory['date_add'],
            'date_ita' => $orderStateDateIta,
            'name' => $orderState->name,
        ];
    }

    protected function writeHeader(\TCPDF $pdf, $data)
    {
        // --- PRIMA RIGA: LOGO + DATI ORDINE ---
        $cellH = 30;
        $logoW = 80;
        $logoH = 25;
        $rightW = 120;
        $startY = $pdf->GetY();

        // Logo
        if (!empty($data['shop_logo']) && @file_exists(_PS_ROOT_DIR_.$data['shop_logo'])) {
            $pdf->Image(_PS_ROOT_DIR_.$data['shop_logo'], $pdf->GetX() + 2, $startY + 2, $logoW - 8, $logoH, '', '', '', true, 300, '', false, false, 0, true, false, false);
        }
        $pdf->SetXY($pdf->GetX(), $startY);
        $pdf->Cell($logoW, $cellH, '', 0, 0, 'L', 0);

        // Dati ordine (tre righe)
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(40, 40, 40);

        $orderId = isset($data['order']['id_order']) ? $data['order']['id_order'] : '';
        $orderDate = isset($data['order']['date_add']) ? date('d/m/Y', strtotime($data['order']['date_add'])) : '';
        $stateName = isset($data['current_state']['name']) ? $data['current_state']['name'] : '';
        $stateDate = isset($data['current_state']['date']) ? date('d/m/Y H:i', strtotime($data['current_state']['date'])) : '';
        $printedDate = date('d/m/Y H:i:s');
        $payment = isset($data['order']['payment']) ? $data['order']['payment'] : '';

        $rightX = $pdf->GetX();
        $rightY = $pdf->GetY();
        $fontsize = 11;
        $pdf->SetXY($rightX, $rightY);

        // Riga 1: Ordine
        $pdf->SetFont('helvetica', 'B', $fontsize);
        $pdf->Cell($rightW, 5.5, "Ordine: $orderId del $orderDate", 0, 2, 'L', 0);
        // Riga 2: Stato
        $pdf->SetFont('helvetica', '', $fontsize);
        $pdf->Cell($rightW, 5, "Stato corrente: $stateName", 0, 2, 'L', 0);
        // Riga 3: Data di stampa
        $pdf->SetFont('helvetica', '', $fontsize);
        $pdf->Cell($rightW, 4, "Data di stampa: $printedDate", 0, 2, 'L', 0);
        // Riga 4: Pagamento
        $pdf->SetX($rightX);
        $pdf->SetFont('helvetica', '', $fontsize);
        $pdf->Cell($rightW, 5, "Tipo di Pagamento: $payment", 0, 2, 'L', 0);

        $pdf->Ln(2);

        // --- LINEA DIVISORIA ---
        $pdf->SetDrawColor(136, 136, 136);
        $pdf->SetLineWidth(0.4);
        $pdf->SetY($pdf->GetY() + 2);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(3);

        // --- SECONDA RIGA: INDIRIZZI E CLIENTE ---
        $boxH = 28;
        $boxFont = 9;
        $colW = [60, 60, 60];
        $colX = [0, 60, 125];
        $startY = $pdf->GetY();
        $x = $pdf->GetX();

        // Indirizzo di spedizione
        $pdf->SetXY($x + $colX[0], $startY);
        $pdf->SetFont('helvetica', 'B', $boxFont);
        $pdf->SetFillColor(247, 247, 247);
        $pdf->SetDrawColor(136, 136, 136);
        $pdf->Cell($colW[0], 6, 'Indirizzo di spedizione', 1, 2, 'L', 1);
        $pdf->SetFont('helvetica', '', $boxFont);
        $pdf->MultiCell($colW[0], 5, $this->formatAddress($data['delivery_address'], $data['delivery_state'], $data['delivery_country']), 1, 'L', 1, 0);

        // Indirizzo di fatturazione
        $pdf->SetXY($x + $colX[1], $startY);
        $pdf->SetFont('helvetica', 'B', $boxFont);
        $pdf->Cell($colW[1], 6, 'Indirizzo di fatturazione', 1, 2, 'L', 1);
        $pdf->SetFont('helvetica', '', $boxFont);
        $pdf->MultiCell($colW[1], 5, $this->formatAddress($data['invoice_address'], $data['invoice_state'], $data['invoice_country']), 1, 'L', 1, 0);

        // Dati ordine
        $pdf->SetXY($x + $colX[2], $startY);
        $pdf->SetFont('helvetica', 'B', $boxFont);
        $pdf->Cell($colW[2], 6, 'Dati ordine', 1, 2, 'L', 1);
        $pdf->SetFont('helvetica', '', $boxFont);

        $customerId = isset($data['customer']['id_customer']) ? $data['customer']['id_customer'] : '';
        $isOld = !empty($data['is_new_customer']) ? false : true;
        $orderDateFull = isset($data['order']['date_add']) ? date('d/m/Y H:i', strtotime($data['order']['date_add'])) : '';
        $total = isset($data['order']['total_order_currency']) ? $data['order']['total_order_currency'] : '';

        $pdf->Cell(30, 10, 'CODICE CLIENTE:', 0, 0, 'L', 0);
        if ($isOld) {
            $pdf->setTextColor(200, 30, 30);
            $pdf->setFont('', 'B', $boxFont);
            $pdf->Cell(5, 10, 'V', 0, 0, 'L');
        }

        $pdf->setFont('', 'B', $boxFont + 1);
        $pdf->setTextColor(40, 40, 40);
        $pdf->Cell(20, 10, "DL{$customerId}", 0, 10, 'L', 0);

        $pdf->setFont('', '', $boxFont);

        // Data ordine e totale ordine subito sotto Codice Cliente
        $pdf->SetFont('helvetica', '', $boxFont);
        $pdf->SetX($x + $colX[2]);
        $pdf->Cell($colW[2], 0, "Data Ordine: $orderDateFull", 0, 10, 'L');
        $pdf->SetX($x + $colX[2]);
        $pdf->Cell($colW[2], 0, "Totale ordine: $total", 0, 10, 'L');

        $pdf->Ln(10);
    }

    /**
     * Formatta l'indirizzo come stringa multilinea per TCPDF.
     */
    protected function formatAddress($address, $state, $country)
    {
        $out = '';
        $company = \Tools::strtoupper($address['company'] ?? '');
        $name = \Tools::strtoupper($address['firstname'].' '.$address['lastname']);
        if ($company == $name) {
            $out .= $company."\n";
        } else {
            $out .= $company."\n";
            $out .= $name."\n";
        }
        $out .= ($address['address1'] ?? '')."\n";
        $out .= ($address['address2'] ?? '')."\n";
        $out .= ($address['postcode'] ?? '').' ';
        $out .= ($address['city'] ?? '').' ';
        $out .= (isset($state['iso_code']) ? '('.$state['iso_code'].')' : '')."\n";
        $out .= (isset($country['iso_code']) ? $country['iso_code'] : '');

        return trim($out);
    }

    protected function writeProducts(\TCPDF &$pdf, $products)
    {
        $w = [
            'thumb' => 20,
            'reference' => 32,
            'name' => 78,
            'qty' => 30,
            'price' => 30,
            'location' => 110,
            'stock_Service' => 60,
        ];
        $h = 7;
        $fontSize = 9;

        $this->drawTableProductHeader($pdf, $w, $h, $fontSize);
        foreach ($products as $i => $product) {
            $this->drawTableProductRow($pdf, $product, $i);
        }
    }

    protected function writeCustomizations(\TCPDF $pdf, $customizedProducts)
    {
        // Definizione larghezze colonne (mm)
        // Larghezze colonna per coprire tutta la pagina (A4, margini 10mm -> 190mm)
        $w = $this->getWidths();
        $colors = $this->getColors();
        $h = $this->getHeight();
        $fontSize = $this->getFontSize();

        if (!$customizedProducts) {
            return;
        }

        $pdf->AddPage();
        $pdf->SetY(10);
        $pdf->SetX(10);
        $pdf->setFontSize(18);
        $pdf->Cell(190, 5, 'Personalizzazioni', 0, 1, 'C');
        $pdf->SetX(10);
        $pdf->setFontSize($fontSize);

        $this->drawTableProductHeader($pdf, $w, $h, $fontSize);

        foreach ($customizedProducts as $i => $product) {
            $this->drawTableProductRow($pdf, $product, $i, true);
        }
    }

    protected function writeMessages($pdf, $id_order)
    {
        $this->drawMessages($pdf, $id_order);
    }

    protected function drawTableProductHeader(&$pdf, $w, $h, $fontSize)
    {
        $pdf->SetFont('helvetica', 'B', $fontSize + 1);
        $pdf->SetFillColor(136, 136, 136);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($w['thumb'], $h, 'IMM', 1, 0, 'C', 1);
        $pdf->Cell($w['reference'], $h, 'Riferimento', 1, 0, 'C', 1);
        $pdf->Cell($w['name'], $h, 'Prodotto', 1, 0, 'C', 1);
        $pdf->Cell($w['qty'], $h, 'Quantità', 1, 0, 'C', 1);
        $pdf->Cell($w['price'], $h, 'Prezzo', 1, 1, 'C', 1);
        // Ripristina font e colori per la riga successiva (riga dati)
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFont('helvetica', '', $fontSize);
        $pdf->SetFillColor(255, 255, 255);
    }

    protected function drawTableProductRow(\TCPDF &$pdf, $product, $i, $drawCustomization = false)
    {
        $w = $this->getWidths();
        $colors = $this->getColors();
        $h = $this->getHeight();
        $fontSize = $this->getFontSize();
        $lineHeight = $this->getLineHeight();

        $fill = (0 == $i % 2) ? [0xFF, 0xFF, 0xFF] : [0xF6, 0xF6, 0xF6];
        $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);

        // Calcolo spazio necessario (3 righe)
        $blockHeight = $h * 3;
        if ($pdf->GetY() + $blockHeight > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
            $pdf->AddPage();
            $this->drawTableProductHeader($pdf, $w, $h, $fontSize);
        }

        // Thumbnail
        $imgDrawn = false;
        if (!empty($product['image_url'])) {
            $imgPath = (0 === strpos($product['image_url'], '/')) ? _PS_ROOT_DIR_.$product['image_url'] : $product['image_url'];
            if (@file_exists($imgPath)) {
                $pdf->Cell($w['thumb'], $blockHeight, '', 1, 0, 'C', 1);
                $x = $pdf->GetX() - $w['thumb'];
                $y = $pdf->GetY();
                $pdf->Image($imgPath, $x + 2, $y + 2, $w['thumb'] - 4, $blockHeight - 4, '', '', '', false, 300, '', false, false, 0, false, false, false);
                $imgDrawn = true;
            }
        }
        if (!$imgDrawn) {
            $pdf->Cell($w['thumb'], $blockHeight, '', 1, 0, 'C', 1);
        }

        // Riga 1: dati principali
        $reference = $product['reference'] ?? '';
        $name = $this->truncateString($product['product_name'] ?? '', 40);
        $qty = $product['product_quantity'] ?? '';
        $price = $product['price_currency'] ?? '';
        $pdf->Cell($w['reference'], $h, $reference, 'LTR', 0, 'L', 1);
        $pdf->Cell($w['name'], $h, $name, 'LTR', 0, 'L', 1);
        $pdf->setFontSize($fontSize + 5);
        $pdf->setTextColor(0xFF, 0xFF, 0xFF);
        $pdf->setFillColor(0x64, 0x64, 0x64);
        $pdf->Cell($w['qty'], $h, $qty, 'LTR', 0, 'C', 1);
        $pdf->setFontSize($fontSize + 2);
        $pdf->setFillColor(0xFF, 0xFF, 0xFF);
        $pdf->setTextColor(0x64, 0x64, 0x64);
        $pdf->Cell($w['price'], $h, $price, 'LTR', 1, 'R', 1);

        // Riga 2: combinazione, stock, sconto
        $pdf->Cell($w['thumb'], $h, '', 0, 0, '', 0);
        $pdf->SetFont('', '', $fontSize - 1);
        $pdf->SetTextColor(0x64, 0x64, 0x64);
        $pdf->Cell($w['reference'], $h, '', 'LR', 0, 'L', 1);

        // Visualizzo la combinazione
        $pdf->SetTextColor(0xB0, 0x64, 0x64);
        $pdf->setFontSize($fontSize + 2);
        $pdf->Cell($w['name'], $h, isset($product['combination']) ? $product['combination'] : '', 'LR', 0, 'L', 1);
        $pdf->setFontSize($fontSize + 2);
        $pdf->SetTextColor(0x80, 0x30, 0xB4);

        // Visualizzo la quantità in magazzino in diversi colori
        $stock = (int) ($product['product_quantity_in_stock'] ?? 0);
        if ($stock < 0) {
            $c = $colors['red'];
        } elseif (0 == $stock) {
            $c = $colors['dark-yellow'];
        } else {
            $c = $colors['green'];
        }
        $pdf->SetTextColor($c[0], $c[1], $c[2]);
        $pdf->Cell($w['qty'] / 2, $h, $stock, 'LR', 0, 'C', 1);

        // Visualizzo lo stock Service
        if ($product['stock_service']['is_stock_service']) {
            $c = $colors['blue'];
            $pdf->SetTextColor($c[0], $c[1], $c[2]);
            $stockService = (int) $product['stock_service']['quantity'];
        } else {
            $c = $colors['dark-gray'];
            $pdf->SetTextColor($c[0], $c[1], $c[2]);
            $stockService = '--';
        }
        $pdf->Cell($w['qty'] / 2, $h, $stockService, 'LR', 0, 'C', 1);

        // Visualizzo lo sconto
        $pdf->setFontSize($fontSize);
        $c = $colors['light-red'];
        $pdf->SetTextColor($c[0], $c[1], $c[2]);
        $discount = (!empty($product['reduction_percent']) ? "({$product['reduction_percent']} %)" : '');
        $pdf->Cell($w['price'], $h, $discount, 'LR', 1, 'R', 1);

        // Riga 3: locazione e data verifica
        $pdf->Cell($w['thumb'], $h, '', 0, 0, '', 0);
        $pdf->SetFont('', '', $fontSize - 1);
        $pdf->SetTextColor(0x35, 0x81, 0xB4);

        $pdf->SetTextColor(0x64, 0x64, 0xB0);
        $pdf->setFontSize($fontSize + 2);
        $pdf->Cell($w['location'], $h, isset($product['location']) ? $product['location'] : '', 'B', 0, 'L', 1);

        $checkDate = !empty($product['check_date']) ? $product['check_date'] : '';
        $c = $colors['dark-gray'];
        $pdf->SetTextColor($c[0], $c[1], $c[2]);
        $pdf->setFontSize($fontSize + 1);
        $pdf->Cell($w['stock_Service'], $h, 'Data verifica: '.$checkDate, 'LRB', 1, 'R', 1);
        // Reset font
        $pdf->SetFont('', '', $fontSize);
        $pdf->SetTextColor(0x28, 0x28, 0x28);

        if ($drawCustomization) {
            $this->drawProductCustomization($pdf, $product);
        }
    }

    protected function drawMessages(\TCPDF $pdf, $order_id)
    {
        $order = new \Order($order_id);
        if (!\Validate::isLoadedObject($order)) {
            return;
        }

        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql->select('id_employee, gravity, content, flags, date_add')
            ->from('mp_note')
            ->where('id_order='.(int) $order_id)
            ->orderBy('date_add DESC');
        $messages = $db->executeS($sql);

        if (!$messages) {
            return;
        }

        foreach ($messages as $key => $message) {
            $flags = json_decode($message['flags'], true);
            foreach ($flags as $flag) {
                $name = \Tools::strtolower($flag['name']);
                $value = $flag['value'];
                $messages[$key][$name] = $value;
            }
        }

        if (!$messages) {
            return '';
        }

        $w = $this->getWidths();
        $colors = $this->getColors();
        $h = $this->getHeight();
        $fontSize = $this->getFontSize();
        $pdf->AddPage();
        $pdf->SetY(10);
        $pdf->SetX(10);
        $pdf->setFontSize(18);
        $pdf->Cell(190, 8, 'MESSAGGI', 0, 1, 'C');
        $pdf->SetX(10);
        $pdf->setFontSize($fontSize);

        // Intestazione tabella
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('', 'B');
        $pdf->Cell(50, 8, 'IMPIEGATO', 1, 0, 'C', 1);
        $pdf->Cell(35, 8, 'DATA', 1, 0, 'C', 1);
        $pdf->Cell(85, 8, 'MESSAGGIO', 1, 0, 'C', 1);
        $pdf->Cell(20, 8, 'CHAT', 1, 1, 'C', 1);
        $pdf->SetFont('', '');
        $pdf->SetFillColor(255, 255, 230);

        $employees = [];
        foreach ($messages as $message) {
            if (!isset($message['stampabile']) || 0 == $message['stampabile']) {
                continue;
            }
            $id_employee = $message['id_employee'];
            if (!isset($employees[$id_employee])) {
                $employee = new \Employee($id_employee);
                $employees[$id_employee] = $employee->firstname.' '.$employee->lastname;
            }
            $impiegato = $employees[$id_employee];
            $data = isset($message['date_add']) ? date('d/m/Y H:i', strtotime($message['date_add'])) : '';
            $messaggio = $message['content'];

            // Calcola l'altezza necessaria per il messaggio
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            $cellW = [50, 35, 85, 20];
            $cellH = 8;
            $paddingBottom = 2; // mm
            // Simula la MultiCell per calcolare l'altezza
            $messaggioHeight = $pdf->getStringHeight($cellW[2], $messaggio);
            $rowHeight = max($cellH, $messaggioHeight) + $paddingBottom;

            // Colonna IMPIEGATO
            $pdf->MultiCell($cellW[0], $rowHeight, $impiegato, 1, 'L', 0, 0);
            // Colonna DATA
            $pdf->MultiCell($cellW[1], $rowHeight, $data, 1, 'C', 0, 0);
            // Colonna MESSAGGIO
            $pdf->MultiCell($cellW[2], $rowHeight, $messaggio, 1, 'L', 0, 0);
            // Colonna CHAT
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell($cellW[3], $rowHeight, '', 1, 'C', 0, 0);
            if (isset($message['chat']) && 1 == $message['chat']) {
                // Centra l'immagine nella cella
                $imgX = $x + ($cellW[3] - 5) / 2;
                $imgY = $startY + ($rowHeight - 5) / 2;
                $pdf->Image(_PS_MODULE_DIR_.'mpdocumentprint/views/img/chat.png', $imgX, $imgY, 5, 5);
            }
            $pdf->Ln();
        }
    }

    protected function drawProductCustomization(\TCPDF &$pdf, $product)
    {
        $w = $this->getWidths();
        $colors = $this->getColors();
        $h = $this->getHeight();
        $fontSize = $this->getFontSize();
        $lineHeight = $this->getLineHeight();

        // Controllo che sia un allegato con immagine
        $imgPath = '';

        if (!isset($product['customizations']) || empty($product['customizations'])) {
            return;
        }

        foreach ($product['customizations'] as $customizations) {
            $startX = $pdf->GetX();
            $startY = $pdf->GetY();
            foreach ($customizations['data'] as $customization) {
                $pdf->SetFont('', '', $fontSize - 1);
                $c = $colors['dark-gray'];
                $pdf->SetTextColor($c[0], $c[1], $c[2]);

                $pdf->SetX($startX + 35);
                $pdf->SetY($pdf->GetY() + 2);
                $pdf->Cell($w['label'], $h, $customization['label'], '', 0, 'L', 0, '', 1);
                $pdf->setFont('', 'B');
                $pdf->MultiCell($w['value'], $h, $customization['value'], 0, 'L', false, 1, $pdf->GetX(), $pdf->GetY() + 1, true, 0, false, true, 0, 'T', false);
                $pdf->setFont('', '');
            }

            // Combinazione LOGO
            $pdf->SetX($startX);
            $pdf->SetY($pdf->GetY() + 2);
            $c = $colors['blue'];
            $pdf->setTextColor($c[0], $c[1], $c[2]);
            $pdf->Cell($w['label'], $h, 'Tipo di Personalizzazione', '', 0, 'L', 0, '', 1);
            $pdf->setFont('', 'B');
            $pdf->MultiCell($w['value'], $h, $product['combination'], 0, 'L', false, 1, $pdf->GetX(), $pdf->GetY() + 1, true, 0, false, true, 0, 'T', false);
            $pdf->setFont('', '');

            foreach ($customizations['data'] as $customization) {
                if ($customization['hasFile']) {
                    $imgPath = _PS_UPLOAD_DIR_.$customization['value'];
                    if (!empty($imgPath)) {
                        if (@file_exists($imgPath)) {
                            $pdf->Image($imgPath, $startX + 160, $startY + 2, 30, 30, '', '', '', true, 300, '', false, false, 1, 'CM', false, true);
                        }
                    }
                }
            }

            $c = $colors['dark-gray'];
            $pdf->setTextColor($c[0], $c[1], $c[2]);
        }
    }

    protected function getWidths()
    {
        return [
            'thumb' => 20,
            'reference' => 32,
            'name' => 78,
            'qty' => 30,
            'price' => 30,
            'location' => 110,
            'stock_Service' => 60,
            'label' => 60,
            'value' => 100,
        ];
    }

    protected function getColors()
    {
        return [
            'red' => [0xC8, 0x1E, 0x1E],
            'light-red' => [0xB0, 0x64, 0x64],
            'green' => [0x64, 0xB0, 0x64],
            'blue' => [0x1E, 0x1E, 0xC8],
            'yellow' => [0xFF, 0xFF, 0x00],
            'dark-yellow' => [0xC0, 0xC0, 0x00],
            'orange' => [0xFF, 0xA5, 0x00],
            'purple' => [0x80, 0x00, 0x80],
            'pink' => [0xFF, 0xC0, 0xCB],
            'brown' => [0xA5, 0x2A, 0x2A],
            'gray' => [0x80, 0x80, 0x80],
            'dark-gray' => [0x40, 0x40, 0x40],
        ];
    }

    protected function getHeight()
    {
        return 7;
    }

    protected function getFontSize()
    {
        return 9;
    }

    protected function getLineHeight()
    {
        return 4.5;
    }

    protected function truncateString($string, $length, $html = false)
    {
        if (strlen($string) > $length) {
            if ($html) {
                // Grabs the original and escapes any quotes
                $original = str_replace('"', '"', $string);
            }

            // Truncates the string
            $string = substr($string, 0, $length);

            // Appends ellipses and optionally wraps in a hoverable span
            if ($html) {
                $string = '<span title="'.$original.'">'.$string.'…</span>';
            } else {
                $string .= '...';
            }
        }

        return $string;
    }
}
