<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */

class Cardgate_Cgp_Model_Paymentfee_Quote_Quote extends Mage_Sales_Model_Quote
{

	public function getTotals ()
	{
		$totals = parent::getTotals();
		
		unset( $totals['cgp_tax'] );
		$totalsIndex = array_keys( $totals );
		if ( array_search( 'cgp_fee', $totalsIndex ) === false ) {
			return $totals;
		}
		unset( $totalsIndex[array_search( 'cgp_fee', $totalsIndex )] );
		$fee = $totals['cgp_fee'];
		unset( $totals['cgp_fee'] );
		
		$feeIndex = array_search( 'shipping', $totalsIndex );
		if ( $feeIndex === false ) {
			$feeIndex = array_search( 'subtotal', $totalsIndex ) + 1;
		}
		
		$sortedTotals = array();
		$size = count( $totalsIndex );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( $i == $feeIndex ) {
				$sortedTotals['cgp_fee'] = $fee;
			}
			$sortedTotals[array_shift( $totalsIndex )] = array_shift( $totals );
		}
		
		return $sortedTotals;
	}
}


