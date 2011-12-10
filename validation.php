<?php
/**
 * $Id$
 *
 * clickandbuy Module
 *
 * Copyright (c) 2009 touchdesign
 *
 * @category Payment
 * @version 0.5
 * @copyright 01.12.2009, touchdesign
 * @author Christoph Gruber, <www.touchdesign.de>
 * @link http://www.touchdesign.de/loesungen/prestashop/clickandbuy.htm
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * Description:
 *
 * Payment module clickandbuy
 *
 * --
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@touchdesign.de so we can send you a copy immediately.
 *
 */

require dirname(__FILE__).'/../../config/config.inc.php';
require dirname(__FILE__).'/clickandbuy.php';
require_once dirname(__FILE__).'/lib/touchdesign.php';

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

touchdesign::redirect(_MODULE_DIR_.$clickandbuy->name.'/finalize.php',
  'result=' . $successString . '&externalBDRID=' . $params['externalBDRID'] 
  . '&info=' . implode('|',$reason));

?>