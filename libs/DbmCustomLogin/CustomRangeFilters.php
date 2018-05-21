<?php
	namespace DbmCustomLogin;

	class CustomRangeFilters {
		
		protected $_photo_credits_parent_term_id = -1;

		function __construct() {
			//echo("\DbmCustomLogin\CustomRangeFilters::__construct<br />");
		}
		
		public function query_events($query_args, $data) {
			//echo("\DbmCustomLogin\CustomRangeFilters::query_auctions<br />");
			
			$query_args['post_type'] = 'event';
			
			$startDate = $data['date'];
			$endDate = $data['date'];
			
			if(isset($data['startDate'])) {
				$startDate = $data['startDate'];
			}
			if(isset($data['endDate'])) {
				$endDate = $data['endDate'];
			}
			
			$query_args['meta_query'] = array(
				array(
					'key' => 'dates',
					'value' => array($startDate, $endDate),
					'compare' => 'BETWEEN',
					'type' => 'DATE'
				)
			);
			
			return $query_args;
		}
		
		
		public function encode_events($return_object, $post_id) {
			//echo("\DbmCustomLogin\CustomRangeFilters::encode_events<br />");
			
			$return_object['events'] = json_decode(get_post_meta($post_id, 'events', true));
			$return_object['dates'] = get_post_meta($post_id, 'dates', false);
				
			return $return_object;
		}
		
		public function query_locations($query_args, $data) {
			//echo("\DbmCustomLogin\CustomRangeFilters::query_locations<br />");
			
			$query_args['post_type'] = array('post', 'page');
			
			$tax_query = array(
				'taxonomy' => 'dbm_type',
				'field' => 'slug',
				'terms' => 'location'
			);

			$query_args['tax_query'] = array();
			$query_args['tax_query'][] = $tax_query;
			
			return $query_args;
		}
		
		public function encode_locations($return_object, $post_id) {
			//echo("\DbmCustomLogin\CustomRangeFilters::encode_locations<br />");
			
			$locations = get_post_meta($post_id, 'lba_locations', true);
			if(!empty($locations)) {
				$return_object['locations'] = $locations;
			}
			else {
				$return_object['locations'] = array();
			}
			
			$parent_term = get_term_by('slug', 'map-grouping', 'dbm_relation');
			
			$map_group_ids = array();
			
			$current_terms = wp_get_post_terms($post_id, 'dbm_relation');
			foreach($current_terms as $current_term) {
				if(term_is_ancestor_of($parent_term, $current_term, 'dbm_relation')) {
					$map_group_ids[] = $current_term->term_id;
				}
			}
			
			$return_object['mapGroups'] = $map_group_ids;
				
			return $return_object;
		}
		
		public function encode_list_description($return_object, $post_id) {
			//echo("\DbmCustomLogin\CustomRangeFilters::encode_list_description<br />");
			
			$return_object['listDescription'] = get_post_meta($post_id, 'lba_list_description', true);
				
			return $return_object;
		}
		
		public function query_ticket_variations($query_args, $data) {
			//echo("\DbmCustomLogin\CustomRangeFilters::query_ticket_variations<br />");
			
			$query_args['post_type'] = array('dbm_additional');
			
			$tax_query = array(
				'taxonomy' => 'dbm_type',
				'field' => 'slug',
				'terms' => 'ticket-type-variation'
			);
			
			$ticket_term_id = get_post_meta($data['id'], 'dbm_relation_term_ticket-type', true);
			
			$ticket_query = array(
				'taxonomy' => 'dbm_relation',
				'field' => 'id',
				'terms' => $ticket_term_id
			);

			$query_args['tax_query'] = array();
			$query_args['tax_query'][] = 'AND';
			$query_args['tax_query'][] = $tax_query;
			$query_args['tax_query'][] = $ticket_query;
			
			return $query_args;
		}
		
		public function encode_ticket_variations($return_object, $post_id) {
			//echo("\DbmCustomLogin\CustomRangeFilters::encode_ticket_variations<br />");
			
			$post = get_post($post_id);
			
			$return_object['content'] = $post->post_content;
			$return_object['price'] = floatval(get_field('lba_ticket_price', $post_id));
			$return_object['totalAmountLeft'] = floatval(get_field('lba_total_amount_of_tickets', $post_id))-floatval(get_post_meta($post_id, 'lba_amount_of_sold_tickets', true));
				
			return $return_object;
		}
		
		public function add_photo_credits($image_data, $image_post_id) {
			//echo("\DbmCustomLogin\CustomRangeFilters::add_photo_credits<br />");
			//var_dump($image_data);
			
			if($this->_photo_credits_parent_term_id === -1) {
				$parent_term = get_term_by('slug', 'photo-credits', 'dbm_relation');
				$this->_photo_credits_parent_term_id = $parent_term->term_id;
			}
			
			$photo_credits = array();
			$terms = wp_get_post_terms($image_post_id, 'dbm_relation');
			
			foreach($terms as $term) {
				if($term->parent === $this->_photo_credits_parent_term_id) {
					$photo_credits[] = $term->term_id;
				}
			}
			
			if(!isset($image_data['relations'])) {
				$image_data['relations'] = array();
			}
			$image_data['relations']['photoCredits'] = $photo_credits;
			
			return $image_data;
		}
		
		public function filter_out_meta_emails($meta_data, $post_id) {
			if(isset($meta_data['lba_question_email'])) {
				unset($meta_data['lba_question_email']);
			}
			
			return $meta_data;
		}
		
		public static function test_import() {
			echo("Imported \DbmCustomLogin\CustomRangeFilters<br />");
		}
	}
?>
