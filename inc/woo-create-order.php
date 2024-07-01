<?php

function woa_create_order_with_api() {

    // Get the WooCommerce cart object
    $cart = WC()->cart;

    // Initialize the poster language variable
    $poster_language = 'English'; // default value

    // Check if the cart is not empty
    if ( !$cart->is_empty() ) {
        // Loop through cart items
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            // Check if the cart item has the desired variation attribute
            if ( isset( $cart_item['variation']['attribute_pa_language'] ) ) {
                // Set the poster language to the attribute value
                $poster_language = sanitize_text_field( $cart_item['variation']['attribute_pa_language'] );
                break; // Stop the loop after finding the first match
            }
        }
    }

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

    // Generate a unique ID
    $unique_id = 'order_' . time();

    // Static data for missing fields
    $order_received_date = date( 'm-d-Y' ); // current date
    $order_type          = 'E-Update Service (With Initial All-In-One Poster)';

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => '60016',
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

    // put_api_response_data(json_encode($api_data));
    // die();

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
    put_api_response_data( 'Create API: ' . $response );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        $response = '{"code":4000,"message":"There was an error processing your request. Please try again."}';
    }

    curl_close( $curl );

    // Decode the response
    $response_data = json_decode( $response, true );
    // extract order_number from response
    $order_number = $response_data['data']['Order_Number'];

    // Check the response code
    if ( $response_data['code'] !== 3000 ) {
        $error_message = $response_data['message'];
        $error_message = json_encode( $error_message, JSON_PRETTY_PRINT );
        wc_add_notice( 'API Error: ' . $error_message, 'error' );
    } else {
        // Store the unique ID in session for later use
        WC()->session->set( 'api_unique_id', $unique_id );
        // store order number in session
        WC()->session->set( 'woa_order_number', $order_number );
        // store poster language in session
        WC()->session->set( 'poster_language', $poster_language );
    }
}
// Order Creation API Integration
add_action( 'woocommerce_checkout_process', 'woa_create_order_with_api' );

function woa_save_unique_id_to_order( $order, $data ) {
    // Get the unique ID from the session
    $unique_id = WC()->session->get( 'api_unique_id' );
    // Get order number from session
    $order_number = WC()->session->get( 'woa_order_number' );
    // Get poster language from session
    $poster_language = WC()->session->get( 'poster_language' );

    // Save the unique ID and order number to order meta
    if ( $unique_id && $order_number ) {
        $order->update_meta_data( '_order_unique_id', $unique_id );
        $order->update_meta_data( '_woa_order_number', $order_number );
        $order->update_meta_data( '_poster_language', $poster_language );

        // Clear the session variables
        WC()->session->set( 'api_unique_id', null );
        WC()->session->set( 'woa_order_number', null );
        WC()->session->set( 'poster_language', null );
    }
}
// Save unique ID to order
add_action( 'woocommerce_checkout_create_order', 'woa_save_unique_id_to_order', 20, 2 );

function woa_woo_update_order_status( $order_id, $old_status, $new_status ) {

    // Check if the status is changing to "cancelled"
    if ( $new_status === 'cancelled' ) {

        // get order unique number from order meta
        $order     = wc_get_order( $order_id );
        $unique_id = $order->get_meta( '_order_unique_id', true );

        // Prepare data to be sent to the API
        $api_data = [
            'Auth_String'    => '525HRD7867200143000',
            'Account_Number' => '60016',
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
    $reference_number = get_post_meta( $order_id, '_reference_number', true );
    $unique_id        = $order->get_meta( '_order_unique_id', true );
    $poster_language  = $order->get_meta( '_poster_language', true );

    // Static data for missing fields
    $order_received_date = date( 'm-d-Y' ); // current date
    $order_type          = 'Poster Replacement Solution (With Initial All-In-One Poster)';

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => '60016',
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

    // put_api_response_data( json_encode( $api_data ) );

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
    put_api_response_data( 'Update API: ' . $response );
    curl_close( $curl );
}
// Hook into the order save action after items are saved
add_action( 'woocommerce_update_order', 'woa_update_order_with_api', 10, 2 );


// Hook into the order edit page to display additional information
add_action( 'woocommerce_admin_order_data_after_billing_address', 'woa_display_order_details_from_api', 11, 1 );
function woa_display_order_details_from_api( $order ) {

    // Get the order ID
    $order_id = $order->get_id();
    // Get the order number from order meta
    $order_number   = $order->get_meta( '_woa_order_number' );
    $account_number = '60016';

    // Make the API call to retrieve order details
    $api_response = woa_make_api_call_for_order_details( $order_id, $account_number, $order_number );

    if ( $api_response && $api_response['code'] === 3000 ) {
        $order_data = $api_response['data'][0];

        // Display the retrieved information in a table view
        $html = <<<EOD
        <div class="order_details_from_api">
            <h2>Order Details From API</h2>
            <table style="text-align:left">
                <tr>
                    <th>First Name</th>
                    <td>{$order_data['Subform_ID.Client_First_Name']}</td>
                </tr>
                <tr>
                    <th>Last Name</th>
                    <td>{$order_data['Subform_ID.Client_Last_Name']}</td>
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
                    <th>Order Received Date</th>
                    <td>{$order_data['Date_Order_Received']}</td>
                </tr>
                <tr>
                    <th>Poster Language</th>
                    <td>{$order_data['Poster_Language']}</td>
                </tr>
                <tr>
                    <th>Order Number</th>
                    <td>{$order_data['Order_Number']}</td>
                </tr>
                <tr>
                    <th>Order Unique ID</th>
                    <td>{$order_data['Subform_ID.Reference_Number']}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{$order_data['Subform_ID.Order_Status']}</td>
                </tr>
            </table>
        </div>
        EOD;

        echo $html;
    }
}

function woa_make_api_call_for_order_details( $order_id, $account_number, $order_number ) {

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
    // put_api_response_data( $response );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        curl_close( $curl );
        return false;
    }

    curl_close( $curl );

    return json_decode( $response, true );
}