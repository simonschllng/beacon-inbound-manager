<?php
/*
Plugin Name: Beacon Inbound Manager
Plugin URI: https://github.com/simonschllng/beacon-inbound-manager
Description: Manage your BLE Beacons to generate inbound traffic to your landing pages.
Version: 1.00
Author: Simon Schilling
Author URI: http://simon.schllng.de
*/

/*  Copyright 2017  Simon Schilling  (email: wp@schllng.de)

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


/** Definitions **/
define("BIMPOSTTYPE",      "beacon") ;
define("BIMBASESLUG",      "b") ;


/** Inits: **/
add_action( 'init', 'bim_register_post_type' );
add_action( 'add_meta_boxes_' . BIMPOSTTYPE, 'bim_add_meta_boxes' );
add_action( 'save_post_' . BIMPOSTTYPE, 'bim_save_metabox', 10, 2 );
add_action( 'template_redirect', 'bim_redirect_beacon_inbound' );
add_filter( 'manage_' . BIMPOSTTYPE . '_posts_columns','bim_custom_columns' );
add_action( 'manage_' . BIMPOSTTYPE . '_posts_custom_column','bim_custom_columns_content', 10, 2 );
add_action( 'plugins_loaded', 'bim_load_textdomain' );



/**
 * Load plugin textdomain.
 */
