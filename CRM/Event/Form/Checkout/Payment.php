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
require_once 'CRM/Event/BAO/Cart.php';

class CRM_Event_Form_Checkout_Payment extends CRM_Event_Form_Checkout
{
  public $contribution_type_id;
  public $description;
  public $line_items;
  public $_fields = array();
  public $_paymentProcessor;
  public $total;
  public $sub_total;
  public $discounts = array();
  public $discount_amount_total = 0;
  public $discount_code_uses = 0;
  public $discount_code;
  public $payment_required = true;

  function addParticipant( $params, $mer_participant, $event ) 
  {
	require_once 'CRM/Core/Transaction.php';
	require_once 'CRM/Contact/BAO/Contact.php';

	$eventID = $event->id;

	$contact = CRM_Contact_BAO_Contact::matchContactOnEmail( $mer_participant->email );
	if ($contact == null)
	{
	  $contact = $this->createNewContact( $mer_participant );
	}

	$contactID = $contact->contact_id;
	$mer_participant->contact_id = $contact->contact_id;

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

	if ( $mer_participant->must_wait ) {
	  $waiting_statuses = CRM_Event_PseudoConstant::participantStatus( null, "class = 'Waiting'" );
	  $params['participant_status_id'] = array_search( 'On waitlist', $waiting_statuses );
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
	  'fee_level'     => $participant->fee_level,
	  'is_pay_later'  => CRM_Utils_Array::value( 'is_pay_later', $params, 0 ),
	  'fee_amount'    => $params['amount'],
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

	require_once 'CRM/Event/BAO/Participant.php';
	$participant = CRM_Event_BAO_Participant::create($participantParams);

	if ( $params['contributionID'] != null ) {
	  require_once 'CRM/Event/BAO/ParticipantPayment.php';
	  $payment_params = array(
		'participant_id' => $participant->id,
		'contribution_id' => $params['contributionID'],
	  );
	  $ids = array( );
	  $paymentParticpant = CRM_Event_BAO_ParticipantPayment::create( $payment_params, $ids );
	}

	if ( $event->event_type_id == 1 ) {
	  $this->addParticipantToConferenceEvents( $mer_participant, $participantParams );
	}
	$custom_values = array
	(
	  'entityID' => $params['contributionID'],
	  'custom_35' => $mer_participant->used_coupon,
	);
	require_once 'CRM/Core/BAO/CustomValueTable.php';
	CRM_Core_BAO_CustomValueTable::setValues( $custom_values );
	$custom_values = array
	(
	  'entityID' =>  $params['contributionID'],
	  'custom_36' => $mer_participant->used_discount,
	);
	CRM_Core_BAO_CustomValueTable::setValues( $custom_values );

	$transaction->commit( );

	return $participant;
  }

  function addParticipantToConferenceEvents( $mer_participant, $participantParams )
  {
	$conference_participants_events = $this->get( 'conference_participants_events' );
	$mer_participants_events = $conference_participants_events[$mer_participant->index];
	foreach ( $mer_participants_events as $event_id ) {
	  $participantParams['event_id'] = $event_id;
	  $participantParams['fee_amount'] = null;
	  $participant = CRM_Event_BAO_Participant::create($participantParams);
	}
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
	require_once 'CRM/Core/BAO/CustomValueTable.php';

	$this->line_items = array();
	$this->sub_total = 0;
	$mer_participants_by_email = array();
	$price_values = $this->getValuesForPage( 'ParticipantsAndPrices' );
	$this->discount_code = $price_values["discountcode"];
		
	// iterate over each event in cart
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  $price_set_id = CRM_Price_BAO_Set::getFor( "civicrm_event", $event_in_cart->event_id );
	  $amount_level = null;
	  if ( $price_set_id === false ) {
		CRM_Core_OptionGroup::getAssoc( "civicrm_event.amount.{$event_in_cart->event_id}", $fee_data, true );
		$cost = $fee_data[$price_values["event_{$event_in_cart->event_id}_amount"]]['value'];
	  } else {
		$event_price_values = array();
		foreach ( $price_values as $key => $value ) {
		  if ( preg_match( "/event_{$event_in_cart->event_id}_(price.*)/", $key, $matches ) ) {
			$event_price_values[$matches[1]] = $value;
		  }
		}
		$price_sets = CRM_Price_BAO_Set::getSetDetail( $price_set_id, true );
		$price_set = $price_sets[$price_set_id];
		$price_set_amount = array( );
		CRM_Price_BAO_Set::processAmount( $price_set['fields'], $event_price_values, $price_set_amount );
		$price_set_index = end(array_keys($event_price_values['amount_priceset_level_radio']));
		$cost = $event_price_values['amount'];
		$amount_level = $event_price_values['amount_level'];
	  }
	  $custom_field_name = 'custom_46';
	  $custom_field_params = array(
	    'entityID' => $event_in_cart->event_id,
	    $custom_field_name => 1
	  );
	  $values = CRM_Core_BAO_CustomValueTable::getValues($custom_field_params);
	  $eligible_for_20_discount = $values[$custom_field_name];
	  
	  // iterate over each paticipant in event
	  foreach ($event_in_cart->participants as $mer_participant) {
		$mer_participant->cost = $cost;
		$mer_participant->fee_level = $amount_level;
		if ( !$mer_participant->must_wait ) {
		  if ($eligible_for_20_discount) {
		      if ( !array_key_exists( $mer_participant->email, $mer_participants_by_email ) )
		      {
			    $mer_pariticpants_by_email[$mer_participant->email] = array( );
		      }
		      $mer_participants_by_email[$mer_participant->email][] = $mer_participant;
		  }
		  // discount validation and application
		  $event_id = $price_values["event_id"];
		  $discount = $this->get_discount_amount($this->discount_code,$event_in_cart->event_id,$mer_participant->cost,$price_set_index);
		  if ($discount) {
			$participant_name = "{$mer_participant->first_name} {$mer_participant->last_name}";
			$mer_participant->discount_amount += $discount['amount'];
			$mer_participant->used_coupon = true;
			$this->discount_amount_total += $discount['amount'];
			$this->discounts[] = array(
			  'amount' => $discount['amount'],
			  'title' => $discount['type'].' discount ('.$this->discount_code.') for ' . $participant_name . ' (' . $mer_participant->email . ')',
			  'cid' => $discount['cid'],
			  'contact_id' => $mer_participant->contact_id,
			);
			$this->discount_code_uses++;
		  }
		}
	  }

	  $num_participants = $event_in_cart->num_not_waiting_participants( );
	  $amount = $cost * $num_participants;
	  $this->line_items[] = array( 
		'amount' => $amount,
		'cost' => $cost,
		'event' => $event_in_cart->event,
		'participants' => $event_in_cart->not_waiting_participants( ),
		'num_participants' => $num_participants, 
		'num_waiting_participants' => $event_in_cart->num_waiting_participants( ),
		'waiting_participants' => $event_in_cart->waiting_participants( ),
	  );
	  
	  $this->sub_total += $amount;
	}
	foreach ($mer_participants_by_email as $participant_email => $mer_participants) {
	  // auto discount
	  if ( count( $mer_participants ) >= 3 )
	  {
		$participant_name = null;
		$total_discount_for_participant = 0;
		foreach ( $mer_participants as $mer_participant )
		{
		  $orig_cost = $mer_participant->cost;
		  $mer_participant->discount_amount += round($mer_participant->cost * 0.20, 2);
		  $mer_participant->used_discount = true;
		  $total_discount_for_participant += $mer_participant->discount_amount;
		  $participant_name = "{$mer_participant->first_name} {$mer_participant->last_name}";
		}
		$this->discount_amount_total += $total_discount_for_participant;
		$this->discounts[] = array(
		  'amount' => $total_discount_for_participant,
		  'title' => '20% discount for ' . $participant_name . ' (' . $participant_email . ')',
		  'contact_id' => $mer_participant->contact_id,
		  //TODO 'cid' => AUTO_DISCOUNT_CID,
		);
	  }
	}
	/* apply discounts */
	$this->total = $this->sub_total - $this->discount_amount_total;
	if ($this->total > 0) {
	  $this->payment_required = true;
	  $this->buildPaymentFields( );
	} else {
	  $this->payment_required = false;
	}
	$this->assign( 'payment_required', $this->payment_required );
	$this->assign( 'line_items', $this->line_items );
	$this->assign( 'sub_total', $this->sub_total );
	$this->assign( 'total', $this->total );
	$this->assign( 'discounts', $this->discounts );
	$buttons = array( );
	$buttons[] = array(
	  'name' => ts('<< Go Back'),
	  'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp',
	  'type' => 'back',
	);	
	$buttons[] = array(
	  'isDefault' => true,
	  'name' => ts('Complete Transaction >>'),
	  'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
	  'type' => 'next',
	);

  	global $user;
  	if (in_array('administrator', array_values($user->roles)) && $this->total > 0) {
		$this->add('text', 'billing_contact_email', 'Billing Email','', true );
  	}
	$this->addButtons( $buttons );

	$this->addFormRule( array( 'CRM_Event_Form_Checkout_Payment', 'formRule' ), $this );
  }

