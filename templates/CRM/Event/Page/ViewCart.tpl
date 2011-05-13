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
	  <a href="/workshop/{$event_in_cart.event.title|urlencode}" title="{ts}View event info page{/ts}" class="bold">{$event_in_cart.event.title}</a>
	</td>
	<td>
	  <a title="Remove From Cart" class="action-item" href="{crmURL p='civicrm/event/remove_from_cart' q="reset=1&id=`$event_in_cart.id`"}">{ts}Remove{/ts}</a>
	</td>
      </tr>
    {/foreach}
  </tbody>
</table>
{if $events_count > 0}
<a href="{$checkout_url}"><img src="/sites/all/themes/compasspoint/images/cart.gif" />Check Out</a><br /><br />
{/if}
<a href="/workshops">&laquo; Back to Workshop Catalog</a>
