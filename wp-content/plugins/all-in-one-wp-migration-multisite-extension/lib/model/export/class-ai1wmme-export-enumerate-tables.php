<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmme_Export_Enumerate_Tables {

	public static function execute( $params ) {
		global $wpdb;

		// Set exclude database
		if ( isset( $params['options']['no_database'] ) ) {
			return $params;
		}

		// Get total tables count
		if ( isset( $params['total_tables_count'] ) ) {
			$total_tables_count = (int) $params['total_tables_count'];
		} else {
			$total_tables_count = 1;
		}

		// Set progress
		Ai1wm_Status::info( __( 'Retrieving a list of WordPress database tables...', AI1WMME_PLUGIN_NAME ) );

		// Get database client
		$db_client = Ai1wm_Database_Utility::create_client();

		// Network or subsites
		if ( isset( $params['options']['sites'] ) ) {

			// Include table prefixes
			if ( ai1wmme_has_mainsite( $params ) ) {
				foreach ( ai1wmme_get_sites( $params ) as $site ) {
					if ( ai1wm_is_mainsite( $site['BlogID'] ) === false ) {
						$db_client->add_table_prefix_filter( ai1wm_table_prefix( $site['BlogID'] ) );
					}
				}

				// Include mainsite tables
				$db_client->add_table_prefix_filter( ai1wm_table_prefix(), ai1wm_table_prefix( '[0-9]+' ) );

			} else {

				// Include subsite tables
				foreach ( ai1wmme_get_sites( $params ) as $site ) {
					$db_client->add_table_prefix_filter( ai1wm_table_prefix( $site['BlogID'] ) );
				}

				// Include WP global tables
				foreach ( array_merge( $wpdb->global_tables, $wpdb->ms_global_tables ) as $table_name ) {
					$db_client->add_table_prefix_filter( ai1wm_table_prefix() . $table_name );
				}

				// Include BuddyPress tables
				$db_client->add_table_prefix_filter( ai1wm_table_prefix( 'bp' ) );
			}
		} else {

			// Include table prefixes
			if ( ai1wm_table_prefix() ) {
				$db_client->add_table_prefix_filter( ai1wm_table_prefix() );

				// Include table prefixes (Webba Booking)
				foreach ( array( 'wbk_services', 'wbk_days_on_off', 'wbk_locked_time_slots', 'wbk_appointments', 'wbk_cancelled_appointments', 'wbk_email_templates', 'wbk_service_categories', 'wbk_gg_calendars', 'wbk_coupons' ) as $table_name ) {
					$db_client->add_table_prefix_filter( $table_name );
				}
			}
		}

		// Create tables list file
		$tables_list = ai1wm_open( ai1wm_tables_list_path( $params ), 'w' );

		// Write table line
		foreach ( $db_client->get_tables() as $table_name ) {
			if ( ai1wm_putcsv( $tables_list, array( $table_name ) ) ) {
				$total_tables_count++;
			}
		}

		// Set progress
		Ai1wm_Status::info( __( 'Done retrieving a list of WordPress database tables.', AI1WMME_PLUGIN_NAME ) );

		// Set total tables count
		$params['total_tables_count'] = $total_tables_count;

		// Close the tables list file
		ai1wm_close( $tables_list );

		return $params;
	}
}
