<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Klarna extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_klarna';

	protected $_model = 'klarna';

	protected $_formBlockType = 'cgp/form_klarna';

	protected $_canUseCheckout = false;

	public function __construct ()
	{
		parent::__construct();
		$klarna_countries = array( 
				'AT', 
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
