{include file="CRM/common/TrackingFields.tpl"}

<div id="ajax_error"></div>

<div id="participants">
  {foreach from=$participants item=participant}
    {include file="CRM/Event/Form/Checkout/Participant.tpl}
  {/foreach}
</div>

<a href="#" onclick="add_participant();">add participant</a>

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">
//<![CDATA[
function add_participant( ) {
  var max_index = 0;
  var matcher = /participant_(\d+)/;
  
  $('.participant').each(
    function(index) {
      matches = matcher.exec($(this).attr('id'));
      index = parseInt(matches[1]);
      if (index > max_index)
      {
	max_index = index;
      }
    }
  );

  $.get("/civicrm/ajax/event/add_participant_to_cart?index=" + (max_index + 1), 
    function(data) {
      $('#participants').append(data);
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
