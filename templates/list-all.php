<?php
global $current_user;
$show_all_users = $current_user->is_admin();
include_once('templates/list.php');
?>