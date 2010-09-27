<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Event/BAO/Cart.php';

class CRM_Event_Form_Checkout_Payment extends CRM_Event_Form_Checkout
{
  public $contribution_type_id;
  public $description;
  public $_fields = array();
  public $_paymentProcessor;
  public $total;

  function addParticipant( $params, $participant, $eventID ) 
  {
    require_once 'CRM/Core/Transaction.php';
    require_once 'CRM/Contact/BAO/Contact.php';

    $contact = CRM_Contact_BAO_Contact::matchContactOnEmail( $participant->email );
    $contactID = $contact->contact_id;

    $transaction = new CRM_Core_Transaction( );

    $groupName = "participant_role";
    $query = "
SELECT  v.label as label ,v.value as value
FROM   civicrm_option_value v, 
       civicrm_option_group g 
WHERE  v.option_group_id = g.id 
  AND  g.name            = %1 
  AND  v.is_active       = 1  
  AND  g.is_active       = 1  
";
    $p = array( 1 => array( $groupName , 'String' ) );

    $dao =& CRM_Core_DAO::executeQuery( $query, $p );
    if ( $dao->fetch( ) ) {
      $roleID = $dao->value;
    }
    
    // handle register date CRM-4320
    $registerDate = date( 'YmdHis' );
    $participantParams = array(
      'id'            => CRM_Utils_Array::value( 'participant_id', $params ),
      'contact_id'    => $contactID,
      'event_id'      => $eventID,
      'status_id'     => CRM_Utils_Array::value( 'participant_status_id', $params, 1 ),
      'role_id'       => CRM_Utils_Array::value( 'participant_role_id', $params, $roleID ),
      'register_date' => ( $registerDate ) ? $registerDate : date( 'YmdHis' ),
      'source'        => isset( $params['participant_source'] ) ?  $params['participant_source']:$params['description'],
      'fee_level'     => $params['amount_level'],
      'is_pay_later'  => CRM_Utils_Array::value( 'is_pay_later', $params, 0 ),
      'fee_amount'    => CRM_Utils_Array::value( 'fee_amount', $params ),
      'registered_by_id' => CRM_Utils_Array::value( 'registered_by_id', $params ),
      'discount_id'      => CRM_Utils_Array::value( 'discount_id', $params ),
      'fee_currency'     => CRM_Utils_Array::value( 'currencyID', $params )
    );

    if ( $this->_action & CRM_Core_Action::PREVIEW || CRM_Utils_Array::value( 'mode', $params ) == 'test' ) {
      $participantParams['is_test'] = 1;
    } else {
      $participantParams['is_test'] = 0;
    }

    if ( CRM_Utils_Array::value( 'note', $this->_params ) ) {
      $participantParams['note'] = $this->_params['note'];
    } else if ( CRM_Utils_Array::value( 'participant_note', $this->_params ) ) {
      $participantParams['note'] = $this->_params['participant_note'];
    }

    // reuse id if one already exists for this one (can happen
    // with back button being hit etc)
    if ( !$participantParams['id'] &&
      CRM_Utils_Array::value( 'contributionID', $params ) ) {
	$pID = CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_ParticipantPayment', 
	  $params['contributionID'], 
	  'participant_id', 
	  'contribution_id' );
	$participantParams['id'] = $pID;
      }
#    require_once 'CRM/Core/BAO/Discount.php';
#    $participantParams['discount_id'] = CRM_Core_BAO_Discount::findSet( $event_id, 'civicrm_event' );

#    if ( !$participantParams['discount_id'] ) {
#      $participantParams['discount_id'] = "null";
#    }

    require_once 'CRM/Event/BAO/Participant.php';
    $participant = CRM_Event_BAO_Participant::create($participantParams);

    $transaction->commit( );

