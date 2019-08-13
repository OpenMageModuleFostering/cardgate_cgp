<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Banktransfer extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_banktransfer';

	protected $_model = 'banktransfer';

	protected $_canUseInternal = true;

	protected $_formBlockType = 'cgp/form_banktransfer';
}
