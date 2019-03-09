<?php
	namespace DbmCustomLogin;

	class CustomRangeFilters {

		function __construct() {
			//echo("\DbmCustomLogin\CustomRangeFilters::__construct<br />");
		}
		
		
		public function encode_notice($return_object, $post_id) {
			//echo("\DbmCustomLogin\CustomRangeFilters::encode_notice<br />");
			
			$type_ids = dbm_get_post_relation($post_id, 'notice-types');
			if(!empty($type_ids)) {
				$return_object['type'] = wprr_encode_term(get_term_by('id', $type_ids[0], 'dbm_relation'));
			}
			else {
				$return_object['type'] = null;
			}
			
			$return_object['content'] = apply_filters('the_content', get_post_field('post_content', $post_id));
				
			return $return_object;
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\CustomRangeFilters<br />");
		}
	}
?>
