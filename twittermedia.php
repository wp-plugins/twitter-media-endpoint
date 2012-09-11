<?php
/*
Plugin Name: Twitter Media Endpoint
Plugin URI: http://sterlinganderson.net/twitter-media-endpoint
Description: Allow your WP install to be a Twitter Media Endpoint
Version: 0.8
Author: Sterling Anderson
Author URI: http://sterlinganderson.net
License: MIT

Copyright (c) 2011 Sterling Anderson

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

session_start();

require_once(dirname(__FILE__) . "/includes/tmhOAuth/tmhOAuth.php");
require_once(dirname(__FILE__) . "/includes/tmhOAuth/tmhUtilities.php");
require_once(dirname(__FILE__) . "/includes/TwitterOAuthEcho.php");

define('TWITTER_CONSUMER_KEY', get_option('twitter_media_consumer_key'));  
define('TWITTER_CONSUMER_SECRET', get_option('twitter_media_consumer_secret'));

function twitter_media_init(){

	global $wpdb;
	//global $wp_locale;
	
	$uri = ltrim($_SERVER['REQUEST_URI'], "/");
	$uri = rtrim($uri, "/");
	if ($uri == get_option('twitter_media_url_endpoint')) {
		//Authenticate the user to Twitter using OAuth Echo
		$oauthecho = new TwitterOAuthEcho();
		$oauthecho->userAgent = $_POST['source'];
		$oauthecho->setCredentialsFromRequestHeaders();
		if ($oauthecho->verify()) {
			// Verification was a success, we should be able to access the user's Twitter info from the responseText.
			$userInfo = json_decode($oauthecho->responseText, true);
			//$v = var_export($userInfo, true);
			//error_log("\n\n************userInfo**************\n\n" . $v, 3, "/var/log/php_errors.log");
			$user_id = isset($userInfo['id']) ? $userInfo['id'] : null;
			if ($user_id){
				$sql = "SELECT usermeta1.user_id wp_user_id, usermeta2.meta_value user_token, usermeta3.meta_value user_secret
						FROM $wpdb->usermeta AS usermeta1
						LEFT JOIN $wpdb->usermeta AS usermeta2
						ON usermeta1.user_id = usermeta2.user_id
						LEFT JOIN $wpdb->usermeta AS usermeta3
						ON usermeta1.user_id = usermeta3.user_id
						WHERE usermeta1.meta_key = 'twitter_media_user_id'
						AND usermeta1.meta_value = '$user_id'
						AND usermeta2.meta_key = 'twitter_media_user_token'
						AND usermeta3.meta_key = 'twitter_media_user_secret'
						LIMIT 1;";
				$result = $wpdb->get_row($sql);
				$wp_user_id = $result->wp_user_id;
				$user_token = $result->user_token;
				$user_secret = $result->user_secret;
				
				wp_set_current_user( $wp_user_id );
				$extension = end(explode(".", $_FILES['media']['name']));
				$date_format = get_option('date_format') . ' ' . get_option('time_format');
				$datetime = date($date_format, current_time("timestamp"));
				$file_name = 'twitter-' . $datetime . "." . $extension;
				$upload = wp_upload_bits($file_name, null, file_get_contents($_FILES["media"]["tmp_name"]));		
					
				if ($upload['error'] == false) {
					$wp_filetype = wp_check_filetype(basename($upload['file']), null );
					$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => 'Twitter - ' . $datetime,
							'post_content' => $_POST['message'],
							'post_status' => 'inherit');
					$attach_id = wp_insert_attachment( $attachment, $upload['file'], get_option('twitter_media_gallery_page'));
					// you must first include the image.php file
					// for the function wp_generate_attachment_metadata() to work
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					if (get_option('twitter_media_send_shortlink') == "true") {
						$url = wp_get_shortlink($attach_id);
					} else {
						$url = get_permalink($attach_id);
					}
					echo "<mediaurl>" . $url . "</mediaurl>";
					exit;
				}
			}
		}
	}
	
}

function twitter_media_admin_init() {
	// Add the section to reading settings so we can add our
	 // fields to it
	 	add_settings_section('twitter_media_section',
			'Twitter Media Endpoint Options',
			'twitter_media_options_section_callback',
			'media');

	 	// Add the field with the names and function to use for our new
	 	// settings, put it in our new section
	 	add_settings_field('twitter_media_consumer_key',
			'Consumer Key',
			'twitter_media_consumer_key_callback',
			'media',
			'twitter_media_section');
			
		add_settings_field('twitter_media_consumer_secret',
			'Consumer Secret',
			'twitter_media_consumer_secret_callback',
			'media',
			'twitter_media_section');
			
		add_settings_field('twitter_media_url_endpoint',
			'URL Endpoint',
			'twitter_media_url_endpoint_callback',
			'media',
			'twitter_media_section');
		
		add_settings_field('twitter_media_gallery_page',
			'Attach to Page',
			'twitter_media_gallery_page_callback',
			'media',
			'twitter_media_section');
				
		add_settings_field('twitter_media_send_shortlink',
			'Send Shortlink',
			'twitter_media_send_shortlink_callback',
			'media',
			'twitter_media_section');

	 	// Register our setting so that $_POST handling is done for us and
	 	// our callback function just has to echo the <input>
	 	register_setting('media','twitter_media_consumer_key');
		register_setting('media','twitter_media_consumer_secret');
		register_setting('media','twitter_media_url_endpoint');
		register_setting('media','twitter_media_gallery_page');
		register_setting('media','twitter_media_send_shortlink');
}

add_action('init', 'twitter_media_init');

add_action('admin_init', 'twitter_media_admin_init');

add_action('show_user_profile', 'twitter_media_profile');
add_action('edit_user_profile', 'twitter_media_profile');

// ------------------------------------------------------------------
// Settings section callback function
// ------------------------------------------------------------------
//
// This function is needed if we added a new section. This function 
// will be run at the start of our section
//

function twitter_media_options_section_callback() {
	echo '<p>You need to register a new application with Twitter here: <a href="https://dev.twitter.com">https://dev.twitter.com</a><br />Everything you enter for your application can remain at the default, except enter your website URL for the "Application Website", and "Callback URL" fields. After you do that enter the Consumer Key and Consumer Secret for that application below.</p>';
}

function twitter_media_consumer_key_callback() {
	echo '<input name="twitter_media_consumer_key" type="text" value="' . get_option('twitter_media_consumer_key') . '" /> The Consumer Key from your Twitter Application';
}

function twitter_media_consumer_secret_callback() {
	echo '<input name="twitter_media_consumer_secret" type="text" value="' . get_option('twitter_media_consumer_secret') . '" /> The Consumer Secret from your Twitter Application';
}

function twitter_media_url_endpoint_callback() {
	echo '<input name="twitter_media_url_endpoint" type="text" value="' . get_option('twitter_media_url_endpoint') . '" /> What URL do you want your Twitter Media Endpoint to have? (http://example.com/TWITTERMEDIA)<br />This is the custom upload service URL you will use in your Twitter client.';
}

function twitter_media_gallery_page_callback() {
	wp_dropdown_pages(array('depth'				=> 0,
							'child_of'			=> 0,
							'selected'			=> get_option('twitter_media_gallery_page'),
							'echo'				=> 1,
							'show_option_none' 	=> ' ',
							'name'				=> 'twitter_media_gallery_page'));
	echo 'Do you want your Twitter media attached to a specific page on your site? This is nice if you want a gallery of all your files';
}

function twitter_media_send_shortlink_callback() {
	$checked = get_option('twitter_media_send_shortlink') == "true" ? ' checked ' : '';
	echo '<input name="twitter_media_send_shortlink" type="checkbox" value="true"' . $checked . '/> Send the shortened link to the media file back to your Twitter client, or the full URL?';
}

function twitter_media_profile() {
	
	//Get the current logged in WP user
	global $current_user;
	$current_user = wp_get_current_user();
	
	$tmhOAuth = new tmhOAuth(array('consumer_key'    => TWITTER_CONSUMER_KEY,
								   'consumer_secret' => TWITTER_CONSUMER_SECRET,));
	
	echo '<h3>Twitter authorization for Twitter Media Endpoint plugin</h3>';
	
	//Get the meta data from the WP DB
	$user_token = 	get_user_meta( $current_user->ID, 'twitter_media_user_token', true);
	$user_secret = 	get_user_meta( $current_user->ID, 'twitter_media_user_secret', true);
	$user_id = 		get_user_meta( $current_user->ID, 'twitter_media_user_id', true);
 
	// already got some credentials stored?
	if ( !isset($_REQUEST['oauth_verifier']) && $user_token != '' && $user_secret != '' && $user_id != '' ) {
		// Verify with Twitter that the user has authorized app
		twitter_media_verify($user_token, $user_secret);
	// Returning from Twitter after user has authorized us. Store user's token and secret.
	} elseif (isset($_REQUEST['oauth_verifier'])) {
		$tmhOAuth = new tmhOAuth(array(
								'consumer_key'    => TWITTER_CONSUMER_KEY,
								'consumer_secret' => TWITTER_CONSUMER_SECRET,
							));
		$tmhOAuth->config['user_token']  = $_SESSION['oauth']['oauth_token'];
		$tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
		$code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/access_token', ''), array('oauth_verifier' => $_REQUEST['oauth_verifier']));
		if ($code == 200) {
			$_SESSION['access_token'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
			unset($_SESSION['oauth']);
			/* The user has been verified and the access tokens can be saved for future use */
			update_user_meta( $current_user->ID, 'twitter_media_user_token', $_SESSION['access_token']['oauth_token']);
			update_user_meta( $current_user->ID, 'twitter_media_user_secret', $_SESSION['access_token']['oauth_token_secret']);
			update_user_meta( $current_user->ID, 'twitter_media_user_id', $_SESSION['access_token']['user_id']);
			twitter_media_verify($_SESSION['access_token']['oauth_token'], $_SESSION['access_token']['oauth_token_secret']);
		} else {
			outputError($tmhOAuth);
		}
		session_end();
	// App is not authorized
	} else {
		twitter_media_authorize();
	}
}

