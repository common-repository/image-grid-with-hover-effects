<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
/*
 Plugin Name: Image grid with hover effects
 Description: Create attractive image grids with hover with title, description and link on hover. Place it anywhere with easy to use shortcode.
 Version: 1.0
 Author: Webgensis
 Author URI: http://www.webgensis.com
 Plugin URI: https://wordpress.org/plugins/image-grid-with-hover-effects
 Text Domain: image-grid-with-hover-effects
 */
/*  Copyright 2017-2018 webgensis  (email : info@webgensis.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/* require CMB2 */
require_once  __DIR__ . '/inc/cmb2/init.php';

/* Version of the plugin */
define('WG_IGHE_VERSION', '1.0' );

/* our plugin name to be used at multiple places */
define( 'WG_IGHE_PLUGIN_NAME', "Image grid with hover effects" );

/* We'll key on the slug, set it here so it can be used in various places */
define( 'WG_IGHE_PLUGIN_SLUG', plugin_basename( __FILE__ ) );

/* We'll define post type here so it can be used in various places */
define( 'WG_IGHE_POST_TYPE', 'imagegrid' );
define( 'WG_IGHE_POST_TYPE_NAME', 'Image Grid' );
define( 'WG_IGHE_POST_TYPE_META_PREFIX', '_wg_ighe_' );

/* plugin admin scripts */
function wg_ighe_admin_style() {
        wp_register_style( 'wg_ighe_admin_css', plugin_dir_url( __FILE__ ) . 'inc/admin/css/wg-ighe-admin.css',1.0,true );
        wp_enqueue_style( 'wg_ighe_admin_css' );
        wp_register_script( 'wg_ighe_admin_js', plugin_dir_url( __FILE__ ) . 'inc/admin/js/wg-ighe-admin.js',1.0,true );
        wp_enqueue_script( 'wg_ighe_admin_js' );
}
add_action( 'admin_enqueue_scripts', 'wg_ighe_admin_style' );
/* registering scripts and style */
function wg_ighe_scripts() {
    if ( ! wp_script_is( 'jquery', 'enqueued' )) {
        wp_enqueue_script( 'jquery' );
    }
    wp_enqueue_style( 'wg_ighe_style', plugin_dir_url( __FILE__ ) . 'inc/css/wg-ighe.css',1.0,true );
    wp_enqueue_script( 'wg_ighe_jquery_hoverdir', plugin_dir_url( __FILE__ ) . 'inc/js/jquery.hoverdir.js',1.0, true );
    wp_enqueue_script( 'wg_ighe_modernizr_custom', plugin_dir_url( __FILE__ ) . 'inc/js/modernizr.custom.js',1.0, true );
}
add_action( 'wp_footer', 'wg_ighe_scripts'); 

/* flush rewrite rules on activation */
register_activation_hook( WG_IGHE_PLUGIN_SLUG, 'wg_ighe_activation' );
function wg_ighe_activation() {
  if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
  }
    flush_rewrite_rules();
}

/* Delete flush rewrite rules once activated properly*/
add_action( 'admin_init','wg_ighe_initialize' );
function wg_ighe_initialize() {
    if( is_admin() && get_option( 'wg_ighe_activation' ) == 'just-activated' ) {
      delete_option( 'wg_ighe_activation' );
        flush_rewrite_rules();
    }
}

/* deactivate plugin */
function wg_ighe_deactivate() {
  flush_rewrite_rules();
}
register_deactivation_hook( WG_IGHE_PLUGIN_SLUG, 'wg_ighe_deactivate' );

/* uninstall plugin */
function  wg_ighe_uninstall() {
  if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
  }
    $args = array (
      'post_type' => WG_IGHE_POST_TYPE,
      'nopaging' => true
    );
    $query = new WP_Query ($args);
    while ($query->have_posts ()) {
      $query->the_post ();
      $id = get_the_ID ();
      wp_delete_post ($id, true);
    }
    wp_reset_postdata ();
    flush_rewrite_rules();
}
register_uninstall_hook( __FILE__, 'wg_ighe_uninstall' );

