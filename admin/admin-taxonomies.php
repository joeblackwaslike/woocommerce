<?php
/**
 * Functions used for taxonomies in admin 
 *
 * These functions control admin interface bits like category ordering.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
 
/**
 * Categories ordering
 */


/**
 * Reorder on category insertion
 * 
 * @param int $term_id
 */
add_action("create_product_cat", 'woocommerce_create_product_cat');

function woocommerce_create_product_cat ($term_id) {
	
	$next_id = null;
	
	$term = get_term($term_id, 'product_cat');
	
	// gets the sibling terms
	$siblings = get_terms('product_cat', "parent={$term->parent}&menu_order=ASC&hide_empty=0");
	
	foreach ($siblings as $sibling) {
		if( $sibling->term_id == $term_id ) continue;
		$next_id =  $sibling->term_id; // first sibling term of the hierachy level
		break;
	}

	// reorder
	woocommerce_order_categories ( $term, $next_id );
	
}


/**
 * Delete terms metas on deletion
 * 
 * @param int $term_id
 */
add_action("delete_product_cat", 'woocommerce_delete_product_cat');

function woocommerce_delete_product_cat($term_id) {
	
	$term_id = (int) $term_id;
	
	if(!$term_id) return;
	
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->woocommerce_termmeta} WHERE `woocommerce_term_id` = " . $term_id);
	
}


/**
 * Move a category before the a	given element of its hierarchy level
 *
 * @param object $the_term
 * @param int $next_id the id of the next slibling element in save hierachy level
 * @param int $index
 * @param int $terms
 */
function woocommerce_order_categories ( $the_term, $next_id, $index=0, $terms=null ) {
	
	if( ! $terms ) $terms = get_terms('product_cat', 'menu_order=ASC&hide_empty=0&parent=0');
	if( empty( $terms ) ) return $index;
	
	$id	= $the_term->term_id;
	
	$term_in_level = false; // flag: is our term to order in this level of terms
	
	foreach ($terms as $term) {
		
		if( $term->term_id == $id ) { // our term to order, we skip
			$term_in_level = true;
			continue; // our term to order, we skip
		}
		// the nextid of our term to order, lets move our term here
		if(null !== $next_id && $term->term_id == $next_id) { 
			$index++;
			$index = woocommerce_set_category_order($id, $index, true);
		}		
		
		// set order
		$index++;
		$index = woocommerce_set_category_order($term->term_id, $index);
		
		// if that term has children we walk through them
		$children = get_terms('product_cat', "parent={$term->term_id}&menu_order=ASC&hide_empty=0");
		if( !empty($children) ) {
			$index = woocommerce_order_categories ( $the_term, $next_id, $index, $children );	
		}
	}
	
	// no nextid meaning our term is in last position
	if( $term_in_level && null === $next_id )
		$index = woocommerce_set_category_order($id, $index+1, true);
	
	return $index;
	
}

/**
 * Set the sort order of a category
 * 
 * @param int $term_id
 * @param int $index
 * @param bool $recursive
 */
function woocommerce_set_category_order ($term_id, $index, $recursive=false) {
	global $wpdb;
	
	$term_id 	= (int) $term_id;
	$index 		= (int) $index;
	
	update_metadata('woocommerce_term', $term_id, 'order', $index);
	
	if( ! $recursive ) return $index;
	
	$children = get_terms('product_cat', "parent=$term_id&menu_order=ASC&hide_empty=0");

	foreach ( $children as $term ) {
		$index ++;
		$index = woocommerce_set_category_order ($term->term_id, $index, true);		
	}
	
	return $index;

}