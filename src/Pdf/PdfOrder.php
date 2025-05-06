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

        $products = $this->getProducts($order);

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
            'products' => $products,
            'current_state' => $this->getCurrentState($order),
            'is_new_customer' => IsNewCustomer::check($order->id_customer),
        ];

        return $this;
    }

    public function render()
    {
        $document = $this->document;
        $id_order = (int) $document['order']['id_order'];

        // Inizializza TCPDF
        $pdf = new \TCPDF();
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
        $products = $order->getProducts();
        foreach ($products as &$product_item) {
            $product = new \Product($product_item['product_id'], false, $this->id_lang);
            $product_item['product_name_full'] = $product_item['product_name'];
            $product_item['product_name'] = $product->name;
            $product_item['combination'] = $this->getCombination($product, $product_item['product_attribute_id']);
            $product_item['image_url'] = $this->getImage($product_item['product_id']);
            $product_item['price_currency'] = $this->locale->formatPrice($product_item['price'], $this->currencyIsoCode);
            $product_item['stock_service'] = rand(0, 10);
            $product_item['check_date'] = date_format(new \DateTime(date('Y-m-d H:i:s')), 'd/m/Y H:i');
        }

        return $products;
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
        $payment = isset($data['order']['payment']) ? $data['order']['payment'] : '';

        $rightX = $pdf->GetX();
        $rightY = $pdf->GetY();
        $pdf->SetXY($rightX, $rightY);

        // Riga 1: Ordine
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell($rightW, 5.5, "Ordine: $orderId del $orderDate", 0, 2, 'L', 0);
        // Riga 2: Stato
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($rightW, 5, "Stato corrente: $stateName", 0, 2, 'L', 0);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->setX($rightX + 25);
        $pdf->Cell($rightW, 4, $stateDate, 0, 2, 'L', 0);
        // Riga 3: Pagamento
        $pdf->setX($rightX);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($rightW, 5, "Tipo di Pagamento: $payment", 0, 2, 'L', 0);

        $pdf->Ln(2);

        // --- LINEA DIVISORIA ---
        $pdf->SetDrawColor(136, 136, 136);
        $pdf->SetLineWidth(0.4);
        $pdf->setY($pdf->GetY() + 2);
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

        $pdf->cell(30, 10, 'CODICE CLIENTE:', 0, 0, 'L', 0);
        $pdf->setFont('', 'B', $boxFont + 1);
        $pdf->Cell(20, 10, $customerId, 0, 0, 'L', 0);
        $pdf->setFont('', '', $boxFont);
        if ($isOld) {
            $pdf->setTextColor(200, 30, 30);
            $pdf->setFont('', 'B', $boxFont);
            $pdf->Cell(10, 10, 'V', 0, 10, 'L');
            $pdf->setTextColor(40, 40, 40);
            $pdf->setFont('', '', $boxFont);
        } else {
            // vai a capo
            $pdf->Ln(10);
        }

        // Data ordine e totale ordine subito sotto Codice Cliente
        $pdf->SetFont('helvetica', '', $boxFont);
        $pdf->setX($x + $colX[2]);
        $pdf->cell($colW[2], 0, "Data Ordine: $orderDateFull", 0, 10, 'L');
        $pdf->setX($x + $colX[2]);
        $pdf->cell($colW[2], 0, "Totale ordine: $total", 0, 10, 'L');

        $pdf->Ln(10);
    }

    /**
     * Formatta l'indirizzo come stringa multilinea per TCPDF.
     */
    protected function formatAddress($address, $state, $country)
    {
        $out = '';
        if (!empty($address['company'])) {
            $out .= $address['company']."\n";
        }
        $out .= (isset($address['firstname']) ? $address['firstname'] : '').' '.(isset($address['lastname']) ? $address['lastname'] : '')."\n";
        $out .= (isset($address['address1']) ? $address['address1'] : '')."\n";
        if (!empty($address['address2'])) {
            $out .= $address['address2']."\n";
        }
        $out .= (isset($address['postcode']) ? $address['postcode'] : '').' '.(isset($address['city']) ? $address['city'] : '');
        if (!empty($state['iso_code'])) {
            $out .= ' '.$state['iso_code'];
        }
        if (!empty($country['iso_code'])) {
            $out .= ' '.$country['iso_code'];
        }

        return trim($out);
    }

    protected function writeProducts(\TCPDF $pdf, $products)
    {
        // Definizione larghezze colonne (mm)
        // Larghezze colonna per coprire tutta la pagina (A4, margini 10mm -> 190mm)
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
        $lineHeight = 4.5;

        // Funzione interna per header tabella prodotti
        $printTableHeader = function () use ($pdf, $w, $h, $fontSize) {
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
        };

        $printTableHeader();

        foreach ($products as $i => $product) {
            $fill = (0 == $i % 2) ? [255, 255, 255] : [246, 246, 246];
            $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);

            // Calcolo spazio necessario (3 righe)
            $blockHeight = $h * 3;
            if ($pdf->GetY() + $blockHeight > ($pdf->getPageHeight() - $pdf->getBreakMargin())) {
                $pdf->AddPage();
                $printTableHeader();
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
            $pdf->Cell($w['reference'], $h, isset($product['reference']) ? $product['reference'] : '', 'LTR', 0, 'L', 1);
            $pdf->Cell($w['name'], $h, isset($product['product_name']) ? $product['product_name'] : '', 'LTR', 0, 'L', 1);
            $pdf->Cell($w['qty'], $h, isset($product['quantity']) ? $product['quantity'] : '', 'LTR', 0, 'C', 1);
            $pdf->Cell($w['price'], $h, isset($product['price_currency']) ? $product['price_currency'] : '', 'LTR', 1, 'R', 1);

            // Riga 2: combinazione, stock, sconto
            $pdf->Cell($w['thumb'], $h, '', 0, 0, '', 0);
            $pdf->SetFont('', '', $fontSize - 1);
            $pdf->SetTextColor(0x64, 0x64, 0x64);
            $pdf->Cell($w['reference'], $h, '', 'LR', 0, 'L', 1);
            $pdf->SetTextColor(0xB0, 0x64, 0x64);
            $pdf->setFontSize($fontSize + 2);
            $pdf->Cell($w['name'], $h, isset($product['combination']) ? $product['combination'] : '', 'LR', 0, 'L', 1);
            $pdf->SetTextColor(0x35, 0x81, 0xB4);
            $pdf->setFontSize($fontSize + 2);
            $pdf->Cell($w['qty'], $h, isset($product['stock_service']) ? $product['stock_service'] : '', 'LR', 0, 'C', 1);
            $pdf->setFontSize($fontSize - 1);
            $pdf->SetTextColor(0xC8, 0x00, 0x00);
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
            $pdf->setTextColor(0x64, 0xB0, 0x64);
            $pdf->setFontSize($fontSize + 1);
            $pdf->Cell($w['stock_Service'], $h, 'Data verifica: '.$checkDate, 'LRB', 1, 'R', 1);
            // Reset font
            $pdf->SetFont('', '', $fontSize);
            $pdf->SetTextColor(0x28, 0x28, 0x28);
        }
    }
}
