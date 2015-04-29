<?php
/**
*Summary
*Controller name: Menus
*Controller description: Data manipulation methods for menus
*/
/**
*/
require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );
require_once( ABSPATH . 'wp-includes/nav-menu.php' );
include_once( ABSPATH . 'wp-content/plugins/thrivehive/lib/thrivehive_buttons.php');
include_once( ABSPATH . 'wp-content/plugins/thrivehive/lib/thrivehive_forms.php');
include_once( ABSPATH . 'wp-content/plugins/thrivehive/lib/thrivehive_theme_options.php');

/**
*Class related to controlling menu and setting options
*@package Controllers\Menus
*/
class JSON_API_Menus_Controller {	/**
	*Adds an item to the specified menu, otherwise add it to the default menu
	*@example URL - /menus/add_menu_item
	*@api
	*@return array containing the nav menu item object
	**/
	public function add_menu_item(){
		global $json_api;

		/*if(!$json_api->query->nonce) {
			$json_api->error("You must include a 'nonce' value to edit menus. Use the `get_nonce` Core API method.");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'add_menu_item');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		if(!current_user_can('edit_pages')){
			$json_api->error("You need to login with a user that has 'edit_posts' capacity.",'**auth**');
		}
		nocache_headers();
		$nav_menu_id = isset($_REQUEST['menu']) ? (int) $_REQUEST['menu'] : 0;

		if(isset($_REQUEST['target_id'])){
			$pages = $json_api->introspector->get_single_post($_REQUEST['target_id']);
			$page = $pages[0];
			$menu_item = $this->map_page($page);
		}
		else if(isset($_REQUEST['custom_url'])){
			$menu_item = $this->map_custom_url($_REQUEST['custom_url'], $_REQUEST['title'], $_REQUEST['target']);
		}
		else{
			$json_api->error("No target_id provided");
		}

			$menu_items = array($menu_item);

			//save  call should be on an array
			$saved_items = wp_save_nav_menu_items($nav_menu_id, $menu_items);

			wp_update_nav_menu_item($nav_menu_id, $saved_items[0], $menu_item);
			update_post_meta($saved_items[0]->ID, "_menu_item_target", $_REQUEST['target']);


		return array(
			'menuid' => $nav_menu_id,
			'items' => $saved_items);
	}

	/**
	*Gets the desired navigation menu from wordpress
	*@example URL - /menus/get_nav_menu
	*@api
	*@return array containing the nav menu object
	**/
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

	/**
	*Gets a list of all nav menus
	*@example URL - /menus/get_nav_menus
	*@api
	*@return array containing navigation menu json objects
	**/
	public function get_nav_menus()
	{
		return array('menus' => wp_get_nav_menus(array('orderby' => 'name')));
	}

	/**
	*Get the items which populate a navigation menu
	*@example URL - /menus/get_nav_menu_items
	*@api
	*@return array containing all nav menu items
	**/
	public function get_nav_menu_items()
	{
		global $json_api;

		if(!isset($_REQUEST['menu_id'])){
			$json_api->error("You must specify the id of the menu to access");
		}

		$ret = wp_get_nav_menu_items($_REQUEST['menu_id'], array());
		foreach ($ret as $item) {
			$page = get_post( (int) $item->object_id );
			if($page){
				$item->post_title = $page->post_title;
			}
		}

		return array('menu_items' => $ret);
	}

	/**
	*Update the menu items for a menu including their ordering
	*@example URL - /menus/update_nav_menu_items
	*@api
	*@return array containing the updated items
	**/
	public function update_nav_menu_items()
	{
		global $json_api;

		if(!isset($_REQUEST['menu_id'])){
			$json_api->error("You must specify the id of the menu to access");
		}

		if(!isset($_REQUEST['items'])){
			$json_api->error("You must include the items to be updated");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'update_nav_menu_items');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$itemstr = str_replace('\\', '', $_REQUEST['items']);
		$items = json_decode($itemstr);

		$current_items = wp_get_nav_menu_items($_REQUEST['menu_id'], array());
		foreach ($items as $item) {
			foreach ($current_items as $current_item) {
				if($item->ID == $current_item->ID)
				{
					$item_data = (array) wp_setup_nav_menu_item( get_post( $current_item->ID ) );
					$page = get_post( (int) $current_item->object_id );
					update_post_meta($current_item->ID, '_menu_item_menu_item_parent', (int) $item->menu_item_parent);
					update_post_meta($current_item->ID, '_menu_item_url', $item->url);
					$item_data['menu_order'] = $item->menu_order;
					$item_data['title'] = $item->title;
					$item_data['post_title'] = $item->title;
					wp_update_post($item_data);
					update_post_meta($item->ID, "_menu_item_target", $item->target == null ? "_self" : $item->target);
				}
			}
		}
		/*foreach ($current_items as $an_item) {
			$menu_item = $this->map_item($an_item);
			wp_update_nav_menu_item($_REQUEST['menu_id'], $an_item->ID, $menu_item);
		}*/

		return array('saved_items' => $items, 'menu_id' => $_REQUEST['menu_id']);

	}

	/**
	*Deletes a specified menu item
	*@example URL - /menus/delete_menu_item
	*@api
	*@return array containing success or failure
	**/
	public function delete_menu_item(){
		global $json_api;

		if(!isset($_REQUEST['item_id'])){
			$json_api->error("You must specify the ID of the menu item to delete");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'delete_menu_item');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$ret = wp_delete_post($_REQUEST['item_id'], true);

		if($ret){
			return array('message' => "success");
		}
		$json_api->error("There was an error deleting the post");
	}

	/**
	*Maps a page to a menu item so that we can create it
	*@param mixed[] $page the page to be mapped
	*@api
	*@return array containing all the menu item objects
	**/
	private function map_page($page){
		$menu_item = array(
			'menu-item-object-id' => $page['ID'],
			'menu-item-object' => "page",
			'menu-item-position' => 0,
			'menu-item-type' => "post_type",
			'menu-item-title' => $page['post_title']);

		return $menu_item;
	}

	/**
	*Maps a page to a menu item so that we can create it
	*@param mixed[] $page the page to be mapped
	*@api
	*@return array containing all the menu item objects
	**/
	private function map_custom_url($custom_url, $title, $target){
		$menu_item = array(
			'menu-item-object' => "custom",
			'menu-item-position' => 0,
			'menu-item-type' => "custom",
			'menu-item-title' => $title,
			'menu-item-url' => $custom_url,
			'menu-item-target' => $target
		);

		return $menu_item;
	}

	/**
	*Maps a menu item for updating
	*@param mixed[] $item the item to be mapped
	*@api
	*@return array containing all the menu-item properties
	**/
	private function map_item($item){
		$menu_item = array(
			'menu-item-db-id' => $item->ID,
			'menu-item-object-id' => $item->object_id,
			'menu_item-object' => $item->object,
			'menu-item-parent-id' => $item->menu_item_parent,
			'menu-item-position' => $item->menu_order,
			'menu-item-type' => 'custom',
			'menu-item-title' => $item->title,
			'menu-item-url' => $item->url,
			'menu-item-description' => $item->post_content,
			'menu-item-attr-title' => $item->post_excerpt,
			'menu-item-target' => $item->target,
            'menu-item-classes' => $item->classes,
            'menu-item-xfn' => $item->xfn,
            'menu-item-status' => $item->post_status);

		return $menu_item;
	}

	//Not particularly useful
	public function get_sidebars(){
		global $wp_registered_sidebars;

		return $wp_registered_sidebars;
	}

	/**
	*Sets the logo for the genesis header, only works with a genesis child theme
	*@example URL - /menus/set_logo
	*@api
	*@return array containing the option value for the logo
	**/
	public function set_logo(){
		global $json_api;

	  //Image should be uploaded by this point
		if(!isset($_REQUEST['image_id'])){
			$json_api->error("You must include the media gallery image id to add");
		}

		/*if(!$json_api->query->nonce) {
			$json_api->error("You must include a 'nonce' value to edit menus. Use the `get_nonce` Core API method.");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'set_logo');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$image_id = $_REQUEST['image_id'];
  		$image_data = wp_get_attachment_image_src($image_id, "medium");

		update_option('th_site_logo', $image_data[0]);

	    $this->set_theme_logo($image_data[1], $image_data[2], $image_data[0]);
	  	$this->set_theme_logo2($image_data[0], $image_data[1], $image_data[2], $image_id);

  		return array('option' => get_option('th_site_logo'));
	}
	/**
	*Adds the theme support options for the image
	*@param int $width the width of the image
	*@param int $height the height of the image
	*@param string $upload the uploaded url of the image
	**/
	private function set_theme_logo($width, $height, $upload){
	$file_name = get_stylesheet_directory().'/functions.php';
	$file_handler = fopen($file_name, "r");
	$tmp_file = fopen($file_name.'.tmp', "w");
	$replaced = false;
	while(!feof($file_handler)) {
	  $line = fgets($file_handler);
	  if(strpos($line, "genesis-custom-header") !== false){
	  	$line = trim($line);
		while(substr($line, -1) !== ';'){
		  $line = fgets($file_handler);
		  $line = trim($line);
		}
	  	$line = "add_theme_support( 'genesis-custom-header', array('flex-height' => true, 'width' => %d, 'height' => %d, 'header_image' => \"%s\" ) ); \n";
		$line = sprintf($line, $width, $height, $upload);
	  	$replaced = true;
	  }
	  fputs($tmp_file, $line);
	}
	fclose($file_handler); fclose($tmp_file);

	if(!$replaced){
	  	$line = "\n add_theme_support( 'genesis-custom-header', array('flex-height' => true, 'width' => %d, 'height' => %d, 'header_image' => \"%s\") );";
		$line = sprintf($line, $width, $height, $upload);
		file_put_contents($file_name, $line, FILE_APPEND);
	}
	else{
	  //copy($tmp_file, $file_name);
	  rename($file_name.'.tmp', $file_name);
	}
	$this->update_css($height);

	$image = wp_get_image_editor($upload);
	if(! is_wp_error($image)){
		$genesisloc = get_template_directory().'/images/favicon.ico';
		$themeloc = get_stylesheet_directory().'/images/favicon.ico';
		$image->resize(16,16, true);
		$image->save($genesisloc);
		$image->save($themeloc);
	}
	$genfile = get_template_directory().'/images/favicon.jpg';
	$themefile = get_stylesheet_directory().'/images/favicon.jpg';
	$newname = basename($genfile, ".jpg").".ico";
	rename($genfile, get_template_directory().'/images/'.$newname);
	$newname = basename($themefile, ".jpg").".ico";
	rename($themefile, get_stylesheet_directory().'/images/'.$newname);
  }

  private function update_css($height)
  {
  	$file_name = get_stylesheet_directory().'/style.css';
  	$file_handler = fopen($file_name, "r");
	$tmp_file = fopen($file_name.'.tmp', "w");
	$replaced = false;

	while(!feof($file_handler)) {
	  $line = fgets($file_handler);
	  if(strpos($line, "/* HEADER RESIZING") !== false){
	  	$line = trim($line);
		while(substr($line, -1) !== '}'){
		  $line = fgets($file_handler);
		  $line = trim($line);
		}
	  	$line = "
	  			/* HEADER RESIZING */ \n
	  			#header, .header-image .site-title a {
	  				mins-height: %dpx;
	  			}
	  			";
		$line = sprintf($line, $height);
	  	$replaced = true;
	  }
	  fputs($tmp_file, $line);
	}
	fclose($file_handler); fclose($tmp_file);

	if(!$replaced){
	  	$line = "
	  			\n /* HEADER RESIZING */ \n
					#header, .header-image .site-title a {
						min-height: %dpx;
					}
	  			";
		$line = sprintf($line, $height);
		file_put_contents($file_name, $line, FILE_APPEND);
	}
	else{
	  //copy($tmp_file, $file_name);
	  rename($file_name.'.tmp', $file_name);
	}

  }

