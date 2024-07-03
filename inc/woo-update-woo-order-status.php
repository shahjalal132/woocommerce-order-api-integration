<?php

/**
 * Update order data to DB fetch from api
 * 
 * 1. Get Orders from sync_order_status table
 * 2. Fetch order details from api by order_number
 * 3. Update Order data and status in sync_order_status table
 */
function poa_fetch_update_order_date_db() {

    // Get order status from DB
    $order_status_from_db = get_order_status_from_db_callback();

    if ( !empty( $order_status_from_db ) && is_array( $order_status_from_db ) ) {

        foreach ( $order_status_from_db as $key => $value ) {

            // Retrieve order number
            $order_number = $value->order_number;

            // Fetch order details from api
            $get_order_details = poa_get_order_details_from_api( $order_number );
            // Update order data and status in DB
            poa_update_order_data_db( $get_order_details );
        }
    }
}

/**
 * Update Woo order status
 * 
 * 1. Get order_id and status form DB
 * 2. Update order status
 */
function poa_update_woo_order_status() {

    // Get order status from DB
    $order_status_from_db = get_order_status_from_db_callback();

    if ( !empty( $order_status_from_db ) && is_array( $order_status_from_db ) ) {

        foreach ( $order_status_from_db as $key => $value ) {

            // Retrieve order_id
            $order_id = $value->order_id;
            // Retrieve order status
            $order_status = $value->order_status;

            // Update order status
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $order->update_status( $order_status, 'Order status updated by api', true );
                }
            }
        }
    }
}
