<?php

class CRM_Event_Form_Checkout_ConferenceEvents extends CRM_Event_Form_Checkout
{
  public $main_conference_event = null;
  public $events_by_slot = array();
  public $mer_participant = null;
  public $mer_participant_index = null;

  function preProcess( )
  {
	parent::preProcess( );
	$matches = array();
	preg_match( "/.*_(\d+)/", $this->_name, $matches );
	$this->mer_participant_index = $matches[1];
	foreach ( $this->cart->events_in_carts as $event_in_cart ) {
	  $event_in_cart->load_associations();
	  $this->main_conference_event = $event_in_cart->event;
	  foreach ( $event_in_cart->participants as $mer_participant ) {
		if ( $mer_participant->index == $this->mer_participant_index ) {
		  $this->mer_participant = $mer_participant;
		  break;
		}
	  }
	}

	$events = new CRM_Event_BAO_Event();
	$query = <<<EOS
	  SELECT
		civicrm_event.*,
		civicrm_value_conference_6.conference_slot_18 AS slot_name
	  FROM
		civicrm_event
	  JOIN
		civicrm_value_conference_6 ON (civicrm_event.id = civicrm_value_conference_6.entity_id)
	  WHERE
		civicrm_value_conference_6.main_conference_event_id_17 = {$this->main_conference_event->id}
	  ORDER BY
		civicrm_value_conference_6.conference_slot_18,
		civicrm_event.start_date
EOS;
	$events->query($query);
	while ( $events->fetch() ) {
	  if ( !array_key_exists( $events->slot_name, $this->events_by_slot ) ) {
		$this->events_by_slot[$events->slot_name] = array();
	  }
	  $this->events_by_slot[$events->slot_name][] = clone($events);
	}
  }

  function buildQuickForm( )
  {
	$slot_index = -1;
	$slot_fields = array( );
	foreach ( $this->events_by_slot as $slot_name => $events ) {
	  $slot_index++;
	  $event_titles = array( );
	  foreach ( $events as $event ) {
		$event_titles[$event->id] = $event->title;
	  }
	  $field_name = "slot_$slot_index";
	  $this->addRadio( "slot_$slot_index", $slot_name, $event_titles );
	  $slot_fields[$slot_name] = $field_name;
	}

	$this->assign( 'mer_participant', $this->mer_participant );
	$this->assign( 'events_by_slot', $this->events_by_slot );
	$this->assign( 'slot_fields', $slot_fields );

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
  }

  function postProcess( )
  {
	$params = $this->controller->exportValues( $this->_name );
	$conference_participants_events = $this->get( 'conference_participants_events' );
	if ( $conference_participants_events == null ) {
	  $conference_participant_events = array( );
	}
	$conference_participants_events[$this->mer_participant_index] = array( );
	$slot_index = -1;
	foreach ( $this->events_by_slot as $slot_name => $events ) {
	  $slot_index++;
	  $field_name = "slot_$slot_index";
	  $conference_participants_events[$this->mer_participant_index][] = $params[$field_name];
	}
	$this->set( 'conference_participants_events', $conference_participants_events );
  }
}
