{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}

{foreach from=$events_in_carts key=index item=event_in_cart}
  <fieldset class="event_form">
    <legend>
      {$event_in_cart->event->title}
    </legend>
    <div class="participants" id="event_in_cart_{$event_in_cart->id}_participants">
      {foreach from=$event_in_cart->participants item=participant}
	{include file="CRM/Event/Form/Checkout/Participant.tpl}
      {/foreach}
    </div>
    <a href="#" onclick="add_participant({$event_in_cart->id});">add participant</a>
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

{literal}
<script type="text/javascript">
//<![CDATA[
function add_participant( event_in_cart_id ) {
  var max_index = 0;
  var matcher = new RegExp("event_in_cart_" + event_in_cart_id + "_participant_(\\d+)");
  
  $('#event_in_cart_' + event_in_cart_id + '_participants .participant').each(
    function(index) {
      matches = matcher.exec($(this).attr('id'));
      index = parseInt(matches[1]);
      if (index > max_index)
      {
        max_index = index;
      }
    }
  );

  $.get("/civicrm/ajax/event/add_participant_to_cart?event_in_cart_id=" + event_in_cart_id + "&index=" + (max_index + 1), 
    function(data) {
      $('#event_in_cart_' + event_in_cart_id + '_participants').append(data);
    }
  );
}

$('#ajax_error').ajaxError(
  function( e, xrh, settings, exception ) {
    $(this).append('<div class="error">Error adding a participant at ' + settings.url + ': ' + exception);
  }
);
//]]>
</script>
{/literal}
