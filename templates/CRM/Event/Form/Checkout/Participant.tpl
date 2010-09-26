  <fieldset class="participant">
    <legend>{$participant->name()}</legend>
    {assign var=fields value=$participant->fields}
    {foreach from=$fields item=field}
      {assign var=field_name value=$field->_attributes.name}
      <label for="{$field->_attributes.id}">
	{$form.$field_name.label}
      </label>
      {$form.$field_name.html}
    {/foreach}
  </fieldset>