  function createNewContact( $mer_participant )
  {
	require_once 'CRM/Contact/BAO/Group.php';

	$params = array( 'name' => 'RegisteredByOther' );
	$values = array( );
	$group = CRM_Contact_BAO_Group::retrieve( $params, $values );
	$add_to_groups = array( );
	if ( $group != null ) {
	  $add_to_groups[] = $group->id;
	}

	$defaults = array( );
	// add the employer id of the signed in user 
	$params = array( );
	$params = array( 'id' => $this->getContactID() );
	$registering_contact = CRM_Contact_BAO_Contact::retrieve( $params, $defaults );
	$params = array( );
	if ($registering_contact->employer_id) {
	  $params['current_employer_id'] = $registering_contact->employer_id;
	}
	$params['email-Primary'] = $mer_participant->email;
	$params['first_name'] = $mer_participant->first_name;
	$params['last_name'] = $mer_participant->last_name;
	$fields = array( );
	$contactID =& CRM_Contact_BAO_Contact::createProfileContact( $params, $fields, null, $add_to_groups );
	$contact = CRM_Contact_BAO_Contact::matchContactOnEmail( $mer_participant->email );
	return $contact;
  }

  function getDefaultFrom( )
  {
	require_once 'CRM/Core/OptionGroup.php';
	$values = CRM_Core_OptionGroup::values('from_email_address');
	return $values[1];
  }

