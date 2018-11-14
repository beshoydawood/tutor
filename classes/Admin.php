<?php
namespace TUTOR;

/**
 * Class Admin
 * @package TUTOR
 *
 * @since v.1.0.0
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

class Admin{
	public function __construct() {
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('admin_init', array($this, 'filter_posts_for_teachers'));

		add_action('load-post.php', array($this, 'check_if_current_users_post') );

		add_action('admin_action_uninstall_tutor_and_erase', array($this, 'erase_tutor_data'));
		add_filter('plugin_action_links_' . plugin_basename(TUTOR_FILE), array( $this, 'plugin_action_links' ) );
	}

	public function register_menu(){
		$unanswered_questions = tutor_utils()->unanswered_question_count();
		$unanswered_bubble = '';
		if ($unanswered_questions){
			$unanswered_bubble = '<span class="update-plugins count-'.$unanswered_questions.'"><span class="plugin-count">'.$unanswered_questions.'</span></span>';
		}

		$course_post_type = tutor()->course_post_type;

		add_menu_page(__('Tutor', 'tutor'), __('Tutor', 'tutor'), 'manage_tutor_teacher', 'tutor', null, 'dashicons-welcome-learn-more', 2);

		add_submenu_page('tutor', __('Categories', 'tutor'), __('Categories', 'tutor'), 'manage_tutor_teacher', 'edit-tags.php?taxonomy=course-category&post_type='.$course_post_type, null );
		add_submenu_page('tutor', __('Tags', 'tutor'), __('Tags', 'tutor'), 'manage_tutor_teacher', 'edit-tags.php?taxonomy=course-tag&post_type='.$course_post_type, null );

		add_submenu_page('tutor', __('Students', 'tutor'), __('Students', 'tutor'), 'manage_tutor', 'tutor-students', array($this, 'tutor_students') );

		add_submenu_page('tutor', __('Teachers', 'tutor'), __('Teachers', 'tutor'), 'manage_tutor', 'tutor-teachers', array($this, 'tutor_teachers') );

		add_submenu_page('tutor', __('Q & A', 'tutor'), __('Q & A '.$unanswered_bubble, 'tutor'), 'manage_tutor_teacher', 'question_answer', array($this, 'question_answer') );

		add_submenu_page('tutor', __('Quiz Attempts', 'tutor'), __('Quiz Attempts', 'tutor'), 'manage_tutor_teacher', 'tutor_quiz_attempts', array($this, 'quiz_attempts') );

		add_submenu_page('tutor', __('E-Mails', 'tutor'), __('E-Mails', 'tutor'), 'manage_tutor_teacher', 'tutor_emails', array($this, 'tutor_emails') );

		//add_submenu_page('tutor', __('Addons', 'tutor'), __('Addons', 'tutor'), 'manage_tutor', 'tutor-addons', array(new Addons(), 'addons_page') );

		add_submenu_page('tutor', __('Status', 'tutor'), __('Status', 'tutor'), 'manage_tutor', 'tutor-status', array($this, 'tutor_status') );

		add_submenu_page('tutor', __('Settings', 'tutor'), __('Settings', 'tutor'), 'manage_tutor', 'tutor', array($this, 'tutor_page') );

		add_submenu_page('tutor',__('Tutor Uninstall', 'tutor'), null, 'deactivate_plugin', 'tutor-uninstall', array($this, 'tutor_uninstall'));
	}

	public function tutor_page(){
		$tutor_option = new Options();
		echo apply_filters('tutor/options/generated-html', $tutor_option->generate());
	}

	public function tutor_students(){
		include tutor()->path.'views/pages/students.php';
	}

	public function tutor_teachers(){
		include tutor()->path.'views/pages/teachers.php';
	}

	public function question_answer(){
		include tutor()->path.'views/pages/question_answer.php';
	}

	public function quiz_attempts(){
		include tutor()->path.'views/pages/quiz_attempts.php';
	}

	public function tutor_emails(){
		include tutor()->path.'views/pages/tutor_emails.php';
	}

	public function tutor_status(){
		include tutor()->path.'views/pages/status.php';
	}


	public function tutor_uninstall(){
		include tutor()->path.'views/pages/uninstall.php';
	}

	/**
	 * Filter posts for teacher
	 */
	public function filter_posts_for_teachers(){
		if (current_user_can(tutor()->teacher_role)){
			remove_menu_page( 'edit-comments.php' ); //Comments
			add_action( 'pre_get_posts', array($this, 'filter_posts_query_for_current_user') );
		}
	}

	/**
	 * @param $query
	 *
	 * Prevent unauthorised posts query at teacher panel
	 */
	public function filter_posts_query_for_current_user($query){
		$user_id = get_current_user_id();
		$query->set('author', $user_id);
	}

	/**
	 * Prevent unauthorised post edit page by direct URL
	 *
	 * @since v.1.0.0
	 */
	public function check_if_current_users_post(){
		if (! current_user_can(tutor()->teacher_role)) {
			return;
		}

		if (! empty($_GET['post']) ) {
			$get_post_id = (int) sanitize_text_field($_GET['post']);
			$get_post = get_post($get_post_id);
			$current_user = get_current_user_id();

			if ($get_post->post_author != $current_user){
				wp_die(__('Permission Denied', 'tutor'));
			}
		}
	}






	/**
	 * Status
	 */


	public static function scan_template_files( $template_path = null ) {
		if ( ! $template_path){
			$template_path = tutor()->path.'templates/';
		}


		$files  = @scandir( $template_path ); // @codingStandardsIgnoreLine.
		$result = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..', '.DS_Store' ), true ) ) {
					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @return array
	 *
	 *
	 */
	public static function template_overridden_files(){
		$template_files = self::scan_template_files();

		$override_files = array();
		foreach ($template_files as $file){
			$file_path = null;
			if (file_exists(trailingslashit(get_stylesheet_directory()).tutor()->template_path.$file)){
				$file_path = $file;
			}elseif (file_exists(trailingslashit(get_template_directory()).tutor()->template_path.$file)){
				$file_path = $file;
			}

			if ($file_path){
				$override_files[] = str_replace( WP_CONTENT_DIR.'/themes/', '', $file_path );
			}
		}

		return $override_files;
	}

	public static function get_environment_info(){

		// Figure out cURL version, if installed.
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		}


		// WP memory limit.
		$wp_memory_limit = tutor_utils()->let_to_num(WP_MEMORY_LIMIT);
		if ( function_exists( 'memory_get_usage' ) ) {
			$wp_memory_limit = max( $wp_memory_limit, tutor_utils()->let_to_num( @ini_get( 'memory_limit' ) ) );
		}

		$database_version = tutor_utils()->get_db_version();

		return array(
			'home_url'                  => get_option( 'home' ),
			'site_url'                  => get_option( 'siteurl' ),
			'version'                   => tutor()->version,
			'wp_version'                => get_bloginfo( 'version' ),
			'wp_multisite'              => is_multisite(),
			'wp_memory_limit'           => $wp_memory_limit,
			'wp_debug_mode'             => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'wp_cron'                   => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'                  => get_locale(),
			'external_object_cache'     => wp_using_ext_object_cache(),
			'server_info'               => isset( $_SERVER['SERVER_SOFTWARE'] ) ? wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '',
			'php_version'               => phpversion(),
			'php_post_max_size'         => tutor_utils()->let_to_num( ini_get( 'post_max_size' ) ),
			'php_max_execution_time'    => ini_get( 'max_execution_time' ),
			'php_max_input_vars'        => ini_get( 'max_input_vars' ),
			'curl_version'              => $curl_version,
			'suhosin_installed'         => extension_loaded( 'suhosin' ),
			'max_upload_size'           => wp_max_upload_size(),
			'mysql_version'             => $database_version['number'],
			'mysql_version_string'      => $database_version['string'],
			'default_timezone'          => date_default_timezone_get(),
			'fsockopen_or_curl_enabled' => ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ),
			'soapclient_enabled'        => class_exists( 'SoapClient' ),
			'domdocument_enabled'       => class_exists( 'DOMDocument' ),
			'gzip_enabled'              => is_callable( 'gzopen' ),
			'mbstring_enabled'          => extension_loaded( 'mbstring' ),
		);
		
	}


	public function erase_tutor_data(){
		global $wpdb;

		$is_erase_data = tutor_utils()->get_option('delete_on_uninstall');
		/**D*/ //=> Deleting Data

		$plugin_file = tutor()->basename;
		if ($is_erase_data && current_user_can( 'deactivate_plugin', $plugin_file )) {
			/**
			 * Deleting Post Type, Meta Data, taxonomy
			 */
			$course_post_type = tutor()->course_post_type;
			$lesson_post_type = tutor()->lesson_post_type;

			$post_types = array(
				$course_post_type,
				$lesson_post_type,
				'tutor_quiz',
				'tutor_question',
				'tutor_enrolled',
				'topics',
				'tutor_enrolled',
				'tutor_announcements',
			);

			$post_type_strings = "'".implode("','", $post_types)."'";
			$tutor_posts = $wpdb->get_col("SELECT ID from {$wpdb->posts} WHERE post_type in({$post_type_strings}) ;");

			if (is_array($tutor_posts) && count($tutor_posts)){
				foreach ($tutor_posts as $post_id){
					//Delete categories
					$terms = wp_get_object_terms( $post_id, 'course-category' );
					foreach( $terms as $term ){
						/**D*/ wp_remove_object_terms( $post_id, array( $term->term_id ), 'course-category' );
					}
					
					//Delete tags if available
					$terms = wp_get_object_terms( $post_id, 'course-tag' );
					foreach( $terms as $term ){
						/**D*/ wp_remove_object_terms( $post_id, array( $term->term_id ), 'course-tag' );
					}

					//Delete All Meta
					/**D*/ $wpdb->delete($wpdb->postmeta, array('post_id' => $post_id) );
					/**D*/ $wpdb->delete($wpdb->posts, array('ID' => $post_id) );
				}
			}

			/**
			 * Deleting Comments (reviews, questions, quiz_answers, etc)
			 */
			$tutor_comments = $wpdb->get_col("SELECT comment_ID from {$wpdb->comments} WHERE comment_agent = 'comment_agent' ;");
			$comments_ids_strings = "'".implode("','", $tutor_comments)."'";
			if (is_array($tutor_comments) && count($tutor_comments)){
				/**D*/ $wpdb->query("DELETE from {$wpdb->commentmeta} WHERE comment_ID in({$comments_ids_strings}) ");
			}
			/**D*/ $wpdb->delete($wpdb->comments, array('comment_agent' => 'comment_agent'));

			/**
			 * Delete Options
			 */

			/**D*/ delete_option('tutor_option');
			/**D*/ $wpdb->delete($wpdb->usermeta, array('meta_key' => '_is_tutor_student'));
			/**D*/ $wpdb->delete($wpdb->usermeta, array('meta_key' => '_tutor_teacher_approved'));
			/**D*/ $wpdb->delete($wpdb->usermeta, array('meta_key' => '_tutor_teacher_status'));
			/**D*/ $wpdb->delete($wpdb->usermeta, array('meta_key' => '_is_tutor_teacher'));
			/**D*/ $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE  '%_tutor_completed_lesson_id_%' ");

			deactivate_plugins($plugin_file);
		}
		
		wp_redirect('plugins.php');
		die();
	}

	public function plugin_action_links($actions){
		$is_erase_data = tutor_utils()->get_option('delete_on_uninstall');

		if ($is_erase_data) {
			$plugin_file = tutor()->basename;
			if ( current_user_can( 'deactivate_plugin', $plugin_file ) ) {
				if ( isset( $actions['deactivate'] ) ) {
					$actions['deactivate'] = '<a href="admin.php?page=tutor-uninstall">' . __('Uninstall', 'tutor') . '</a>';
				}
			}
		}

		$actions['settings'] = '<a href="admin.php?page=tutor">' . __('Settings', 'tutor') . '</a>';
		return $actions;
	}



}