<?php

function woo_create_order_callback( $order_id ) {
    global $wpdb;

    // Get the order
    $order = wc_get_order( $order_id );
    if ( !$order ) {
        return;
    }

    // Get order status
    $status = $order->get_status();

    // Get custom fields from checkout
    $account_number   = get_post_meta( $order_id, '_account_number', true );
    $reference_number = get_post_meta( $order_id, '_reference_number', true );
    $po_number        = get_post_meta( $order_id, '_po_number', true );
    $poster_state     = get_post_meta( $order_id, '_poster_state', true );
    $poster_language  = get_post_meta( $order_id, '_poster_language', true );

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

    // Prepare data for API submission
    $api_data = array(
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => $account_number,
        'Date_Order_Received'     => $order_date_formatted,
        'Client_Company'          => $company,
        'Reference_Number'        => $reference_number,
        'Unique_ID'               => $unique_id,
        'PO_Number'               => $po_number,
        'Client_First_Name'       => $first_name,
        'Client_Last_Name'        => $last_name,
        'Client_Street_Address_1' => $address_1,
        'Client_Street_Address_2' => $address_2,
        'Client_City'             => $city,
        'Client_State'            => $state,
        'Client_ZIP'              => $postcode,
        'Client_Email_Address'    => $email,
        'Client_Phone_Number'     => $phone,
        'Order_Type'              => 'E-Update',
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

    // Handle the response if needed
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        error_log( "Order API submission failed: $error_message" );
        update_post_meta( $order_id, '_api_submission_error', "Order API submission failed: $error_message" );
        $order->update_status( 'failed', __( 'Order API submission failed.', 'woocommerce' ) );
    } else {
        $response_body = wp_remote_retrieve_body( $response );
        $response_data = json_decode( $response_body, true );

        if ( isset( $response_data['code'] ) && $response_data['code'] == 3001 ) {
            $error_message = "Order API submission failed: " . implode( ' ', array_column( $response_data['error'], 'alert_message' ) );
            error_log( $error_message );
            update_post_meta( $order_id, '_api_submission_error', $error_message );
            $order->update_status( 'failed', __( 'Order API submission failed.', 'woocommerce' ) );
        } elseif ( isset( $response_data['code'] ) && $response_data['code'] == 3000 ) {
            update_post_meta( $order_id, '_api_submission_success', __( 'Order API submission successful.', 'woocommerce' ) );
            wc_add_notice( __( 'Order API submission successful.', 'woocommerce' ), 'success' );
        } else {
            $error_message = "Unexpected API response: $response_body";
            error_log( $error_message );
            update_post_meta( $order_id, '_api_submission_error', $error_message );
            $order->update_status( 'failed', __( 'Order API submission failed.', 'woocommerce' ) );
        }
    }
}

// Hook the function to the WooCommerce order status completed action
add_action( 'woocommerce_thankyou', 'woo_create_order_callback', 10, 1 );


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
