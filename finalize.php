<?php
/**
 * $Id: finalize.php 35 2010-07-12 18:33:03Z chris $
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

require dirname(__FILE__).'/../../config/config.inc.php';
require dirname(__FILE__).'/clickandbuy.php';
require dirname(__FILE__).'/lib/nusoap.php';

$clickandbuy = new clickandbuy();

$params['externalBDRID'] = $_GET['externalBDRID'];
$params['successString'] = $_GET['result'];
$params['info'] = $_GET['info'];
$params['state'] = 'undef';

if($params['successString'] == 'success'){

	$client = new nusoap_client('http://wsdl.eu.clickandbuy.com/TMI/1.4/TransactionManagerbinding.wsdl',true); 
	$secondconfirmation = array(
		'sellerID' => Configuration::get('CLICKANDBUY_SELLER_ID'),  
		'tmPassword' => Configuration::get('CLICKANDBUY_TRANS_PASSWD'),
		'slaveMerchantID' => '0',
		'externalBDRID' => $params['externalBDRID']
	);

	$result = $client->call('isExternalBDRIDCommitted',$secondconfirmation,'https://clickandbuy.com/TransactionManager/','https://clickandbuy.com/TransactionManager/');
	if ($client->fault) {
		$params['state'] = 'investigate';
	} else {
		$err = $client->getError();
		if ($err) {
			$params['state'] = 'fault';
		} else {
			$params['state'] = 'created'; 
		}
	}

}else{

  $params['state'] = "error"; 
}

$cart = new Cart(intval($params['externalBDRID']));
if($cart && is_object($cart) && $params['state'] == 'created'){
  $params['orderState'] = Configuration::get('CLICKANDBUY_OS_ACCEPTED');
}else{
  $params['orderState'] = Configuration::get('CLICKANDBUY_OS_ERROR');
}

$e = @$clickandbuy->switchOrderState($params['externalBDRID'], $params['orderState'], 
	floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', '')), 
	'TransactionID:' . $params['externalBDRID'] . ', Info: ' . $params['info']);

if($e !== false){

  $params['finalizeOrder'] = "SUCCESS: " . $e;
  $redirectUrl = (Configuration::get('PS_SSL_ENABLED') == 1 ? 'https://' : 'http://')
	. $_SERVER['HTTP_HOST'].__PS_BASE_URI__.'history.php';

}else{

  $params['finalizeOrder'] = "ERROR, cant set new order state";
  $redirectUrl = (Configuration::get('PS_SSL_ENABLED') == 1 ? 'https://' : 'http://')
	. $_SERVER['HTTP_HOST'].__PS_BASE_URI__.'order.php';  

}

Tools::RedirectLink($redirectUrl);

?>