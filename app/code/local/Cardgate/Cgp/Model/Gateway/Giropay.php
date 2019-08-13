<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Giropay extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_giropay';

	protected $_model = 'giropay';

	protected $_canUseInternal = true;
}
