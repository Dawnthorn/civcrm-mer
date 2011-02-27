<table>
  <thead>
    <tr>
      <th>
      </th>
	  <th>
      </th>
    </tr>
  </thead>
  <tbody>
    {foreach from=$events_in_carts item=event_in_cart}
      <tr>
	<td>
	  <a href="/workshop/{$event_in_cart.event.title|replace:' ':'-'}" title="{ts}View event info page{/ts}" class="bold">{$event_in_cart.event.title}</a>
	</td>
	<td>
	  <a title="Remove From Cart" class="action-item" href="{crmURL p='civicrm/event/remove_from_cart' q="reset=1&id=`$event_in_cart.id`"}">{ts}Remove{/ts}</a>
	</td>
      </tr>
    {/foreach}
  </tbody>
</table>
<a href="{$checkout_url}">Checkout</a>
