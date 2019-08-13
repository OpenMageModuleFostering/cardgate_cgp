<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Klarnaaccount extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_klarnaaccount';

	protected $_model = 'klarnaaccount';

	protected $_formBlockType = 'cgp/form_klarnaaccount';

	protected $_canUseCheckout = false;

	public function __construct ()
	{
		parent::__construct();
		// This payment method is not used in Austria;
		$klarna_countries = array( 
				'DK', 
				'FI', 
				'DE', 
				'NL', 
				'NO', 
				'SE' 
		);
		$country = Mage::getSingleton( 'checkout/session' )->getQuote()
			->getBillingAddress()
			->getCountry();
		if ( isset( $country ) && in_array( $country, $klarna_countries ) ) {
			$this->_canUseCheckout = true;
		}
	}
}
 