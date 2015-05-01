<?php

//Register Assessment as Custom Post Type
add_action( 'init' , 'register_assessment_type' );
function register_assessment_type(){
    
    //Labels
    $labels = array(
                    'name' => _x( 'Assessment' , 'post type general name' , PLUGIN_DOMAIN ) ,
                    'name' => _x( 'Assessment' , 'post type singular name'  , PLUGIN_DOMAIN ) ,
                    'menu_name' => _x( 'Assessment' , 'admin menu' , PLUGIN_DOMAIN ) ,
                    'name_admin_bar' => _x( 'Assessment' , 'add new on admin bar' , PLUGIN_DOMAIN ) ,
                    'add_new' => _x( 'Add New' , 'assessment' , PLUGIN_DOMAIN ) ,
                    'add_new_item' => __( 'Add New Assessment' , PLUGIN_DOMAIN ) ,
                    'new_item' => __( 'New Assessment' , PLUGIN_DOMAIN ) ,
                    'edit_item' => __( 'Edit Assessment' , PLUGIN_DOMAIN ) ,
                    'view_item' => __( 'View Assessment' , PLUGIN_DOMAIN ) ,
                    'all_items' => __( 'All Assessments' , PLUGIN_DOMAIN ) ,
                    'search_items' => __( 'Search Assessments' , PLUGIN_DOMAIN ) ,
                    'not_found' => __( 'No assessments found' , PLUGIN_DOMAIN )
                    );
    
    //Arguments
    $args = array(
                    'labels' => $labels ,
                    'description' => 'Create Assessment' ,
                    'public' => true ,
                    'show_ui' => true ,
                    'menu_icon' => 'dashicons-list-view' ,
                    'has_archive' => true ,
                    'menu_position' => 25 ,
                    'taxonomies' => array('post_tag') ,
                    'supports' => array( 'title', 'editor' , 'thumbnail' , 'author' , 'revisions', 'custom-fields' ) ,
                    'register_meta_box_cb' => 'assessment_metaboxes'
                  );
    register_post_type( 'assessment' , $args );
}

?>