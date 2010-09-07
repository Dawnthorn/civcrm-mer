<?php

class CRM_Event_StateMachine_Checkout extends CRM_Core_StateMachine
{
  function __construct( $controller, $action = CRM_Core_Action::NONE )
  {
    parent::__construct( $controller, $action );

    require_once 'CRM/Event/BAO/Cart.php';
    $cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $cart->load_associations();
    if ( $cart->is_empty() ) {
      CRM_Core_Error::statusBounce( ts( "You don't have any events in you cart. Please add some events." ), CRM_Utils_System::url( 'civicrm/event' ) );
    }

    $pages = array( );
    $is_monetary = false;
    foreach ($cart->events_in_carts as $event_in_cart) {
      $event_in_cart->load_associations();
      if ($event_in_cart->event->is_monetary) {
	$is_monetary = true;
      }
      $pages["Register_{$event_in_cart->id}"] = array( "className" => "CRM_Event_Form_Checkout_Register", "title" => "Register For {$event_in_cart->event->title}" );
    }

    //handle additional participant scenario, where we need to insert participant pages on runtime
    $additionalParticipant = null;

    // check that the controller has some data, hence we dont send the form name                                         
    // which results in an invalid argument error                                                                        
    $values = $controller->exportValues( );
    //first check POST value then QF
    if ( isset( $_POST['additional_participants'] ) && CRM_Utils_Rule::positiveInteger( $_POST['additional_participants'] ) ) {
      // we need to use $_POST since the QF framework has not yet been called
      // and the additional participants page is the next one, so need to set this up
      // now
      $additionalParticipant = $_POST['additional_participants'];
    } else if ( isset( $values['additional_participants'] ) && CRM_Utils_Rule::positiveInteger( $values['additional_participants'] ) ) {
      $additionalParticipant = $values['additional_participants'];
    }

    if ( $additionalParticipant ) {
      $additionalParticipant = CRM_Utils_Type::escape( $additionalParticipant, 'Integer' );
      $controller->set( 'addParticipant', $additionalParticipant );
    }

    //to add instances of Additional Participant page, only if user has entered any additional participants
    if ( $additionalParticipant ) {
      require_once "CRM/Event/Form/Checkout/AdditionalParticipant.php";
      $extraPages =& CRM_Event_Form_Checkout_AdditionalParticipant::getPages( $additionalParticipant );
      $pages = array_merge( $pages, $extraPages );
    }

    $additionalPages = array( 'CRM_Event_Form_Checkout_Confirm'   => null,
      'CRM_Event_Form_Checkout_ThankYou'  => null
    );

    $pages = array_merge( $pages, $additionalPages );

    if ( !$is_monetary ) {
      unset( $pages['CRM_Event_Form_Checkout_Confirm'] );
    }
    dlog("Pages: " . dlog_debug_var($pages));
    $this->addSequentialPages( $pages, $action );
  }
}

?>
