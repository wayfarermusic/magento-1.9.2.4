<?php
class Rack_Jp_Core_Block_Adminhtml_Customer_Edit_Tab_Addresses extends Mage_Adminhtml_Block_Customer_Edit_Tab_Addresses
{
    public function __construct()
    {
        parent::__construct();
        if(Mage::getStoreConfig('jpcore/name/usekana')) {
            $this->setTemplate('jpcore/customer/edit/tab/addresses.phtml');
        }
    }
}