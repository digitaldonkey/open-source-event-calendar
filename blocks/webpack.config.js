// Import the @wordpress/scripts config.
// Import the utility to auto-generate the entry points in the src directory.
const defaultConfig =   require('@wordpress/scripts/config/webpack.config');
const { getWebpackEntryPoints } = require( '@wordpress/scripts/utils/config' )
const CopyWebpackPlugin = require('copy-webpack-plugin');

/**
 * Uses default Wp-Blocks webpack config
 *   and copy dayjs locales over for frontend loading.
 */
module.exports = {
	// Spread the existing WordPress config.
	...defaultConfig,
	...getWebpackEntryPoints,
	plugins : [
		...defaultConfig.plugins,
		new CopyWebpackPlugin({
			patterns : [
				// Add dayjs locales for frontend loading.
				{
					from: "dayjs/locale",
					to: "react-big-calendar/dayjs-locales",
					context: 'node_modules'
				}
			],
		}),
	],
}

