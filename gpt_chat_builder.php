<?php
/**
 * @package  GPTSupport
 */
/*
Plugin Name: GPT Chat Builder
Description: Build a chat bot with GPT integration within a single click
Version: 1.0.0
Author: Malay Patra
Author URI: malay77patra@gmail.com
License: GPLv2 or later
Text Domain: gpt-chat-builder-plugin
*/


defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

if ( !class_exists( 'GPTSupport' ) ) {

	class GPTSupport
	{

		public $plugin;

		function __construct() {
			$this->plugin = plugin_basename( __FILE__ );
		}

		function register() {

			add_action( 'init', array($this, 'create_page'));
			add_action( 'init', array($this, 'ratings_fansub_create'));
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
			add_action( 'wp_ajax_save_flow', array( $this, 'my_ajax_save_flow_handler') );
			add_action( 'wp_ajax_nopriv_save_flow', array( $this, 'my_ajax_save_flow_handler') );
			add_action( 'wp_ajax_get_flow', array( $this, 'my_ajax_get_flow_handler') );
			add_action( 'wp_ajax_nopriv_get_flow', array( $this, 'my_ajax_get_flow_handler') );
			add_action( 'rest_api_init', array($this, 'register_rest_api') );

		}
		function ratings_fansub_create() {

			global $wpdb;
			$table_name = $wpdb->prefix. "save_flow";
			$ai_collected_user_data = $wpdb->prefix. "ai_collected_user_data";
			global $charset_collate;
			$charset_collate = $wpdb->get_charset_collate();
			global $db_version;
		
			if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") !=  $table_name){   
				$create_sql = "CREATE TABLE " . $table_name . " (
					id INT(11) NOT NULL AUTO_INCREMENT,
					type VARCHAR(100) DEFAULT NULL,
					msg TEXT,
					query VARCHAR(100) DEFAULT NULL,
					prompt TEXT,
					success TEXT,
					elsecase TEXT,
					optionname VARCHAR(100) DEFAULT NULL,
					options LONGTEXT,
					`key` VARCHAR(100) DEFAULT NULL,
					stepname VARCHAR(100) DEFAULT NULL,
					PRIMARY KEY (id))$charset_collate;";
				require_once(ABSPATH . "wp-admin/includes/upgrade.php");
				dbDelta( $create_sql );
			}
			if( $wpdb->get_var("SHOW TABLES LIKE '" . $ai_collected_user_data . "'") !=  $ai_collected_user_data){   
				$create_sql_data = "CREATE TABLE " . $ai_collected_user_data . " (
					id INT(11) NOT NULL AUTO_INCREMENT,
					data LONGTEXT,
					PRIMARY KEY (id))$charset_collate;";
				require_once(ABSPATH . "wp-admin/includes/upgrade.php");
				dbDelta( $create_sql_data );
			}
		
		}
		public function register_rest_api() {
			register_rest_route( 'gpt-support/v1', 'get-reply', array(
                    'methods'  => 'POST',
                    'callback' => array( $this, 'get_rest_reply' ),
					'permission_callback' => '__return_true',
                ),
            );
			register_rest_route( 'gpt-support/v1', 'get-chats', array(
                    'methods'  => 'GET',
                    'callback' => array( $this, 'get_rest_chats' ),
					'permission_callback' => '__return_true',
                ),
            );
			register_rest_route( 'gpt-support/v1', 'save-user-data', array(
                    'methods'  => 'POST',
                    'callback' => array( $this, 'save_rest_user_data' ),
					'permission_callback' => '__return_true',
                ),
            );
		}
		public function save_rest_user_data( $request ) {
			$params = $request->get_params();
			$received_data = wp_unslash( $params['data'] );
			global $wpdb;
			$table_name = $wpdb->prefix. "ai_collected_user_data";
			$wpdb->insert( $table_name, [
				"data" => json_encode($received_data),
			]);

		}
		public function get_rest_chats() {
			global $wpdb;
			$table_name = $wpdb->prefix . 'save_flow';
			$query = "SELECT * FROM $table_name";
			$results = $wpdb->get_results($query, ARRAY_A);

			$processed_results = array();
			foreach ($results as $key => $result) {
				if ($result['type'] != 'openai') {
					if (isset($result['options'])) {
						$result['options'] = json_decode($result['options'], true);
					}
					$processed_results[] = $result;
				}
			}

			return $processed_results;
			die();
		}
		public function get_rest_reply($data) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'save_flow';
			$api_key = $wpdb->get_var("SELECT `key` FROM $table_name WHERE type = 'openai'");

			if (empty($api_key)) {
				return new WP_Error('gpt_error', 'OpenAI key not found in the database.', array('status' => 400));
			}
			try {
				$params = $data->get_params();
				$url = 'https://api.openai.com/v1/chat/completions';
				$prompt = $params['prompt'];
				$request_body = json_encode(array(
					"model" => "gpt-3.5-turbo",
					"messages" => array(array("role" => "user", "content" => $prompt)),
					"temperature" => 0.7
				));
		
				$response = wp_remote_post( $url, array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body' => $request_body,
				));
		
				if ( is_wp_error( $response ) ) {
					return new WP_Error('gpt_error', 'API request error', array('status' => 400));
				}
		
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
		
				if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
					return new WP_Error('gpt_error', 'Invalid API RESPONSE.', array('status' => 400));
				}
		
				$completionText = $data['choices'][0]['message']['content'];
				return json_decode( $completionText );
		
			} catch (Exception $e) {
				error_log( 'Error in get_rest_reply function: ' . $e->getMessage() );
		
				return new WP_Error('gpt_error', 'Error while processing', array('status' => 400));
			}
		}
		public function my_ajax_get_flow_handler() {
			$nonce = $_SERVER['HTTP_X_WP_NONCE'];
			if (wp_verify_nonce( $nonce, 'get_gpt_flow' ) && current_user_can('manage_options') ) {
				if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
					global $wpdb;
					$table_name = $wpdb->prefix. 'save_flow';
					$query = "SELECT * FROM $table_name";
					$results = $wpdb->get_results($query, ARRAY_A);

					foreach ($results as $key => $result) {
						if (isset($result['options'])) {
							$results[$key]['options'] = json_decode($result['options'], true);
						}
					}

					echo json_encode($results, JSON_UNESCAPED_SLASHES);
					die();
				}
			}
		}
		public function my_ajax_save_flow_handler() {
			$nonce = $_SERVER['HTTP_X_WP_NONCE'];
			if (wp_verify_nonce( $nonce, 'save_gpt_flow' ) && current_user_can('manage_options') ) {
				if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$post_data = $_POST;
					$received_data = wp_unslash( $post_data['data'] );
					global $wpdb;
					$table_name = $wpdb->prefix . 'save_flow';
					$wpdb->query( "TRUNCATE TABLE $table_name" );
					foreach ( $received_data as $item ) {
						if ( $item['type'] == 'openai' ) {
							$wpdb->insert( $table_name, [
								"type" => $item['type'],
								"stepname" => "openai",
								"key" => $item['key'],
							]);
						} elseif ( $item['type'] == 'normal' ) {
							$wpdb->insert( $table_name, [
								"stepname" => $item['stepname'],
								"type" => $item['type'],
								"msg" => $item['msg'],
							] );
						} elseif ( $item['type'] =='ai-msg' ) {
							$wpdb->insert( $table_name, [
								"stepname" => $item['stepname'],
								"type" => $item['type'],
								"prompt" => $item['prompt'],
							]);
						} elseif ( $item['type'] =='ai-qry' ) {
							$wpdb->insert( $table_name, [
								"stepname" => $item['stepname'],
								"type" => $item['type'],
								"query" => $item['query'],
								"prompt" => $item['prompt'],
								"success" => $item['success'],
								"elsecase" => $item['elsecase'],
								"key" => $item['key'],
							]);
						} elseif ( $item['type'] =='option' ) {
							$wpdb->insert( $table_name, [
								"stepname" => $item['stepname'],
								"optionname" => $item['optionname'],
								"type" => $item['type'],
								"options" => wp_json_encode( $item['options'], JSON_UNESCAPED_SLASHES ),
							]);
						}
					}
					echo "Data saved succesfully !";
					die();
				}
            }
		}
		

		public function add_admin_pages() {
			add_menu_page( 'GPTSupport', 'GPTSupport', 'manage_options', 'get_gpt_support', array( $this, 'admin_index' ), 'dashicons-networking', 110 );
		}

		public function admin_index() {
		
			require_once plugin_dir_path( __FILE__ ) . 'admin/admin.php';
		}		
		function create_page() {
			$gpt_upload_dir = wp_upload_dir()['basedir'] . '/gpt';
			$gpt_upload_url = wp_upload_dir()['baseurl'] . '/gpt';
			if ( ! file_exists( $gpt_upload_dir ) ) {
				wp_mkdir_p( $gpt_upload_dir );
			} else {
				$bot = $gpt_upload_dir . '/bot.png';
				if ( ! file_exists( $bot ) ) {
					copy( plugin_dir_path( __FILE__ ). 'assets/bot.png', $bot );
				}
				$send_message = $gpt_upload_dir . '/send-message.png';
				if (! file_exists( $send_message ) ) {
                    copy( plugin_dir_path( __FILE__ ). 'assets/send-message.png', $send_message );
                }
			}
			$page_name = 'get-gpt-support';
			$objPage = get_page_by_path($page_name);
		
			if (!$objPage) {
				$file_path = plugin_dir_path(__FILE__) . 'user/chat.html';
				$content = file_get_contents($file_path);
				$ajax_url = get_rest_url( null, 'gpt-support/v1' );
				$content = str_replace('{AJAX_URL}', $ajax_url, $content);
				$content = str_replace('{ADMIN_AJAX_URL}', admin_url('admin-ajax.php'), $content);
				$content = str_replace('{UPLOADS_URL}', $gpt_upload_url, $content);
				kses_remove_filters();
				$page_id = wp_insert_post(
					array(
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_author'    => 1,
						'post_name'      => $page_name,
						'post_status'    => 'publish',
						'post_content'   => $content,
						'post_type'      => 'page',
					)
				);
				kses_init_filters();
			}
		
		}

	}

	$GPTSupport = new GPTSupport();
	$GPTSupport->register();

}
?>