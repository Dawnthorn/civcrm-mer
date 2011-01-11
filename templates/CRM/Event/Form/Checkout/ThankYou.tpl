{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-block crm-event-thankyou-form-block">
  {foreach from=$events_in_carts item=event_in_cart}
    {$event_in_cart->event->title}
    {if $event_in_cart->event->thankyou_text}
      <div id="intro_text" class="crm-section event_thankyou_text-section">
	<p>
	  {$event_in_cart->event->thankyou_text}
	</p>
      </div>
    {/if}
    {if $event_in_cart->num_not_waiting_participants() > 0}
      <div class="participants">
	<div>
	  Participants:
	</div>
	{foreach from=$event_in_cart->not_waiting_participants() item=participant}
	  <div class="participants">
	    {$participant->first_name} {$participant->last_name}
	  </div>
	{/foreach}
      </div>
    {/if}
    {if $event_in_cart->num_waiting_participants() > 0}
      <div class="waitlisted_participants">
	<div>
	  Waitlisted:
	</div>
	{foreach from=$event_in_cart->waiting_participants() item=participant}
	  <div class="participants">
	    {$participant->first_name} {$participant->last_name}
	  </div>
	{/foreach}
      </div>
    {/if}
  {/foreach}
</div>

<hr/>

<div class="contribution">
  Total: {$transaction->total_amount|crmMoney}<br/>
  Transaction Date: {$transaction->trxn_date|crmDate}<br/>
  Transaction #: {$transaction->trxn_id}
</div>

