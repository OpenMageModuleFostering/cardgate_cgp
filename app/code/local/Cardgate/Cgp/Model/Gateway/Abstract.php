<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
abstract class Cardgate_Cgp_Model_Gateway_Abstract extends Mage_Payment_Model_Method_Abstract
{

	/**
	 * config root (cgp or payment)
	 *
	 * @var string
	 */
	protected $_module = 'cgp';

	/**
	 * payment method code (used for loading settings)
	 *
	 * @var string
	 */
	protected $_code;

	/**
	 * payment model
	 *
	 * @var string
	 */
	protected $_model;

	/**
	 * Paymentgateway URL
	 *
	 * @var string
	 */
	protected $_url = 'https://gateway.cardgateplus.com/';

	/**
	 * supported countries
	 * 
	 * @var array
	 */
	protected $_supportedCurrencies = array( 
			'EUR', 
			'USD', 
			'JPY', 
			'BGN', 
			'CZK', 
			'DKK', 
			'GBP', 
			'HUF', 
			'LTL', 
			'LVL', 
			'PLN', 
			'RON', 
			'SEK', 
			'CHF', 
			'NOK', 
			'HRK', 
			'RUB', 
			'TRY', 
			'AUD', 
			'BRL', 
			'CAD', 
			'CNY', 
			'HKD', 
			'IDR', 
			'ILS', 
			'INR', 
			'KRW', 
			'MXN', 
			'MYR', 
			'NZD', 
			'PHP', 
			'SGD', 
			'THB', 
			'ZAR' 
	);

	/**
	 * Mage_Payment_Model settings
	 *
	 * @var bool
	 */
	protected $_isGateway = true;

	protected $_canAuthorize = true;

	protected $_canCapture = true;

	protected $_canUseInternal = false;

	protected $_canUseCheckout = true;

	protected $_canUseForMultishipping = false;

	/**
	 * Return Gateway Url
	 *
	 * @return string
	 */
	public function getGatewayUrl ()
	{
		if ( ! empty( $_SERVER['CGP_GATEWAY_URL'] ) ) {
			return $_SERVER['CGP_GATEWAY_URL'];
		} else {
			return $this->_url;
		}
	}

	/**
	 * Get plugin version to send to gateway (debugging purposes)
	 *
	 * @return string
	 */
	public function getPluginVersion ()
	{
		return ( string ) Mage::getConfig()->getNode( 'modules/Cardgate_Cgp/version' );
	}

	/**
	 * Get checkout session namespace
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	public function getCheckout ()
	{
		return Mage::getSingleton( 'checkout/session' );
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote ()
	{
		return $this->getCheckout()->getQuote();
	}

	/**
	 * Get current order
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder ()
	{
		$order = Mage::getModel( 'sales/order' );
		$order->loadByIncrementId( $this->getCheckout()
			->getLastRealOrderId() );
		return $order;
	}

	/**
	 * Magento tries to set the order from payment/, instead of cgp/
	 *
	 * @param Mage_Sales_Model_Order $order        	
	 * @return void
	 */
	public function setSortOrder ( $order )
	{
		$this->sort_order = $this->getConfigData( 'sort_order' );
	}

	/**
	 * Append the current model to the URL
	 *
	 * @param string $url        	
	 * @return string
	 */
	function getModelUrl ( $url )
	{
		if ( ! empty( $this->_model ) ) {
			$url .= '/model/' . $this->_model;
		}
		return Mage::getUrl( $url, array( 
				'_secure' => true 
		) );
	}