function twitter_media_authorize() {
	$tmhOAuth = new tmhOAuth(array('consumer_key' => TWITTER_CONSUMER_KEY, 'consumer_secret' => TWITTER_CONSUMER_SECRET));
							
	$params = array('oauth_callback' => tmhUtilities::php_self());
		
	$code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/request_token', ''), $params);
		
	if ($code == 200) {
		$_SESSION['oauth'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
		$authurl = $tmhOAuth->url("oauth/authorize", '') .  "?oauth_token={$_SESSION['oauth']['oauth_token']}";
		echo '<a href="' . $authurl . '"><img style="vertical-align:middle" src="' . plugins_url( 'includes/sign-in-with-twitter-d.png' , __FILE__ ) . '" ></a>&nbsp;Allow Wordpress to access Twitter</p>';		
	} else {
		outputError($tmhOAuth);
	}
}

function twitter_media_verify($user_token, $user_secret){
	$tmhOAuth = new tmhOAuth(array('consumer_key'    => TWITTER_CONSUMER_KEY,
								   'consumer_secret' => TWITTER_CONSUMER_SECRET,
								   'user_token'  => $user_token,
								   'user_secret' => $user_secret));
	$code = $tmhOAuth->request('GET', $tmhOAuth->url('1/account/verify_credentials'));
	if ($code == 200) {
		$resp = json_decode($tmhOAuth->response['response']);
		echo '<p><a href="http://twitter.com/' . $resp->screen_name . '"><img src="' . $resp->profile_image_url . '" style="float:left; padding-right:5px; vertical-align:middle" /></a>Twitter account '
			. '<a href="http://twitter.com/' . $resp->screen_name . '">@' . $resp->screen_name . '</a><br />'
			. ' is successfully authorized.</p>';
		echo '<p style="clear=both">&nbsp;</p><p>Your Twitter media endpoint API URL is: <strong>' . site_url() . '/' . get_option('twitter_media_url_endpoint') . '</strong></p>';
	} elseif ($code == 401) {
		twitter_media_authorize();
	} else {
		outputError($tmhOAuth);
	}
}

function outputError($tmhOAuth) {
	echo 'Error: ' . $tmhOAuth->response['response'] . PHP_EOL;
	tmhUtilities::pr($tmhOAuth);
}



?>
