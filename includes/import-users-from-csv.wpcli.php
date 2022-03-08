<?php

WP_CLI::add_command( 'iucsv', function ( $args, $params ) {
	$subcommand = $args[0] ?? '';

	switch ( $subcommand ) {
		case 'import':
			$filepath = $args[1] ?? false;
			if ( $filepath === false || ! file_exists( $filepath ) ) {
				echo esc_html( 'provided filename does not exists', 'import-users-from-csv' ) . PHP_EOL;
				exit;
			}

			$args = array(
				'users_update'               => rest_sanitize_boolean( $params['users_update'] ?? false ),
				'new_user_notificationd_nag' => rest_sanitize_boolean( $params['new_user_notificationd_nag'] ?? false ),
			);

			$result = IS_IU_Import_Users::import_csv( $filepath, [ 'users_update' => true ] );

			echo sprintf( esc_html( 'Updated %d users', 'import-users-from-csv' ), count( $result['user_ids'] ) );

			if ( ! empty( $result['errors'] ) ) {
				echo ' '. esc_html( 'with errors:', 'import-users-from-csv' );
				echo implode( PHP_EOL, $result['errors'] );
			}

			echo PHP_EOL;
			break;

		default:
			echo esc_html( 'usage: wp iucsv import /path/to/file.csv', 'import-users-from-csv' ) . PHP_EOL;
			exit;
	}
} );
