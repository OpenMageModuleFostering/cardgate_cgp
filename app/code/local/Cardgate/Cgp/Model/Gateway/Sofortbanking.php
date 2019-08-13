<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Sofortbanking extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_sofortbanking';

	protected $_model = 'sofortbanking';

	protected $_canUseInternal = true;
}
