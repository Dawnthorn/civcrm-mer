{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}

{foreach from=$events_in_carts key=index item=event_in_cart}
  <fieldset class="price_form">
    <legend>
      {$event_in_cart->event->title}
    </legend>
    {if $event_in_cart->event->is_monetary }
      <div class="price_choices">
	{assign var=event_id value=$event_in_cart->event_id}
	{foreach from=$price_fields_for_event.$event_id key=price_index item=price_field_name}
	  <div class="label">
	    {$form.$price_field_name.label}
	  </div>
	  <div class="label">
	    {$form.$price_field_name.html}
	  </div>
	{/foreach}
      </div>
    {else}
      There is no charge for this event.
    {/if}
  </fieldset>
{/foreach}

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
