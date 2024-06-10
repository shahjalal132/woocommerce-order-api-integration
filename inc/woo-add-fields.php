<?php

// Add custom checkout fields
add_action( 'woocommerce_after_order_notes', 'add_custom_checkout_fields' );
function add_custom_checkout_fields( $checkout ) {
    echo '<div id="custom_checkout_field"><h2>' . __( 'Custom Fields' ) . '</h2>';

    woocommerce_form_field( 'account_number', array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Account Number' ),
        'placeholder' => __( 'Enter your account number' ),
        'required'    => true,
    ), $checkout->get_value( 'account_number' ) );

    woocommerce_form_field( 'reference_number', array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Reference Number' ),
        'placeholder' => __( 'Enter your reference number' ),
        'required'    => true,
    ), $checkout->get_value( 'reference_number' ) );

    woocommerce_form_field( 'po_number', array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'PO Number' ),
        'placeholder' => __( 'Enter your PO number' ),
        'required'    => true,
    ), $checkout->get_value( 'po_number' ) );

    woocommerce_form_field( 'poster_state', array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Poster State' ),
        'placeholder' => __( 'Enter your Poster State' ),
        'required'    => true,
    ), $checkout->get_value( 'poster_state' ) );

    woocommerce_form_field( 'poster_language', array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Poster Language' ),
        'placeholder' => __( 'Enter your Poster Language' ),
        'required'    => true,
    ), $checkout->get_value( 'poster_language' ) );

    echo '</div>';
}


// Validate Custom Fields
add_action( 'woocommerce_checkout_process', 'validate_custom_checkout_fields' );
function validate_custom_checkout_fields() {
    if ( empty( $_POST['account_number'] ) ) {
        wc_add_notice( __( 'Please enter an account number.' ), 'error' );
    }
    if ( empty( $_POST['reference_number'] ) ) {
        wc_add_notice( __( 'Please enter a reference number.' ), 'error' );
    }
    if ( empty( $_POST['po_number'] ) ) {
        wc_add_notice( __( 'Please enter a PO number.' ), 'error' );
    }
    if ( empty( $_POST['poster_state'] ) ) {
        wc_add_notice( __( 'Please enter a poster state.' ), 'error' );
    }
    if ( empty( $_POST['poster_language'] ) ) {
        wc_add_notice( __( 'Please enter a poster language.' ), 'error' );
    }
}


// Save Custom Fields
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_fields' );
function save_custom_checkout_fields( $order_id ) {
    if ( !empty( $_POST['account_number'] ) ) {
        update_post_meta( $order_id, '_account_number', sanitize_text_field( $_POST['account_number'] ) );
    }
    if ( !empty( $_POST['reference_number'] ) ) {
        update_post_meta( $order_id, '_reference_number', sanitize_text_field( $_POST['reference_number'] ) );
    }
    if ( !empty( $_POST['po_number'] ) ) {
        update_post_meta( $order_id, '_po_number', sanitize_text_field( $_POST['po_number'] ) );
    }
    if ( !empty( $_POST['poster_state'] ) ) {
        update_post_meta( $order_id, '_poster_state', sanitize_text_field( $_POST['poster_state'] ) );
    }
    if ( !empty( $_POST['poster_language'] ) ) {
        update_post_meta( $order_id, '_poster_language', sanitize_text_field( $_POST['poster_language'] ) );
    }
}


// Display Custom Fields in Order Admin
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_checkout_fields_in_admin', 10, 1 );
function display_custom_checkout_fields_in_admin( $order ) {
    echo '<p><strong>' . __( 'Account Number' ) . ':</strong> ' . get_post_meta( $order->get_id(), '_account_number', true ) . '</p>';
    echo '<p><strong>' . __( 'Reference Number' ) . ':</strong> ' . get_post_meta( $order->get_id(), '_reference_number', true ) . '</p>';
    echo '<p><strong>' . __( 'PO Number' ) . ':</strong> ' . get_post_meta( $order->get_id(), '_po_number', true ) . '</p>';
    echo '<p><strong>' . __( 'Poster State' ) . ':</strong> ' . get_post_meta( $order->get_id(), '_poster_state', true ) . '</p>';
    echo '<p><strong>' . __( 'Poster Language' ) . ':</strong> ' . get_post_meta( $order->get_id(), '_poster_language', true ) . '</p>';
}

