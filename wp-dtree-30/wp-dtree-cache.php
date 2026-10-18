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

/** Fetch post memberships only for displayed terms, assigning a parent before limits. */
function wpdt_get_term_posts($term_ids, $taxonomy, $args) {
	global $wpdb;
	if (!is_string($taxonomy) || !taxonomy_exists($taxonomy) || !wpdt_valid_post_sort($args['cpsortby'])) { return array(); }
	$term_ids = array_values(array_unique(array_filter(array_map('intval', $term_ids), function ($id) { return $id > 0; })));
	if (!$term_ids) { return array(); }
	$ids = implode(',', $term_ids);
	$sortby = $args['cpsortby'];
	$order = wpdt_sort_order($args['cpsortorder']);
	$limit = max(0, (int) $args['limit_posts']);
	$post_exclusions = wpdt_build_exclude_statement($args['postexclude'], 'wpdt_post.ID');
	$parent_selection = '';
	$values = array($taxonomy);
	if (empty($args['allowdupes'])) {
		// The ordered visible-term list is authoritative, even when the chosen
		// branch's display limit later hides the post. Never move it to a second branch.
		$parent_selection = " AND wpdt_term.term_id = (
			SELECT wpdt_choice.term_id FROM {$wpdb->term_relationships} AS wpdt_membership
			INNER JOIN {$wpdb->term_taxonomy} AS wpdt_choice ON wpdt_choice.term_taxonomy_id = wpdt_membership.term_taxonomy_id
			WHERE wpdt_membership.object_id = wpdt_post.ID AND wpdt_choice.taxonomy = %s
			AND wpdt_choice.term_id IN ({$ids})
			ORDER BY FIELD(wpdt_choice.term_id, {$ids}) LIMIT 1
		)";
		$values[] = $taxonomy;
	}
	$post_order = "wpdt_post.{$sortby} {$order}" . ($sortby === 'ID' ? '' : ", wpdt_post.ID {$order}");
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted table names, integer term/exclusion lists, fixed clauses and allowlisted ordering; taxonomy values are prepared below.
	$query = $wpdb->prepare("SELECT wpdt_post.ID, wpdt_post.post_title, wpdt_term.term_id
		FROM {$wpdb->posts} AS wpdt_post
		INNER JOIN {$wpdb->term_relationships} AS wpdt_relation ON wpdt_relation.object_id = wpdt_post.ID
		INNER JOIN {$wpdb->term_taxonomy} AS wpdt_term ON wpdt_term.term_taxonomy_id = wpdt_relation.term_taxonomy_id
		WHERE wpdt_term.taxonomy = %s AND wpdt_term.term_id IN ({$ids})
		AND wpdt_post.post_type = 'post' AND wpdt_post.post_status = 'publish'
		{$post_exclusions} {$parent_selection}", $values);
	if ($limit) {
		$queries = array();
		foreach ($term_ids as $term_id) {
			$queries[] = "({$query} AND wpdt_term.term_id = {$term_id} ORDER BY {$post_order} LIMIT {$limit})";
		}
		$query = implode(' UNION ALL ', $queries);
	} else {
		$query .= " ORDER BY {$post_order}, FIELD(wpdt_term.term_id, {$ids})";
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared taxonomy query; subsequent clauses contain only integer IDs/limits and allowlisted ordering.
	return (array) $wpdb->get_results($query);
}
?>
