import {__} from '@wordpress/i18n';

import BlockEdit from '../components/Form/BlockEdit';

/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 */
export default function Edit(props) {
	return (
		<BlockEdit
			{...props}
			title={__('Osec Calendar Big Calendar', 'open source-event-calendar')}
		/>
	);
}
