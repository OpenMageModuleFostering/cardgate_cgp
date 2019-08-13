<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
abstract class Cardgate_Cgp_Model_Gateway_Abstract extends Mage_Payment_Model_Method_Abstract {

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
	 * @var string $_url
	 */
	protected $_url = 'https://secure.curopayments.net/gateway/cardgate/';

	/**
	 *
	 * @var string $_urlStaging
	 */
	protected $_urlStaging = 'https://secure-staging.curopayments.net/gateway/cardgate/';

	/**
	 *
	 * @var string $_apiUrl
	 */
	protected $_apiUrl = 'https://secure.curopayments.net/rest/v1/';

	/**
	 *
	 * @var string $_apiUrlStaging
	 */
	protected $_apiUrlStaging = 'https://secure-staging.curopayments.net/rest/v1/';

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

	protected $_canUseInternal = true;

	protected $_canUseCheckout = true;

	protected $_canUseForMultishipping = false;

	protected $_canRefund = true;

	protected $_canRefundInvoicePartial = true;

	protected $_canVoid = true;

	protected $_canCapturePartial = true;

	protected $_canCompleteByMerchant = false; // customers need to complete transactions

	protected $_infoBlockType = 'cgp/info_payment';

	public function __construct()
	{
		/**
		 * @var Cardgate_Cgp_Model_Base $base
		 */
		$base = Mage::getSingleton( 'cgp/base' );
		if ( ! $base->isRESTCapable() ) {
			$this->_canRefund = false;
			$this->_canRefundInvoicePartial = false;
		}
	}

	/**
	 * Return Gateway Url
	 *
	 * @return string
	 */
	public function getGatewayUrl () {
		if ( ! empty( $_SERVER['CGP_GATEWAY_URL'] ) ) {
			return $_SERVER['CGP_GATEWAY_URL'];
		} else {
			$base = Mage::getSingleton( 'cgp/base' );
			return $base->isTest() ? $this->_urlStaging : $this->_url;
		}
	}

	/**
	 * Return API Url
	 *
	 * @return string
	 */
	public function getAPIUrl () {
		if ( ! empty( $_SERVER['CGP_API_URL'] ) ) {
			return $_SERVER['CGP_API_URL'];
		} else {
			$base = Mage::getSingleton( 'cgp/base' );
			return $base->isTest() ? $this->_apiUrlStaging : $this->_apiUrl;
		}
	}

	/**
	 * Get plugin version to send to gateway (debugging purposes)
	 *
	 * @return string
	 */
	public function getPluginVersion () {
		return ( string ) Mage::getConfig()->getNode( 'modules/Cardgate_Cgp/version' );
	}

	/**
	 * Get checkout session namespace
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	public function getCheckout () {
		return Mage::getSingleton( 'checkout/session' );
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote () {
		return $this->getCheckout()->getQuote();
	}

	/**
	 * Get current order
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder () {
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
	public function setSortOrder ( $order ) {
		$this->sort_order = $this->getConfigData( 'sort_order' );
	}

	/**
	 * Append the current model to the URL
	 *
	 * @param string $url
	 * @return string
	 */
	function getModelUrl ( $url ) {
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
	public function getOrderPlaceRedirectUrl () {
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
	public function getConfigData ( $field, $storeId = null ) {
		if ( $storeId === null ) {
			$storeId = $this->getStore();
		}

		$configSettings = Mage::getStoreConfig( $this->_module . '/settings', $storeId );
		if ( ! is_array( $configSettings ) )
			$configSettings = array();
		$configGateway = Mage::getStoreConfig( $this->_module . '/' . $this->_code, $storeId );
		if ( ! is_array( $configGateway ) )
			$configGateway = array();
		$config = array_merge( $configSettings, $configGateway );

		return @$config[$field];
	}

	/**
	 * Validate if the currency code is supported by Card Gate Plus
	 *
	 * @return Cardgate_Cgp_Model_Abstract
	 */
	public function validate () {
		parent::validate();
		$base = Mage::getSingleton( 'cgp/base' );

		$currency_code = $this->getQuote()->getBaseCurrencyCode();
		if ( empty( $currency_code ) ) {
			$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
		}
		if ( ! in_array( $currency_code, $this->_supportedCurrencies ) ) {
			$base->log( 'Unacceptable currency code (' . $currency_code . ').' );
			Mage::throwException( Mage::helper( 'cgp' )->__( 'Selected currency code "%s" is not compatible with CardGate', $currency_code ) );
		}

		return $this;
	}

	/**
	 * Change order status
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	protected function initiateTransactionStatus ( $order ) {
		// Change order status
		$newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$newStatus = $this->getConfigData( 'initialized_status' );
		$statusMessage = Mage::helper( 'cgp' )->__( 'Transaction started, waiting for payment.' );
		$statusMessage .= "<br/>\n" . Mage::helper( 'cgp' )->__( 'Paymentmethod used' ) . ' : ' . $order->getPayment()->getMethod();
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
	public function getCheckoutFormFields () {
		$extra_data = $_SESSION['cgp_formdata']['payment']['cgp'];
		$order = $this->getOrder();

		try {
			$order->getPayment()->setAdditionalInformation('cardgate_redirected',time());
			$order->save();
		} catch (Exception $e) {
			/* ignore */
		}

		return $this->getRegisterFields($order, $extra_data);
	}

