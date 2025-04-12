/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import {useCallback, useEffect, useState} from 'react';

import {__} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {useBlockProps} from '@wordpress/block-editor';

import EditForm from "../components/Form/EditForm";

/**
 * Edit()
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit(props) {
	const { isSelected } = props;
	const [settings, setSettings] = useState();

	const fetchSettings = useCallback(async()=> {
		const url =  'osec/v1/settings';
		const fetched = await apiFetch( { path:url } );
		setSettings(fetched);

	}, [setSettings])

	useEffect(() => {
		(async () => {
			await fetchSettings()
		})()
	}, [fetchSettings]);

	const blockProps = useBlockProps({
		className: 'inline-edit-wrapper',
	});

	return (
		<div {...blockProps}>
			<div
				style={{display: 'flex', background: '#f0f0f0', marginRight: '-12px', marginLeft: '-12px'}}
				onClick={( event ) => {
					// TODO: How to close/isSelected=false block?
					//   Maybe @see https://stackoverflow.com/a/56619556/308533
				}}
			>
				<div className="dashicon dashicons dashicons-calendar-alt" style={{fontSize: '4.5em', width: 'auto', height: 'auto', marginBottom: '12px'}}>
					{/*	Calendar icon */}
				</div>
				<p>
					{__(
						'Osec Calendar Classic',
						'open source-event-calendar'
					)}
					{!isSelected && (
						<>
							<br/>
							<small><a style={{cursor: 'pointer'}}>{__(
								'Edit',
								'open source-event-calendar'
							)}</a></small>
						</>
					)}
				</p>
			</div>

			{isSelected && settings && (
				<EditForm settings={settings} {...props} />
			)}
		</div>
	);
}
