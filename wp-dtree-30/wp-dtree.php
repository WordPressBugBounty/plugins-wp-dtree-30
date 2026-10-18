<?php
	/*
	Plugin Name: WP-dTree
	Plugin URI: https://wordpress.org/plugins/wp-dtree-30/
	Description: Collapsible navigation widgets for archives, categories, pages, links, taxonomies and menus.
	Version: 4.5.2
	Requires at least: 6.0
	Requires PHP: 7.4
	Author: Ulf Benjaminsson
	Author URI: http://www.ulfbenjaminsson.com
	License: GPL2
	Text Domain: wp-dtree-30
	Domain Path: /lang
	
	WP-dTree - Creates a JS navigation tree for your blog archives	
	Copyright (C) 2007-2021 Ulf Benjaminsson (email: hello at ulfbenjaminsson dotcom)	
	Copyright (C) 2006 Christopher Hwang (email: chris@silpstream.com)	 
	
	This is a plugin created for Wordpress in order to generate JS navigation trees	for your archives. 
	It uses the (much modified) JS engine dTree that was created by Geir Landrö (http://www.destroydrop.com/javascripts/tree/).
	Christopher Hwang wrapped the wordpress APIs around it so that we can use it as a plugin. He handled all development of WP-dTree up to version 2.2 (~2007).	
	*/		
	defined('ABSPATH') || exit;
	add_action('plugins_loaded', 'wpdt_init');
	add_action( 'wpmu_new_blog', 'wpdt_new_blog', 10, 6);
	register_activation_hook(__FILE__, 'wpdt_activate');	
	register_deactivation_hook(__FILE__, 'wpdt_deactivate');				
	global $wpdt_tree_ids;
	$wpdt_tree_ids = array('arc' => 0, 'cat' => 0, 'pge' => 0, 'lnk' => 0, 'tax' => 0, 'mnu' => 0);//used to create unique instance names for the javascript trees.	
	require_once('wp-dtree-cache.php');	
	function wpdt_init() {
		if(!defined('ULFBEN_DONATE_URL')){
			define('ULFBEN_DONATE_URL', 'http://www.amazon.com/gp/registry/wishlist/2QB6SQ5XX2U0N/105-3209188-5640446?reveal=unpurchased&filter=all&sort=priority&layout=standard&x=21&y=17');
		}		
		define('WPDT_BASENAME', plugin_basename( __FILE__ ));		
		define('WPDT_SCRIPT', 'wp-dtree.min.js');	
		define('WPDT_STYLE', 'wp-dtree.min.css');			
		add_action('init', 'wpdt_load_textdomain', 0);
		add_action('init', 'wpdt_maybe_upgrade', 0);
		add_filter('plugin_row_meta', 	'wpdt_set_plugin_meta', 2, 10);			
		add_action('admin_menu', 		'wpdt_add_option_page');	
		add_action('deleted_post', 		'wpdt_update_cache'); 
		add_action('save_post', 		'wpdt_update_cache');
		// Includes custom taxonomies and recounts completed after relationship changes.
		add_action('clean_term_cache', 'wpdt_update_cache');
		// Relationship events also cover imports that defer term counting.
		add_action('set_object_terms', 'wpdt_update_cache');
		add_action('deleted_term_relationships', 'wpdt_update_cache');
		add_action('wp_update_nav_menu', 'wpdt_update_cache');
		add_action('update_option_permalink_structure', 'wpdt_update_cache');
		add_action('add_link', 			'wpdt_update_cache');
		add_action('delete_link', 		'wpdt_update_cache');
		add_action('edit_link', 		'wpdt_update_cache');
		add_action('wp_enqueue_scripts', 'wpdt_css');
		add_action('wp_enqueue_scripts', 'wpdt_js');
		add_action('widgets_init', 		'wpdt_load_widgets');	
		add_action('apto_order_update', 'wpdt_update_cache');	// Support for "Advanced Post Types Order" plugin, by sydcode (August 2013)
		add_action('apto_order_update_hierarchical', 'wpdt_update_cache');	// Support for "Advanced Post Types Order" plugin, by sydcode (August 2013)
		wpdt_print_errors();		
	}			
	function wpdt_print_errors(){	
		// Retained for callers of older versions. Diagnostics belong in WP_DEBUG_LOG.
	}
	function wpdt_load_textdomain() {
		load_plugin_textdomain('wp-dtree-30', false, dirname(WPDT_BASENAME).'/lang/');
	}
	function wpdt_maybe_upgrade() {
		$installed = get_option('wpdt_options');
		if (!is_array($installed) || !isset($installed['version']) || $installed['version'] !== wpdt_get_version()) {
			wpdt_install_options();
			wpdt_update_cache();
		}
	}
		
	function wpdt_get_version(){
		static $plugin_data;
		if(!$plugin_data){
			require_once( ABSPATH . 'wp-admin/includes/plugin.php');
			$plugin_data = get_plugin_data( __FILE__, false, false );
		}
		return "".$plugin_data['Version'];
	}	
	function wpdt_activate($networkwide) {
		global $wpdb;	
		if (function_exists('is_multisite') && is_multisite()) {
			if ($networkwide) {
	        	$original_blog_id = $wpdb->blogid;
            	// Get all blog ids
            	$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            	foreach ($blogids as $blog_id) {
            	    switch_to_blog($blog_id);
            	    _wpdt_activate();
            	}
            	switch_to_blog($original_blog_id);
            	return;
        	} else {
				_wpdt_activate();      		
			}
    	}  else {
			_wpdt_activate();      		
		}
	}	
	
	function _wpdt_activate() {
		delete_option('wpdt_db_version');		
		wpdt_install_cache();		
		wpdt_install_options();
		wpdt_print_errors();	
	}
	
	function wpdt_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    	global $wpdb; 
    	if (is_plugin_active_for_network('wp-dtree-30/wp-dtree.php')) {
        	$old_blog = $wpdb->blogid;
        	switch_to_blog($blog_id);
        	_wpdt_activate();
        	switch_to_blog($old_blog);
    	}
	}	
	function wpdt_deactivate($networkwide){
	   	global $wpdb;
		if(function_exists('is_multisite') && is_multisite()) {// check if it is a network activation - if so, run the activation function for each blog id
            if ($networkwide) {
            	$old_blog = $wpdb->blogid;
            	// Get all blog ids
            	$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            	foreach ($blogids as $blog_id) {
            	    switch_to_blog($blog_id);
            	    _wpdt_deactivate();
            	}
            	switch_to_blog($old_blog);
            	return;
        	} else {
				_wpdt_deactivate();
			}
    	} else {
			_wpdt_deactivate();
		}
	}	
	function _wpdt_deactivate() {			
		wpdt_uninstall_cache(); //options are only cleared on plugin uninstall (ie. delete from admin panel)	
	}
		
	function wpdt_set_plugin_meta($links, $file) {		
		if($file == WPDT_BASENAME) {
			return array_merge($links, array(sprintf( '<a href="options-general.php?page=%s">%s</a>', WPDT_BASENAME, __('Settings', 'wp-dtree-30'))));
		}
		return $links;
	}	
	function wpdt_add_admin_footer(){ //shows some plugin info in the footer of the config screen.
		$plugin_data = get_plugin_data(__FILE__);
		printf('%1$s by %2$s (who appreciates <a href="%3$s">books</a> and <a href="https://store.steampowered.com/wishlist/id/ulfben/#sort=order&type=all">Steam games</a>.) :)<br />', esc_html($plugin_data['Name'].' '.$plugin_data['Version']), esc_html($plugin_data['AuthorName']), esc_url(ULFBEN_DONATE_URL));
	}								
	function wpdt_add_option_page(){				
		add_options_page('WP-dTree Settings', 'WP-dTree', 'manage_options', WPDT_BASENAME, 'wpdt_option_page');						 
	}		
	function wpdt_css(){
		if(is_admin() || is_feed()){return;}
		$opt = wpdt_get_options();
		if(!$opt['disable_css']){
			wp_enqueue_style('dtree.css', plugin_dir_url(__FILE__).WPDT_STYLE, false, wpdt_get_version());
		}
	}
	function wpdt_js() {			   	
		if(is_admin() || is_feed()){return;}
		$opt = wpdt_get_options();
		$deps = array();
		if($opt['animate']){
			$deps = array('jquery');
		}
		wp_enqueue_script('dtree', plugin_dir_url(__FILE__).WPDT_SCRIPT, $deps, wpdt_get_version(), false);				
		wp_localize_script('dtree', 'WPdTreeSettings', array('animate' => $opt['animate'],'duration'=>$opt['duration'],'imgurl'=>plugin_dir_url(__FILE__)));
	}	
	function wpdt_load_widgets() {
		require_once('wp-dtree-widget.php');
		require_once('wp-dtree-arc-widget.php');
		require_once('wp-dtree-cat-widget.php');
		require_once('wp-dtree-tax-widget.php');
		require_once('wp-dtree-pge-widget.php');
		require_once('wp-dtree-lnk-widget.php');
		require_once('wp-dtree-mnu-widget.php');
		register_widget('WPDT_Archives_Widget');
		register_widget('WPDT_Categories_Widget');
		register_widget('WPDT_Taxonomies_Widget');
		register_widget('WPDT_Pages_Widget');
		register_widget('WPDT_Links_Widget');
		register_widget('WPDT_Menu_Widget');
	}
	/*These are convenience-functions for theme developers. They work kind of like the WordPress-function they replace. 
		They all accept template tag arguments (query string or assoc. array) - http://codex.wordpress.org/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters
		They accept empty parameter lists and gives reasonable defaults	
	Give array('echo' => 0) to get a very long string in return.
	More info: http://wordpress.org/extend/plugins/wp-dtree-30/other_notes/ */		
	function wpdt_list_archives($args = array()){ 	//similar to wp_get_archives		
		$args = wp_parse_args($args, wpdt_get_defaults('arc'));
		return wpdt_list_($args);
	}
	function wpdt_get_archives($args = array()){ 	//if you want to use WP inconsistent naming... :)
		return wpdt_list_archives($args);
	}
	function wpdt_list_categories($args = array()){ //similar to wp_list_categories
		$args = wp_parse_args($args, wpdt_get_defaults('cat'));		
		return wpdt_list_($args);			
	}	
	function wpdt_list_taxonomies($args = array()){ //similar to wp_list_categories
		$args = wp_parse_args($args, wpdt_get_defaults('tax'));		
		return wpdt_list_($args);			
	}	
	function wpdt_list_pages($args = array()){ 		//similar to wp_list_pages
		$args = wp_parse_args($args, wpdt_get_defaults('pge'));
		return wpdt_list_($args);
	}
	function wpdt_list_links($args = array()){		//similar wp_list_bookmarks
		$args = wp_parse_args($args, wpdt_get_defaults('lnk'));
		return wpdt_list_($args);
	}
	function wpdt_list_bookmarks($args = array()){ 	//wrapper to emulate new WP function names
		return wpdt_list_links($args); 
	}
	function wpdt_list_menu($args = array()){ 	
		$args = wp_parse_args($args, wpdt_get_defaults('mnu'));
		return wpdt_list_($args);
	}
	function wpdt_get_archives_defaults(){ //to simplify finding all parameters
		return wpdt_get_defaults('arc');
	}
	function wpdt_get_categories_defaults(){
		return wpdt_get_defaults('cat');
	}
	function wpdt_get_taxonomies_defaults(){
		return wpdt_get_defaults('tax');
	}
	function wpdt_get_pages_defaults(){
		return wpdt_get_defaults('pge');
	}
	function wpdt_get_links_defaults(){
		return wpdt_get_defaults('lnk');
	}
	function wpdt_get_menu_defaults(){
		return wpdt_get_defaults('mnu');
	}
	
	/*End "public" functions*/	
		
	function wpdt_list_($args){//common stub for "wp_list_*"-wrappers.
		$args['echo'] = !isset($args['echo']) ? 1 : $args['echo']; //default to print
		if($args['echo']){
			echo wpdt_get_tree($args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Builder escapes HTML fields and JSON-encodes script data; escaping the complete tree would break its markup and script.
		}else{
			return wpdt_get_tree($args);
		}
	}		
	function wpdt_print_tree($args){		
		echo wpdt_get_tree($args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Builder escapes HTML fields and JSON-encodes script data; escaping the complete tree would break its markup and script.
	}
	function wpdt_set_child_of_current(&$args){			
		if($args['treetype'] == 'pge' && is_page()){			
			$args['child_of'] = get_the_ID();
		}else if($args['treetype'] == 'cat'){
			$catObj = get_the_category();
			if($catObj && isset($catObj[0])){
				$args['child_of'] = $catObj[0]->cat_ID;
			}
		}else if($args['treetype'] == 'tax'){
			$terms = get_the_terms(get_the_ID(), $args['taxonomy']);
			if($terms && !is_wp_error($terms) && isset($terms[0])){ 
				$args['child_of'] = $terms[0]->term_id;				
			}
		}		
	}
	function wpdt_get_tree($args){ 				
		require_once('wp-dtree-build.php');	
		global $wpdt_tree_ids;
		$args = wp_parse_args($args, wpdt_get_defaults($args['treetype']));
		if(isset($args['child_of_current']) && $args['child_of_current'] == 1){
			wpdt_set_child_of_current($args);
		}
		$wpdt_tree_ids[$args['treetype']] += 1; //uniquely identify all trees.
		$opt = wpdt_get_options();	
		$was_cached = ($args['cache'] == 1);
		$seed = '';
		$tree = '';		
		if($args['cache']){
			$seed = wpdt_get_seed($args);		
			$tree = wpdt_get_cached_data($seed);			
		}			
		if(!$tree){
			$was_cached = false;
			if($args['treetype'] == 'arc'){
				require_once('wp-dtree-arc.php');
				$nodelist = wpdt_get_archive_nodelist($args);
				if(isset($args['show_post_count'])){$args['showcount'] = $args['show_post_count'];} //convert vanilla wp_get_archives arguments				
				$tree = wpdt_build_tree($nodelist, $args);
				if($opt['addnoscript']){
					$args['echo'] = 0;		
					$tree .= "\n<noscript>\n".wp_get_archives($args)."\n</noscript>\n";								
				}
			}else if($args['treetype'] == 'cat'){
				require_once('wp-dtree-cat.php');
				if(isset($args['parent']) && $args['parent'] == 'none'){unset($args['parent']);} //no default for parent, so let's flag and turn it off here.								
				if(isset($args['show_count'])){$args['showcount'] = $args['show_count'];} //convert vanilla wp_list_categories arguments
				if(isset($args['orderby'])){$args['sortby'] = $args['orderby'];}
				if(isset($args['order'])){$args['sortorder'] = $args['order'];}
				if(isset($args['feed'])){$args['showrss'] = 1;}			
				$nodelist = wpdt_get_category_nodelist($args);
				$tree = wpdt_build_tree($nodelist, $args);
				if($opt['addnoscript']){
					$args['echo'] = 0;		
					$tree .= "\n<noscript>\n".wp_list_categories($args)."\n</noscript>\n";								
				}
			}else if($args['treetype'] == 'tax'){
				require_once('wp-dtree-tax.php');
				if(isset($args['parent']) && $args['parent'] == 'none'){unset($args['parent']);} //no default for parent, so let's flag and turn it off here.								
				if(isset($args['show_count'])){$args['showcount'] = $args['show_count'];} //convert vanilla wp_list_categories arguments
				if(isset($args['orderby'])){$args['sortby'] = $args['orderby'];}
				if(isset($args['order'])){$args['sortorder'] = $args['order'];}
				if(isset($args['feed'])){$args['showrss'] = 1;}			
				$nodelist = wpdt_get_taxonomy_nodelist($args);
				$tree = wpdt_build_tree($nodelist, $args);
				if($opt['addnoscript']){
					$args['echo'] = 0;		
					$tree .= "\n<noscript>\n".wp_list_categories($args)."\n</noscript>\n";	//http://groups.google.com/group/wp-hackers/browse_thread/thread/24a41454c945dd9f?pli=1							
				}
			}else if($args['treetype'] == 'pge'){
				require_once('wp-dtree-pge.php');
				if(!isset($args['sort_column']) || $args['sort_column'] == ''){$args['sort_column'] = $args['sortby'];} //handle the vanilla wp_get_pages arguments.
				$nodelist = wpdt_get_pages_nodelist($args);
				$tree = wpdt_build_tree($nodelist, $args);
				if($opt['addnoscript']){
					$args['echo'] = 0;		
					$tree .= "\n<noscript>\n".wp_list_pages($args)."\n</noscript>\n";								
				}				
			}else if($args['treetype'] == 'lnk'){ 
				require_once('wp-dtree-lnk.php');
				if(!isset($args['orderby']) || $args['orderby'] == ''){$args['orderby'] = $args['sortby'];} //handle the vanilla wp_get_bookmarks arguments.	
				if(!isset($args['order']) || $args['order'] == ''){$args['order'] = $args['sort_order'];} 
				$nodelist = wpdt_get_links_nodelist($args);
				$tree = wpdt_build_tree($nodelist, $args);
				if($opt['addnoscript']){
					$args['echo'] = 0;		
					$tree .= "\n<noscript>\n".wp_list_bookmarks($args)."\n</noscript>\n";								
				}					
			}else if($args['treetype'] == 'mnu'){ 
				require_once('wp-dtree-mnu.php');				
				$nodelist = wpdt_get_menu_nodelist($args);
				$tree = wpdt_build_tree($nodelist, $args);
					/*no no-script version supported for menus.*/
			}else{//user error. no type given. 
				return false;// '<!-- wpdt_get_tree: user error, no treetype given. -->';
			}			
		}		
		if($args['cache'] && !$was_cached){
			wpdt_insert_tree_data($tree, $seed);
		} 	
		if($args['opentoselection'] || $args['opento']){ 	
			$tree_id = wpdt_get_tree_id($tree); 
			$openTo = '';			
			if($tree_id){
				$listposts = (isset($args['listposts']) && $args['listposts'] == 1); //a special case for category trees
				if($args['opentoselection'] && isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])){
					$openTo .= wpdt_open_tree_to(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])), $tree_id, $tree, false, $listposts);
				}
				if($args['opento']){ //force open to			
					$openTo .= wpdt_force_open_to($args['opento'], $tree_id, $tree, $listposts);	
				}
			}
			if($openTo){
				$openScript = ($opt['openscript']) ? $opt['openscript'] : "<script type='text/javascript'>"; //this happens for some reason?
				$closeScript = ($opt['closescript']) ? $opt['closescript'] : '</script>' ; 
				$tree .= $openScript . $openTo . $closeScript;	
			}
		}
		unset($opt);
		wpdt_print_errors();
		return $tree;
	}	
	
	function wpdt_get_defaults($treetype){
		$common = array('title' => '', 'cache'=> 1, 'opento' => '', 'uselines' => 1, 'useicons' => 0, 
			'exclude' => '', 'closelevels' => 1, 'folderlinks' => 0, 'showselection' => 0, 'include' => '',
			'opentoselection' => 1,'truncate' => 0, 'sort_order' => 'ASC', 'sortby' => 'ID', 'treetype' => $treetype,
			'openlink' 	=> __('open all', 'wp-dtree-30'), 'closelink' => __('close all', 'wp-dtree-30'), 'oclink_sep' => ' | '
		);		
		if($treetype == 'mnu'){
			return array_merge($common,array(
				'title' => 				__('Menu', 'wp-dtree-30'),
				'order'                 => 'ASC',
				'orderby'               => 'menu_order',
				'post_type'             => 'nav_menu_item',
				'post_status'           => 'publish',
				'output'                => ARRAY_A,
				'output_key'            => 'menu_order',
				'nopaging'              => true,
				'update_post_term_cache'=> false,
				'menuslug'				=> ''
			));
		}else if($treetype == 'arc'){			
			return array_merge($common, array(				
				'title' => __('Archives', 'wp-dtree-30'),
				'sortby' 	=> 'post_date',
				'sort_order'=> 'DESC',
				'exclude_cats' => '',
				'include_cats' => '',				
				'listposts' => 1,				
				'showrss' 	=> 0,
				'type' 		=> 'monthly',
				'showcount' => 1,		//show_post_count 
				'limit_posts'=> 0,
				'number_of_posts'=> 0,
				'posttype'	=> 'post'
			));
		}else if($treetype == 'cat'){
			return array_merge($common, array(			
				'title' => __('Categories', 'wp-dtree-30'),								
				'cpsortby' 		=> 'post_date',
				'cpsortorder' 	=> 'DESC',			
				'hide_empty' 	=> 1,
				'child_of' 		=> 0,
				'child_of_current' => 0,
				'parent' 		=> 'none', //there is no default for parents.
				'allowdupes' 	=> 1,
				'postexclude' 	=> '',
				'listposts' 	=> 1,									
				'showrss' 		=> 0,
				'showcount' 	=> 0,	//show_count
				'taxonomy' 		=> 'category',			
				'pad_counts' 	=> 1,
				'hierarchical' 	=> 1,
				'number' 		=> 0,
				'limit_posts'	=> 0,
				'more_link' 	=> "Show more (%excluded%)...", //if number of posts-limit is hit, show link to full category listing
				'include_last_update_time' => 0
			));		
		}else if($treetype == 'tax'){
			return array_merge($common, array(				
				'title' => __('Taxonomy', 'wp-dtree-30'),								
				'cpsortby' 		=> 'post_date',
				'cpsortorder' 	=> 'DESC',			
				'usedescription' => 0, //use taxonomy description, instead of name, to render the tree
				'hide_empty' 	=> 1,
				'child_of' 		=> 0,
				'child_of_current' => 0,
				'parent' 		=> 'none', //there is no default for parents.
				'allowdupes' 	=> 1,
				'postexclude' 	=> '',
				'listposts' 	=> 1,									
				'showrss' 		=> 0,
				'showcount' 	=> 0,	//show_count
				'taxonomy' 		=> 'taxonomy', //or any registered taxonomy			
				'pad_counts' 	=> 1,
				'hierarchical' 	=> 0,
				'number' 		=> 0,
				'limit_posts'	=> 0,
				'more_link' 	=> "Show more (%excluded%)...", //if number of posts-limit is hit, show link to full category listing
				'include_last_update_time' => 0
			));		
		}else if($treetype == 'pge'){
			return array_merge($common, array(
				'title' => __('Pages', 'wp-dtree-30'),
				'folderlinks' 	=> 1,
				//'sort_column' 	=> '', //handle inconsistent argument names in WordPress API. Other functions use 'sortby'.
				'meta_key' 		=> '',
				'meta_value' 	=> '',
				'authors' 		=> '',
				'child_of'		=> 0, 
				'child_of_current' => 0,
				'parent_as_root'=> 0, //when selecting children, make the parent visible as their root too.
				'parent' 		=> -1,
				'exclude_tree' 	=> -1,				
				//'number' 		=> -1, //unused. don't know what it's for. :P
				//'offset' 		=> 0, //same. No idea what I added this for. 
				'hierarchical' 	=> 1				
			));				
		}else if($treetype == 'lnk'){
			return array_merge($common, array(
				//limit -1
				'title' => __('Links', 'wp-dtree-30'),
				'opentoselection' => 0,
				'useselection' 	=> 0,
				'showcount'		=> 0,
				'catsorderby'	=> 'name',
				'catssort_order'=> 'ASC',
				'folderlinks' 	=> 0,			
				'sortby' 		=> 'name',
				//'orderby'       => 'name', //inconsistent argument names in WordPress API. All others use 'sortby'.				
				//'order'         => 'ASC', //other uses 'sort_order'								
				'category'      => '', //Comma separated list of bookmark category ID's.
				'category_name' => '', //Category name of a catgeory of bookmarks to retrieve. Overrides category parameter.
				'hide_invisible'=> 1,
				'show_updated'  => 0,								
				'search'        => '' //Searches link_url, link_name or link_description like the search string.				
			));				
		}else{
			return array(				
				'openscript'=> "\n<script type='text/javascript'>\n/* <![CDATA[ */\ntry{\n",
				'closescript'=> "}catch(e){} /* ]]> */\n</script>\n",
				'addnoscript'=> 0,
				'version' 	=> wpdt_get_version(),
				'animate' 	=> 1, 
				'duration' 	=> 250,
				'disable_css'=> 0
			);
		}
	}
		
	function wpdt_install_options(){						
		$old = get_option('wpdt_options');
		$default = wpdt_get_defaults('gen'); //general settings	
		if(isset($old['genopt'])){ //old leftovers from previous version. Nukem.
			update_option('wpdt_options', $default);
		}else{
			if(empty($old) || !is_array($old)){
					$old = array();
			}
			$new = array_merge($default,$old);
			$new['version'] = wpdt_get_version();
			update_option('wpdt_options',$new);
		}		
	}

	/** Normalize historical settings before they reach HTML or script output. */
	function wpdt_get_options() {
		$defaults = wpdt_get_defaults('gen');
		$saved = get_option('wpdt_options');
		$opt = array_merge($defaults, is_array($saved) ? $saved : array());
		foreach (array('animate', 'addnoscript', 'disable_css') as $flag) {
			$opt[$flag] = empty($opt[$flag]) ? 0 : 1;
		}
		$opt['duration'] = is_scalar($opt['duration']) ? max(0, (int) $opt['duration']) : 250;
		$wrappers = array(
			"\n<script type='text/javascript'>\ntry{\n" => "}catch(e){}</script>\n",
			"\n<script type='text/javascript'>\n<!--\ntry{\n" => "}catch(e){} //-->\n</script>\n",
			$defaults['openscript'] => $defaults['closescript'],
		);
		if (!is_string($opt['openscript']) || !isset($wrappers[$opt['openscript']])) {
			$opt['openscript'] = $defaults['openscript'];
		}
		$opt['closescript'] = $wrappers[$opt['openscript']];
		return $opt;
	}

	function wpdt_option_page(){
		if(!function_exists('current_user_can') || !current_user_can('manage_options') ){
			wp_die(esc_html__('You are not allowed to change these settings.', 'wp-dtree-30'), '', array('response' => 403));
		}				
		require_once('wp-dtree-cache.php');	 
		add_action('in_admin_footer', 'wpdt_add_admin_footer');
		$oplain	= "\n<script type='text/javascript'>\ntry{\n";	
		$cplain = "}catch(e){}</script>\n";
		$ohtml = "\n<script type='text/javascript'>\n<!--\ntry{\n";
		$chtml = "}catch(e){} //-->\n</script>\n";
		$oxml = "\n<script type='text/javascript'>\n/* <![CDATA[ */\ntry{\n";		
		$cxml = "}catch(e){} /* ]]> */\n</script>\n";
		$opt = wpdt_get_options();		
		if($opt['version'] != wpdt_get_version()){
			wpdt_install_options(); //update options if the user forgot to disable the plugin prior to upgrading.
			$opt = wpdt_get_options();			
		}				
		if(isset($_POST['submit'])){
			if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD']) {
				wp_die(esc_html__('Invalid request method.', 'wp-dtree-30'), '', array('response' => 405));
			}
			check_admin_referer('wpdt_save_settings');
			$opt['version'] = wpdt_get_version();	
			$opt['duration'] = isset($_POST['duration']) && is_scalar($_POST['duration']) ? max(0, (int) $_POST['duration']) : 250;
			$opt['animate'] = isset($_POST['animate']) ? 1 : 0;	
			$opt['addnoscript'] = isset($_POST['addnoscript']) ? 1 : 0;
			$opt['disable_css'] = isset($_POST['disable_css']) ? 1 : 0;
			$method = isset($_POST['openscript']) && is_string($_POST['openscript']) ? sanitize_key(wp_unslash($_POST['openscript'])) : 'plain';
			if($method == 'html'){
				$opt['openscript'] = $ohtml;
				$opt['closescript'] = $chtml;
			}else if($method == 'xml'){
				$opt['openscript'] = $oxml;
				$opt['closescript'] = $cxml;
			}else{
				$opt['openscript'] = $oplain;
				$opt['closescript'] = $cplain;
			}
			update_option('wpdt_options', $opt);
			echo '<div id="message" class="notice notice-success"><p>'.esc_html__('WP-dTree settings updated...','wp-dtree-30').'</p></div>';
			wpdt_update_cache();
		}		
	?>	
	
	<form method="post">
	<?php wp_nonce_field('wpdt_save_settings'); ?>
	<div class="wrap">									
		<h2><?php esc_html_e('WP-dTree General Settings','wp-dtree-30'); ?></h2>				
		<table class="optiontable" width="80%">
			<fieldset class="options">
			<tr><td valign="top">
			<p style="font-weight:bold;">Widget-settings are in <a href="<?php echo esc_url(admin_url('widgets.php')); ?>">the widget panels</a>.</p>
			<p>
				<label for="animate" title="<?php esc_attr_e('Use jquery to animate the tree opening/closing.','wp-dtree-30'); ?>"><?php esc_html_e('Animate:', 'wp-dtree-30'); ?></label>
				<input class="checkbox" type="checkbox" <?php checked($opt['animate'], true ); ?> id="animate" name="animate" /> 								
				<input type="number" min="0" value="<?php echo esc_attr($opt['duration']); ?>" name="duration" id="duration" size="10" />
				<label><?php esc_html_e('Duration (milliseconds)', 'wp-dtree-30'); ?></label>
			</p><p>
				<label for="disable_css" title="<?php esc_attr_e('To style the trees, copy wp-dtree.css to your themes\'s stylesheet and edit that. Then disable this.','wp-dtree-30'); ?>"><?php esc_html_e('Disable WP-dTree\'s default stylesheet:', 'wp-dtree-30'); ?></label>
				<input class="checkbox" type="checkbox" <?php checked($opt['disable_css'], true ); ?> id="disable_css" name="disable_css" /> 			
			</p><p>
				<label for="addnoscript" title="<?php esc_attr_e('Outputs normal archives/pages/links/categories, to no-javascript users. Doubles the size of each tree!','wp-dtree-30'); ?>"><?php echo wp_kses_post(__('Include <a href="http://www.w3schools.com/tags/tag_noscript.asp">noscript</a> fallbacks:', 'wp-dtree-30')); ?></label>
				<input class="checkbox" type="checkbox" <?php checked($opt['addnoscript'], true ); ?> id="addnoscript" name="addnoscript" /> 			
			</p><p>
				<label for="openscript" title="<?php esc_attr_e('Might be useful for validation of your site','wp-dtree-30'); ?>"><?php esc_html_e('Javascript escape method:', 'wp-dtree-30'); ?></label> 
				<select id="openscript" name="openscript">
					<option value="html" <?php selected($ohtml, $opt['openscript']);?>><?php echo esc_html('<!--'); ?></option>
					<option value="xml" <?php selected($oxml, $opt['openscript']);?>><?php echo esc_html('/* <![CDATA[ */'); ?></option>
					<option value="plain" <?php selected($oplain, $opt['openscript']);?>>(no escaping)</option>
				</select>
			</p>
			<p><input id="submit" type="submit" name="submit" value="<?php esc_attr_e('Update Settings', 'wp-dtree-30'); ?>" /></p>
			</td><td></td>
			</tr>			
			</fieldset>												
		</table>
										
	</div>		
	</form>
	<?php
}
?>
