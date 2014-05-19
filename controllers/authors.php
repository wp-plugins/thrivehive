<?php
class JSON_API_Authors_Controller{

	public function create_author(){
		global $json_api;
		$nonce_id = $json_api->get_nonce_id('authors', 'create_author');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		if(!isset($_REQUEST['user'])){
			$json_api->error("You must specify the `user` name");
		}

		$username = $_REQUEST['user'];

		$password = $this->randString(10);

		$newUser = wp_create_user($username, $password);

		if(is_a($newUser, 'WP_Error')){
			$json_api->error($newUser->errors['existing_user_login'][0]);
		}

		$up_data = array('ID' => $newUser, 'role' => 'author');

		wp_update_user($up_data);

		$user = get_user_by('id', $newUser);
		$slug = $user->data->user_nicename;

		return array('slug' => $slug);
	}

	private function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'){
	    $str = '';
	    $count = strlen($charset);
	    while ($length--) {
	        $str .= $charset[mt_rand(0, $count-1)];
	    }
	    return $str;
	}

	public function get_all_users(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('authors', 'get_all_users');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$args = array('fields' => array('user_login', 'user_nicename', 'ID', 'display_name'));

		$users = get_users($args);

		return array('users' => $users);
	}

	public function delete_user(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('authors', 'delete_user');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$target_id = get_user_by('slug', $_REQUEST['target']);
		if(isset($_REQUEST['reassign'])){
			$reassign_id = get_user_by('slug', $_REQUEST['reassign']);

			wp_delete_user($target_id, $reassign_id);
		}
		else{
			wp_delete_user($target_id);
		}


		return array();
	}

	public function update_display_name(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('authors', 'update_display_name');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$user = get_user_by('slug', $_REQUEST['target']);

		$up_data = array('ID' => $user->ID, 'display_name' => $_REQUEST['display_name']);

		wp_update_user($up_data);

		return array();
	}

	public function update_google_plus_profile(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('authors', 'update_google_plus_profile');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$user = get_user_by('slug', $_REQUEST['user']);


		$res = update_user_meta($user->ID, 'googleplus', $_REQUEST['profile']);

		return array($res);
	}

	public function get_google_plus_profile(){
		$user = get_user_by('slug', $_REQUEST['user']);

		$res = get_user_meta($user->ID, 'googleplus', true);

		return array('profile' => $res);
	}
}
?>