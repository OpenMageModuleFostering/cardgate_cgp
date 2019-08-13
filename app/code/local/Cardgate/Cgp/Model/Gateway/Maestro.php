<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Maestro extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_maestro';

	protected $_model = 'maestro';

	protected $_canUseInternal = true;
}
