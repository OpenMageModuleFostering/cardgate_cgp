<?php
/**
 * Magento Carg Gate Plus payment extension
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

class Cardgate_Cgp_Model_Adminhtml_System_Config_Source_Modes
{
	public function toOptionArray() {
		return array(
			array(
                "value" => "test",
				"label" => Mage::helper("cgp")->__("Test Mode")
			),
			array(
				"value" => "live",
				"label" => Mage::helper("cgp")->__("Live Mode")
			),
		);
	}
}