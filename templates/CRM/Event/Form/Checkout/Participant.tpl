  <div class="participant" id="event_in_cart_{$event_in_cart->id}_participant_{$participant->index}">
    {$participant->name()}
    {if $participant->index == 0}
      {$contact->email}    
    {/if}
    {assign var=fields value=$participant->fields}
    {foreach from=$fields item=field}
      {assign var=field_name value=$field->_attributes.name}
      <label for="{$field->_attributes.id}">
	{$form.$field_name.label}
      </label>
      {$form.$field_name.html}
    {/foreach}
  </div>
