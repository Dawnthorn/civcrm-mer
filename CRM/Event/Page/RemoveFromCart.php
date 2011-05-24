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

require_once 'CRM/Core/Page.php';

class CRM_Event_Page_RemoveFromCart extends CRM_Core_Page {
  function run( ) {
    $this->_id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this, true );
    require_once 'CRM/Event/BAO/Cart.php';
    $cart = CRM_Event_BAO_Cart::find_or_create_for_current_session( );
    $removed_event_in_cart = $cart->remove_event_in_cart( $this->_id );
	CRM_Core_Session::setStatus( ts("%1 has been removed from your cart.", array( 1 => $removed_event_in_cart->event->title ) ) );
	return CRM_Utils_System::redirect( $_SERVER['HTTP_REFERER'] );
  }
}

?>
