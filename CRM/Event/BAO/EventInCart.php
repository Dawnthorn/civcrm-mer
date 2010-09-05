<?php

require_once 'CRM/Event/DAO/EventInCart.php';

class CRM_Event_BAO_EventInCart extends CRM_Event_DAO_EventInCart implements
  ArrayAccess
{
  public $assocations_loaded = false;
  public $event;
  public $event_cart;

  function __construct( )
  {
    parent::__construct( );
  }

  public static function create( $params )
  {
    require_once 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction( );
    $event_in_cart = new CRM_Event_BAO_EventInCart( );
    $event_in_cart->copyValues( $params );
    $event_in_cart = $event_in_cart->save( );

    if ( is_a( $event_in_cart, 'CRM_Core_Error') ) {
      CRM_Core_DAO::transaction( 'ROLLBACK' );
      CRM_Core_Error::fatal( ts( 'There was an error creating an event_in_cart') );
    }

    $transaction->commit( );

    return $event_in_cart;
  }

  public static function find_all_by_event_cart_id( $event_cart_id )
  {
    return self::find_all_by_params( array( 'event_cart_id' => $event_cart_id ) );
  }

  public static function find_all_by_params( $params )
  {
    $event_in_cart = new CRM_Event_BAO_EventInCart( );
    $event_in_cart->copyValues( $params );
    $result = array();
    if ( $event_in_cart->find( ) ) {
      while ( $event_in_cart->fetch( ) ) {
	$result[] = clone( $event_in_cart );
      }
    }
    return $result;
  }

  public static function find_by_id( $id )
  {
    return self::find_by_params( array( 'id' => $id ) );
  }

  public static function find_by_params( $params )
  {
    $event_in_cart = new CRM_Event_BAO_EventInCart( );
    $event_in_cart->copyValues( $params );
    if ( $event_in_cart->find( true ) ) {
      return $event_in_cart;
    } else {
      return false;
    }
  }

  public function load_associations( $event_cart = null ) {
    if ( $assocations_loaded ) {
      return;
    }
    $assocations_loaded = true;
    require_once 'CRM/Event/BAO/Event.php';
    $params = array( 'id' => $this->event_id );
    $defaults = array( );
    $this->event = CRM_Event_BAO_Event::retrieve( $params, $defaults );
    if ( $event_cart != null ) {
      $this->event_cart = $event_cart;
    } else {
      $this->event_cart = CRM_Event_BAO_Cart::find_by_id( $this->event_cart_id);
    }
  }

  public function offsetExists( $offset ) {
    return array_key_exists($this->fields( ), $offset);
  }

  public function offsetGet( $offset ) {
    if ( $offset == 'event' ) {
      return $this->event->toArray();
    }
    if ( $offset == 'id' ) {
      return $this->id;
    }
    $fields =& $this->fields( );
    return $fields[$offset];
  }

  public function offsetSet( $offset, $value ) {
  }

  public function offsetUnset( $offset ) {
  }
}
?>