/* Add post type */
add_action( 'init', 'wg_ighe_post_type' );
function wg_ighe_post_type() {
  $labels = array(
    'name'               => WG_IGHE_POST_TYPE,
    'singular_name'      => WG_IGHE_POST_TYPE_NAME,
    'menu_name'          => WG_IGHE_POST_TYPE_NAME,
    'name_admin_bar'     => WG_IGHE_POST_TYPE_NAME,
    'add_new'            => WG_IGHE_POST_TYPE_NAME,
    'add_new_item'       => 'Add New ' . WG_IGHE_POST_TYPE_NAME,
    'new_item'           => 'New ' . WG_IGHE_POST_TYPE_NAME,
    'edit_item'          => 'Edit ' . WG_IGHE_POST_TYPE_NAME,
    'view_item'          => 'View ' .WG_IGHE_POST_TYPE_NAME,
    'all_items'          => 'All ' . WG_IGHE_POST_TYPE_NAME,
    'search_items'       => 'Search ' . WG_IGHE_POST_TYPE_NAME,
    'parent_item_colon'  => 'Parent ' . WG_IGHE_POST_TYPE_NAME . ':',
    'not_found'          => 'No ' . WG_IGHE_POST_TYPE_NAME . ' found.',
    'not_found_in_trash' => 'No ' . WG_IGHE_POST_TYPE_NAME . ' found in Trash.',
  );
  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'menu_icon'          => 'dashicons-grid-view',
    'has_archive'        => false,
    'hierarchical'       => false,
    'menu_position'      => null,
    'supports'           => array( 'title' ),
  );
    register_post_type(WG_IGHE_POST_TYPE, $args);
}

/* Add column to post type */
add_filter('manage_edit-imagegrid_columns', 'wg_ighe_columns_head');
add_action('manage_imagegrid_posts_custom_column', 'wg_ighe_columns_content', 10, 2);
function wg_ighe_columns_head($defaults) {
    $defaults['shortcode'] = 'Shortcode';
    return $defaults;
}
function wg_ighe_columns_content($column_name, $post_ID) {
    if ($column_name == 'shortcode') {
      $key=WG_IGHE_POST_TYPE_META_PREFIX.'shortcode';
      echo get_post_meta($post_ID, $key, true);
    }
}

/* our post-type fields */
add_action( 'cmb2_admin_init', 'wg_ighe_metabox');
function  wg_ighe_metabox() {
  /* Start with an underscore to hide fields from custom fields list */
  $prefix = WG_IGHE_POST_TYPE_META_PREFIX;
  // instantiate metabox
    $cmb = new_cmb2_box( array(
        'id'            => $prefix.'metabox',
        'title'         => 'Images and Hover info',
        'object_types'  => array( WG_IGHE_POST_TYPE )
    ) );
    $cmb->add_field( array(
    'name'  =>  __('Shortcode', WG_IGHE_POST_TYPE),
    'id'   => $prefix.'shortcode',
    'type' => 'text',
    'attributes'  => array(
      'readonly' => 'readonly',
    ),
  ) );
  $cmb->add_field( array(
      'name'  =>  __('Grid Hover Delay', WG_IGHE_POST_TYPE),
    'desc'  => 'Hover Delay in miliseconds, Leave blank for no delay (optional)',
    'id'  => $prefix.'delay',
      'type'    => 'text',
      'attributes' => array(
            'type' => 'number',
        ),
    ) );
  $cmb->add_field( array(
    'name'  => __('Grid Hover Inverse', WG_IGHE_POST_TYPE),
    'desc'  => '(optional)',
    'id'    => $prefix.'inverse',
    'type'  => 'checkbox',
  ) );
  // $group_field_id is the field id string, so in this case: $prefix . 'demo'
    $group_id = $cmb->add_field( array(
        'id'                => $prefix.'grid_item',
        'type'              => 'group',
        'description'       => 'Grid item should have have hover info filled',
        'options'           => array(
            'group_title'   => 'Grid-items {#}',
            'add_button'    => 'Add Another Grid item',
            'remove_button' => 'Remove Grid item',
            'sortable'      => true
        )
    ) );
    $cmb->add_group_field( $group_id, array(
        'id'            => $prefix.'image',
        'name'          => __('Item Image', WG_IGHE_POST_TYPE),
        'type'          => 'file',
        'desc'          => 'Grid item image.',
        'attributes'    => array(
      'placeholder' => 'Item Image',
      'required'    => 'required',
    ),
    ) );
    $cmb->add_group_field( $group_id, array(
        'id'            => $prefix.'title',
        'name'          => __('Hover Title', $prefix.WG_IGHE_POST_TYPE),
        'type'          => 'text_medium',
        'attributes'  => array(
      'placeholder' => 'Item Heading',
      'required'    => 'required',
    ),
    ) );
   $cmb->add_group_field( $group_id,  array(
        'name'  => __('External Link', WG_IGHE_POST_TYPE),
        'desc'  => 'Check if Link provided below is external (optional)',
        'id'    => $prefix.'external_link',
        'type'  => 'checkbox',
    ) );
    $cmb->add_group_field( $group_id, array(
        'id'            => $prefix.'title_link',
        'name'          => __('Hover Title URL', $prefix.WG_IGHE_POST_TYPE),
        'type'          => 'text_url',
        'attributes'  => array(
      'placeholder' => 'Item Heading',
            'required'    => 'required',
    ),
    ) );
    $cmb->add_group_field( $group_id, array(
        'id'            => $prefix.'item_description',
        'name'          => __('Hover Description', $prefix.WG_IGHE_POST_TYPE),
        'type'          => 'text',
        'attributes'  => array(
      'placeholder' => 'Item Description',
    ),
    ) );
}

