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
 * @author Christin Gruber, <www.touchdesign.de>
 * @link http://www.touchdesign.de/loesungen/prestashop/clickandbuy.htm
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * Description:
 *
 * Payment module clickandbuy by touchdesign
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

$useSSL = true;

require_once dirname(__FILE__).'/../../config/config.inc.php';
require_once dirname(__FILE__).'/clickandbuy.php';
require_once dirname(__FILE__).'/../../header.php';
require_once dirname(__FILE__).'/lib/touchdesign.php';

if (!$cookie->isLogged(true)){
  touchdesign::redirect(__PS_BASE_URI__.'order.php','order=back.php');
}

$clickandbuy = new clickandbuy();

echo $clickandbuy->execPayment($cart);

include_once dirname(__FILE__).'/../../footer.php';

?>