function bim_load_textdomain() {
  load_plugin_textdomain( 'bim', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Registers custom post type "Beacon"
 *
 * @global none
 * @return nothing.
 *
 * */
function bim_register_post_type() {
  $labels = array(
    'name'               => _x( 'Beacons', 'post type general name', 'bim' ),
    'singular_name'      => _x( 'Beacon', 'post type singular name', 'bim' ),
    'add_new'            => __( 'New beacon', 'bim' ),
    'add_new_item'       => __( 'Register new beacon', 'bim' ),
    'edit_item'          => __( 'Change beacon', 'bim' ),
    'new_item'           => __( 'New beacon', 'bim' ),
    'all_items'          => __( 'All beacons', 'bim' ),
    'view_item'          => __( 'View beacons', 'bim' ),
    'search_items'       => __( 'Search beacon', 'bim' ),
    'not_found'          => __( 'No beacon found', 'bim' ),
    'not_found_in_trash' => __( 'No beacons found in trash', 'bim' ),
    'parent_item_colon'  => '',
    'menu_name'          => __( 'Beacons', 'bim' ),
  );
  $supports = array(
    'title',
  //  'editor', //(content)
    'author',
  //  'thumbnail', //(featured image, current theme must also support post-thumbnails)
  //  'excerpt',
  //  'trackbacks',
  //  'custom-fields',
  //  'comments', //(also will see comment count balloon on edit screen)
  //  'revisions', //(will store revisions)
  //  'page-attributes', //(menu order, hierarchical must be true to show Parent option)
  //  'post-formats', //add post formats, see Post Formats
  );
  $rewrite = array(
    'slug'        => BIMBASESLUG, //Customize the permalink structure slug. Defaults to the $post_type value. Should be translatable.
    'with_front'  => true, //Should the permalink structure be prepended with the front base. (example: if your permalink structure is /blog/, then your links will be: false->/news/, true->/blog/news/). Defaults to true
    'feeds'       => false, //Should a feed permalink structure be built for this post type. Defaults to has_archive value.
    'pages'       => false, //Should the permalink structure provide for pagination. Defaults to true
  //  'ep_mask'     => const, //As of 3.4 Assign an endpoint mask for this post type. For more info see Rewrite
  );
  $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj4NCiAgPGcgZmlsbD0icmVkIj4NCiAgICA8cGF0aCBkPSJNNDMuNTYgOS45NGw0LjY2LTMuOS0xLjEyIDMuODh6TTk3LjI0IDMyLjFsMi4yNi0uODQtNy43LTI0LjkzLTQuMjgtMi4wMyAyLjIgMy41N3pNNjUuMTIuNjRsNCA1LjUzIDUuMTUgMS45OCA4Ljg2LTUuM0w3NS40LjJ6TTM1LjYgNzYuNDhsMy4wNi43LS45IDQuNTh6bTM0LjM2IDExLjY1bC0uMSA0LjcgMy43Ny00Ljd6bTguNTctMS40NWw4LjUtMjMuOTYgOS41IDIwLjU2TDY4LjYgOTl6TTUwLjk2IDkuODVsMi4yMi01Ljc1IDcuNS0yLjkgNC45NSA1Ljgyek04OSA1OGw4LjMzLTIyIDIuMTUtLjU2TDk4IDc2Ljkyek03Ny4wMyAzOC45bC0xLjYtMjYuMjggNS42Ny00LjU1IDUuMjggMS4xNiA4LjA0IDI0LjIzTDg2LjkgNTQuNXptLTM0LjggMjQuNjhMMjkuNSA0OC45OCA0Mi41MiAxMi40bDMuODMtLjI4em0tOC4zNSA4LjM2bDQuNCAxLjQyIDIuNS02LjY3LTExLjU2LTEyLjl6bTcuNiAzLjg2bC4yMiA5LjQyIDI0LjA3IDE0LjUuNTctMTEuNzZMNDQgNzAuNDN6bTMuNDUtOS41M2wzMC4yLTIzLjQgOS40MyAxNi4xLTguODMgMjQuMy03LjY3LjU4em0xLjczLTYuMjVsMy4zLTQ2LjIyIDE3LjItMy41OCA0LjEzIDEuMjUgMS4zIDI3Ljl6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz4NCiAgICA8cGF0aCBzdHlsZT0iaXNvbGF0aW9uOmF1dG87bWl4LWJsZW5kLW1vZGU6bm9ybWFsIiBkPSJNMTcuMDcgNDUuNjhsNi41Ny0zQTE2LjczIDE2LjczIDAgMCAxIDMxLjkgMjAuNWwtMy02LjU3YTIzLjk2IDIzLjk2IDAgMCAwLTExLjgzIDMxLjc1ek0zLjkzIDUxLjdsNi41Ny0zQTMxLjIgMzEuMiAwIDAgMSAyNS44OCA3LjM0bC0zLTYuNTdBMzguNCAzOC40IDAgMCAwIDMuOTMgNTEuN3oiIGNvbG9yPSIjMDAwIiBvdmVyZmxvdz0idmlzaWJsZSIgc29saWQtY29sb3I9IiMwMDAwMDAiLz4NCiAgPC9nPg0KPC9zdmc+';
  $args = array(
    'label'				   	     => '',
    'labels'					     => $labels,
    'description'				   => __( 'Verwaltet deine BLE Beacons', 'bim' ),
    'public'					     => true,
    'exclude_from_search'	 => true,
    'publicly_queryable'	 => true,
    'show_ui'					     => true,
    'show_in_nav_menus'		 => false,
    'show_in_menu'				 => true,
    'show_in_admin_bar'		 => false,
    'menu_position'				 => 45,
    'menu_icon'					   => $icon,
  //  'capability_type'			 => '',
  //  'capabilities'				 => '',
  //  'map_meta_cap'				 => '',
    'hierarchical'				 => false,
    'supports'					   => $supports,
  //  'register_meta_box_cb' => '',
  //  'taxonomies'					 => '',
  //  'has_archive'					 => '',
    'rewrite'					     => $rewrite,
    'query_var'					   => false,
    'can_export'					 => false,
    'delete_with_user'		 => false,
    'show_in_rest'				 => false,

  );
  register_post_type( BIMPOSTTYPE, $args );
}




/**
 * Add a meta box to the editor
 *
 * @global none
 * @return nothing.
 *
 * */

function bim_add_meta_boxes(){
  add_meta_box(
    BIMPOSTTYPE . '-location',
    __( 'Beacon Location', 'bim' ),
    'bim_location_form_render',
    BIMPOSTTYPE,
    'normal',
    'high'
  );
  add_meta_box(
    BIMPOSTTYPE . '-behavior',
    __( 'Beacon Behavior', 'bim' ),
    'bim_behavior_form_render',
    BIMPOSTTYPE,
    'normal',
    'high'
  );
  add_meta_box(
    BIMPOSTTYPE . '-settings',
    __( 'Beacon Settings', 'bim' ),
    'bim_settings_form_render',
    BIMPOSTTYPE,
    'normal',
    'high'
  );
  add_meta_box(
    BIMPOSTTYPE . '-hardware',
    __( 'Beacon Hardware', 'bim' ),
    'bim_hardware_form_render',
    BIMPOSTTYPE,
    'normal',
    'high'
  );
};



/**
 * Renders the location form meta box
 *
 * @global none
 * @return nothing
 *
 * */

function bim_location_form_render(){
  bim_input_field_text('location-city', __('City name', 'bim') );
  bim_input_field_text('location-coordinates', __('Coordinates', 'bim') );
  bim_input_field_text('location-description', __('Description', 'bim') );
};



/**
 * Renders the behavior form meta box
 *
 * @global $post
 * @return nothing
 *
 * */

function bim_behavior_form_render(){
  global $post;
  ?>
  <table>
    <tr>
      <td>
        <?php bim_input_field_text('url-beacon', __('Beacon Short URL', 'bim') ); ?>
      </td>
      <td>
        <span class="dashicons dashicons-arrow-right-alt2"></span>
      </td>
      <td>
        <?php bim_input_field_text('url-destination', __('Destination Page', 'bim') ); ?>
      </td>
    </tr>
    <tr>
      <td>
        <?php _e('Input the url from the shortening service that redirects to the beacons URL:', 'bim'); ?> <input type="text" disabled value="<?php echo get_post_permalink($post->ID); ?>">.
      </td>
      <td>
        &nbsp;
      </td>
      <td>
        <?php _e('Input the landingpage you want the user to land on.', 'bim'); ?>
      </td>
    </tr>
  </table>
  <?php
};



/**
 * Renders the settings form meta box
 *
 * @global none
 * @return nothing
 *
 * */

function bim_settings_form_render(){
  bim_input_field_text('settings-password', __('Password', 'bim') );
  bim_input_field_text('settings-start', __('Daily Broadcast Start Time', 'bim') );
  bim_input_field_text('settings-stop', __('Daily Broadcast Stop Time', 'bim') );
  bim_input_field_text('settings-interval', __('Broadcast Interval', 'bim') );
  bim_input_field_text('settings-power', __('Tx Power', 'bim') );
};



/**
 * Renders the hardware form meta box
 *
 * @global none
 * @return nothing
 *
 * */

function bim_hardware_form_render(){
  bim_input_field_text('hw-model', __('Beacon Model', 'bim') );
  bim_input_field_text('hw-mac', __('MAC Address', 'bim') );
};




/**
 * Renders a single input field with label
 *
 * @global $post
 * @return nothing
 *
 * */

function bim_input_field_text($field_id, $display_name, $write_protected=false ){
  global $post;
  $prefix = 'bim-meta';

  $post_meta = get_post_meta($post->ID);

  $name = $prefix . "-" . $field_id;
  $disabled = $write_protected ? "disabled" : "";
  ?>
  <p class="bim-label-wrapper">
    <label for="<?php echo $name; ?>"><?php echo __($display_name, 'bim'); ?>:</label>
  </p>
  <input type="text" id="<?php echo $name; ?>" name="<?php echo $name; ?>"
         value="<?php echo $post_meta[$name][0]; ?>" <?php echo $disabled; ?> />
  <?php
};



/**
 * Saves all meta data for the custom post type
 *
 * @global none
 * @return nothing
 *
 * */

function bim_save_metabox( $post_id, $post ){
  $prefix = 'bim-meta';
  foreach ($_POST as $key=>$val) {
      if( strpos($key, $prefix) === 0 ) {
          update_post_meta($post_id, $key, $val);
      }
  }
};



/**
 * Adds additional columns to the beacon list
 *
 * @global none
 * @return $collums[]
 *
 * */

function bim_custom_columns( $columns ) {
    $columns['city_name'] = __('City name', 'bim');
    $columns['description'] = __('Description', 'bim');
    return $columns;
}



/**
 * Populates the additional columns in the beacon list
 *
 * @global none
 * @return nothing
 *
 * */

function bim_custom_columns_content ( $column_id, $post_id ) {
  $prefix = 'bim-meta';
  switch( $column_id ) {
    case 'city_name':
        echo ($value = get_post_meta($post_id, $prefix.'-location-city', true ) ) ? $value : '-';
    break;
    case 'description':
        echo ($value = get_post_meta($post_id, $prefix.'-location-description', true ) ) ? $value : '-';
    break;
  }
}



/**
 * Redirects the visitor to the specified landingpage for this beacon
 *
 * @global $post
 * @return nothing
 *
 * */

function bim_redirect_beacon_inbound(){
  global $post;
  $prefix = 'bim-meta';

  if(get_post_type($post)!==BIMPOSTTYPE){
    return;
  }

  $post_meta = get_post_meta($post->ID);
  $url = $post_meta[$prefix . '-url-destination'][0];

  if($url){
    bim_redirect( $url );
  } else {
    bim_redirect( home_url( '/' ) );
  }
};



/**
 * Perform redirection
 *
 * */

function bim_redirect( $to ){
  wp_redirect( $to, 301 );
  exit;
};
