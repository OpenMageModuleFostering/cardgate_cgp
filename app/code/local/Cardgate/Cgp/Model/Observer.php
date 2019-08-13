<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Observer extends Mage_Core_Model_Abstract
{

	public function initFromOrderSessionQuoteInitialized ( Varien_Event_Observer $observer )
	{
		$quote = $observer->getEvent()->getSessionQuote()->getQuote();
		self::addInvoiceFeeToQuote( $quote );
	}

	public function salesQuoteCollectTotalsAfter ( Varien_Event_Observer $observer )
	{
		$quote = $observer->getEvent()->getQuote();
		self::addInvoiceFeeToQuote( $quote );
	}

	public function adminhtmlCheckoutSubmitAllAfter ( Varien_Event_Observer $observer )
	{
		/**
		 * @var Cardgate_Cgp_Model_Base $base
		 */
		$base = Mage::getSingleton( 'cgp/base' );
		if ( ! $base->isRESTCapable() ) {
			return;
		}

		$quote = $observer->getEvent()->getQuote();
		$order = $observer->getEvent()->getOrder();
		$payment = $order->getPayment();

		if ( substr( $payment->getMethodInstance()->getCode(), 0, 3 ) != 'cgp' ) {
			return;
		}

		/**
		 * @var Cardgate_Cgp_Model_Gateway_Abstract $paymentmethod
		 */
		$paymentmethod = $payment->getMethodInstance();
		$registerdata = $paymentmethod->register($order);
		// YYY: Do we need $registerdata?
	}

	protected static function addInvoiceFeeToQuote( $quote )
	{
		$quote->setInvoiceFee( 0 );
		$quote->setBaseInvoiceFee( 0 );
		$quote->setInvoiceFeeExcludedVat( 0 );
		$quote->setBaseInvoiceFeeExcludedVat( 0 );
		$quote->setInvoiceTaxAmount( 0 );
		$quote->setBaseInvoiceTaxAmount( 0 );
		$quote->setInvoiceFeeRate( 0 );

		foreach ( $quote->getAllAddresses() as $address ) {
			$quote->setInvoiceFee( ( float ) $quote->getInvoiceFee() + $address->getInvoiceFee() );
			$quote->setBaseInvoiceFee( ( float ) $quote->getBaseInvoiceFee() + $address->getBaseInvoiceFee() );

			$quoteFeeExclVat = $quote->getInvoiceFeeExcludedVat();
			$addressFeeExclCat = $address->getInvoiceFeeExcludedVat();
			$quote->setInvoiceFeeExcludedVat( ( float ) $quoteFeeExclVat + $addressFeeExclCat );

			$quoteBaseFeeExclVat = $quote->getBaseInvoiceFeeExcludedVat();
			$addressBaseFeeExclVat = $address->getBaseInvoiceFeeExcludedVat();
			$quote->setBaseInvoiceFeeExcludedVat( ( float ) $quoteBaseFeeExclVat + $addressBaseFeeExclVat );

			$quoteFeeTaxAmount = $quote->getInvoiceTaxAmount();
			$addressFeeTaxAmount = $address->getInvoiceTaxAmount();
			$quote->setInvoiceTaxAmount( ( float ) $quoteFeeTaxAmount + $addressFeeTaxAmount );

			$quoteBaseFeeTaxAmount = $quote->getBaseInvoiceTaxAmount();
			$addressBaseFeeTaxAmount = $address->getBaseInvoiceTaxAmount();
			$quote->setBaseInvoiceTaxAmount( ( float ) $quoteBaseFeeTaxAmount + $addressBaseFeeTaxAmount );
			$quote->setInvoiceFeeRate( $address->getInvoiceFeeRate() );
		}
	}

	public function salesOrderPaymentPlaceEnd ( Varien_Event_Observer $observer )
	{
		$payment = $observer->getPayment();

		if ( substr( $payment->getMethodInstance()->getCode(), 0, 3 ) != 'cgp' ) {
			return;
		}

		$info = $payment->getMethodInstance()->getInfoInstance();
		$quote = Mage::getSingleton( 'checkout/session' )->getQuote();
		if ( ! $quote->getId() ) {
			$quote = Mage::getSingleton( 'adminhtml/session_quote' )->getQuote();
		}

		$info->setAdditionalInformation( 'invoice_fee', $quote->getInvoiceFee() );
		$info->setAdditionalInformation( 'base_invoice_fee', $quote->getBaseInvoiceFee() );
		$info->setAdditionalInformation( 'invoice_fee_exluding_vat', $quote->getInvoiceFeeExcludedVat() );
		$info->setAdditionalInformation( 'base_invoice_fee_exluding_vat', $quote->getBaseInvoiceFeeExcludedVat() );
		$info->setAdditionalInformation( 'invoice_tax_amount', $quote->getInvoiceTaxAmount() );
		$info->setAdditionalInformation( 'base_invoice_tax_amount', $quote->getBaseInvoiceTaxAmount() );
		$info->setAdditionalInformation( 'invoice_fee_rate', $quote->getInvoiceFeeRate() );

		$info->save();
	}
}
