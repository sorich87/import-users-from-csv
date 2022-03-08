<?php
/*
Plugin Name: Import Users from CSV
Plugin URI: http://wordpress.org/extend/plugins/import-users-from-csv/
Description: Import Users data and metadata from a csv file.
Version: 1.1
Author: Andrew Lima
Author URI: https://andrewlima.co.za
License: GPL2
Text Domain: import-users-from-csv
*/

/*
 * Copyright 2011  Ulrich Sossou  (https://github.com/sorich87)
 * Copyright 2018  Andrew Lima  (https://github.com/andrewlimaza/import-users-from-csv)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * @package Import_Users_from_CSV
 */

load_plugin_textdomain( 'import-users-from-csv', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! defined( 'IS_IU_CSV_DELIMITER' ) ){
	define ( 'IS_IU_CSV_DELIMITER', ',' );
}

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class IS_IU_Import_Users {
	private static $log_dir_path = '';
	private static $log_dir_url  = '';

	/**
	 * Initialization
	 *
	 * @since 0.1
	 **/
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_pages' ) );
		add_action( 'init', array( __CLASS__, 'process_csv' ) );

		$upload_dir = wp_upload_dir();
		self::$log_dir_path = trailingslashit( $upload_dir['basedir'] );
		self::$log_dir_url  = trailingslashit( $upload_dir['baseurl'] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once __DIR__ . '/includes/import-users-from-csv.wpcli.php';
		}

		do_action('is_iu_after_init');
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public static function add_admin_pages() {
		add_users_page( __( 'Import From CSV' , 'import-users-from-csv'), __( 'Import From CSV' , 'import-users-from-csv'), 'create_users', 'import-users-from-csv', array( __CLASS__, 'users_page' ) );
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public static function process_csv() {
		if ( isset( $_POST['_wpnonce-is-iu-import-users-users-page_import'] ) ) {
			check_admin_referer( 'is-iu-import-users-users-page_import', '_wpnonce-is-iu-import-users-users-page_import' );

			if ( !empty( $_FILES['users_csv']['tmp_name'] ) ) {
				/* Setup settings variables */
				$filename              = sanitize_text_field( $_FILES['users_csv']['tmp_name'] );
				$users_update          = isset( $_POST['users_update'] ) ? sanitize_text_field( $_POST['users_update'] ) : false;
				$new_user_notification = isset( $_POST['new_user_notification'] ) ? sanitize_text_field( $_POST['new_user_notification'] ) : false;

				$results = self::import_csv( $filename, array(
					'new_user_notification' => intval( $new_user_notification ),
					'users_update' => intval( $users_update )
				) );

				if ( ! $results['user_ids'] ){
					/* No users imported? */
					wp_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );
				} else if ( $results['errors'] ){
					/* Some users imported? */
					wp_redirect( add_query_arg( 'import', 'errors', wp_get_referer() ) );
				} else {
					/* All users imported? :D */
					wp_redirect( add_query_arg( 'import', 'success', wp_get_referer() ) );
				}
				exit;
			}

			wp_redirect( add_query_arg( 'import', 'file', wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public static function users_page() {
		if ( ! current_user_can( 'create_users' ) ){
			wp_die( __( 'You do not have sufficient permissions to access this page.' , 'import-users-from-csv') );
		}

		?>

		<div class="wrap">
			<h2><?php _e( 'Import users from a CSV file' , 'import-users-from-csv'); ?></h2>
			<?php
				$error_log_file = self::$log_dir_path . 'is_iu_errors.log';
				$error_log_url  = self::$log_dir_url . 'is_iu_errors.log';

				if ( ! file_exists( $error_log_file ) ) {
					if ( ! @fopen( $error_log_file, 'x' ) ){
						$message = sprintf( __( 'Notice: please make the directory %s writable so that you can see the error log.' , 'import-users-from-csv'), self::$log_dir_path );
						self::render_notice('updated', $message);
					}
				}

				$import = isset( $_GET['import'] ) ? sanitize_text_field( $_GET['import'] ) : false;

				if ( $import ) {
					$error_log_msg = '';
					if ( file_exists( $error_log_file ) ){
						$error_log_msg = sprintf( __( ", please <a href='%s' target='_blank'>check the error log</a>", 'import-users-from-csv'), esc_url( $error_log_url ) );
					}

					switch ( $import ) {
						case 'file':
							$message = __( 'Error during file upload.' , 'import-users-from-csv');
							self::render_notice('error', $message);
							break;
						case 'data':
							$message = __( 'Cannot extract data from uploaded file or no file was uploaded.' , 'import-users-from-csv');
							self::render_notice('error', $message);
							break;
						case 'fail':
							$message = sprintf( __( 'No user was successfully imported%s.' , 'import-users-from-csv'), $error_log_msg );
							self::render_notice('error', $message);
							break;
						case 'errors':
							$message = sprintf( __( 'Some users were successfully imported but some were not%s.' , 'import-users-from-csv'), $error_log_msg );
							self::render_notice('update-nag', $message);
							break;
						case 'success':
							$message = __( 'Users import was successful.' , 'import-users-from-csv');
							self::render_notice('updated', $message);
							break;
						default:
							break;
					}
				}
			?>

			<form method="post" action="" enctype="multipart/form-data">
				<?php wp_nonce_field( 'is-iu-import-users-users-page_import', '_wpnonce-is-iu-import-users-users-page_import' ); ?>

				<?php do_action('is_iu_import_page_before_table'); ?>

				<table class="form-table widefat wp-list-table" style='padding: 5px;'>
					<?php do_action('is_iu_import_page_inside_table_top'); ?>
					<tr valign="top">
						<td scope="row">
							<strong>
								<label for="users_csv"><?php _e( 'CSV file' , 'import-users-from-csv'); ?></label>
							</strong>
						</td>
						<td>
							<input type="file" id="users_csv" name="users_csv" value="" class="all-options" /><br />
							<span class="description">
								<?php
									echo sprintf( __( 'You may want to see <a href="%s">the example of the CSV file</a>.' , 'import-users-from-csv'), esc_url( plugin_dir_url(__FILE__).'examples/import.csv' ) );
								?>
							</span>
						</td>
					</tr>
					<tr valign="top">
						<td scope="row">
							<strong>
								<?php _e( 'Notification' , 'import-users-from-csv'); ?>
							</strong>
						</td>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Notification' , 'import-users-from-csv'); ?></span></legend>

								<label for="new_user_notification">
									<input id="new_user_notification" name="new_user_notification" type="checkbox" value="1" />
									<?php _e('Send to new users', 'import-users-from-csv'); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<td scope="row"><strong><?php _e( 'Users update' , 'import-users-from-csv'); ?></strong></td>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Users update' , 'import-users-from-csv' ); ?></span></legend>

								<label for="users_update">
									<input id="users_update" name="users_update" type="checkbox" value="1" />
									<?php _e( 'Update user when a username or email exists', 'import-users-from-csv' ) ;?>
								</label>
							</fieldset>
						</td>
					</tr>

					<?php do_action('is_iu_import_page_inside_table_bottom'); ?>

				</table>

				<?php do_action('is_iu_import_page_after_table'); ?>

				<p class="submit">
				 	<input type="submit" class="button-primary" value="<?php _e( 'Import' , 'import-users-from-csv'); ?>" />
				</p>
			</form>
		<?php
	}

	/**
	 * Import a csv file
	 *
	 * @since 0.5
	 */
	public static function import_csv( $filename, $args ) {
		/* Stop timeouts */
		@set_time_limit(0);

		if ( ! class_exists( 'ReadCSV' ) ) {
			include( plugin_dir_path( __FILE__ ) . 'class-readcsv.php' );
		}

		$errors = $user_ids = array();

		$defaults = array(
			'new_user_notification' => false,
			'users_update' => false
		);

		extract( wp_parse_args( $args, $defaults ) );

		/*
		 * User data field map, used to match datasets
		*/
		$userdata_fields = array(
			'ID',
			'user_login',
			'user_pass',
			'user_email',
			'user_url',
			'user_nicename',
			'display_name',
			'user_registered',
			'first_name',
			'last_name',
			'nickname',
			'description',
			'rich_editing',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'show_admin_bar_admin',
			'role'
		);

		/* Filter for the user field map */
		apply_filters('is_iu_userdata_fields', $userdata_fields);

		/* Loop through the file lines */
		$file_handle = @fopen( $filename, 'r' );
		if($file_handle) {
			$csv_reader = new ReadCSV( $file_handle, IS_IU_CSV_DELIMITER, "\xEF\xBB\xBF" ); // Skip any UTF-8 byte order mark.

			$first = true;
			$rkey = 0;
			while ( ( $line = $csv_reader->get_row() ) !== NULL ) {
				if ( empty( $line ) ) {
					if ( $first ){
						/* If the first line is empty, abort */
						break;
					} else{
						/* If another line is empty, just skip it */
						continue;
					}
				}

				if ( $first ) {
					/* If we are on the first line, the columns are the headers */
					$headers = $line;
					$first = false;
					continue;
				}

				/* Prepare an array for multiple user roles */
				$user_roles = array();

				/* Separate user data from meta */
				$userdata = $usermeta = array();
				foreach ( $line as $ckey => $column ) {
					$column_name = $headers[$ckey];
					$column = trim( $column );

					if ( in_array( $column_name, $userdata_fields ) ) {
						$userdata[$column_name] = $column;
					} else {
						/**
						 * Data cleanup:
						 *
						 * Let's do a loose match on the column name
						 * This is to allow for small typos like 'UsEr PaSS' to be converted to 'user_pass'
						 *
						 * Todo: Add support for all uppercase as well
						*/
						$formatted_column_name = strtolower($column_name);
						$formatted_column_name = str_replace(' ', '_', $formatted_column_name);
						$formatted_column_name = str_replace('-', '_', $formatted_column_name);
						if( in_array( $formatted_column_name, $userdata_fields) ){
							/**
							 * We have a formatted match
							*/
							$userdata[$formatted_column_name] = $column;
						} else {
							/*
							 * We still have no match
							 * let's assume this is a meta value
							*/
							$usermeta[$column_name] = $column;
						}
					}
				}

				/*
				 * Hooks to allow other plugins from filtering this data
				*/
				$userdata = apply_filters( 'is_iu_import_userdata', $userdata, $usermeta );
				$usermeta = apply_filters( 'is_iu_import_usermeta', $usermeta, $userdata );

				if ( empty( $userdata ) ){
					/* If no user data, bailout! */
					continue;
				}

				/* Hook to allow other plugins to execute additional code pre-import */
				do_action( 'is_iu_pre_user_import', $userdata, $usermeta );

				$user = $user_id = false;
				if ( isset( $userdata['ID'] ) ){
					$user = get_user_by( 'ID', $userdata['ID'] );
				}

				/**
				 * Find the user by some alternative fields
				 *
				 * Fields checked: user_login, user_email
				*/
				if ( ! $user && $users_update ) {
					if ( isset( $userdata['user_login'] ) ){
						$user = get_user_by( 'login', $userdata['user_login'] );
					}

					if ( ! $user && isset( $userdata['user_email'] ) ){
						$user = get_user_by( 'email', $userdata['user_email'] );
					}
				}

				$update = false;
				if ( $user ) {
					$userdata['ID'] = $user->ID;
					$update = true;
				}

				if ( ! $update && empty( $userdata['user_pass'] ) ){
					/* No password set for this user, let's generate one automatically */
					$userdata['user_pass'] = wp_generate_password( 12, false );
				}
                
                if ( ! empty( $userdata['role'] ) ) {
                    $userdata['role'] = strtolower( $userdata['role'] );

                    $user_roles = explode( ',', $userdata['role'] );
	                $user_roles = array_map( 'trim', $user_roles );

                    if( count( $user_roles ) > 1 ) {
	                    $userdata['role'] = reset( $user_roles );
                    }
                }

				if ( $update ){
					$user_id = wp_update_user( $userdata );
				} else {
					$user_id = wp_insert_user( $userdata );
				}

				/* Is there an error o_O? */
				if ( is_wp_error( $user_id ) ) {
					$errors[$rkey] = $user_id;
				} else {
					/* If no error, let's update the user meta too! */
					if ( $usermeta ) {
						foreach ( $usermeta as $metakey => $metavalue ) {
							$metavalue = maybe_unserialize( $metavalue );
							update_user_meta( $user_id, $metakey, $metavalue );
						}
					}

					/* Let's update the user roles! */
                    foreach( $user_roles as $user_role ){
                        $user = new WP_User( $user_id );
                        $user->add_role( $user_role );
                    }

					/* If we created a new user, maybe send new notification */
					if ( ! $update ) {
						if ( $new_user_notification ) {
							wp_new_user_notification( $user_id, null, 'user' );
						}
					}

					/* Hook to allow other plugins to run functionality post import */
					do_action( 'is_iu_post_user_import', $user_id, $userdata, $usermeta );

					$user_ids[] = $user_id;
				}

				$rkey++;
			}
			fclose( $file_handle );
		} else {
			$errors[] = new WP_Error('file_read', 'Unable to open CSV file.');
		}

		/* One more thing to do after all imports? */
		do_action( 'is_iu_post_users_import', $user_ids, $errors );

		$errors = apply_filters( 'is_iu_errors_filter', $errors, $user_ids );

		/* Let's log the errors */
		self::log_errors( $errors );

		return apply_filters( 'is_iu_return_import_results', array(
			'user_ids' => $user_ids,
			'errors'   => $errors
		) );
	}

	/**
	 * Log errors to a file
	 *
	 * @since 0.2
	 **/
	private static function log_errors( $errors ) {
		if ( empty( $errors ) ){
			return;
		}

		$log = @fopen( self::$log_dir_path . 'is_iu_errors.log', 'a' );
		@fwrite( $log, sprintf( __( 'BEGIN %s' , 'import-users-from-csv'), date_i18n( 'Y-m-d H:i:s', time() ) ) . "\n" );

		foreach ( $errors as $key => $error ) {
			$line = $key + 1;
			$message = $error->get_error_message();
			@fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , 'import-users-from-csv'), $line, $message ) . "\n" );
		}

		@fclose( $log );
	}

	/**
	 * Echo out a notice withs specific class.
	 *
	 * @param $class - class to add to div
	 * @param $message - The content of the notice. This should be escaped before being passed in to ensure proper escaping is done.
	 *
	 *
	 * @since 1.0.1
	*/
	private static function render_notice($class, $message){
		$class = esc_attr($class);
		echo "<div class='$class'><p><strong>$message</strong></p></div>";
	}
}

IS_IU_Import_Users::init();
