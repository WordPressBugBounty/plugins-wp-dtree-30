<?php
defined('ABSPATH') || exit;
function wpdt_get_menu_nodelist($args){	
	extract($args, EXTR_SKIP);		
	$idcount = 1;
	$nodelist = array();	
	$menu_items = wp_get_nav_menu_items($menuslug);
	if(!$menu_items){
		return $nodelist;
	}
	$menu_ids = array_fill_keys(array_column($menu_items, 'ID'), true);
	foreach((array)$menu_items as $key => $menu_item){
		$pid = $menu_item->menu_item_parent;
		if(!isset($menu_ids[(int)$pid])){$pid = 0;} //connect orphans to root.
		$nodelist[$idcount] = array(
			'id' => $menu_item->ID, 
			'pid' => $pid, 
			'url' => esc_url($menu_item->url), 
			'name' => wp_strip_all_tags(apply_filters('the_title', $menu_item->title)), 
			'title' => wp_strip_all_tags($menu_item->description)
		);
		$idcount++;		
	}	
	return $nodelist;	
}
/*if(($locations = get_nav_menu_locations()) && isset($locations[$menu_name])){
		$menu = wp_get_nav_menu_object($locations[$menu_name]);*/
?>
