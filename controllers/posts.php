<?php
/*
Controller name: Posts
Controller description: Data manipulation methods for posts
*/

class JSON_API_Posts_Controller {

  public function create_post() {
    global $json_api;

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }


    $nonce_id = $json_api->get_nonce_id('posts', 'create_post');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if (!current_user_can('edit_posts')) {
      $json_api->error("You need to login with a user that has 'edit_posts' capacity.",'**auth**');
    }
    nocache_headers();
    $post = new JSON_API_Post();

    $id = $post->create($_REQUEST);
    if (empty($id)) {
      $json_api->error("Could not create post.");
    }
    return array(
      'post' => $post
    );
  }
  
  public function update_post() {
    global $json_api;
    $post = $json_api->introspector->get_current_post();
    if (empty($post)) {
      $json_api->error("Post not found.");
    }
    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to update posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post');
    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if (!current_user_can('edit_post', $post->ID)) {
      $json_api->error("You need to login with a user that has the 'edit_post' capacity for that post.", '**auth**');
    }
    nocache_headers();
    $post = new JSON_API_Post($post);
    $post->update($_REQUEST);
    return array(
      'post' => $post
    );
  }
  
  public function delete_post() {
    global $json_api;
    $post = $json_api->introspector->get_current_post();
    if (empty($post)) {
      $json_api->error("Post not found.");
    }
    if (!current_user_can('edit_post', $post->ID)) {
      $json_api->error("You need to login with a user that has the 'edit_post' capacity for that post.", '**auth**');
    }
    if (!current_user_can('delete_posts')) {
      $json_api->error("You need to login with a user that has the 'delete_posts' capacity.", "**auth**");
    }
    if ($post->post_author != get_current_user_id() && !current_user_can('delete_other_posts')) {
      $json_api->error("You need to login with a user that has the 'delete_other_posts' capacity.", "**auth**");
    }
    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to update posts. Use the `get_nonce` Core API method.");
    }
    $nonce_id = $json_api->get_nonce_id('posts', 'delete_post');
    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }
    nocache_headers();
    wp_delete_post($post->ID);
    return array();
  }

  public function upload_image() {
    global $json_api;

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'upload_image');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
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

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'update_image_alt');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('upload_files'))
    {
      $json_api->error("You must log into an account with 'upload_files' capacity", '**auth**');
    }

     $res = update_post_meta($_REQUEST['post_id'], '_wp_attachment_image_alt', $_REQUEST['alt_text']);

     return array('modified' => $res);
  }

  //Requires the all-in-one-seo plugin
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

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post_meta_title');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
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

  //Requires the all-in-one-seo plugin
  public function get_post_meta(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must specify the 'post_id' to poll");
    }

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'get_post_meta');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $title = get_post_meta($_REQUEST['post_id'], '_aioseop_title', true);
    $desc = get_post_meta($_REQUEST['post_id'], '_aioseop_description', true);

    return array('title' => $title, 'desc' => $desc);
  }

  public function get_preview_meta(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must specify the 'post_id' to poll");
    }

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'get_preview_meta');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $postpreview = get_post_meta($_REQUEST['post_id'], "th-published-preview", true);

    if($postpreview == ""){
      $postpreview = 0;
    }

    return array('postpreview' => $postpreview);
  }

  //Requires the all-in-one-seo plugin
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

    if (!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'update_post_meta_desc');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
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

  public function get_post_preview(){
    global $json_api;

    if(!isset($_REQUEST['post_id']))
    {
      $json_api->error("You must include the 'post_id' of the post to access");
    }

    if(!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to access the preview. Use the `get_nonce` core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'get_post_preview');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    $res =  $this->get_preview_link($_REQUEST['post_id']);

    return array('link' => $res);
  }

  //This is only for public post preview, not for other API calls
  private function get_preview_link( $post_id ) {
    return add_query_arg(
      array(
        'preview' => true,
        '_ppp'    => $this->create_nonce( 'public_post_preview_' . $post_id ),
      ),
      get_permalink( $post_id )
    );
  }

  //This is only for public post preview, not for other API calls
  function create_nonce( $action ) {
    $nonce_life = apply_filters( 'ppp_nonce_life', 60 * 60 * 48 ); // 48 hours
    $i = ceil( time() / ( $nonce_life / 2 ) );

    return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
  }

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

    if(!$json_api->query->nonce) {
      $json_api->error("You must include a 'nonce' value to access the preview. Use the `get_nonce` core API method.");
    }

    $nonce_id = $json_api->get_nonce_id('posts', 'update_published_preview_meta');

    if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
      $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }

    if(!current_user_can('edit_posts'))
    {
      $json_api->error("You must log into an account with 'edit_posts' capacity", '**auth**');
    }

    $res =  update_post_meta($_REQUEST['post_id'], 'th-published-preview', $_REQUEST['target_id']);

    return array('modified' => $res);
  }
  
}

?>