	/**
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @param array $extra_data
	 * @return string[]|number[]|unknown[]|NULL[]|mixed[]
	 */
	public function getRegisterFields($order, $extra_data = array()) {

		$base = Mage::getSingleton( 'cgp/base' );
		if ( ! $this->getConfigData( 'orderemail_at_payment' ) ) {
			$order->sendNewOrderEmail();
			$order->setEmailSent( true );
		}
		$customer = $order->getBillingAddress();
		$ship_customer = $order->getShippingAddress();

		$s_arr = array();
		$s_arr['language'] = $this->getConfigData( 'lang' );

		$cartitems = array();

		foreach ( $order->getAllItems() as $itemId => $item ) {
			if ( $item->getQtyToInvoice() > 0 ) {
				$aAdditionalCartData = array();
				$stockItem = Mage::getModel( 'cataloginventory/stock_item' )->loadByProduct( $item->getProductId() );
				if ( $stockItem->getUseConfigManageStock() ) {
					$aAdditionalCartData['stock'] = max( floatval( $stockItem->getQty() ), 0 );
				}
				$cartitems[] = array_merge( array(
					'quantity' => $item->getQtyToInvoice(),
					'sku' => $item->getSku(),
					'name' => $item->getName(),
					'price' => sprintf( '%01.2f', ( float ) $item->getPriceInclTax() ),
					'vat_amount' => sprintf( '%01.2f', ( float ) $item->getTaxAmount() / $item->getQtyToInvoice() ),
					'vat' => ( float ) $item->getData( 'tax_percent' ),
					'vat_inc' => 1,
					'type' => 1
				), $aAdditionalCartData );
			}
		}

		if ( $order->getDiscountAmount() < 0 ) {
			$amount = $order->getDiscountAmount();
			$applyAfter = Mage::helper( 'tax' )->applyTaxAfterDiscount( $order->getStoreId() );
			$priceIncludesTax = Mage::helper( 'tax' )->priceIncludesTax( $order->getStoreId() );

			if ( $applyAfter == true && $priceIncludesTax == false ) {
				// With this setting active the discount will not have the
				// correct value.
				// We need to take each respective products rate and calculate a
				// new value.
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

			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-discount',
				'name' => 'Discount',
				'price' => sprintf( '%01.2f', round( $amount, 2 ) ),
				'vat_amount' => 0,
				'vat' => 0,
				'vat_inc' => 1,
				'type' => 4
			);
		}

		$tax_info = $order->getFullTaxInfo();
		// add shipping
		if ( $order->getShippingAmount() > 0 ) {

			$flags = 8;
			if ( ! isset( $tax_info[0]['percent'] ) ) {
				$tax_rate = 0;
			} else {
				$tax_rate = $tax_info[0]['percent'];
				$flags += 32;
			}
			$tax_rate = ( isset( $tax_info[0]['percent'] ) ? $tax_info[0]['percent'] : 0 );
			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-shipping',
				'name' => 'Shipping fee',
				'price' => sprintf( '%01.2f', $order->getShippingInclTax() ),
				'vat_amount' => sprintf( '%01.2f', $order->getShippingTaxAmount() ),
				'vat' => $tax_rate,
				'vat_inc' => 1,
				'type' => 2
			);
		}

