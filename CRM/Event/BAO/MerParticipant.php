<?php

class CRM_Event_BAO_MerParticipant
{
  public $index;
  public $fields = array( );

  function load_fields( $form )
  {
    $field_name = "participant_{$this->index}_email";
    $field = $form->add( 'text', $field_name, ts( 'Email Address' ), array( 'size' => 30, 'maxlength' => 60, true ) );;
    $this->fields[] = $field;
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
