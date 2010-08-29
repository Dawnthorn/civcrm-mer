<?php

require_once 'CRM/Event/DAO/Cart.php';

class CRM_Event_BAO_Cart extends CRM_Event_DAO_Cart
{
  function __construct( )
  {
    parent::__construct( );
  }

  public static function add( &$params )
  {
    $cart = new CRM_Event_DAO_Cart( );
    $cart->copyValues( $params );
    $result = $cart->save( );
    return $result;
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
    $cart = new CRM_Event_DAO_Cart( );
    $cart->copyValues( $params );
    if ( $cart->find( true ) ) {
      return $cart;
    } else {
      return false;
    }
  }

  public static function find_by_user_id( $user_id )
  {
    return self::find_by_params( array( 'user_id' => $user->id ) );
  }

  public static function find_or_create_for_current_session( )
  {
    $session = CRM_Core_Session::singleton( );
    $event_cart_id = $session->get( 'event_cart_id' );
    if ( is_null( $event_cart_id ) ) {
      $userID = $session->get( 'userID' );
      if ( is_null( $userID ) ) {
	$cart = self::create( array( ) );
      } else {
	$cart = self::find_by_user_id( $userID );
	if ( $cart === false ) {
	  $cart = self::create( array( 'user_id' => $userID ) );
	}
      }
      $session->set( 'event_cart_id', $cart->id );
    } else {
      $cart = self::find_by_id( $event_cart_id );
      if ( $cart === false ) {
	CRM_Core_Error::fatal( ts('The event_cart_id session variable is set to %1, but there is no such cart in the database.', array( 1 => $event_cart_id ) ) );
      }
    }
    return $cart;
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