		// add invoice fee
		if ( $order->getPayment()->getAdditionalInformation( 'invoice_fee' ) > 0 ) {

			$tax_rate = $order->getPayment()->getAdditionalInformation( 'invoice_fee_rate' );
			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-invoice',
				'name' => 'Invoice fee',
				'price' => sprintf( '%01.2f', $order->getPayment()->getAdditionalInformation( 'invoice_fee' ) ),
				'vat_amount' => ( isset( $tax_info[0]['percent'] ) ? round( $order->getPayment()->getAdditionalInformation( 'invoice_fee' ) * ( $tax_rate / 100 ), 2 ) : 0 ),
				'vat' => $tax_rate,
				'vat_inc' => 1,
				'type' => 5
			);
		}

		// add Magestore affiliateplus discount
		if ( ! is_null( $order->getAffiliateplusDiscount() ) && $order->getAffiliateplusDiscount() != 0 ) {
			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-affdiscount',
				'name' => 'Discount',
				'price' => sprintf( '%01.2f', $order->getAffiliateplusDiscount() ),
				'vat_amount' => 0,
				'vat' => 0,
				'vat_inc' => 1,
				'type' => 4
			);
		}

		// add ET Payment Extra Charge
		if ( ! is_null( $order->getEtPaymentExtraCharge() ) && $order->getEtPaymentExtraCharge() != 0 ) {
			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-paymentcharge',
				'name' => 'ET Payment fee',
				'price' => sprintf( '%01.2f', $order->getEtPaymentExtraCharge() ),
				'vat_amount' => sprintf( '%01.2f', $order->getEtPaymentExtraCharge() - $order->getEtPaymentExtraChargeExcludingTax() ),
				// 'vat' => 0,
				'vat_inc' => 1,
				'type' => 5
			);
		}

		// failsafe
		$cartpricetotal = $cartvattotal = 0;
		foreach ( $cartitems as $cartitem ) {
			$cartpricetotal += ceil( ( $cartitem['price'] * $cartitem['quantity'] ) * 100 );
			$cartvattotal += ceil( ( $cartitem['vat_amount'] * $cartitem['quantity'] ) * 100 );
		}
		//$cartvattotal-=1;
		if ( $cartvattotal != ceil( $order->getTaxAmount() * 100 ) ) {
			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-vatcorrection',
				'name' => 'VAT Correction',
				'price' => sprintf( '%01.2f', ( ceil( $order->getTaxAmount() * 100 ) / 100 ) - ( $cartvattotal / 100 ) ),
				'vat_amount' => sprintf( '%01.2f', ( ceil( $order->getTaxAmount() * 100 ) / 100 ) - ( $cartvattotal / 100 ) ),
				'vat_inc' => 1,
				'vat' => 100,
				'type' => 7
			);
			$cartpricetotal += ceil( $order->getTaxAmount() * 100 ) - $cartvattotal;
		}
		if ( $cartpricetotal != ceil( $order->getGrandTotal() * 100 )) {
			$iCorrectionAmount = ( ceil( $order->getGrandTotal() * 100 ) / 100 ) - ( $cartpricetotal / 100 );
			$cartitems[] = array(
				'quantity' => '1',
				'sku' => 'cg-correction',
				'name' => 'Correction',
				'price' => sprintf( '%01.2f', $iCorrectionAmount ),
				'vat_amount' => 0,
				'vat' => 0,
				'vat_inc' => 1,
				'type' => ( $iCorrectionAmount > 0 ) ? 1 : 4
			);
		}

		$s_arr['cartitems'] = serialize( $cartitems );

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

			// Mister Cash
			case 'mistercash':
				$s_arr['option'] = 'bancontact';
				break;

			/*// Giropay
			case 'giropay':
				$s_arr['option'] = 'giropay';
				break;

			// PayPal
			case 'paypal':
				$s_arr['option'] = 'paypal';
				break;

			// Webmoney
			case 'webmoney':
				$s_arr['option'] = 'webmoney';
				break;*/

			// Klarna
			case 'klarna':
				$s_arr['option'] = 'klarna';
				if ( isset( $extra_data['klarna-personal-number'] ) ) {
					$s_arr['dob'] = $extra_data['klarna-personal-number'];
				} else {
					$s_arr['dob'] = $extra_data['klarna-dob_day'] . '-' . $extra_data['klarna-dob_month'] . '-' . $extra_data['klarna-dob_year'];
					$s_arr['gender'] = $extra_data['klarna-gender'];
				}
				$s_arr['language'] = $extra_data['klarna-language'];
				$s_arr['account'] = 0;

				break;

			// Klarna
			case 'klarnaaccount':
				$s_arr['option'] = 'klarna';

				if ( isset( $extra_data['klarna-account-personal-number'] ) ) {
					$s_arr['dob'] = $extra_data['klarna-account-personal-number'];
				} else {
					$s_arr['dob'] = $extra_data['klarna-account-dob_day'] . '-' . $extra_data['klarna-account-dob_month'] . '-' . $extra_data['klarna-account-dob_year'];
					$s_arr['gender'] = $extra_data['klarna-account-gender'];
				}
				$s_arr['language'] = $extra_data['klarna-account-language'];
				$s_arr['account'] = 1;

				break;

			/*// Banktransfer
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

			// Afterpay
			case 'afterpay':
				$s_arr['option'] = 'afterpay';
				break;

			// Bitcoin
			case 'bitcoin':
				$s_arr['option'] = 'bitcoin';
				break;

			// POS (offline PM)
			case 'pos':
				$s_arr['option'] = 'pos';
				break;

			// paysafecard
			case 'paysafecard':
				$s_arr['option'] = 'paysafecard';
				break;*/

			// Default
			default:
				$s_arr['option'] = $this->_model;
				$s_arr['suboption'] = '';
				break;
		}

		// Add new state
		$this->initiateTransactionStatus( $order );

		$s_arr['siteid'] = $this->getConfigData( 'site_id' );
		$s_arr['ref'] = $order->getIncrementId();

		$s_arr['first_name'] = $customer->getFirstname();
		$s_arr['last_name'] = $customer->getLastname();
		$s_arr['company_name'] = $customer->getCompany();
		$s_arr['email'] = $order->getCustomerEmail();
		$s_arr['address'] = $customer->getStreet( 1 ) . ( $customer->getStreet( 2 ) ? ', ' . $customer->getStreet( 2 ) : '' );
		$s_arr['city'] = $customer->getCity();
		$s_arr['country_code'] = $customer->getCountry();
		$s_arr['postal_code'] = $customer->getPostcode();
		$s_arr['phone_number'] = $customer->getTelephone();
		$s_arr['state'] = $customer->getRegionCode();

		// CURO protocol... because..
		$s_arr['shipto_firstname'] = $ship_customer->getFirstname();
		$s_arr['shipto_lastname'] = $ship_customer->getLastname();
		$s_arr['shipto_company'] = $ship_customer->getCompany();
		$s_arr['shipto_email'] = $ship_customer->getEmail();
		$s_arr['shipto_address'] = $ship_customer->getStreet( 1 ) . ( $ship_customer->getStreet( 2 ) ? ', ' . $ship_customer->getStreet( 2 ) : '' );
		$s_arr['shipto_city'] = $ship_customer->getCity();
		$s_arr['shipto_country_id'] = $ship_customer->getCountry();
		$s_arr['shipto_zipcode'] = $ship_customer->getPostcode();
		$s_arr['shipto_phone'] = $ship_customer->getTelephone();
		$s_arr['shipto_state'] = $ship_customer->getRegionCode();

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
		$s_arr['extra'] = $order->getQuoteId();

		if ( $base->isTest() ) {
			//$s_arr['test'] = '1';
			$hash_prefix = 'TEST';
		} else {
			$hash_prefix = '';
		}

		$s_arr['amount'] = sprintf( '%.0f', ceil( $order->getGrandTotal() * 100 ) );
		$s_arr['currency'] = $order->getOrderCurrencyCode();
		$s_arr['description'] = str_replace( '%id%', $order->getIncrementId(), $this->getConfigData( 'order_description' ) );
		$s_arr['hash'] = md5( $hash_prefix . $this->getConfigData( 'site_id' ) . $s_arr['amount'] . $s_arr['ref'] . $this->getConfigData( 'hash_key' ) );

		// Logging
		$base->log( 'Initiating a new transaction' );
		$base->log( 'Sending customer to Card Gate Plus with values:' );
		$base->log( 'URL = ' . $this->getGatewayUrl() );
		$base->log( $s_arr );

		$locale = Mage::app()->getLocale()->getLocaleCode();
		return $s_arr;
	}

	protected function _getParentTransactionId ( Varien_Object $payment ) {
		return $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();
	}

	/**
	 * Do RESTful API call
	 * @param string $entrypoint
	 * @param string $calldata
	 * @return Zend_Http_Response
	 * @throws Zend_Http_Client_Exception
	 */
	public function doApiCall( $entrypoint, $calldata = array() ) {
		$config = array(
			'maxredirects' => 5,
			'timeout' => 30,
			'verifypeer' => 0
		);

		$data = array();
		$data['shop_version'] = 'Magento ' . Mage::getVersion();
		$data['plugin_name'] = 'Cardgate_Cgp';
		$data['plugin_version'] = $this->getPluginVersion();
		$data = array_merge( $data, $calldata );

		$APIid = $this->getConfigData( 'api_id' );
		$APIkey = $this->getConfigData( 'api_key' );
		$headers = array(
			'Accept: application/json'
		);

		$client = new Varien_Http_Client();
		$client->setUri( $this->getAPIUrl() . $entrypoint )
			->setAuth( $APIid, $APIkey, Varien_Http_Client::AUTH_BASIC )
			->setConfig( $config )
			->setMethod( Zend_Http_Client::POST )
			->setHeaders( $headers )
			->setRawData( json_encode( $data ) );
		$response = $client->request();
		$responsecode = $response->getStatus();
		$responsebody = $response->getBody();
		$result = json_decode( $responsebody, true );
		return array( 'code'=>$responsecode, 'result'=>$result, 'body'=>$responsebody );
	}

	public function register ( $order, $emptyMethod = false ) {
		/**
		 *
		 * @var Cardgate_Cgp_Model_Base $base
		 */
		$base = Mage::getModel( 'cgp/base' );
		$registerdata = $this->getRegisterFields($order);
		$registerdata['ip'] = '0.0.0.0'; // unknown at this point

		$apiresult = array();
		try {
			if ( $emptyMethod ) {
				unset ( $registerdata['option'] );
			}
			$apiresult = $this->doApiCall( ( $registerdata['option'] ? $registerdata['option'].'/' : '' ) . 'payment', $registerdata );
			$result = $apiresult['result'];
		} catch ( Exception $e ) {
			$base->log( 'Refund failed! ' . $e->getCode() . '/' . $e->getMessage() );
			Mage::throwException( Mage::helper( 'cgp' )->__( 'CardGate register for order %s failed. Reason: %s', $order->getId(), $e->getCode() . '/' . $e->getMessage() ) );
		}
		return $apiresult;
	}

	/**
	 * Refund a capture transaction
	 *
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @param float $amount
	 */
	public function refund ( Varien_Object $payment, $amount ) {

		/**
		 *
		 * @var Cardgate_Cgp_Model_Base $base
		 */
		$base = Mage::getModel( 'cgp/base' );

		$trxid = $this->_getParentTransactionId( $payment );
		$base->log( "CG REFUND " . $trxid . " -- " . $amount );
		if ( $trxid ) {
			if ( $base->isLocked( $trxid ) ) {
				$base->log( "Transaction {$trxid} is locked, can't refund now. Aborting." );
				Mage::throwException( "Transaction {$trxid} is locked, can't refund now. Aborting." );
			}

			/**
			 *
			 * @var Mage_Sales_Model_Order $order
			 */
			$order = $payment->getOrder();
			$currencycode = $order->getOrderCurrencyCode();

			if ( ! in_array( $currencycode, $this->_supportedCurrencies ) ) {
				$base->log( 'Unacceptable currency code (' . $currencycode . ').' );
				Mage::throwException( Mage::helper( 'cgp' )->__( 'Selected currency code "%s" is not compatible with CardGate', $currencycode ) );
			}

			$apiresult = array();
			try {
				$apiresult = $this->doApiCall('refund', array(
					'refund' => array(
						'site_id' => $this->getConfigData( 'site_id' ),
						'transaction_id' => $trxid,
						'amount' => sprintf( '%.0f', ceil( $amount * 100 ) ),
						'currency' => $currencycode
					)

				));
				$result = $apiresult['result'];
			} catch ( Exception $e ) {
				$base->log( 'Refund failed! ' . $e->getCode() . '/' . $e->getMessage() );
				Mage::throwException( Mage::helper( 'cgp' )->__( 'CardGate refund for Transaction %s failed. Reason: %s', $trxid, $e->getCode() . '/' . $e->getMessage() ) );
			}

			$base->log( 'CardGate refund request for ' . $trxid . ' amount: ' . $currencycode . ' ' . $amount . '. Response: ' . $apiresult['body'] );

			if ( $apiresult['code'] < 200 || $apiresult['code'] > 299 || ! is_array( $result ) || isset( $result['error'] ) ) {
				$base->log( 'CardGate refund for Transaction ' . $trxid . ' declined. Got: ' . $apiresult['body'] );
				Mage::throwException( Mage::helper( 'cgp' )->__( 'CardGate refund for Transaction %s declined. Reason: %s', $trxid, ( isset( $result['error']['message'] ) ? $result['error']['message'] : "({$apiresult['code']}) {$apiresult['body']}" ) ) );
			} else {
				/**
				 *
				 * @var Mage_Sales_Model_Order_Payment $refundpayment
				 */
				$refundpayment = Mage::getModel( 'sales/order_payment' )->setMethod( $this->_code )
					->setTransactionId( $result['refund']['transaction_id'] )
					->setIsTransactionClosed( true );

				$order->setPayment( $refundpayment );
				$refundpayment->addTransaction( Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, null, false, "CardGate refund {$result['refund']['transaction_id']}" );

				$order->save();

				if ( $result['refund']['action'] == 'redirect' ) {
					$order->addStatusHistoryComment( Mage::helper( 'cgp' )->__( "Action required for Cardgate refund <b>Order # %s</b> <b>(transaction # %s)</b>. <a href='%s' target='_blank'>Click here</a>", $order->getIncrementId(), $result['refund']['transaction_id'], $result['refund']['url'] ) );

					$storeId = $order->getStore()->getId();
					$ident = Mage::getStoreConfig( 'cgp/settings/notification_email' );
					$sender_email = Mage::getStoreConfig( 'trans_email/ident_general/email', $storeId );
					$sender_name = Mage::getStoreConfig( 'trans_email/ident_general/name', $storeId );
					$recipient_email = Mage::getStoreConfig( 'trans_email/ident_' . $ident . '/email', $storeId );
					$recipient_name = Mage::getStoreConfig( 'trans_email/ident_' . $ident . '/name', $storeId );

					$mail = new Zend_Mail();
					$mail->setFrom( $sender_email, $sender_name );
					$mail->addTo( $recipient_email, $recipient_name );
					$mail->setSubject( Mage::helper( "cgp" )->__( 'Cardgate refund action required' ) );
					$mail->setBodyText(
							Mage::helper( "cgp" )->__( 'Action required for Cardgate refund Order # %s (transaction # %s). See URL %s', $order->getIncrementId(), $result['refund']['transaction_id'], $result['refund']['url'] ) );
					$mail->setBodyHtml(
							Mage::helper( "cgp" )->__(
									"Action required for Cardgate refund <b>Order # %s</b> <b>(transaction # %s)</b>. <a href='%s' target='_blank'>Click here</a>",
									$order->getIncrementId(), $result['refund']['transaction_id'], $result['refund']['url'] ) );
					$mail->send();

					Mage::getSingleton( 'core/session' )->addWarning( Mage::helper( 'cgp' )->__( "Action required for Cardgate refund <b>Order # %s</b> <b>(transaction # %s)</b>. <a href='%s' target='_blank'>Click here</a>", $order->getIncrementId(), $result['refund']['transaction_id'], $result['refund']['url'] ) );
				}

			}
		} else {
			$base->log( 'CardGate refund failed because transaction could not be found' );
			Mage::throwException( sprintf( Mage::helper( 'cgp' )->__( 'CardGate refund failed because transaction could not be found' ) ) );
		}

		return $this;
	}

}
