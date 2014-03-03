<?php
/**
*Controller name: Core
*Controller description: Basic introspection methods
*/
/**
*Class for the core API actions
*@package Controllers\Core
*/
class JSON_API_Core_Controller {
  /**
  *@api
  **/
  public function info() {
    global $json_api;
    $php = '';
    if (!empty($json_api->query->controller)) {
      return $json_api->controller_info($json_api->query->controller);
    } else {
      $dir = json_api_dir();
      if (file_exists("$dir/json-api.php")) {
        $php = file_get_contents("$dir/json-api.php");
      } else {
        // Check one directory up, in case json-api.php was moved
        $dir = dirname($dir);
        if (file_exists("$dir/json-api.php")) {
          $php = file_get_contents("$dir/json-api.php");
        }
      }
      if (preg_match('/^\s*Version:\s*(.+)$/m', $php, $matches)) {
        $version = $matches[1];
      } else {
        $version = '(Unknown)';
      }
      $active_controllers = explode(',', get_option('json_api_controllers', 'core'));
      $controllers = array_intersect($json_api->get_controllers(), $active_controllers);
      return array(
        'json_api_version' => $version,
        'controllers' => array_values($controllers)
      );
    }
  }

  /**
  *@api
  **/
  public function get_recent_posts() {
    global $json_api;
    $posts = $json_api->introspector->get_posts();
    return $this->posts_result($posts);
  }
  
  /**
  *Grab all posts matching the desired parameters 
  *@example URL - /core/get_posts/
  *@api
  *@return array containing all posts 
  **/
   public function get_posts() {
    global $wpdb, $json_api;

    if(isset($_REQUEST['count']))
    {
      $count = $_REQUEST['count'];
    }

    if(isset($_REQUEST['offset']))
    {
      $offset = $_REQUEST['offset'];
    }

    if(!isset($_REQUEST['extra_type']))
    {
      $extra_type = null;
    }
    else
    {
      $extra_type = $_REQUEST['extra_type'];
    }
    if(!isset($_REQUEST['post_type']))
    {
      $post_type = "post";
    }
    else
    {
      $post_type = $_REQUEST['post_type'];
    }
    if(is_null($extra_type))
    {
      $query = "
          SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_status
          FROM $wpdb->posts p
          LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id AND m.meta_key LIKE 'th_extra_type'
          WHERE p.post_type = '$post_type' 
          AND p.post_status != 'auto-draft' 
          AND p.post_status != 'trash'
          AND m.meta_key is null
          ORDER BY p.post_date DESC
          ";
      if($count != 0){
        $query=$query."\nLIMIT $count OFFSET $offset";
      }
          $query=$query.";";
      $res = $wpdb->get_results($query, ARRAY_A);
    }
    else
    {
        $query = "
          SELECT p.ID, p.post_title, p.post_type, p.post_status
          FROM $wpdb->posts p
          JOIN $wpdb->postmeta m ON p.ID = m.post_id
          WHERE p.post_type = '$post_type' 
          AND p.post_status != 'auto-draft' 
          AND p.post_status != 'trash'
          AND m.meta_key =  'th_extra_type'
          AND m.meta_value = '$extra_type'
          ORDER BY p.post_date DESC
          ";
        if($count != 0)
        {
          $query=$query."\nLIMIT $count OFFSET $offset";
        }
        $query=$query.";";
        $res = $wpdb->get_results($query, ARRAY_A);
    }
    $newres = array();
    foreach ($res as $retpost) {
      $retpost['url'] = get_permalink($retpost['ID']);
      $retpost['status'] = $retpost['post_status'];
      $retpost['title'] = $retpost['post_title'];
      $retpost['type'] = $retpost['post_type'];
      $retpost['id'] = $retpost['ID'];
      unset($retpost['post_status']);
      unset($retpost['post_type']);
      unset($retpost['post_title']);
      unset($retpost['ID']);
      array_push($newres, $retpost);
    }
    return array('posts'=>$newres);

  }

  /**
  *Removes all preview drafts from the given posts
  *@param post[] posts - array of all posts
  **/
  private function remove_preview_drafts($posts)
  {
    $count = count($posts);
    $previewdrafts = array();
    for ($i=0; $i < $count; $i++) { 
      $id = $posts[$i]->id;
      if(in_array($id, $previewdrafts))
      {
        unset($previewdrafts[$id]);
        unset($posts[$i]);
      }

      $meta = get_post_meta($id, "th-published-preview", true);

      if($meta == "")
      {
        $meta = 0;
      }

      if($meta != 0)
      {
        array_push($previewdrafts, $meta);
      }
    }

    for ($i=0; $i < $count ; $i++) { 
      $id = $posts[$i]->id;
      if(in_array($id, $previewdrafts))
      {
        unset($previewdrafts[array_search($id, $previewdrafts)]);
        unset($posts[$i]);
      }
    }
    return array_values($posts);
  }

