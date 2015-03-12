<?php
/**
*Controller name: Posts
*Controller description: Data manipulation methods for posts
*/


/**
*Class for interactions related to posts
*@package Controllers\Posts
*/
class JSON_API_Posts_Controller {

  /**
  *@api
  **/
  public function create_post() {
    global $json_api;

   /* if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'create_post');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if (!current_user_can('edit_posts')) {
      $json_api->error("You need to login with a user that has 'edit_posts' capacity.",'**auth**');
    }

    $categories = strtolower(str_replace('\\', '', $_REQUEST['categories']));
    $categories = json_decode($categories);
    $this->create_categories($categories);
    $categories = implode(",", $categories);
    $_REQUEST['categories'] = $categories;

    nocache_headers();
    $post = new JSON_API_Post();

    $id = $post->create($_REQUEST);
    if (empty($id)) {
      $json_api->error("Could not create post.");
    }
    if(isset($_REQUEST['extra_type']))
    {
      update_post_meta($id, 'th_extra_type', $_REQUEST['extra_type']);
    }
    if(isset($_REQUEST['th_data']))
    {
      update_post_meta($id, 'th_data', $_REQUEST['th_data']);
    }
    return array(
      'post' => $post
    );
  }

  /**
  *@api
  **/
  public function update_post() {
    global $json_api;
    $post = $json_api->introspector->get_current_post();
    if (empty($post)) {
      $json_api->error("Post not found.");
    }
    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to update posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if (!current_user_can('edit_post', $post->ID)) {
      $json_api->error("You need to login with a user that has the 'edit_post' capacity for that post.", '**auth**');
    }

    $categories = strtolower(str_replace('\\', '', $_REQUEST['categories']));
    $categories = json_decode($categories);
    $this->create_categories($categories);
    $categories = implode(",", $categories);
    $_REQUEST['categories'] = $categories;

    nocache_headers();
    $post = new JSON_API_Post($post);
    $post->update($_REQUEST);
    if(isset($_REQUEST['extra_type']))
    {
      update_post_meta($id, 'th_extra_type', $_REQUEST['extra_type']);
    }
    if(isset($_REQUEST['th_data']))
    {
      update_post_meta($_REQUEST['post_id'], 'th_data', $_REQUEST['th_data']);
    }
    return array(
      'post' => $post
    );
  }

  public function update_post_author(){
    global $json_api;

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post_author');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if (!current_user_can('edit_post', $_REQUEST['id'])) {
      $json_api->error("You need to login with a user that has the 'edit_post' capacity for that post.", '**auth**');
    }
    $user = get_user_by('slug', $_REQUEST['author']);
    $author_id = $user->ID;
    $updated = wp_update_post(array('ID' => $_REQUEST['id'], 'post_author' => $author_id));

    return array('updated' => $updated);
  }

