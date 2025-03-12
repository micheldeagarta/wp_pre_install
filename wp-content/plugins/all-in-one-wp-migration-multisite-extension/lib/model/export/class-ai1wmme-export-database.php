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

class Ai1wmme_Export_Database {

	public static function execute( $params ) {
		global $wpdb;

		// Set exclude database
		if ( isset( $params['options']['no_database'] ) ) {
			return $params;
		}

		// Set query offset
		if ( isset( $params['query_offset'] ) ) {
			$query_offset = (int) $params['query_offset'];
		} else {
			$query_offset = 0;
		}

		// Set table index
		if ( isset( $params['table_index'] ) ) {
			$table_index = (int) $params['table_index'];
		} else {
			$table_index = 0;
		}

		// Set table offset
		if ( isset( $params['table_offset'] ) ) {
			$table_offset = (int) $params['table_offset'];
		} else {
			$table_offset = 0;
		}

		// Set table rows
		if ( isset( $params['table_rows'] ) ) {
			$table_rows = (int) $params['table_rows'];
		} else {
			$table_rows = 0;
		}

		// Set total tables count
		if ( isset( $params['total_tables_count'] ) ) {
			$total_tables_count = (int) $params['total_tables_count'];
		} else {
			$total_tables_count = 1;
		}

		// What percent of tables have we processed?
		$progress = (int) ( ( $table_index / $total_tables_count ) * 100 );

		// Set progress
		Ai1wm_Status::info( sprintf( __( 'Exporting multisite database...<br />%d%% complete<br />%s records saved', AI1WMME_PLUGIN_NAME ), $progress, number_format_i18n( $table_rows ) ) );

		// Get tables list file
		$tables_list = ai1wm_open( ai1wm_tables_list_path( $params ), 'r' );

		// Loop over tables
		$tables = array();
		while ( list( $table_name ) = fgetcsv( $tables_list ) ) {
			$tables[] = $table_name;
		}

		// Close the tables list file
		ai1wm_close( $tables_list );

		// Get database client
		$db_client = Ai1wm_Database_Utility::create_client();

		// Network or subsites
		if ( isset( $params['options']['sites'] ) ) {

			// Loop over subsites
			foreach ( ai1wmme_get_sites( $params ) as $site ) {

				// Exclude spam comments
				if ( isset( $params['options']['no_spam_comments'] ) ) {
					$db_client->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'comments', "`comment_approved` != 'spam'" )
						->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'commentmeta', sprintf( "`comment_ID` IN (SELECT `comment_ID` FROM `%s` WHERE `comment_approved` != 'spam')", ai1wm_table_prefix( $site['BlogID'] ) . 'comments' ) );
				}

				// Exclude post revisions
				if ( isset( $params['options']['no_post_revisions'] ) ) {
					$db_client->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'posts', "`post_type` != 'revision'" );
				}

				// Exclude site options
				$db_client->set_table_where_query( ai1wm_table_prefix() . 'options', sprintf( "`option_name` NOT IN ('%s', '%s', '%s', '%s', '%s', '%s', '%s')", AI1WM_STATUS, AI1WM_SECRET_KEY, AI1WM_AUTH_USER, AI1WM_AUTH_PASSWORD, AI1WM_AUTH_HEADER, AI1WM_BACKUPS_LABELS, AI1WM_SITES_LINKS ) );

				// Set table select columns
				if ( ( $column_names = $db_client->get_column_names( ai1wm_table_prefix( $site['BlogID'] ) . 'options' ) ) ) {
					if ( isset( $column_names['option_name'], $column_names['option_value'] ) ) {
						$column_names['option_value'] = sprintf( "(CASE WHEN option_name = '%s' THEN 'a:0:{}' WHEN (option_name = '%s' OR option_name = '%s') THEN '' ELSE option_value END) AS option_value", AI1WM_ACTIVE_PLUGINS, AI1WM_ACTIVE_TEMPLATE, AI1WM_ACTIVE_STYLESHEET );
					}

					$db_client->set_table_select_columns( ai1wm_table_prefix( $site['BlogID'] ) . 'options', $column_names );
				}

