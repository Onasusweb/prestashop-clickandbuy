<?php
/**
 * $Id$
 *
 * clickandbuy Module
 *
 * Copyright (c) 2009 touchdesign
 *
 * @category Payment
 * @version 0.4
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
require dirname(__FILE__).'/lib/nusoap.php';
require_once dirname(__FILE__).'/lib/touchdesign.php';

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
  $order = new Order($clickandbuy->currentOrder);
  $params['finalizeOrder'] = "SUCCESS: " . $e;
  touchdesign::redirect(__PS_BASE_URI__.'order-confirmation.php','id_cart=' . $cart->id 
    . '&id_module=' . $clickandbuy->id . '&id_order=' . $clickandbuy->currentOrder 
    . '&key='.$order->secure_key);
}else{
  $params['finalizeOrder'] = "ERROR, cant set new order state";
  touchdesign::redirect(__PS_BASE_URI__.'history.php');
}

?>