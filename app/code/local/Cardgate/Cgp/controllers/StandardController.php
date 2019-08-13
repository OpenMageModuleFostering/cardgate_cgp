<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_StandardController extends Mage_Core_Controller_Front_Action {

	private $_gatewayModel;

	/**
	 * Verify the callback
	 *
	 * @param array $data
	 * @return boolean
	 */
	protected function validate ( $data ) {
		$base = Mage::getSingleton( 'cgp/base' );

		$hashString = ( ( $data['is_test'] || $data['testmode'] ) ? 'TEST' : '' ) . $data['transaction_id'] . $data['currency'] . $data['amount'] . $data['ref'] . $data['status'] . $base->getConfigData( 'hash_key' );

		if ( md5( $hashString ) == $data['hash'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if within the URL is param model
	 * if not, return default gateway model
	 *
	 * @return string
	 */
	protected function getGatewayModel () {
		if ( $this->_gatewayModel ) {
			return $this->_gatewayModel;
		}

		$model = $this->getRequest()->getParam( 'model' );
		$model = preg_replace( '/[^[[:alnum:]]]+/', '', $model );

		if ( ! empty( $model ) ) {
			return 'gateway_' . $model;
		} else {
			return 'gateway_default';
		}
	}

	/**
	 * Redirect customer to the gateway using his prefered payment method
	 */
	public function redirectAction () {
		$paymentModel = 'cgp/' . $this->getGatewayModel();
		Mage::register( 'cgp_model', $paymentModel );

		$session = Mage::getSingleton( 'checkout/session' );
		$session->setCardgateQuoteId( $session->getQuoteId() );

		$this->loadLayout();
		$block = $this->getLayout()->createBlock( 'Cardgate_Cgp_Block_Redirect' );

		$this->getLayout()
			->getBlock( 'content' )
			->append( $block );
		$this->renderLayout();
	}

	/**
	 * After a failed transaction a customer will be send here
	 */
	public function cancelAction () {
		$message = $this->__( 'Your payment has failed. If you wish, you can try using a different payment method or try again.' );
		Mage::getSingleton( 'core/session' )->addError( $message );

		$base = Mage::getSingleton( 'cgp/base' );
		$session = Mage::getSingleton( 'checkout/session' );
		/*
		 * $order_id = $session->getLastRealOrderId();
		 * if ( $order_id ) {
		 * // if order has failed it is canceled via the control url and should
		 * not be canceled a second time
		 * $order = Mage::getSingleton( 'sales/order' )->loadByIncrementId(
		 * $order_id );
		 *
		 * if ( $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED ) {
		 * $order->setState( $base->getConfigData( 'order_status_failed' ) );
		 * $order->cancel();
		 * $order->save();
		 * }
		 * }
		 *
		 * if ( $session->getCgpOnestepCheckout() == true ) {
		 * $quote = Mage::getModel( 'sales/quote' )->load(
		 * $session->getCgpOnestepQuoteId() );
		 * } else {
		 * $quote = Mage::getModel( 'sales/quote' )->load(
		 * $session->getCardgateQuoteId() );
		 * }
		 */
		$quote = Mage::getModel( 'sales/quote' )->load( $session->getCardgateQuoteId() );

		if ( $quote->getId() ) {
			$quote->setIsActive( true );
			if ( $quote->getReservedOrderId() ) {
				$quote->setOrigOrderId( $quote->getReservedOrderId() );
				$quote->setReservedOrderId();
			}
			$quote->save();
		}

		// clear session flag so that it will redirect to the gateway, and not
		// to cancel
		// $session->setCgpOnestepCheckout(false);
		$this->_redirect( 'checkout/cart' );
	}

	/**
	 * After a successful transaction a customer will be send here
	 */
	public function successAction () {
		$session = Mage::getSingleton( 'checkout/session' );
		$quote = Mage::getModel( 'sales/quote' )->load( $session->getCardgateQuoteId() );
		if ( $quote->getId() ) {
			$quote->setIsActive( false );
			$quote->delete();
		} else {
			/**
			 *
			 * @var Mage_Checkout_Model_Session $session
			 */
			$session = Mage::getSingleton( 'checkout/session' );
			if ($this->getRequest()->getParam('code', false) == '200') {
				$session->addSuccess( $this->__( 'Transaction successfully completed.' ) );
			}
		}
		// clear session flag so that next order will redirect to the gateway
		// $session->setCgpOnestepCheckout(false);

		$this->_redirect( 'checkout/onepage/success', array(
			'_secure' => true
		) );
	}

	/**
	 * Control URL called by gateway
	 */
	public function controlAction () {
		$base = Mage::getModel( 'cgp/base' );
		$data = $this->getRequest()->getPost();

		// Verify callback hash
		if ( ! $this->getRequest()->isPost() || ! $this->validate( $data ) ) {
			$message = 'Callback hash validation failed!';
			$base->log( $message );
			echo $message;
			exit();
		}

		// Process callback
		if ( intval( $data['amount'] ) < 0 ) {
			$base->setCallbackData( $data )->processRefundCallback();
		} else {
			$base->setCallbackData( $data )->processCallback();
		}

		// Obtain quote and status
		$status = ( int ) $data['status'];
		$quote = Mage::getModel( 'sales/quote' )->load( $data['extra'] );

		if ( 200 <= $status && $status <= 299 ) {
			// Set Mage_Sales_Model_Quote to inactive and delete
			if ( $quote->getId() ) {
				$quote->setIsActive( false );
				$quote->delete();
			}
		} elseif ( 400 <= $status && $status <= 499 ) {
			// Refund callback - do nothing
		} else {
			// Set Mage_Sales_Model_Quote to active and save
			if ( $quote->getId() ) {
				$quote->setIsActive( true );
				if ( $quote->getReservedOrderId() ) {
					$quote->setOrigOrderId( $quote->getReservedOrderId() );
					$quote->setReservedOrderId();
				}
				$quote->save();
			}
		}

		// Display transaction_id and status
		echo $data['transaction_id'] . '.' . $data['status'];
	}

	public function testAction () {
		$base = Mage::getModel( 'cgp/base' );
		switch ( $this->getRequest()->getParam('action') ) {
			case "restful":
				echo '<pre>';
				if ( $this->getRequest()->getParam('hash') != md5( $base->getConfigData( 'site_id' ) . $base->getConfigData( 'hash_key' ) ) ) {
					die ( 'HASHKEY ERROR' );
				}

				/**
				 * @var Cardgate_Cgp_Model_Gateway_Default $gateway
				 */
				$gateway = Mage::getModel( 'cgp/gateway_default');
				$result = $gateway->doApiCall( 'billingoptions/' . $gateway->getConfigData( 'site_id' ));
				if ( $result['code'] == 200 ) {
					echo "OK.\nRESTful API call for API user '" . $gateway->getConfigData( 'api_id' ) . "' successfull.\n\n-------------\nBilling options for site " . $gateway->getConfigData( 'site_id' ) . ":\n-------------\n\n";
					foreach ($result['result']['billing_options'] as $option) {
						echo "{$option['name']}\n";
					}
				} else {
					echo "ERROR.\nRESTful API call for API user '" . $gateway->getConfigData( 'api_id' ) . "' did not return status 200.\n\n-------------\nResult:\n-------------\n\n";
					print_r( $result );
				}
				break;
			default:
				echo "NOT IMPLEMENTED";
				break;
		}
	}

	public function versionAction () {
		$base = Mage::getModel( 'cgp/base' );
		if ( $this->getRequest()->getParam('hash') != md5( $base->getConfigData( 'site_id' ) . $base->getConfigData( 'hash_key' ) ) ) {
			die ( json_encode( array ( 'error'=>true, 'message'=>'Hash error' ) ) );
		}
		/**
		 * @var Cardgate_Cgp_Model_Gateway_Default $gateway
		 */
		$gateway = Mage::getModel( 'cgp/gateway_default');
		die ( json_encode( array ( 'plugin_version'=>$gateway->getPluginVersion(), 'magento_version'=>Mage::getVersion() ) ) );
	}

	public function resumeAction () {
		$base = Mage::getModel( 'cgp/base' );
		/**
		 *
		 * @var Mage_Checkout_Model_Session $session
		 */
		$session = Mage::getSingleton( 'checkout/session' );

		$order_id = $this->getRequest()->getParam( 'order' );
		if ( !$order_id ) {
			$session->addError( $this->__( 'An error occurred' ) );
			$this->_redirect( 'checkout/cart' );
			$this->getResponse()->sendHeadersAndExit();
			exit;
		}

		/**
		 *
		 * @var Mage_Sales_Model_Order $order
		 */
		$order = Mage::getSingleton( 'sales/order' )->loadByIncrementId( $order_id );

		if ( $this->getRequest()->getParam('hash') != md5( $order->getCustomerEmail() . $base->getConfigData( 'site_id' ) . $base->getConfigData( 'hash_key' ) . $this->getRequest()->getParam( 'action' ) ) ) {
			$session->addError( $this->__( 'A security error occurred' ) );
		}

		if ( !$order->getPayment() ) {
			$this->resumeOrder( $order, true ); // This redirects the client.
			exit; // We won't reach this statement.
		}

		if ( $order->getPayment() && $order->getPayment()->getAmountPaid() > 0 ) {
			$session->addError( $this->__( 'This order is already paid.' ) );
			$this->_redirect( 'checkout/cart' );
			$this->getResponse()->sendHeadersAndExit();
			exit;
		}

		switch ( $this->getRequest()->getParam( 'action' ) ) {
			case "payment":

				/**
				 *
				 * @var Cardgate_Cgp_Model_Gateway_Abstract $method
				 */
				$method = $order->getPayment()->getMethodInstance();
				if (!$method || substr( $method->getCode(), 0, 4 ) != 'cgp_' ) {
					$session->addWarning( $this->__( 'Payment method is not available. Please choose another method.' ) );
					$this->resumeOrder( $order, true ); // This redirects the client.
					exit; // We won't reach this statement.
				}
				$result = $method->register( $order );
				if ( isset( $result['result'] ) && isset( $result['result']['payment'] ) && isset( $result['result']['payment']['issuer_auth_url'] ) ) {
					$this->_redirectUrl( $result['result']['payment']['issuer_auth_url'] );
					$this->getResponse()->sendHeadersAndExit();
					exit; // We won't reach this statement.
				} else {
					$session->addWarning( $this->__( 'An exception occurred registering the transaction. Please try again.' ) );
					$this->resumeOrder( $order, true ); // This redirects the client.
					exit; // We won't reach this statement.
				}

				break;

			case "checkout":

				/**
				 *
				 * @var Cardgate_Cgp_Model_Gateway_Abstract $method
				 */
				$method = Mage::getModel( 'cgp/gateway_default');
				$result = $method->register( $order, true );
				if ( isset( $result['result'] ) && isset( $result['result']['payment'] ) && isset( $result['result']['payment']['issuer_auth_url'] ) ) {
					$this->_redirectUrl( $result['result']['payment']['issuer_auth_url'] );
					$this->getResponse()->sendHeadersAndExit();
					exit; // We won't reach this statement.
				} else {
					$session->addWarning( $this->__( 'An exception occurred registering the transaction. Please try again.' ) );
					$this->resumeOrder( $order, true ); // This redirects the client.
					exit; // We won't reach this statement.
				}

			break;
		}

		$this->_redirect( 'checkout/cart' );
	}

	/**
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @param bool $bDoLogin
	 */
	private function resumeOrder( $order, $bDoLogin=false ) {
		/**
		 *
		 * @var Mage_Checkout_Model_Session $session
		 */
		$session = Mage::getSingleton( 'checkout/session' );
		/**
		 *
		 * @var Mage_Sales_Model_Quote $quote
		 */
		$quote = Mage::getModel( 'sales/quote' )->load( $order->getQuoteId() );
		if ( $quote ) {
			$quote->setIsActive( true );
			$quote->save();
			/**
			 *
			 * @var Mage_Checkout_Model_Session $session
			 */
			$session->replaceQuote( $quote );
			$session->getQuote()->setOrigOrderId(  $order->getIncrementId() );

			if ( $bDoLogin && $order->getCustomerId() ) {
				$customer = Mage::getModel( 'customer/customer' )->load( $order->getCustomerId(), 'entity_id' );
				/**
				 *
				 * @var Mage_Customer_Model_Session $custsession
				 */
				$custsession = Mage::getSingleton( 'customer/session' );
				$custsession->setCustomer( $customer );
			}

			$session->addSuccess( $this->__( 'Your order is resumed.' ) );
		}

		$this->_redirect( 'checkout/cart' );
		$this->getResponse()->sendHeadersAndExit();
	}

}