    return $participant;
  }

  function buildPaymentFields( )
  {
    $event_titles = array();
    $payment_processor_id = null;
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
      $event_titles[] = $event_in_cart->event->title;
      if ( $payment_processor_id == null && $event_in_cart->event->payment_processor_id != null ) {
	$payment_processor_id = $event_in_cart->event->payment_processor_id;
	$this->contribution_type_id = $event_in_cart->event->contribution_type_id;
      } else {
	if ( $event_in_cart->event->payment_processor_id != NULL && $event_in_cart->event->payment_processor_id != $payment_processor_id ) {
	  CRM_Core_Error::statusBounce( ts( 'When registering for multiple events all events must use the same payment processor. ') );
	}
      }
    }

    $this->description = "Payment for " . implode( ", ", $event_titles ) . ".";

    if ( $payment_processor_id == null ) {
      CRM_Core_Error::statusBounce( ts( 'A payment processor must be selected for this event registration page, or the event must be configured to give users the option to pay later (contact the site administrator for assistance).' ) );
    }

    require_once 'CRM/Core/BAO/PaymentProcessor.php';
    $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $payment_processor_id, $this->_mode );
    $this->assign( 'paymentProcessor', $this->_paymentProcessor );

    require_once 'CRM/Core/Payment/Form.php';
    CRM_Core_Payment_Form::setCreditCardFields( $this );
    CRM_Core_Payment_Form::buildCreditCard( $this );
  }


  function buildQuickForm( )
  {
    $line_items = array();
    $this->total = 0;
    $price_values = $this->getValuesForPage( 'Prices' );
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
      $price_set_id = CRM_Price_BAO_Set::getFor( "civicrm_event", $event_in_cart->event_id );
      if ( $price_set_id === false ) {
	CRM_Core_OptionGroup::getAssoc( "civicrm_event.amount.{$event_in_cart->event_id}", $fee_data, true );
	$cost = $fee_data[$price_values["event_{$event_in_cart->event_id}_amount"]]['value'];
      } else {
	$price_sets = CRM_Price_BAO_Set::getSetDetail( $price_set_id, true );
	$price_set = $price_sets[$price_set_id];
	$price_set_amount = array( );
	CRM_Price_BAO_Set::processAmount( $price_set['fields'], $price_values, $price_set_amount );
	$cost = $price_values['amount'];
      }
      $num_participants = count( $this->participants );
      $amount = $cost * $num_participants;
      $line_items[] = array( 
	'event' => $event_in_cart->event,
	'num_participants' => $num_participants, 
	'cost' => $cost,
	'amount' => $amount,
      );
      $this->total += $amount;
    }

    $this->buildPaymentFields( );

    $this->assign( 'line_items', $line_items );
    $this->assign( 'total', $total );
    $buttons = array( );
    $buttons[] = array(
      'name' => ts('<< Go Back'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp',
      'type' => 'back',
    );
    $buttons[] = array(
      'isDefault' => true,
      'name' => ts('Continue >>'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'type' => 'next',
    );
    $this->addButtons( $buttons );

    $this->addFormRule( array( 'CRM_Event_Form_Checkout_Payment', 'formRule' ), $this );
  }

  static function formRule( $fields, $files, $self ) 
  {
    $errors = array( );
    $payment =& CRM_Core_Payment::singleton( $self->_mode, 'Event', $self->_paymentProcessor, $this );
    $error = $payment->checkConfig( $self->_mode );
    if ( $error ) {
      $errors['_qf_default'] = $error;
    }

    foreach ( $self->_fields as $name => $field ) {
      if ( $field['is_required'] && CRM_Utils_System::isNull( CRM_Utils_Array::value( $name, $fields ) ) ) {
	$errors[$name] = ts( '%1 is a required field.', array( 1 => $field['title'] ) );
      }
    }

    require_once 'CRM/Utils/Rule.php';

    if ( CRM_Utils_Array::value( 'credit_card_type', $fields ) ) {
      if ( CRM_Utils_Array::value( 'credit_card_number', $fields ) &&
	! CRM_Utils_Rule::creditCardNumber( $fields['credit_card_number'], $fields['credit_card_type'] ) ) {
	  $errors['credit_card_number'] = ts( "Please enter a valid Credit Card Number" );
	}

      if ( CRM_Utils_Array::value( 'cvv2', $fields ) &&
	! CRM_Utils_Rule::cvv( $fields['cvv2'], $fields['credit_card_type'] ) ) {
	  $errors['cvv2'] =  ts( "Please enter a valid Credit Card Verification Number" );
	}
    }
    
    return empty( $errors ) ? true : $errors;
  }

  function postProcess( ) {
    $contact_id = parent::getContactID( );
    $payment =& CRM_Core_Payment::singleton( $this->_mode, 'Event', $this->_paymentProcessor, $this );
    $params = $this->_submitValues;
    CRM_Core_Payment_Form::mapParams( "", $params, $params, true );
    $params['contribution_type_id'] = $this->contribution_type_id;
    $params['description'] = $this->description;
    $params['amount'] = $this->total;
    $params['month'] = $params['credit_card_exp_date']['M'];
    $params['year'] = $params['credit_card_exp_date']['Y'];
    $params['invoiceID'] = md5(uniqid(rand(), true));
    $result =& $payment->doDirectPayment( $params );
    if ( is_a( $result, 'CRM_Core_Error' ) ) {
      CRM_Core_Error::displaySessionError( $result );
      CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/event/cart_checkout', "_qf_Payment_display=1&qfKey={$this->controller->_key}", true, null, false ) );
    }
    require_once 'CRM/Event/Form/Registration/Confirm.php';
    $contribution =& CRM_Event_Form_Registration_Confirm::processContribution( $this, $params, $result, $contact_id, false, false );
    $this->set ('contributionID', $contribution->id );
    $params['contributionID'] = $contribution->id;
    $params['contributionTypeID'] = $contribution->contribution_type_id;
    $params['receive_date'] =  $contribution->receive_date;
    $params['trxn_id'] = $contribution->trxn_id;

    $participant_values = $this->getValuesForPage( 'Participants' ); 
    foreach ( $this->cart->events_in_carts as $event_in_cart ) {
      foreach ( $this->participants as $participant ) {
	$this->addParticipant( $params, $participant, $event_in_cart->event_id );
      }
    }
  }
}
