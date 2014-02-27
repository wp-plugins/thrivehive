<?php
/**
*Data oject for an author for wordpress posts
*@package Models\Author
**/
class JSON_API_Author {
  /**
  *@var int $id ID of the author 
  **/
  var $id;          
  /**
  *@var string $slug author keyword slug
  **/
  var $slug;        
  /**
  *@var string $name actual name of the author
  **/
  var $name;        
  /**
  *@var string $first_name first name of the author
  **/
  var $first_name;  
  /**
  *@var string $last_name last name of the author
  **/
  var $last_name;   
  /**
  *@var string nickname nickname of the author
  **/
  var $nickname;   
  /**
  *@var string $url url for the author in wordpress
  **/
  var $url;         
  /**
  *@var string $description description of the author
  **/
  var $description; 
  
  // Note:
  //   JSON_API_Author objects can include additional values by using the
  //   author_meta query var.
  /**Basic constructor for an author**/
  function JSON_API_Author($id = null) {
    if ($id) {
      $this->id = (int) $id;
    } else {
      $this->id = (int) get_the_author_meta('ID');
    }
    $this->set_value('slug', 'user_nicename');
    $this->set_value('name', 'display_name');
    $this->set_value('first_name', 'first_name');
    $this->set_value('last_name', 'last_name');
    $this->set_value('nickname', 'nickname');
    $this->set_value('url', 'user_url');
    $this->set_value('description', 'description');
    $this->set_author_meta();
    //$this->raw = get_userdata($this->id);
  }
  
  function set_value($key, $wp_key = false) {
    if (!$wp_key) {
      $wp_key = $key;
    }
    $this->$key = get_the_author_meta($wp_key, $this->id);
  }
  
  function set_author_meta() {
    global $json_api;
    if (!$json_api->query->author_meta) {
      return;
    }
    $protected_vars = array(
      'user_login',
      'user_pass',
      'user_email',
      'user_activation_key'
    );
    $vars = explode(',', $json_api->query->author_meta);
    $vars = array_diff($vars, $protected_vars);
    foreach ($vars as $var) {
      $this->set_value($var);
    }
  }
  
}

?>
