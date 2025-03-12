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

class Ai1wmme_Export_Enumerate_Content {

	public static function execute( $params ) {

		$exclude_filters = array_merge( array( ai1wm_get_uploads_dir(), ai1wm_get_plugins_dir() ), ai1wm_get_themes_dirs() );

		// Get total content files count
		if ( isset( $params['total_content_files_count'] ) ) {
			$total_content_files_count = (int) $params['total_content_files_count'];
		} else {
			$total_content_files_count = 1;
		}

		// Get total content files size
		if ( isset( $params['total_content_files_size'] ) ) {
			$total_content_files_size = (int) $params['total_content_files_size'];
		} else {
			$total_content_files_size = 1;
		}

		// Set progress
		Ai1wm_Status::info( __( 'Retrieving a list of WordPress content files...', AI1WMME_PLUGIN_NAME ) );

		// Exclude cache
		if ( isset( $params['options']['no_cache'] ) ) {
			$exclude_filters[] = 'cache';
		}

		// Exclude must-use plugins
		if ( isset( $params['options']['no_muplugins'] ) ) {
			$exclude_filters[] = 'mu-plugins';
		}

		$include_filters = array();

		// Exclude media
		if ( isset( $params['options']['no_media'] ) ) {
			$exclude_filters[] = 'blogs.dir';
		} elseif ( isset( $params['options']['sites'] ) ) {
			foreach ( ai1wmme_get_sites( $params ) as $site ) {
				if ( ai1wm_is_mainsite( $site['BlogID'] ) === false ) {
					$include_filters[] = ai1wm_blog_blogsdir_abspath( $site['BlogID'] );
				}
			}

			$exclude_filters[] = 'blogs.dir';
		}

		// Exclude SQLite file
		if ( defined( 'FQDB' ) ) {
			$exclude_filters[] = FQDB;
		}

		$user_filters = array();

		// Exclude selected files
		if ( isset( $params['options']['exclude_files'], $params['excluded_files'] ) ) {
			if ( ( $excluded_files = explode( ',', $params['excluded_files'] ) ) ) {
				foreach ( $excluded_files as $excluded_path ) {
					$exclude_filters[] = $user_filters[] = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . untrailingslashit( $excluded_path );
				}
			}
		}

		// Create content list file
		$content_list = ai1wm_open( ai1wm_content_list_path( $params ), 'w' );

		// Enumerate over blogs directory
		if ( isset( $params['options']['no_media'] ) === false ) {

			// Loop over blogs directory
			if ( $include_filters ) {
				foreach ( $include_filters as $path ) {
					if ( is_dir( $path ) ) {

						// Iterate over blogs directory
						$iterator = new Ai1wm_Recursive_Directory_Iterator( $path );

						// Exclude new line file names
						$iterator = new Ai1wm_Recursive_Exclude_Filter( $iterator, apply_filters( 'ai1wm_exclude_media_from_export', ai1wm_media_filters( $user_filters ) ) );

						// Recursively iterate over blogs directory
						$iterator = new Ai1wm_Recursive_Iterator_Iterator( $iterator, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD );

						// Write path line
						foreach ( $iterator as $item ) {
							if ( $item->isFile() ) {
								if ( ai1wm_putcsv( $content_list, array( $iterator->getPathname(), substr_replace( $iterator->getPathname(), '', 0, strlen( WP_CONTENT_DIR ) + 1 ), $iterator->getSize(), $iterator->getMTime() ) ) ) {
									$total_content_files_count++;

									// Add current file size
									$total_content_files_size += $iterator->getSize();
								}
							}
						}
					}
				}
			}
		}

		// Enumerate over content directory
		if ( isset( $params['options']['no_themes'], $params['options']['no_muplugins'], $params['options']['no_plugins'] ) === false ) {

			// Iterate over content directory
			$iterator = new Ai1wm_Recursive_Directory_Iterator( WP_CONTENT_DIR );

			// Exclude content files
			$iterator = new Ai1wm_Recursive_Exclude_Filter( $iterator, apply_filters( 'ai1wm_exclude_content_from_export', ai1wm_content_filters( $exclude_filters ) ) );

			// Recursively iterate over content directory
			$iterator = new Ai1wm_Recursive_Iterator_Iterator( $iterator, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD );

			// Write path line
			foreach ( $iterator as $item ) {
				if ( $item->isFile() ) {
					if ( ai1wm_putcsv( $content_list, array( $iterator->getPathname(), $iterator->getSubPathname(), $iterator->getSize(), $iterator->getMTime() ) ) ) {
						$total_content_files_count++;

						// Add current file size
						$total_content_files_size += $iterator->getSize();
					}
				}
			}
		}

		// Set progress
		Ai1wm_Status::info( __( 'Done retrieving a list of WordPress content files.', AI1WMME_PLUGIN_NAME ) );

		// Set total content files count
		$params['total_content_files_count'] = $total_content_files_count;

		// Set total content files size
		$params['total_content_files_size'] = $total_content_files_size;

		// Close the content list file
		ai1wm_close( $content_list );

		return $params;
	}
}
