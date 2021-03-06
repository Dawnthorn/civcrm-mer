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

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Event/BAO/Cart.php';
require_once 'CRM/Event/Form/Checkout.php';
require_once 'CRM/Price/BAO/Set.php';
      
class CRM_Event_Form_Checkout_ParticipantsAndPrices extends CRM_Event_Form_Checkout
{
  public $price_fields_for_event;

  function buildQuickForm( )
  {
    $this->price_fields_for_event = array();
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
      foreach ( $event_in_cart->participants as $participant ) {
        $participant->load_fields( $this, $event_in_cart );
      }
      $this->price_fields_for_event[$event_in_cart->event_id] = array();
      $base_field_name = "event_{$event_in_cart->event_id}_amount";
      $price_set_id = CRM_Price_BAO_Set::getFor( "civicrm_event", $event_in_cart->event_id );
      if ( $price_set_id === false ) {
        require_once 'CRM/Utils/Money.php';
        CRM_Core_OptionGroup::getAssoc( "civicrm_event.amount.{$event_in_cart->event_id}", $fee_data, true );
        $choices = array();
        foreach ( $fee_data as $fee ) {
          if ( is_array( $fee ) ) {
            $choices[] = $this->createElement( 'radio', null, '', CRM_Utils_Money::format( $fee['value']) . ' ' . $fee['label'], $fee['amount_id'] );
          }
        }
        $this->addGroup( $choices, $base_field_name, "");
        $this->price_fields_for_event[$event_in_cart->event_id][] = $base_field_name;
      } else {
        $price_sets = CRM_Price_BAO_Set::getSetDetail( $price_set_id, true );
        $price_set = $price_sets[$price_set_id];
        $index = -1;
        foreach ( $price_set['fields'] as $field ) {
          $index++;
          $field_name = "event_{$event_in_cart->event_id}_price_{$field['id']}";
          CRM_Price_BAO_Field::addQuickFormElement( $this, $field_name, $field['id'], false, true );
          $this->price_fields_for_event[$event_in_cart->event_id][] = $field_name;
        }
      }
    }
    $this->addElement('text', 'discountcode', ts('If you have a discount code, enter it here'));
    $this->assign( 'events_in_carts', $this->cart->events_in_carts );
    $this->assign( 'price_fields_for_event', $this->price_fields_for_event );
    $this->addButtons( 
      array ( 
      array ( 'type' => 'upload',
      'name' => ts('Continue >>'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'isDefault' => true )
      )
    );
    $this->addFormRule( array( 'CRM_Event_Form_Checkout_ParticipantsAndPrices', 'formRule' ), $this );
  }

  static function formRule( $fields, $files, $self )
  {
    $errors = array();
    
    if (! empty($fields['discountcode'])) {
      // check to see if the code exists
      $query = "SELECT cid FROM {civievent_discount} WHERE code = '".stripslashes($fields['discountcode'])."'";
      $result = db_query($query);
      $row = db_fetch_array($result);
      if (empty($row)) {
        $errors['discountcode'] = ts( "The discount code you've entered does not appear to be valid." );
      }
    }
    
    foreach ( $self->cart->events_in_carts as $event_in_cart ) {
      $price_set_id = CRM_Price_BAO_Set::getFor( "civicrm_event", $event_in_cart->event_id );
      if ( $price_set_id ) {
        $priceField = new CRM_Price_DAO_Field( );
        $priceField->price_set_id = $price_set_id;
        $priceField->find( );
        
        $check = array( );
        
        while ( $priceField->fetch( ) ) {
          if ( ! empty( $fields["event_{$event_in_cart->event_id}_price_{$priceField->id}"] ) ) {
            $check[] = $priceField->id; 
          }
        }
        
        if ( empty( $check ) ) {
          $errors['_qf_default'] = ts( "Select at least one option from Event Fee(s)." );
        }

        $lineItem = array( );
        if ( is_array( $self->_values['fee']['fields'] ) ) {
          CRM_Price_BAO_Set::processAmount( $self->_values['fee']['fields'], $fields, $lineItem );
          if ($fields['amount'] < 0) {
          $errors['_qf_default'] = ts( "Event Fee(s) can not be less than zero. Please select the options accordingly" );
          }
        }
      }
      
      foreach ( $event_in_cart->participants as $mer_participant ) {
        $contact = CRM_Contact_BAO_Contact::matchContactOnEmail( $mer_participant->email );
        if ($contact != null) {
          $participant = new CRM_Event_BAO_Participant();
          $participant->event_id = $event_in_cart->event_id;
          $participant->contact_id = $contact->contact_id;
          $num_found = $participant->find();
          if ($num_found > 0) {
          $errors[$mer_participant->email_field_name( $event_in_cart )] = "The participant {$mer_participant->email} is already registered for {$event_in_cart->event->title} ({$event_in_cart->event->start_date}).";
          }
        }
      }
    }
    return empty( $errors ) ? true : $errors;
  }

  function preProcess( )
  {
    $user_id = $this->getContactID( );
    if ( $user_id === NULL ) {
      CRM_Core_Session::setStatus( ts( "You must log in or create an account to register for events." ) );
      return CRM_Utils_System::redirect( "/user?destination=civicrm/event/cart_checkout&reset=1" );
    }
    else {
      return parent::preProcess( );
    }
  }

  function postProcess( )
  {
	$is_conference = false;
	$conference_participant_indexes = array( );
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  $event_in_cart->load_associations();
	  if ( $event_in_cart->event->event_type_id == 1 ) {
		$is_conference = true;
		foreach ( $event_in_cart->participants as $mer_participant ) {
		  $conference_participant_indexes[] = $mer_participant->index;
		}
		break;
	  }
	}
	if ( $is_conference ) {
	  $this->set( 'conference_participant_indexes', $conference_participant_indexes );
	} else {
	  $this->set( 'conference_participant_indexes', null );
	}
  }
}
