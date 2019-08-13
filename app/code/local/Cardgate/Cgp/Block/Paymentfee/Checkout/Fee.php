<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */

class Cardgate_Cgp_Block_Paymentfee_Checkout_Fee extends Mage_Checkout_Block_Total_Default
{

	protected $_template = 'cardgate/cgp/checkout/fee.phtml';

	/**
	 * Get Payment fee including tax
	 *
	 * @return float
	 */
	public function getInvoiceFeeIncludeTax ()
	{
		return $this->getTotal()
			->getAddress()
			->getInvoiceFee();
	}

	/**
	 * Get Payment fee excluding tax
	 *
	 * @return float
	 */
	public function getInvoiceFeeExcludeTax ()
	{
		return $this->getTotal()
			->getAddress()
			->getInvoiceFeeExcludedVat();
	}

	/**
	 * Checks if including and excluding tax prices should be shown
	 *
	 * @return bool
	 */
	public function displayBoth ()
	{
		return Mage::helper( "tax" )->displayCartBothPrices();
	}

	/**
	 * Checks if including tax price should be shown
	 *
	 * @return bool
	 */
	public function displayIncludeTax ()
	{
		return Mage::helper( "tax" )->displayCartPriceInclTax();
	}

	/**
	 * Get the label for "excluding tax"
	 *
	 * @return string
	 */
	public function getExcludeTaxLabel ()
	{
		return Mage::helper( "tax" )->getIncExcTaxLabel( false );
	}

	/**
	 * Get the label for "including tax"
	 *
	 * @return string
	 */
	public function getIncludeTaxLabel ()
	{
		return Mage::helper( "tax" )->getIncExcTaxLabel( true );
	}
}
