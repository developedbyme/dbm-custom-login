<?php
	namespace DbmCustomLogin\OddCore\Utils;
	
	// \DbmCustomLogin\OddCore\Utils\TaxonomyFunctions
	class TaxonomyFunctions {
		
		public static function get_term_by_slugs($slugs, $taxonomy) {
			
			$current_id = 0;
			
			foreach($slugs as $slug) {
				$args = array(
					'taxonomy' => $taxonomy,
					'fields' => 'ids',
					'slug' => $slug,
					'parent' => $current_id,
					'hide_empty' => false
				);
					
				$terms = get_terms($args);
				
				if(empty($terms)) {
					return null;
				}
				$current_id = $terms[0];
			}
			
			return get_term_by('id', $current_id, $taxonomy);
		}
		
		public static function add_term($name, $path_slugs, $taxonomy) {
			
			$current_term = self::get_term_by_slugs($path_slugs, $taxonomy);
			if($current_term) {
				return $current_term;
			}
			
			$object_slug = array_pop($path_slugs);
			$parent_term_id = 0;
			if(!empty($path_slugs)) {
				$parent_term = self::get_term_by_slugs($path_slugs, $taxonomy);
				if($parent_term) {
					$parent_term_id = $parent_term->term_id;
				}
				else {
					//METODO: error message
					return null;
				}
			}
			
			$args = array(
				'slug' => $object_slug,
				'parent' => $parent_term_id
			);
			
			$new_term = wp_insert_term($name, $taxonomy, $args);
		}
		
		public static function get_all_children_of_term($parent_id, $taxonomy) {
			$args = array(
				'taxonomy' => $taxonomy,
				'parent' => $parent_id,
				'hide_empty' => false
			);
				
			return get_terms($args);
		}
		
		public static function test_import() {
			echo("Imported \OddCore\Utils\TaxonomyFunctions<br />");
		}
	}
?>