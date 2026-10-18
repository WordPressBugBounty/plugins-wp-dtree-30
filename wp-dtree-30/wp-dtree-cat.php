<?php
defined('ABSPATH') || exit;
function wpdt_get_category_nodelist($args){	
	global $wpdb;	
	extract($args, EXTR_SKIP);			
	if (!wpdt_valid_post_sort($cpsortby)) { return array(); }
	$cpsortorder = wpdt_sort_order($cpsortorder);
	$limit_posts = max(0, (int) $limit_posts);
	$idcount = 1;
	$nodelist = array();
	$catids = array();
	$catresults = get_categories(array(
		'orderby' => $sortby, 'order' => $sort_order, 'taxonomy' => $taxonomy,
		'hide_empty' => $hide_empty, 'include_last_update_time' => $include_last_update_time, 'hierarchical' => 1, 
		'exclude' => $exclude, 'include' => $include, 'number' => $number, 'pad_counts' => $pad_counts, 'child_of' => $child_of
//		'parent' => $parent
	));			
	foreach ($catresults as $cat){						
		$nodelist[$idcount] = array( 
			'id' => -$cat->cat_ID, 
			'pid' => -$cat->category_parent,					 
			'url' => get_category_link($cat->term_id),
			'name' => ($showcount) ? wp_strip_all_tags($cat->name ." ({$cat->count})") : wp_strip_all_tags($cat->name),
			'title' => wp_strip_all_tags($cat->description)			
		);// <span class='dtcount'>($postcount)</span>
		$catids[$cat->cat_ID] = array('posts_returned' => 0, 'count' => $cat->count); //save the ID, post-counter and actual post count in case we're asked to limit the tree and needs to know how much we've kept back
		$idcount++;		
	}	
	//categories can be arranged arbitrarily, and with some creative exlusion/inclusion, you'll easily create a tree without a single page connecting to root or a even parent.		
	$node_ids = array_fill_keys(array_column($nodelist, 'id'), true);
	foreach($nodelist as $key => $node){ //thus this step to fixup any orphans.
		if($node['pid'] == 0){continue;} //connected to root.
		if(!isset($node_ids[$node['pid']])){$nodelist[$key]['pid'] = 0;} //connect orphans to root.
	}				
	if(!$listposts || !count($nodelist)){ //it's either empty or we don't need to list posts. Either way - skip the rest.		
		return $nodelist;
	}	
	unset($catresults);
	$postresults = wpdt_get_term_posts(array_keys($catids), 'category', $args);
	foreach($postresults as $postresult){
		if(!isset($catids[$postresult->term_id])){
			continue;
		}
		$text = wp_strip_all_tags(apply_filters('the_title', $postresult->post_title));		
		$url = esc_url(get_permalink($postresult->ID));
		$catids[$postresult->term_id]['posts_returned'] += 1; //add
		$nodelist[$idcount] = array(
			'id' => $postresult->ID, 
			'pid' => -$postresult->term_id,
			'name' => $text, 
			'url' => $url, 
			'title' => ''
		);
		$idcount++;	
	}		
	if($limit_posts > 0){ //add the "Show more"-links, if we've limited the tree length
		$show_more = ($more_link) ? $more_link : "Show more (%excluded%)...";
		foreach($catids as $catid => $count){
			$excluded = $count['count']-$count['posts_returned'];
			if($excluded > 0){				
				$nodelist[$idcount++] = array(
					'id' => "'{$idcount}'", //a string, to avoid ID-trampling.
					'pid' => -$catid, 
					'name' => str_replace('%excluded%', $excluded, $show_more), //add category count? 
					'url' => get_category_link($catid), 
					/* translators: %s: term name. */
					'title' => sprintf(__('Browse all posts in %s', 'wp-dtree-30'), get_cat_name($catid))
				);				
			}
		}	
	}	
	unset($catids);
	unset($postresults);
	// Support for "Advanced Post Types Order" plugin
	// Only when sorting posts by menu order, like in free plugin
	// Added by sydcode (August 2013)
	if(!defined('APTO_SLUG') || $cpsortby != 'menu_order' || APTO_SLUG != 'advanced-post-types-order') {
		return $nodelist;
	}
	// Check table exists
	$apto_table = $wpdb->prefix . 'apto';
	$table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($apto_table)));
	if(!$table_exists){
		return $nodelist;
	}
	// Find category nodes
	foreach($nodelist as $node) {
		if(!is_numeric($node['id']) || $node['id'] >= 0){
			continue;
		}
		// Get ordered posts for each category node
		$term_id = abs($node['id']);
		$sql = "SELECT post_id FROM {$apto_table} 
			WHERE taxonomy = 'category' AND post_type = 'post' 
			AND term_id = %d			
			ORDER BY id {$cpsortorder}
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Term ID is prepared; table is the trusted WordPress prefix and sort direction is allowlisted above.
		$apto_posts = $wpdb->get_results($wpdb->prepare($sql, $term_id), ARRAY_A);
		if(empty($apto_posts)) {
			continue;
		}
		// Search for matching posts in nodelist
		foreach ($apto_posts as $post1) {
			foreach ($nodelist as $key1 => $node1) {
				if ($node['id'] == $node1['pid'] && $post1['post_id'] == $node1['id']) {
					// Move post to end of nodelist
					$item = $nodelist[$key1];
					unset($nodelist[$key1]);
					array_push($nodelist, $item); 
					break;
				}
			}
		}
	}
	return $nodelist;
}
?>