  /**
  *Edits the meta for the logo image and sets theme values
  *@param string $url url of the image
  *@param int $width the width of the image
  *@param int $height the height of the image
  *@param int $id the id of the image
  **/
	private function set_theme_logo2($url, $width, $height, $id){
	$choice = array('url'=> $url, 'width' => $width, 'height' => $height, 'attachment_id' => $id);
	$header_image_data = (object) array(
	  'attachment_id' => $choice['attachment_id'],
	  'url'           => $choice['url'],
	  'thumbnail_url' => $choice['url'],
	  'height'        => $choice['height'],
	  'width'         => $choice['width'],
	);
	update_post_meta($choice['attachment_id'], '_wp_attachment_is_custom_header', get_stylesheet());
	set_theme_mod('header_image', $choice['url']);
	set_theme_mod('header_image_data', $header_image_data);
	set_theme_mod( 'header_textcolor', 'blank' );
	}

	public function get_theme_logo()
	{
		global $json_api;

		$logo = get_theme_mod('header_image');

		return array('logo' => $logo);
	}

	public function remove_theme_logo()
	{
		set_theme_mod('header_image', '');
		set_theme_mod('header_image_data', '');
		remove_theme_mod('header_textcolor');

		return array();
	}

	/**
	*Sets the tracking info option values for ThriveHive
	*@example URL - /menus/set_tracking_info
	*@api
	*@return array containing success or failure
	**/
	public function set_tracking_info(){
		global $json_api;

		if(!isset($_REQUEST['account_id'])){
			$json_api->error("You must include the ThriveHive account_id");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value. use the `get_nonce` Core API method");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'set_tracking_info');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		update_option('th_tracking_code', $_REQUEST['account_id']);

		if(isset($_REQUEST['form_html'])){
			update_option('th_form_html', $_REQUEST['form_html']);
		}
		if(isset($_REQUEST['phone_num'])){
			update_option('th_phone_number', $_REQUEST['phone_num']);
		}
		if(isset($_REQUEST['landing_id'])){
			update_option('th_landingform_id', $_REQUEST['landing_id']);
		}
		if(isset($_REQUEST['contactform_id'])){
			update_option('th_contactform_id', $_REQUEST['contactform_id']);
		}
		if(isset($_REQUEST['address'])){
			update_option('th_company_address', $_REQUEST['address']);
		}
		if(isset($_REQUEST['env'])){
			update_option('th_environment', $_REQUEST['env']);
		}
		if(isset($_REQUEST['homepage_seo'])){
			$seo_options = get_option('aioseop_options');

		    $seo_options['aiosp_home_title'] = stripslashes($_REQUEST['homepage_seo']);

		    update_option('aioseop_options', $seo_options);
		}

		return array('message' => 'success');
	}

	public function set_company_info()
	{
		global $json_api;

		if(!isset($_REQUEST['company_address'])){
			$json_api->error("You must include the `company_address`");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'set_company_info');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your `nonce` value was incorrect. Use the `get_nonce` API method");
		}

		update_option('th_company_address', $_REQUEST['company_address']);

		return array('message' => 'success');
	}

