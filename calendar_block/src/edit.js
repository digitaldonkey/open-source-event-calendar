/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import {__} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {useSelect} from "@wordpress/data";
import {useBlockProps} from '@wordpress/block-editor';

/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
/**
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

import {store as coreDataStore} from '@wordpress/core-data';
import {useCallback, useEffect, useState} from 'react';

import TaxonomySelect from "./components/TaxonomySelect";
import ViewSelect from "./components/ViewSelect";
import BoolSwitch from "./components/BoolSwitch";
import DateAndTime from "./components/DateAndTime/DateAndTime";
import OsecEventsFilter from "./components/OsecEventsFilter";
import LimitBy from "./components/LimitBy";

/**
 * Edit()
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit(props) {
	const {attributes, setAttributes, isSelected, toggleSelection} = props;
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

			{isSelected && (
				<>
					<p>
						<strong>{__(
							'View',
							'open source-event-calendar'
						)}
						</strong>
						<br/>
						<ViewSelect
							defaultValue={attributes.view}
							onChange={(val) => {
								setAttributes({
									view: val
								})
							}}
						/>
					</p>

					{attributes.view === 'agenda' && (
						<LimitBy
							defaultLimitBy={attributes.limitBy}
							defaultLimit={attributes.limit}
							onChange={(obj) => {
								if (obj.limitBy === 'days') {
									setAttributes({displayDateNavigation: false})
								}
								setAttributes(obj)
							}}
						/>
					)}
			<p>
			<strong>{__(
						'Fixed calendar date',
						'open source-event-calendar'
						)}
						</strong>
						<br />
						<DateAndTime
							id={'fixedDate'}
							labelText={'Selected date for fixed calendar start time'}
							onChange={(date) => {
								const timestamp = date ? '' + (date.getTime()/1000) : null;
								setAttributes({
									fixedDate: timestamp
								})
							}}
							placeholder={'Defaults to current day'}
							defaultValue={attributes.fixedDate}
							isRequired={false}
							dateFormat={settings.dateFormat}
						/>
					</p>


					<p>
						<strong>{__(
							'Filters',
							'open source-event-calendar'
						)}
						</strong>
					</p>

					{(taxonomies) && (
						<>
							{taxonomies.map((taxonomy) => {
								const defaultValue = attributes.taxonomies.filter(t => {
									return t.id === taxonomy.slug
								})
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
							setAttributes({
								postIds: array
							})
						}}
						defaultValue={attributes.postIds}
					/>
					<p>
						<strong>{__(
							'View settings',
							'open source-event-calendar'
						)}
						</strong>
					</p>
					<p>
						<BoolSwitch
							labelText={__(
								'Display filters',
								'open source-event-calendar'
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
								'Display view select',
								'open source-event-calendar'
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
						<BoolSwitch
							labelText={__(
								'Display date navigation',
								'open source-event-calendar'
							)}
							value={ (attributes.limitBy !== 'days' &&  attributes.displayDateNavigation) }
							disabled={ attributes.limitBy === 'days' }
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
								'open source-event-calendar'
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
