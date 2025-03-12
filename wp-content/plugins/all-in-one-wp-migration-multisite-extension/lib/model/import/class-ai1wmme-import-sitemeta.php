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

class Ai1wmme_Import_Sitemeta {

	public static function execute( $params ) {
		// Skip site meta import
		if ( ! is_file( ai1wm_database_path( $params ) ) ) {
			return $params;
		}

		// Set network ID
		if ( isset( $params['options']['network'] ) ) {
			$network_id = (int) $params['options']['network'];
		} else {
			$network_id = 1;
		}

		// Read multisite.json file
		$handle = ai1wm_open( ai1wm_multisite_path( $params ), 'r' );

		// Parse multisite.json file
		$multisite = ai1wm_read( $handle, filesize( ai1wm_multisite_path( $params ) ) );
		$multisite = json_decode( $multisite, true );

		// Close handle
		ai1wm_close( $handle );

		// Network
		if ( empty( $multisite['Network'] ) ) {

			// Set progress
			Ai1wm_Status::info( __( 'Preparing site meta...', AI1WMME_PLUGIN_NAME ) );

			// Read blogs.json file
			$handle = ai1wm_open( ai1wm_blogs_path( $params ), 'r' );

			// Parse blogs.json file
			$blogs = ai1wm_read( $handle, filesize( ai1wm_blogs_path( $params ) ) );
			$blogs = json_decode( $blogs, true );

			// Close handle
			ai1wm_close( $handle );

			// Insert site meta
			if ( $blogs ) {

				// Get database client
				$db_client = Ai1wm_Database_Utility::create_client();

				// Get base prefix
				$base_prefix = ai1wm_table_prefix();

				// Get mainsite prefix
				$mainsite_prefix = ai1wm_table_prefix( 'mainsite' );

				// Update site meta
				$db_client->query( "UPDATE {$mainsite_prefix}sitemeta SET meta_key = REPLACE(meta_key, '{$mainsite_prefix}', '{$base_prefix}')" );

				// Insert site meta
				$db_client->query(
					"INSERT INTO
						{$base_prefix}sitemeta (
							site_id,
							meta_key,
							meta_value
						)
					SELECT
						{$network_id},
						msm.meta_key,
						msm.meta_value
					FROM
						{$mainsite_prefix}sitemeta AS msm
					LEFT JOIN
						{$base_prefix}sitemeta AS sm ON (CONVERT(msm.meta_key USING utf8) = CONVERT(sm.meta_key USING utf8))
					WHERE
						sm.meta_key IS NULL"
				);
			}

			// Set progress
			Ai1wm_Status::info( __( 'Done preparing site meta.', AI1WMME_PLUGIN_NAME ) );
		}

		return $params;
	}
}
