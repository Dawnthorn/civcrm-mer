{include file="CRM/common/TrackingFields.tpl"}

<table>
  <thead>
    <tr>
      <th>
	Event
      </th>
      <th>
	Participants
      </th>
      <th>
	Cost
      </th>
      <th>
	Amount
      </th>
    </tr>
  <thead>
  <tbody>
    {foreach from=$line_items item=line_item}
      <tr>
	<td>
	  {$line_item.event->title} ({$line_item.event->start_date})
	</td>
	<td>
	  {$line_item.num_participants}<br/>
	  {if $line_item.num_participants > 0}
	    <div class="participants" style="padding-left: 10px;">
	      {foreach from=$line_item.participants item=participant}
		{$participant->first_name} {$participant->last_name}
	      {/foreach}
	    </div>
	  {/if}
	  {if $line_item.num_waiting_participants > 0}
	    Waitlisted:<br/>
	    <div class="participants" style="padding-left: 10px;">
	      {foreach from=$line_item.waiting_participants item=participant}
		{$participant->first_name} {$participant->last_name}
	      {/foreach}
	    </div>
	  {/if}
	</td>
	<td>
	  {$line_item.cost}
	</td>
	<td>
	  &nbsp;{$line_item.amount}
	</td>
      </tr>
    {/foreach}
  </tbody>
  <tfoot>
  {if $discounts}
    <tr>
      <td>
      </td>
      <td>
      </td>
      <td>
	Subtotal:
      </td>
      <td>
	&nbsp;{$sub_total}
      </td>
    </tr>  
  {foreach from=$discounts key=myId item=i}
    <tr>
      <td>{$i.title}
      </td>
      <td>
      </td>
      <td>
      </td>
      <td>
   -{$i.amount}
      </td>
    </tr>
   {/foreach} 
   {/if} 
    <tr>
      <td>
      </td>
      <td>
      </td>
      <td>
	Total:
      </td>
      <td>
	&nbsp;{$total}
      </td>
    </tr>
  </tfoot>
</table>
{if $payment_required == true}
{include file='CRM/Core/BillingBlock.tpl'}
{/if}
<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
