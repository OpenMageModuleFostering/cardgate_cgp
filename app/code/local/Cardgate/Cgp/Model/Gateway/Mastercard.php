<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Mastercard extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_mastercard';

	protected $_model = 'mastercard';

	protected $_canUseInternal = true;
}
