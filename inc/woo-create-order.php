<?php

function woo_create_order_callback() {

    $api_data = [
        'Auth_String'             => '525HRD7867200143000',
        'Client_ZIP'              => '1230',
        'Order_Type'              => 'ePoster Service',
        'Client_Company'          => 'Imjol',
        'Client_Street_Address_1' => 'Uttara, Dhaka, Bangladesh',
        'Client_City'             => 'Dhaka',
        'Client_First_Name'       => 'Shah',
        'Client_Last_Name'        => 'Jalal',
        'Account_Number'          => '60016',
        'Client_State'            => 'Dh',
        'Unique_ID'               => '44521587',
        'Referance_Number'        => '01740247505',
        'PO_Number'               => '00254',
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
            CURLOPT_POSTFIELDS     => $api_data,
        )
    );

    $response = curl_exec( $curl );

    curl_close( $curl );
    echo $response;

}

add_shortcode( 'woo_create_order', 'woo_create_order_callback' );



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
