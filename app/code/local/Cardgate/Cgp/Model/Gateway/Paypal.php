<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Paypal extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_paypal';

	protected $_model = 'paypal';

	protected $_canUseInternal = true;
}
