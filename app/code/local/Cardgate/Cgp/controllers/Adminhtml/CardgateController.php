<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Adminhtml_CardgateController extends Mage_Adminhtml_Controller_Action {

	public function indexAction () {
		$this->loadLayout();

		$this->_title( $this->__( 'CardGate' ) );

		$this->renderLayout();
	}

	public function resendAction () {
		$this->loadLayout();

		$this->_title( $this->__( 'CardGate' ) )
			->_title( $this->__( 'Send payment link' ) );

		$block = $this->getLayout()->createBlock( 'Cardgate_Cgp_Block_Adminhtml_Paymentlink_Resend' );
		$this->getLayout()
			->getBlock( 'content' )
			->append( $block );
		$this->renderLayout();
	}

	public function resendPaymentAction () {
		$this->loadLayout();
		$this->_title( $this->__( 'CardGate' ) )
			->_title( $this->__( 'Send payment link' ) );

		/**
		 *
		 * @var Mage_Sales_Model_Order $order
		 */
		$order = Mage::getModel( 'sales/order' )->load( $this->getRequest()
			->get( 'orderid' ) );
		$order = Mage::getModel( 'sales/order' )->load( $this->getRequest()
			->get( 'orderid' ) );
		if ( empty( $order ) ) {
			$this->addText( Mage::helper( 'cgp' )->__( 'Error loading order #%s' ), $this->getRequest()
				->get( 'orderid' ) );
		}
		$payment = $order->getPayment();
		if ( empty( $payment ) ) {
			$this->addText( Mage::helper( 'cgp' )->__( 'Error loading payment info for order #%s' ), $this->getRequest()
				->get( 'orderid' ) );
		}
		try {
			$title = $payment->getMethodInstance()->getTitle();
		} catch ( Exception $e ) {
			/* ignore */
		}
		if ( empty( $title ) ) {
			$title = $payment->getMethod();
		}

		/**
		 *
		 * @var Cardgate_Cgp_Model_Paymentlink $paymentlink
		 */
		$paymentlink = Mage::getModel( 'cgp/paymentlink' );
		$paymentlink->queueNewPaymentEmail( $order, true, 'payment' );

		Mage::getSingleton( 'core/session' )->addSuccess( Mage::helper( 'cgp' )->__( "Payment link mail sent using method \'%s\'", $title ) );
		$this->_redirect( '*/sales_order/view', array(
			'order_id' => $order->getId()
		) );
	}

	public function resendCheckoutAction () {
		$this->loadLayout();
		$this->_title( $this->__( 'CardGate' ) )
			->_title( $this->__( 'Send payment link' ) );

		/**
		 *
		 * @var Mage_Sales_Model_Order $order
		 */
		$order = Mage::getModel( 'sales/order' )->load( $this->getRequest()
			->get( 'orderid' ) );

		/**
		 *
		 * @var Cardgate_Cgp_Model_Paymentlink $paymentlink
		 */
		$paymentlink = Mage::getModel( 'cgp/paymentlink' );
		$paymentlink->queueNewPaymentEmail( $order, true, 'checkout' );

		Mage::getSingleton( 'core/session' )->addSuccess( Mage::helper( 'cgp' )->__( "Payment link mail sent allowing all available paymentmethods" ) );
		$this->_redirect( '*/sales_order/view', array(
			'order_id' => $order->getId()
		) );
	}

}