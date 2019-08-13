<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Adminhtml_System_Config_Source_Modes
{

	public function toOptionArray ()
	{
		return array( 
				array( 
						"value" => "test", 
						"label" => Mage::helper( "cgp" )->__( "Test Mode" ) 
				), 
				array( 
						"value" => "live", 
						"label" => Mage::helper( "cgp" )->__( "Live Mode" ) 
				) 
		);
	}
}