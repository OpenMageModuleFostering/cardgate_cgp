<?php
/**
 * Magento CardGatePlus payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Mage
 * @package     Cardgate_Cgp
 * @author      Paul Saparov, <support@cardgate.com>
 * @copyright   Copyright (c) 2011 CardGatePlus B.V. (http://www.cardgateplus.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Cardgate_Cgp_Model_Gateway_Klarnaaccount extends Cardgate_Cgp_Model_Gateway_Abstract
{
	protected $_code  = 'cgp_klarnaaccount';
	protected $_model = 'klarnaaccount';
	protected $_formBlockType = 'cgp/form_klarnaaccount';
        protected $_canUseCheckout = false;
        
        public function __construct() {
            parent::__construct();
            // This payment method is not used in Austria;
            $klarna_countries = array('DK','FI','DE','NL','NO','SE');
             $country = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCountry();
             if (isset($country) && in_array($country,$klarna_countries)){
                $this->_canUseCheckout = true;
            }
        }
}
 