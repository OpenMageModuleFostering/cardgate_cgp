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
			'' => 'Please select',
			'RABONL2U' => 'Rabobank', 
			'ABNANL2A' => 'ABN Amro Bank',
			'INGBNL2A' => 'ING', 
			'SNSBNL2A' => 'SNS Bank',
			'KNABNL2H' => 'Knab',
			'FVLBNL22' => 'Van Lanschot Bankiers', 
			'TRIONL2U' => 'Triodos Bank', 
			'ASNBNL21' => 'ASN Bank', 
			'RBRBNL21' => 'RegioBank',
            'BUNQNL2A' => 'bunq'
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
		
	    $ideal = Mage::getSingleton( 'cgp/gateway_ideal' );
		$client = new Varien_Http_Client( $ideal->getGatewayUrl() . '/cache/idealDirectoryCUROPayments.dat' );
		try{
			$response = $client->request();
			if ($response->isSuccessful()) {
				$aBanks = unserialize( $response->getBody() );
				if ( is_array( $aBanks ) ) {
					unset($aBanks[0]);
					$this->_banks = array_merge(array(''=>''),$aBanks);
				}
			}
		} catch (Exception $e) {
		}
		$this->_banks[''] = Mage::helper( 'cgp' )->__( '--Please select--' );
		return $this->_banks;
	}
}