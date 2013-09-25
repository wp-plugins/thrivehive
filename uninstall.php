<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

// For Single site
if ( !is_multisite() ) 
{
    $args = array(
    'posts_per_page'   => -1,
    'offset'           => 0,
    'category'         => '',
    'orderby'          => 'post_date',
    'order'            => 'DESC',
    'include'          => '',
    'exclude'          => '',
    'meta_key'         => '',
    'meta_value'       => '',
    'post_type'        => 'th_draft',
    'post_mime_type'   => '',
    'post_parent'      => '',
    'post_status'      => 'any',
    'suppress_filters' => true );

    $posts = get_posts($args);

    if (is_array($posts)) {
        foreach ($posts as $post ) {
            $id = $post->ID;

            wp_delete_post($id, true);
            echo "Deleted Post: ".$post->title."\r\n";
        }
    }

   /* delete_option('th_tracking_code');
    delete_option('th_phone_number');
    delete_option('th_form_html');
    delete_option('th_landingform_id');
    delete_option('th_site_logo');
    delete_option('public_post_preview');*/
} 
?>