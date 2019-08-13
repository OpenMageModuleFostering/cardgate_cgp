<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Paymentlink extends Mage_Core_Model_Abstract {

	public function queueNewPaymentEmail ( Mage_Sales_Model_Order $order, $forceMode = false, $action = "payment" ) {
		$storeId = $order->getStore()->getId();

		// Get the destination email addresses to send copies to
		$copyTo = false;
		$data = Mage::getStoreConfig( 'cgp/email_paymentlink/copy_to', $storeId );
        if (!empty($data)) {
            $copyTo = explode(',', $data);
        }

		$copyMethod = Mage::getStoreConfig( 'cgp/email_paymentlink/copy_method', $storeId );

		// Start store emulation process
		/** @var $appEmulation Mage_Core_Model_App_Emulation */
		$appEmulation = Mage::getSingleton( 'core/app_emulation' );
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation( $storeId );

		try {
			// Retrieve specified view block from appropriate design package
			// (depends on emulated store)
			$paymentBlock = Mage::helper( 'payment' )->getInfoBlock( $order->getPayment() )
				->setIsSecureMode( true );
			$paymentBlock->getMethod()->setStore( $storeId );
			$paymentBlockHtml = $paymentBlock->toHtml();
		} catch ( Exception $exception ) {
			// Stop store emulation process
			$appEmulation->stopEnvironmentEmulation( $initialEnvironmentInfo );
			throw $exception;
		}

		// Stop store emulation process
		$appEmulation->stopEnvironmentEmulation( $initialEnvironmentInfo );

		// Retrieve corresponding email template id and customer name
		if ( $order->getCustomerIsGuest() ) {

			$templateId = Mage::getStoreConfig( 'cgp/email_paymentlink/guest_template', $storeId );
			//$templateId = Mage::getStoreConfig( Mage_Sales_Model_Order::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId );
			$customerName = $order->getBillingAddress()->getName();
		} else {
			$templateId = Mage::getStoreConfig( 'cgp/email_paymentlink/template', $storeId );
			//$templateId = Mage::getStoreConfig( Mage_Sales_Model_Order::XML_PATH_EMAIL_TEMPLATE, $storeId );
			$customerName = $order->getCustomerName();
		}

		/** @var $mailer Mage_Core_Model_Email_Template_Mailer */
		$mailer = Mage::getModel( 'core/email_template_mailer' );
		/** @var $emailInfo Mage_Core_Model_Email_Info */
		$emailInfo = Mage::getModel( 'core/email_info' );
		$emailInfo->addTo( $order->getCustomerEmail(), $customerName );
		if ( $copyTo && $copyMethod == 'bcc' ) {
			// Add bcc to customer email
			foreach ( $copyTo as $email ) {
				$emailInfo->addBcc( $email );
			}
		}
		$mailer->addEmailInfo( $emailInfo );

		// Email copies are sent as separated emails if their copy method is
		// 'copy'
		if ( $copyTo && $copyMethod == 'copy' ) {
			foreach ( $copyTo as $email ) {
				$emailInfo = Mage::getModel( 'core/email_info' );
				$emailInfo->addTo( $email );
				$mailer->addEmailInfo( $emailInfo );
			}
		}

		$base = Mage::getModel( 'cgp/base' );
		$hash = md5( $order->getCustomerEmail() . $base->getConfigData( 'site_id' ) . $base->getConfigData( 'hash_key' ) . $action );

		// Set all required params and send emails
		$mailer->setSender( Mage::getStoreConfig( 'cgp/email_paymentlink/identity', $storeId ) );
		$mailer->setStoreId( $storeId );
		$mailer->setTemplateId( $templateId );
		$mailer->setTemplateParams( array(
			'payment_link' => Mage::app()->getStore()->getUrl('cgp/standard/resume', array( 'action'=>$action, 'order'=>$order->getIncrementId(), 'hash'=>$hash ) ),
			'order' => $order,
			'billing' => $order->getBillingAddress(),
			'payment_html' => $paymentBlockHtml
		) );

		/** @var $emailQueue Mage_Core_Model_Email_Queue */
		$emailQueue = Mage::getModel( 'core/email_queue' );
		$emailQueue->setEntityId( $order->getId() )
			->setEntityType( Mage_Sales_Model_Order::ENTITY )
			->setEventType( 'resend_paymentlink' )
			->setIsForceCheck( ! $forceMode );

		$mailer->setQueue( $emailQueue )->send();
		$emailQueue->send();

		//$this->setEmailSent( true );
		//$this->_getResource()->saveAttribute( $this, 'email_sent' );

		return true;
	}

}