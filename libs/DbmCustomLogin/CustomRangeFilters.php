<?php
	namespace DbmCustomLogin;

	class CustomRangeFilters {

		function __construct() {
			//echo("\DbmCustomLogin\CustomRangeFilters::__construct<br />");
		}
		
		public function register() {
			//echo("\DbmCustomLogin\CustomRangeFilters::register<br />");
			
			add_filter('wprr/range_encoding/notice', array($this, 'encode_notice'), 10, 2);
			
			add_filter('wprr/global-item/dbmcl/invite', array($this, 'filter_global_dbmcl_invite'), 10, 3);
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
		
		public function filter_global_dbmcl_invite($return_object, $item_name, $data) {
			
			if(!isset($data['token'])) {
				throw(new \Exception("No token"));
			}
			
			$invite_id = dbm_new_query('dbm_data')->set_field('post_status', array('private', 'publish'))->add_type_by_path('signup-invite')->add_meta_query('token', $data['token'])->get_post_id();
			
			if(!$invite_id) {
				throw(new \Exception("No invite for token ".$data['token']));
			}
			
			$return_object['id'] = $invite_id;
			$return_object['token'] = $data['token'];
			
			$post = dbm_get_post($invite_id);
			$status = $post->get_single_relation('invite-status');
			$status_term = get_term_by('id', $status, 'dbm_relation');
			$status_string = $status_term->slug;
			$return_object['status'] = $status_string;
			if($status_string === 'open') {
				$return_object['data'] = $post->get_meta('data');
			}
			
			return $return_object;
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\CustomRangeFilters<br />");
		}
	}
?>
