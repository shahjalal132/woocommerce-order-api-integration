<?php

function poa_create_order() {

    // Get the WooCommerce cart object
    $cart = WC()->cart;

    // put_api_response_data( json_encode($cart) );

    // Initialize the poster language variable
    $poster_language = 'English'; // default value
    // Initialize the order type variable
    $order_type = '';
    // Initialize the order state variable
    $poster_state = '';

    // Check if the cart is not empty
    if ( !$cart->is_empty() ) {
        // Loop through cart items
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

            // Get the product ID
            $product_id = $cart_item['product_id'];
            // Get the product object
            $product = wc_get_product( $product_id );

            // Retrieve product information
            $product_title = $product->get_title();

            // Convert Title to an array to get State
            $words = explode( ' ', $product_title );
            // Retrieve First word.
            $poster_state = $words[0];

            // Check if the cart item has the desired variation attribute
            if ( isset( $cart_item['variation']['attribute_pa_language'] ) ) {
                // Get the attribute value
                $poster_language = sanitize_text_field( $cart_item['variation']['attribute_pa_language'] );

                // Check if the cart item has the desired variation attribute for order type
                if ( isset( $cart_item['variation']['attribute_pa_option'] ) ) {
                    // Get the attribute value for order type
                    $order_type = sanitize_text_field( $cart_item['variation']['attribute_pa_option'] );
                }

                // If the language is 'both', create two orders
                if ( $poster_language === 'both' ) {
                    // Create order for English
                    create_order_for_language( $poster_state, 'English' );
                    // Create order for Spanish
                    create_order_for_language( $poster_state, 'Spanish' );
                    return; // Stop the function after creating both orders
                }
                break; // Stop the loop after finding the first match
            }
        }
    }

    // If not 'both', create a single order
    create_order_for_language( $poster_state, $poster_language );
}

function create_order_for_language( $poster_state, $poster_language ) {

    // Retrieve billing checkout fields
    $billing_first_name = sanitize_text_field( $_POST['billing_first_name'] );
    $billing_last_name  = sanitize_text_field( $_POST['billing_last_name'] );
    $billing_company    = sanitize_text_field( $_POST['billing_company'] );
    $billing_address_1  = sanitize_text_field( $_POST['billing_address_1'] );
    $billing_address_2  = sanitize_text_field( $_POST['billing_address_2'] );
    $billing_city       = sanitize_text_field( $_POST['billing_city'] );
    $billing_state      = sanitize_text_field( $_POST['billing_state'] );
    $billing_postcode   = sanitize_text_field( $_POST['billing_postcode'] );
    $billing_email      = sanitize_email( $_POST['billing_email'] );
    $billing_phone      = sanitize_text_field( $_POST['billing_phone'] );

    // Check if 'Ship to a different address' is checked
    $ship_to_different_address = isset( $_POST['ship_to_different_address'] ) && $_POST['ship_to_different_address'] === '1';

    if ( $ship_to_different_address ) {
        // Retrieve shipping checkout fields
        $shipping_first_name = sanitize_text_field( $_POST['shipping_first_name'] );
        $shipping_last_name  = sanitize_text_field( $_POST['shipping_last_name'] );
        $shipping_company    = sanitize_text_field( $_POST['shipping_company'] );
        $shipping_address_1  = sanitize_text_field( $_POST['shipping_address_1'] );
        $shipping_address_2  = sanitize_text_field( $_POST['shipping_address_2'] );
        $shipping_city       = sanitize_text_field( $_POST['shipping_city'] );
        $shipping_state      = sanitize_text_field( $_POST['shipping_state'] );
        $shipping_postcode   = sanitize_text_field( $_POST['shipping_postcode'] );
    } else {
        // Use billing address as shipping address
        $shipping_first_name = $billing_first_name;
        $shipping_last_name  = $billing_last_name;
        $shipping_company    = $billing_company;
        $shipping_address_1  = $billing_address_1;
        $shipping_address_2  = $billing_address_2;
        $shipping_city       = $billing_city;
        $shipping_state      = $billing_state;
        $shipping_postcode   = $billing_postcode;
    }

    // Generate a unique ID
    $unique_id = 'order_' . time() . '_' . $poster_language;

    // Static data for missing fields
    $order_received_date = date( 'm-d-Y' ); // current date
    $order_type          = 'E-Update Service (With Initial All-In-One Poster)';

    // Prepare data to be sent to the API
    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Account_Number'          => '60016',
        'Date_Order_Received'     => $order_received_date,
        'Client_Company'          => $shipping_company,
        'Unique_ID'               => $unique_id,
        'Client_First_Name'       => $shipping_first_name,
        'Client_Last_Name'        => $shipping_last_name,
        'Client_Street_Address_1' => $shipping_address_1,
        'Client_Street_Address_2' => $shipping_address_2,
        'Client_City'             => $shipping_city,
        'Client_State'            => $shipping_state,
        'Client_ZIP'              => $shipping_postcode,
        'Client_Email_Address'    => $billing_email,
        'Client_Phone_Number'     => $billing_phone,
        'Order_Type'              => $order_type,
        'Poster_State'            => $poster_state,
        'Poster_Language'         => $poster_language,
    ];

    // Call the API
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
    // put_api_response_data( $response );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        $response = '{"code":4000,"message":"There was an error processing your request. Please try again."}';
    }

    curl_close( $curl );

    // Decode the response
    $response_data = json_decode( $response, true );
    // Extract order_number from response
    $order_number = $response_data['data']['Order_Number'];

    // Check the response code
    if ( $response_data['code'] !== 3000 ) {
        $error_message = $response_data['message'];
        $error_message = json_encode( $error_message, JSON_PRETTY_PRINT );
        wc_add_notice( 'API Error: ' . $error_message, 'error' );
    } else {
        // Store the unique ID in session for later use
        WC()->session->set( 'api_unique_id', $unique_id );
        // Store order number in session
        WC()->session->set( 'woa_order_number', $order_number );
        // Store poster language in session
        WC()->session->set( 'poster_language', $poster_language );
    }
}

