<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Block_Form_Banktransfer extends Mage_Payment_Block_Form
{

	protected function _construct ()
	{
		parent::_construct();
		$this->setTemplate( 'cardgate/cgp/form/banktransfer.phtml' );
	}

	/**
	 * Return information payment object
	 *
	 * @return Mage_Payment_Model_Info
	 */
	public function getInfoInstance ()
	{
		return $this->getMethod()->getInfoInstance();
	}

	/**
	 * Returns HTML options for select field with iDEAL banks
	 *
	 * @return string
	 */
	public function getInstructions ()
	{
		$settings = Mage::getStoreConfig( 'cgp/cgp_banktransfer' );
		return nl2br( trim( $settings['instructions'] ) );
	}
}