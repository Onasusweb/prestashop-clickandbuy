{**
 * $Id$
 *
 * clickandbuy Module
 *
 * Copyright (c) 2009 touchdesign
 *
 * @category Payment
 * @version 1.0
 * @copyright 19.08.2009, touchdesign
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
 *}

<!-- touchdesign | clickandbuy Module | http://www.touchdesign.de/loesungen/prestashop/clickandbuy.htm -->
{capture name=path}{l s='clickandbuy payment' mod='clickandbuy'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='clickandbuy'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
  <p class="warning">{l s='Your shopping cart is empty.' mod='clickandbuy'}</p>
{else}

<h3>{l s='clickandbuy payment' mod='clickandbuy'}</h3>

<p><img src="{$this_path}clickandbuy.png" alt="clickandbuy.png" title="" width="139" height="37" mod='clickandbuy'}" /></p>
<p>{l s='You have chosen to pay by clickandbuy.' mod='clickandbuy'} {l s='the total amount of your order is' mod='clickandbuy'} <span id="amount" class="price">{displayPrice price=$total}</span> {l s='(tax incl.)' mod='clickandbuy'}</p>
<p style="margin-top:20px;"><b>{l s='Please confirm your order by clicking \'I confirm my order\'.' mod='clickandbuy'}</b></p>

<p class="cart_navigation">
  <a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Other payment methods' mod='clickandbuy'}</a>
  <a href="{$gateway}" class="exclusive_large">{l s='I confirm my order' mod='clickandbuy'}</a>
</p>

{/if}
<!-- touchdesign | clickandbuy Module | http://www.touchdesign.de/loesungen/prestashop/sofortueberweisung.htm -->