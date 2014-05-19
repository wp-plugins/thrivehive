<?php
function get_theme_options_by_name($theme){
	global $wpdb;
	$table_name = thrivehive_theme_options_table_name();
	return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE theme = %s;", $theme), ARRAY_A);
}

function add_theme_options($theme, $options, $version){
	global $wpdb;
	$data = array('theme' => $theme, 'options' => $options, 'version' => $version);
	$table_name = thrivehive_theme_options_table_name();
	return $wpdb->insert($table_name, $data);
}

function update_theme_options($theme, $options, $version){
	global $wpdb;
	$table_name = thrivehive_theme_options_table_name();
	$wpdb->query($wpdb->prepare("UPDATE " . $table_name . " SET options = %s, version= %s WHERE theme = %s", $options, $version, $theme));
}

function thrivehive_theme_options_table_name(){
	global $wpdb;
	return $wpdb->prefix . "TH_" . "theme_options";
}
?>