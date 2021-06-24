<?php
/**
 * Static functions used in the OCDI plugin.
 *
 * @package ocdi
 */

namespace OCDI;

/**
 * Class with static helper functions.
 */
class Helpers {
	/**
	 * Holds the date and time string for demo import and log file.
	 *
	 * @var string
	 */
	public static $demo_import_start_time = '';

	/**
	 * Filter through the array of import files and get rid of those who do not comply.
	 *
	 * @param  array $import_files list of arrays with import file details.
	 * @return array list of filtered arrays.
	 */
	public static function validate_import_file_info( $import_files ) {
		$filtered_import_file_info = array();

		foreach ( $import_files as $import_file ) {
			if ( self::is_import_file_info_format_correct( $import_file ) ) {
				$filtered_import_file_info[] = $import_file;
			}
		}

		return $filtered_import_file_info;
	}


	/**
	 * Helper function: a simple check for valid import file format.
	 *
	 * @param  array $import_file_info array with import file details.
	 * @return boolean
	 */
	private static function is_import_file_info_format_correct( $import_file_info ) {
		if ( empty( $import_file_info['import_file_name'] ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @param  array  $import_file_info array with import file details.
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_import_files( $import_file_info ) {
		$downloaded_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
			'redux'      => '',
		);
		$downloader = new Downloader();

		$import_file_info = self::apply_filters('ocdi/pre_download_import_files', $import_file_info);

		// ----- Set content file path -----
		// Check if 'import_file_url' is not defined. That would mean a local file.
		if ( empty( $import_file_info['import_file_url'] ) ) {
			if ( file_exists( $import_file_info['local_import_file'] ) ) {
				$downloaded_files['content'] = $import_file_info['local_import_file'];
			}
		}
		else {
			// Set the filename string for content import file.
			$content_filename = self::apply_filters( 'ocdi/downloaded_content_file_prefix', 'demo-content-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'ocdi/downloaded_content_file_suffix_and_file_extension', '.xml' );

			// Download the content import file.
			$downloaded_files['content'] = $downloader->download_file( $import_file_info['import_file_url'], $content_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['content'] ) ) {
				return $downloaded_files['content'];
			}
		}

		// ----- Set widget file path -----
		// Get widgets file as well. If defined!
		if ( ! empty( $import_file_info['import_widget_file_url'] ) ) {
			// Set the filename string for widgets import file.
			$widget_filename = self::apply_filters( 'ocdi/downloaded_widgets_file_prefix', 'demo-widgets-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'ocdi/downloaded_widgets_file_suffix_and_file_extension', '.json' );

			// Download the widgets import file.
			$downloaded_files['widgets'] = $downloader->download_file( $import_file_info['import_widget_file_url'], $widget_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['widgets'] ) ) {
				return $downloaded_files['widgets'];
			}
		}
		else if ( ! empty( $import_file_info['local_import_widget_file'] ) ) {
			if ( file_exists( $import_file_info['local_import_widget_file'] ) ) {
				$downloaded_files['widgets'] = $import_file_info['local_import_widget_file'];
			}
		}

		// ----- Set customizer file path -----
		// Get customizer import file as well. If defined!
		if ( ! empty( $import_file_info['import_customizer_file_url'] ) ) {
			// Setup filename path to save the customizer content.
			$customizer_filename = self::apply_filters( 'ocdi/downloaded_customizer_file_prefix', 'demo-customizer-import-file_' ) . self::$demo_import_start_time . self::apply_filters( 'ocdi/downloaded_customizer_file_suffix_and_file_extension', '.dat' );

			// Download the customizer import file.
			$downloaded_files['customizer'] = $downloader->download_file( $import_file_info['import_customizer_file_url'], $customizer_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['customizer'] ) ) {
				return $downloaded_files['customizer'];
			}
		}
		else if ( ! empty( $import_file_info['local_import_customizer_file'] ) ) {
			if ( file_exists( $import_file_info['local_import_customizer_file'] ) ) {
				$downloaded_files['customizer'] = $import_file_info['local_import_customizer_file'];
			}
		}

		// ----- Set Redux file paths -----
		// Get Redux import file as well. If defined!
		if ( ! empty( $import_file_info['import_redux'] ) && is_array( $import_file_info['import_redux'] ) ) {
			$redux_items = array();

			// Setup filename paths to save the Redux content.
			foreach ( $import_file_info['import_redux'] as $index => $redux_item ) {
				$redux_filename = self::apply_filters( 'ocdi/downloaded_redux_file_prefix', 'demo-redux-import-file_' ) . $index . '-' . self::$demo_import_start_time . self::apply_filters( 'ocdi/downloaded_redux_file_suffix_and_file_extension', '.json' );

				// Download the Redux import file.
				$file_path = $downloader->download_file( $redux_item['file_url'], $redux_filename );

				// Return from this function if there was an error.
				if ( is_wp_error( $file_path ) ) {
					return $file_path;
				}

				$redux_items[] = array(
					'option_name' => $redux_item['option_name'],
					'file_path'   => $file_path,
				);
			}

			// Download the Redux import file.
			$downloaded_files['redux'] = $redux_items;
		}
		else if ( ! empty( $import_file_info['local_import_redux'] ) ) {

			$redux_items = array();

			// Setup filename paths to save the Redux content.
			foreach ( $import_file_info['local_import_redux'] as $redux_item ) {
				if ( file_exists( $redux_item['file_path'] ) ) {
					$redux_items[] = $redux_item;
				}
			}

			// Download the Redux import file.
			$downloaded_files['redux'] = $redux_items;
		}

		return $downloaded_files;
	}


	/**
	 * Write content to a file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @return string|WP_Error path to the saved file or WP_Error object with error message.
	 */
	public static function write_to_file( $content, $file_path ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		if ( ! $wp_filesystem->put_contents( $file_path, $content ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while writing file to your server! Tried to write a file to: %1$s%2$s.', 'one-click-demo-import' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file path on successful file write.
		return $file_path;
	}


	/**
	 * Append content to the file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @param string $separator_text separates the existing content of the file with the new content.
	 * @return boolean|WP_Error, path to the saved file or WP_Error object with error message.
	 */
	public static function append_to_file( $content, $file_path, $separator_text = '' ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		$existing_data = '';
		if ( file_exists( $file_path ) ) {
			$existing_data = $wp_filesystem->get_contents( $file_path );
		}

		// Style separator.
		$separator = PHP_EOL . '---' . $separator_text . '---' . PHP_EOL;

		if ( ! $wp_filesystem->put_contents( $file_path, $existing_data . $separator . $content . PHP_EOL ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while writing file to your server! Tried to write a file to: %1$s%2$s.', 'one-click-demo-import' ),
					'<br>',
					$file_path
				)
			);
		}

		return true;
	}


	/**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string $data, content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to read a file.
		global $wp_filesystem;

		$data = $wp_filesystem->get_contents( $file_path );

		if ( ! $data ) {
			return new \WP_Error(
				'failed_reading_file_from_server',
				sprintf( /* translators: %1$s - br HTML tag, %2$s - file path */
					__( 'An error occurred while reading a file from your server! Tried reading file from path: %1$s%2$s.', 'one-click-demo-import' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file data.
		return $data;
	}


	/**
	 * Helper function: check for WP file-system credentials needed for reading and writing to a file.
	 *
	 * @return boolean|WP_Error
	 */
	private static function check_wp_filesystem_credentials() {
		// Check if the file-system method is 'direct', if not display an error.
		if ( ! ( 'direct' === get_filesystem_method() ) ) {
			return new \WP_Error(
				'no_direct_file_access',
				sprintf( /* translators: %1$s and %2$s - strong HTML tags, %3$s - HTML link to a doc page. */
					__( 'This WordPress page does not have %1$sdirect%2$s write file access. This plugin needs it in order to save the demo import xml file to the upload directory of your site. You can change this setting with these instructions: %3$s.', 'one-click-demo-import' ),
					'<strong>',
					'</strong>',
					'<a href="http://gregorcapuder.com/wordpress-how-to-set-direct-filesystem-method/" target="_blank">How to set <strong>direct</strong> filesystem method</a>'
				)
			);
		}

		// Get plugin page settings.
		$plugin_page_setup = self::get_plugin_page_setup_data();

		// Get user credentials for WP file-system API.
		$demo_import_page_url = wp_nonce_url( $plugin_page_setup['parent_slug'] . '?page=' . $plugin_page_setup['menu_slug'], $plugin_page_setup['menu_slug'] );

		if ( false === ( $creds = request_filesystem_credentials( $demo_import_page_url, '', false, false, null ) ) ) {
			return new \WP_error(
				'filesystem_credentials_could_not_be_retrieved',
				__( 'An error occurred while retrieving reading/writing permissions to your server (could not retrieve WP filesystem credentials)!', 'one-click-demo-import' )
			);
		}

		// Now we have credentials, try to get the wp_filesystem running.
		if ( ! WP_Filesystem( $creds ) ) {
			return new \WP_Error(
				'wrong_login_credentials',
				__( 'Your WordPress login credentials don\'t allow to use WP_Filesystem!', 'one-click-demo-import' )
			);
		}

		return true;
	}


	/**
	 * Get log file path
	 *
	 * @return string, path to the log file
	 */
	public static function get_log_path() {
		if(getenv('WORDPRESS_PLUGIN_OCDI_LOG_PATH')){
			$upload_dir['path'] = getenv('WORDPRESS_PLUGIN_OCDI_LOG_PATH');
			is_dir($upload_dir['path']) || mkdir($upload_dir['path']);
		} else {
			$upload_dir = wp_upload_dir();
		}

		$upload_path = self::apply_filters( 'ocdi/upload_file_path', trailingslashit( $upload_dir['path'] ) );

		$log_path = $upload_path . self::apply_filters( 'ocdi/log_file_prefix', 'log_file_' ) . self::$demo_import_start_time . self::apply_filters( 'ocdi/log_file_suffix_and_file_extension', '.txt' );

		if(!getenv('WORDPRESS_PLUGIN_OCDI_LOG_PATH')) {
			self::register_file_as_media_attachment($log_path);
		}

		return $log_path;
	}


	/**
	 * Register file as attachment to the Media page.
	 *
	 * @param string $log_path log file path.
	 * @return void
	 */
	public static function register_file_as_media_attachment( $log_path ) {
		// Check the type of file.
		$log_mimes = array( 'txt' => 'text/plain' );
		$filetype  = wp_check_filetype( basename( $log_path ), self::apply_filters( 'ocdi/file_mimes', $log_mimes ) );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => self::get_log_url( $log_path ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => self::apply_filters( 'ocdi/attachment_prefix', esc_html__( 'One Click Demo Import - ', 'one-click-demo-import' ) ) . preg_replace( '/\.[^.]+$/', '', basename( $log_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the file as attachment in Media page.
		$attach_id = wp_insert_attachment( $attachment, $log_path );
	}


	/**
	 * Get log file url
	 *
	 * @param string $log_path log path to use for the log filename.
	 * @return string, url to the log file.
	 */
	public static function get_log_url( $log_path ) {
		$upload_dir = wp_upload_dir();
		$upload_url = self::apply_filters( 'ocdi/upload_file_url', trailingslashit( $upload_dir['url'] ) );

		return $upload_url . basename( $log_path );
	}


	/**
	 * Check if the AJAX call is valid.
	 */
	public static function verify_ajax_call() {
		check_ajax_referer( 'ocdi-ajax-verification', 'security' );

		// Check if user has the WP capability to import data.
		if ( ! current_user_can( 'import' ) ) {
			wp_die(
				sprintf( /* translators: %1$s - opening div and paragraph HTML tags, %2$s - closing div and paragraph HTML tags. */
					__( '%1$sYour user role isn\'t high enough. You don\'t have permission to import demo data.%2$s', 'one-click-demo-import' ),
					'<div class="notice  notice-error"><p>',
					'</p></div>'
				)
			);
		}
	}


	/**
	 * Process uploaded files and return the paths to these files.
	 *
	 * @param array  $uploaded_files $_FILES array form an AJAX request.
	 * @param string $log_file_path path to the log file.
	 * @return array of paths to the content import and widget import files.
	 */
	public static function process_uploaded_files( $uploaded_files, $log_file_path ) {
		// Variable holding the paths to the uploaded files.
		$selected_import_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
			'redux'      => '',
		);

		// Upload settings to disable form and type testing for AJAX uploads.
		$upload_overrides = array(
			'test_form' => false,
			'test_type' => false,
		);

		// Error data if the demo file was not provided.
		$file_not_provided_error = array(
			'error' => esc_html__( 'No file provided.', 'one-click-demo-import' )
		);

		// Handle demo file uploads.
		$content_file_info = isset( $_FILES['content_file'] ) ?
			wp_handle_upload( $_FILES['content_file'], $upload_overrides ) :
			$file_not_provided_error;

		$widget_file_info = isset( $_FILES['widget_file'] ) ?
			wp_handle_upload( $_FILES['widget_file'], $upload_overrides ) :
			$file_not_provided_error;

		$customizer_file_info = isset( $_FILES['customizer_file'] ) ?
			wp_handle_upload( $_FILES['customizer_file'], $upload_overrides ) :
			$file_not_provided_error;

		$redux_file_info = isset( $_FILES['customizer_file'] ) ?
			wp_handle_upload( $_FILES['redux_file'], $upload_overrides ) :
			$file_not_provided_error;

		// Process content import file.
		if ( $content_file_info && ! isset( $content_file_info['error'] ) ) {
			// Set uploaded content file.
			$selected_import_files['content'] = $content_file_info['file'];
		}
		else {
			// Add this error to log file.
			$log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Content file was not uploaded. Error: %s', 'one-click-demo-import' ),
					$content_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'one-click-demo-import' )
			);
		}

		// Process widget import file.
		if ( $widget_file_info && ! isset( $widget_file_info['error'] ) ) {
			// Set uploaded widget file.
			$selected_import_files['widgets'] = $widget_file_info['file'];
		}
		else {
			// Add this error to log file.
			$log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Widget file was not uploaded. Error: %s', 'one-click-demo-import' ),
					$widget_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'one-click-demo-import' )
			);
		}

		// Process Customizer import file.
		if ( $customizer_file_info && ! isset( $customizer_file_info['error'] ) ) {
			// Set uploaded customizer file.
			$selected_import_files['customizer'] = $customizer_file_info['file'];
		}
		else {
			// Add this error to log file.
			$log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Customizer file was not uploaded. Error: %s', 'one-click-demo-import' ),
					$customizer_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'one-click-demo-import' )
			);
		}

		// Process Redux import file.
		if ( $redux_file_info && ! isset( $redux_file_info['error'] ) ) {
			if ( isset( $_POST['redux_option_name'] ) && empty( $_POST['redux_option_name'] ) ) {
				// Write error to log file and send an AJAX response with the error.
				self::log_error_and_send_ajax_response(
					esc_html__( 'Missing Redux option name! Please also enter the Redux option name!', 'one-click-demo-import' ),
					$log_file_path,
					esc_html__( 'Upload files', 'one-click-demo-import' )
				);
			}

			// Set uploaded Redux file.
			$selected_import_files['redux'] = array(
				array(
					'option_name' => $_POST['redux_option_name'],
					'file_path'   => $redux_file_info['file'],
				),
			);
		}
		else {
			// Add this error to log file.
			$log_added = self::append_to_file(
				sprintf( /* translators: %s - the error message. */
					__( 'Redux file was not uploaded. Error: %s', 'one-click-demo-import' ),
					$redux_file_info['error']
				),
				$log_file_path,
				esc_html__( 'Upload files' , 'one-click-demo-import' )
			);
		}

		// Add this message to log file.
		$log_added = self::append_to_file(
			__( 'The import files were successfully uploaded!', 'one-click-demo-import' ) . self::import_file_info( $selected_import_files ),
			$log_file_path,
			esc_html__( 'Upload files' , 'one-click-demo-import' )
		);

		// Return array with paths of uploaded files.
		return $selected_import_files;
	}


	/**
	 * Get import file information and max execution time.
	 *
	 * @param array $selected_import_files array of selected import files.
	 */
	public static function import_file_info( $selected_import_files ) {
		$redux_file_string = '';

		if ( ! empty( $selected_import_files['redux'] ) ) {
			$redux_file_string = array_reduce( $selected_import_files['redux'], function( $string, $item ) {
				return sprintf( '%1$s%2$s -> %3$s %4$s', $string, $item['option_name'], $item['file_path'], PHP_EOL );
			}, '' );
		}

		return PHP_EOL .
		sprintf( /* translators: %s - the max execution time. */
			__( 'Initial max execution time = %s', 'one-click-demo-import' ),
			ini_get( 'max_execution_time' )
		) . PHP_EOL .
		sprintf( /* translators: %1$s - new line break, %2$s - the site URL, %3$s - the file path for content import, %4$s - the file path for widgets import, %5$s - the file path for widgets import, %6$s - the file path for redux import. */
			__( 'Files info:%1$sSite URL = %2$s%1$sData file = %3$s%1$sWidget file = %4$s%1$sCustomizer file = %5$s%1$sRedux files:%1$s%6$s', 'one-click-demo-import' ),
			PHP_EOL,
			get_site_url(),
			empty( $selected_import_files['content'] ) ? esc_html__( 'not defined!', 'one-click-demo-import' ) : $selected_import_files['content'],
			empty( $selected_import_files['widgets'] ) ? esc_html__( 'not defined!', 'one-click-demo-import' ) : $selected_import_files['widgets'],
			empty( $selected_import_files['customizer'] ) ? esc_html__( 'not defined!', 'one-click-demo-import' ) : $selected_import_files['customizer'],
			empty( $redux_file_string ) ? esc_html__( 'not defined!', 'one-click-demo-import' ) : $redux_file_string
		);
	}


	/**
	 * Write the error to the log file and send the AJAX response.
	 *
	 * @param string $error_text text to display in the log file and in the AJAX response.
	 * @param string $log_file_path path to the log file.
	 * @param string $separator title separating the old and new content.
	 */
	public static function log_error_and_send_ajax_response( $error_text, $log_file_path, $separator = '' ) {
		// Add this error to log file.
		$log_added = self::append_to_file(
			$error_text,
			$log_file_path,
			$separator
		);

		// Send JSON Error response to the AJAX call.
		wp_send_json( $error_text );
	}


	/**
	 * Set the $demo_import_start_time class variable with the current date and time string.
	 */
	public static function set_demo_import_start_time() {
		self::$demo_import_start_time = date( self::apply_filters( 'ocdi/date_format_for_file_names', 'Y-m-d__H-i-s' ) );
	}


	/**
	 * Get the category list of all categories used in the predefined demo imports array.
	 *
	 * @param  array $demo_imports Array of demo import items (arrays).
	 * @return array|boolean       List of all the categories or false if there aren't any.
	 */
	public static function get_all_demo_import_categories( $demo_imports ) {
		$categories = array();

		foreach ( $demo_imports as $item ) {
			if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
				foreach ( $item['categories'] as $category ) {
					$categories[ sanitize_key( $category ) ] = $category;
				}
			}
		}

		if ( empty( $categories ) ) {
			return false;
		}

		return $categories;
	}


	/**
	 * Return the concatenated string of demo import item categories.
	 * These should be separated by comma and sanitized properly.
	 *
	 * @param  array  $item The predefined demo import item data.
	 * @return string       The concatenated string of categories.
	 */
	public static function get_demo_import_item_categories( $item ) {
		$sanitized_categories = array();

		if ( isset( $item['categories'] ) ) {
			foreach ( $item['categories'] as $category ) {
				$sanitized_categories[] = sanitize_key( $category );
			}
		}

		if ( ! empty( $sanitized_categories ) ) {
			return implode( ',', $sanitized_categories );
		}

		return false;
	}


	/**
	 * Set the OCDI transient with the current importer data.
	 *
	 * @param array $data Data to be saved to the transient.
	 */
	public static function set_ocdi_import_data_transient( $data ) {
		set_transient( 'ocdi_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}


	/**
	 * Backwards compatible apply_filters helper.
	 * With 3.0 we changed the filter prefix from 'pt-ocdi/' to just 'ocdi/',
	 * but we needed to make sure backwards compatibility is in place.
	 * This method should be used for all apply_filters calls.
	 *
	 * @param string $hook         The filter hook name.
	 * @param mixed  $default_data The default filter data.
	 *
	 * @return mixed|void
	 */
	public static function apply_filters( $hook, $default_data ) {
		$new_data = apply_filters( $hook, $default_data );

		if ( $new_data !== $default_data ) {
			return $new_data;
		}

		$old_data = apply_filters( "pt-$hook", $default_data );

		if ( $old_data !== $default_data ) {
			return $old_data;
		}

		return $default_data;
	}

	/**
	 * Backwards compatible do_action helper.
	 * With 3.0 we changed the action prefix from 'pt-ocdi/' to just 'ocdi/',
	 * but we needed to make sure backwards compatibility is in place.
	 * This method should be used for all do_action calls.
	 *
	 * @param string $hook   The action hook name.
	 * @param mixed  ...$arg Optional. Additional arguments which are passed on to the
	 *                       functions hooked to the action. Default empty.
	 */
	public static function do_action( $hook, ...$arg ) {
		if ( has_action( $hook ) ) {
			do_action( $hook, ...$arg );
		} else if ( has_action( "pt-$hook" ) ) {
			do_action( "pt-$hook", ...$arg );
		}
	}

	/**
	 * Backwards compatible has_action helper.
	 * With 3.0 we changed the action prefix from 'pt-ocdi/' to just 'ocdi/',
	 * but we needed to make sure backwards compatibility is in place.
	 * This method should be used for all has_action calls.
	 *
	 * @param string        $hook              The name of the action hook.
	 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
	 *
	 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
	 *                  anything registered. When checking a specific function, the priority of that
	 *                  hook is returned, or false if the function is not attached. When using the
	 *                  $function_to_check argument, this function may return a non-boolean value
	 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
	 *                  return value.
	 */
	public static function has_action( $hook, $function_to_check = false ) {
		if ( has_action( $hook ) ) {
			return has_action( $hook, $function_to_check );
		} else if ( has_action( "pt-$hook" ) ) {
			return has_action( "pt-$hook", $function_to_check );
		}

		return false;
	}

	/**
	 * Get the plugin page setup data.
	 *
	 * @return array
	 */
	public static function get_plugin_page_setup_data() {
		return Helpers::apply_filters( 'ocdi/plugin_page_setup', array(
			'parent_slug' => 'themes.php',
			'page_title'  => esc_html__( 'One Click Demo Import' , 'one-click-demo-import' ),
			'menu_title'  => esc_html__( 'Import Demo Data' , 'one-click-demo-import' ),
			'capability'  => 'import',
			'menu_slug'   => 'one-click-demo-import',
		) );
	}
}
