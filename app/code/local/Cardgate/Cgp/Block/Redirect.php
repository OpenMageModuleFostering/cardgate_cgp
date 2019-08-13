<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Block_Redirect extends Mage_Core_Block_Template
{

	protected function _construct ()
	{
		$this->setTemplate( 'cardgate/cgp/redirect.phtml' );
	}

	public function getForm ()
	{
		$model = Mage::getModel( Mage::registry( 'cgp_model' ) );
		Mage::unregister( 'cgp_model' );
		
		$form = new Varien_Data_Form();
		$form->setAction( $model->getGatewayUrl() )
			->setId( 'cardgateplus_checkout' )
			->setName( 'cardgateplus_checkout' )
			->setMethod( 'POST' )
			->setUseContainer( true );
		
		foreach ( $model->getCheckoutFormFields() as $field => $value ) {
			$form->addField( $field, 'hidden', array( 
					'name' => $field, 
					'value' => $value 
			) );
		}
		return $form->getHtml();
	}
}
