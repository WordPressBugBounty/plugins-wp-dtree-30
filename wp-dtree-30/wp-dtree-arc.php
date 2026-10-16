<?php
defined('ABSPATH') || exit;
function wpdt_get_archive_nodelist($args){ //get archive nodelist	
	global $month, $wpdb, $wp_locale;	
	extract( $args, EXTR_SKIP );			
	// Identifiers cannot be SQL placeholders; accept only the supported sort columns.
	if (!wpdt_valid_post_sort($sortby) || !is_string($posttype) || !post_type_exists($posttype)) { return array(); }
	$sort_order = wpdt_sort_order($sort_order);
	$limit_posts = max(0, (int) $limit_posts);
	$include_cats = wpdt_clean_exclusion_list($include_cats);
	$exclude_cats = wpdt_clean_exclusion_list($exclude_cats);
	$isyearly = ($type == 'yearly');	
	$idcount = 1; 
	$mpidcount = 0; 	
	$count = ($showcount) ? ", count({$wpdb->posts}.ID) AS 'posts' ": '';
	$limit = ($limit_posts > 0) ? " LIMIT {$limit_posts}" : '';
	$postexclusions = wpdt_build_exclude_statement($exclude, $wpdb->posts.'.ID');
	$from = " FROM {$wpdb->posts} ";
	$catexclusions = '';
	$catinclusions = '';
	if($include_cats){
		$catinclusions = " AND {$wpdb->term_relationships}.object_id = {$wpdb->posts}.ID
		 AND {$wpdb->term_taxonomy}.taxonomy = 'category' 
		 AND {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id 
		 AND {$wpdb->term_taxonomy}.term_id IN ({$include_cats}) ";
		 $from .= ", {$wpdb->term_relationships}, {$wpdb->term_taxonomy} ";
	} else if($exclude_cats){
		$catexclusions = " AND {$wpdb->term_relationships}.object_id = {$wpdb->posts}.ID
		 AND {$wpdb->term_taxonomy}.taxonomy = 'category' 
		 AND {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id 
		 AND {$wpdb->term_taxonomy}.term_id NOT IN ({$exclude_cats}) ";
		 $from .= ", {$wpdb->term_relationships}, {$wpdb->term_taxonomy} ";
	}	
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- SQL fragments contain only trusted wpdb tables, fixed clauses and integer category/exclusion lists.
	$query = $wpdb->prepare("SELECT YEAR({$wpdb->posts}.post_date) AS 'year', MONTH({$wpdb->posts}.post_date) AS 'month' {$count}
		 $from  
		 WHERE {$wpdb->posts}.post_type = %s AND {$wpdb->posts}.post_status = 'publish'
		 {$postexclusions}		 
		 {$catexclusions}
		 {$catinclusions}		 
		 GROUP BY year, month 
		 ORDER BY {$wpdb->posts}.post_date DESC", $posttype);
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query assembled immediately above from validated fragments.
	$arcresults = $wpdb->get_results($query);		

	if(!$arcresults){
		return array(); //just create empty tree and bail
	}	
	
	$nodelist = array();
	$curyear = -1;
	$query = array();		
	$select_sortby = ($sortby == 'ID' || $sortby == 'post_date' || $sortby == 'post_title') ? '' : ", {$wpdb->posts}.{$sortby} AS '{$sortby}'"; 
	
	foreach($arcresults as $arcresult){		
		if($isyearly){			
			if($arcresult->year != $curyear){ //prepare the year as a parent node, counting it's children etc.
				$postcount = 0;					
				if($showcount){ //avoid this loop if not needed!					
					foreach($arcresults as $temp){ if($temp->year == $arcresult->year){$postcount +=  $temp->posts; } }
				}
				$nodelist[$idcount] = array( 
					'id' => -$idcount, 
					'pid' => 0,						 
					'url' => get_year_link($arcresult->year), 
					'name' => ($showcount) ? $arcresult->year ." ($postcount)" : $arcresult->year,
					'title' => ''									
				);					
				$mpidcount = -$idcount;
				$idcount++;
				$curyear = $arcresult->year;
			}			
		}		
		$nodelist[$idcount] = array( 
			'id' => -$idcount, 
			'pid' => $mpidcount,				 
			'url' => get_month_link($arcresult->year, $arcresult->month), 
			/* translators: 1: month name, 2: four-digit year. */
			'name' => sprintf(__('%1$s %2$d', 'wp-dtree-30'), $wp_locale->get_month($arcresult->month), $arcresult->year),
			'title' => ''
		);		
		if($showcount){
			$nodelist[$idcount]['name'] .= "&nbsp;({$arcresult->posts})";
		}		
		$pidcount = -$idcount;//by keeping parent ID's < 0, we're effeciently avoiding ID-trampling between posts and our generated year/month ID's
		$idcount++;
		if(!$listposts){
			continue; //nothing more to do, get back to the top
		}
		$startmonth = $arcresult->year."-".zeroise($arcresult->month, 2)."-01 00:00:00";
		$endmonth = wpdt_add_month($startmonth, 1);		
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Interpolated fragments use trusted tables, allowlisted columns and integer IDs/limits/exclusions.
		$query[] = $wpdb->prepare("(SELECT {$wpdb->posts}.ID AS 'ID', {$wpdb->posts}.post_date AS 'post_date', {$wpdb->posts}.post_title AS 'post_title', {$pidcount} AS 'pID' {$select_sortby}
			 {$from}
			 WHERE {$wpdb->posts}.post_type = %s AND {$wpdb->posts}.post_status = 'publish'
				AND {$wpdb->posts}.post_date >= %s
				AND {$wpdb->posts}.post_date < %s
			 {$postexclusions}	
			 {$catexclusions}
			 {$catinclusions}			 
			 $limit)", $posttype, $startmonth, $endmonth);
	}
	unset($arcresults);	
	if($listposts && count($query)){
		$query = (count($query) > 1) ? implode(' UNION ALL ', $query)." ORDER BY {$sortby} {$sort_order}" : $query[0]." ORDER BY {$sortby} {$sort_order}";		
		//_log($query);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- UNION of prepared month queries; trailing sort column/direction were allowlisted above.
		if($postresults = $wpdb->get_results($query)){
			foreach($postresults as $postresult){			
				$text = wp_strip_all_tags(apply_filters('the_title', $postresult->post_title));			
				$url = get_permalink($postresult->ID);
				$nodelist[$idcount] = array( 
					'id' => $postresult->ID,
					'pid' => $postresult->pID, 
					'name' => $text,
					'url' => $url, 
					'title' => ''
				);
				$idcount++;
			}		
			unset($postresults);		
		}				
	}	
	return $nodelist;
}
function wpdt_add_month($datestring, $nummonths='1'){
	// Archive dates are local database calendar values, not Unix timestamps.
	$date = new DateTimeImmutable($datestring, new DateTimeZone('UTC'));
	return $date->modify(sprintf('%+d months', (int) $nummonths))->setTime(0, 0)->format('Y-m-d H:i:s');
}
?>
