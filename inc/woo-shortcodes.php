<?php

function poa_update_order_status_db() {

    $data_file_path = WOO_ORDER_API_PLUGIN_PATH . '/api_response/order-data.json';
    $api_data       = file_get_contents( $data_file_path );
    $api_data       = json_decode( $api_data, true );

    poa_update_order_data_db( $api_data );
    // poa_update_order_data_db( 'Cancelled', 'order_1719985633_english' );

    return 'Order status updated successfully';

}
add_shortcode( 'update_order_status', 'poa_update_order_status_db' );