// Order Creation API Integration
add_action( 'woocommerce_checkout_process', 'poa_create_order' );

function poa_save_order_meta_data( $order, $data ) {

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
add_action( 'woocommerce_checkout_create_order', 'poa_save_order_meta_data', 20, 2 );

/**
 * Insert order data to database
 * 
 * @param int $order_id
 */
function poa_insert_order_data_db_after_order_create( $order_id ) {

    // Get order
    $order = wc_get_order( $order_id );
    // Get order number
    $order_number = $order->get_meta( '_woa_order_number', true );

    // Get Order details from api
    $get_order_details = poa_get_order_details_from_api( $order_number );
    // Insert order data to database
    poa_insert_order_data_db( $order_id, $get_order_details );

}
add_action( 'woocommerce_thankyou', 'poa_insert_order_data_db_after_order_create', 20, 2 );

function poa_cancel_order_and_status( $order_id, $old_status, $new_status ) {

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
        // put_api_response_data( 'Cancel API: ' . $response );
        curl_close( $curl );
    }
}
// Hook the function to the WooCommerce order status changed action
add_action( 'woocommerce_order_status_changed', 'poa_cancel_order_and_status', 10, 3 );

function poa_update_order( $order_id, $items ) {

    // Get the order object
    $order = wc_get_order( $order_id );

    if ( !$order ) {
        return;
    }

    // Retrieve the billing address data
    $billing_first_name = $order->get_billing_first_name();
    $billing_last_name  = $order->get_billing_last_name();
    $billing_company    = $order->get_billing_company();
    $billing_address_1  = $order->get_billing_address_1();
    $billing_address_2  = $order->get_billing_address_2();
    $billing_city       = $order->get_billing_city();
    $billing_state      = $order->get_billing_state();
    $billing_postcode   = $order->get_billing_postcode();
    $billing_email      = $order->get_billing_email();
    $billing_phone      = $order->get_billing_phone();

    // Check if shipping address is different
    $ship_to_different_address = $order->get_shipping_address_1() ? true : false;

    if ( $ship_to_different_address ) {
        // Retrieve the shipping address data
        $shipping_first_name = $order->get_shipping_first_name();
        $shipping_last_name  = $order->get_shipping_last_name();
        $shipping_company    = $order->get_shipping_company();
        $shipping_address_1  = $order->get_shipping_address_1();
        $shipping_address_2  = $order->get_shipping_address_2();
        $shipping_city       = $order->get_shipping_city();
        $shipping_state      = $order->get_shipping_state();
        $shipping_postcode   = $order->get_shipping_postcode();
    } else {
        // Use billing address as shipping address
        $shipping_first_name = $billing_first_name;
        $shipping_last_name  = $billing_last_name;
        $shipping_company    = $billing_company;
        $shipping_address_1  = $billing_address_1;
        $shipping_address_2  = $billing_address_2;
        $shipping_city       = $billing_city;
        $shipping_state      = $billing_state;
        $shipping_postcode   = $billing_postcode;
    }

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
        'Client_Company'          => $shipping_company,
        'Reference_Number'        => $reference_number,
        'Unique_ID'               => $unique_id,
        'Client_First_Name'       => $shipping_first_name,
        'Client_Last_Name'        => $shipping_last_name,
        'Client_Street_Address_1' => $shipping_address_1,
        'Client_Street_Address_2' => $shipping_address_2,
        'Client_City'             => $shipping_city,
        'Client_State'            => $shipping_state,
        'Client_ZIP'              => $shipping_postcode,
        'Client_Email_Address'    => $billing_email,
        'Client_Phone_Number'     => $billing_phone,
        'Order_Type'              => $order_type,
        'Poster_State'            => $shipping_state,
        'Poster_Language'         => $poster_language,
    ];

    // Debugging purpose
    // put_api_response_data(json_encode($api_data));

    $curl = curl_init();

    curl_setopt_array( $curl, [
        CURLOPT_URL            => 'https://www.posterelite.com/api/Order_Update.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => http_build_query( $api_data ),
    ] );

    $response = curl_exec( $curl );

    if ( curl_errno( $curl ) ) {
        $error_msg = curl_error( $curl );
        error_log( 'Curl error: ' . $error_msg );
        $response = '{"code":4000,"message":"There was an error processing your request. Please try again."}';
    }

    curl_close( $curl );
}
// Hook into the order save action after items are saved
add_action( 'woocommerce_update_order', 'poa_update_order', 10, 2 );


// Hook into the order edit page to display additional information
add_action( 'woocommerce_admin_order_data_after_billing_address', 'poa_get_order_and_display', 11, 1 );
function poa_get_order_and_display( $order ) {

    // Get the order ID
    $order_id = $order->get_id();
    // Get the order number from order meta
    $order_number = $order->get_meta( '_woa_order_number' );

    // Make the API call to retrieve order details
    $api_response = poa_get_order_details_from_api( $order_number );

    // put_api_response_data( json_encode( $api_response ) );
    // poa_insert_order_data_db( $order_id, $api_response );

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

/**
 * Fetch Order details from api by order_number
 *
 * @param string $order_number
 */
function poa_get_order_details_from_api( $order_number ) {

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
                'Account_Number' => '60016',
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