  function emailParticipant( $contact_id, $event_in_cart, $participant, $mer_participant )
  {
	if ( !$event_in_cart->event->is_email_confirm ) {
	  return;
	}
	require_once 'CRM/Contact/BAO/Contact.php';
	require_once 'CRM/Core/BAO/MessageTemplates.php';
	$params = array
	(
	  'entity_id' => $event_in_cart->event->id,
	  'entity_table' => 'civicrm_event',
	);
	$location_values = CRM_Core_BAO_Location::getValues( $params, true );
	$event_values = array( );
	CRM_Core_DAO::storeValues( $event_in_cart->event, $event_values );
	$contact_details = CRM_Contact_BAO_Contact::getContactDetails( $participant->contact_id );
	if ($this->payment_required) {
	  $payer_contact_details = CRM_Contact_BAO_Contact::getContactDetails( $contact_id );
	  $payer_values = array
	  (
		'email' => $payer_contact_details[1],
		'name' => $payer_contact_details[0],
	  );
	} else {
	  $payer_values = array
	  (
		'email' => '',
		'name' => '',
	  );
	}
	$from = "{$event_values['confirm_from_name']} <{$event_values['confirm_from_email']}>";
	if (!$event_values['confirm_from_email']) {
	  $from = $this->getDefaultFrom( );
	}
	$bcc = CRM_Utils_Array::value( 'bcc_confirm',  $event_values );
	if (trim($bcc) != '') {
	  $bcc .= ", ";
	}
	$bcc .= "webmaster@compasspoint.org";
	$send_template_params = array
	(
	  'bcc' => $bcc,
	  'cc' => CRM_Utils_Array::value( 'cc_confirm',  $event_values ),
	  'contactId' => $participant->contact_id,
	  'isTest' => false,
	  'from' => $from,
	  'groupName' => 'msg_tpl_workflow_event',
	  'toEmail' => $contact_details[1],
	  'toName' => $contact_details[0],
	  'tplParams' => array
	  (
		'email' => $contact_details[1],
		'event' => $event_values,
		'is_pay_later' => false,
		'isOnWaitlist' => $mer_participant->must_wait,
		'isShowLocation' => true,
		'isRequireApproval' => false,
		'location' => $location_values,
		'name' => $contact_details[0],
		'participant' => $participant,
		'payer' => $payer_values,
	  ),
	  'valueName' => 'event_online_receipt',
	);
	CRM_Core_BAO_MessageTemplates::sendTemplate( $send_template_params );
  }

