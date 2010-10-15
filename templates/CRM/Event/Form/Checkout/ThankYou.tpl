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
    <div class="participants">
      <div>
	Participants:
      </div>
      {foreach from=$event_in_cart->participants item=participant}
	<div class="participants">
	  {$participant->email}
	</div>
      {/foreach}
    </div>
  {/foreach}
</div>

<hr/>

<div class="contribution">
  Total: {$contribution->total_amount|crmMoney}<br/>
  Transaction Date: {$contribution->receive_date|crmDate}<br/>
  Transaction #: {$contribution->trxn_id}
</div>

