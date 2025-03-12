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

class Ai1wmme_Import_Users {

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
			Ai1wm_Status::info( __( 'Preparing users...', AI1WMME_PLUGIN_NAME ) );

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

				// Get base prefix
				$base_prefix = ai1wm_table_prefix();

				// Get mainsite prefix
				$mainsite_prefix = ai1wm_table_prefix( 'mainsite' );

				// Insert users
				$db_client->query(
					"INSERT INTO
						{$base_prefix}users (
							user_login,
							user_pass,
							user_nicename,
							user_email,
							user_url,
							user_registered,
							user_activation_key,
							user_status,
							display_name
						)
					SELECT
						mu.user_login,
						mu.user_pass,
						mu.user_nicename,
						mu.user_email,
						mu.user_url,
						mu.user_registered,
						mu.user_activation_key,
						mu.user_status,
						mu.display_name
					FROM
						{$mainsite_prefix}users AS mu
					LEFT JOIN
						{$base_prefix}users AS u ON (CONVERT(mu.user_login USING utf8) = CONVERT(u.user_login USING utf8))
					WHERE
						u.ID IS NULL"
				);

				$tables = $db_client->get_tables();

				// Update posts and comments
				foreach ( $blogs as $blog ) {

					// Get table prefix
					$table_prefix = ai1wm_table_prefix( $blog['New']['BlogID'] );

					// Update WordPress comments
					if ( in_array( "{$table_prefix}comments", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}comments AS c
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (c.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								c.user_id = u.ID"
						);
					}

					// Update WordPress posts
					if ( in_array( "{$table_prefix}posts", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}posts AS p
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (p.post_author = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								p.post_author = u.ID"
						);
					}

					// Update AutomateWoo abandoned carts
					if ( in_array( "{$table_prefix}automatewoo_abandoned_carts", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}automatewoo_abandoned_carts AS aac
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (aac.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								aac.user_id = u.ID"
						);
					}

					// Update AutomateWoo customers
					if ( in_array( "{$table_prefix}automatewoo_customers", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}automatewoo_customers AS ac
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (ac.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								ac.user_id = u.ID"
						);
					}

					// Update Gravity Forms entry notes
					if ( in_array( "{$table_prefix}gf_entry_notes", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}gf_entry_notes AS gen
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (gen.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								gen.user_id = u.ID"
						);
					}

					// Update MailPoet subscribers
					if ( in_array( "{$table_prefix}mailpoet_subscribers", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}mailpoet_subscribers AS ms
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (ms.wp_user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								ms.wp_user_id = u.ID"
						);
					}

					// Update MailPoet user flags
					if ( in_array( "{$table_prefix}mailpoet_user_flags", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}mailpoet_user_flags AS muf
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (muf.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								muf.user_id = u.ID"
						);
					}

					// Update WooCommerce CRM customer list
					if ( in_array( "{$table_prefix}wc_crm_customer_list", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}wc_crm_customer_list AS wccl
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wccl.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wccl.user_id = u.ID"
						);
					}

