<?php
/**
 * @package externals links
 * @version 0.1
 */
/*
Plugin Name: Import Users from CSV
Plugin URI: http://intside.com/
Description: Import Users data and metadata from csv file.
Author: Intside
Version: 0.1
Author URI: http://intside.com/
*/

if ( ! function_exists( 'str_getcsv' ) ) {
	function str_getcsv( $input, $delimiter=',', $enclosure='"', $escape=null, $eol=null ) {
		$temp = fopen( "php://memory", "rw" );
		fwrite( $temp, $input );
		fseek( $temp, 0 );
		$r = array();
		while ( ( $data = fgetcsv( $temp, 4096, $delimiter, $enclosure ) ) !== false ) {
			$r[] = $data;
		}
		fclose( $temp );
		if ( 1 === count( $r ) )
			$r = $r[0];
		return $r;
	}
}


/**
 * Main plugin class
 *
 * @since 0.1
 **/
class IS_SM_Import_Users {

	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'admin_init', array( $this, 'process_csv' ) );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		add_users_page( __( 'Import From CSV' ), __( 'Import From CSV' ), 'manage_options', 'import-users-from-csv', array( $this, 'users_page' ) );
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function process_csv() {
		if ( isset( $_FILES['users_csv']['tmp_name'] ) ) {
			$rows            = file( $_FILES['users_csv']['tmp_name'] );
			$headers         = str_getcsv( $rows[0] );
			$rows            = array_slice( $rows, 1 );
			$errors          = array();
			$userdata_fields = array(
				'ID', 'user_login', 'user_pass',
				'user_email', 'user_url', 'user_nicename',
				'display_name', 'user_registered', 'first_name',
				'last_name', 'nickname', 'description',
				'rich_editing', 'comment_shortcuts', 'admin_color',
				'use_ssl', 'show_admin_bar_front', 'show_admin_bar_admin'
			);

			$sms = new IS_SM_Sms_Messages();

			foreach ( $rows as $rkey => $row ) {
				$columns = str_getcsv( $row );
				$userdata = $usermeta = array();

				foreach ( $columns as $ckey => $column ) {
					$column_name = $headers[$ckey];
					$column = trim( $column );

					if ( in_array( $column_name, $userdata_fields ) ) {
						$userdata[$column_name] = $column;
					} elseif ( in_array( $column_name, array( 'e', 'e_pl', 'r', 'c', 'a_m' ) ) ) {
						if ( ! empty( $column ) )
							$usermeta['profession'] = $column;
					} else {
						$usermeta[$column_name] = $column;
					}
				}

				if ( ! empty( $userdata['first_name'] ) ) {
					$userdata['first_name'] = strtolower( $userdata['first_name'] );
					$userdata['first_name'] = ucfirst( $userdata['first_name'] );
				}

				if ( ! empty( $usermeta['phone'] ) ) {
					$usermeta['phone'] = str_replace( ' ', '', $usermeta['phone'] );
					$usermeta['phone'] = '229' . $usermeta['phone'];
				}

				if ( empty( $userdata['user_login'] ) )
					$userdata['user_login'] = $usermeta['phone'];

				if ( empty( $userdata['user_email'] ) )
					$userdata['user_email'] = 'test+' . $userdata['user_login'] . '@localhost.localdomain';

				if ( empty( $userdata['user_pass'] ) )
					$userdata['user_pass'] = wp_generate_password( 12, false );

				if ( 11 != strlen( $usermeta['phone'] ) )
					$userdata = array();

				$user_id = wp_insert_user( $userdata );

				if ( is_wp_error( $user_id ) ) {
					$errors[$rkey] = $user_id;
				} else {
					foreach ( $usermeta as $metakey => $metavalue ) {
						update_user_meta( $user_id, $metakey, $metavalue );
					}

					$sms->send_registration_sms( $user_id );
				}
			}

			$sms->process();

			wp_redirect( add_query_arg( 'success', 'true', wp_get_referer() ) );
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function users_page() {
		if ( ! current_user_can( 'manage_options' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
?>

<div class="wrap">
	<h2><?php _e( 'Import users from a CSV file' ); ?></h2>
	<form method="post" action="" enctype="multipart/form-data">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'CSV file' ); ?></th>
				<td><input type="file" name="users_csv" value="" class="all-options" /></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Import Users' ); ?>" />
		</p>
	</form>
<?php
	}
}

new IS_SM_Import_Users;
