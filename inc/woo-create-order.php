<?php

// Hook to validate checkout and call the API
add_action( 'woocommerce_after_checkout_validation', 'woo_validate_order_with_api', 10, 2 );

function woo_validate_order_with_api( $data, $errors ) {
    if ( !empty( $errors->get_error_messages() ) ) {
        return;
    }

    $account_number   = isset( $_POST['account_number'] ) ? sanitize_text_field( $_POST['account_number'] ) : '';
    $reference_number = isset( $_POST['reference_number'] ) ? sanitize_text_field( $_POST['reference_number'] ) : '';
    $po_number        = isset( $_POST['po_number'] ) ? sanitize_text_field( $_POST['po_number'] ) : '';
    $poster_state     = isset( $_POST['poster_state'] ) ? sanitize_text_field( $_POST['poster_state'] ) : '';
    $poster_language  = isset( $_POST['poster_language'] ) ? sanitize_text_field( $_POST['poster_language'] ) : '';

    // Prepare data for API submission
    $api_data = array(
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => $account_number,
        'Date_Order_Received'     => date( 'm-d-Y' ),
        'Client_Company'          => $data['billing_company'],
        'Reference_Number'        => $reference_number,
        'Unique_ID'               => uniqid( 'order_' ),
        'PO_Number'               => $po_number,
        'Client_First_Name'       => $data['billing_first_name'],
        'Client_Last_Name'        => $data['billing_last_name'],
        'Client_Street_Address_1' => $data['billing_address_1'],
        'Client_Street_Address_2' => $data['billing_address_2'],
        'Client_City'             => $data['billing_city'],
        'Client_State'            => $data['billing_state'],
        'Client_ZIP'              => $data['billing_postcode'],
        'Client_Email_Address'    => $data['billing_email'],
        'Client_Phone_Number'     => $data['billing_phone'],
        'Poster_State'            => $poster_state,
        'Poster_Language'         => $poster_language,
    );

    // Send data to the external API
    $response = wp_remote_post(
        'https://www.posterelite.com/api/Order_Creation.php',
        array(
            'method' => 'POST',
            'body'   => $api_data,
        )
    );

    // Check the API response
    if ( is_wp_error( $response ) ) {
        // Handle HTTP request errors
        $error_message = $response->get_error_message();
        $errors->add( 'validation', __( 'API request failed: ' . $error_message, 'woocommerce' ) );
        return;
    }

    $response_body = wp_remote_retrieve_body( $response );
    $response_data = json_decode( $response_body, true );

    if ( isset( $response_data['code'] ) && $response_data['code'] == 3000 ) {
        // Success response, store data in session for later use
        WC()->session->set( 'account_number', $account_number );
        WC()->session->set( 'reference_number', $reference_number );
        WC()->session->set( 'po_number', $po_number );
        WC()->session->set( 'poster_state', $poster_state );
        WC()->session->set( 'poster_language', $poster_language );
        WC()->session->set( 'api_response_data', $response_data );
    } else {
        // Error response, add error message
        $error_message = isset( $response_data['error'] ) ? json_encode( $response_data['error'] ) : 'API error';
        $errors->add( 'validation', __( 'Order could not be placed: ' . $error_message, 'woocommerce' ) );
    }
}

// Hook to create order and use session data
add_action( 'woocommerce_checkout_create_order', 'woo_create_order_callback', 10, 2 );

function woo_create_order_callback( $order, $data ) {
    global $wpdb;

    $order_id = $order->get_id();

    // Retrieve data from session
    $account_number    = WC()->session->get( 'account_number' );
    $reference_number  = WC()->session->get( 'reference_number' );
    $po_number         = WC()->session->get( 'po_number' );
    $poster_state      = WC()->session->get( 'poster_state' );
    $poster_language   = WC()->session->get( 'poster_language' );
    $api_response_data = WC()->session->get( 'api_response_data' );

    // Clear session data
    WC()->session->__unset( 'account_number' );
    WC()->session->__unset( 'reference_number' );
    WC()->session->__unset( 'po_number' );
    WC()->session->__unset( 'poster_state' );
    WC()->session->__unset( 'poster_language' );
    WC()->session->__unset( 'api_response_data' );

    // Get order status
    $status = $order->get_status();

    // Get order date and format it as MM-dd-YYYY
    $order_date           = $order->get_date_created();
    $order_date_formatted = $order_date ? $order_date->format( 'm-d-Y' ) : '';

    // Generate unique ID (e.g., using uniqid)
    $unique_id = uniqid( 'order_' );

    // Get billing details
    $company    = $order->get_billing_company() ?? '';
    $first_name = $order->get_billing_first_name() ?? '';
    $last_name  = $order->get_billing_last_name() ?? '';
    $address_1  = $order->get_billing_address_1() ?? '';
    $address_2  = $order->get_billing_address_2() ?? '';
    $city       = $order->get_billing_city() ?? '';
    $state      = $order->get_billing_state() ?? '';
    $postcode   = $order->get_billing_postcode() ?? '';
    $email      = $order->get_billing_email() ?? '';
    $phone      = $order->get_billing_phone() ?? '';

    // Combine all data into an array for the custom table
    $order_data = array(
        'account_number'   => $account_number,
        'order_date'       => $order_date->format( 'm-d-Y' ),
        'reference_number' => $reference_number,
        'unique_id'        => $unique_id,
        'po_number'        => $po_number,
        'company'          => $company,
        'first_name'       => $first_name,
        'last_name'        => $last_name,
        'address_1'        => $address_1,
        'address_2'        => $address_2,
        'city'             => $city,
        'state'            => $state,
        'postcode'         => $postcode,
        'email'            => $email,
        'phone'            => $phone,
        'poster_state'     => $poster_state,
        'poster_language'  => $poster_language,
    );

    // Prepare data for insertion into the custom table
    $table_name = $wpdb->prefix . 'woai_orders';
    $data       = array(
        'order_id'   => $order_id,
        'order_data' => json_encode( $order_data ),
        'status'     => $status,
    );

    // Insert data into the database
    $wpdb->insert( $table_name, $data );

    // Save the successful API response to order meta
    update_post_meta( $order_id, '_api_submission_response', json_encode( $api_response_data ) );
}


// Display API submission messages on the order received page
add_action( 'woocommerce_thankyou', 'display_api_submission_message' );
function display_api_submission_message( $order_id ) {
    $api_submission_error   = get_post_meta( $order_id, '_api_submission_error', true );
    $api_submission_success = get_post_meta( $order_id, '_api_submission_success', true );

    if ( !empty( $api_submission_error ) ) {
        wc_print_notice( $api_submission_error, 'error' );
    }

    if ( !empty( $api_submission_success ) ) {
        wc_print_notice( $api_submission_success, 'success' );
    }
}



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
