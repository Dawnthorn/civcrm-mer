<?php

class CRM_Event_BAO_MerParticipant
{
  public $contact_id;
  public $email;
  public $fields = array( );
  public $index;

  function email_field_name( $event_in_cart )
  {
    return "event_in_cart_{$event_in_cart->id}_participant_{$this->index}_email";
  }

  function load_fields( $form, $event_in_cart )
  {
    $field = $form->add( 'text', $this->email_field_name( $event_in_cart ), ts( 'Email Address' ), array( 'size' => 30, 'maxlength' => 60, true ) );;
    $this->fields[] = $field;
  }

  function load_values( $values, $event_in_cart )
  {
    $this->email = $values[$this->email_field_name( $event_in_cart )];
  }

  function name( )
  {
    return "Participant {$this->number()}";
  }

  function number( )
  {
    return $this->index + 1;
  }
}
