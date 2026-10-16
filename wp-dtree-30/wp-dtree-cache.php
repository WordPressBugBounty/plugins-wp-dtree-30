<?php
defined('ABSPATH') || exit;
//In WP 2.6, I suddenly got problems with global variables "dissapearing", so these getters are... Q&D.
function wpdt_get_table_name(){
	global $wpdb; return $wpdb->prefix . "dtree_cache";
}

function wpdt_install_cache(){
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	global $wpdb;
	$wpdt_cache = wpdt_get_table_name();
	wpdt_uninstall_cache();			
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE {$wpdt_cache} (
	hash BINARY(16) NOT NULL, 
	content MEDIUMTEXT NOT NULL,				
	UNIQUE KEY  hash (hash)		
	) {$charset_collate};";	
	dbDelta($sql);					
}

function wpdt_uninstall_cache(){			
	global $wpdb;
	$wpdt_cache = wpdt_get_table_name();	
	if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdt_cache))) == $wpdt_cache) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name comes only from the trusted WordPress database prefix plus a fixed suffix (WP 6.0 has no %i placeholder).
		$wpdb->query("DROP TABLE " . $wpdt_cache);
	}	
}

/*we no longer have a single monoholotic cache.
when the blog changes, we simply invalidate all stored cache rows.*/
function wpdt_update_cache(){ 
	global $wpdb;		
	$wpdt_cache = wpdt_get_table_name();
	$wpdb->query("DELETE FROM {$wpdt_cache}");
}

function wpdt_get_seed($args){	
	return wpdt_get_version() . ':' . serialize($args);
}

function wpdt_insert_tree_data($treedata, $seed){
	if(!isset($treedata) || $treedata == ""){
		return;
	}	
	global $wpdb;
	$wpdt_cache = wpdt_get_table_name();			
	$sql = $wpdb->prepare("REPLACE {$wpdt_cache} (hash, content) VALUES (UNHEX(MD5(%s)), %s)", $seed, $treedata);
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Both values are prepared above; table name is the trusted WordPress prefix plus a fixed suffix.
	$wpdb->query($sql);
}

function wpdt_get_cached_data($seed){
	global $wpdb;
	if(is_array($seed)){
		$seed = wpdt_get_seed($seed);
	}
	$wpdt_cache = wpdt_get_table_name();	
	$results = $wpdb->get_var($wpdb->prepare("SELECT content FROM {$wpdt_cache} WHERE hash = UNHEX(MD5(%s)) LIMIT 1", $seed));
	return ($results) ? $results : '';
}

function wpdt_clear_cache($seed){ /*args = settings array for a tree*/
	global $wpdb;
	if(is_array($seed)){
		$seed = wpdt_get_seed($seed);
	}	
	$wpdt_cache = wpdt_get_table_name();		
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdt_cache} WHERE hash = UNHEX(MD5(%s))", $seed));
	return;
}

function wpdt_clean_exclusion_list($excluded){	
	$cleanlist = '';
	if(empty($excluded) || !is_scalar($excluded)){ return $cleanlist; }
	$exposts = preg_split('/[\s,]+/',$excluded);	
	if(!count($exposts)){ return $cleanlist; }
	$exposts = array_unique($exposts);
	foreach($exposts as $expostID){
		if(!is_numeric($expostID)){continue;}
		if(empty($cleanlist)){
			$cleanlist = intval($expostID); 
		} else{		
			$cleanlist = $cleanlist . "," . intval($expostID);
		}						
	}				
	return $cleanlist;
}

function wpdt_build_exclude_statement($excluded, $field = 'ID'){
	$excluded = wpdt_clean_exclusion_list($excluded);
	return ($excluded) ? " AND {$field} NOT IN ($excluded) " : '';
}

function wpdt_valid_post_sort($column) {
	return in_array($column, array('ID', 'post_title', 'post_date', 'post_modified', 'post_author', 'post_name', 'menu_order'), true);
}

function wpdt_sort_order($order) {
	return is_string($order) && strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
}
?>
