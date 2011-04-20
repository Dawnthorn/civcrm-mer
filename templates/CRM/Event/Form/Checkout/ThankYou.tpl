{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-block crm-event-thankyou-form-block">
  <p>This is your receipt of payment made for the following workshop, event registration or purchase
made at CompassPoint Nonprofit Services.</p>

	<p>Your order number is <strong>#{$transaction->trxn_id}</strong>. Please print this confirmation for your records. Information about the workshops will be sent separately to each participant.
Here's a summary of your transaction placed on {$transaction->trxn_date|date_format:"%D %I:%M %p %Z"}:</p>
	<p>
	  
	{if $contributeMode ne 'notify' and $paidEvent and ! $is_pay_later and ! $isAmountzero and !$isOnWaitlist and !$isRequireApproval}   
        <div class="crm-group billing_name_address-group">
            <div class="header-dark">
                {ts}Billing Name and Address{/ts}
            </div>
        	<div class="crm-section no-label billing_name-section">
        		<div class="content">{$billingName}</div>
        		<div class="clear"></div>
        	</div>
        	<div class="crm-section no-label billing_address-section">
        		<div class="content">{$address|nl2br}</div>
        		<div class="clear"></div>
        	</div>
        </div>
    {/if}

    {if $contributeMode eq 'direct' and $paidEvent and ! $is_pay_later and !$isAmountzero and !$isOnWaitlist and !$isRequireApproval}
        <div class="crm-group credit_card-group">
            <div class="header-dark">
                {ts}Credit Card Information{/ts}
            </div>
            <div class="crm-section no-label credit_card_details-section">
                <div class="content">{$credit_card_type}</div>
        		<div class="content">{$credit_card_number}</div>
        		<div class="content">{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}</div>
        		<div class="clear"></div>
        	</div>
        </div>
    {/if}
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
		{foreach from=$events_in_carts item=event_in_cart}
		  		<tr>
		  <td>
			{$event_in_cart->event->title} ({$event_in_cart->event->start_date|date_format:"%D"})<br />
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
			{$event_in_cart->event->start_date|date_format:"%D %I:%M %p"} - {$event_in_cart->event->end_date|date_format:"%I:%M %p"}
		  </td>
		  <td>
			{if $event_in_cart->num_not_waiting_participants() > 0}
			  <div class="participants" style="padding-left: 10px;">
				{foreach from=$event_in_cart->not_waiting_participants() item=participant}
				  {$participant->first_name} {$participant->last_name}<br />
				{/foreach}
			  </div>
			{/if}
			{if $event_in_cart->num_waiting_participants() > 0}
			  Waitlisted:<br/>
			  <div class="participants" style="padding-left: 10px;">
				{foreach from=$event_in_cart->waiting_participants() item=participant}
				  {$participant->first_name} {$participant->last_name}<br />
				{/foreach}
			  </div>
			{/if}
		  </td>
		  <td>
			{$event_in_cart->cost|crmMoney:$currency|string_format:"%10s"}
		  </td>
		  <td>
			{$event_in_cart->num_not_waiting_participants()}
		  </td>
		  <td>
			&nbsp;{$event_in_cart->amount|crmMoney:$currency|string_format:"%10s"}
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
			<strong>&nbsp;{$transaction->total_amount|crmMoney:$currency|string_format:"%10s"}</strong>
		  </td>
		</tr>
      </tfoot>
    </table>
  
  <p><strong>Comments:</strong> If you are paying by check, please send payments to CompassPoint Nonprofit Services, 731 Market Street, Suite 200, San Francisco, CA 94103 501c Tax Deductible</p>
	
  <p>If you have questions about the status of your registration or purchase please call 415.541.9000.</p>
</div>