<?php
/**
*Data object for a wordpress post category
*@package Models\Category
**/
class JSON_API_Category {
  /**
  *@var int $id ID of the category
  **/
  var $id;          
  /**
  *@var string $slug slug keyword for the category
  **/
  var $slug;        
  /**
  *@var string $title title name for the category
  **/
  var $title;       
  /**
  *@var string $description description for the category
  **/
  var $description; 
  /**
  *@var int $parent parent category ID
  **/
  var $parent;      
  /**
  *@var int $post_count Number of posts under this category??
  **/
  var $post_count;  
  
  /**basic constructor for a Category**/
  function JSON_API_Category($wp_category = null) {
    if ($wp_category) {
      $this->import_wp_object($wp_category);
    }
  }
  
  function import_wp_object($wp_category) {
    $this->id = (int) $wp_category->term_id;
    $this->slug = $wp_category->slug;
    $this->title = $wp_category->name;
    $this->description = $wp_category->description;
    $this->parent = (int) $wp_category->parent;
    $this->post_count = (int) $wp_category->count;
  }
  
}

?>
