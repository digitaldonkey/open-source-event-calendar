/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */

console.log({metadata})

registerBlockType( metadata.name, {
	title: metadata.title,
	category: metadata.category,
	icon: metadata.icon,
	description: metadata.description,
	supports: metadata.supports,
	attributes: metadata.attributes,
	attributesIformalOnly: {
		view: {
			// General form: Some Other view
			//   [osec view="someother"]
			// monthly, weekly, oneday, agenda
			type: "string",
			default: 'agenda',
			enum: [
				'monthly',
				'weekly',
				'oneday',
				'agenda',
			]
		},
		taxonomies: {
			// Filter by event category names/slugs (separate names by comma)
			//   [osec cat_name="Lunar Cycles,zodiac-date-ranges"]
			// [{id: <tax_name>, values: ['term-slug1', 'term-slug2']}]
			default: [],
			type: 'array',

		},
		postIds: {
			// Filter by post IDs (separate IDs by comma): [osec post_id="1,2"]
		},
		limit: {
			// Limit number of events per page
			//   [osec events_limit="5"]
		},
		limitBy: {
			//  Limit by events, days ?
		},
		displaySubscribe: {
			// Show the subscribe button in the widget
		}
	},
	/**
	 * @see ./edit.js
	 */
	edit: Edit
} );

