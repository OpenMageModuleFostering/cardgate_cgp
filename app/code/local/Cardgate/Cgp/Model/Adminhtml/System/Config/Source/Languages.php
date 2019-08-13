<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Adminhtml_System_Config_Source_Languages
{

	public function toOptionArray ()
	{
		return array( 
				array( 
						"value" => "nl", 
						"label" => Mage::helper( "cgp" )->__( 'Dutch' ) 
				), 
				array( 
						"value" => "en", 
						"label" => Mage::helper( "cgp" )->__( 'English' ) 
				), 
				array( 
						"value" => "de", 
						"label" => Mage::helper( "cgp" )->__( 'German' ) 
				), 
				array( 
						"value" => "fr", 
						"label" => Mage::helper( "cgp" )->__( 'French' ) 
				), 
				array( 
						"value" => "es", 
						"label" => Mage::helper( "cgp" )->__( 'Spanish' ) 
				), 
				array( 
						"value" => "gr", 
						"label" => Mage::helper( "cgp" )->__( 'Greek' ) 
				), 
				array( 
						"value" => "hr", 
						"label" => Mage::helper( "cgp" )->__( 'Croatian' ) 
				), 
				array( 
						"value" => "it", 
						"label" => Mage::helper( "cgp" )->__( 'Italian' ) 
				), 
				array( 
						"value" => "cz", 
						"label" => Mage::helper( "cgp" )->__( 'Czech' ) 
				), 
				array( 
						"value" => "ru", 
						"label" => Mage::helper( "cgp" )->__( 'Russian' ) 
				), 
				array( 
						"value" => "se", 
						"label" => Mage::helper( "cgp" )->__( 'Swedish' ) 
				) 
		);
	}
}