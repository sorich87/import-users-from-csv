<?php
/**
 * @package Import_Users_from_CSV
 */
/*
Plugin Name: Import Users from CSV
Plugin URI: http://wordpress.org/extend/plugins/import-users-from-csv/
Description: Import Users data and metadata from a csv file.
Version: 1.0.0
Author: Ulrich Sossou
Author URI: http://ulrichsossou.com/
License: GPL2
Text Domain: import-users-from-csv
*/
/*  Copyright 2011  Ulrich Sossou  (https://github.com/sorich87)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain( 'import-users-from-csv', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! defined( 'IS_IU_CSV_DELIMITER' ) )
	define ( 'IS_IU_CSV_DELIMITER', ',' );

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
	public function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_pages' ) );
		add_action( 'init', array( __CLASS__, 'process_csv' ) );

		$upload_dir = wp_upload_dir();
		self::$log_dir_path = trailingslashit( $upload_dir['basedir'] );
		self::$log_dir_url  = trailingslashit( $upload_dir['baseurl'] );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_users_page( __( 'Import From CSV' , 'import-users-from-csv'), __( 'Import From CSV' , 'import-users-from-csv'), 'create_users', 'import-users-from-csv', array( __CLASS__, 'users_page' ) );
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function process_csv() {
		if ( isset( $_POST['_wpnonce-is-iu-import-users-users-page_import'] ) ) {
			check_admin_referer( 'is-iu-import-users-users-page_import', '_wpnonce-is-iu-import-users-users-page_import' );

			if ( isset( $_FILES['users_csv']['tmp_name'] ) ) {
				// Setup settings variables
				$filename              = $_FILES['users_csv']['tmp_name'];
				$password_nag          = isset( $_POST['password_nag'] ) ? $_POST['password_nag'] : false;
				$users_update          = isset( $_POST['users_update'] ) ? $_POST['users_update'] : false;
				$new_user_notification = isset( $_POST['new_user_notification'] ) ? $_POST['new_user_notification'] : false;

				$results = self::import_csv( $filename, array(
					'password_nag' => $password_nag,
					'new_user_notification' => $new_user_notification,
					'users_update' => $users_update
				) );

				// No users imported?
				if ( ! $results['user_ids'] )
					wp_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );

				// Some users imported?
				elseif ( $results['errors'] )
					wp_redirect( add_query_arg( 'import', 'errors', wp_get_referer() ) );

				// All users imported? :D
				else
					wp_redirect( add_query_arg( 'import', 'success', wp_get_referer() ) );

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
	public function users_page() {
		if ( ! current_user_can( 'create_users' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.' , 'import-users-from-csv') );
?>

<div class="wrap">
	<h2><?php _e( 'Import users from a CSV file' , 'import-users-from-csv'); ?></h2>
	<?php
	$error_log_file = self::$log_dir_path . 'is_iu_errors.log';
	$error_log_url  = self::$log_dir_url . 'is_iu_errors.log';

	if ( ! file_exists( $error_log_file ) ) {
		if ( ! @fopen( $error_log_file, 'x' ) )
			echo '<div class="updated"><p><strong>' . sprintf( __( 'Notice: please make the directory %s writable so that you can see the error log.' , 'import-users-from-csv'), self::$log_dir_path ) . '</strong></p></div>';
	}

	if ( isset( $_GET['import'] ) ) {
		$error_log_msg = '';
		if ( file_exists( $error_log_file ) )
			$error_log_msg = sprintf( __( ', please <a href="%s">check the error log</a>' , 'import-users-from-csv'), $error_log_url );

		switch ( $_GET['import'] ) {
			case 'file':
				echo '<div class="error"><p><strong>' . __( 'Error during file upload.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			case 'data':
				echo '<div class="error"><p><strong>' . __( 'Cannot extract data from uploaded file or no file was uploaded.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			case 'fail':
				echo '<div class="error"><p><strong>' . sprintf( __( 'No user was successfully imported%s.' , 'import-users-from-csv'), $error_log_msg ) . '</strong></p></div>';
				break;
			case 'errors':
				echo '<div class="error"><p><strong>' . sprintf( __( 'Some users were successfully imported but some were not%s.' , 'import-users-from-csv'), $error_log_msg ) . '</strong></p></div>';
				break;
			case 'success':
				echo '<div class="updated"><p><strong>' . __( 'Users import was successful.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			default:
				break;
		}
	}
	?>
	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'is-iu-import-users-users-page_import', '_wpnonce-is-iu-import-users-users-page_import' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="users_csv"><?php _e( 'CSV file' , 'import-users-from-csv'); ?></label></th>
				<td>
					<input type="file" id="users_csv" name="users_csv" value="" class="all-options" /><br />
					<span class="description"><?php echo sprintf( __( 'You may want to see <a href="%s">the example of the CSV file</a>.' , 'import-users-from-csv'), plugin_dir_url(__FILE__).'examples/import.csv'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Notification' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Notification' , 'import-users-from-csv'); ?></span></legend>
					<label for="new_user_notification">
						<input id="new_user_notification" name="new_user_notification" type="checkbox" value="1" />
						<?php _e('Send to new users', 'import-users-from-csv') ?>
					</label>
				</fieldset></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Password nag' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Password nag' , 'import-users-from-csv'); ?></span></legend>
					<label for="password_nag">
						<input id="password_nag" name="password_nag" type="checkbox" value="1" />
						<?php _e('Show password nag on new users signon', 'import-users-from-csv') ?>
					</label>
				</fieldset></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Users update' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Users update' , 'import-users-from-csv' ); ?></span></legend>
					<label for="users_update">
						<input id="users_update" name="users_update" type="checkbox" value="1" />
						<?php _e( 'Update user when a username or email exists', 'import-users-from-csv' ) ;?>
					</label>
				</fieldset></td>
			</tr>
		</table>
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
		$errors = $user_ids = array();

		$defaults = array(
			'password_nag' => false,
			'new_user_notification' => false,
			'users_update' => false
		);
		extract( wp_parse_args( $args, $defaults ) );

		// User data fields list used to differentiate with user meta
		$userdata_fields       = array(
			'ID', 'user_login', 'user_pass',
			'user_email', 'user_url', 'user_nicename',
			'display_name', 'user_registered', 'first_name',
			'last_name', 'nickname', 'description',
			'rich_editing', 'comment_shortcuts', 'admin_color',
			'use_ssl', 'show_admin_bar_front', 'show_admin_bar_admin',
			'role'
		);

		include( plugin_dir_path( __FILE__ ) . 'class-readcsv.php' );

		// Loop through the file lines
		$file_handle = fopen( $filename, 'r' );
		$csv_reader = new ReadCSV( $file_handle, IS_IU_CSV_DELIMITER, "\xEF\xBB\xBF" ); // Skip any UTF-8 byte order mark.

		$first = true;
		$rkey = 0;
		while ( ( $line = $csv_reader->get_row() ) !== NULL ) {

			// If the first line is empty, abort
			// If another line is empty, just skip it
			if ( empty( $line ) ) {
				if ( $first )
					break;
				else
					continue;
			}

			// If we are on the first line, the columns are the headers
			if ( $first ) {
				$headers = $line;
				$first = false;
				continue;
			}

			// Separate user data from meta
			$userdata = $usermeta = array();
			foreach ( $line as $ckey => $column ) {
				$column_name = $headers[$ckey];
				$column = trim( $column );

				if ( in_array( $column_name, $userdata_fields ) ) {
					$userdata[$column_name] = $column;
				} else {
					$usermeta[$column_name] = $column;
				}
			}

			// A plugin may need to filter the data and meta
			$userdata = apply_filters( 'is_iu_import_userdata', $userdata, $usermeta );
			$usermeta = apply_filters( 'is_iu_import_usermeta', $usermeta, $userdata );

			// If no user data, bailout!
			if ( empty( $userdata ) )
				continue;

			// Something to be done before importing one user?
			do_action( 'is_iu_pre_user_import', $userdata, $usermeta );

			$user = $user_id = false;

			if ( isset( $userdata['ID'] ) )
				$user = get_user_by( 'ID', $userdata['ID'] );

			if ( ! $user && $users_update ) {
				if ( isset( $userdata['user_login'] ) )
					$user = get_user_by( 'login', $userdata['user_login'] );

				if ( ! $user && isset( $userdata['user_email'] ) )
					$user = get_user_by( 'email', $userdata['user_email'] );
			}

			$update = false;
			if ( $user ) {
				$userdata['ID'] = $user->ID;
				$update = true;
			}

			// If creating a new user and no password was set, let auto-generate one!
			if ( ! $update && empty( $userdata['user_pass'] ) )
				$userdata['user_pass'] = wp_generate_password( 12, false );

			if ( $update )
				$user_id = wp_update_user( $userdata );
			else
				$user_id = wp_insert_user( $userdata );

			// Is there an error o_O?
			if ( is_wp_error( $user_id ) ) {
				$errors[$rkey] = $user_id;
			} else {
				// If no error, let's update the user meta too!
				if ( $usermeta ) {
					foreach ( $usermeta as $metakey => $metavalue ) {
						$metavalue = maybe_unserialize( $metavalue );
						update_user_meta( $user_id, $metakey, $metavalue );
					}
				}

				// If we created a new user, maybe set password nag and send new user notification?
				if ( ! $update ) {
					if ( $password_nag )
						update_user_option( $user_id, 'default_password_nag', true, true );

					if ( $new_user_notification )
						wp_new_user_notification( $user_id, $userdata['user_pass'] );
				}

				// Some plugins may need to do things after one user has been imported. Who know?
				do_action( 'is_iu_post_user_import', $user_id );

				$user_ids[] = $user_id;
			}

			$rkey++;
		}
		fclose( $file_handle );

		// One more thing to do after all imports?
		do_action( 'is_iu_post_users_import', $user_ids, $errors );

		// Let's log the errors
		self::log_errors( $errors );

		return array(
			'user_ids' => $user_ids,
			'errors'   => $errors
		);
	}

	/**
	 * Log errors to a file
	 *
	 * @since 0.2
	 **/
	private static function log_errors( $errors ) {
		if ( empty( $errors ) )
			return;

		$log = @fopen( self::$log_dir_path . 'is_iu_errors.log', 'a' );
		@fwrite( $log, sprintf( __( 'BEGIN %s' , 'import-users-from-csv'), date( 'Y-m-d H:i:s', time() ) ) . "\n" );

		foreach ( $errors as $key => $error ) {
			$line = $key + 1;
			$message = $error->get_error_message();
			@fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , 'import-users-from-csv'), $line, $message ) . "\n" );
		}

		@fclose( $log );
	}
}

IS_IU_Import_Users::init();