  function emailReceipt( $contact_id, $events_in_cart, $trxn, $params )
  {
	require_once 'CRM/Contact/BAO/Contact.php';
	require_once 'CRM/Core/BAO/MessageTemplates.php';
	$contact_details = CRM_Contact_BAO_Contact::getContactDetails( $contact_id );
	$state_province = new CRM_Core_DAO_StateProvince();
	$state_province->id = $params['billing_state_province_id-'];
	$state_province->find( );
	$state_province->fetch( );
	$country = new CRM_Core_DAO_Country();
	$country->id = $params['billing_country_id-'];
	$country->find( );
	$country->fetch( );
	foreach ( $this->line_items as &$line_item ) {
	  $location_params = array( 'entity_id' => $line_item['event']->id, 'entity_table' => 'civicrm_event' );
	  $line_item['location'] = CRM_Core_BAO_Location::getValues( $location_params, true );
	}
	$send_template_params = array
	(
	  'bcc' => 'webmaster@compasspoint.org',		
	  'contactId' => $contact_id,
	  'from' => $this->getDefaultFrom( ),
	  'groupName' => 'msg_tpl_workflow_event',
	  'isTest' => false,
	  'toEmail' => $contact_details[1],
	  'toName' => $contact_details[0],
	  'tplParams' => array
	  (
		'billing_name' => "{$params['billing_first_name']} {$params['billing_last_name']}",
		'billing_city' => $params['billing_city-'],
		'billing_country' => $country->name,
		'billing_postal_code' => $params['billing_postal_code-'],
		'billing_state' => $state_province->abbreviation,
		'billing_street_address' => "{$params['billing_street_address-']}", 
		'credit_card_exp_date' => $params['credit_card_exp_date'],
		'credit_card_type' => $params['credit_card_type'],
		'credit_card_number' => "************" . substr($params['credit_card_number'], -4, 4),
		'discounts' => $this->discounts,
		'email' => $contact_details[1],
		'events_in_cart' => $events_in_cart,
		'line_items' => $this->line_items,
		'name' => $contact_details[0],
		'trxn' => $trxn,
	  ),
	  'valueName' => 'event_registration_receipt',
	);
	$template_params_to_copy = array
	(
	  'billing_name',
	  'billing_city',
	  'billing_country',
	  'billing_postal_code',
	  'billing_state',
	  'billing_street_address',
	  'credit_card_exp_date',
	  'credit_card_type',
	  'credit_card_number',
	);
	foreach ( $template_params_to_copy as $template_param_to_copy ) {
	  $this->set( $template_param_to_copy, $send_template_params['tplParams'][$template_param_to_copy]);
	}
	CRM_Core_BAO_MessageTemplates::sendTemplate( $send_template_params );
  }

