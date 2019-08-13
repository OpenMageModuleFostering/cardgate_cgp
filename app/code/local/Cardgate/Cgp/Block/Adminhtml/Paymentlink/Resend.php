<?php

class Cardgate_Cgp_Block_Adminhtml_Paymentlink_Resend extends Mage_Core_Block_Text
{
		/**
		 * {@inheritDoc}
		 * @see Varien_Data_Form_Element_Renderer_Interface::render()
		 */
		public function __construct() {
			/**
			 * @var Mage_Sales_Model_Order $order
			 */

			$order = Mage::getModel( 'sales/order' )->load( $this->getRequest()->get( 'orderid' ) );
			if ( empty( $order ) ) {
				$this->addText( Mage::helper('cgp')->__('Error loading order #%s'), $this->getRequest()->get( 'orderid' ) );
			}
			$payment = $order->getPayment();
			if ( empty( $payment ) ) {
				$this->addText( Mage::helper('cgp')->__('Error loading payment info for order #%s'), $this->getRequest()->get( 'orderid' ) );
			}
			try {
				$title = $payment->getMethodInstance()->getTitle();
			} catch ( Exception $e ) {
				/* ignore */
			}
			if ( empty( $title ) ) {
				$title = $payment->getMethod();
			}
			$cardgateMethod = ( substr($payment->getMethod(), 0, 3) == 'cgp' );
			$this->addText( Mage::helper('cgp')->__('Send payment link for order #%s to email \'%s\'', $order->getId(), $order->getCustomerEmail() ));

			if ( $cardgateMethod && Mage::getSingleton('admin/session')->isAllowed('cardgate/resendpayment') ) {
				$this->addText( '<br/><br/>' );
				$fixedText = Mage::helper('cgp')->__("Send direct payment link using method '%s'", $title);
				$fixedUrl = Mage::helper('adminhtml')->getUrl('*/cardgate/resendpayment', array('orderid' => $order->getId()) );
				$this->addText( '<button class="scalable" type="button" title="'.$fixedText.'" onclick="setLocation(\''.$fixedUrl.'\');">'.$fixedText.'</button>');
			}

			if ( Mage::getSingleton('admin/session')->isAllowed('cardgate/resendcheckout') ) {
				$flexText = Mage::helper('cgp')->__('Send direct payment link allowing all available paymentmethods');
				$flexUrl = Mage::helper('adminhtml')->getUrl('*/cardgate/resendcheckout', array('orderid' => $order->getId()) );
				$this->addText( '<br/><br/><button class="scalable" type="button" title="'.$flexText.'" onclick="setLocation(\''.$flexUrl.'\');">'.$flexText.'</button>' );
				$this->addText( '<br/><br/>' . Mage::helper('cgp')->__('Please notice the order is already finalized and additional transactioncosts don\'t change when other paymentmethods are applied.') );
			}

		}

}