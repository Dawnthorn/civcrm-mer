<?php

require_once 'CRM/Event/DAO/Cart.php';
require_once 'CRM/Event/BAO/EventInCart.php';

class CRM_Event_BAO_Cart extends CRM_Event_DAO_Cart
{
  public $associations_loaded = false;
  public $events_in_carts = array();

  function __construct( )
  {
    parent::__construct( );
  }

  public static function add( &$params )
  {
    $cart = new CRM_Event_BAO_Cart( );
    $cart->copyValues( $params );
    $result = $cart->save( );
    return $result;
  }

  public function add_event( $event_id )
  {
    $this->load_associations();
    foreach ( $this->events_in_carts as $event_in_cart ) {
      if ( $event_in_cart->event_id == $event_id ) {
	return;
      }
    }

    $params = array(
      'event_id' => $event_id, 
      'event_cart_id' => $this->id 
    );
    $event_in_cart = CRM_Event_BAO_EventInCart::create( $params );
    array_push($this->events_in_carts, $event_in_cart);
  }

  public static function create( $params )
  {
    require_once 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction( );

    $cart = self::add( $params );

    if ( is_a( $cart, 'CRM_Core_Error') ) {
      CRM_Core_DAO::transaction( 'ROLLBACK' );
      CRM_Core_Error::fatal( ts( 'There was an error creating an event cart') );
    }

    $transaction->commit( );

    return $cart;
  }

  public static function find_by_id( $id )
  {
    return self::find_by_params( array( 'id' => $id ) );
  }

  public static function find_by_params( $params )
  {
    $cart = new CRM_Event_BAO_Cart( );
    $cart->copyValues( $params );
    if ( $cart->find( true ) ) {
      return $cart;
    } else {
      return false;
    }
  }

  public static function find_or_create_for_current_session( )
  {
    $session = CRM_Core_Session::singleton( );
    $event_cart_id = $session->get( 'event_cart_id' );
    $cart = false;
    if ( !is_null( $event_cart_id ) ) {
      $cart = self::find_uncompleted_by_id( $event_cart_id );
    }
    if ( $cart === false ) {
      $userID = $session->get( 'userID' );
      if ( is_null( $userID ) ) {
	$cart = self::create( array( ) );
      } else {
	$cart = self::find_uncompleted_by_user_id( $userID );
	if ( $cart === false ) {
	  $cart = self::create( array( 'user_id' => $userID ) );
	}
      }
      $session->set( 'event_cart_id', $cart->id );
    }
    return $cart;
  }

  public static function find_uncompleted_by_id( $id )
  {
    return self::find_by_params( array( 'id' => $id, 'completed' => 0 ) );
  }

  public static function find_uncompleted_by_user_id( $user_id )
  {
    return self::find_by_params( array( 'user_id' => $user->id, 'completed' => 0 ) );
  }

  public function get_event_in_cart_by_id( $event_in_cart_id )
  {
    foreach ( $this->events_in_carts as $event_in_cart ) {
      if ( $event_in_cart->id == $event_in_cart_id ) {
	return $event_in_cart;
      }
    }
    return null;
  }

  public function load_associations( )
  {
    if ( $this->associations_loaded ) {
      return;
    }
    $this->associations_loaded = true;
    $this->events_in_carts = CRM_Event_BAO_EventInCart::find_all_by_event_cart_id( $this->id );
    foreach ( $this->events_in_carts as $event_in_cart ) {
      $event_in_cart->load_associations($this);
    }
  }

  public function remove_event_in_cart( $event_in_cart_id ) {
    $event_in_cart = CRM_Event_BAO_EventInCart::find_by_id( $event_in_cart_id );
    $event_in_cart->load_associations( );
    $event_in_cart->delete( );
    return $event_in_cart;
  }

  public static function retrieve( &$params, &$values )
  {
    $cart = self::find_by_params( $params );
    if ( $cart === false ) {
      CRM_Core_Error::fatal( ts( 'Could not find cart matching %1', array ( 1 => var_export( $params, true ) ) ) );
    }
    CRM_Core_DAO::storeValues( $cart, $values );
    return $values;
  }

}

?>
