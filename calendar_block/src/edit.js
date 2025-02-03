/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import {__} from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {useBlockProps} from '@wordpress/block-editor';


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
import {useSelect, select} from "@wordpress/data";
import {store as coreDataStore} from '@wordpress/core-data';
import React, {useState} from 'react';
import { store as blockEditorStore } from '@wordpress/block-editor';

import './components/TaxonomySelect';
import TaxonomySelect from "./components/TaxonomySelect";
import ViewSelect from "./components/ViewSelect";
import BoolSwitch from "./components/BoolSwitch";
import OsecEventsFilter from "./components/OsecEventsFilter";

export default function Edit(props) {
	console.log({props})
	const {attributes, setAttributes, isSelected, toggleSelection} = props;

	const postType = 'osec_event';
	const taxonomies = useSelect((select) => {
		return select(coreDataStore).getTaxonomies({
			type: postType,
			context: 'embed',
			types: 'view'
		});
	});

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
						'Osec Calendar',
						'all-in-one-event-calendar'
					)}
					{!isSelected && (
						<>
							<br/>
							<small><a style={{cursor: 'pointer'}}>{__(
								'Edit',
								'all-in-one-event-calendar'
							)}</a></small>
						</>
					)}
				</p>
			</div>

			{isSelected && (
				<>
					<p><strong>{__(
						'Display View',
						'all-in-one-event-calendar'
					)}</strong>
					</p>
					<ViewSelect
						defaultValue={attributes.view}
						onChange={(val) => {
							setAttributes({
								view: val
							})
						}}
					/>
					<p>
						<BoolSwitch
							labelText={__(
								'Display view select',
								'all-in-one-event-calendar'
							)}
							defaultValue={attributes.displayViewSwitch}
							onChange={(val) => {
								setAttributes({
									displayViewSwitch: val
								})
							}}
						/>
					</p>

					<p>
						<strong>{__(
							'Filters',
							'all-in-one-event-calendar'
						)}
						</strong>
					</p>

					{(taxonomies) && (
						<>
							{taxonomies.map((taxonomy) => {
								const defaultValue = attributes.taxonomies.filter(t => {
									return t.id === taxonomy.slug
								})
								console.log(defaultValue)

								const defaultValueFinal = (defaultValue && defaultValue[0]) ? defaultValue[0].value : [];
								return (
									<TaxonomySelect
										key={taxonomy.slug}
										defaultValue={defaultValueFinal}
										taxonomy={taxonomy}
										onChange={(val) => {
											const newList = attributes.taxonomies.filter(t => t.id !== taxonomy.slug)
											newList.push(val)
											setAttributes({
												taxonomies: newList
											})
										}}
									/>
								)
							})}
						</>
					)}

					<OsecEventsFilter
						onChange={(array) => {
							console.log('OsecEventsFilter CHANGE', array)
							setAttributes({
								postIds: array
							})
						}}
						defaultValue={attributes.postIds}
					/>
					<p>
						<BoolSwitch
							labelText={__(
								'Display filters',
								'all-in-one-event-calendar'
							)}
							defaultValue={attributes.displayFilters}
							onChange={(val) => {
								setAttributes({
									displayFilters: val
								})
							}}
						/>
					</p>

					<p>
						<BoolSwitch
							labelText={__(
								'Display date navigation',
								'all-in-one-event-calendar'
							)}
							defaultValue={attributes.displayDateNavigation}
							onChange={(val) => {
								setAttributes({
									displayDateNavigation: val
								})
							}}
						/>
					</p>
					<p>
						<BoolSwitch
							labelText={__(
								'Display iCal Feeds',
								'all-in-one-event-calendar'
							)}
							defaultValue={attributes.displaySubscribe}
							onChange={(val) => {
								setAttributes({
									displaySubscribe: val
								})
							}}
						/>
					</p>
				</>
			)}
		</div>
	);
}
