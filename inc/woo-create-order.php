<?php

add_action( 'woocommerce_thankyou', 'woo_create_order_callback', 10, 1 );

function woo_create_order_callback( $order_id ) {
    if ( !$order_id ) {
        return;
    }

    // Get an instance of the WC_Order object
    $order = wc_get_order( $order_id );

    // Retrieve order data
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $company    = $order->get_billing_company();
    $address_1  = $order->get_billing_address_1();
    $city       = $order->get_billing_city();
    $state      = $order->get_billing_state();
    $postcode   = $order->get_billing_postcode();
    $phone      = $order->get_billing_phone();

    // retrieve fields data
    $account_number   = get_post_meta( $order_id, '_account_number', true );
    $reference_number = get_post_meta( $order_id, '_reference_number', true ) ?? '';
    $po_number        = get_post_meta( $order_id, '_po_number', true ) ?? '';

    // Generate a unique ID (example using order ID and timestamp)
    $unique_id = $order_id . '_' . time();

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Client_ZIP'              => $postcode,
        'Order_Type'              => 'ePoster Service',
        'Client_Company'          => $company,
        'Client_Street_Address_1' => $address_1,
        'Client_City'             => $city,
        'Client_First_Name'       => $first_name,
        'Client_Last_Name'        => $last_name,
        'Account_Number'          => '60016', // Assuming this is static or retrieved differently
        'Client_State'            => $state,
        'Unique_ID'               => $unique_id,
        'Referance_Number'        => $phone,
        'PO_Number'               => '00254', // Assuming this is static or retrieved differently
    ];

    $curl = curl_init();

    curl_setopt_array(
        $curl,
        array(
            CURLOPT_URL            => 'https://www.posterelite.com/api/Order_Creation.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => http_build_query( $api_data ),
        )
    );

    $response = curl_exec( $curl );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        $response = 'There was an error processing your request. Please try again.';
    }

    curl_close( $curl );

    // Log response for debugging purposes
    error_log( 'API Response: ' . $response );

    // Store the response in a WooCommerce session variable
    WC()->session->set( 'api_response_message', $response );
}

function display_api_response_message() {
    // Get the response message from the session
    $response_message = WC()->session->get( 'api_response_message' );

    // Display the response message if it exists
    if ( $response_message ) {
        echo '<div class="woocommerce-message">' . esc_html( $response_message ) . '</div>';

        // Clear the session variable to avoid displaying the message again
        WC()->session->set( 'api_response_message', null );
    }
}

add_action( 'woocommerce_thankyou', 'display_api_response_message', 20 );



// Function to update the status in the custom table
function woo_update_order_status( $order_id, $old_status, $new_status ) {
    global $wpdb;

    // Check if the status is changing from "processing" to "cancelled"
    if ( $old_status === 'processing' && $new_status === 'cancelled' ) {
        // Prepare data for update
        $table_name = $wpdb->prefix . 'woai_orders';
        $data       = array( 'status' => $new_status );
        $where      = array( 'order_id' => $order_id );

        // Update the status in the custom table
        $wpdb->update( $table_name, $data, $where );
    }
}

// Hook the function to the WooCommerce order status changed action
add_action( 'woocommerce_order_status_changed', 'woo_update_order_status', 10, 3 );
