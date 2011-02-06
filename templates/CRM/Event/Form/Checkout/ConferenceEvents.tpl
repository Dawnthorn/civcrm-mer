{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}

<h2>Choose Events For {$mer_participant->first_name} {$mer_participant->last_name} ({$mer_participant->email})</h2>

{foreach from=$slot_fields key=slot_name item=field_name}
  <fieldset>
    <legend>
      {$slot_name}
    </legend>
    <div>
      {$form.$field_name.html}
    </div>
  </fieldset>
{/foreach}    

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
