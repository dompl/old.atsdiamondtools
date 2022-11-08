<?php

/**
 * Return a list of all tables used by this plugin.
 */
function wcsc_table_names() {
    return array(
        WCSC::table_name(),
        WCSC_Error_Logs::table_name()
    );
}

/**
 * Set up the sql tables.
 */
function wcsc_setup_tables() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_name = WCSC::table_name();
    $sql = "CREATE TABLE " . $table_name . " (
        id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
        cron_key varchar(255) NOT NULL,
        hook_name varchar(255) NOT NULL,
        start BIGINT(20) NOT NULL,
        end BIGINT(20) NULL,
        result TEXT NULL,
        PRIMARY KEY  (id),
        KEY cron_key (cron_key),
        KEY cron_key_hook_name (cron_key, hook_name)
        )
        ENGINE = innodb;";
    dbDelta($sql);

    $table_name = WCSC_Error_Logs::table_name();
    $sql = "CREATE TABLE " . $table_name . " (
        id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
        cron_key varchar(255) NOT NULL,
        hook_name varchar(255) NULL,
        sent_date DATETIME NULL,
        PRIMARY KEY  (id),
        KEY cron_key (cron_key)
        )
        ENGINE = innodb;";
    dbDelta($sql);

    update_option( '_wcsc_version', WCSC_VERSION );
}

/**
 * Drop the tables.
 */
function wcsc_destroy_tables() {
    global $wpdb;

    $table_name = WCSC::table_name();
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );

    $table_name = WCSC_Error_Logs::table_name();
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );
}