/* shortcode to meta */
add_filter( 'save_post', 'wg_ighe_update_shortcode_meta',10,3);
function wg_ighe_update_shortcode_meta( $post_id, $post ) { 
  if( WG_IGHE_POST_TYPE == $post->post_type ) {
    $prefix = WG_IGHE_POST_TYPE_META_PREFIX;
    $value="[ighe id=\"".$post_id."\"]"; 
    update_post_meta($post_id,$prefix.'shortcode', $value);
  }
}

/*Frontend view Shortcode*/
function wg_ighe_grid_output($atts){
  extract( shortcode_atts( array(
    'id' => 0,
  ), $atts));
  if ($id==0) {
    $output='Please add Image grid ID';
  }else{
    $meta_prefix = WG_IGHE_POST_TYPE_META_PREFIX;
    $grid_items = get_post_meta($id, $meta_prefix.'grid_item',true);
    $grid_item_hover_delay = get_post_meta($id, $meta_prefix.'delay',true);
    $grid_item_hover_inverse = get_post_meta($id, $meta_prefix.'inverse',true);
    $output='<ul id="da-'.$id.'" class="da-thumbs">';
    foreach ($grid_items as $grid_item) {
      if ($grid_item[$meta_prefix.'external_link']=="on"){
        $target="target='_blank'";
      }else{
        $target="";
      }
      $grid_item[$meta_prefix.'image_id'];
      $grid_medium_image=$grid_item[$meta_prefix.'image'];
      $output.='<li>';
        $output.='<a href="'.$grid_item[$meta_prefix.'title_link'].'" title="'.$grid_item[$meta_prefix.'title'].'" '.$target.'>';
          $output.='<img src="'.$grid_medium_image.'" />';
          $output.='<div><span>'.$grid_item[$meta_prefix.'title'].'</span>';
          if (trim($grid_item[$meta_prefix.'item_description'])!="") {
            $output.='<p>'.$grid_item[$meta_prefix.'item_description'].'</p>';
          }
          $output.='</div>';
        $output.='</a>';
      $output.='</li>';
    }
    $output.='</ul>';
    $output.='<script type="text/javascript" defer>';
    $output.='jQuery(document).ready(function($){';
    $output.='$(" #da-'.$id.' > li ").each( function() { jQuery(this).hoverdir({';
    if (trim($grid_item_hover_delay)!="") {
      $output.='hoverDelay : '.$grid_item_hover_delay.',';
    }
    if ($grid_item_hover_inverse=="on") {
      $output.='inverse : true,';
    }
    $output.='}); } );';
    $output.='});';
    $output.='</script>';
  }
  return $output;
  unset($output);
}
add_shortcode('ighe', 'wg_ighe_grid_output');
?>