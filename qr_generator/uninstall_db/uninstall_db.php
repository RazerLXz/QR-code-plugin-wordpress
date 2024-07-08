<?php
// uninstall-plugin.php

global $wpdb;

$sql_delete_tables = "DROP TABLE IF EXISTS {$wpdb->prefix}entrace_code_table, {$wpdb->prefix}users_table;";

$wpdb->query($sql_delete_tables);