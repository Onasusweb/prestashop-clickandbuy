<!-- ClickandBuy Module | http://www.touchdesign.de/loesungen/prestashop/clickandbuy_en.htm -->
{if $status == 'accepted' || $status == 'pending'}
  <p>
    {l s='Your order on' mod='clickandbuy'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='clickandbuy'}
    <br /><br />
    {l s='The total amount of this order is' mod='clickandbuy'} <span class="price">{$total_to_pay}</span>
  </p>
{else}
  <p class="warning">
    {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='clickandbuy'} 
    <a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='clickandbuy'}</a>.
  </p>
{/if}
<!-- ClickandBuy Module | http://www.touchdesign.de/loesungen/prestashop/clickandbuy_en.htm -->