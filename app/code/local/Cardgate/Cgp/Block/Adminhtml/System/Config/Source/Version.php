<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Block_Adminhtml_System_Config_Source_Version
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

	public function render (Varien_Data_Form_Element_Abstract $element)
	{
		/**
		 * @var Cardgate_Cgp_Model_Base $base
		 */
	    $base = Mage::getSingleton( 'cgp/base' );
	    /**
		 * @var Cardgate_Cgp_Model_Gateway_Default $gateway
		 */
	    $gateway = Mage::getModel( 'cgp/gateway_default');
	    
	    $sTestMode = '';
	    $sTestMode.= $base->isTest() ? "<span style='color:#F00'>TEST MODE</span><br/>\n" : '';
	    $sTestMode.= !empty( $_SERVER['CGP_GATEWAY_URL'] ) ? "<span style='color:#F00'>FORCED GW URL : {$_SERVER['CGP_GATEWAY_URL']}</span><br/>\n" : '';
	    $sTestMode.= !empty( $_SERVER['CGP_API_URL'] ) ? "<span style='color:#F00'>FORCED API URL : {$_SERVER['CGP_API_URL']}</span><br/>\n" : '';
	    
		$missing = array();
	    foreach ( array('hash_key'=>'Hash Key','site_id'=>'Site ID','api_id'=>'RESTful API Username','api_key'=>'RESTful API Key') as $k=>$v ) {
	    	if ( !$gateway->getConfigData( $k ) ) {
	    		$missing[$k] = Mage::helper( 'cgp' )->__($v);
	    	}
	    }
	    if ( !count( $missing ) ) {
	    	$sTestMode.= Mage::helper( 'cgp' )->__("Refunds enabled.") . ' <a href="/cgp/standard/test?action=restful&hash=' . md5( $gateway->getConfigData( 'site_id' ) . $gateway->getConfigData( 'hash_key' )) . '" target="_blank">'.
	    			Mage::helper( 'cgp' )->__("Test current RESTful API settings.") . "</a><br/>\n";
	    } else {
	    	$sTestMode.= Mage::helper( 'cgp' )->__("Refunds disabled. Missing settings:") . " '".implode("', '", $missing)."'.<br/>\n";
	    }
	    
        return $sTestMode . 'CardGate v' . Mage::getConfig()->getNode( 'modules/Cardgate_Cgp/version' ) . ' / Magento v' . Mage::getVersion();
	}
	
}