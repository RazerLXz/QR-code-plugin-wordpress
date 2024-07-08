<?php
global $wpdb;
//-----------------------------------------------------------------------------------------
$entrace_code_table = $wpdb->prefix . 'entrace_code_table';
if($wpdb->get_var("SHOW TABLES LIKE '$entrace_code_table'") != $entrace_code_table) {
    $sql = "CREATE TABLE $entrace_code_table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        code VARCHAR(32) NOT NULL UNIQUE,
        creation_day DATE,
        death_day DATE,
        validate_day DATE,
        used TINYINT(1) DEFAULT 0,
        payment_method VARCHAR(255) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        client_email VARCHAR(255) NOT NULL,
        cliente_name  VARCHAR(255) NOT NULL,
        PRIMARY KEY  (id)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
//-----------------------------------------------------------------------------------------

$users_table = $wpdb->prefix . 'users_table';
if($wpdb->get_var("SHOW TABLES LIKE '$users_table'") != $users_table) {
    $sql = "CREATE TABLE $users_table (
        user_id INT(11) NOT NULL AUTO_INCREMENT,
        first_name  VARCHAR(255) NOT NULL,
        last_name  VARCHAR(255) NOT NULL,
        document_type  VARCHAR(255) NOT NULL,
        document_number  VARCHAR(255) NOT NULL,
        email  VARCHAR(191) NOT NULL UNIQUE,
        password_hash  VARCHAR(255) NOT NULL,
        PRIMARY KEY  (user_id)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

//-----------------------------------------------------------------------------------------


?>
