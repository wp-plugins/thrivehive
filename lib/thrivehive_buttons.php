<?php
/**
*Retrieves the desired thrivehive button from the database
*@param int $id id of the button to fetch
*@return array containing row data for the button
**/
function get_thrivehive_button($id){
	global $wpdb;
	$table_name = thrivehive_table_name();
	return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE id = %d;", $id), ARRAY_A);
}
/**
*Retrieves all thrivehive buttons from the database
*@return array containing row data for the buttons
**/
function get_thrivehive_buttons(){
	global $wpdb;
	$table_name = thrivehive_table_name();
	return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name), ARRAY_A);
}
/**
*Sets the data for an existing thrivehive button to the specified values
*@param int $id id of the button to update
*@param mixed[] $data the date to set the button's values to
*@return array containing row data for the button
**/
function set_thrivehive_button($id, $data){
	global $wpdb;
	$table_name = thrivehive_table_name();
	$wpdb->query($wpdb->prepare("UPDATE " . $table_name . " SET text = %s, 
															norm_gradient1 = %s, 
															norm_gradient2 = %s, 
															hover_gradient1 = %s, 
															hover_gradient2 = %s,
															norm_border_color = %s,
															hover_border_color = %s,
															norm_text_color = %s,
															hover_text_color = %s,
															generated_css = %s,
															url = %s WHERE id = %d ", 
															$data['text'],
															$data['norm_gradient1'],
															$data['norm_gradient2'],
															$data['hover_gradient1'],
															$data['hover_gradient2'],
															$data['norm_border_color'],
															$data['hover_border_color'],
															$data['norm_text_color'],
															$data['hover_text_color'],
															$data['generated_css'],
															$data['url'], $id));
	return get_thrivehive_button($id);
}
/**
*Create a new thrivehive button
*@param mixed[] $data the date to set the button's values to
*@return array containing row data for the button
**/
function create_thrivehive_button($data){
	global $wpdb;
	$table_name = thrivehive_table_name();
	$wpdb->insert($table_name, $data);
	return get_thrivehive_button($wpdb->insert_id);
}

function delete_thrivehive_button($id){
	global $wpdb;
	$table_name = thrivehive_table_name();
	$wpdb->delete($table_name, array('id' => $id));
}

/**
*Gets the string for the button table name
*@return string the table name
**/
function thrivehive_table_name(){
	global $wpdb;
	return $wpdb->prefix . "TH_" . "buttons";
}
?>