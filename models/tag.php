<?php
/**
*Data object for a wordpress tag
*@package Models\Tag
**/
class JSON_API_Tag {
  /**
  *@var int $id ID of the tag
  **/
  var $id;          
  /**
  *@var string $slug keyword slug for the tag
  **/
  var $slug;        
  /**
  *@var string $title Title name of the slug
  **/
  var $title;       
  /**
  *@var string $description Description of the tag
  **/
  var $description; 
  
  /**Basic constructor for a wordpress tag**/
  function JSON_API_Tag($wp_tag = null) {
    if ($wp_tag) {
      $this->import_wp_object($wp_tag);
    }
  }
  
  function import_wp_object($wp_tag) {
    $this->id = (int) $wp_tag->term_id;
    $this->slug = $wp_tag->slug;
    $this->title = $wp_tag->name;
    $this->description = $wp_tag->description;
    $this->post_count = (int) $wp_tag->count;
  }
  
}

?>