	/**
	*Gets custom thrivehive buttons
	*@example URL - /menus/get_buttons
	*@api
	*@return array of button objects and their properties
	**/
	public function get_buttons(){
		global $json_api;

		$buttons = get_thrivehive_buttons();

		return array('buttons' => $buttons);
	}

	/**
	*Gets a single thrivehive button
	*@example URL - /menus/get_button
	*@api
	*@return array containing the button and its properties
	**/
	public function get_button(){
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to retrieve");
		}
		$button = get_thrivehive_button($_REQUEST['id']);

		return array('button' => $button);
	}

	/**
	*Sets the button's values and updates it in the database
	*@example URL - /menus/set_button
	*@api
	*@return array containing the button with its new values
	**/
	public function set_button(){
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to edit");
		}
		if(!isset($_REQUEST['text'])){
			$json_api->error("You must include the `text` value");
		}
		if(!isset($_REQUEST['norm_gradient1'])){
			$json_api->error("You must include the `norm_gradient1` value");
		}
		if(!isset($_REQUEST['norm_gradient2'])){
			$json_api->error("You must include the `norm_gradient2` value");
		}
		if(!isset($_REQUEST['hover_gradient1'])){
			$json_api->error("You must include the `hover_gradient1` value");
		}
		if(!isset($_REQUEST['hover_gradient2'])){
			$json_api->error("You must include the `hover_gradient2` value");
		}
		if(!isset($_REQUEST['norm_border_color'])){
			$json_api->error("You must include the `norm_border_color` value");
		}
		if(!isset($_REQUEST['hover_border_color'])){
			$json_api->error("You must include the `hover_border_color` value");
		}
		if(!isset($_REQUEST['norm_text_color'])){
			$json_api->error("You must include the `norm_text_color` value");
		}
		if(!isset($_REQUEST['hover_text_color'])){
			$json_api->error("You must include the `hover_text_color` value");
		}
		if(!isset($_REQUEST['generated_css'])){
			$json_api->error("You must include the `generated_css` value");
		}
		if(!isset($_REQUEST['url'])){
			$json_api->error("You must include the redirect `url` value");
		}
		if(!isset($_REQUEST['target'])){
			$json_api->error("You must include the window `target` value");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'set_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$data = array(
			'text'=>$_REQUEST['text'],
			'norm_gradient1'=>$_REQUEST['norm_gradient1'],
			'norm_gradient2'=>$_REQUEST['norm_gradient2'],
			'hover_gradient1'=>$_REQUEST['hover_gradient1'],
			'hover_gradient2'=>$_REQUEST['hover_gradient2'],
			'norm_border_color'=>$_REQUEST['norm_border_color'],
			'hover_border_color'=>$_REQUEST['hover_border_color'],
			'norm_text_color'=>$_REQUEST['norm_text_color'],
			'hover_text_color'=>$_REQUEST['hover_text_color'],
			'generated_css'=>$_REQUEST['generated_css'],
			'url'=>$_REQUEST['url'],
			'target'=>$_REQUEST['target']
			);

		$button = set_thrivehive_button($_REQUEST['id'], $data);

		return array($button);
	}

	/**
	*Create a new thrivehive button with all the specified values
	*@example URL - /menus/create_button
	*@api
	*@return array containing the button created
	**/
	public function create_button()
	{
		global $json_api;

		if(!isset($_REQUEST['text'])){
			$json_api->error("You must include the `text` value");
		}
		if(!isset($_REQUEST['norm_gradient1'])){
			$json_api->error("You must include the `norm_gradient1` value");
		}
		if(!isset($_REQUEST['norm_gradient2'])){
			$json_api->error("You must include the `norm_gradient2` value");
		}
		if(!isset($_REQUEST['hover_gradient1'])){
			$json_api->error("You must include the `hover_gradient1` value");
		}
		if(!isset($_REQUEST['hover_gradient2'])){
			$json_api->error("You must include the `hover_gradient2` value");
		}
		if(!isset($_REQUEST['norm_border_color'])){
			$json_api->error("You must include the `norm_border_color` value");
		}
		if(!isset($_REQUEST['hover_border_color'])){
			$json_api->error("You must include the `hover_border_color` value");
		}
		if(!isset($_REQUEST['norm_text_color'])){
			$json_api->error("You must include the `norm_text_color` value");
		}
		if(!isset($_REQUEST['hover_text_color'])){
			$json_api->error("You must include the `hover_text_color` value");
		}
		if(!isset($_REQUEST['generated_css'])){
			$json_api->error("You must include the `generated_css` value");
		}
		if(!isset($_REQUEST['url'])){
			$json_api->error("You must include the redirect `url` value");
		}
		if(!isset($_REQUEST['target'])){
			$json_api->error("You must include the window `target` value");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'create_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}
		$data = array(
			'text'=>$_REQUEST['text'],
			'norm_gradient1'=>$_REQUEST['norm_gradient1'],
			'norm_gradient2'=>$_REQUEST['norm_gradient2'],
			'hover_gradient1'=>$_REQUEST['hover_gradient1'],
			'hover_gradient2'=>$_REQUEST['hover_gradient2'],
			'norm_border_color'=>$_REQUEST['norm_border_color'],
			'hover_border_color'=>$_REQUEST['hover_border_color'],
			'norm_text_color'=>$_REQUEST['norm_text_color'],
			'hover_text_color'=>$_REQUEST['hover_text_color'],
			'generated_css'=>$_REQUEST['generated_css'],
			'url'=>$_REQUEST['url'],
			'target'=>$_REQUEST['target']
			);
		$button = create_thrivehive_button($data);
		return array('button' => $button);
	}

	public function delete_button()
	{
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to edit");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'delete_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		delete_thrivehive_button($_REQUEST['id']);

		return array();
	}

	/**
	*Sets the theme to the specified target theme
	*@example URL - /menus/set_theme
	*@api
	*@return array containing success or failure
	**/
	public function set_theme(){
		global $json_api;
		if(!isset($_REQUEST['target_theme'])){
			$json_api->error("You must specify the `target_theme`");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must specify the `nonce`");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'set_theme');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect.");
		}

		$logo_data = get_theme_mod('header_image_data');
		if($logo_data){
		$logo_id = $logo_data->attachment_id;
		}

		$sidebars = get_option('sidebars_widgets');

		//persist background settings
		$color = get_theme_mod('background_color');
		$img = get_theme_mod('background_image');
		$thumb = get_theme_mod('background_image_thumb');

		//remove background settings for old themes
		set_theme_mod('background_image', '');
		set_theme_mod('background_image_thumb', '');

		switch_theme($_REQUEST['target_theme']);

		//remove background settings for old themes
		set_theme_mod('background_color', $color);
		set_theme_mod('background_image', $img);
		set_theme_mod('background_image_thumb', $thumb);

		update_option('sidebars_widgets', $sidebars);

		if($logo_data){
		//Update the logo settings
		$image_data = wp_get_attachment_image_src($logo_id, "medium");

		update_option('th_site_logo', $image_data[0]);


	    	$this->set_theme_logo($image_data[1], $image_data[2], $image_data[0]);
	  	$this->set_theme_logo2($image_data[0], $image_data[1], $image_data[2], $logo_id);
		}
		$this->set_footer();

		return array();
	}


	private function set_footer()
	{
	$file_name = get_stylesheet_directory().'/functions.php';
  	$file_handler = fopen($file_name, "r");
	$tmp_file = fopen($file_name.'.tmp', "w");
	$replaced = false;

	while(!feof($file_handler)) {
	  $line = fgets($file_handler);
	  if(strpos($line, "/* Customize footer credits */") !== false){
	  	$line = trim($line);
	  	while(substr($line, -1) !== '}'){
		  $line = fgets($file_handler);
		  $line = trim($line);
		}
	  	$line = "
	  			/* Customize footer credits */\n
				add_filter( 'genesis_footer_creds_text', 'custom_footer_creds_text' );
				function custom_footer_creds_text() {
				\$address = str_replace( '</br>', '', get_option('th_company_address'));
				\$name = get_option('blogname');
				echo '<div class=\"creds\"><p>';
				echo 'Copyright &copy; ';
				echo date('Y');
				echo ' &middot; <a href=\"/\">'.\$name.'</a> &middot; '.\$address.' &middot; Powered by <a href=\"http://www.thrivehive.com\" target=\"_new\" title=\"ThriveHive\" rel=\"nofollow\">ThriveHive</a>';
				echo '</p></div>';
				}
				";
	  	$replaced = true;
	  }
	  fputs($tmp_file, $line);
	}
	fclose($file_handler); fclose($tmp_file);

	if(!$replaced){
	  	$line = "
	  			/* Customize footer credits */\n
				add_filter( 'genesis_footer_creds_text', 'custom_footer_creds_text' );
				function custom_footer_creds_text() {
				\$address = str_replace( '</br>', '', get_option('th_company_address'));
				\$name = get_option('blogname');
				echo '<div class=\"creds\"><p>';
				echo 'Copyright &copy; ';
				echo date('Y');
				echo ' &middot; <a href=\"/\">'.\$name.'</a> &middot; '.\$address.' &middot; Powered by <a href=\"http://www.thrivehive.com\" target=\"_new\" title=\"ThriveHive\" rel=\"nofollow\">ThriveHive</a>';
				echo '</p></div>';
				}
				";
		file_put_contents($file_name, $line, FILE_APPEND);
	}
	else{
	  //copy($tmp_file, $file_name);
	  rename($file_name.'.tmp', $file_name);
	}
	}

	public function get_theme(){
		global $json_api;

		$theme_dir = get_stylesheet_directory();

		$theme = basename($theme_dir);

		return array('theme' => $theme);
	}
	/**
	*Sets the background image for the current theme with the config values
	*@example URL - /menus/set_background_image
	*@api
	*@return array containing success or failure
	**/
	public function set_background_image(){
		global $json_api;
		if(!isset($_REQUEST['target_image'])){
			$json_api->error("You must specify the `target_image`");
		}
		if(!isset($_REQUEST['repeat'])){
			$json_api->error("You must specify the `repeat`	value");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce`");
		}*/
		$nonce_id = $json_api->get_nonce_id('menus', 'set_background_image');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect.");
		}
		update_post_meta( $_REQUEST['target_image'], '_wp_attachment_is_custom_background', get_option('stylesheet' ) );
		$image_data = wp_get_attachment_image_src($_REQUEST['target_image'], "full");
		$thumb = wp_get_attachment_image_src($_REQUEST['target_image'],"thumbnail");
		set_theme_mod('background_image', esc_url_raw($image_data[0]));
		set_theme_mod('background_image_thumb', esc_url_raw($thumb[0]));
		set_theme_mod('background_repeat', $_REQUEST['repeat']);
		if(isset($_REQUEST['position'])){
			set_theme_mod('background_position_x', $_REQUEST['position']);
		}

		return array();
	}

	public function get_background_image()
	{
		global $json_api;

		$image = get_theme_mod('background_image');
		$thumb = get_theme_mod('background_image_thumb');
		$repeat = get_theme_mod('background_repeat');
		$posx = get_theme_mod('background_position_x');

		return array('image' => $image, 'thumb' => $thumb, 'repeat' => $repeat, 'pos_x' => $posx);
	}

	public function clear_background_image(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'clear_background_image');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}
		$content = content_url();
		set_theme_mod('background_image', $content . '/b.gif');
		set_theme_mod('background_image_thumb', $content . '/b.gif');

		return array();
	}

	public function update_background_image_settings(){
		global $json_api;
		$nonce_id = $json_api->get_nonce_id('menus', 'update_background_image_settings');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		if(isset($_REQUEST['repeat'])){
			set_theme_mod('background_repeat', $_REQUEST['repeat']);
		}

		return array();
	}


	/**
	*Sets the background color for the current theme
	*@example URL - /menus/set_background_color
	*@api
	*@return array containing success or failure
	**/
	public function set_background_color(){
		global $json_api;
		if(!isset($_REQUEST['target_color'])){
			$json_api->error("You must specify the `target_color`");
		}

		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include a `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'set_background_color');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}
		$content = content_url();
		set_theme_mod('background_color', $_REQUEST['target_color']);
		set_theme_mod('background_image', $content . '/b.gif');
		set_theme_mod('background_image_thumb', $content . '/b.gif');

		return array();
	}

	public function get_background_color(){
		global $json_api;

		$color = get_theme_mod('background_color');
		return array('color' => $color);
	}

	public function remove_background_color(){
		remove_theme_mod('background_color');
		set_theme_mod('background_image', '');
		set_theme_mod('background_image_thumb', '');

		return array();
	}


	public function set_thrivehive_environment(){
		global $json_api;

		if(!isset($_REQUEST['env'])){
			$json_api->error("You must specify the `env` to set");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'set_thrivehive_environment');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		update_option('th_environment', $_REQUEST['env']);

		return array();
	}

	public function get_thrivehive_environment(){
		$opt = get_option('th_environment');

		return array('env' => $opt);
	}

	public function set_social_accounts(){

		$twitter = $_REQUEST['twitter'];
		$facebook = $_REQUEST['facebook'];

		if(isset($twitter)){
			update_option('th_twitter', $twitter);
		}
		if(isset($facebook)){
			update_option('th_facebook', $facebook);
		}

		return array();
	}

	public function set_social_widget_settings(){
		global $json_api;
		$res = array();
		$nonce_id = $json_api->get_nonce_id('menus', 'set_social_widget_settings');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		$accountstr = str_replace('\\', '', $_REQUEST['accounts']);
		$smAccounts = json_decode($accountstr, true);

		foreach ($smAccounts as $key => $account) {
			switch ($key) {
				case 'facebook':
					$url = parse_url($account);
					$path = str_replace('/', '', $url['path']);
					$res['facebook'] = update_option('th_facebook', $path);
					break;
				case 'twitter':
					$url = parse_url($account);
					$path = str_replace('/', '', $url['path']);
					$res['twitter'] = update_option('th_twitter', $path);
					break;
				case 'linkedin':
					$res['linkedin'] = update_option('th_linkedin', $account);
					break;
				case 'yelp':
					$url = parse_url($account);
					$path = str_replace('/', '', basename($url['path']));
					$res['yelp'] = update_option('th_yelp', $path);
					break;
				case 'googleplus':
					$res['googleplus'] = update_option('th_googleplus', $account);
					break;
				case 'instagram':
					$url = parse_url($account);
					$path = str_replace('/', '', $url['path']);
					$res['instagram'] = update_option('th_instagram', $path);
					break;
				case 'youtube':
					$res['youtube'] = update_option('th_youtube', $account);
					break;
				case 'houzz':
					$res['houzz'] = update_option('th_houzz', $account);
					break;
				case 'angieslist':
					$res['angieslist'] = update_option('th_angieslist', $account);
					break;
				case 'pinterest':
					$res['pinterest'] = update_option('th_pinterest', $account);
					break;
				case 'foursquare':
					$res['foursquare'] = update_option('th_foursquare', $account);
					break;
				case 'tripadvisor':
					$res['tripadvisor'] = update_option('th_tripadvisor', $account);
					break;
				default:
					break;
			}
		}

		update_option('th_social_blogroll', $_REQUEST['blogroll']);

		update_option('th_social_blog', $_REQUEST['blog']);

		update_option('th_social_sidebar', $_REQUEST['sidebar']);

		return array($res);
	}

	public function get_social_widget_settings(){
		$settings = array('blogroll' => false, 'blog' => false, 'sidebar' => false);
		$accounts = array(
			'facebook' => '',
			'twitter' => '',
			'yelp' => '',
			'linkedin' => '',
			'googleplus' => '',
			'instagram' => '',
			'youtube' => '',
			'houzz' => '',
			'angieslist' => '',
			'pinterest' => '',
			'foursquare' => ''
			);
		//Accounts
		$twitter = get_option('th_twitter');
		$facebook = get_option('th_facebook');
		$linkedin = get_option('th_linkedin');
		$yelp = get_option('th_yelp');
		$googleplus = get_option('th_googleplus');
		$instagram = get_option('th_instagram');
		$youtube = get_option('th_youtube');
		$houzz = get_option('th_houzz');
		$angieslist = get_option('th_angieslist');
		$pinterest = get_option('th_pinterest');
		$foursquare = get_option('th_foursquare');

		//Settings
		$blogroll = get_option('th_social_blogroll');
		$blog = get_option('th_social_blog');
		$sidebar = get_option('th_social_sidebar');

		if($twitter){
			$accounts['twitter'] = $twitter;
		}
		if($facebook){
			$accounts['facebook'] = $facebook;
		}
		if($linkedin){
			$accounts['linkedin'] = $linkedin;
		}
		if($yelp){
			$accounts['yelp'] = $yelp;
		}
		if($googleplus){
			$accounts['googleplus'] = $googleplus;
		}
		if($instagram){
			$accounts['instagram'] = $instagram;
		}
		if($youtube){
			$accounts['youtube'] = $youtube;
		}
		if($houzz){
			$accounts['houzz'] = $houzz;
		}
		if($angieslist){
			$accounts['angieslist'] = $angieslist;
		}
		if($pinterest){
			$accounts['pinterest'] = $pinterest;
		}
		if($foursquare){
			$accounts['foursquare'] = $foursquare;
		}

		if($blogroll){
			$settings['blogroll'] = $blogroll;
		}
		if($blog){
			$settings['blog'] = $blog;
		}
		if($blog){
			$settings['sidebar'] = $sidebar;
		}
		return array('accounts' => $accounts, 'settings' => $settings);
	}

	public function set_custom_javascript(){
		global $json_api;
		$nonce_id = $json_api->get_nonce_id('menus', 'set_custom_javascript');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		$js = stripslashes($_REQUEST['custom_js']);
		update_option('th_javascript', $js);

		return array();
	}

	public function set_custom_css(){
		global $json_api;
		$nonce_id = $json_api->get_nonce_id('menus', 'set_custom_css');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		$css = stripslashes(urldecode($_REQUEST['custom_css']));
		update_option('th_css', $css);

		return array();
	}

	public function get_custom_javascript(){
		$js = get_option('th_javascript');
		return array('option'=>$js);
	}

	public function get_custom_css(){
		$css = get_option('th_css');
		return array('option'=>$css);
	}

	public function set_thrivehive_form(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_thrivehive_form');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		$th_id = $_REQUEST['th_id'];
		$html = stripslashes($_REQUEST['html']);
		$type = $_REQUEST['type'];

		$form = get_form_from_id($th_id);

		if(isset($form['th_id'])){
			update_thrivehive_form($th_id, $html);
		}
		else{
			add_thrivehive_form($th_id, $html, $type);
	  	}

		return array();
	}

	public function set_theme_option(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_theme_option');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}
		$optionstr = str_replace('\\', '', $_REQUEST['option']);
		$new_option = json_decode($optionstr, true);

		$theme = basename(get_stylesheet_directory());
		$theme_options = get_theme_options_by_name($theme);
		if(isset($theme_options["theme"]))
		{
			$options = unserialize($theme_options['options']);
			$option_found = false;
			for ($i=0; $i < count($options); $i++) {
				if($options[$i]['Option'] == $new_option['Option']){
					foreach ($new_option as $key => $value) {
						$options[$i][$key] = $value;
					}
					$option_found = true;
					break;
				}
			}
			if(!$option_found){
				array_push($options, $new_option);
			}

			update_theme_options($theme, serialize($options));
		}

		else{
			$options = array($new_option);
			$res = add_theme_options($theme, serialize($options));
		}

		return array('options' => $options, 'theme' => $theme, 'res' => $res);
	}

	public function set_all_theme_options(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_theme_option');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		$optionsstr = str_replace('\\', '', $_REQUEST['options']);
		$new_options = json_decode($optionsstr, true);
		$theme = basename(get_stylesheet_directory());
		$theme_options = get_theme_options_by_name($theme);
		if(isset($theme_options["theme"]))
		{
			update_theme_options($theme, serialize($new_options), $_REQUEST['version']);
		}
		else{
			$res = add_theme_options($theme, serialize($options), $_REQUEST['version']);
		}
		return array('options' => $options);
	}

	public function get_theme_options(){
		$theme = basename(get_stylesheet_directory());
		$theme_options = get_theme_options_by_name($theme);
		$options = unserialize($theme_options['options']);

		if($options == false){
			$options = array();
		}

		return array('options' => $options, 'theme' => $theme, 'version' => $theme_options['version']);
	}

	public function set_default_theme_options(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_default_theme_options');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		$theme = basename(get_stylesheet_directory());
		$theme_options = get_theme_options_by_name($theme);

		if(isset($theme_options["theme"])){
			//DO NOTHING
		}
		else{
			//NO Option found
			$optionsstr = str_replace('\\', '', $_REQUEST['options']);
			$options = json_decode($optionsstr, true);
			$defaults = array();
			foreach ($options as $opt) {
				array_push($defaults, $opt);
			}
			$res = add_theme_options($theme, serialize($defaults), $_REQUEST['version']);
		}
		return array("options" => $options);
	}

	public function get_parallax_pages(){
		$settings = get_option("th_parallax_settings");
		$settings_json = json_encode($settings["blocks"]);
		return array("blocks" => $settings_json, "maxBlocks" => $settings["max_blocks"]);
	}

	public function set_parallax_pages(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_parallax_pages');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		if(!isset($_REQUEST["blocks"])){
			$json_api->error("You must specify the `blocks` variable to set");
		}

		$settings = get_option("th_parallax_settings");

		$blocks = json_decode(stripslashes($_REQUEST["blocks"]), true);
		$index = 0;
		foreach ($blocks as $block) {
			$current_setting = $settings["blocks"][$index];

			if($block["custom"] || $block["empty"]){
				if($current_setting["ID"]){
					delete_post_meta($current_setting["ID"], "th_parallax_page");
				}
			}

			elseif ($block["ID"]) {
				if($current_setting["ID"] && $current_setting["ID"] != $block["ID"]){
					delete_post_meta($current_setting["ID"], "th_parallax_page");
					update_post_meta($block["ID"], "th_parallax_page", true);
				}
				else{
					update_post_meta($block["ID"], "th_parallax_page", true);
				}
			}
			$index += 1;
		}

		$settings["blocks"] = $blocks;
		update_option("th_parallax_settings", $settings);

		return array($blocks);
	}

	public function get_page_auto_add_setting(){
		$option = get_option('th_page_nav_autoadd');

		if($option){
			return array('option' => (bool)$option);
		}
		else{
			return array('option' => false);
		}
	}

	public function set_page_auto_add_setting(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_page_auto_add_setting');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		if(!isset($_REQUEST["value"])){
			$json_api->error("You must provide a `value` to set for the option");
		}

		$value = strtolower($_REQUEST["value"]) === "true";

		update_option('th_page_nav_autoadd', $value);

		return array();
	}
	public function set_default_landing_form_id(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'set_default_landing_form_id');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must provide an `id` to set for the option");
		}

		update_option("th_default_landingform_id", $_REQUEST['id']);
	}
	public function get_default_landing_form_id(){
		global $json_api;

		$nonce_id = $json_api->get_nonce_id('menus', 'get_default_landing_form_id');
		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 	`nonce` value was incorrect");
		}
		$id = get_option("th_default_landingform_id");

		return array('id' => $id);
	}

}
