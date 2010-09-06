<?php
/**
 * $Id$
 *
 * ClickandBuy Module
 *
 * Copyright (c) 2009 Christoph Gruber, <www.touchdesign.de>
 *
 * @category Payment
 * @author Christoph Gruber, www.touchdesign.de
 * @version 0.1
 * @copyright 01.12.2009, 12:15:52, Christoph Gruber, touchDesign
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * Description:
 *
 * Payment module ClickandBuy
 *
 */

class clickandbuy extends PaymentModule
{
    private $_html = '';

    public function __construct()
    {
        $this->name = 'clickandbuy';
        $this->tab = 'Payment';
        $this->version = '0.1';
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('ClickandBuy');
        $this->description = $this->l('Accepts payments by ClickandBuy');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
    }

    public function install()
    {
      $sql = "CREATE TABLE "._DB_PREFIX_."clickandbuy(
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                status VARCHAR(30) NOT NULL,
                custRef INT NOT NULL,
                BDRID INT NOT NULL,
                externalBDRID VARCHAR(64),
                price INT NOT NULL,
                currency VARCHAR(3) NOT NULL,
                userIP VARCHAR(15) NOT NULL,
                date_submitted TIMESTAMP,
                PRIMARY KEY (id) 
              ) ENGINE=MyISAM default CHARSET=utf8";
        if(!Db::getInstance()->Execute($sql)){
            return false;
        }
        if (
          !parent::install() || 
          !Configuration::updateValue('CLICKANDBUY_TRANS_LINK', '') || 
          !Configuration::updateValue('CLICKANDBUY_TRANS_PASSWD', '') || 
          !Configuration::updateValue('CLICKANDBUY_MD5_KEY', Tools::passwdGen(20)) ||
          !Configuration::updateValue('CLICKANDBUY_SELLER_ID', '') ||         
          !Configuration::updateValue('CLICKANDBUY_OS_ERROR', 8) ||
          !Configuration::updateValue('CLICKANDBUY_OS_ACCEPTED', 2) ||
          !Configuration::updateValue('CLICKANDBUY_OS_PENDING', 3) ||
          !Configuration::updateValue('CLICKANDBUY_BLOCK_LOGO', 'Y') ||
          !$this->registerHook('payment') ||
          !$this->registerHook('leftColumn')
        ){
            return false;
        }
		