  /**
  *Get all images uploaded to the media gallery
  *@example URL - /core/get_gallery_images/
  *@api
  *@return array containing all `images`
  **/
  public function get_gallery_images() {
    global $json_api;

    if(!current_user_can('upload_files')){
      $json_api->error("You must log into an account with 'upload_files' capacity", '**auth**');
    }

    $args = array(
    'post_type' => 'attachment',
    'post_status' => 'published',
    'post_mime_type' => 'image',
    'posts_per_page' => -1,
    'numberposts' => null,
    );

    $query_images = new WP_Query($args);
    $images = array();

    foreach( $query_images->posts as $image) {
      $id = $image->ID;
      $imageurl = wp_get_attachment_url($id);
      $src = wp_get_attachment_image_src($id, 'thumbnail');
      $alttext = get_post_meta($id, '_wp_attachment_image_alt', true);

      $images[] = array('id' => $id, 'url' => $imageurl, 'alt_text' => $alttext, 'thumbnail' => $src[0]);
    }
    return array('images' => $images);
  }
  
  /**
  *login to the specified user account
  *@example URL - /core/login
  *@api
  *@return array containing the user logged in
  **/
  public function login(){
    global $json_api;
    $creds['user_login'] = $json_api->query->user;
    $creds['user_password'] = $json_api->query->password;
    $creds['rememeber'] = true;
    $user = wp_signon($creds, false);
    if(isset($user->errors))
    {
      $json_api->error("Incorrect username or password", '**auth**');
    }
    return $user;
  }
  
  /**
  *Grab the post with the specified ID
  *@example URL - /core/get_post/
  *@api
  *@return array containing the specified post 
  **/
  public function get_post() {
    global $json_api, $post;
    $post = $json_api->introspector->get_current_post();
    if ($post) {
      $previous = get_adjacent_post(false, '', true);
      $next = get_adjacent_post(false, '', false);
      $response = array(
        'post' => new JSON_API_Post($post)
      );
      if ($previous) {
        $response['previous_url'] = get_permalink($previous->ID);
      }
      if ($next) {
        $response['next_url'] = get_permalink($next->ID);
      }
      return $response;
    } else {
      $json_api->error("Not found.");
    }
  }

  /**
  *Grab the page with the specified ID
  *@example URL - /core/get_page/
  *@api
  *@return array containing the specified page 
  **/
  public function get_page() {
    global $json_api;
    extract($json_api->query->get(array('id', 'slug', 'page_id', 'page_slug', 'children')));
    if ($id || $page_id) {
      if (!$id) {
        $id = $page_id;
      }
      $posts = $json_api->introspector->get_posts(array(
        'page_id' => $id
      ));
    } else if ($slug || $page_slug) {
      if (!$slug) {
        $slug = $page_slug;
      }
      $posts = $json_api->introspector->get_posts(array(
        'pagename' => $slug
      ));
    } else {
      $json_api->error("Include 'id' or 'slug' var in your request.");
    }
    
    // Workaround for https://core.trac.wordpress.org/ticket/12647
    if (empty($posts)) {
      $url = $_SERVER['REQUEST_URI'];
      $parsed_url = parse_url($url);
      $path = $parsed_url['path'];
      if (preg_match('#^http://[^/]+(/.+)$#', get_bloginfo('url'), $matches)) {
        $blog_root = $matches[1];
        $path = preg_replace("#^$blog_root#", '', $path);
      }
      if (substr($path, 0, 1) == '/') {
        $path = substr($path, 1);
      }
      $posts = $json_api->introspector->get_posts(array('pagename' => $path));
    }
    
    if (count($posts) == 1) {
      if (!empty($children)) {
        $json_api->introspector->attach_child_posts($posts[0]);
      }
      return array(
        'page' => $posts[0]
      );
    } else {
      $json_api->error("Not found.");
    }
  }
  
