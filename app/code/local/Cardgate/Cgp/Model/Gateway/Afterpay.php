<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Afterpay extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_afterpay';

	protected $_model = 'afterpay';

	protected $_canUseInternal = true;
}
