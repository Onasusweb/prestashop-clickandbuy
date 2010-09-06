<?php
/**
 * $Id$
 *
 * ClickandBuy Module
 *
 * Copyright (c) 2009 touchDesign
 *
 * @category Payment
 * @author Christoph Gruber <www.touchdesign.de>
 * @version 0.2
 * @copyright 01.12.2009, touchDesign
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * Description:
 *
 * Payment module ClickandBuy
 *
 */

require dirname(__FILE__).'/../../config/config.inc.php';
require dirname(__FILE__).'/clickandbuy.php';

$clickandbuy = new clickandbuy();

$params = array();
$params['custRef'] = isset($_SERVER['HTTP_X_USERID']) 
	? $_SERVER['HTTP_X_USERID'] : NULL;
$params['price'] = isset($_SERVER['HTTP_X_PRICE']) 
	? $_SERVER['HTTP_X_PRICE'] : NULL;
$params['BDRID'] = isset($_SERVER['HTTP_X_TRANSACTION']) 
	? $_SERVER['HTTP_X_TRANSACTION'] : NULL;
$params['currency'] = isset($_SERVER['HTTP_X_CURRENCY']) 
	? $_SERVER['HTTP_X_CURRENCY'] : NULL;
$params['userIP'] = isset($_SERVER['HTTP_X_USERIP']) 
	? $_SERVER['HTTP_X_USERIP'] : NULL;
$params['linkID'] = isset($_SERVER['HTTP_X_CONTENTID']) 
	? $_SERVER['HTTP_X_CONTENTID'] : NULL;
$params['proxyIP'] = isset($_SERVER['REMOTE_ADDR']) 
	? $_SERVER['REMOTE_ADDR'] : NULL;
$params['externalBDRID'] = isset($_GET['externalBDRID']) 
	? $_GET['externalBDRID'] : NULL;

$finalizeUrl = (Configuration::get('PS_SSL_ENABLED') == 1 ? 'https://' : 'http://')
	. $_SERVER['HTTP_HOST']._MODULE_DIR_.$clickandbuy->name.'/finalize.php';
$successString = 'success';
$reason = array();
$orderState = Configuration::get('CLICKANDBUY_OS_PENDING');

$concateParams = implode("",$params);
if($concateParams == $params['proxyIP']){
	$successString = 'error';
	$reason[] = "00";
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');	
}

if(empty($params['custRef']) || is_nan($params['custRef'])){
	$successString = 'error';
	$reason[] = "01";
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');	
}

if(substr($params['proxyIP'],0,11) != '217.22.128.'){
	$successString = 'error';
	$reason[] = '02';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');		
}

if(empty($params['price'])) {
	$successString = 'error';
	$reason[] = '03';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');		
}

if(empty($params['BDRID'])) {
	$successString = 'error';
	$reason[] = '04';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');
}

if(empty($params['externalBDRID'])) {
	$successString = 'error';
	$reason[] = '05';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');		
}

$cart = new Cart(intval($params['externalBDRID']));
if(!is_object($cart) || !$cart){
	$successString = 'error';
	$reason[] = '06';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');	
}

$isBDRID = Db::getInstance()->getValue("SELECT id FROM "._DB_PREFIX_."clickandbuy WHERE externalBDRID = '".pSQL($params['externalBDRID'])."'");
if($isBDRID){
	$successString = 'error';
	$reason[] = '07';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');	
}

$cartTotal = number_format($cart->getOrderTotal(true, 3),2);
$externalTotal = number_format((int)$params['price']/100000,2);

$params['externalCartTotal'] = $externalTotal;
$params['internalCartTotal'] = $cartTotal;

if(strcasecmp(trim($cartTotal), trim($externalTotal)) != 0){
	$successString = 'error';
	$reason[] = '08';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');
}

$currency = $clickandbuy->getCurrency();
if($currency->iso_code != $params['currency']){
	$successString = 'error';
	$reason[] = '09';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');
}

$sql="INSERT INTO "._DB_PREFIX_."clickandbuy
  SET
    status='$successString', 
    custRef='".pSQL($params['custRef'])."', 
    BDRID='".pSQL($params['BDRID'])."', 
    externalBDRID='".pSQL($params['externalBDRID'])."',
    price='".pSQL($params['price'])."',
    currency='".pSQL($params['currency'])."',
    userIP='".pSQL($params['userIP'])."',
    date_submitted=NOW()";

if(!Db::getInstance()->Execute($sql)){
	$successString = 'error';
	$reason[] = '10';
	$orderState = Configuration::get('CLICKANDBUY_OS_ERROR');
}

$urlSecondHandshake = $finalizeUrl . '?result=' . $successString 
	. '&externalBDRID=' . $params['externalBDRID'] . '&info=' . implode('|',$reason);

Tools::RedirectLink($urlSecondHandshake);

?>