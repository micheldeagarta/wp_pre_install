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

class Ai1wmme_Import_Usermeta {

	public static function execute( $params ) {
		// Skip users import
		if ( ! is_file( ai1wm_database_path( $params ) ) ) {
			return $params;
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
			Ai1wm_Status::info( __( 'Preparing user meta...', AI1WMME_PLUGIN_NAME ) );

			// Read blogs.json file
			$handle = ai1wm_open( ai1wm_blogs_path( $params ), 'r' );

			// Parse blogs.json file
			$blogs = ai1wm_read( $handle, filesize( ai1wm_blogs_path( $params ) ) );
			$blogs = json_decode( $blogs, true );

			// Close handle
			ai1wm_close( $handle );

			// Insert users
			if ( $blogs ) {

				// Get database client
				$db_client = Ai1wm_Database_Utility::create_client();

				$meta_value = array();

				// Get base prefix
				$base_prefix = ai1wm_table_prefix();

				// Get mainsite prefix
				$mainsite_prefix = ai1wm_table_prefix( 'mainsite' );

				// Set meta value
				foreach ( $blogs as $blog ) {
					$home_urls = array();

					// Add Home URL
					if ( ! empty( $blog['Old']['HomeURL'] ) ) {
						$home_urls[] = $blog['Old']['HomeURL'];
					}

					// Add Internal Home URL
					if ( ! empty( $blog['Old']['InternalHomeURL'] ) ) {
						if ( parse_url( $blog['Old']['InternalHomeURL'], PHP_URL_SCHEME ) && parse_url( $blog['Old']['InternalHomeURL'], PHP_URL_HOST ) ) {
							$home_urls[] = $blog['Old']['InternalHomeURL'];
						}
					}

					// Get Home URL
					foreach ( $home_urls as $home_url ) {

						// Set primary blog
						$meta_value[] = sprintf( " WHEN mum.meta_key = 'primary_blog' AND mum.meta_value = '%s' THEN '%s' ", $blog['Old']['BlogID'], $blog['New']['BlogID'] );

						// Set source domain
						$meta_value[] = sprintf( " WHEN mum.meta_key = 'source_domain' AND mum.meta_value = '%s' THEN '%s' ", parse_url( $home_url, PHP_URL_HOST ), parse_url( $blog['New']['HomeURL'], PHP_URL_HOST ) );
					}
				}

				// Set main primary blog
				$meta_value[] = sprintf( " WHEN mum.meta_key = 'primary_blog' THEN '%s' ", 1 );

				// Set main source domain
				$meta_value[] = sprintf( " WHEN mum.meta_key = 'source_domain' THEN '%s' ", parse_url( site_url(), PHP_URL_HOST ) );

				// Replace meta value
				$meta_replace = sprintf( ' CASE %s ELSE mum.meta_value END ', implode( ' ', $meta_value ) );

				// Update user meta
				$db_client->query( "UPDATE {$mainsite_prefix}usermeta SET meta_key = REPLACE(meta_key, '{$mainsite_prefix}', '{$base_prefix}')" );

				// Insert user meta
				$db_client->query(
					"INSERT INTO
						{$base_prefix}usermeta (
							user_id,
							meta_key,
							meta_value
						)
					SELECT
						u.ID,
						mum.meta_key,
						{$meta_replace} AS meta_value
					FROM
						{$mainsite_prefix}usermeta AS mum
					INNER JOIN
						{$mainsite_prefix}users AS mu ON (mum.user_id = mu.ID)
					INNER JOIN
						{$base_prefix}users AS u ON (CONVERT(mu.user_login USING utf8) = CONVERT(u.user_login USING utf8))
					LEFT JOIN
						{$base_prefix}usermeta AS um ON (um.user_id = u.ID AND CONVERT(mum.meta_key USING utf8) = CONVERT(um.meta_key USING utf8))
					WHERE
						um.meta_key IS NULL"
				);
			}

			// Set progress
			Ai1wm_Status::info( __( 'Done preparing user meta.', AI1WMME_PLUGIN_NAME ) );
		}

		return $params;
	}
}
