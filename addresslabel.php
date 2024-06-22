<?php
/**
* NOTICE OF LICENSE
*
*  @author    Kjeld Borch Egevang
*  @copyright 2023 Kjeld Borch Egevang
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*  $Date: 2024/06/22 04:05:30 $
*  E-mail: kjeld@mail4us.dk
*/

class AddressLabel extends Module
{
    private $setup;
    private $setup_vars;
    private $v177;
    private $v16;
    private $v17;

    public function __construct()
    {
        $this->v16 = _PS_VERSION_ >= "1.6.0.0";
        $this->v17 = _PS_VERSION_ >= "1.7.0.0";
        $this->v177 = _PS_VERSION_ >= "1.7.7.0";
        $this->name = 'addresslabel';
        $this->tab = 'shipping_logistics';
        $this->version = '0.0.6';
        $this->author = 'Kjeld Borch Egevang';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Address label');
        $this->description = $this->l('Module that enables you to print address labels.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your settings?');
        if (!$this->v16) {
            $this->warning = $this->l('This module only works for PrestaShop 1.6');
        }
    }


    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        if ($this->v177) {
            if (!$this->registerHook('displayAdminOrderMainBottom')) {
                return false;

            }
        } else {
            if (!$this->registerHook('displayAdminOrder')) {
                return false;
            }
        }
        return true;
    }


    public function uninstall()
    {
        $this->getSetup();
        foreach ($this->setup_vars as $setup_var) {
            $vars = $this->varsObj($setup_var);
            if (!Configuration::deleteByName($vars->glob_name)) {
                return false;
            }
        }
        return parent::uninstall();
    }


    public function varsObj($setup_var)
    {
        $vars = new StdClass();
        $keys = array(
                'glob_name',
                'var_name',
                'var_text',
                'def_val');
        $i = 0;
        foreach ($keys as $key) {
            $vars->$key = $setup_var[$i++];
        }
        return $vars;
    }


    public function getSetup()
    {
        $id_lang = $this->context->language->id;
        $this->setup_vars = array(
            array('ADDRESSLABEL_WIDTH', 'width', $this->l('Label width in mm'), 90),
            array('ADDRESSLABEL_HEIGHT', 'height', $this->l('Label height in mm'), 29),
            array('ADDRESSLABEL_SENDER', 'sender', $this->l('Sender label'), true));
        $this->setup = new StdClass();
        foreach ($this->setup_vars as $setup_var) {
            $vars = $this->varsObj($setup_var);
            $field = $vars->var_name;
            $this->setup->$field = Configuration::get($vars->glob_name);
            if ($this->setup->$field === false)
                $this->setup->$field = $vars->def_val;
        }
        return $this->setup;
    }


    public function getModuleLink($id_order, $name)
    {
        $params = array('id_order' => $id_order);
        return $this->context->link->getModuleLink($this->name, $name, $params, true);
    }


    public function getContent()
    {
        @include(_PS_MODULE_DIR_.'addresslabel/update.php');
        $output = '';
        $this->getSetup();
        if (((bool)Tools::isSubmit('submitAddresslabelModule')) == true) {
            $this->postProcess();
            $output .= '
                <div class="conf confirm">
                <img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
                '.$this->l('Settings updated').'
                </div>';
        }
        $this->context->smarty->assign('module_dir', $this->_path);
        if (class_exists('AdddresslabelUpdate', false)) {
            $update = new AddresslabelUpdate();
            if (Tools::getValue('updatedFlag')) {
                $output .= $this->makeWarning($this->l('Module updated'));
            }
            if (Tools::isSubmit('submitUpdate')) {
                $update->updateModule($this); // Will redirect on success
                if ($update->errors) {
                    $text = '<h3>'.$this->l('Errors').'</h3>';
                    $output .= $this->makeError($text, $update->errors);
                }
            }
            if ($update->newVersionAvailable()) {
                $output .= $this->makeWarning(
                    '<h3>'.$this->l('Update').'</h3>'.
                    $this->l('New version available.').'
                    <form action="'.$_SERVER['REQUEST_URI'].'" method="post">
                    <input type="submit" name="submitUpdate" value="'.
                    $this->l('Update').'" class="btn btn-default button" />
                    </form>'
                );
            }
        }
        $output .= $this->context->smarty->fetch(
            $this->local_path.'views/templates/admin/configure.tpl'
        );
        $output .= $this->renderForm();
        return $output;
    }


    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang =
            Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAddresslabelModule';
        $helper->currentIndex =
            $this->context->link->getAdminLink('AdminModules', false).
            '&configure='.$this->name.
            '&tab_module='.$this->tab.
            '&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars =
            array(
                'fields_value' => $this->getConfigFormValues(),
                'languages' => $this->context->controller->getLanguages(),
                'id_language' => $this->context->language->id,
             );

        $out = $helper->generateForm(array($this->getConfigSettings()));
        return $out;
    }


    protected function getConfigInput($vars)
    {
        if (is_int($vars->def_val)) {
            $input = array(
		'size' => $vars->def_val === '' ? 40 : 10,
		'col' => $vars->def_val === '' ? 3 : 1,
		'type' => 'text',
		'name' => $vars->glob_name,
		'label' => $vars->var_text
            );
        }
        else {
            $input = array(
                'type' => 'switch',
                'name' => $vars->glob_name,
                'label' => $vars->var_text,
                'values' => array(
                    array(
                        'id' => 'on',
                        'value' => '1',
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id' => 'off',
                        'value' => '0',
                        'label' => $this->l('No'),
                     )
                )
            );
        }
        return $input;
    }


    protected function getConfigSettings()
    {
        $inputs = array();
        foreach ($this->setup_vars as $setup_var) {
            $vars = $this->varsObj($setup_var);
            $inputs[] = $this->getConfigInput($vars);
        }
        $submit = array(
                'title' => $this->l('Save'),
                );
        $form = array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                    ),
                'input' => $inputs,
                'submit' => $submit,
                );
        return array('form' => $form);
    }


    protected function getConfigFormValues()
    {
        $setup = $this->setup;
        $values = array();
        foreach ($this->setup_vars as $setup_var) {
            $vars = $this->varsObj($setup_var);
            $field = $vars->var_name;
            $values[$vars->glob_name] = $setup->$field;
        }
        return $values;
    }


    protected function postProcess()
    {
        foreach ($this->setup_vars as $setup_var) {
            $vars = $this->varsObj($setup_var);
            $val = Tools::getValue($vars->glob_name, null);
            if ($val === null) {
                continue;
            }
            if (is_int($vars->def_val)) {
                $val = (int)$val;
            }
            if (is_float($vars->def_val)) {
                $val = (float)$val;
            }
            Configuration::updateValue($vars->glob_name, $val);
        }
        // Read the new setup
        $setup = $this->getSetup();
    }


    public function hookDisplayAdminOrderMainBottom($params)
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);
        $url = $this->getModuleLink($order->id, 'print');
        $token = $order->secure_key;
        $this->context->smarty->assign(
            array(
                'url' => $url,
                'token' => $token,
                'print' => $this->l('Print')
            )
        );
        $html = $this->context->smarty->fetch(
            $this->local_path.'views/templates/hook/order.tpl'
        );
        return $html;
    }


    public function hookDisplayAdminOrder($params)
    {
        if ($this->v177) {
            return '';
        } else {
            return $this->hookDisplayAdminOrderMainBottom($params);
        }
    }


    public function addPage(&$pdf, $lines, $sender)
    {
        $setup = $this->setup;
        $pdf->AddPage();
        $pdf->setMargins(0, 0);
        $pdf->setX(0);
        $pdf->setY(0);

        for ($fontSize = 20; $fontSize >= 9; $fontSize--) {
            $pdf->setFontSize($fontSize);
            $pdf->startTransaction();
            $start_x = $pdf->GetX();
            $start_y = $pdf->GetY();
            $end_x  = 0;
            if ($sender) {
                $pdf->setFontSize(9);
                $pdf->Write(0, $this->l('Sender:'), '', 0, 'L', true);
                $pdf->setFontSize($fontSize);
            }
            foreach ($lines as $line) {
                if ($line) {
                    $pdf->Write(0, $line, '', 0, 'L');
                    if ($end_x < $pdf->GetX())
                        $end_x = $pdf->GetX();
                    $pdf->Write(0, '', '', 0, 'L', true);
                }
            }
            $end_y = $pdf->GetY();
            $width = $end_x - $start_x;
            $height = $end_y - $start_y;
            $pdf = $pdf->rollbackTransaction();
            $left = ($setup->width - $width) / 2;
            $top = ($setup->height - $height) / 2;
            if ($left > 0 && $top > 0)
                break;
        }

        if ($top > 0)
            $pdf->setY($top);
        if ($sender) {
            $pdf->SetLineWidth(0.3);
            $pdf->Line(0, 0, $setup->width, $setup->height);
            $pdf->Line(0, $setup->height, $setup->width, 0);
            $pdf->setFontSize(9);
            $pdf->Write(0, $this->l('Sender:'), '', 0, 'L', true);
            $pdf->setFontSize($fontSize);
        }
        foreach ($lines as $line) {
            if ($line) {
                if ($left > 0)
                    $pdf->setX($left);
                $pdf->Write(0, $line, '', 0, 'L', true);
            }
        }
    }


    public function printLabel()
    {
        if (!$this->v17) {
            new PDFGenerator();
        }
        $setup = $this->getSetup();
        $id_order = Tools::getValue('id_order');
        $token = Tools::getValue('token');
        $order = new Order((int)$id_order);
        if ($token != $order->secure_key) {
            die('Bad token');
        }
        $id_lang = $order->id_lang;
        $address = new Address($order->id_address_delivery);
        $country = new Country($address->id_country);
        $state = new State($address->id_state);
        $countryName = Country::getNameById($id_lang, $country->id);
        $stateName = State::getNameById($state->id);
        $shopState = new State(Configuration::get('PS_SHOP_STATE_ID'));
        $shopStateName = State::getNameById($shopState->id);
        $shopCountry = new Country(Configuration::get('PS_SHOP_COUNTRY_ID'));
        $shopCountryName = Country::getNameById($id_lang, $shopCountry->id);
        if ($setup->width > $setup->height)
            $pdf = new TCPDF('L', 'mm', array($setup->width, $setup->height));
        else
            $pdf = new TCPDF('P', 'mm', array($setup->width, $setup->height));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        $lines = array();
        $lines[] = trim($address->company);
        $lines[] = $address->firstname.' '.$address->lastname;
        $lines[] = $address->address1;
        $lines[] = $address->address2;
        $lines[] = $address->postcode.' '.$address->city;
        $lines[] = $stateName;
        if ($country->iso_code != $shopCountry->iso_code)
            $lines[] = $countryName;
        $this->addPage($pdf, $lines, false);

        if ($setup->sender) {
            $lines = array();
            $lines[] = Configuration::get('PS_SHOP_NAME');
            $lines[] = Configuration::get('PS_SHOP_ADDR1');
            $lines[] = Configuration::get('PS_SHOP_ADDR2');
            $lines[] = Configuration::get('PS_SHOP_CODE').' '.
                Configuration::get('PS_SHOP_CITY');
            $lines[] = $shopStateName;
            if ($country->iso_code != $shopCountry->iso_code)
                $lines[] = $shopCountryName;
            $this->addPage($pdf, $lines, true);
        }

        $pdf->Output('label.pdf', 'I');
    }
}
