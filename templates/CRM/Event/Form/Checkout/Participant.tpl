  <fieldset class="participant" id="event_in_cart_{$event_in_cart->id}_participant_{$participant->index}">
    <legend>
      {$participant->name()}
    </legend>
    {assign var=fields value=$participant->fields}
    {foreach from=$fields item=field}
      {assign var=field_name value=$field->_attributes.name}
      <label for="{$field->_attributes.id}">
	{$form.$field_name.label}
      </label>
      {$form.$field_name.html}
    {/foreach}
    {if $participant->index > 0}
      <a href="#" onclick="delete_participant({$event_in_cart->id}, {$participant->index})">delete</a>
    {/if}
  </fieldset>
