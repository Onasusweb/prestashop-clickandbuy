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

$clickandbuy = new clickandbuy();

if(substr($_SERVER["REMOTE_ADDR"],0,11) != '217.22.128.'){
  die("NOK");
}

if(!isset($_POST['xml']) || empty($_POST['xml'])){
  die("NOK");
}else{
  $xmlData = str_replace("\\" , "" ,$_POST['xml']);
  $transactionDetails.="XMLDATA: $xmlData\n";
  $xml = new SimpleXMLElement($xmlData);  
}

$event['id'] = (string)$xml->GLOBAL->{'event-id'};
$event['crn'] = (string)$xml->GLOBAL->crn;
$event['systemID'] = (int)$xml->GLOBAL->systemID;
$event['cb_datetime'] = (string)$xml->GLOBAL->datetime;
$event['action'] = (string)$xml->BDR->{'bdr-data'}->action;
$event['annotation'] = (string)$xml->BDR->{'bdr-data'}->annotation;
$event['externalBDRID'] = (string)$xml->BDR->{'bdr-data'}->externalBDRID;
$event['bdrID'] = (string)$xml->BDR->{'bdr-data'}->{'bdr-id'};
$event['price'] = (string)$xml->BDR->{'bdr-data'}->price;
$event['currency'] = (string)$xml->BDR->{'bdr-data'}->currency;
$event['linkID'] = (string)$xml->BDR->{'bdr-data'}->{'link-nr'};
 
if(empty($event['id']) || empty($event['action']) 
  || empty($event['externalBDRID'])){
  die("NOK");
}

$cart = new Cart($event['externalBDRID']);
$orderState = $clickandbuy->getEMSOrderState($event['action']);
if($orderState !== false){
  @$clickandbuy->switchOrderState($event['externalBDRID'], $orderState, 
    floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', '')), 
    $clickandbuy->l('TransactionID: '.$event['externalBDRID']).' '.$clickandbuy->l('Action: '.$event['action']));
}

echo "OK";

?>