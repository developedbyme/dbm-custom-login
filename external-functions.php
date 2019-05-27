<?php
	function dbm_custom_login_create_magic_link($user_id, $landing_page_url) {
		
		$salt = 'v/,+Kfm(j({wb|+?[2OD>0=@ksOny5%4tl|DM#]B~dj-mlU2y.F!GO?%@gHz]$uq';
		$validation_salt = 'EC3>b$sA9,Nw,|}EG{?]/yj-!3573{9WS-/%kqQop0-/T|$#h&V|UIW9xifmoqA%';
		
		$time = time();
		$code = uniqid('', true);
		$validation = uniqid('', true);
		
		$parent_id = dbm_new_query('dbm_additional')->add_type_by_path('admin-grouping/magic-links')->get_post_id();
		
		$args = array(
			'post_type' => 'dbm_additional',
			'post_title' => 'Magic link '.$user_id.' '.date('Y-m-d H:i:s', $time),
			'post_parent' => $parent_id
		);
		
		$new_id = wp_insert_post($args);
		
		dbm_add_post_type($new_id, 'magic-link');
		dbm_add_post_relation($new_id, 'magic-link-status/unused');
		update_post_meta($new_id, 'generated_at', $time);
		update_post_meta($new_id, 'user_id', $user_id);
		update_post_meta($new_id, 'landing_page_url', $landing_page_url);
		
		$link_key = md5($new_id.$user_id.$time.$code.$salt);
		update_post_meta($new_id, 'link_key', $link_key);
		update_post_meta($new_id, 'link_validation', md5($link_key.$validation.$validation_salt));
		
		wp_update_post(array(
			'ID' => $new_id,
			'post_status' => 'publish'
		));
		
		$return_link = get_permalink($new_id);
		$return_link = add_query_arg('code', $code, $return_link);
		$return_link = add_query_arg('validation', $validation, $return_link);
		
		update_post_meta($new_id, 'initial_link_validation_check', dbm_custom_login_validate_link($new_id, $code, $validation));
		
		return $return_link;
	}
	
	function dbm_custom_login_validate_link($link_id, $code, $validation) {
		$salt = 'v/,+Kfm(j({wb|+?[2OD>0=@ksOny5%4tl|DM#]B~dj-mlU2y.F!GO?%@gHz]$uq';
		$validation_salt = 'EC3>b$sA9,Nw,|}EG{?]/yj-!3573{9WS-/%kqQop0-/T|$#h&V|UIW9xifmoqA%';
		
		$time = (int)get_post_meta($link_id, 'generated_at', true);
		$user_id = (int)get_post_meta($link_id, 'user_id', true);
		
		$link_key = md5($link_id.$user_id.$time.$code.$salt);
		$link_validation = md5($link_key.$validation.$validation_salt);
		
		$stored_link_key = get_post_meta($link_id, 'link_key', true);
		$stored_link_validation = get_post_meta($link_id, 'link_validation', true);
		
		return ($link_key === $stored_link_key && $link_validation === $stored_link_validation);
	}
	
	function dbm_custom_login_try_to_apply_magic_link($link_id, $code, $validation) {
		
		$current_time = time();
		$time = (int)get_post_meta($link_id, 'generated_at', true);
		
		add_post_meta($link_id, 'try_to_apply_at', $time);
		
		$landing_page_url = get_post_meta($link_id, 'landing_page_url', true);
		
		if($current_time >= ($time+48*60*60*1000)) {
			$sign_in_post_id = dbm_new_query('page')->add_relation_by_path('global-pages/sign-in')->get_post_id();
			
			$sign_in_link = get_permalink($sign_in_post_id);
			$sign_in_link = add_query_arg('notice', 'expired_key', $sign_in_link);
			$sign_in_link = add_query_arg('redirect_to', $landing_page_url, $sign_in_link);
			
			wp_safe_redirect($sign_in_link);
			exit;
		}
		
		$is_valid = dbm_custom_login_validate_link($link_id, $code, $validation);
		if($is_valid) {
			add_post_meta($link_id, 'applied_at', $time);
			
			$user_id = (int)get_post_meta($link_id, 'user_id', true);
			
			dbm_set_single_relation_by_name($link_id, 'magic-link-status', 'used');
			
			wp_clear_auth_cookie();
			wp_set_current_user($user_id);
			wp_set_auth_cookie($user_id);
			
			wp_safe_redirect($landing_page_url);
			exit;
		}
		else {
			add_post_meta($link_id, 'failed_at', $time);
			
			$sign_in_post_id = dbm_new_query('page')->add_relation_by_path('global-pages/sign-in')->get_post_id();
			
			$sign_in_link = get_permalink($sign_in_post_id);
			$sign_in_link = add_query_arg('notice', 'invalid_key', $sign_in_link);
			$sign_in_link = add_query_arg('redirect_to', $landing_page_url, $sign_in_link);
			
			wp_safe_redirect($sign_in_link);
			exit;
		}
	}
?>