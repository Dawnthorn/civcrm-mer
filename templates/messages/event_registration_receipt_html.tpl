<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
  </head>
  <body>
    {capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
    {capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
    {capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}
    <p>{ts}Please print this confirmation for your records. Information about
      the workshops will be sent separately to each participant.{/ts}</p>
    <b>Transaction ID:</b> {$transaction_id}
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
      </thead>
      <tbody>
	{foreach from=$line_items item=line_item}
	  <tr>
	    <td>
	      {$line_item.event->title} ({$line_item.event->start_date})
	    </td>
	    <td>
	      {$line_item.num_participants}<br/>
	      {if $line_item.num_participants > 0}
		<div class="participants" style="padding-left: 10px;">
		  {foreach from=$line_item.participants item=participant}
		    {$participant->first_name} {$participant->last_name}
		  {/foreach}
		</div>
	      {/if}
	      {if $line_item.num_waiting_participants > 0}
		Waitlisted:<br/>
		<div class="participants" style="padding-left: 10px;">
		  {foreach from=$line_item.waiting_participants item=participant}
		    {$participant->first_name} {$participant->last_name}
		  {/foreach}
		</div>
	      {/if}
	    </td>
	    <td>
	      {$line_item.cost}
	    </td>
	    <td>
	      &nbsp;{$line_item.amount}
	    </td>
	  </tr>
	{/foreach}
      </tbody>
      <tfoot>
	{if $discounts}
	  <tr>
	    <td>
	    </td>
	    <td>
	    </td>
	    <td>
	      Subtotal:
	    </td>
	    <td>
	      &nbsp;{$sub_total}
	    </td>
	  </tr>
	  {foreach from=$discounts key=myId item=i}
	    <tr>
	      <td>
		{$i.title}
	      </td>
	      <td>
	      </td>
	      <td>
	      </td>
	      <td>
		-{$i.amount}
	      </td>
	    </tr>
	  {/foreach}
	{/if}
	<tr>
	  <td>
	  </td>
	  <td>
	  </td>
	  <td>
	    Total:
	  </td>
	  <td>
	    &nbsp;{$total}
	  </td>
	</tr>
      </tfoot>
    </table>
  </body>
</html>
