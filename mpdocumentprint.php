<?php

/*
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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/vendor/autoload.php';
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class MpDocumentPrint extends Module
{
    protected $adminClassName = 'AdminMpDocumentPrint';

    public function __construct()
    {
        $this->name = 'mpdocumentprint';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('MP Stampa documenti');
        $this->description = $this->l('Stampa documenti personalizzati');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook([
                'actionAdminControllerSetMedia',
                'displayAdminEndContent',
            ])
            && $this->installMenu();
    }

    public function installMenu()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $this->adminClassName;
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'MP Document Print';
        }

        $tabRes = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $tab_id = $tabRes->findOneIdByClassName('AdminAdvancedParameters');

        $tab->id_parent = $tab_id;
        $tab->module = $this->name;
        $tab->icon = 'local_shipping';
        $tab->enabled = 1;
        $tab->active = 1;

        return $tab->add();
    }

    public function uninstallMenu()
    {
        $tab = new Tab();
        $tab->class_name = $this->adminClassName;
        $tab->module = $this->name;

        return $tab->delete();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallMenu();
    }

    public function getContent()
    {
        $output = '';
        $keys = [];
        if (Tools::isSubmit('submit_mpbrtapishipment')) {
            // Salva tutti i parametri
            foreach ($keys as $key) {
                $val = Tools::getValue($key);
                if (is_array($val)) {
                    $val = implode(',', $val);
                }
                Configuration::updateValue($key, $val);
            }
            $output .= $this->displayConfirmation($this->l('Configurazione aggiornata'));
        }

        // Recupera dati per i select/multiselect dinamici
        $employees = $this->getEmployees();
        $orderStates = $this->getOrderStates();

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->l('Configurazione BRT API')],
                'input' => [
                    // Link CRON
                    [
                        'type' => 'html',
                        'label' => $this->l('Link CRON'),
                        'name' => 'cron_link',
                        'html_content' => '',
                    ],
                    // Ambiente e credenziali
                    [
                        'type' => 'select',
                        'label' => $this->l('Ambiente BRT (default)'),
                        'name' => 'BRT_ENVIRONMENT',
                        'options' => [
                            'query' => [
                                ['id' => 'real', 'name' => 'Produzione'],
                                ['id' => 'sandbox', 'name' => 'Sandbox/Test'],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'col' => 2,
                        'type' => 'text',
                        'label' => $this->l('UserID Produzione'),
                        'name' => 'BRT_REAL_USERID',
                    ],
                    [
                        'col' => 2,
                        'type' => 'text',
                        'label' => $this->l('Password Produzione'),
                        'name' => 'BRT_REAL_PASSWORD',
                    ],
                    [
                        'col' => 2,
                        'type' => 'text',
                        'label' => $this->l('UserID Sandbox'),
                        'name' => 'BRT_SANDBOX_USERID',
                    ],
                    [
                        'col' => 2,
                        'type' => 'text',
                        'label' => $this->l('Password Sandbox'),
                        'name' => 'BRT_SANDBOX_PASSWORD',
                    ],
                    // Accesso impiegati
                    [
                        'col' => 6,
                        'type' => 'select',
                        'label' => $this->l('Impiegati abilitati'),
                        'name' => 'BRT_EMPLOYEES',
                        'multiple' => true,
                        'class' => 'select2',
                        'options' => [
                            'query' => $employees,
                            'id' => 'id_employee',
                            'name' => 'name',
                        ],
                    ],
                    // Stati ordine: mostra pulsante
                    [
                        'col' => 6,
                        'type' => 'select',
                        'label' => $this->l('Mostra pulsante BRT su stato ordine'),
                        'name' => 'BRT_ORDERSTATE_SHOWBTN',
                        'class' => 'select2',
                        'options' => [
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ],
                    ],
                    // Stati ordine: dopo invio
                    [
                        'col' => 6,
                        'type' => 'select',
                        'label' => $this->l('Stato ordine dopo invio a BRT'),
                        'name' => 'BRT_ORDERSTATE_AFTERSEND',
                        'class' => 'select2',
                        'options' => [
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ],
                    ],
                    // Departure Depot
                    [
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Codice Filiale Mittente (departureDepot)'),
                        'name' => 'BRT_DEPARTURE_DEPOT',
                    ],
                    [
                        'col' => 6,
                        'type' => 'select',
                        'label' => $this->l('Tipo di pagamento contrassegno'),
                        'name' => 'BRT_PAYMENT_COD',
                        'class' => 'select2',
                        'options' => [
                            'query' => [
                                ['value' => '',   'label' => 'ACCETTARE CONTANTE'],
                                ['value' => 'BM', 'label' => 'ACCETTARE ASSEGNO BANCARIO INTESTATO ALLA MITTENTE'],
                                ['value' => 'CM', 'label' => 'ACCETTARE ASSEGNO CIRCOLARE INTESTATO ALLA MITTENTE'],
                                ['value' => 'BB', 'label' => 'ACCETTARE ASSEGNO BANCARIO INTESTATO CORRIERE CON MANLEVA'],
                                ['value' => 'OM', 'label' => 'ACCETTARE ASSEGNO INTESTATO AL MITTENTE ORIGINALE'],
                                ['value' => 'OC', 'label' => 'ACCETTARE ASSEGNO CIRCOLARE INTESTATO AL MITTENTE ORIGINALE'],
                            ],
                            'id' => 'value',
                            'name' => 'label',
                        ],
                    ],
                    // Avvisi
                    [
                        'type' => 'switch',
                        'label' => $this->l('Avvisa via Email'),
                        'name' => 'BRT_ALERT_BY_EMAIL',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Si')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Avvisa via SMS'),
                        'name' => 'BRT_ALERT_BY_SMS',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Si')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    // Parametri label
                    [
                        'type' => 'switch',
                        'label' => $this->l('Creazione etichetta?'),
                        'name' => 'BRT_IS_LABEL_REQUIRED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Si')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Stampa PDF (altrimenti ZPL)'),
                        'name' => 'BRT_LABEL_OUTPUT_TYPE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 'PDF', 'label' => $this->l('PDF')],
                            ['id' => 'off', 'value' => 'ZPL', 'label' => $this->l('ZPL')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Etichetta con bordo?'),
                        'name' => 'BRT_LABEL_BORDER',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Si')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Etichetta con barcode?'),
                        'name' => 'BRT_LABEL_BARCODE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Si')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Etichetta con logo?'),
                        'name' => 'BRT_LABEL_LOGO',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Si')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Offset X'),
                        'name' => 'BRT_LABEL_OFFSET_X',
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'label' => $this->l('Offset Y'),
                        'name' => 'BRT_LABEL_OFFSET_Y',
                    ],
                ],
                'submit' => ['title' => $this->l('Salva')],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submit_mpbrtapishipment';
        // Precompila valori
        foreach ($keys as $key) {
            $value = Configuration::get($key);
            $helper->fields_value[$key] = $value;
        }

        return $output.$helper->generateForm([$fields_form]);
    }

    /**
     * Restituisce elenco impiegati per multiselect.
     */
    private function getEmployees()
    {
        $emps = [];
        foreach (Employee::getEmployees() as $e) {
            $emps[] = [
                'id_employee' => $e['id_employee'],
                'name' => $e['firstname'].' '.$e['lastname'],
            ];
        }

        return $emps;
    }

    /**
     * Restituisce elenco stati ordine.
     */
    private function getOrderStates()
    {
        $states = [];
        foreach (OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')) as $s) {
            $states[] = [
                'id_order_state' => $s['id_order_state'],
                'name' => $s['name'],
            ];
        }

        return $states;
    }

    /**
     * Carica CSS/JS custom nell'admin quando necessario.
     *
     * @param array $params
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Tools::getValue('controller');
        $cssPath = $this->getLocalPath().'views/css/';
        $jsPath = $this->getLocalPath().'views/js/';
        $id_order = (int) Tools::getvalue('id_order');

        if (in_array($controller, ['AdminOrders', 'AdminModules'])) {
            $this->context->controller->addCSS([
                'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=barcode',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                $jsPath.'Select2/select2.min.css',
                $cssPath.'style.css',
            ]);
            $this->context->controller->addJS([
                $jsPath.'swal2/sweetalert2.min.js',
                $jsPath.'swal2/request/SwalConfirm.js',
                $jsPath.'swal2/request/SwalError.js',
                $jsPath.'swal2/request/SwalInput.js',
                $jsPath.'swal2/request/SwalLoading.js',
                $jsPath.'swal2/request/SwalNote.js',
                $jsPath.'swal2/request/SwalSuccess.js',
                $jsPath.'swal2/request/SwalWarning.js',
                $jsPath.'QzTray/QzTray.js',
                $jsPath.'Printer/PrintDocument.js',
                $jsPath.'Printer/TestQzTray.js',
                $jsPath.'Admin/AdminOrdersList.js',
                $jsPath.'openBase64PdfInNewTab.js',
                $jsPath.'previewBase64Pdf.js',
                $jsPath.'previewBase64PdfFullHeight.js',
            ]);
        }

        if (preg_match('/AdminOrders/i', $controller) && $id_order) {
            $this->context->controller->addJs([
                $jsPath.'Admin/AdminOrder.js',
            ]);
        }
    }

    /**
     * Mostra contenuto custom in fondo alla pagina ordine in BO.
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminEndContent($params)
    {
        $controller = Tools::getValue('controller');
        $isAdminOrdersController = preg_match('/AdminOrders/i', $controller);
        $isModuleAdminController = preg_match('/AdminModules/i', $controller);
        $id_order = (int) Tools::getValue('id_order');

        if (!$isAdminOrdersController && !$isModuleAdminController) {
            return '';
        }

        if ($isAdminOrdersController && !$id_order) {
            $adminControllerURL = $this->context->link->getAdminLink($this->adminClassName);

            $script = <<<JS
                <script type="text/javascript">
                    const MpDocumentPrintAdminControllerURL = "{$adminControllerURL}";
                    //creo un nuovo evento custom
                    const MPDocumentPrintCustomEvent = new CustomEvent("MPDocumentPrintCustomEvent");
                    document.dispatchEvent(MPDocumentPrintCustomEvent);
                </script>
            JS;

            return $script;
        }

        if ($isAdminOrdersController && $id_order > 0) {
            $adminControllerURL = $this->context->link->getAdminLink($this->adminClassName);

            $script = <<<JS
                <script type="text/javascript">
                    const MpDocumentPrintAdminControllerURL = "{$adminControllerURL}";
                    
                    //creo un nuovo evento custom
                    const MPDocumentPrintCustomEvent = new CustomEvent("MPDocumentPrintCustomEvent", {
                        detail: {
                            orderId: "{$id_order}",
                        },
                    });
                    document.dispatchEvent(MPDocumentPrintCustomEvent);
                    
                    document.addEventListener('DOMContentLoaded', () => {
                        console.log("DOMCONTENT Loaded: AdminOrder");
                        $(".select2").select2({
                            language: "it",
                            width: '100%'
                        });
                    });
                </script>
            JS;

            return $script;
        }

        if ($isModuleAdminController) {
            $adminControllerURL = $this->context->link->getAdminLink($this->adminClassName);

            $script = <<<JS
                <script type="text/javascript">
                    const MpDocumentPrintAdminControllerURL = "{$adminControllerURL}";
                    document.addEventListener('DOMContentLoaded', () => {
                        console.log("DOMCONTENT Loaded: Applying Select2");
                        $(".select2").select2({
                            language: "it",
                            width: '100%'
                        });
                    });
                </script>
            JS;

            return $script;
        }

        return '';
    }
}
