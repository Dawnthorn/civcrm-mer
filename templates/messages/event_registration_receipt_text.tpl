Transaction ID: {$transaction_id}

{foreach from=$line_items item=line_item}
{$line_item.event->title} ({$line_item.event->start_date}):
  {$line_item.num_participants}
{if $line_item.num_participants > 0}
  {foreach from=$line_item.participants item=participant}
    {$participant->first_name} {$participant->last_name}
  {/foreach}
{/if}
{if $line_item.num_waiting_participants > 0}
  Waitlisted:<br/>
    {foreach from=$line_item.waiting_participants item=participant}
      {$participant->first_name} {$participant->last_name}
    {/foreach}
{/if}
Cost: {$line_item.cost}
Total For This Event: {$line_item.amount}

{/foreach}

{if $discounts}
Subtotal: {$sub_total}
--------------------------------------
Discounts
{foreach from=$discounts key=myId item=i}
  {$i.title}: -{$i.amount}
{/foreach}
{/if}
======================================
Total: {$total}

