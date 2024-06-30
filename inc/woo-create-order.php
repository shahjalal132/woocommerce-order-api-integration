<?php

function woa_validate_order_with_api() {

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
    /* $account_number   = sanitize_text_field( $_POST['account_number'] );
    $reference_number = sanitize_text_field( $_POST['reference_number'] );
    $po_number        = sanitize_text_field( $_POST['po_number'] ); */

    $account_number = '60016';

    // Generate a unique ID (example using current timestamp)
    $unique_id = 'order_' . time();

    // Static data for missing fields
    $order_received_date = date( 'm-d-Y' ); // current date
    $order_type          = 'E-Update Service (With Initial All-In-One Poster)';
    $poster_language     = 'English';

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => $account_number,
        'Date_Order_Received'     => $order_received_date,
        'Client_Company'          => $company,
        'Unique_ID'               => $unique_id,
        'Client_First_Name'       => $first_name,
        'Client_Last_Name'        => $last_name,
        'Client_Street_Address_1' => $address_1,
        'Client_Street_Address_2' => $address_2,
        'Client_City'             => $city,
        'Client_State'            => $state,
        'Client_ZIP'              => $postcode,
        'Client_Email_Address'    => $email,
        'Client_Phone_Number'     => $phone,
        'Order_Type'              => $order_type,
        'Poster_State'            => $state,
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
// Order Creation API Integration
add_action( 'woocommerce_checkout_process', 'woa_validate_order_with_api' );

function woa_save_unique_id_to_order( $order, $data ) {
    // Get the unique ID from the session
    $unique_id = WC()->session->get( 'api_unique_id' );

    // Save the unique ID to order meta
    if ( $unique_id ) {
        $order->update_meta_data( '_order_unique_id', $unique_id );

        // Clear the session variable
        WC()->session->set( 'api_unique_id', null );
    }
}
// Save unique ID to order
add_action( 'woocommerce_checkout_create_order', 'woa_save_unique_id_to_order', 20, 2 );

function woa_woo_update_order_status( $order_id, $old_status, $new_status ) {

    // Check if the status is changing from "processing" to "cancelled"
    if ( $old_status === 'processing' && $new_status === 'cancelled' ) {

        // Retrieve the account number and unique ID from the order meta
        $account_number = '60016';
        // $account_number = get_post_meta( $order_id, '_account_number', true );
        $unique_id = get_post_meta( $order_id, '_order_unique_id', true );

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
        curl_close( $curl );
    }
}
// Hook the function to the WooCommerce order status changed action
add_action( 'woocommerce_order_status_changed', 'woa_woo_update_order_status', 10, 3 );

function woa_update_order_with_api( $order_id, $items ) {
    // Get the order object
    $order = wc_get_order( $order_id );

    if ( !$order ) {
        return;
    }

    // Retrieve the updated order data
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $company    = $order->get_billing_company();
    $address_1  = $order->get_billing_address_1();
    $address_2  = $order->get_billing_address_2();
    $city       = $order->get_billing_city();
    $state      = $order->get_billing_state();
    $postcode   = $order->get_billing_postcode();
    $email      = $order->get_billing_email();
    $phone      = $order->get_billing_phone();

    // Retrieve custom fields data if available
    $account_number   = '60016';
    $reference_number = get_post_meta( $order_id, '_reference_number', true );
    $unique_id        = get_post_meta( $order_id, '_order_unique_id', true );

    // Static data for missing fields
    $order_received_date = date( 'm-d-Y' ); // current date
    $order_type          = 'Poster Replacement Solution (With Initial All-In-One Poster)';
    $poster_language     = 'English';

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => $account_number,
        'Date_Order_Received'     => $order_received_date,
        'Client_Company'          => $company,
        'Reference_Number'        => $reference_number,
        'Unique_ID'               => $unique_id,
        'Client_First_Name'       => $first_name,
        'Client_Last_Name'        => $last_name,
        'Client_Street_Address_1' => $address_1,
        'Client_Street_Address_2' => $address_2,
        'Client_City'             => $city,
        'Client_State'            => $state,
        'Client_ZIP'              => $postcode,
        'Client_Email_Address'    => $email,
        'Client_Phone_Number'     => $phone,
        'Order_Type'              => $order_type,
        'Poster_State'            => $state,
        'Poster_Language'         => $poster_language,
    ];

    $curl = curl_init();

    curl_setopt_array(
        $curl,
        array(
            CURLOPT_URL            => 'https://www.posterelite.com/api/Order_Update.php',
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

    curl_close( $curl );
}
// Hook into the order save action after items are saved
add_action( 'woocommerce_update_order', 'woa_update_order_with_api', 10, 2 );


// Hook into the order edit page to display additional information
add_action( 'woocommerce_admin_order_data_after_billing_address', 'woa_display_order_details_from_api', 11, 1 );
function woa_display_order_details_from_api( $order ) {
    // Get the order ID
    $order_id = $order->get_id();

    // Make the API call to retrieve order details
    $api_response = make_api_call_for_order_details( $order_id );

    if ( $api_response && $api_response['code'] === 3000 ) {
        $order_data = $api_response['data'][0];

        // Display the retrieved information in a table view
        $html = <<<EOD
        <div class="order_details_from_api">
            <h2>Order Details from API</h2>
            <table>
                <tr>
                    <th>Date Order Received</th>
                    <td>{$order_data['Date_Order_Received']}</td>
                </tr>
                <tr>
                    <th>Street Address 1</th>
                    <td>{$order_data['Subform_ID.Client_Street_Address_1']}</td>
                </tr>
                <tr>
                    <th>Street Address 2</th>
                    <td>{$order_data['Subform_ID.Client_Street_Address_2']}</td>
                </tr>
                <tr>
                    <th>ZIP Code</th>
                    <td>{$order_data['Subform_ID.Zip_Code']}</td>
                </tr>
                <tr>
                    <th>City</th>
                    <td>{$order_data['Subform_ID.Client_City1']}</td>
                </tr>
                <tr>
                    <th>State</th>
                    <td>{$order_data['Subform_ID.Client_State1']}</td>
                </tr>
                <tr>
                    <th>Company</th>
                    <td>{$order_data['Subform_ID.Client_Company']}</td>
                </tr>
                <tr>
                    <th>First Name</th>
                    <td>{$order_data['Subform_ID.Client_First_Name']}</td>
                </tr>
                <tr>
                    <th>Last Name</th>
                    <td>{$order_data['Subform_ID.Client_Last_Name']}</td>
                </tr>
                <tr>
                    <th>Order Type</th>
                    <td>{$order_data['Poster_Order_Type.Poster_Order_Type']}</td>
                </tr>
                <tr>
                    <th>Poster Language</th>
                    <td>{$order_data['Poster_Language']}</td>
                </tr>
                <tr>
                    <th>Order Unique Number</th>
                    <td>{$order_data['Subform_ID.Reference_Number']}</td>
                </tr>
            </table>
        </div>
        EOD;

        echo $html;
    }
}

function make_api_call_for_order_details( $order_id ) {
    $order_number   = '2024-06-29-1679218'; // Replace with dynamic order number logic if needed
    $account_number = '60016'; // Replace with dynamic account number logic if needed

    $curl = curl_init();

    curl_setopt_array(
        $curl,
        array(
            CURLOPT_URL            => 'https://www.posterelite.com/api/Order_Details.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => array(
                'Auth_String'    => '525HRD7867200143000',
                'Account_Number' => $account_number,
                'order_number'   => $order_number,
                'status'         => '',
                'start_index'    => '0',
            ),
        )
    );

    $response = curl_exec( $curl );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        curl_close( $curl );
        return false;
    }

    curl_close( $curl );

    return json_decode( $response, true );
}