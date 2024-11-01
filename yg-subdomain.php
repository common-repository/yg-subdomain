<?php
/*
Plugin Name: yg-subdomain
Plugin URI: http://dragon-dev.dyn.dhs.org/
Description: Use selecttive selected post as subdomain
Version: 0.1
Author: YUN GAUN
Author URI: http://dragon-dev.dyn.dhs.org/

* LICENSE
    Copyright 2013 YUN GAUN  (email : yyccii412@gmail.com)

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

class subSubdomain{
    var $slug;
    var $field;
    function  __construct() {
        $this->field ='p';
    }

    function getSubdomain(){
        global $wpdb;
        $url = getenv( 'HTTP_HOST' );

        $domain = explode( ".", $url );
        $postid = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key LIKE '_fake_subdomain_value_key' AND meta_value LIKE '%s'", $domain[0]));
        if($postid == null)
            return;
        $this->slug = $postid;
        return $this->slug;
    }

    function getRewriteRules(){
        $rules = array();
        $rules["feed/(feed|rdf|rss|rss2|atom)/?$"] = "index.php?" . $this->field . "=" . $this->slug . "&feed=\$matches[1]";
        $rules["(feed|rdf|rss|rss2|atom)/?$"] = "index.php?" . $this->field . "=" . $this->slug . "&feed=\$matches[1]";
        $rules["page/?([0-9]{1,})/?$"] = "index.php?" . $this->field . "=" . $this->slug . "&paged=\$matches[1]";
        $rules["$"] = "index.php?" . $this->field . "=" . $this->slug;
        $rules['[^/]+/attachment/([^/]+)/?$']='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]';
        $rules['[^/]+/attachment/([^/]+)/trackback/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&tb=1';
        $rules['[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&feed=$matches[2]';
        $rules['[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&feed=$matches[2]';
        $rules['[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&cpage=$matches[2]';
        $rules['([^/]+)/trackback/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&name=$matches[1]&tb=1';
        $rules['([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?' . $this->field . "=" . $this->slug . '&name=$matches[1]&feed=$matches[2]';
        $rules['([^/]+)/(feed|rdf|rss|rss2|atom)/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&name=$matches[1]&feed=$matches[2]';
        $rules['([^/]+)/page/?([0-9]{1,})/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&name=$matches[1]&paged=$matches[2]';
        $rules['([^/]+)/comment-page-([0-9]{1,})/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&name=$matches[1]&cpage=$matches[2]';
        $rules['([^/]+)(/[0-9]+)?/?$']='index.php?' . $this->field . "=" . $this->slug . '&name=$matches[1]&page=$matches[2]';
        $rules['[^/]+/([^/]+)/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]';
        $rules['[^/]+/([^/]+)/trackback/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&tb=1';
        $rules['[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'] ='index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&feed=$matches[2]';
        $rules['[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&feed=$matches[2]';
        $rules['[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$']= 'index.php?' . $this->field . "=" . $this->slug . '&attachment=$matches[1]&cpage=$matches[2]';
        return $rules;
    }
}

class initPlugin extends subSubdomain{
    function __construct(){
        parent::__construct();
    }
    function addActions() {
        add_action( 'init', 'wps_init', 2 );
        add_action( 'add_meta_boxes', 'fake_subdomain_add_custom_box' );
        add_action( 'save_post', 'fake_subdomain_save_postdata' );
    }
    function addFilters(){
        add_filter( 'post_rewrite_rules', 'sub_post_rewrite_rules' );
        add_filter( 'post_link', 'sub_post_link', 10, 2 );
        add_filter( 'page_rewrite_rules', 'sub_post_rewrite_rules' );
        add_filter( 'page_link', 'sub_post_link', 10, 2 );
    }
}

$obj_sub = new initPlugin;
$obj_sub->addActions();
$obj_sub->addFilters();

// action

/* Adds a box to the main column on the Post and Page edit screens */
function fake_subdomain_add_custom_box() {
    $screens = array( 'post', 'page' );
    foreach ($screens as $screen) {
        add_meta_box(
            'fake_subdomain_sectionid',
            __( 'Fake SubDomain', 'fake_subdomain_textdomain' ),
            'fake_subdomain_inner_custom_box',
            $screen
        );
    }
}

