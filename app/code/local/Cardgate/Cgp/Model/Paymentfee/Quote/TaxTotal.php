<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Paymentfee_Quote_TaxTotal extends Mage_Sales_Model_Quote_Address_Total_Tax
{

	/**
	 * Collect the order total
	 *
	 * @param object $address
	 *        	The address instance to collect from
	 *        	
	 * @return Cardgate_Cgp_Model_Paymentfee_Quote_TaxTotal
	 */
	public function collect ( Mage_Sales_Model_Quote_Address $address )
	{
		$quote = $address->getQuote();
		if ( ! is_a( $quote, 'Cardgate_Cgp_Model_Paymentfee_Quote_Quote' ) ) {
			throw new Exception( 'Plugin clash detected. ' . get_class( $quote ) );
		}
		
		if ( ( $quote->getId() == null ) || ( $address->getAddressType() != "shipping" ) ) {
			return $this;
		}
		
		$payment = $quote->getPayment();
		
		if ( ( substr( $payment->getMethod(), 0, 3 ) != 'cgp' ) && ( ! count( $quote->getPaymentsCollection() ) ||
				 ( ! $payment->hasMethodInstance() ) ) ) {
			return $this;
		}

		$methodInstance = $payment->getMethodInstance();
		
		if ( substr( $methodInstance->getCode(), 0, 3 ) != 'cgp' ) {
			return $this;
		}
		
		$helper = Mage::helper( 'cgp/paymentfee' );
		
		$fee = $helper->getPaymentFeeArray( $methodInstance->getCode(), $quote );
		
		if ( ! is_array( $fee ) ) {
			return $this;
		}
		
		$address->setTaxAmount( $address->getTaxAmount() + $fee['taxamount'] );
		$address->setBaseTaxAmount( $address->getBaseTaxAmount() + $fee['base_taxamount'] );
		
		$address->setInvoiceTaxAmount( $fee['taxamount'] );
		$address->setBaseInvoiceTaxAmount( $fee['base_taxamount'] );
		
		return $this;
	}
}
