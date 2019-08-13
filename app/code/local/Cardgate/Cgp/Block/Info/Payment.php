<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Block_Info_Payment extends Mage_Payment_Block_Info
{

	/**
	 * Retrieve order model object
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return Mage::registry('sales_order');
	}

	protected function _toHtml() {
		if ( false === $this->getIsSecureMode() ) {
			$extraLinks = '';
			if ( !empty( $this->getOrder() ) ) {
				$order = $this->getOrder();

				/**
				 * @var Cardgate_Cgp_Model_Base $base
				 */
				$base = Mage::getSingleton( 'cgp/base' );

				if ( $base->isRESTCapable() && intval( $order->getTotalPaid() ) == 0 ) {
					$text = Mage::helper('cgp')->__('Send payment link');
					$url = Mage::helper('adminhtml')->getUrl('*/cardgate/resend', array('orderid' => $order->getId()) );
					$extraLinks.= '<button class="scalable" type="button" title="'.$text.'" onclick="setLocation(\''.$url.'\');">'.$text.'</button>';
				} elseif ( intval( $order->getTotalPaid() ) > 0 ) {
					if ( ! empty( $order->getPayment() ) ) {
						$info = $order->getPayment()->getAdditionalInformation();
						if ( !empty( $info['cardgate_transaction_id'])) {
							$transactionid = $info['cardgate_transaction_id'];
							$testmode = $info['cardgate_testmode'];
							if ( $testmode ) {
								$host = 'staging.curopayments.net';
							} else {
								$host = 'my.cardgate.com';
							}
							$text = Mage::helper('cgp')->__('CardGate transaction information %s', $transactionid);
							$url = "https://".$host."/details/".$transactionid;
							$extraLinks.= '<button class="scalable" type="button" title="'.$text.'" onclick="popWin(\''.$url.'\', \'Cgp_transaction_info\', \'resizable,scrollbars,status\');">'.$text.'</button>';
						}

					}
				}
			}
			return ($extraLinks ? $extraLinks.'<br/>' : '') . parent::_toHtml();
		} else {
			return parent::_toHtml();
		}
	}

}