<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_StandardController extends Mage_Core_Controller_Front_Action
{

	private $_gatewayModel;

	/**
	 * Verify the callback
	 *
	 * @param array $data        	
	 * @return boolean
	 */
	protected function validate ( $data )
	{
		$base = Mage::getSingleton( 'cgp/base' );
		
		$hashString = ( $data['is_test'] ? 'TEST' : '' ) . $data['transaction_id'] . $data['currency'] . $data['amount'] .
				 $data['ref'] . $data['status'] . $base->getConfigData( 'hash_key' );
		
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
	protected function getGatewayModel ()
	{
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
	public function redirectAction ()
	{
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
	public function cancelAction ()
	{
		switch ( $_REQUEST['cgpstatusid'] ) {
			case 0:
				$message = $this->__( 
						'Your payment is being evaluated by the bank. Please do not attempt to pay again, until your payment is either confirmed or denied by the bank.' );
				break;
			case 305:
				break;
			case 300:
				$message = $this->__( 
						'Your payment has failed. If you wish, you can try using a different payment method.' );
				break;
		}
		if ( isset( $message ) ) {
			Mage::getSingleton( 'core/session' )->addError( $message );
		}
		
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
			if ($quote->getReservedOrderId()) {
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
	public function successAction ()
	{
		$session = Mage::getSingleton( 'checkout/session' );
		$quote = Mage::getModel( 'sales/quote' )->load( $session->getCardgateQuoteId() );
		if ( $quote->getId() ) {
			$quote->setIsActive( false );
			$quote->delete();
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
	public function controlAction ()
	{
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
		$base->setCallbackData( $data )->processCallback();
		
		// Obtain quote and status
		$status = ( int ) $data['status'];
		$quote = Mage::getModel( 'sales/quote' )->load( $data['extra'] );
		
		// Set Mage_Sales_Model_Quote to inactive and delete
		if ( 200 <= $status && $status <= 299 ) {
			
			// $retain = $base->getConfigData('retain_cart_on_cancel');
			if ( $quote->getId() ) {
				$quote->setIsActive( false );
				$quote->delete();
			}
			// Set Mage_Sales_Model_Quote to active and save
		} else {
			if ( $quote->getId() ) {
				$quote->setIsActive( true );
				if ($quote->getReservedOrderId()) {
					$quote->setOrigOrderId( $quote->getReservedOrderId() );
					$quote->setReservedOrderId();
				}
				$quote->save();
			}
		}
		
		// Display transaction_id and status
		echo $data['transaction_id'] . '.' . $data['status'];
	}
}