	/**
	 * Magento will use this for payment redirection
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl ()
	{
		$_SESSION['cgp_formdata'] = $_POST;
		return $this->getModelUrl( 'cgp/standard/redirect' );
	}

	/**
	 * Retrieve config value for store by path
	 *
	 * @param string $path        	
	 * @param mixed $store        	
	 * @return mixed
	 */
	public function getConfigData ( $field, $storeId = null )
	{
		if ( $storeId === null ) {
			$storeId = $this->getStore();
		}
		
		$configSettings = Mage::getStoreConfig( $this->_module . '/settings', $storeId );
		$configGateway = Mage::getStoreConfig( $this->_module . '/' . $this->_code, $storeId );
		$config = array_merge( $configSettings, $configGateway );
		
		return @$config[$field];
	}

	/**
	 * Validate if the currency code is supported by Card Gate Plus
	 *
	 * @return Cardgate_Cgp_Model_Abstract
	 */
	public function validate ()
	{
		parent::validate();
		$base = Mage::getSingleton( 'cgp/base' );
		
		$currency_code = $this->getQuote()->getBaseCurrencyCode();
		if ( empty( $currency_code ) ) {
			$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
		}
		if ( ! in_array( $currency_code, $this->_supportedCurrencies ) ) {
			$base->log( 'Unacceptable currency code (' . $currency_code . ').' );
			Mage::throwException( 
					Mage::helper( 'cgp' )->__( 'Selected currency code ' ) . $currency_code .
							 Mage::helper( 'cgp' )->__( ' is not compatible with Card Gate Plus' ) );
		}
		
		return $this;
	}

	/**
	 * Change order status
	 *
	 * @param Mage_Sales_Model_Order $order        	
	 * @return void
	 */
	protected function initiateTransactionStatus ( $order )
	{
		// Change order status
		$newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$newStatus = $this->getConfigData( 'initialized_status' );
		$statusMessage = Mage::helper( 'cgp' )->__( 'Transaction started, waiting for payment.' );
		if ( $order->getState() != Mage_Sales_Model_Order::STATE_PROCESSING ) {
			$order->setState( $newState, $newStatus, $statusMessage );
			$order->save();
		}
	}

