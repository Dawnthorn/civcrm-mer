<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
  </head>
  <body>
    {capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
    {capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
    {capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}
    
	<p>Dear {contact.display_name},</p>
	<p>This is being sent to you as a receipt of payment made for the following workshop, event registration or purchase
made at CompassPoint Nonprofit Services.</p>

	<p>Your order number is #{$trxn->trxn_id}. Please print this confirmation for your records. Information about the workshops will be sent separately to each participant.
Here's a summary of your transaction placed on {$trxn->trxn_date|date_format:"%D %I:%M %p %Z"}:</p>
	<p>
	  ===========================================================
	  {ts}Billing Name and Address{/ts}
	  ===========================================================
	</p>
	<p>
	  {$billingName}<br />
	  {$address}<br /><br />
	  {$email}
	</p>
    <table>
      <thead>
		<tr style="border-bottom: 1px solid #ccc">
		  <th>
			Event
		  </th>
		  <th>
			Participants
		  </th>
		  <th>
			Price
		  </th>
		  <th>
			Quantity
		  </th>
		  <th>
			Total
		  </th>
		</tr>
      </thead>
      <tbody>
	  {foreach from=$line_items item=line_item}
		<tr>
		  <td>
			{$line_item.event->title} ({$line_item.event->start_date|date_format:"%D"})<br />
			{if $isShowLocation}
			  {if $location.address.1.name}
				{$location.address.1.name}
			  {/if}
			  {if $location.address.1.street_address}
				{$location.address.1.street_address}
			  {/if}
			  {if $location.address.1.supplemental_address_1}
				{$location.address.1.supplemental_address_1}
			  {/if}
			  {if $location.address.1.supplemental_address_2}
				{$location.address.1.supplemental_address_2}
			  {/if}
			  {if $location.address.1.city}
				{$location.address.1.city} {$location.address.1.postal_code}
			  {/if}
			{/if}{*End of isShowLocation condition*}<br /><br />
			{$line_item.event->start_date|date_format:"%D %I:%M %p"} - {$line_item.event->end_date|date_format:"%I:%M %p"}
		  </td>
		  <td>
			{if $line_item.num_participants > 0}
			  <div class="participants" style="padding-left: 10px;">
				{foreach from=$line_item.participants item=participant}
				  {$participant->first_name} {$participant->last_name}<br />
				{/foreach}
			  </div>
			{/if}
			{if $line_item.num_waiting_participants > 0}
			  Waitlisted:<br/>
			  <div class="participants" style="padding-left: 10px;">
				{foreach from=$line_item.waiting_participants item=participant}
				  {$participant->first_name} {$participant->last_name}<br />
				{/foreach}
			  </div>
			{/if}
		  </td>
		  <td>
			{$line_item.cost|crmMoney:$currency|string_format:"%10s"}
		  </td>
		  <td>
			{$line_item.num_participants}
		  </td>
		  <td>
			&nbsp;{$line_item.amount|crmMoney:$currency|string_format:"%10s"}
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
			&nbsp;{$sub_total|crmMoney:$currency|string_format:"%10s"}
		  </td>
		</tr>
		{foreach from=$discounts key=myId item=i}
		  <tr>
			<td>
		  {$i.title}
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
			<strong>Total:</strong>
		  </td>
		  <td>
			<strong>&nbsp;{$total|crmMoney:$currency|string_format:"%10s"}</strong>
		  </td>
		</tr>
      </tfoot>
    </table>
		
	<p>
	  ===========================================================
	  {ts}Payment Information{/ts}
	  ===========================================================
	</p>
	<p>
	  {$credit_card_type}<br />
	  {$credit_card_number}<br />
	  {ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
	</p>
	
	<p><strong>Comments:</strong> If you are paying by check, please send payments to CompassPoint Nonprofit Services, 731 Market
	Street, Suite 200, San Francisco, CA 94103 501c Tax Deductible</p>
	
	<p>If you have questions about the status of your registration or purchase please visit: www.compasspoint.org or call
	415.541.9000.</p>
  </body>
</html>
