<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Gateway_Ideal extends Cardgate_Cgp_Model_Gateway_Abstract
{

	protected $_code = 'cgp_ideal';

	protected $_model = 'ideal';

	protected $_canUseInternal = true;

	protected $_formBlockType = 'cgp/form_ideal';
}
