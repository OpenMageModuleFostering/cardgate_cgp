<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Pos extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_pos';

	protected $_model = 'pos';

	protected $_canUseInternal = true;
	protected $_canUseCheckout = false;
	protected $_canReviewPayment = true;
	protected $_canCompleteByMerchant = true;
}