  static function formRule( $fields, $files, $self ) 
  {
	$errors = array( );

	if ($self->payment_required)
	{	  
	  require_once 'CRM/Core/BAO/PaymentProcessor.php';
	  require_once 'CRM/Core/Payment/Form.php';
	  $payment =& CRM_Core_Payment::singleton( $self->_mode, $self->_paymentProcessor, $this );
	  $error = $payment->checkConfig( $self->_mode );
	  if ( $error ) {
		$errors['_qf_default'] = $error;
	  }

	  // Validate that the billing contact email is valid
	  if ( CRM_Utils_Array::value( 'billing_contact_email', $fields ) ) {
		  $contact_details = CRM_Contact_BAO_Contact::matchContactOnEmail( $fields['billing_contact_email'] );
		  if ($contact_details == NULL) {
			  $errors['billing_contact_email'] = ts( "Billing contact email does not appear to belong to a valid user." );
		  }
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
	}

	return empty( $errors ) ? true : $errors;
  }

  function postProcess( ) {
	require_once 'CRM/Contact/BAO/Contact.php';
	require_once 'CRM/Contribute/BAO/Contribution.php';
	require_once 'CRM/Contribute/PseudoConstant.php';
	require_once 'CRM/Core/BAO/CustomValueTable.php';
	require_once 'CRM/Core/Config.php';
	require_once 'CRM/Core/Transaction.php';
	require_once 'CRM/Core/BAO/FinancialTrxn.php';
	require_once 'CRM/Event/Form/Registration/Confirm.php';
	require_once 'CRM/Event/PseudoConstant.php';
	require_once 'CRM/Utils/Rule.php';
	$transaction = new CRM_Core_Transaction( );
	$trxn = null;
	// mark redemptions of discount code
	$this->redeem_discount();
	$params = $this->_submitValues;
	$contribution_statuses = CRM_Contribute_PseudoConstant::contributionStatus( null, 'name' );
	if ( $params['billing_contact_email'] ) {
	  // get the contact ID from $this->billing_contact_email
	  // get contactID from email address
	  $contact_details = CRM_Contact_BAO_Contact::matchContactOnEmail( $params['billing_contact_email'] );
	  $contact_id = $contact_details->contact_id;
	} else {
	  $contact_id = parent::getContactID( );
	}
	$now = date( 'YmdHis' );
	$params['invoiceID'] = md5(uniqid(rand(), true));
	if ($this->payment_required) 
	{
	  $payment =& CRM_Core_Payment::singleton( $this->_mode, $this->_paymentProcessor, $this );
	  CRM_Core_Payment_Form::mapParams( "", $params, $params, true );
	  $params['contribution_type_id'] = $this->contribution_type_id;
	  $params['description'] = $this->description;
	  $params['amount'] = $this->total;
	  $params['month'] = $params['credit_card_exp_date']['M'];
	  $params['year'] = $params['credit_card_exp_date']['Y'];
	  $result =& $payment->doDirectPayment( $params );
	  if ( is_a( $result, 'CRM_Core_Error' ) ) {
		CRM_Core_Error::displaySessionError( $result );
		CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/event/cart_checkout', "_qf_Payment_display=1&qfKey={$this->controller->_key}", true, null, false ) );
		return;
	  }
	  $trxnParams = array
	  (
		'trxn_date'         => $now,
		'trxn_type'         => 'Debit',
		'total_amount'      => $params['amount'],
		'fee_amount'        => CRM_Utils_Array::value( 'fee_amount', $result ),
		'net_amount'        => CRM_Utils_Array::value( 'net_amount', $result, $params['amount'] ), 
		'currency'          => $params['currencyID'],
		'payment_processor' => $this->_paymentProcessor['payment_processor_type'],
		'trxn_id'           => $result['trxn_id'],
	  );
	  $trxn = new CRM_Core_DAO_FinancialTrxn();
	  $trxn->copyValues($trxnParams);
	  if (! CRM_Utils_Rule::currencyCode($trxn->currency)) {
		$config = CRM_Core_Config::singleton();
		$trxn->currency = $config->defaultCurrency;
	  }
	  $trxn->save();
	  $credit_card_types = array_flip(CRM_Core_OptionGroup::values('accept_creditcard')); 
	  $credit_card_type_id = $credit_card_types[$params['credit_card_type']];
	  $this->set( 'transaction_id', $trxn->id );
	  $this->emailReceipt( $contact_id, $this->events_in_carts, $trxn, $params );
	}
	$this->set( 'last_event_cart_id', $this->cart->id );
	$this->cart->completed = true;
	$this->cart->save( );
	$participant_values = $this->getValuesForPage( 'ParticipantsAndPrices' ); 
	$index = 0;
	$participant_ids = array( );
	if ($trxn == null) {
	  $trxn_id = strftime("VR%Y%m%d%H%M%S");
	} else {
	  $trxn_id = $trxn->trxn_id;
	}
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  foreach ( $event_in_cart->participants as $mer_participant ) {
		$is_voucher = ($params['amount'] == 0);
		$index += 1;
		$params['amount'] = 0;
		$params['contributionID'] = null;
		$params['contributionTypeID'] = null;
		$params['receive_date'] =  null;
		$params['trxn_id'] = null;
		$params['status_id'] = ($mer_participant->must_wait ? 7 /*TODO*/ : 1);

		if ($mer_participant->must_wait) {
		    $participant = $this->addParticipant( $params, $mer_participant, $event_in_cart->event );
		    $participant_ids[] = $participant->id;
		    $this->emailParticipant( $contact_id, $event_in_cart, $participant, $mer_participant );
		    continue;
		}

		$params['amount'] = $mer_participant->cost - $mer_participant->discount_amount;
		$sub_trxn_id = "$trxn_id-$index";
		$payment_instrument_id = 1;
		if ( $is_voucher ) {
		  $payment_instrument_id = 7;
		}
		$contribParams = array
		(
		  'contact_id' => $contact_id,
		  'contribution_type_id' => $event_in_cart->event->contribution_type_id,
		  'receive_date' => $now,
		  'total_amount' => $params['amount'],
		  'amount_level' => $mer_participant->fee_level,
		  'fee_amount' => $mer_participant->cost,
		  'net_amount' => $params['amount'],
		  'invoice_id' => "{$params['invoiceID']}-$index",
		  'trxn_id' => $sub_trxn_id,
		  'currency' => $params['currencyID'],
		  'source' => $event_in_cart->event->title,
		  'contribution_status_id' => array_search( 'Completed', $contribution_statuses ),
		  'payment_instrument_id' => $payment_instrument_id,
		);
		$contribution =& CRM_Contribute_BAO_Contribution::add( $contribParams, $ids );
		if ( is_a( $contribution, 'CRM_Core_Error' ) ) {
		  CRM_Core_Error::fatal( ts( "There was an error creating a contribution record for your event. Please report this error to CompassPoint. Details:\n" . dlog_debug_var( $contribution ) ) );
		}
		$custom_values = array
		(
		  'entityID' => $event_in_cart->event->id,
		  'custom_2' => 1,
		);
		$result = CRM_Core_BAO_CustomValueTable::getValues( $custom_values );
		$event_gl_code = $result['custom_2'];
		$custom_values = array
		(
		  'entityID' => $contribution->id,
		  'custom_15' => $event_gl_code,
		);
		CRM_Core_BAO_CustomValueTable::setValues( $custom_values );
		$custom_values = array
		(
		  'entityID' => $contribution->id,
		  'custom_28' => $credit_card_type_id,
		);
		CRM_Core_BAO_CustomValueTable::setValues( $custom_values );
		$params['contributionID'] = $contribution->id;
		$params['contributionTypeID'] = $contribution->contribution_type_id;
		$params['receive_date'] =  $contribution->receive_date;
		$params['trxn_id'] = $contribution->trxn_id;
		if ( $trxn != null ) {
		  $entity_financial_trxn_params = array(
			'entity_table'      => "civicrm_contribution",
			'entity_id'         => $contribution->id,
			'financial_trxn_id' => $trxn->id,
			'amount'            => $params['amount'],
			'currency'          => $trxn->currency,
		  );
		  $entity_trxn =& new CRM_Core_DAO_EntityFinancialTrxn();
		  $entity_trxn->copyValues($entity_financial_trxn_params);
		  $entity_trxn->save();
		}
		$participant = $this->addParticipant( $params, $mer_participant, $event_in_cart->event );
		$participant_ids[] = $participant->id;
		$this->emailParticipant( $contact_id, $event_in_cart, $participant, $mer_participant );
	  }
	}
	$this->saveDataToSession( $participant_ids, $trxn_id );
	$transaction->commit();
  }