  /**
  *@api
  **/
  public function delete_post() {
    global $json_api;
    //$post = $json_api->introspector->get_current_post();
    $post = get_post($_REQUEST['post_id']);
    if (empty($post)) {
      $json_api->error("Post not found.");
    }
    if (!current_user_can('edit_post', $post->ID)) {
      $json_api->error("You need to login with a user that has the 'edit_post' capacity for that post.", '**auth**');
    }
    if (!current_user_can('delete_posts')) {
      $json_api->error("You need to login with a user that has the 'delete_posts' capacity.", "**auth**");
    }
    if (!current_user_can('delete_others_posts')) {
      $json_api->error("You need to login with a user that has the 'delete_others_posts' capacity.", "**auth**");
    }
    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to update posts. Use the `get_nonce` Core API method.");
    }*/
    $nonce_id = $json_api->get_nonce_id('posts', 'delete_post');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }
    nocache_headers();
    wp_delete_post($post->ID);
    return array();
  }

  /**
  *Uploads an image into the wordpress media gallery
  *@api
  *@example URL - /api/posts/upload_image
  *@return array containing the image `id`, `url`, and `thumbnail`
  **/
  public function upload_image() {
    global $json_api;

    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'upload_image');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('upload_files')){
      $json_api->error("You must log into an account with 'upload_files' capacity", '**auth**');
    }

    nocache_headers();

    if(!empty($_FILES['attachment'])){

      include_once ABSPATH . '/wp-admin/includes/file.php';
      include_once ABSPATH . '/wp-admin/includes/media.php';
      include_once ABSPATH . '/wp-admin/includes/image.php';

      //Not associated with any post
      //Attachment can be made if we already have a post ID i.e. editing an existing post
      $id = media_handle_upload('attachment', 0);
      if(is_a($id, 'WP_Error')){
        $json_api->error_code($id->errors['upload_error'][0], "error", "413 ERROR");
      }

       //We're uploading a PDF, we need to do some magic to get a thumbnail for it
      if($_FILES['attachment']['type'] == "application/pdf"){
        //Const for PDF dir
        $PDF_UPLOAD_DIR = "TH_PDFS";
        $upload_dir = wp_upload_dir();
        $target_upload_dir = $upload_dir['basedir']."/$PDF_UPLOAD_DIR";
        //If our directory doesn't exist, create it.
        if (!is_dir($target_upload_dir)) {
            mkdir($target_upload_dir);
        }
        //Create our file name based off the base name of the upload
        $file_name = basename($_FILES["attachment"]["name"], ".pdf").".jpg";
        //URL for the pdf we uploaded
        $pdf_url_path = './'.parse_url( wp_get_attachment_url( $id) );
        $pdf_url =$pdf_url_path["path"];
        //The image we will be creating for the thumbnail
        $output_image = $target_upload_dir."/$file_name";
        $output = null;
        //Create a thumbnail from the first page of our PDF
        exec("convert -thumbnail x180 -define pdf:use-trimbox=true \"{$pdf_url}[0]\" \"{$output_image}\" 2>&1", $output);
        unset($_FILES['attachment']);
        $output_image_url = $upload_dir['baseurl']."/$PDF_UPLOAD_DIR/$file_name";
        update_post_meta($id, "th_pdf_thumbnail", $output_image_url);
        return array('id' => $id, 'thumbnail' => $output_image_url, 'url' => wp_get_attachment_url($id));
      }

      unset($_FILES['attachment']);

      //Toss in both thumbnail and full size image URLs from the first index of the array
      $image_src = wp_get_attachment_image_src($id, "full");
      $image_url = $image_src[0];
      $thumbnail_src = wp_get_attachment_image_src($id, "thumbnail");
      $thumbnail_url = $thumbnail_src[0];

      return array(
        'id' => $id,
        'url' => $image_url,
        'thumbnail' => $thumbnail_url
        );
    }

    //Do we need an error if no file is attached?
    $json_api->error("You must specify an attachment");
  }

  /**
  *Updates the all text for a specified image
  *@api
  *@example URL - /api/posts/update_image_alt
  *@return array containing the modified value
  **/
  public function update_image_alt(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must include the 'post_id' of the image attachment to update");
    }

    if(!isset($_REQUEST['alt_text']))
    {
      $json_api->error("You must include the 'alt_text' to include in the image post");
    }

    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }
    */

    $nonce_id = $json_api->get_nonce_id('posts', 'update_image_alt');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('upload_files'))
    {
      $json_api->error("You must log into an account with 'upload_files' capacity", '**auth**');
    }
    $post_details = array('ID' => $_REQUEST['post_id'], 'post_excerpt' => $_REQUEST['caption']);
    wp_update_post($post_details);
    $res = update_post_meta($_REQUEST['post_id'], '_wp_attachment_image_alt', $_REQUEST['alt_text']);

     return array('modified' => $res);
  }

  /**
  *Updates the meta title for a given wordpress post requires the all-in-one SEO plugin'
  *@api
  *@example URL - /api/posts/update_post_meta_title
  *@link http://wordpress.org/plugins/all-in-one-seo-pack/
  *@return array containing the modified value
  **/
  public function update_post_meta_title(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must include the 'post_id' of the image attachment to update");
    }

    if(!isset($_REQUEST['title']))
    {
      $json_api->error("You must include the 'title' to include in the image post");
    }

    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post_meta_title');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('edit_posts'))
    {
      $json_api->error("You must log into an account with 'edit_posts' capacity", '**auth**');
    }

    $res =  update_post_meta($_REQUEST['post_id'], '_aioseop_title', $_REQUEST['title']);

    if($res != true || $res != false)
    {
      $json_api->error("You must install the All In One SEO plugin to use this", '**SEO**');
    }

    return array('modified' => $res);

  }

  /**
  *Gets the post meta title and description for a given post
  *@api
  *@example URL - /api/posts/get_post_meta
  *@return array containing the title and desc for the post
  **/
  public function get_post_meta(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must specify the 'post_id' to poll");
    }

    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'get_post_meta');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $title = get_post_meta($_REQUEST['post_id'], '_aioseop_title', true);
    $desc = get_post_meta($_REQUEST['post_id'], '_aioseop_description', true);

    return array('title' => $title, 'desc' => $desc);
  }

  /**
  *Looks for the postpreview meta value to see if we have a preview available for a given post
  *@api
  *@example URL - /api/posts/get_preview_meta
  *@return array containing the `postpreview` value
  **/
  public function get_preview_meta(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must specify the 'post_id' to poll");
    }

    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'get_preview_meta');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $postpreview = get_post_meta($_REQUEST['post_id'], "th-published-preview", true);

    if($postpreview == ""){
      $postpreview = 0;
    }

    return array('postpreview' => $postpreview);
  }

  /**
  *Updates the meta description for a given post. Requires the all-in-one SEO plugin
  *@api
  *@example URL - /api/posts/update_post_meta_desc
  *@link http://wordpress.org/plugins/all-in-one-seo-pack/
  *@return array containing the modified value
  **/
  public function update_post_meta_desc(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must include the 'post_id' of the image attachment to update");
    }

    if(!isset($_REQUEST['desc']))
    {
      $json_api->error("You must include the 'title' to include in the image post");
    }

    /*if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post_meta_desc');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('edit_posts'))
    {
      $json_api->error("You must log into an account with 'edit_posts' capacity", '**auth**');
    }

    $res =  update_post_meta($_REQUEST['post_id'], '_aioseop_description', $_REQUEST['desc']);

    if($res != true || $res != false)
    {
      $json_api->error("You must install the All In One SEO plugin to use this", '**SEO**');
    }

    return array('modified' => $res);

  }

  /**
  *Gets the link to the preview version of a post
  *@api
  *@example URL - /api/posts/get_post_preview
  *@return array containing the `link` to the post preview
  **/
  public function get_post_preview(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must include the 'post_id' of the post to access");
    }

   /* if(!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to access the preview. Use the `get_nonce` core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'get_post_preview');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $res =  $this->get_preview_link($_REQUEST['post_id']);
    $res = add_query_arg(array('preview' => true), get_permalink($_REQUEST['post_id']));*/
    $res = site_url('/') . "?p=" . $_REQUEST['post_id'] . "&preview=true";

    return array('link' => $res);
  }

  /**
  *Gets the preview link to the specified post
  *@api
  *@param int $post_id the id of the desired post
  *@return string the permalink for the post
  **/
  private function get_preview_link( $post_id ) {
    return add_query_arg(
      array(
        'preview' => true,
        '_ppp'    => $this->create_nonce( 'public_post_preview_' . $post_id ),
      ),
      get_permalink( $post_id )
    );
  }

  /**
  *Creates a nonce for fetching the post preview
  *@api
  *@param string $action the action calling ('public_post_preview_')
  *@return string the nonce value for the action -- lasts 48 hours
  **/
  function create_nonce( $action ) {
    $nonce_life = apply_filters( 'ppp_nonce_life', 60 * 60 * 48 ); // 48 hours
    $i = ceil( time() / ( $nonce_life / 2 ) );

    return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
  }

  /**
  *Updates the published preview meta value for the given post
  *@api
  *@example URL - /api/posts/update_published_preview_meta
  *@return array containing the modified value
  **/
  public function update_published_preview_meta(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must include the 'post_id' of the post to access");
    }

    if(!isset($_REQUEST['target_id']))
    {
      $json_api->error("You must include the 'target_id' of the post to save as draft");
    }

    /*if(!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to access the preview. Use the `get_nonce` core API method.");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'update_published_preview_meta');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('edit_posts'))
    {
      $json_api->error("You must log into an account with 'edit_posts' capacity", '**auth**');
    }

    $res =  update_post_meta($_REQUEST['post_id'], 'th-published-preview', $_REQUEST['target_id']);

    return array('modified' => $res);
  }

  /**
  *Gets a list of all plugins active on the site
  *@api
  *@example URL - /api/posts/active_plugins
  *@return array containing the list of plugins
  **/
  public function active_plugins(){
    global $json_api;
    $plugins = wp_get_active_and_valid_plugins();
    return array('plugins' => $plugins);
  }

  public function get_page_template_files(){
    global $json_api;
  include_once ABSPATH . 'wp-admin/includes/theme.php';
  require_once(ABSPATH . 'wp-content/plugins/thrivehive/class-page-template-example.php' );

  $page = Page_Template_Plugin::get_instance();
  $page->register_project_templates();

  $templates = get_page_templates();

  $newarray = array();
  while($filename = current($templates))
  {
    array_push($newarray, array('name' => key($templates), 'file' => $filename));
    next($templates);
  }

    return array('templates' => $newarray);
  }

   public function get_page_template_file(){
    global $json_api;
    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must specify the `post_id` of the page to retrieve");
    }

    $template = get_post_meta($_REQUEST['post_id'], '_wp_page_template', true);

    return array('template' => $template);
  }

  public function set_page_template(){
    global $json_api;
    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must specify the `post_id` of the page to update");
    }
    if(!isset($_REQUEST['template']))
    {
      $json_api->error("You must specify the `template` to set");
    }
    /*if(!isset($_REQUEST['nonce']))
    {
      $json_api->error("You must specify the `nonce` value. Use get_nonce");
    }*/

    $nonce_id = $json_api->get_nonce_id('posts', 'set_page_template');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    update_post_meta($_REQUEST['post_id'], '_wp_page_template', $_REQUEST['template']);

    return array();
  }

  private function create_categories($categories){
    global $wp_rewrite;
    foreach ($categories as $cat) {
      $cat_exists = get_term_by('name', $cat, 'category');
      if(!$cat_exists)
      {
        wp_insert_term($cat, 'category',
          array(
          'description'=>$cat,
          'slug'=>sanitize_title($cat),
          'parent'=>''
          ));
      }
    }
    $wp_rewrite->flush_rules();
  }

  /**
  *Sets the SEO homepage title setting for the All In One SEO pack
  *Requires that plugin to be installed
  *@api
  *
  **/
  public function set_homepage_seo_title(){
    global $json_api;

    $nonce_id = $json_api->get_nonce_id('posts', 'set_homepage_seo_title');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $seo_options = get_option('aioseop_options');

    $seo_options['aiosp_home_title'] = $_REQUEST['title'];

    update_option('aioseop_options', $seo_options);

    return array();
  }

  public function set_homepage_seo_description(){
    global $json_api;

    $nonce_id = $json_api->get_nonce_id('posts', 'set_homepage_seo_description');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $seo_options = get_option('aioseop_options');

    $seo_options['aiosp_home_description'] = $_REQUEST['description'];

    update_option('aioseop_options', $seo_options);

    return array();
  }

  public function get_homepage_seo_title(){
    $seo_options = get_option('aioseop_options');
    $option = $seo_options['aiosp_home_title'];

    return array("option" => $option);
  }

  public function get_homepage_seo_description(){
    $seo_options = get_option('aioseop_options');
    $option = $seo_options['aiosp_home_description'];

    return array("option" => $option);
  }

  public function update_post_comments(){
    global $json_api;
    $ids = strtolower(str_replace('\\', '', $_REQUEST['ids']));
    $ids = json_decode($ids);
    $status = $_REQUEST['status'];

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post_comments');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    foreach ($ids as $id) {
      wp_set_comment_status($id, $status);
    }
    return array();
  }

  public function delete_post_comments(){
    global $json_api;

    $ids = strtolower(str_replace('\\', '', $_REQUEST['ids']));
    $ids = json_decode($ids);

    $nonce_id = $json_api->get_nonce_id('posts', 'delete_post_comments');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    foreach ($ids as $id) {
      wp_delete_comment($id);
    }
    return array();
  }
  public function reply_to_comment(){
    global $json_api;
    $id = $_REQUEST['id'];
    $content = $_REQUEST['content'];
    $post_id = $_REQUEST['post_id'];
    $user_id = $_REQUEST['user_id'];
    $nonce_id = $json_api->get_nonce_id('posts', 'reply_to_comment');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $user = get_user_by('id', $user_id);
    $args = array(
        'comment_post_ID' => $post_id,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_author_url' => get_site_url(),
        'comment_content' => $content,
        'comment_type' => '',
        'comment_parent' => $id,
        'user_id' => $user_id,
        'comment_author_IP' => '127.0.0.1'
      );
    $comment_id = wp_new_comment($args);

    //auto approve the comment
    wp_set_comment_status($comment_id, "approve");
    return array($comment_id);
  }

  public function upload_file(){
    global $json_api;
    if(!current_user_can('upload_files')){
      $json_api->error("You must log into an account with 'upload_files' capacity", '**auth**');
    }

    nocache_headers();

    if(!empty($_FILES['attachment'])){
      $bits = $_FILES['attachment'];
      $name = $_FILES["attachment"]["name"];

      $upload = wp_upload_bits($name, null, file_get_contents($bits));

      return $upload;
    }
  }

  public function get_page_genesis_layout(){
    global $json_api;
    $nonce_id = $json_api->get_nonce_id('posts', 'get_page_genesis_layout');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!isset($_REQUEST["post_id"])){
      $json_api->error("You must specify the `post_id` of the page to access");
    }

    $layout = get_post_meta($_REQUEST["post_id"], "_genesis_layout", true);

    if(!$layout){
      return array();
    }

    return array("option" => $layout);
  }

  public function set_page_genesis_layout(){
    global $json_api;
    $nonce_id = $json_api->get_nonce_id('posts', 'set_page_genesis_layout');

    $nonce = wp_create_nonce($nonce_id);

    if (!wp_verify_nonce($nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!isset($_REQUEST["post_id"])){
      $json_api->error("You must specify the `post_id` of the page to access");
    }
    if(!isset($_REQUEST["layout"])){
      $json_api->error("You must specify the `layout` of the page to set");
    }

    update_post_meta($_REQUEST["post_id"], "_genesis_layout", $_REQUEST["layout"]);

    return array();
  }
}
?>
