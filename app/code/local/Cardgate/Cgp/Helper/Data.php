<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * Check if OneStepCheckout is activated or not
	 *
	 * @return bool
	 */
	public function isOneStepCheckout ()
	{
		return ( bool ) Mage::getStoreConfig( 'onestepcheckout/general/rewrite_checkout_links' );
	}
}
