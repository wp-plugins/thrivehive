<?php
function get_form_from_id($id){
	global $wpdb;
	$table_name = thrivehive_forms_table_name();
	return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE th_id = %d;", $id), ARRAY_A);
}

function add_thrivehive_form($th_id, $html){
	global $wpdb;
	$data = array('th_id' => $th_id, 'html' => $html);
	$table_name = thrivehive_forms_table_name();
	$wpdb->insert($table_name, $data);
}

function update_thrivehive_form($th_id, $html){
	global $wpdb;
	$table_name = thrivehive_forms_table_name();
	$wpdb->query($wpdb->prepare("UPDATE " . $table_name . " SET html = %s WHERE th_id = %d", $html, $th_id));
}

function thrivehive_forms_table_name(){
	global $wpdb;
	return $wpdb->prefix . "TH_" . "forms";
}
?>