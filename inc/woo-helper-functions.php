<?php

/**
 * Put api response data to filesystem
 *
 * @param string $data
 * @return string
 */
function put_api_response_data( $data ) {
    // Ensure directory exists to store response data
    $directory = WOO_ORDER_API_PLUGIN_PATH . '/api_response/';
    if ( !file_exists( $directory ) ) {
        mkdir( $directory, 0777, true );
    }

    // Construct file path for response data
    $fileName = $directory . 'response.log';

    // Get the current date and time
    $current_datetime = date( 'Y-m-d H:i:s' );

    // Append current date and time to the response data
    $data = $data . ' - ' . $current_datetime;

    // Append new response data to the existing file
    if ( file_put_contents( $fileName, $data . "\n\n", FILE_APPEND | LOCK_EX ) !== false ) {
        return "Data appended to file successfully.";
    } else {
        return "Failed to append data to file.";
    }
}

// insert order data to database
function poa_insert_order_data_db( $order_id, $api_order_data ) {

    // Extract order details from API response
    $order_data = $api_order_data['data'][0];

    global $wpdb;
    $table_name = $wpdb->prefix . 'sync_order_status';

    if ( !empty( $order_data ) ) {
        // Prepare the data to be inserted
        $data = array(
            'order_id'        => $order_id,
            'order_unique_id' => $order_data['Subform_ID.Reference_Number'],
            'order_number'    => $order_data['Order_Number'],
            'order_data'      => json_encode( $order_data ),
            'order_status'    => $order_data['Subform_ID.Order_Status'],
        );

        // Insert the data into the database
        $wpdb->insert(
            $table_name,
            $data
        );
    }
}

/**
 * update order data to database
 *
 * @param array $api_order_data
 * @return void
 */
function poa_update_order_data_db( $api_order_data ) {

    // Check if the API response contains the expected data structure
    if ( isset( $api_order_data['data'][0] ) ) {

        $order_data      = $api_order_data['data'][0];
        $order_unique_id = $order_data['Subform_ID.Reference_Number'];
        $order_status    = $order_data['Subform_ID.Order_Status'];

        // Check if the necessary order details are present
        if ( !empty( $order_unique_id ) && !empty( $order_status ) ) {

            global $wpdb;
            $table_name = $wpdb->prefix . 'sync_order_status';

            // Prepare the data to be updated
            $data = array(
                'order_data'   => json_encode( $order_data ),
                'order_status' => $order_status,
            );

            // Update the data in the database
            $where = array( 'order_unique_id' => $order_unique_id );
            $wpdb->update(
                $table_name,
                $data,
                $where
            );
        }
    }
}