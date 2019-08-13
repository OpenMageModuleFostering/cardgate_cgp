<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Bitcoin extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_bitcoin';

	protected $_model = 'bitcoin';

	protected $_canUseInternal = true;
}