				// Set table prefix columns
				$db_client->set_table_prefix_columns( ai1wm_table_prefix( $site['BlogID'] ) . 'options', array( 'option_name' ) );
			}

			$users = array();
			foreach ( ai1wmme_get_sites( $params ) as $site ) {
				$users[] = ai1wm_table_prefix( $site['BlogID'] ) . 'capabilities';
				$users[] = ai1wm_table_prefix( $site['BlogID'] ) . 'user_level';
			}

			// Include users and user meta
			if ( $users ) {
				$usermeta = array();

				// Set user meta query
				if ( ai1wmme_has_mainsite( $params ) ) {
					foreach ( ai1wmme_get_sites( $params ) as $site ) {
						if ( ai1wm_is_mainsite( $site['BlogID'] ) === false ) {
							$usermeta[] = sprintf( "`meta_key` REGEXP '^%s'", ai1wm_table_prefix( $site['BlogID'] ) );
						}
					}

					$usermeta[] = sprintf( "`meta_key` NOT REGEXP '^%s'", ai1wm_table_prefix( '[0-9]+' ) );

				} else {

					// Include subsite user meta
					foreach ( ai1wmme_get_sites( $params ) as $site ) {
						$usermeta[] = sprintf( "`meta_key` REGEXP '^%s'", ai1wm_table_prefix( $site['BlogID'] ) );
					}

					$usermeta[] = sprintf( "`meta_key` NOT REGEXP '^%s'", ai1wm_table_prefix() );
				}

				// Include user meta
				$db_client->set_table_where_query( ai1wm_table_prefix() . 'usermeta', sprintf( "`user_id` IN (SELECT `user_id` FROM `%s` WHERE `meta_key` IN ('%s')) AND (%s)", ai1wm_table_prefix() . 'usermeta', implode( "', '", $users ), implode( ' OR ', $usermeta ) ) );

				// Include users
				$db_client->set_table_where_query( ai1wm_table_prefix() . 'users', sprintf( "`ID` IN (SELECT `user_id` FROM `%s` WHERE `meta_key` IN ('%s'))", ai1wm_table_prefix() . 'usermeta', implode( "', '", $users ) ) );
			}

			$old_table_prefixes = $old_column_prefixes = array();
			$new_table_prefixes = $new_column_prefixes = array();

			// Set table prefixes
			if ( ai1wmme_has_mainsite( $params ) ) {

				// Set subsite table prefixes
				foreach ( ai1wmme_get_sites( $params ) as $site ) {
					if ( ai1wm_is_mainsite( $site['BlogID'] ) === false ) {
						$old_table_prefixes[] = $old_column_prefixes[] = ai1wm_table_prefix( $site['BlogID'] );
						$new_table_prefixes[] = $new_column_prefixes[] = ai1wm_servmask_prefix( $site['BlogID'] );
					}
				}

				// Set mainsite table prefixes
				foreach ( array_merge( $wpdb->global_tables, $wpdb->ms_global_tables ) as $table_name ) {
					$old_table_prefixes[] = ai1wm_table_prefix() . $table_name;
					$new_table_prefixes[] = ai1wm_servmask_prefix( 'mainsite' ) . $table_name;
				}

				// Set basesite table prefixes
				if ( ai1wm_table_prefix() ) {
					$old_table_prefixes[] = ai1wm_table_prefix();
					$new_table_prefixes[] = ai1wm_servmask_prefix( 'basesite' );
				} else {
					foreach ( $tables as $table_name ) {
						$old_table_prefixes[] = $table_name;
						$new_table_prefixes[] = ai1wm_servmask_prefix( 'basesite' ) . $table_name;
					}
				}

				// Set basesite column prefixes
				if ( strlen( ai1wm_table_prefix() ) > 1 ) {
					$old_column_prefixes[] = ai1wm_table_prefix();
					$new_column_prefixes[] = ai1wm_servmask_prefix( 'basesite' );
				} else {
					foreach ( array( 'user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time' ) as $column_prefix ) {
						$old_column_prefixes[] = ai1wm_table_prefix() . $column_prefix;
						$new_column_prefixes[] = ai1wm_servmask_prefix( 'basesite' ) . $column_prefix;
					}
				}
			} else {

				// Set subsite table prefixes
				foreach ( ai1wmme_get_sites( $params ) as $site ) {
					$old_table_prefixes[] = $old_column_prefixes[] = ai1wm_table_prefix( $site['BlogID'] );
					$new_table_prefixes[] = $new_column_prefixes[] = ai1wm_servmask_prefix( $site['BlogID'] );
				}

				// Set mainsite table prefixes
				if ( ai1wm_table_prefix() ) {
					$old_table_prefixes[] = ai1wm_table_prefix();
					$new_table_prefixes[] = ai1wm_servmask_prefix( 'mainsite' );
				} else {
					foreach ( $tables as $table_name ) {
						$old_table_prefixes[] = $table_name;
						$new_table_prefixes[] = ai1wm_servmask_prefix( 'mainsite' ) . $table_name;
					}
				}

				// Set mainsite column prefixes
				if ( strlen( ai1wm_table_prefix() ) > 1 ) {
					$old_column_prefixes[] = ai1wm_table_prefix();
					$new_column_prefixes[] = ai1wm_servmask_prefix( 'mainsite' );
				} else {
					foreach ( array( 'user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time' ) as $column_prefix ) {
						$old_column_prefixes[] = ai1wm_table_prefix() . $column_prefix;
						$new_column_prefixes[] = ai1wm_servmask_prefix( 'mainsite' ) . $column_prefix;
					}
				}
			}

			$db_client->set_tables( $tables )
				->set_old_table_prefixes( $old_table_prefixes )
				->set_new_table_prefixes( $new_table_prefixes )
				->set_old_column_prefixes( $old_column_prefixes )
				->set_new_column_prefixes( $new_column_prefixes );

		} else {

			// Loop over subsites
			foreach ( ai1wmme_get_sites( $params ) as $site ) {

				// Exclude spam comments
				if ( isset( $params['options']['no_spam_comments'] ) ) {
					$db_client->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'comments', "`comment_approved` != 'spam'" )
						->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'commentmeta', sprintf( "`comment_ID` IN ( SELECT `comment_ID` FROM `%s` WHERE `comment_approved` != 'spam' )", ai1wm_table_prefix( $site['BlogID'] ) . 'comments' ) );
				}

				// Exclude post revisions
				if ( isset( $params['options']['no_post_revisions'] ) ) {
					$db_client->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'posts', "`post_type` != 'revision'" );
				}

				// Exclude site options
				$db_client->set_table_where_query( ai1wm_table_prefix( $site['BlogID'] ) . 'options', sprintf( "`option_name` NOT IN ('%s', '%s', '%s', '%s', '%s', '%s')", AI1WM_STATUS, AI1WM_SECRET_KEY, AI1WM_AUTH_USER, AI1WM_AUTH_PASSWORD, AI1WM_BACKUPS_LABELS, AI1WM_SITES_LINKS ) );

				// Set table select columns
				if ( ( $column_names = $db_client->get_column_names( ai1wm_table_prefix( $site['BlogID'] ) . 'options' ) ) ) {
					if ( isset( $column_names['option_name'], $column_names['option_value'] ) ) {
						$column_names['option_value'] = sprintf( "(CASE WHEN option_name = '%s' THEN 'a:0:{}' WHEN (option_name = '%s' OR option_name = '%s') THEN '' ELSE option_value END) AS option_value", AI1WM_ACTIVE_PLUGINS, AI1WM_ACTIVE_TEMPLATE, AI1WM_ACTIVE_STYLESHEET );
					}

					$db_client->set_table_select_columns( ai1wm_table_prefix( $site['BlogID'] ) . 'options', $column_names );
				}

				// Set table prefix columns
				$db_client->set_table_prefix_columns( ai1wm_table_prefix( $site['BlogID'] ) . 'options', array( 'option_name' ) );
			}

			$old_table_prefixes = $old_column_prefixes = array();
			$new_table_prefixes = $new_column_prefixes = array();

			// Set table prefixes
			foreach ( ai1wmme_get_sites( $params ) as $site ) {
				if ( ai1wm_is_mainsite( $site['BlogID'] ) === false ) {
					$old_table_prefixes[] = $old_column_prefixes[] = ai1wm_table_prefix( $site['BlogID'] );
					$new_table_prefixes[] = $new_column_prefixes[] = ai1wm_servmask_prefix( $site['BlogID'] );
				}
			}

			// Set table prefixes
			if ( ai1wm_table_prefix() ) {
				$old_table_prefixes[] = ai1wm_table_prefix();
				$new_table_prefixes[] = ai1wm_servmask_prefix();
			} else {
				foreach ( $tables as $table_name ) {
					$old_table_prefixes[] = $table_name;
					$new_table_prefixes[] = ai1wm_servmask_prefix() . $table_name;
				}
			}

			// Set column prefixes
			if ( strlen( ai1wm_table_prefix() ) > 1 ) {
				$old_column_prefixes[] = ai1wm_table_prefix();
				$new_column_prefixes[] = ai1wm_servmask_prefix();
			} else {
				foreach ( array( 'user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time' ) as $column_prefix ) {
					$old_column_prefixes[] = ai1wm_table_prefix() . $column_prefix;
					$new_column_prefixes[] = ai1wm_servmask_prefix() . $column_prefix;
				}
			}

			$db_client->set_tables( $tables )
				->set_old_table_prefixes( $old_table_prefixes )
				->set_new_table_prefixes( $new_table_prefixes )
				->set_old_column_prefixes( $old_column_prefixes )
				->set_new_column_prefixes( $new_column_prefixes );
		}

		// Exclude column prefixes
		$db_client->set_reserved_column_prefixes( array( 'wp_force_deactivated_plugins', 'wp_page_for_privacy_policy' ) );

		// Exclude site meta
		$db_client->set_table_where_query( ai1wm_table_prefix() . 'sitemeta', sprintf( "`meta_key` NOT IN ('%s')", AI1WM_ACTIVE_SITEWIDE_PLUGINS ) );

		// Set table prefix columns
		$db_client->set_table_prefix_columns( ai1wm_table_prefix() . 'usermeta', array( 'meta_key' ) );

		// Export database
		if ( $db_client->export( ai1wm_database_path( $params ), $query_offset, $table_index, $table_offset, $table_rows ) ) {

			// Set progress
			Ai1wm_Status::info( __( 'Done exporting multisite database.', AI1WMME_PLUGIN_NAME ) );

			// Unset query offset
			unset( $params['query_offset'] );

			// Unset table index
			unset( $params['table_index'] );

			// Unset table offset
			unset( $params['table_offset'] );

			// Unset table rows
			unset( $params['table_rows'] );

			// Unset total tables count
			unset( $params['total_tables_count'] );

			// Unset completed flag
			unset( $params['completed'] );

		} else {

			// What percent of tables have we processed?
			$progress = (int) ( ( $table_index / $total_tables_count ) * 100 );

			// Set progress
			Ai1wm_Status::info( sprintf( __( 'Exporting multisite database...<br />%d%% complete<br />%s records saved', AI1WMME_PLUGIN_NAME ), $progress, number_format_i18n( $table_rows ) ) );

			// Set query offset
			$params['query_offset'] = $query_offset;

			// Set table index
			$params['table_index'] = $table_index;

			// Set table offset
			$params['table_offset'] = $table_offset;

			// Set table rows
			$params['table_rows'] = $table_rows;

			// Set total tables count
			$params['total_tables_count'] = $total_tables_count;

			// Set completed flag
			$params['completed'] = false;
		}

		return $params;
	}
}
