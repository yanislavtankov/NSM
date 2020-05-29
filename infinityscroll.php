<?php
   /*
   Plugin Name: Infinity Scroll
   Plugin URI: https://gim.newmark.bg
   description: Task for NS Media
   Version: 1.0
   Author: Yanislav Tankov
   Author URI: http://newmark.bg
   */

//Create a database table for tracking visitors when plugin is activated
function create_track_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'track';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		ip tinytext NOT NULL,
		browser text NOT NULL,
		url varchar(55) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	add_option( 'jal_db_version', '1.0' );
}
register_activation_hook( __FILE__, 'create_track_table' );

//Function that loads on every page load and write to table track IP, Browser data and page visited
function visitor_data() {
    global $wpdb;
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    //get browser data
    $browser = $_SERVER['HTTP_USER_AGENT'];
    //get the current uri
    $uri = $_SERVER['REQUEST_URI'];
    $table_name = $wpdb->prefix . 'track';
    $now = date("Y-m-d h:m:s");
    $data = array( 'time' => $now, 'ip' => $ip, 'browser' => $browser, 'url' => $uri);
    $format = array('%s','%s','%s', '%s');
    //write data to track table
    $wpdb->insert($table_name,$data,$format);
    $my_id = $wpdb->insert_id;
}
add_action( 'wp', 'visitor_data' );

//localise all files of the infinity scroll and initialise the plugin
function infinityscroll_localisation() {
    global $wp_query;
    //register infinityscroll.js js script
    wp_register_script( 'infinityscroll', get_stylesheet_directory_uri() . '/assets/js/infinityscroll.js', array('jquery') );
    //register infinityscroll.css stylesheet
    wp_register_style( 'infinityscroll', get_stylesheet_directory_uri() . '/assets/css/infinityscroll.css', array('css') );
    //set up infinityscroll_variables that will be used in infinityscroll.js
	wp_localize_script( 'infinityscroll', 'infinityscroll_variables', array(
		'ajaxurl' => site_url() . '/wp-admin/admin-ajax.php',
		'posts' => json_encode( $wp_query->query_vars ),
		'paged' => get_query_var( 'paged' ) ? get_query_var('paged') : 1
	) );
	wp_enqueue_script( 'infinityscroll' );
}
add_action( 'wp_enqueue_scripts', 'infinityscroll_localisation' );

//the handler of all requestes from infinityscroll.js
function infinityscroll_ajax_handler(){
    global $wp_query;
    //set params for the query
	$param = json_decode( stripslashes( $_POST['query'] ), true );
	$param['post_status'] = 'publish';
	$param['paged'] = $_POST['page'] + 1;
	query_posts( $param );
	//The loop
	if( have_posts() ) {
		$wp_query->is_search = false;
		while( have_posts() ) {
		    //post details and structure
			echo '<span class="postID" id="' . $wp_query->post->ID . '"></span>';
			echo '<hr class="post-separator styled-separator is-style-wide section-inner" aria-hidden="true" />';
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );
		}
	}
	die;
}

//adding both actions for registered and unregistered users
add_action('wp_ajax_loadmore', 'infinityscroll_ajax_handler');
add_action('wp_ajax_nopriv_loadmore', 'infinityscroll_ajax_handler');
