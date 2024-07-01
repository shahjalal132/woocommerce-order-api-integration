<?php

// Modify checkout company field to required
add_filter( 'woocommerce_checkout_fields', 'woa_make_company_field_required' );
function woa_make_company_field_required( $fields ) {
    // Make the company field required
    $fields['billing']['billing_company']['required'] = true;

    return $fields;
}

// Add custom field to the edit order form
add_action( 'woocommerce_admin_order_data_after_billing_address', 'woa_add_custom_field_to_edit_order_form' );
function woa_add_custom_field_to_edit_order_form( $order ) {
    $reference_number = get_post_meta( $order->get_id(), '_reference_number', true );
    ?>
    <div class="form-field form-field-wide" style="margin-bottom: 20px;">
        <label for="reference_number"><?php _e( 'Reference Number', 'woocommerce' ); ?></label>
        <input type="text" name="reference_number" id="reference_number" placeholder="Enter your reference number"
            value="<?php echo esc_attr( $reference_number ); ?>" required>
    </div>
    <?php
}

// Validate the custom field before saving the order
add_action( 'woocommerce_admin_order_data_before_save', 'woa_validate_custom_field_before_save', 10, 1 );
function woa_validate_custom_field_before_save( $order ) {
    if ( isset( $_POST['reference_number'] ) && empty( $_POST['reference_number'] ) ) {
        wc_add_notice( __( 'Reference Number is a required field.', 'woocommerce' ), 'error' );
        // Stop the order from being saved
        remove_action( 'woocommerce_process_shop_order_meta', 'woa_save_custom_field_value' );
    }
}

// Save the custom field value when the order is updated
add_action( 'woocommerce_process_shop_order_meta', 'woa_save_custom_field_value' );
function woa_save_custom_field_value( $order_id ) {
    if ( isset( $_POST['reference_number'] ) ) {
        update_post_meta( $order_id, '_reference_number', sanitize_text_field( $_POST['reference_number'] ) );
    }
}


