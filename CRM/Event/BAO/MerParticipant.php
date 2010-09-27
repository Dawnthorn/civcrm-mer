<?php

class CRM_Event_BAO_MerParticipant
{
  public $contact_id;
  public $email;
  public $fields = array( );
  public $index;

  function email_field_name( )
  {
    return "participant_{$this->index}_email";
  }

  function load_fields( $form )
  {
    $field_name =     $field = $form->add( 'text', $this->email_field_name( ), ts( 'Email Address' ), array( 'size' => 30, 'maxlength' => 60, true ) );;
    $this->fields[] = $field;
  }

  function load_values( $values )
  {
    $this->email = $values[$this->email_field_name( )];
  }

  function name( )
  {
    if ( $this->index == 0 ) {
      return "Main Participant";
    } else {
      return "Participant {$this->number()}";
    }
  }

  function number( )
  {
    return $this->index + 1;
  }
}
