<?php
/**
 * PrestaPay - A Sample Payment Module for PrestaShop 1.7
 *
 * Order Validation Controller
 *
 * @author Pier Luigi Papeschi info@pierluigipapeschi.com
 * @license https://opensource.org/licenses/afl-3.0.php
 */

class PPPostepayValidationModuleFrontController extends ModuleFrontController
{
  /**
	 * @see FrontController::postProcess()
	 */
  public function postProcess()
  {
    $cart = $this->context->cart;
    $authorized = false;

    if (!$this->module->active 
      || $cart->id_customer == 0 
      || $cart->id_address_delivery == 0
      || $cart->id_address_invoice == 0) 
    {
      Tools::redirect('index.php?controller=order&step=1');
    }

    foreach (Module::getPaymentModules() as $module) {
      if ($module['name'] == 'pppostepay') {
        $authorized = true;
        break;
      }
    }

    if (!$authorized) {
      die($this->l('This payment method is not available.', 'pppostepay'));
    }

    $customer = new Customer($cart->id_customer);

    if (!Validate::isLoadedObject($customer)) {
      Tools::redirect('index.php?controller=order&step=1');
    }

    $mailVars = array(
			'{owner_name}' => Configuration::get('PPPMOD_OWNAME'),
			'{owner_cf}' => nl2br(Configuration::get('PPPMOD_OWNCF')),
			'{ppnr}' => nl2br(Configuration::get('PPPMOD_PPNR'))
		);

    $this->module->validateOrder(
      (int) $this->context->cart->id,
      Configuration::get('PS_OS_PPPMOD_PAYMENT'),
      (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
      $this->module->displayName,
      null,
      $mailVars,
      (int) $this->context->currency->id,
      false,
      $customer->secure_key
    );

    Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
  }
}