	/**
	 * Generates checkout form fields
	 *
	 * @return array
	 */
	public function getCheckoutFormFields ()
	{
		$base = Mage::getSingleton( 'cgp/base' );
		$extra_data = $_SESSION['cgp_formdata']['payment']['cgp'];
		
		$order = $this->getOrder();
		
		if ( ! $this->getConfigData( 'orderemail_at_payment' ) ) {
			$order->sendNewOrderEmail();
			$order->setEmailSent( true );
		}
		$customer = $order->getBillingAddress();
		
		$s_arr = array();
		$s_arr['language'] = $this->getConfigData( 'lang' );
		
		switch ( $this->_model ) {
			// CreditCards
			case 'visa':
			case 'mastercard':
			case 'americanexpress':
			case 'maestro':
			case 'cartebleue':
			case 'cartebancaire':
			case 'vpay':
				$s_arr['option'] = 'creditcard';
				break;
			
			// DIRECTebanking, Sofortbanking
			case 'sofortbanking':
				$s_arr['option'] = 'directebanking';
				break;
			
			// iDEAL
			case 'ideal':
				$s_arr['option'] = 'ideal';
				$s_arr['suboption'] = $extra_data['ideal_issuer_id'];
				break;
			
			// Giropay
			case 'giropay':
				$s_arr['option'] = 'giropay';
				break;
			
			// Mister Cash
			case 'mistercash':
				$s_arr['option'] = 'mistercash';
				break;
			
			// PayPal
			case 'paypal':
				$s_arr['option'] = 'paypal';
				break;
			
			// Webmoney
			case 'webmoney':
				$s_arr['option'] = 'webmoney';
				break;
			
			// Klarna
			case 'klarna':
				$s_arr['option'] = 'klarna';
				if ( isset( $extra_data['klarna-personal-number'] ) ) {
					$s_arr['dob'] = $extra_data['klarna-personal-number'];
				} else {
					$s_arr['dob'] = $extra_data['klarna-dob_day'] . '-' . $extra_data['klarna-dob_month'] . '-' .
							 $extra_data['klarna-dob_year'];
					$s_arr['gender'] = $extra_data['klarna-gender'];
				}
				$s_arr['language'] = $extra_data['klarna-language'];
				$s_arr['account'] = 0;
				
				$cartitems = array();
				
				foreach ( $order->getAllItems() as $itemId => $item ) {
					// add item
					if ( $item->getQtyToInvoice() > 0 ) {
						$cartitem = array();
						$cartitem['qty'] = $item->getQtyToInvoice();
						$cartitem['id'] = $item->getSku();
						$cartitem['name'] = $item->getName();
						$cartitem['price'] = sprintf( '%01.2f', ( float ) $item->getPriceInclTax() );
						
						$item_tax = ( float ) $item->getData( 'tax_percent' );
						$cartitem['vat'] = $item_tax;
						// $cartitem['discount'] = sprintf( '%01.2f',
						// $item->getData( 'discount_percent' ) );
						if ( $item_tax > 0 ) {
							$cartitem['flags'] = 32;
						} else {
							$cartitem['flags'] = 0;
						}
						$cartitems[] = $cartitem;
					}
				}
				
				if ( $order->getDiscountAmount() < 0 ) {
					$amount = $order->getDiscountAmount();
					$applyAfter = Mage::helper( 'tax' )->applyTaxAfterDiscount( $order->getStoreId() );
					if ( $applyAfter == true ) {
						// With this setting active the discount will not have
						// the correct
						// value. We need to take each respective products rate
						// and calculate
						// a new value.
						$amount = 0;
						foreach ( $order->getAllVisibleItems() as $product ) {
							$rate = $product->getTaxPercent();
							$newAmount = $product->getDiscountAmount() * ( ( $rate / 100 ) + 1 );
							$amount -= $newAmount;
						}
						// If the discount also extends to shipping
						$shippingDiscount = $order->getShippingDiscountAmount();
						if ( $shippingDiscount ) {
							$taxClass = Mage::getStoreConfig( 'tax/classes/shipping_tax_class' );
							$rate = $this->getTaxRate( $taxClass );
							$newAmount = $shippingDiscount * ( ( $rate / 100 ) + 1 );
							$amount -= $newAmount;
						}
					}
					
					$discount = array();
					$discount['qty'] = 1;
					$discount['id'] = '';
					$discount['name'] = 'Discount total';
					$discount['price'] = sprintf( '%01.2f', round( $amount, 2 ) );
					$discount['vat'] = 0;
					$discount['flags'] = 32;
					
					$cartitems[] = $discount;
				}
				
				$settings = Mage::getStoreConfig( 'cgp/cgp_klarna' );
				
				// add shipping
				if ( $order->getShippingAmount() > 0 ) {
					
					$tax_info = $order->getFullTaxInfo();
					
					$flags = 8;
					if ( ! isset( $tax_info[0]['percent'] ) ) {
						$tax_rate = 0;
					} else {
						$tax_rate = $tax_info[0]['percent'];
						$flags += 32;
					}

					$price = round( $order->getShippingAmount() * ( 1 + $tax_rate / 100 ), 2 );
					$shipping = array();
					$shipping['qty'] = 1;
					$shipping['id'] = '';
					$shipping['name'] = 'Shipping fee';
					$shipping['price'] = sprintf( '%01.2f', $price );
					
					$shipping['flags'] = $flags;
					$shipping['vat'] = $tax_rate;
					
					$cartitems[] = $shipping;
				}
				
				// add invoice fee
				if ( $order->getPayment()->getAdditionalInformation('invoice_fee') > 0 ) {
					$fee = array();
					$fee['qty'] = 1;
					$fee['id'] = '';
					$fee['name'] = 'Invoice fee';
					$fee['price'] = sprintf( '%01.2f', $order->getPayment()->getAdditionalInformation('invoice_fee') );
					$fee['vat'] = $order->getPayment()->getAdditionalInformation('invoice_fee_rate');
					$fee['flags'] = ( $fee['vat'] > 0 ) ? 32 : 0 ;
					$cartitems[] = $fee;
				}
				
				$s_arr['cartitems'] = serialize( $cartitems );

				//var_dump($order->getPayment()->getAdditionalInformation(), $cartitems); die();
				break;
			
			// Klarna
			case 'klarnaaccount':
				$s_arr['option'] = 'klarna';
				
				if ( isset( $extra_data['klarna-account-personal-number'] ) ) {
					$s_arr['dob'] = $extra_data['klarna-account-personal-number'];
				} else {
					$s_arr['dob'] = $extra_data['klarna-account-dob_day'] . '-' . $extra_data['klarna-account-dob_month'] .
							 '-' . $extra_data['klarna-account-dob_year'];
					$s_arr['gender'] = $extra_data['klarna-account-gender'];
				}
				$s_arr['language'] = $extra_data['klarna-account-language'];
				$s_arr['account'] = 1;
				
				$cartitems = array();
				foreach ( $order->getAllItems() as $itemId => $item ) {
					if ( $item->getQtyToInvoice() > 0 ) {
						$cartitem = array();
						$cartitem['qty'] = $item->getQtyToInvoice();
						$cartitem['id'] = $item->getSku();
						$cartitem['name'] = $item->getName();
						
						$cartitem['price'] = sprintf( '%01.2f', ( float ) $item->getPriceInclTax() );
						
						$item_tax = ( float ) $item->getData( 'tax_percent' );
						$cartitem['vat'] = $item_tax;
						if ( $item_tax > 0 ) {
							$cartitem['flags'] = 32;
						} else {
							$cartitem['flags'] = 0;
						}
						$cartitems[] = $cartitem;
					}
				}
				
				if ( $order->getDiscountAmount() < 0 ) {
					$amount = $order->getDiscountAmount();
					$applyAfter = Mage::helper( 'tax' )->applyTaxAfterDiscount( $order->getStoreId() );
					if ( $applyAfter == true ) {
						// With this setting active the discount will not have
						// the correct
						// value. We need to take each respective products rate
						// and calculate
						// a new value.
						$amount = 0;
						foreach ( $order->getAllVisibleItems() as $product ) {
							$rate = $product->getTaxPercent();
							$newAmount = $product->getDiscountAmount() * ( ( $rate / 100 ) + 1 );
							$amount -= $newAmount;
						}
						// If the discount also extends to shipping
						$shippingDiscount = $order->getShippingDiscountAmount();
						if ( $shippingDiscount ) {
							$taxClass = Mage::getStoreConfig( 'tax/classes/shipping_tax_class' );
							$rate = $this->getTaxRate( $taxClass );
							$newAmount = $shippingDiscount * ( ( $rate / 100 ) + 1 );
							$amount -= $newAmount;
						}
					}
					
					$discount = array();
					$discount['qty'] = 1;
					$discount['id'] = '';
					$discount['name'] = 'Discount total';
					$discount['price'] = sprintf( '%01.2f', round( $amount, 2 ) );
					$discount['vat'] = 0;
					$discount['flags'] = 32;
					
					$cartitems[] = $discount;
				}
				
				// add shipping
				if ( $order->getShippingAmount() > 0 ) {
					
					$tax_info = $order->getFullTaxInfo();
					
					$flags = 8;
					if ( ! isset( $tax_info[0]['percent'] ) ) {
						$tax_rate = 0;
					} else {
						$tax_rate = $tax_info[0]['percent'];
						$flags += 32;
					}

					$price = round( $order->getShippingAmount() * ( 1 + $tax_rate / 100 ), 2 );
					$shipping = array();
					$shipping['qty'] = 1;
					$shipping['id'] = '';
					$shipping['name'] = 'Shipping fee';
					$shipping['price'] = sprintf( '%01.2f', $price );
					
					$shipping['flags'] = $flags;
					$shipping['vat'] = $tax_rate;
					
					$cartitems[] = $shipping;
				}
				
				// add invoice fee
				if ( $order->getPayment()->getAdditionalInformation('invoice_fee') > 0 ) {
					$fee = array();
					$fee['qty'] = 1;
					$fee['id'] = '';
					$fee['name'] = 'Invoice fee';
					$fee['price'] = sprintf( '%01.2f', $order->getPayment()->getAdditionalInformation('invoice_fee') );
					$fee['vat'] = $order->getPayment()->getAdditionalInformation('invoice_fee_rate');
					$fee['flags'] = ( $fee['vat'] > 0 ) ? 32 : 0 ;
					$cartitems[] = $fee;
				}
				
				$s_arr['cartitems'] = serialize( $cartitems );
				break;
			
			// Banktransfer
			case 'banktransfer':
				$s_arr['option'] = 'banktransfer';
				break;
			
			// Directdebit
			case 'directdebit':
				$s_arr['option'] = 'directdebit';
				break;
			
			// Przelewy24
			case 'przelewy24':
				$s_arr['option'] = 'przelewy24';
				break;
			
			// Default
			default:
				$s_arr['option'] = '';
				$s_arr['suboption'] = '';
				break;
		}
		
		// Add new state
		$this->initiateTransactionStatus( $order );
		
		$s_arr['siteid'] = $this->getConfigData( 'site_id' );
		$s_arr['ref'] = $order->getIncrementId();
		$s_arr['first_name'] = $customer->getFirstname();
		$s_arr['last_name'] = $customer->getLastname();
		$s_arr['email'] = $order->getCustomerEmail();
		$s_arr['address'] = $customer->getStreet( 1 ) .
				 ( $customer->getStreet( 2 ) ? ', ' . $customer->getStreet( 2 ) : '' );
		$s_arr['city'] = $customer->getCity();
		$s_arr['country_code'] = $customer->getCountry();
		$s_arr['postal_code'] = $customer->getPostcode();
		$s_arr['phone_number'] = $customer->getTelephone();
		$s_arr['state'] = $customer->getRegionCode();
		
		if ( $this->getConfigData( 'use_backoffice_urls' ) == false ) {
			$s_arr['return_url'] = Mage::getUrl( 'cgp/standard/success/', array( 
					'_secure' => true 
			) );
			$s_arr['return_url_failed'] = Mage::getUrl( 'cgp/standard/cancel/', array( 
					'_secure' => true 
			) );
		}
		
		$s_arr['shop_version'] = 'Magento ' . Mage::getVersion();
		$s_arr['plugin_name'] = 'Cardgate_Cgp';
		$s_arr['plugin_version'] = $this->getPluginVersion();
		$s_arr['extra'] = $this->getCheckout()->getCardgateQuoteId();
		
		if ( $base->isTest() ) {
			$s_arr['test'] = '1';
			$hash_prefix = 'TEST';
		} else {
			$hash_prefix = '';
		}
		
		$s_arr['amount'] = sprintf( '%.0f', $order->getGrandTotal() * 100 );
		$s_arr['currency'] = $order->getOrderCurrencyCode();
		$s_arr['description'] = str_replace( '%id%', $order->getIncrementId(), 
				$this->getConfigData( 'order_description' ) );
		$s_arr['hash'] = md5( 
				$hash_prefix . $this->getConfigData( 'site_id' ) . $s_arr['amount'] . $s_arr['ref'] .
						 $this->getConfigData( 'hash_key' ) );
		
		// Logging
		$base->log( 'Initiating a new transaction' );
		$base->log( 'Sending customer to Card Gate Plus with values:' );
		$base->log( 'URL = ' . $this->getGatewayUrl() );
		$base->log( $s_arr );
		
		$locale = Mage::app()->getLocale()->getLocaleCode();
		return $s_arr;
	}
}