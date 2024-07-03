<?php

// Create sync_order_status Table When Plugin Activated
function poa_create_order_status_table() {

    global $wpdb;

    $table_name      = $wpdb->prefix . 'sync_order_status';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT,
        order_id INT NOT NULL UNIQUE,
        order_data TEXT NULL,
        order_status VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}