/* Prints the box content */
function fake_subdomain_inner_custom_box( $post ) {

    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'fake_subdomain_noncename' );

    // The actual fields for data entry
    // Use get_post_meta to retrieve an existing value from the database and use the value for the form
    $value = get_post_meta( get_the_ID(), $key = '_fake_subdomain_value_key', $single = true );
    $value = str_replace("-", " ", $value);
    $value_check = "";
    if($value != null || $value != "")
    {
        $value_check = "checked";
    }

    echo '<input type="checkbox" id="fake_subdomain_new_check" name ="fake_subdomain_new_check" value="fake_subdomain" ' . $value_check . ' />';
    echo '<label for="fake_subdomain_new_field">';
    _e("Fake subDomain: ", 'fake_subdomain_textdomain' );
    echo '</label> ';
    echo '<input type="text" id="fake_subdomain_new_field" name="fake_subdomain_new_field" value="'.esc_attr($value).'" size="25" />';
}

/* When the post is saved, saves our custom data */
function fake_subdomain_save_postdata( $post_id ) {
    global $wpdb;
    // First we need to check if the current user is authorised to do this action.
    if ( 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) )
            return;
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) )
            return;
    }
    $mydata = null;
    // Secondly we need to check if the user intended to change this value.
    if ( ! isset( $_POST['fake_subdomain_noncename'] ) || ! wp_verify_nonce( $_POST['fake_subdomain_noncename'], plugin_basename( __FILE__ ) ) )
    {
        return;
    }

    $mydata = sanitize_text_field( $_POST['fake_subdomain_new_field'] );
    $check_value = $_POST['fake_subdomain_new_check'];
    if($check_value == null)
    {
        $mydata = "";
    }
    //if saving in a custom table, get post_ID
    $post_ID = $_POST['post_ID'];
    //sanitize user input

    // Do something with $mydata
    // either using
    $mydata = trim($mydata);
    $mydata = str_replace(" ", "-", $mydata);

    if($mydata != null)
    {
        $meta_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE '_fake_subdomain_value_key' AND meta_value LIKE '%s'", $mydata));
        if($meta_count > 0)
            return;
    }

    add_post_meta($post_ID, '_fake_subdomain_value_key', $mydata, true) or
    update_post_meta($post_ID, '_fake_subdomain_value_key', $mydata);
    // or a custom table (see Further Reading section below)
}

// filter

function wps_init () {
    if (!is_admin()) {
        // Stuff changed in WP 2.8
        if (function_exists('set_transient')) {
            set_transient('rewrite_rules', "");
            update_option('rewrite_rules', "");
        } else {
            update_option('rewrite_rules', "");
        }
    }
}

add_filter( 'root_rewrite_rules', 'wps_root_rewrite_rules' );

function wps_root_rewrite_rules( $rules ) {
    return $rules;
}

function sub_post_rewrite_rules($rules){
    global $obj_sub;
    if($domain = $obj_sub->getSubdomain()){
        $rules = $obj_sub->getRewriteRules();
    }
    return $rules;
}

function sub_post_link( $link, $id ){
    $value = get_post_meta( get_the_ID(), $key = '_fake_subdomain_value_key', $single = true );
    if($value != null)
    {
        $link = str_replace('www.','',$link);

        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(.*)\/([a-z0-9\-\_]+)(\.html)/', $value . '.$1', $link);
        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/([a-z0-9\-\_]+)(\.html)/', $value .'.$1', $link);

        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(.*)\/(\?p=[0-9]+)/', $value . '.$1', $link);
        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(\?p=[0-9]+)/', $value .'.$1', $link);

        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(.*)\/([0-9]+)\/([0-9]+)\/([0-9]+)\/([a-z0-9\-\_]+)/', $value . '.$1', $link);
        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/([0-9]+)\/([0-9]+)\/([0-9]+)\/([a-z0-9\-\_]+)/', $value .'.$1', $link);

        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(.*)\/(archives)\/([0-9]+)/', $value . '.$1', $link);
        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(archives)\/([0-9]+)/', $value .'.$1', $link);

        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/(.*)\/([a-z0-9\-\_]+)/', $value . '.$1', $link);
        $link = preg_replace('/(?<=http\:\/\/)([a-z0-9_\-\.]+)\/([a-z0-9\-\_]+)/', $value .'.$1', $link);
    }

    return $link;
}
?>