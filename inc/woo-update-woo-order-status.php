<?php

function poa_update_woo_order_status( $order_id, $new_status ) {

    // Get an instance of the WC_Order object
    $order = wc_get_order( $order_id );

    // Update the status
    if ( $order ) {
        $order->update_status( $new_status, 'Order status updated based on the API', true );
    }
}
