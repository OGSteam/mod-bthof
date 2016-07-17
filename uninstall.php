<?php
if (!defined('IN_SPYOGAME')) {
    die("Hacking attempt");
}

global $db, $table_prefix;
$mod_uninstall_name = "bt_hof";
$mod_uninstall_table = $table_prefix."bthof_conf".', '.$table_prefix."bthof_flottes";
uninstall_mod($mod_uninstall_name, $mod_uninstall_table);
?>