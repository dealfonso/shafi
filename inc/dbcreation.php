<?php
require_once(__SHAFI_INC . 'user.php');

$db_tables_prefix = '';
$db_files_table_name = $db_tables_prefix . 'files';
$db_token_table_name = $db_tables_prefix . 'tokens';
$db_users_table_name = $db_tables_prefix . 'users';
$db_log_table_name = $db_tables_prefix . 'log';

function create_db() {
    global $wpdb;
    global $db_files_table_name;
    global $db_token_table_name;
    global $db_users_table_name;

    // oid is the ID used to reference the file. It is unique so it could be used as ID; the problem is that
    $sql = "
    CREATE TABLE IF NOT EXISTS $db_files_table_name (
        id varchar(36) NOT NULL,
        owner text NOT NULL,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        expired datetime DEFAULT NULL,
        name text NOT NULL,
        path text NOT NULL,
        state VARCHAR(1) DEFAULT 'a' NOT NULL,
        stid VARCHAR(36) NOT NULL,
        size bigint NOT NULL,
        PRIMARY KEY (id)
    );";

    $result = $wpdb->query($sql);
    if ($result === false) {
        return false;
    }

    // Using id because it is easier to manage with my classes; token is a uuidv4 (32bytes + 4 hypens 8-4-4-4-12)
    $sql = "
    CREATE TABLE IF NOT EXISTS $db_token_table_name (
        id bigint NOT NULL AUTO_INCREMENT,
        oid varchar(36) NOT NULL,
        password varchar(255) DEFAULT NULL,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        expired datetime DEFAULT NULL,
        exp_secs int DEFAULT NULL,
        exp_hits int DEFAULT NULL,
        hits int DEFAULT 0 NOT NULL,
        fileid varchar(36) NOT NULL,
        state VARCHAR(1) DEFAULT 'a' NOT NULL,
        PRIMARY KEY (id)
    );";

    $result = $wpdb->query($sql);
    if ($result === false) {
        return false;
    }

    $sql = "
    CREATE TABLE IF NOT EXISTS $db_users_table_name (
        id bigint NOT NULL AUTO_INCREMENT,
        username varchar(32) NOT NULL,
        password varchar(255) DEFAULT NULL,
        permissions varchar(5) DEFAULT 'u',
        PRIMARY KEY (id), UNIQUE (username)
    );";

    $result = $wpdb->query($sql);
    if ($result === false) {
        return false;
    }
    return true;
}

function create_first_user($username, $password) {
    // Create the first user
    $firstuser = new SHAUser();
    $firstuser->set_field('username', $username);
    $firstuser->set_field('permissions', 'ua');
    $firstuser->set_password($password, true);
    return $firstuser->create();
}