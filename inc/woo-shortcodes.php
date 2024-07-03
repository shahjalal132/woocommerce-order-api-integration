<?php

/**
 * Update Order data to DB
 *
 * @return string
 */
function poa_update_order_status_db() {

    $data_file_path = WOO_ORDER_API_PLUGIN_PATH . '/api_response/order-data.json';
    $api_data       = file_get_contents( $data_file_path );
    $api_data       = json_decode( $api_data, true );

    poa_update_order_data_db( $api_data );

    return 'Order status updated successfully';

}
add_shortcode( 'update_order_status', 'poa_update_order_status_db' );


/**
 * Get Orders from DB
 *
 */
function get_order_status_from_db_callback() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'sync_order_status';
    $sql        = "SELECT id, order_id, order_unique_id, order_number, order_status FROM $table_name";
    // $sql          = "SELECT id, order_id, order_unique_id, order_number, order_status FROM $table_name WHERE order_status != 'completed'";

    $order_status = $wpdb->get_results( $wpdb->prepare( $sql ) );

    // echo '<pre>';
    // print_r( $order_status );
    return $order_status;
}

add_shortcode( 'get_order_status_from_db', 'get_order_status_from_db_callback' );


function update_woo_order_status_callback() {

    $order_status_from_db = get_order_status_from_db_callback();

    if ( !empty( $order_status_from_db ) && is_array( $order_status_from_db ) ) {

        foreach ( $order_status_from_db as $key => $value ) {

            $order_id   = $value->order_id;
            $new_status = $value->order_status;

            // Update the status
            if ( $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $order->update_status( $new_status, 'Order status updated by api', true );
                }
            }
        }
    }

}

add_shortcode( 'update_woo_order_status', 'update_woo_order_status_callback' );