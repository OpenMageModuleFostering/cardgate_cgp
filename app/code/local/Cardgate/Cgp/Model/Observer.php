<?php

/**
 * Event observer
 *
 * PHP Version 5.3
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */

/**
 * Class to observe and handle Magento events
 *
 * @category Payment
 * @package  Klarna_Module_Magento
 * @author   MS Dev <ms.modules@klarna.com>
 * @license  http://opensource.org/licenses/BSD-2-Clause BSD2
 * @link     http://integration.klarna.com
 */
class Cardgate_Cgp_Model_Observer extends Mage_Core_Model_Abstract {

    public function salesQuoteCollectTotalsBefore( Varien_Event_Observer $observer ) {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();

        if ( $quote->getCustomer()->getId() ) {
            $address = $quote->getCustomer()->getAddress();

            $payment = $quote->getPayment();

            try {
                /**
                 * Instead of relying on hasMethodInstance which would not always
                 * work when i.e the order total is reloaded with coupon codes, we
                 * try to get the instance directly instead.
                 */
                $p = $payment->getMethodInstance();
            } catch ( Mage_Core_Exception $e ) {
                return;
            }
            $paymentMethod = $p->getCode();

            // For Klarna invoice, add Invoice fee
            if ( $paymentMethod == 'cgp_klarna' ) {

                $settings = Mage::getStoreConfig( 'cgp/cgp_klarna', $quote->getStoreId() );
                $fee = floatval( $settings['klarna_invoice_fee_ex'] );

                $store = Mage::app()->getStore( $quote->getStoreId() );
                $carriers = Mage::getStoreConfig( 'carriers', $store );

                foreach ( $carriers as $carrierCode => $carrierConfig ) {

                    // F for fixed, P for percentage
                    $store->setConfig( "carriers/{$carrierCode}/handling_type", 'F' );

                    // 0 for no fee, otherwise fixed of percentage value
                    $handlingFee = $store->getConfig( "carriers/{$carrierCode}/handling_fee" );
                    $store->setConfig( "carriers/{$carrierCode}/handling_fee", $handlingFee + $fee );
                }
            }
        }
    }

}
