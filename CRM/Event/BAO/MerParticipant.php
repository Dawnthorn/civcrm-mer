<?php

class CRM_Event_BAO_MerParticipant
{
  public $contact_id;
  public $cost;
  public $email;
  public $fields = array( );
  public $first_name;
  public $index;
  public $last_name;
  public $must_wait = false;

  function email_field_name( $event_in_cart )
  {
	return $this->html_field_name( $event_in_cart, "email" );
  }

  static function full_field_name( $event_in_cart, $index, $field_name )
  {
	return "event_in_cart_{$event_in_cart->id}_participant_{$index}_$field_name";
  }

  function html_field_name( $event_in_cart, $field_name )
  {
	return self::full_field_name( $event_in_cart, $this->index, $field_name );
  }

  function load_fields( $form, $event_in_cart )
  {
	$this->fields[] = $form->add( 'text', $this->email_field_name( $event_in_cart ), ts( 'Email Address' ), array( 'size' => 30, 'maxlength' => 60), true );
	$this->fields[] = $form->add( 'text', $this->html_field_name( $event_in_cart, 'first_name' ), ts( 'First Name' ), array( 'size' => 30, 'maxlength' => 60 ), true );
	$this->fields[] = $form->add( 'text', $this->html_field_name( $event_in_cart, 'last_name' ), ts( 'Last Name' ), array( 'size' => 30, 'maxlength' => 60 ), true );
  }

  function load_values( $values, $event_in_cart )
  {
	$this->email = $values[$this->email_field_name( $event_in_cart )];
	$this->first_name = $values[$this->html_field_name( $event_in_cart, 'first_name' ) ];
	$this->last_name = $values[$this->html_field_name( $event_in_cart, 'last_name' ) ];
  }

  function name( )
  {
	return "Participant {$this->number()}";
  }

  function number( )
  {
	return $this->index + 1;
  }

  static function primary_email_from_contact( $contact )
  {
	foreach ( $contact->email as $email ) {
	  if ( $email['is_primary'] ) {
		return $email['email'];
	  }
	}

	return null;
  }
}
