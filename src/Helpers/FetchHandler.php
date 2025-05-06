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

class FetchHandler
{
    private $module;
    private $controller;

    public function __construct($module, $controller)
    {
        $this->module = $module;
        $this->controller = $controller;
    }

    public function run()
    {
        // controlla se la richiesta è via browser o tramite cli
        if ('cli' !== php_sapi_name()) {
            $phpInput = file_get_contents('php://input');
            if ($phpInput) {
                $jsonData = json_decode($phpInput, true);
                $isAjax = isset($jsonData['ajax']) && 1 === (int) $jsonData['ajax'];
                if ($jsonData && isset($jsonData['action']) && $isAjax) {
                    $action = 'ajaxProcess'.\Tools::ucfirst($jsonData['action']);
                    if (method_exists($this->controller, $action)) {
                        $result = $this->controller->$action($jsonData);
                        $this->sendAjaxResponse($result);
                        exit;
                    }
                    http_response_code(400);
                    exit('<div style="color:red;padding:2em;">Azione non valida.</div>');
                }
            }
        } else {
            $this->sendAjaxResponse('Richiesta non valida.', false, 400, 'Richiesta non valida.', ['action' => 'fetch']);
        }
    }

    /**
     * Restituisce una risposta AJAX JSON standardizzata.
     *
     * @param mixed       $data     Dati da restituire (o Exception)
     * @param bool        $success  true se l'operazione è andata a buon fine
     * @param int         $httpCode Codice HTTP da restituire (default 200 o 400/500 in base all'esito)
     * @param string|null $message  Messaggio opzionale
     * @param array       $extra    Altri dati opzionali da aggiungere alla risposta
     */
    protected function sendAjaxResponse($data, $success = true, $httpCode = null, $message = null, $extra = [])
    {
        $response = [
            'success' => $success,
            'data' => null,
            'error' => null,
            'message' => $message,
        ];

        if ($data instanceof \Exception) {
            $response['success'] = false;
            $response['error'] = $data->getMessage();
            $response['data'] = null;
            if (null === $httpCode) {
                $httpCode = 500;
            }
        } elseif ($success) {
            $response['data'] = $data;
            $response['error'] = null;
            if (null === $httpCode) {
                $httpCode = 200;
            }
        } else {
            $response['data'] = null;
            $response['error'] = is_string($data) ? $data : 'Errore sconosciuto';
            if (null === $httpCode) {
                $httpCode = 400;
            }
        }

        if (!empty($extra) && is_array($extra)) {
            $response = array_merge($response, $extra);
        }

        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
