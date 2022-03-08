<?php

WP_CLI::add_command( 'iucsv', function ( $args, $params ) {
	$subcommand = $args[0] ?? '';

	switch ( $subcommand ) {
		case 'import':
			$filepath = $args[1] ?? false;
			if ( $filepath === false || ! file_exists( $filepath ) ) {
				echo "provided filename does not exists" . PHP_EOL;
				exit;
			}

			$args = array(
				'users_update'               => rest_sanitize_boolean( $params['users_update'] ?? false ),
				'new_user_notificationd_nag' => rest_sanitize_boolean( $params['new_user_notificationd_nag'] ?? false ),
			);

			$result = IS_IU_Import_Users::import_csv( $filepath, [ 'users_update' => true ] );

			echo "Updated " . count( $result['user_ids'] ) . " users";

			if ( ! empty( $result['errors'] ) ) {
				echo " with errors:";
				echo implode( PHP_EOL, $result['errors'] );
			}

			echo PHP_EOL;
			break;

		default:
			echo "usage: wp iucsv import /path/to/file.csv" . PHP_EOL;
			exit;
	}
} );
