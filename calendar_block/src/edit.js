/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import {useCallback, useEffect, useRef, useState} from 'react';

import {__} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {useDispatch, useSelect} from "@wordpress/data";
import {useBlockProps, store as blockEditorStore} from '@wordpress/block-editor';
import {RadioControl} from '@wordpress/components';
import {store as coreDataStore} from '@wordpress/core-data';

/**
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
/**
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import TaxonomySelect from "./components/TaxonomySelect";
import ViewSelect from "./components/ViewSelect";
import BoolSwitch from "./components/BoolSwitch";
import DateAndTime from "./components/DateAndTime/DateAndTime";
import OsecEventsFilter from "./components/OsecEventsFilter";
import LimitBy from "./components/LimitBy";
import Reveal from "./components/Reveal";
import DocumentStyleCache from "./components/DocumentStyleCache";

import './editor.scss';

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


	// The block editor renders blocks in an iframe, see DocumentStyleCache.
	const [ownerDocument, setOwnerDocument] = useState(null);
	const blockRef = useCallback((node) => {
		if (node) {
			setOwnerDocument(node.ownerDocument);
		}
	}, []);

	// The header toggles the settings: a click selects the block, a click on the selected
	// block deselects it. The editor selects on pointer down, so the click handler would
	// always see a selected block: remember the state before. Selecting is ours too: a
	// deselected block keeps the focus, and the editor only selects when focus arrives.
	const {clearSelectedBlock, selectBlock} = useDispatch(blockEditorStore);
	const wasSelected = useRef(false);

	const blockProps = useBlockProps({
		className: 'inline-edit-wrapper',
		ref: blockRef,
	});

	return (
		<div {...blockProps}>
			<div
				style={{display: 'flex', background: '#f0f0f0', marginRight: '-12px', marginLeft: '-12px', cursor: 'pointer'}}
				onPointerDownCapture={() => {
					wasSelected.current = isSelected;
				}}
				onClick={() => {
					if (wasSelected.current) {
						clearSelectedBlock();
					} else {
						selectBlock(props.clientId);
					}
				}}
			>
				<div className="dashicon dashicons dashicons-calendar-alt" style={{fontSize: '4.5em', width: 'auto', height: 'auto', marginBottom: '12px', marginLeft: '6px', marginTop: '6px', marginRight: '6px'}}>
					{/*	Calendar icon */}
				</div>
				<p>
					<strong>
						{__(
						'Osec Calendar',
						'open-source-event-calendar'
					)}
					</strong>
					<div className="osec-settings-toggle">
						<a>
							<span
								className={'dashicons dashicons-arrow-down-alt2' + (isSelected ? ' is-open' : '')}
								aria-hidden="true"
							/>
							{isSelected
								? __('Settings close', 'open-source-event-calendar')
								: __('Settings open', 'open-source-event-calendar')}
						</a>
					</div>
				</p>
			</div>

			{settings && (
				<Reveal show={isSelected}>
					<DocumentStyleCache ownerDocument={ownerDocument}>
						<p>
							<strong>{__(
								'View',
								'open-source-event-calendar'
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
						<p>
							<BoolSwitch
								labelText={__(
									'Display view select',
									'open-source-event-calendar'
								)}
								value={attributes.displayViewSwitch}
								onChange={(val) => {
									setAttributes({
										displayViewSwitch: val
									})
								}}
							/>
						</p>

						{/* Also when visitors can switch to the agenda. */}
						<Reveal show={attributes.view === 'agenda' || attributes.displayViewSwitch}>
							<p>
								<strong>{__(
									'Agenda view',
									'open-source-event-calendar'
								)}
								</strong>
							</p>
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
							<p>
								<BoolSwitch
									labelText={__(
										'Keep all events expanded (disables toggler)',
										'open-source-event-calendar'
									)}
									value={attributes.agendaToggle}
									onChange={(val) => {
										setAttributes({
											agendaToggle: val
										})
									}}
								/>
							</p>
						</Reveal>
				<p>
				<strong>{__(
							'Fixed calendar date',
							'open-source-event-calendar'
							)}
							</strong>
							<br />
							<DateAndTime
								id={'fixedDate'}
								labelText={__('Selected date for fixed calendar start time', 'open-source-event-calendar')}
								onChange={(date) => {
									const timestamp = date ? '' + (date.getTime()/1000) : null;
									setAttributes({
										fixedDate: timestamp
									})
								}}
								placeholder={__('Defaults to current day', 'open-source-event-calendar')}
								defaultValue={attributes.fixedDate}
								isRequired={false}
								dateFormat={settings.dateFormat}
							/>
						</p>


						<p>
							<strong>{__(
								'Filters',
								'open-source-event-calendar'
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
								'open-source-event-calendar'
							)}
							</strong>
						</p>
						<p>
							<BoolSwitch
								labelText={__(
									'Display filters',
									'open-source-event-calendar'
								)}
								value={attributes.displayFilters}
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
									'open-source-event-calendar'
								)}
								value={ (attributes.limitBy !== 'days' &&  attributes.displayDateNavigation) }
								disabled={ (attributes.view === 'agenda' && attributes.limitBy === 'days') }
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
									'open-source-event-calendar'
								)}
								value={attributes.displaySubscribe}
								onChange={(val) => {
									setAttributes({
										displaySubscribe: val
									})
								}}
							/>
						</p>
						<RadioControl
							label={__(
								'Print icon',
								'open-source-event-calendar'
							)}
							selected={
								// Blocks saved before printIcon existed only carry displayPrint.
								attributes.printIcon !== 'global'
									? attributes.printIcon
									: (attributes.displayPrint === false ? 'hide' : 'global')
							}
							options={[
								{
									label: __('Use the global setting', 'open-source-event-calendar'),
									value: 'global'
								},
								{label: __('Show', 'open-source-event-calendar'), value: 'show'},
								{label: __('Hide', 'open-source-event-calendar'), value: 'hide'},
							]}
							onChange={(val) => {
								setAttributes({
									printIcon: val,
									displayPrint: true
								})
							}}
						/>
					</DocumentStyleCache>
				</Reveal>
			)}
		</div>
	);
}

/**
 * Sloppy transform fixed set of PHP date formats into JS equivalents.
 *
 * @type {{s: *, const: {d: string, m: string}, let: string}}
 */
const transformDateInputFormat = (inputformat) => {
	const replaceMe = {
		'm': 'mm',
		'd': 'dd'
	};
	return inputformat.replace(/[abc]/g, m => replaceMe[m]);
}
