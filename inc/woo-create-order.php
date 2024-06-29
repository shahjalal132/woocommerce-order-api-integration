<?php

// Order Creation API Integration
add_action( 'woocommerce_checkout_process', 'validate_order_with_api' );
function validate_order_with_api() {

    // Retrieve checkout fields
    $first_name = sanitize_text_field( $_POST['billing_first_name'] );
    $last_name  = sanitize_text_field( $_POST['billing_last_name'] );
    $company    = sanitize_text_field( $_POST['billing_company'] );
    $address_1  = sanitize_text_field( $_POST['billing_address_1'] );
    $address_2  = sanitize_text_field( $_POST['billing_address_2'] );
    $city       = sanitize_text_field( $_POST['billing_city'] );
    $state      = sanitize_text_field( $_POST['billing_state'] );
    $postcode   = sanitize_text_field( $_POST['billing_postcode'] );
    $email      = sanitize_email( $_POST['billing_email'] );
    $phone      = sanitize_text_field( $_POST['billing_phone'] );

    // Retrieve custom fields data
    $account_number   = sanitize_text_field( $_POST['account_number'] );
    $reference_number = sanitize_text_field( $_POST['reference_number'] );
    $po_number        = sanitize_text_field( $_POST['po_number'] );

    // Generate a unique ID (example using current timestamp)
    $unique_id = 'order_' . time();

    // Static data for missing fields
    $order_received_date = date( 'm-d-Y' ); // current date
    $order_type          = 'E-Update Service (With Initial All-In-One Poster)';
    $poster_state        = 'CA';
    $poster_language     = 'English';

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => $account_number ?: '60016',
        'Date_Order_Received'     => $order_received_date,
        'Client_Company'          => $company ?: 'Imjol IT',
        'Unique_ID'               => $unique_id,
        'Client_First_Name'       => $first_name,
        'Client_Last_Name'        => $last_name,
        'Client_Street_Address_1' => $address_1,
        'Client_Street_Address_2' => $address_2 ?: 'STE 207',
        'Client_City'             => $city,
        'Client_State'            => $state,
        'Client_ZIP'              => $postcode,
        'Client_Email_Address'    => $email ?: 'ffshahjalal@gmail.com',
        'Client_Phone_Number'     => $phone ?: '916-555-1212',
        'Order_Type'              => $order_type,
        'Poster_State'            => $poster_state,
        'Poster_Language'         => $poster_language,
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

    put_api_response_data( $response );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        $response = '{"code":4000,"message":"There was an error processing your request. Please try again."}';
    }

    curl_close( $curl );

    // Decode the response
    $response_data = json_decode( $response, true );

    // Check the response code
    if ( $response_data['code'] !== 3000 ) {
        $error_message = $response_data['message'];
        $error_message = json_encode( $error_message, JSON_PRETTY_PRINT );
        wc_add_notice( 'API Error: ' . $error_message, 'error' );
    } else {
        // Store the unique ID in session for later use (e.g., saving in order meta)
        WC()->session->set( 'api_unique_id', $unique_id );
    }
}


// Save unique ID to order
add_action( 'woocommerce_checkout_create_order', 'save_unique_id_to_order', 20, 2 );
function save_unique_id_to_order( $order, $data ) {
    // Get the unique ID from the session
    $unique_id = WC()->session->get( 'api_unique_id' );

    // Save the unique ID to order meta
    if ( $unique_id ) {
        $order->update_meta_data( '_order_unique_id', $unique_id );

        // Clear the session variable
        WC()->session->set( 'api_unique_id', null );
    }
}



// Function to update the status in the custom table
function woo_update_order_status( $order_id, $old_status, $new_status ) {

    // Check if the status is changing from "processing" to "cancelled"
    if ( $old_status === 'processing' && $new_status === 'cancelled' ) {

        // Retrieve the account number and unique ID from the order meta
        $account_number = get_post_meta( $order_id, '_account_number', true );
        $unique_id      = get_post_meta( $order_id, '_order_unique_id', true );

        // If the account number or unique ID is not found, log an error and return
        if ( empty( $account_number ) || empty( $unique_id ) ) {
            return;
        }

        // Prepare data to be sent to the API
        $api_data = [
            'Auth_String'    => '525HRD7867200143000',
            'Account_Number' => $account_number,
            'Unique_ID'      => $unique_id,
        ];

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://www.posterelite.com/api/Order_Cancellation.php',
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

        put_api_response_data( $response );

        curl_close( $curl );
    }
}

// Hook the function to the WooCommerce order status changed action
add_action( 'woocommerce_order_status_changed', 'woo_update_order_status', 10, 3 );
