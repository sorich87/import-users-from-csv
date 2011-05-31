<?php
/**
 * @package Import_Users_from_CSV
 * @version 0.2.2
 */
/*
Plugin Name: Customize Import Users from CSV
Plugin URI: http://pubpoet.com/plugins/
Description: Customize Import Users from CSV plugin. This is an example plugin. It shows how to modify the data retrieved from the CSV files.
Version: 0.1
Author: PubPoet
Author URI: http://pubpoet.com/
License: GPL2
*/
/*  Copyright 2011  Ulrich Sossou  (email : sorich87@gmail.com)

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

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class IS_IU_Custom_Import_Users {

	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_filter( 'is_iu_import_userdata', array( $this, 'filter_import_userdata' ), 10, 2 );
		add_filter( 'is_iu_import_usermeta', array( $this, 'filter_import_usermeta' ), 10, 2 );
	}

	/**
	 * Filter user data
	 *
	 * @since 0.1
	 **/
	public function filter_import_userdata( $userdata, $usermeta ) {
		// do your changes here

		return $userdata;
	}

	/**
	 * Filter user meta data
	 *
	 * @since 0.1
	 **/
	public function filter_import_usermeta( $usermeta, $userdata ) {
		// do your changes here

		return $usermeta;
	}
}

new IS_IU_Custom_Import_Users;
