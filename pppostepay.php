<?php
/*
* 2007-2015 PrestaShop
* 2021 Pier Luig Papeschi
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PPPostePay extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $owner_name;
    public $owner_cf;
    public $ppnr;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'pppostepay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Pier Luigi Papeschi';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ricarica PostyePay');
        $this->description = $this->l('Modulo di gestione del pagamento della ricarica della tua carta PostePay. Da non confondersi con il pagamento con la carta postepay usata come carta di credito.');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        if (!empty(Configuration::get('PPPMOD_OWNAME')))
            $this->owner_name = Configuration::get('PPPMOD_OWNAME');
        if (!empty(Configuration::get('PPPMOD_OWNCF')))
			$this->owner_cf = nl2br(Configuration::get('PPPMOD_OWNCF'));
        if (!empty(Configuration::get('PPPMOD_PPNR')))
			$this->ppnr = nl2br(Configuration::get('PPPMOD_PPNR'));

        $this->extra_mail_vars = array(
            '{owner_name}' => $this->owner_name,
			'{owner_cf}' => $this->owner_cf,
			'{ppnr}' => $this->ppnr
        );
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->installOrderState();
    }

    public function installOrderState()
	{
		if (Configuration::get('PS_OS_PPPMOD_PAYMENT') < 1)
		{
			$order_state = new OrderState();
			$order_state->send_email = true;
			$order_state->module_name = $this->name;
			$order_state->invoice = false;
			$order_state->color = '#f6ff33';
			$order_state->logable = true;
			$order_state->shipped = false;
			$order_state->unremovable = false;
			$order_state->delivery = false;
			$order_state->hidden = false;
			$order_state->paid = false;
			$order_state->deleted = false;
			$order_state->name = array((int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('Ricarica PostePay')));
			$order_state->template = array();
			foreach (LanguageCore::getLanguages() as $l)
				$order_state->template[$l['id_lang']] = 'pppostepay';

			foreach (LanguageCore::getLanguages() as $l)
			{
				$module_path = dirname(__FILE__).'/views/templates/mails/'.$l['iso_code'].'/';
				$application_path = dirname(__FILE__).'/../../mails/'.$l['iso_code'].'/';
				if (!copy($module_path.'pppostepay.txt', $application_path.'pppostepay.txt') ||
					!copy($module_path.'pppostepay.html', $application_path.'pppostepay.html'))
					return false;
			}

			if ($order_state->add())
			{
				Configuration::updateValue('PS_OS_PPPMOD_PAYMENT', $order_state->id);

				copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/os/'.$order_state->id.'.gif');
				copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/tmp/order_state_mini_'.$order_state->id.'.gif');
			}
			else
				return false;
		}
		return true;
	}

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function processConfiguration()
    {
        if (Tools::isSubmit('ppp_pc_form'))
        {
            $this->owner_name = Tools::getValue('owner_name');
            $this->owner_cf = Tools::getValue('owner_cf');
            $this->ppnr = Tools::getValue('ppnr');

            Configuration::updateValue('PPPMOD_OWNAME', $this->owner_name);
            Configuration::updateValue('PPPMOD_OWNCF', $this->owner_cf);
            Configuration::updateValue('PPPMOD_PPNR', $this->ppnr);

            //$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
            $this->context->smarty->assign('confirmation', 'ok');
        }
    }

    public function assignConfiguration()
    {
        $this->owner_name = Configuration::get('PPPMOD_OWNAME');
        $this->owner_cf = Configuration::get('PPPMOD_OWNCF');
        $this->ppnr = Configuration::get('PPPMOD_PPNR');

        $this->context->smarty->assign('owner_name', $this->owner_name);
        $this->context->smarty->assign('owner_cf', $this->owner_cf);
        $this->context->smarty->assign('ppnr', $this->ppnr);
    }

    public function getContent()
    {
        $this->processConfiguration();
        $this->assignConfiguration();

        return $this->display(__FILE__, 'getContent.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $formAction = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
        $paymentForm = $this->context->smarty->assign(array(
                'action' => $formAction,
                'owner_name' => Configuration::get('PPPMOD_OWNAME'),
                'owner_cf' => Configuration::get('PPPMOD_OWNCF'),
                'ppnr' => Configuration::get('PPPMOD_PPNR')
            ))->fetch('module:pppostepay/views/templates/hook/payment_options.tpl');
 
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption
            ->setModuleName($this->displayName)
            ->setCallToActionText($this->displayName)
            ->setAction($formAction)
            ->setForm($paymentForm)
            ->setLogo(_MODULE_DIR_ . 'pppostepay/views/img/postepay_icon_32.png');
 
        $payment_options = array(
            $newOption,
        );
 
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
 
        return $this->context->smarty->assign(array(
                'owner_name' => Configuration::get('PPPMOD_OWNAME'),
                'owner_cf' => Configuration::get('PPPMOD_OWNCF'),
                'ppnr' => Configuration::get('PPPMOD_PPNR')
            ))->fetch('module:pppostepay/views/templates/hook/payment_return.tpl');
    }
}