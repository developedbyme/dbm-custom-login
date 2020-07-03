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
	
	function create_api_key($user_id) {
		
		$time_zone = get_option('timezone_string');
		if($time_zone) {
			date_default_timezone_set($time_zone);
		}
		
		$current_date = new \DateTime();
		
		$new_id = dbm_create_data('Api key - ' . $user_id . ' - ' . $current_date->format('Y-m-d H:i:s'), 'api-key', 'admin-grouping/api-keys');
		
		$key = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		$password = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		
		$salt = 'S<KUn@DHY/JY.M9X)zh0<dJ-H~89j}Ge>-H?;r@Pr:k=~_R^GX?(}Gdqji[+~i_+';
		
		$encoded_password = md5($user_id.$key.$password.$salt);
		
		update_post_meta($new_id, 'userId', $user_id);
		update_post_meta($new_id, 'key', $key);
		update_post_meta($new_id, 'password', $encoded_password);
		
		wp_update_post(array(
			'ID' => $new_id,
			'post_status' => 'private'
		));
		
		return array('id' => $new_id, 'key' => $key, 'password' => $password);
	}
	
	function dbm_custom_login_catch_token($logged_in_cookie, $expire, $expiration, $user_id, $type, $token) {
		global $new_token;
		
		$new_token = $token;
	}
	
	function dbm_custom_login_perform_login($user, $remember = false) {
		
		add_action('set_logged_in_cookie', 'dbm_custom_login_catch_token', 10, 6);
		
		wp_clear_auth_cookie();
		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID, $remember);
		
		//MENOTE: this is modified from wp_create_nonce('wp_rest'); as the session is not in the cookie variable
		global $new_token;
		
		$action = 'wp_rest';
		$uid = $user->ID;
		$token = $new_token;
		$i = wp_nonce_tick();
		
		$nonce = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
		
		return array(
			'restNonce' => $nonce,
			'restNonceGeneratedAt' => time()
		);
	}
	
	function dbm_custom_login_create_invite($for_id, $data = null) {
		
		$new_id = dbm_create_data('Signup invite for '.$for_id, 'signup-invite', 'signup-invites');
		$post = dbm_get_post($new_id);
		$post->add_relation_by_name('invite-status/open');
		
		$token = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		$post->update_meta('token', $token);
		
		$post->add_outgoing_relation_by_name($for_id, 'invite-for');
		
		$post->update_meta('data', $data);
		
		$post->change_status('private');
		
		return array('inviteId' => $new_id, 'token' => $token);
	}
?>