					// Update WooCommerce CRM log
					if ( in_array( "{$table_prefix}wc_crm_log", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}wc_crm_log AS wcl
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wcl.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wcl.user_id = u.ID"
						);
					}

					// Update WooCommerce download log
					if ( in_array( "{$table_prefix}wc_download_log", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}wc_download_log AS wdl
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wdl.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wdl.user_id = u.ID"
						);
					}

					// Update WooCommerce web hooks
					if ( in_array( "{$table_prefix}wc_webhooks", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}wc_webhooks AS wwh
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wwh.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wwh.user_id = u.ID"
						);
					}

					// Update WooCommerce API keys
					if ( in_array( "{$table_prefix}woocommerce_api_keys", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}woocommerce_api_keys AS wak
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wak.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wak.user_id = u.ID"
						);
					}

					// Update WooCommerce downloadable product permissions
					if ( in_array( "{$table_prefix}woocommerce_downloadable_product_permissions", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}woocommerce_downloadable_product_permissions AS wdpp
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wdpp.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wdpp.user_id = u.ID"
						);
					}

					// Update WooCommerce payment tokens
					if ( in_array( "{$table_prefix}woocommerce_payment_tokens", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}woocommerce_payment_tokens AS wpt
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (wpt.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								wpt.user_id = u.ID"
						);
					}

					// Update BuddyPress activity
					if ( in_array( "{$table_prefix}bp_activity", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_activity AS bpa
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpa.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpa.user_id = u.ID"
						);
					}

					// Update BuddyPress document
					if ( in_array( "{$table_prefix}bp_document", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_document AS bpd
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpd.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpd.user_id = u.ID"
						);
					}

					// Update BuddyPress document folder
					if ( in_array( "{$table_prefix}bp_document_folder", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_document_folder AS bpdf
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpdf.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpdf.user_id = u.ID"
						);
					}

					// Update BuddyPress follow (Leader ID)
					if ( in_array( "{$table_prefix}bp_follow", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_follow AS bpf
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpf.leader_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpf.leader_id = u.ID"
						);
					}

					// Update BuddyPress follow (Follower ID)
					if ( in_array( "{$table_prefix}bp_follow", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_follow AS bpf
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpf.follower_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpf.follower_id = u.ID"
						);
					}

					// Update BuddyPress friends (Initiator ID)
					if ( in_array( "{$table_prefix}bp_friends", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_friends AS bpf
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpf.initiator_user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpf.initiator_user_id = u.ID"
						);
					}

					// Update BuddyPress friends (Friend ID)
					if ( in_array( "{$table_prefix}bp_friends", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_friends AS bpf
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpf.friend_user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpf.friend_user_id = u.ID"
						);
					}

					// Update BuddyPress groups
					if ( in_array( "{$table_prefix}bp_groups", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_groups AS bpg
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpg.creator_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpg.creator_id = u.ID"
						);
					}

					// Update BuddyPress groups members (User ID)
					if ( in_array( "{$table_prefix}bp_groups_members", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_groups_members AS bpgm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpgm.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpgm.user_id = u.ID"
						);
					}

					// Update BuddyPress groups members (Inviter ID)
					if ( in_array( "{$table_prefix}bp_groups_members", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_groups_members AS bpgm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpgm.inviter_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpgm.inviter_id = u.ID"
						);
					}

					// Update BuddyPress invitations (User ID)
					if ( in_array( "{$table_prefix}bp_invitations", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_invitations AS bpgm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpgm.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpgm.user_id = u.ID"
						);
					}

					// Update BuddyPress invitations (Inviter ID)
					if ( in_array( "{$table_prefix}bp_invitations", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_invitations AS bpgm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpgm.inviter_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpgm.inviter_id = u.ID"
						);
					}

					// Update BuddyPress media
					if ( in_array( "{$table_prefix}bp_media", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_media AS bpm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpm.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpm.user_id = u.ID"
						);
					}

					// Update BuddyPress media albums
					if ( in_array( "{$table_prefix}bp_media_albums", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_media_albums AS bpma
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpma.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpma.user_id = u.ID"
						);
					}

					// Update BuddyPress messages messages
					if ( in_array( "{$table_prefix}bp_messages_messages", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_messages_messages AS bpmm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpmm.sender_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpmm.sender_id = u.ID"
						);
					}

					// Update BuddyPress messages recipients
					if ( in_array( "{$table_prefix}bp_messages_recipients", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_messages_recipients AS bpmr
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpmr.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpmr.user_id = u.ID"
						);
					}

					// Update BuddyPress notifications
					if ( in_array( "{$table_prefix}bp_notifications", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_notifications AS bpn
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpn.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpn.user_id = u.ID"
						);
					}

					// Update BuddyPress user blogs
					if ( in_array( "{$table_prefix}bp_user_blogs", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_user_blogs AS bpub
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpub.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpub.user_id = u.ID"
						);
					}

					// Update BuddyPress xprofile data
					if ( in_array( "{$table_prefix}bp_xprofile_data", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_xprofile_data AS bpxd
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpxd.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpxd.user_id = u.ID"
						);
					}

					// Update BuddyPress zoom meetings
					if ( in_array( "{$table_prefix}bp_zoom_meetings", $tables ) ) {
						$db_client->query(
							"UPDATE
								{$table_prefix}bp_zoom_meetings AS bpzm
							INNER JOIN
								{$mainsite_prefix}users AS mu ON (bpzm.user_id = mu.ID)
							INNER JOIN
								{$base_prefix}users AS u ON (CONVERT(u.user_login USING utf8) = CONVERT(mu.user_login USING utf8))
							SET
								bpzm.user_id = u.ID"
						);
					}
				}
			}

			// Set progress
			Ai1wm_Status::info( __( 'Done preparing users.', AI1WMME_PLUGIN_NAME ) );
		}

		return $params;
	}
}
