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
	    $base = Mage::getSingleton( 'cgp/base' );
	    $sTestMode = $base->isTest() ? "<span style='color:#F00'>TEST MODE</span><br/>\n" : '';
	    $sTestMode.= !empty( $_SERVER['CGP_GATEWAY_URL'] ) ? "<span style='color:#F00'>FORCED URL : {$_SERVER['CGP_GATEWAY_URL']}</span><br/>\n" : '';
        return $sTestMode . 'CardGate v' . Mage::getConfig()->getNode( 'modules/Cardgate_Cgp/version' ) . ' / Magento v' . Mage::getVersion();
	}
	
}