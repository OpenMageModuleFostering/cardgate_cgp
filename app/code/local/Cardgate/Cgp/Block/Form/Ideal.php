<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Block_Form_Ideal extends Mage_Payment_Block_Form
{

	protected $_banks = array( 
			'0021' => 'Rabobank', 
			'0031' => 'ABN Amro', 
			'0091' => 'Friesland Bank', 
			'0721' => 'ING', 
			'0751' => 'SNS Bank', 
			'0001' => '------ Additional Banks ------', 
			'0161' => 'Van Lanschot Bank', 
			'0511' => 'Triodos Bank', 
			'0761' => 'ASN Bank', 
			'0771' => 'SNS Regio Bank' 
	);

	protected function _construct ()
	{
		parent::_construct();
		$this->setTemplate( 'cardgate/cgp/form/ideal.phtml' );
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
	public function getSelectField ()
	{
		$a2 = array();
		$aBanks = $this->getBankOptions();
		foreach ( $aBanks as $id => $name ) {
			$a2[$id] = Mage::helper( 'cgp' )->__( $name );
		}
		$_code = $this->getMethodCode();
		
		$form = new Varien_Data_Form();
		$form->addField( $_code . '_ideal_issuer', 'select', 
				array( 
						'name' => 'payment[cgp][ideal_issuer_id]', 
						'class' => 'required-entry', 
						'label' => Mage::helper( 'cgp' )->__( 'Select your bank' ), 
						'values' => $a2, 
						'value' => '', 
						'required' => true, 
						'disabled' => false 
				) );
		return $form->getHtml();
	}

	/**
	 * Fetch iDEAL bank options from CardGatePlus if possible and return as
	 * array.
	 *
	 * @return array
	 */
	private function getBankOptions ()
	{
		$url = 'https://gateway.cardgateplus.com/cache/idealDirectoryRabobank.dat';
		if ( ! function_exists( 'file_get_contents' ) ) {
			$result = false;
		} else {
			$result = file_get_contents( $url );
		}
		
		$aBanks = array();
		
		if ( $result ) {
			$aBanks = unserialize( $result );
			unset( $aBanks[0] );
			$a2 = array();
			foreach ( $aBanks as $id => $name ) {
				if ( $id == 1 )
					$id = '0001';
				$a2[$id] = $name;
			}
			$aBanks = $a2;
			$aBanks = array_merge( array( 
					'' => Mage::helper( 'cgp' )->__( '--Please select--' ) 
			), $aBanks );
		}
		if ( count( $aBanks ) < 1 ) {
			$aBanks = array_merge( array( 
					'' => Mage::helper( 'cgp' )->__( '--Please select--' ) 
			), $this->_banks );
		}
		return $aBanks;
	}
}