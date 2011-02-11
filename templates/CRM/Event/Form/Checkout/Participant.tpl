  <fieldset class="participant" id="event_in_cart_{$event_in_cart->id}_participant_{$participant->index}">
    <legend>
      {$participant->name()}
    </legend>
	<div class="clearfix">
	  {assign var=fields value=$participant->fields}
	  {foreach from=$fields item=field}
		{assign var=field_name value=$field->_attributes.name}
	  <div class="participant-info">
		{$form.$field_name.label}
		{$form.$field_name.html}
	  </div>
	  {/foreach}
	</div>
    {if $participant->index > 0}
    <a class="link-delete" href="#" onclick="delete_participant({$event_in_cart->id}, {$participant->index})">Delete {$participant->name()}</a>
	{/if}
  </fieldset>