  function saveDataToSession( $participant_ids, $trxn_id )
  {
	$this->set( 'participant_ids', $participant_ids );
	$session_line_items = array( );
	foreach ( $this->line_items as $line_item ) {
	  $session_line_item = array();
	  $session_line_item['amount'] = $line_item['amount'];
	  $session_line_item['cost'] = $line_item['cost'];
	  $session_line_item['event_id'] = $line_item['event']->id;
	  $session_line_items[] = $session_line_item;
	}
	$this->set( 'line_items', $session_line_items );
	$this->set( 'discounts', $this->discounts );
	$this->set( 'payment_required', $this->payment_required );
	$this->set( 'trxn_id', $trxn_id );
	$this->set( 'total', $this->total );
  }

  function setDefaultValues()
  {
	require_once 'CRM/Core/Config.php';
	require_once 'CRM/Contact/BAO/Contact.php';
	require_once 'CRM/Event/BAO/MerParticipant.php';

	$defaults = array( );
	$defaults = parent::setDefaultValues();
	$defaults['billing_first_name'] = $defaults['first_name'];
	$defaults['billing_middle_name'] = $defaults['billing_middle_name']; 
	$defaults['billing_last_name'] = $defaults['last_name'];

        $params = array( 'id' => parent::getContactID() );
        $contact = CRM_Contact_BAO_Contact::retrieve( $params, $defaults );

	$billing_address = CRM_Event_BAO_MerParticipant::billing_address_from_contact($contact);

	if ($billing_address != null) {
	    $defaults['billing_street_address-'] = $billing_address['street_address'];
	    $defaults['billing_city-'] = $billing_address['city'];
	    $defaults['billing_postal_code-'] = $billing_address['postal_code'];
	    $defaults['billing_state_province_id-'] = $billing_address['state_province_id'];
	    $defaults['billing_country_id-'] = $billing_address['country_id'];
	} else {
	    $config = CRM_Core_Config::singleton();
	    $default_country = new CRM_Core_DAO_Country();
	    $default_country->iso_code = $config->defaultContactCountry();
	    $default_country->find(true);
	    $defaults['billing_country_id-'] = $default_country->id;
	}
	return $defaults;
      }
  
