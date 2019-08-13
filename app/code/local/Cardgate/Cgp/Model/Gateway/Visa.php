<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Visa extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_visa';

	protected $_model = 'visa';

	protected $_canUseInternal = true;
}