  /**
  *@api
  **/
  public function get_date_posts() {
    global $json_api;
    if ($json_api->query->date) {
      $date = preg_replace('/\D/', '', $json_api->query->date);
      if (!preg_match('/^\d{4}(\d{2})?(\d{2})?$/', $date)) {
        $json_api->error("Specify a date var in one of 'YYYY' or 'YYYY-MM' or 'YYYY-MM-DD' formats.");
      }
      $request = array('year' => substr($date, 0, 4));
      if (strlen($date) > 4) {
        $request['monthnum'] = (int) substr($date, 4, 2);
      }
      if (strlen($date) > 6) {
        $request['day'] = (int) substr($date, 6, 2);
      }
      $posts = $json_api->introspector->get_posts($request);
    } else {
      $json_api->error("Include 'date' var in your request.");
    }
    return $this->posts_result($posts);
  }
  
  /**
  *@api
  **/
  public function get_category_posts() {
    global $json_api;
    $category = $json_api->introspector->get_current_category();
    if (!$category) {
      $json_api->error("Not found.");
    }
    $posts = $json_api->introspector->get_posts(array(
      'cat' => $category->id
    ));
    return $this->posts_object_result($posts, $category);
  }
  
  /**
  *@api
  **/
  public function get_tag_posts() {
    global $json_api;
    $tag = $json_api->introspector->get_current_tag();
    if (!$tag) {
      $json_api->error("Not found.");
    }
    $posts = $json_api->introspector->get_posts(array(
      'tag' => $tag->slug
    ));
    return $this->posts_object_result($posts, $tag);
  }
  
  /**
  *@api
  **/
  public function get_author_posts() {
    global $json_api;
    $author = $json_api->introspector->get_current_author();
    if (!$author) {
      $json_api->error("Not found.");
    }
    $posts = $json_api->introspector->get_posts(array(
      'author' => $author->id
    ));
    return $this->posts_object_result($posts, $author);
  }
  
  /**
  *@api
  **/
  public function get_search_results() {
    global $json_api;
    if ($json_api->query->search) {
      $posts = $json_api->introspector->get_posts(array(
        's' => $json_api->query->search
      ));
    } else {
      $json_api->error("Include 'search' var in your request.");
    }
    return $this->posts_result($posts);
  }
  
  /**
  *@api
  **/
  public function get_date_index() {
    global $json_api;
    $permalinks = $json_api->introspector->get_date_archive_permalinks();
    $tree = $json_api->introspector->get_date_archive_tree($permalinks);
    return array(
      'permalinks' => $permalinks,
      'tree' => $tree
    );
  }
  
  /**
  *@api
  **/
  public function get_category_index() {
    global $json_api;
    $args = null;
    if (!empty($json_api->query->parent)) {
      $args = array(
        'parent' => $json_api->query->parent
      );
    }
    $categories = $json_api->introspector->get_categories($args);
    return array(
      'count' => count($categories),
      'categories' => $categories
    );
  }
  
  /**
  *@api
  **/
  public function get_tag_index() {
    global $json_api;
    $tags = $json_api->introspector->get_tags();
    return array(
      'count' => count($tags),
      'tags' => $tags
    );
  }
  
  /**
  *@api
  **/
  public function get_author_index() {
    global $json_api;
    $authors = $json_api->introspector->get_authors();
    return array(
      'count' => count($authors),
      'authors' => array_values($authors)
    );
  }
  
  /**
  *@api
  **/
  public function get_page_index() {
    global $json_api;
    $pages = array();
    $post_type = $json_api->query->post_type ? $json_api->query->post_type : 'page';
    
    // Thanks to blinder for the fix!
    $numberposts = empty($json_api->query->count) ? -1 : $json_api->query->count;
    $wp_posts = get_posts(array(
      'post_type' => $post_type,
      'post_parent' => 0,
      'order' => 'ASC',
      'orderby' => 'menu_order',
      'numberposts' => $numberposts
    ));
    foreach ($wp_posts as $wp_post) {
      $pages[] = new JSON_API_Post($wp_post);
    }
    foreach ($pages as $page) {
      $json_api->introspector->attach_child_posts($page);
    }
    return array(
      'pages' => $pages
    );
  }
  
  /**
  *@api
  **/
  public function get_nonce() {
    global $json_api;
    extract($json_api->query->get(array('controller', 'method')));
    if ($controller && $method) {
      $controller = strtolower($controller);
      if (!in_array($controller, $json_api->get_controllers())) {
        $json_api->error("Unknown controller '$controller'.");
      }
      require_once $json_api->controller_path($controller);
      if (!method_exists($json_api->controller_class($controller), $method)) {
        $json_api->error("Unknown method '$method'.");
      }
      $nonce_id = $json_api->get_nonce_id($controller, $method);
      return array(
        'controller' => $controller,
        'method' => $method,
        'nonce' => wp_create_nonce($nonce_id)
      );
    } else {
      $json_api->error("Include 'controller' and 'method' vars in your request.");
    }
  }
  