        return true;
    }

    public function uninstall()
    {
        if (
          !Configuration::deleteByName('CLICKANDBUY_TRANS_LINK') || 
          !Configuration::deleteByName('CLICKANDBUY_TRANS_PASSWD') ||
          !Configuration::deleteByName('CLICKANDBUY_MD5_KEY') ||          
          !Configuration::deleteByName('CLICKANDBUY_SELLER_ID') ||
          !Configuration::deleteByName('CLICKANDBUY_OS_ERROR') ||
          !Configuration::deleteByName('CLICKANDBUY_OS_ACCEPTED') ||
          !Configuration::deleteByName('CLICKANDBUY_OS_PENDING') ||
          !Configuration::deleteByName('CLICKANDBUY_BLOCK_LOGO') ||
          !parent::uninstall()
         ){
            return false;
         }
        $sql = "DROP TABLE "._DB_PREFIX_."clickandbuy";
        if(!Db::getInstance()->Execute($sql)){
          return false;
        }
        return true;
    }

    private function _postValidation()
    {
        if (Tools::getValue('submitUpdate')){
            if (!Tools::getValue('CLICKANDBUY_TRANS_LINK')){
                $this->_postErrors[] = $this->l('ClickandBuy "transaction link" is required.');
            }
            if (!Tools::getValue('CLICKANDBUY_TRANS_PASSWD')){
                $this->_postErrors[] = $this->l('ClickandBuy "transaction password" is required.');
            }
            if (!Tools::getValue('CLICKANDBUY_SELLER_ID')){
                $this->_postErrors[] = $this->l('ClickandBuy "seller id" is required.');
            }        
        }
    }

    public function getContent()
    {
        $this->_html .= '<h2>'.$this->displayName.'</h2>';
        if (Tools::isSubmit('submitUpdate')){
            Configuration::updateValue('CLICKANDBUY_TRANS_LINK', Tools::getValue('CLICKANDBUY_TRANS_LINK'));
            Configuration::updateValue('CLICKANDBUY_TRANS_PASSWD', Tools::getValue('CLICKANDBUY_TRANS_PASSWD'));
            Configuration::updateValue('CLICKANDBUY_MD5_KEY', Tools::getValue('CLICKANDBUY_MD5_KEY'));            
            Configuration::updateValue('CLICKANDBUY_SELLER_ID', Tools::getValue('CLICKANDBUY_SELLER_ID'));
            Configuration::updateValue('CLICKANDBUY_BLOCK_LOGO', Tools::getValue('CLICKANDBUY_BLOCK_LOGO'));
        } elseif (Tools::getValue('SellerID') && Tools::getValue('LinkURL') 
          && Tools::getValue('TMIPassword')){
            Configuration::updateValue('CLICKANDBUY_TRANS_LINK', Tools::getValue('LinkURL'));
            Configuration::updateValue('CLICKANDBUY_TRANS_PASSWD', Tools::getValue('TMIPassword'));
            Configuration::updateValue('CLICKANDBUY_SELLER_ID', Tools::getValue('SellerID'));
            $this->getSuccessMessage();
        }

        $this->_postValidation();
        if (isset($this->_postErrors) && sizeof($this->_postErrors)){
            foreach ($this->_postErrors AS $err){
                $this->_html .= '<div class="alert error">'. $err .'</div>';
            }
        }elseif(Tools::getValue('submitUpdate') && !isset($this->_postErrors)){
          $this->getSuccessMessage();
        }

        return $this->_displayForm();
    }

    public function getSuccessMessage()
    {
      $this->_html.='
        <div class="conf confirm">
          <img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
          '.$this->l('Settings updated').'
        </div>';
    }

    private function getEmsPushScriptUrl() 
	{
      return (Configuration::get('PS_SSL_ENABLED') == 1 ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST']._MODULE_DIR_.$this->name.'/emsPush.php';
    }

    private function getConfirmationUrl() 
	{
      return (Configuration::get('PS_SSL_ENABLED') == 1 ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST']._MODULE_DIR_.$this->name.'/validation.php';
    }

    private function getShopUrl() 
	{
      return (Configuration::get('PS_SSL_ENABLED') == 1 ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST'].__PS_BASE_URI__;
    }

    private function getAutoRegisterUrl()
	{
        $params = array(
          'portalid' => 'touchDesign',
          'shopurl' => $this->getShopUrl(),
          'shopname' => Configuration::get('PS_SHOP_NAME'),
          'configurationurl' => 'http://'
			. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
          'md5key' => Configuration::get('CLICKANDBUY_MD5_KEY'),
        );

        $urlTouchDesign= 'http://www.touchdesign.de/loesungen/prestashop/clickandbuy.htm?';
        foreach($params AS $k => $v){
          $urlTouchDesign .= $k.'='.urlencode($v)."&";
        }

        return substr($urlTouchDesign,0,-1);
    }

    private function _displayForm()
    {
        $this->_html.= '
          <style type="text/css">
            fieldset a {
              color:#0099ff;
              text-decoration:underline;"
            }
            fieldset a:hover {
              color:#000000;
              text-decoration:underline;"
            }
          </style>';

        $this->_html.= '
          <div><img src="'.$this->_path.'logoBig.png" alt="logoBig.png" alt="logoBig.png" title="ClickandBuy" /></div>
          <br /><br />';

        $this->_html.= '
            <fieldset>
              <legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Merchant Registration').'</legend>
              <div>
                '.$this->l('Automatic').' <a href="'.$this->getAutoRegisterUrl().'"><strong>'.$this->l('ClickandBuy Merchant Registration').'</strong></a>.
              </div>
            </fieldset>
            <br /><br />
            <div class="clear"></div>';

        $this->_html.= '
            <form method="post" action="'.$_SERVER['REQUEST_URI'].'">
            <fieldset>
                <legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Settings').'</legend>
                
                <label>'.$this->l('ClickandBuy seller ID?').'</label>
                <div class="margin-form">
                    <input type="text" name="CLICKANDBUY_SELLER_ID" value="'.Configuration::get('CLICKANDBUY_SELLER_ID').'" />
                    <p>'.$this->l('Your Seller id').'</p>
                </div>
                <div class="clear"></div>

                <label>'.$this->l('ClickandBuy transaction link?').'</label>
                <div class="margin-form">
                    <input type="text" name="CLICKANDBUY_TRANS_LINK" value="'.Configuration::get('CLICKANDBUY_TRANS_LINK').'" />
                    <p>'.$this->l('Your Premiumlink').'</p>
                </div>
                <div class="clear"></div>             
                
                <label>'.$this->l('ClickandBuy MD5 Password?').'</label>
                <div class="margin-form">
                    <input type="text" name="CLICKANDBUY_MD5_KEY" value="'.Configuration::get('CLICKANDBUY_MD5_KEY').'" />
                    <p>'.$this->l('Leave it blank for disabling').'</p>
                </div>
                <div class="clear"></div>   
                
                <label>'.$this->l('ClickandBuy transaction password?').'</label>
                <div class="margin-form">
                    <input type="text" name="CLICKANDBUY_TRANS_PASSWD" value="'.Configuration::get('CLICKANDBUY_TRANS_PASSWD').'" />
                    <p>'.$this->l('Your transaction password').'</p>
                </div>
                <div class="clear"></div>

                <label>'.$this->l('ClickandBuy Logo?').'</label>
                <div class="margin-form">
					<select name="CLICKANDBUY_BLOCK_LOGO">
						<option '.(Configuration::get('CLICKANDBUY_BLOCK_LOGO') == "Y" ? "selected" : "").' value="Y">'.$this->l('Yes, display the logo (recommended)').'</option>
						<option '.(Configuration::get('CLICKANDBUY_BLOCK_LOGO') == "N" ? "selected" : "").' value="N">'.$this->l('No, do not display').'</option>
					</select>
                    <p>'.$this->l('Display logo and payment info block in left column').'</p>
                </div>
                <div class="clear"></div>';
          
		  $this->_html.= '
                <div class="margin-form clear pspace"><input type="submit" name="submitUpdate" value="'.$this->l('Update').'" class="button" /></div>
            </fieldset>
            </form>
            <div class="clear"></div>';

          $this->_html.= '
            <br /><br />
            <fieldset>
                <legend><img src="'.$this->_path.'logo.gif" />'.$this->l('URLs').'</legend>
                <b>'.$this->l('Confirmation-Url:').'</b><br /><textarea rows=1 style="width:98%;">'.$this->getConfirmationUrl().'</textarea>
                <b>'.$this->l('emsPush-Script:').'</b><br /><textarea rows=1 style="width:98%;">'.$this->getEmsPushScriptUrl().'</textarea>
            </fieldset>';

          $this->_html.= '
            <fieldset class="space">
              <legend><img src="../img/admin/unknown.gif" alt="" class="middle" />'.$this->l('Help').'</legend>   
              <b>'.$this->l('@Link:').'</b> <a target="_blank" href="http://www.clickanbuy.com/">'.$this->l('ClickandBuy.com').'</a><br />
              <b>'.$this->l('@Vendor:').'</b> ClickandBuy International Limited<br />
              '.$this->l('@Author:').' Christoph Gruber, <a target="_blank" href="http://www.touchdesign.de/">www.touchdesign.de</a><br />
              '.$this->l('@See:').' <a target="_blank" href="http://www.prestashop-deutschland.de/">Prestashop-Deutschland.de</a>
            </fieldset><br />';
        
        return $this->_html;
    }

    public function hookPayment($params)
    {
        $isPayment = $this->isPayment();
        if($isPayment !== true)
          return $this->l($isPayment);

        global $smarty;

        $addressInvoice = new Address(intval($params['cart']->id_address_invoice));
        $addressDelivery = new Address(intval($params['cart']->id_address_delivery));
        $customer = new Customer(intval($params['cart']->id_customer));
        $currency = $this->getCurrency();
        $countryInvoice = new Country(intval($addressInvoice->id_country));
        $countryDelivery = new Country(intval($addressDelivery->id_country));
		$lang = Language::getIsoById(intval($params['cart']->id_lang));

        if (!Validate::isLoadedObject($addressInvoice) || !Validate::isLoadedObject($addressDelivery) 
          || !Validate::isLoadedObject($customer) || !Validate::isLoadedObject($currency))
            return $this->l($this->displayName.' Error: (invalid address or customer)');

        $timestamp = time();
        $premiumLink = Configuration::get('CLICKANDBUY_TRANS_LINK');

        $params = array(
          'price' => number_format(Tools::convertPrice($params['cart']->getOrderTotal(), $currency), 2, '.', ''), 
          'cb_currency' => $currency->iso_code, 
          'externalBDRID' => $params['cart']->id,
          'cb_content_name_utf' => $this->l('CartId:').' '.$timestamp.intval($params['cart']->id),
          'cb_content_info_utf' => $customer->firstname.' '.ucfirst(strtolower($customer->lastname)),
          'lang' => $lang,
          'cb_billing_FirstName' => $addressInvoice->firstname,
          'cb_billing_LastName' => $addressInvoice->lastname,
          'cb_billing_Street' => $addressInvoice->address1,
          'cb_billing_City' => $addressInvoice->city,
          'cb_billing_ZIP' => $addressInvoice->postcode,
          'cb_billing_Nation' => $countryInvoice->iso_code,
          'cb_shipping_FirstName' => $addressDelivery->firstname,
          'cb_shipping_LastName' => $addressDelivery->lastname,
          'cb_shipping_Street' => $addressDelivery->address1,
          'cb_shipping_City' => $addressDelivery->city,
          'cb_shipping_ZIP' => $addressDelivery->postcode,
          'cb_shipping_Nation' => $countryDelivery->iso_code,
        );

        $query="";
        foreach($params AS $k => $v){
          $query .= $k."=".urlencode($v)."&";
        }

        $url = $premiumLink.'?'.substr($query,0,-1);
        if(Configuration::get('CLICKANDBUY_MD5_KEY')){
          $url.='&fgkey='.md5(Configuration::get('CLICKANDBUY_MD5_KEY') . "/" . basename($url));
        }

        $smarty->assign('gateway',$url);

        return $this->display(__FILE__, 'clickandbuy.tpl');
    }

	function hookLeftColumn($params)
	{
		if(Configuration::get('CLICKANDBUY_BLOCK_LOGO') == "N")
			return false;
		return $this->display(__FILE__, 'blockclickandbuylogo.tpl');
	}
	
    public function isPayment()
    {
        if (!$this->active)
          return false;
        if (!Configuration::get('CLICKANDBUY_TRANS_LINK'))
            return $this->l($this->displayName.' Error: (invalid or undefined transaction link)');
        if (!Configuration::get('CLICKANDBUY_SELLER_ID'))
            return $this->l($this->displayName.' Error: (invalid or undefined transaction link)');
        return true;
    }

	public function switchOrderState($cartId, $orderState, $amount=NULL, $message=NULL)
	{
		$orderId = Order::getOrderByCartId($cartId);
		if ($orderId){
			$order = new Order($orderId);

			if($order->getCurrentState() != $orderState){
				$history = new OrderHistory();
				$history->id_order = $orderId;
				$history->changeIdOrderState($orderState, $orderId);
				$history->addWithemail(true);        

				if($message !== NULL){
					$orderMessage = new Message();
					$orderMessage->message = $message;
					$orderMessage->private = 1;
					$orderMessage->id_order = $orderId;
					$orderMessage->add();
				}
				
				return 1;
			}
		} else {
			$this->validateOrder($cartId, $orderState, $amount, 
				$this->displayName, $message);
			
			return 0;
		}
		
		return false;
    }

    function getEMSOrderState($action) 
	{
		switch ($action) {
			case 'payment_successful':
			case 'charge back lifted':
			case 'BDR successfully collected from collection agency':
			case 'booked-in':
				return Configuration::get('CLICKANDBUY_OS_ACCEPTED');
			case 'BDR not collected from collection agency':
			case 'booked-out':
			case 'cancelled':
				return Configuration::get('CLICKANDBUY_OS_ERROR');
			case 'BDR to collection agency':
			case 'charge back':
				return Configuration::get('CLICKANDBUY_OS_PENDING');
			default:
				return false;
		}
    }	

}

?>