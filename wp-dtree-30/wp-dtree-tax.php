<?php
defined('ABSPATH') || exit;
function wpdt_get_taxonomy_nodelist($args){	
	global $wpdb;
	extract($args, EXTR_SKIP);	
	if (!wpdt_valid_post_sort($cpsortby) || !is_string($taxonomy) || !taxonomy_exists($taxonomy)) { return array(); }
	$cpsortorder = wpdt_sort_order($cpsortorder);
	$limit_posts = max(0, (int) $limit_posts);
	$idcount = 1;
	$nodelist = array();
	$termids = array();
	$termargs = array(
		'orderby' => $sortby,
		'order' => $sort_order, 
		'taxonomy' => $taxonomy,
		'hide_empty' => $hide_empty, 
		'include_last_update_time' => $include_last_update_time, 
		'hierarchical' => 1, //is_taxonomy_hierarchical($taxonomy) 
		'exclude' => $exclude, 
		'include' => $include, 
		'number' => $number, 
		'pad_counts' => $pad_counts, 
		'child_of' => $child_of
		//'parent' => $parent
	);				
	$taxonomyresults = get_terms($termargs);
	if(is_wp_error($taxonomyresults)){
		return array();
	}	
	foreach ($taxonomyresults as $term){				
		$name = ($usedescription == true) ? wp_strip_all_tags($term->description) : wp_strip_all_tags($term->name);
		$nodelist[$idcount] = array( 
			'id' => -$term->term_id, 
			'pid' => -$term->parent,				
			'url' => get_term_link($term, $taxonomy),
			'name' => ($showcount) ? $name ."&nbsp;({$term->count})" : $name,			
			'title' => wp_strip_all_tags($term->name .': '. $term->description)			
		);		
		$termids[$term->term_id] = array('posts_returned' => 0, 'count' => $term->count); //save the ID, post-counter and actual post count in case we're asked to limit the tree and needs to know how much we've kept back
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
	unset($taxonomyresults);
	$postexclusions = wpdt_build_exclude_statement($postexclude, $wpdb->posts.'.ID');		
	$catexclusions = wpdt_build_exclude_statement($exclude, $wpdb->terms.'.term_id');	
	$groupby = (!$allowdupes) ? " GROUP BY {$wpdb->posts}.ID ": '';	
	$unions = array();	
	// Only trusted table names, allowlisted sort syntax and integer exclusion lists are interpolated.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- See validation above and wpdt_build_exclude_statement().
	$query = $wpdb->prepare("(SELECT {$wpdb->posts}.ID AS 'ID', {$wpdb->posts}.post_title AS 'post_title', {$wpdb->terms}.term_id AS 'term_id'
				 FROM {$wpdb->posts}, {$wpdb->terms}, {$wpdb->term_relationships}, {$wpdb->term_taxonomy} 
				 WHERE {$wpdb->term_relationships}.object_id = {$wpdb->posts}.ID 			 
				 AND {$wpdb->term_taxonomy}.taxonomy = %s
				 AND {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id 
				 AND {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id 	 
				 /*tax-id*/
				 AND {$wpdb->posts}.post_status = 'publish' 
				 AND {$wpdb->posts}.post_type = 'post' 
				{$catexclusions} 				
				{$postexclusions} 
				{$groupby} 
				ORDER BY {$wpdb->posts}.{$cpsortby} {$cpsortorder}
				/*limit*/)", $taxonomy);
	if($limit_posts > 0){ //selecting subsets of subsets: http://www.mysqlperformanceblog.com/2006/08/10/using-union-to-implement-loose-index-scan-to-mysql/		
		foreach($termids as $termid => $count){
			$termid = (int) $termid;
			$unions[] = str_replace('/*tax-id*/', " AND {$wpdb->term_taxonomy}.term_id = {$termid} ", str_replace('/*limit*/', " LIMIT {$limit_posts}", $query));			
		}			
		$query = implode(' UNION ALL ', $unions);
		unset($unions);
	}	
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared taxonomy; unions add only integer term IDs/limits, allowlisted sorting and integer exclusions.
	$postresults = (array)$wpdb->get_results($query);
	foreach($postresults as $postresult){
		$text = wp_strip_all_tags(apply_filters('the_title', $postresult->post_title));		
		$url = esc_url(get_permalink($postresult->ID));
		$termids[$postresult->term_id]['posts_returned'] += 1; //add 
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
		foreach($termids as $termid => $count){
			$excluded = $count['count']-$count['posts_returned'];
			if($excluded > 0){				
				$nodelist[$idcount++] = array(
					'id' => "'{$idcount}'", //a string, to avoid ID-trampling.
					'pid' => -$termid, 
					'name' => str_replace('%excluded%', $excluded, $show_more), //add category count? 
					'url' => get_term_link($termid, $taxonomy), 
					/* translators: %s: term name. */
					'title' => sprintf(__('Browse all posts in %s', 'wp-dtree-30'), get_cat_name($termid))
				);				
			}
		}	
	}	
	unset($termids);
	unset($postresults);
	return $nodelist;
}
?>
