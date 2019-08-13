<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Vpay extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_vpay';

	protected $_model = 'vpay';

	protected $_canUseInternal = true;
}
