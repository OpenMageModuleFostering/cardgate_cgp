<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Paysafecard extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_paysafecard';

	protected $_model = 'paysafecard';

	protected $_canUseInternal = true;
}
