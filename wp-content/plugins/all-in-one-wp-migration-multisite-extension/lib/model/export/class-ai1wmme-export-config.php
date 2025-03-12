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

class Ai1wmme_Export_Config {

	public static function execute( $params ) {

		// Set progress
		Ai1wm_Status::info( __( 'Preparing multisite configuration file...', AI1WMME_PLUGIN_NAME ) );

		$config = array();

		// Set network
		if ( isset( $params['options']['sites'] ) ) {
			$config['Network'] = false;
		} else {
			$config['Network'] = true;
		}

		// Set networks
		$config['Networks'] = ai1wmme_get_networks();

		// Set subsites
		foreach ( ai1wmme_get_sites( $params ) as $site ) {
			switch_to_blog( $site['BlogID'] );

			// Add site details
			$config['Sites'][] = array(
				'BlogID'     => (int) $site['BlogID'],
				'SiteID'     => (int) $site['SiteID'],
				'LangID'     => (int) $site['LangID'],
				'SiteURL'    => get_site_url( $site['BlogID'] ),
				'HomeURL'    => get_home_url( $site['BlogID'] ),
				'Domain'     => $site['Domain'],
				'Path'       => $site['Path'],
				'Plugins'    => array_values( array_diff( ai1wm_active_plugins(), ai1wm_active_servmask_plugins() ) ),
				'Template'   => ai1wm_active_template(),
				'Stylesheet' => ai1wm_active_stylesheet(),
				'Uploads'    => get_option( 'upload_path' ),
				'UploadsURL' => get_option( 'upload_url_path' ),
				'WordPress'  => array(
					'Uploads'    => ai1wm_get_uploads_dir(),
					'UploadsURL' => ai1wm_get_uploads_url(),
				),
			);

			restore_current_blog();
		}

		// Set plugin version
		$config['Plugin'] = array( 'Version' => AI1WMME_VERSION );

		// Set active plugins
		$config['Plugins'] = array_values( array_diff( ai1wm_active_sitewide_plugins(), ai1wm_active_servmask_plugins() ) );

		// Set super admins
		$config['Admins'] = array_values( get_super_admins() );

		// Save multisite.json file
		$handle = ai1wm_open( ai1wm_multisite_path( $params ), 'w' );
		ai1wm_write( $handle, json_encode( $config ) );
		ai1wm_close( $handle );

		// Set progress
		Ai1wm_Status::info( __( 'Done preparing multisite configuration file.', AI1WMME_PLUGIN_NAME ) );

		return $params;
	}
}
