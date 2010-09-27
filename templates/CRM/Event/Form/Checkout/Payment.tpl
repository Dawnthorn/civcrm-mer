{include file="CRM/common/TrackingFields.tpl"}

<table>
  <thead>
    <tr>
      <th>
	Event
      </th>
      <th>
	Participants
      </th>
      <th>
	Cost
      </th>
      <th>
	Amount
      </th>
    </tr>
  <thead>
  <tbody>
    {foreach from=$line_items item=line_item}
      <tr>
	<td>
	  {$line_item.event->title}
	</td>
	<td>
	  {$line_item.num_participants}
	</td>
	<td>
	  {$line_item.cost}
	</td>
	<td>
	  {$line_item.amount}
	</td>
      </tr>
    {/foreach}
  </tbody>
  <tfoot>
    <tr>
      <td>
      </td>
      <td>
      </td>
      <td>
	Total:
      </td>
      <td>
	{$total}
      </td>
    </tr>
  </tfoot>
</table>

{include file='CRM/Core/BillingBlock.tpl'}

<div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
