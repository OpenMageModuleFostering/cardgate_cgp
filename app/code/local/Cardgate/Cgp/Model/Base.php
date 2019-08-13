<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Base extends Varien_Object
{

	protected $_callback;

	protected $_config = null;

	protected $_isLocked = false;

	protected $_logFileName = "cardgateplus.log";

	/**
	 * Initialize basic cgp settings
	 */
	public function _construct ()
	{
		$this->_config = Mage::getStoreConfig( 'cgp/settings' );
	}

	/**
	 * Retrieve config value
	 *
	 * @param string $field        	
	 * @return mixed
	 */
	public function getConfigData ( $field )
	{
		if ( isset( $this->_config[$field] ) ) {
			return $this->_config[$field];
		} else {
			return false;
		}
	}

	/**
	 * Set callback data
	 *
	 * @param array $data        	
	 * @return Cardgate_Cgp_Model_Base
	 */
	public function setCallbackData ( $data )
	{
		$this->_callback = $data;
		return $this;
	}

	/**
	 * Get callback data
	 *
	 * @param string $field        	
	 * @return string
	 */
	public function getCallbackData ( $field = null )
	{
		if ( $field === null ) {
			return $this->_callback;
		} else {
			return @$this->_callback[$field];
		}
	}

	/**
	 * If the debug mode is enabled
	 *
	 * @return bool
	 */
	public function isDebug ()
	{
		return $this->getConfigData( 'debug' );
	}

	/**
	 * If the test mode is enabled
	 *
	 * @return bool
	 */
	public function isTest ()
	{
		return ( $this->getConfigData( 'test_mode' ) == "test" );
	}

	/**
	 * Log data into the logfile
	 *
	 * @param string $msg        	
	 * @return void
	 */
	public function log ( $msg )
	{
		if ( $this->getConfigData( 'debug' ) ) {
			Mage::log( $msg, null, $this->_logFileName );
		}
	}

	/**
	 * Create lock file
	 *
	 * @return Cardgate_Cgp_Model_Base
	 */
	public function lock ()
	{
		$varDir = Mage::getConfig()->getVarDir( 'locks' );
		$lockFilename = $varDir . DS . $this->getCallbackData( 'ref' ) . '.lock';
		$fp = @fopen( $lockFilename, 'x' );
		
		if ( $fp ) {
			$this->_isLocked = true;
			$pid = getmypid();
			$now = date( 'Y-m-d H:i:s' );
			fwrite( $fp, "Locked by $pid at $now\n" );
		}
		
		return $this;
	}

	/**
	 * Unlock file
	 *
	 * @return Cardgate_Cgp_Model_Base
	 */
	public function unlock ()
	{
		$this->_isLocked = false;
		$varDir = Mage::getConfig()->getVarDir( 'locks' );
		$lockFilename = $varDir . DS . $this->getCallbackData( 'ref' ) . '.lock';
		unlink( $lockFilename );
		
		return $this;
	}

	/**
	 * Create and mail invoice
	 *
	 * @param Mage_Sales_Model_Order $order        	
	 * @return boolean
	 */
	protected function createInvoice ( Mage_Sales_Model_Order $order )
	{
		if ( $order->canInvoice() && ! $order->hasInvoices() ) {
			$invoice = $order->prepareInvoice();
			$invoice->register();
			if ( $invoice->canCapture() ) {
				$invoice->capture();
			}
			
			$invoice->save();
			
			Mage::getModel( "core/resource_transaction" )->addObject( $invoice )
				->addObject( $invoice->getOrder() )
				->save();
			
			$mail_invoice = $this->getConfigData( "mail_invoice" );
			if ( $mail_invoice ) {
				$invoice->setEmailSent( true );
				$invoice->save();
				$invoice->sendEmail();
			}
			
			$statusMessage = $mail_invoice ? "Invoice # %s created and send to customer." : "Invoice # %s created.";
			$order->addStatusToHistory( $order->getStatus(), 
					Mage::helper( "cgp" )->__( $statusMessage, $invoice->getIncrementId(), $mail_invoice ) );
			
			return true;
		}
		
		return false;
	}

	/**
	 * Notify shop owners on failed invoicing creation
	 *
	 * @param Mage_Sales_Model_Order $order        	
	 * @return void
	 */
	protected function eventInvoicingFailed ( $order )
	{
		$storeId = $order->getStore()->getId();
		
		$ident = Mage::getStoreConfig( 'cgp/settings/notification_email' );
		$sender_email = Mage::getStoreConfig( 'trans_email/ident_general/email', $storeId );
		$sender_name = Mage::getStoreConfig( 'trans_email/ident_general/name', $storeId );
		$recipient_email = Mage::getStoreConfig( 'trans_email/ident_' . $ident . '/email', $storeId );
		$recipient_name = Mage::getStoreConfig( 'trans_email/ident_' . $ident . '/name', $storeId );
		
		$mail = new Zend_Mail();
		$mail->setFrom( $sender_email, $sender_name );
		$mail->addTo( $recipient_email, $recipient_name );
		$mail->setSubject( Mage::helper( "cgp" )->__( 'Automatic invoice creation failed' ) );
		$mail->setBodyText( 
				Mage::helper( "cgp" )->__( 
						'Magento was unable to create an invoice for Order # %s after a successful payment via Card Gate (transaction # %s)', 
						$order->getIncrementId(), $this->getCallbackData( 'transaction_id' ) ) );
		$mail->setBodyHtml( 
				Mage::helper( "cgp" )->__( 
						'Magento was unable to create an invoice for <b>Order # %s</b> after a successful payment via Card Gate <b>(transaction # %s)</b>', 
						$order->getIncrementId(), $this->getCallbackData( 'transaction_id' ) ) );
		$mail->send();
	}

	/**
	 * Returns true if the amounts match
	 *
	 * @param Mage_Sales_Model_Order $order        	
	 * @return boolean
	 */
	protected function validateAmount ( Mage_Sales_Model_Order $order )
	{
		$amountInCents = ( int ) sprintf( '%.0f', $order->getGrandTotal() * 100 );
		$callbackAmount = ( int ) $this->getCallbackData( 'amount' );
		
		if ( ( $amountInCents != $callbackAmount ) and ( abs( $callbackAmount - $amountInCents ) > 1 ) ) {
			$this->log( 
					'OrderID: ' . $order->getId() . ' do not match amounts. Sent ' . $amountInCents . ', received: ' .
							 $callbackAmount );
			$statusMessage = Mage::helper( "cgp" )->__( 
					"Hacker attempt: Order total amount does not match CardGate's gross total amount!" );
			$order->addStatusToHistory( $order->getStatus(), $statusMessage );
			$order->save();
			return false;
		}
		
		return true;
	}

	/**
	 * Process callback for all transactions
	 *
	 * @return void
	 */
	public function processCallback ()
	{
		$id = $this->getCallbackData( 'ref' );
		$order = Mage::getModel( 'sales/order' );
		$order->loadByIncrementId( $id );
		
		// Log callback data
		$this->log( 'Receiving callback data:' );
		$this->log( $this->getCallbackData() );
		
		// Validate amount
		if ( ! $this->validateAmount( $order ) ) {
			$this->log( 'Amount validation failed!' );
			exit();
		}
		
		$statusWaitconf = $this->getConfigData( "waitconf_status" );
		$statusPending = $this->getConfigData( "pending_status" );
		$statusComplete = $this->getConfigData( "complete_status" );
		$statusFailed = $this->getConfigData( "failed_status" );
		$statusFraud = $this->getConfigData( "fraud_status" );
		$autocreateInvoice = $this->getConfigData( "autocreate_invoice" );
		$evInvoicingFailed = $this->getConfigData( "event_invoicing_failed" );
		
		$complete = false;
		$canceled = false;
		$newState = null;
		$newStatus = true;
		$statusMessage = '';
		
		$this->log( 
				"Got: {$statusPending}/{$statusComplete}/{$statusFailed}/{$statusFraud}/{$autocreateInvoice}/{$evInvoicingFailed} : " .
						 $this->getCallbackData( 'status_id' ) );
		
		switch ( $this->getCallbackData( 'status_id' ) ) {
			case "0":
				$complete = false;
				$newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$newStatus = $statusPending;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Payment sucessfully authorised.' );
				break;
			case "100":
				$complete = false;
				$newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$newStatus = $statusPending;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Payment sucessfully authorised.' );
				break;
			case "200":
				$complete = true;
				$newState = Mage_Sales_Model_Order::STATE_PROCESSING;
				$newStatus = $statusComplete;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Payment complete.' );
				break;
			case "300":
				$canceled = true;
				$newState = Mage_Sales_Model_Order::STATE_CANCELED;
				$newStatus = $statusFailed;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Payment failed.' );
				break;
			case "301":
				$canceled = true;
				$newState = Mage_Sales_Model_Order::STATE_CANCELED;
				$newStatus = $statusFraud;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Transaction failed, payment is fraud.' );
				break;
			case "308":
				$canceled = true;
				$newState = Mage_Sales_Model_Order::STATE_CANCELED;
				$newStatus = $statusFailed;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Payment expired.' );
				break;
			case "309":
				$canceled = true;
				$newState = Mage_Sales_Model_Order::STATE_CANCELED;
				$newStatus = $statusFailed;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Payment canceled by user.' );
				break;
			case "700":
				// Banktransfer pending status
				$complete = false;
				$newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$newStatus = $statusPending;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Transaction pending: Waiting for customer action.' );
 				$order->sendNewOrderEmail();
 				$order->setIsCustomerNotified( true );
 				$order->save();
				break;
			case "701":
				// Direct debit pending status
				$complete = false;
				$newState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$newStatus = $statusWaitconf;
				$statusMessage = Mage::helper( 'cgp' )->__( 'Transaction pending: Waiting for confirmation.' );
 				$order->sendNewOrderEmail();
 				$order->setIsCustomerNotified( true );
 				$order->save();
				break;
			default:
				$msg = 'Status not recognised: ' . $this->getCallbackData( 'status' );
				$this->log( $msg );
				die( $msg );
		}
		
		// Additional logging for direct-debit
		if ( $this->getCallbackData( 'recipient_name' ) && $this->getCallbackData( 'recipient_iban' )
		    && $this->getCallbackData( 'recipient_bic' ) && $this->getCallbackData( 'recipient_reference' )
	    ) {
	        $statusMessage.= "<br/>\n" . Mage::helper( 'cgp' )->__( 'Additional information' )." : "
	            . "<br/>\n" . Mage::helper( 'cgp' )->__( 'Benificiary' ) ." : ". $this->getCallbackData( 'recipient_name' )
	            . "<br/>\n" . Mage::helper( 'cgp' )->__( 'Benificiary IBAN' ) ." : ". $this->getCallbackData( 'recipient_iban' )
	            . "<br/>\n" . Mage::helper( 'cgp' )->__( 'Benificiary BIC' ) ." : ". $this->getCallbackData( 'recipient_bic' )
	            . "<br/>\n" . Mage::helper( 'cgp' )->__( 'Reference' ) ." : ". $this->getCallbackData( 'recipient_reference' );
	    }
		
		// Update only certain states
		$canUpdate = false;
		$undoCancel = false;
		if ( $order->getState() == Mage_Sales_Model_Order::STATE_NEW ||
				 $order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT ||
				 $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED ) {
			$canUpdate = true;
		}
		
		foreach ( $order->getStatusHistoryCollection( true ) as $_item ) {
			// Don't update order status if the payment is complete
			if ( $_item->getStatusLabel() == ucfirst( $statusComplete ) ) {
				$canUpdate = false;
				// Uncancel an order if the payment is considered complete
			} elseif ( ( $_item->getStatusLabel() == ucfirst( $statusFailed ) ) ||
					 ( $_item->getStatusLabel() == ucfirst( $statusFraud ) ) ) {
				$undoCancel = true;
			}
		}
		
		// increase inventory if the payment failed
		if ( $canUpdate && ! $complete && $canceled && $order->getStatus() != Mage_Sales_Model_Order::STATE_CANCELED ) {
			foreach ( $order->getAllItems() as $_item ) {
				$qty = $_item->getQtyOrdered();
				$stockItem = Mage::getModel( 'cataloginventory/stock_item' )->loadByProduct( $_item->getProductId() );
				$stockItemId = $stockItem->getId();
				$stock = array();
				// then set product's stock data to update
				if ( ! $stockItemId ) {
					// FIXME: This cant work!
					$stockItem->setData( 'product_id', $_item->getProductId() );
					$stockItem->setData( 'stock_id', 1 );
				} else {
					$stock = $stockItem->getData();
				}
				
				$oldQty = $stockItem->getData( 'qty' );
				$stockItem->setData( 'qty', $oldQty + $qty );
				// call save() method to save your product with updated data
				try {
					$stockItem->save();
					// $product->save($p);
				} catch ( Exception $ex ) {
					// handle the error here!!
				}
			}
		}
		
		// Lock
		$this->lock();
		
		// Uncancel order if necessary
		if ( $undoCancel ) {
			foreach ( $order->getAllItems() as $_item ) {
				if ( $_item->getQtyCanceled() > 0 )
					$_item->setQtyCanceled( 0 )->save();
				if ( $_item->getQtyInvoiced() > 0 )
					$_item->setQtyInvoiced( 0 )->save();
			}
			
			$order->setBaseDiscountCanceled( 0 )
				->setBaseShippingCanceled( 0 )
				->setBaseSubtotalCanceled( 0 )
				->setBaseTaxCanceled( 0 )
				->setBaseTotalCanceled( 0 )
				->setDiscountCanceled( 0 )
				->setShippingCanceled( 0 )
				->setSubtotalCanceled( 0 )
				->setTaxCanceled( 0 )
				->setTotalCanceled( 0 );
		}
		
		// Update the status if changed
		if ( $canUpdate && ( ( $newState != $order->getState() ) || ( $newStatus != $order->getStatus() ) ) ) {
			// Create an invoice when the payment is completed
			if ( $complete && ! $canceled && $autocreateInvoice ) {
				$invoiceCreated = $this->createInvoice( $order );
				if ( $invoiceCreated ) {
					$this->log( "Creating invoice for order ID: $id." );
				} else {
					$this->log( "Unable to create invoice for order ID: $id." );
				}
				
				// Send notification
				if ( ! $invoiceCreated && $evInvoicingFailed ) {
					$this->eventInvoicingFailed( $order );
				}
			}
			
			// Set order state and status
			if ( $newState == $order->getState() ) {
			    $order->addStatusToHistory( $newStatus, $statusMessage );
			} else {
			    $order->setState( $newState, $newStatus, $statusMessage );
			}
			$this->log( "Changing state to '$newState' from '".$order->getState()."' with message '$statusMessage' for order ID: $id." );
			
			// Send new order e-mail
			if ( $complete && ! $canceled && ! $order->getEmailSent() ) {
				$order->sendNewOrderEmail();
				$order->setEmailSent( true );
			}
			
			// Save order status changes
			$order->save();
		}
		
		// Unlock
		$this->unlock();
	}
}
