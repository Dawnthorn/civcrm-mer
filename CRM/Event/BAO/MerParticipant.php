<?php

/*
Copyright (C) 2011 Giant Rabbit LLC

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the Free
Software Foundation, either version 3 of the License, or (at your option) any
later version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class CRM_Event_BAO_MerParticipant
{
  public $contact_id;
  public $discount_amount = 0;
  public $cost;
  public $email;
  public $fee_level;
  public $fields = array( );
  public $first_name;
  public $index;
  public $last_name;
  public $must_wait = false;
  public $used_coupon = false;
  public $used_discount = false;

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

  static function billing_address_from_contact( $contact )
  {
        foreach ($contact->address as $loc) {
            if ($loc['is_billing']) return $loc;
        }
        foreach ($contact->address as $loc) {
            if ($loc['is_primary']) return $loc;
        }
        return null;
  }
}