  protected function get_object_posts($object, $id_var, $slug_var) {
    global $json_api;
    $object_id = "{$type}_id";
    $object_slug = "{$type}_slug";
    extract($json_api->query->get(array('id', 'slug', $object_id, $object_slug)));
    if ($id || $$object_id) {
      if (!$id) {
        $id = $$object_id;
      }
      $posts = $json_api->introspector->get_posts(array(
        $id_var => $id
      ));
    } else if ($slug || $$object_slug) {
      if (!$slug) {
        $slug = $$object_slug;
      }
      $posts = $json_api->introspector->get_posts(array(
        $slug_var => $slug
      ));
    } else {
      $json_api->error("No $type specified. Include 'id' or 'slug' var in your request.");
    }
    return $posts;
  }
  
  protected function posts_result($posts) {
    global $wp_query;
    return array(
      'count' => count($posts),
      'count_total' => (int) $wp_query->found_posts,
      'pages' => $wp_query->max_num_pages,
      'posts' => $posts
    );
  }
  
  protected function posts_object_result($posts, $object) {
    global $wp_query;
    // Convert something like "JSON_API_Category" into "category"
    $object_key = strtolower(substr(get_class($object), 9));
    return array(
      'count' => count($posts),
      'pages' => (int) $wp_query->max_num_pages,
      $object_key => $object,
      'posts' => $posts
    );
  }

  /**
  *Gets the values for formatting SEO options from the all-in-one SEO plugin
  *@example URL - /api/core/get_seo_format_data
  *@api
  *@return array containing `post_title_format`, `blog_title`, `blog_description`, 
  *`category_title`, `post_author_login`, `post_author_nicename`, `post_author_firstname`, `post_author_lastname`
  **/
  public function get_seo_format_data()
  {
    global $aioseop_options, $json_api;

    $id = $json_api->query->get('id');

    if(!$id)
    {
      $json_api->error("You must include the 'id' of the post you wish to get SEO info about");
    }

    if(empty($aioseop_options))
    {
      $json_api->error("You must install the All In One SEO plugin to use this", "**SEO**");
    }

    $postdata = get_post($json_api->query->get('id'), ARRAY_A);
    $authorMeta = get_the_author_meta($postdata['post_author']);
    $categories = get_the_category(278);
    $category = '';

    if(count($categories) > 0){
      $category = $categories[0]->cat_name;
    }

    return array(
      'post_title_format' => $aioseop_options['aiosp_post_title_format'],
      'blog_title' => get_bloginfo('name'),
      'blog_description' => get_bloginfo('description'),
      'category_title' => $category,
      'post_author_login' => get_the_author_meta('user_login', $postdata['post_author']),
      'post_author_nicename' => get_the_author_meta('user_nicename', $postdata['post_author']),
      'post_author_firstname' => get_the_author_meta('first_name', $postdata['post_author']),
      'post_author_lastname' => get_the_author_meta('last_name', $postdata['post_author']),
      );
  }

  public function get_object_counts()
  {
    global $json_api, $wpdb;

    $query = 
    "
      SELECT
        (SELECT count(*) FROM wp_posts WHERE post_type = 'post' AND post_status = 'publish') as post_count,
        (SELECT count(*)
          FROM wp_posts p
          LEFT JOIN wp_postmeta m ON p.ID = m.post_id AND m.meta_key LIKE 'th_extra_type'
          WHERE p.post_type = 'page' 
          AND p.post_status = 'publish' 
          AND m.meta_key is null) as page_count,
        (SELECT count(*) FROM wp_TH_buttons) as button_count;
    ";
    $res = $wpdb->get_results($query, OBJECT);
    return $res[0];
  }

  public function get_installed_themes()
  {
    $theme_root = get_theme_root();

    $directories = glob($theme_root . '/*' , GLOB_ONLYDIR);
    $dirnames = array();

    foreach($directories as $theme){
      array_push($dirnames, basename($theme));
    }

    return $dirnames;
  }

  public function get_blog_info()
  {
    $title = get_bloginfo('name');

    return array('title' => $title);
  }

  public function get_home_and_blog_pages()
  {
    $home = get_option('page_on_front');
    $blog = get_option('page_for_posts');

    return array('pages' => array('home_page' => $home, 'blog_page' => $blog));
  }
  
}

?>
