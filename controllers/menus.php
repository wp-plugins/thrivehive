<?php
/*
Controller name: Menus
Controller description: Data manipulation methods for menus
*/
require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );
require_once( ABSPATH . 'wp-includes/nav-menu.php' );

class JSON_API_Menus_Controller {

	public function add_menu_item(){
		global $json_api;

		if(!$json_api->query->nonce) {
			$json_api->error("You must include a 'nonce' value to edit menus. Use the `get_nonce` Core API method.");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'add_menu_item');

		if(!wp_verify_nonce($json_api->query->nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		if(!current_user_can('edit_pages')){
			$json_api->error("You need to login with a user that has 'edit_posts' capacity.",'**auth**');
		}
		nocache_headers();

		$nav_menu_id = isset($_REQUEST['menu']) ? (int) $_REQUEST['menu'] : 0;

		if(isset($_REQUEST['page_id'])){
			$pages = $json_api->introspector->get_posts(array('page_id' => $_REQUEST['page_id']));
			$page = $pages[0];
		}
		else{
			$json_api->error("No page_id provided");
		}

			$menu_item = $this->map_page($page);
			$menu_items = array($menu_item);


			//save  call should be on an array
			$saved_items = wp_save_nav_menu_items($nav_menu_id, $menu_items);

			wp_update_nav_menu_item($nav_menu_id, $saved_items[0], $menu_item);


		return array(
			'menuid' => $nav_menu_id,
			'items' => $saved_items);
	}

	public function get_nav_menu()
	{
		global $json_api;
		if(!isset($_REQUEST['menu_name'])){
			$json_api->error("You must specify the name of a menu to access it");
		}

		$ret = $this->get_nav_menus();
		$menus = $ret['menus'];

		foreach ($menus as $value){
			if($value->name == $_REQUEST['menu_name']){
				return $value;
			}
		}
		$json_api->error("No menu with the indicated name could be found");
	}

	public function get_nav_menus()
	{
		return array('menus' => wp_get_nav_menus(array('orderby' => 'name')));
	}

	private function map_page($page){
		$menu_item = array(
			'menu-item-object-id' => $page->id, 
			'menu-item-object' => "page",
			'menu-item-position' => 0,
			'menu-item-type' => "post_type",
			'menu-item-title' => $page->title);

		return $menu_item;
	}

	//Not particularly useful
	public function get_sidebars(){
		global $wp_registered_sidebars;

		return $wp_registered_sidebars;
	}

	public function set_logo(){
		global $json_api;

		//Image should be uploaded by this point
		if(!isset($_REQUEST['image_id'])){
			$json_api->error("You must include the media gallery image id to add");
		}

		if(!$json_api->query->nonce) {
			$json_api->error("You must include a 'nonce' value to edit menus. Use the `get_nonce` Core API method.");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'set_logo');

		if(!wp_verify_nonce($json_api->query->nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$image_id = $_REQUEST['image_id'];
  		$image_data = wp_get_attachment_image_src($image_id, "medium");

  		update_option('th_site_logo', $image_data[0]);

  		return array('option' => get_option('th_site_logo'));
	}
}