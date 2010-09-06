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