  /** 
   * Calculate discount code amounts to apply
   */
  function get_discount_amount($code,$eventID,$price,$priceSetID) {
    $discount = array();
	$query = "SELECT cid, code, description, amount, amount_type, events, pricesets, memberships, organization, autodiscount, count_use, count_max, expiration FROM {civievent_discount} WHERE code = '".stripslashes($code)."'";
	$result = db_query($query);
	$row = db_fetch_array($result, MYSQL_ASSOC);
	
    if (!$row) {
      $errors['discountcode'] = ts('Discount code is invalid.');
    } else {
	$events = unserialize($row['events']); 
	$priceSets = unserialize($row['pricesets']);
	$discount['cid'] = $row['cid'];
	if (intval($row['expiration']) > 0 && (time() > strtotime($row['expiration']))) {
	  $errors['discountcode'] = ts('Code has expired.');
	} else if ($row["count_max"] > 0 && $row['count_use'] && ($row['count_use'] + $this->discount_code_uses >= $row["count_max"])) {
	  $errors['discountcode'] = ts('Max uses exceeded for discount code.');
	} else if (! empty($events) && ! in_array($eventID,$events) ) {
	  $errors['discountcode'] = ts('Code not valid for this event.');  
	} else if (! empty($priceSets) && ! in_array($priceSetID, $priceSets) ) {
	  $errors['discountcode'] = ts('Code not valid for this price set.');
	} else {
	  // get the discount amount
	  if ($row['amount_type'] == 'P') {
		// calculate percentage discount
		$discount['amount'] =  $price * ($row['amount'] * .01);
		$discount['type'] = $row['amount'].'%';
	  } else if ($row['amount_type'] == 'M') {
		// do a straight subtraction.
		$discount['amount'] = $row['amount'];
		$discount['type'] = '$'.$row['amount'];
	  }
	}
    }
    return $discount;
  }
  
  function redeem_discount()
  {
    foreach ($this->discounts as $discount) {
	civievent_discount_increment_counter($discount['cid']);
	$track = array(
	    'contact_id' => $discount['contact_id'], //TODO contact may not have been created yet
	    'cid' => $discount['cid'],
	    'amount' => $discount['amount'],
	    'type' => 'Event',
	    'description' => $discount['title'],
	);
	civievent_discount_set_tracking($track);
    }
  }
}
