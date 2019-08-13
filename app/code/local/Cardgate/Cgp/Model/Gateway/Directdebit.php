<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Directdebit extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_directdebit';

	protected $_model = 'directdebit';

	protected $_canUseInternal = true;
}
