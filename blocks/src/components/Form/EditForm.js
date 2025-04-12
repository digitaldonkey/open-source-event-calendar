import React, {useEffect, useState} from 'react'
import {__} from "@wordpress/i18n";
import Switch from "react-switch";
import ViewSelect from "../ViewSelect";
import LimitBy from "../LimitBy";
import DateAndTime from "../DateAndTime/DateAndTime";
import TaxonomySelect from "../TaxonomySelect";
import OsecEventsFilter from "../OsecEventsFilter";
import BoolSwitch from "../BoolSwitch";
import {useSelect} from "@wordpress/data";
import {store as coreDataStore} from "@wordpress/core-data";


//
export default function EditForm(props) {
	const {attributes, setAttributes} = props;
	const taxonomies = useSelect((select) => {
		return select(coreDataStore).getTaxonomies({
			type: 'osec_event',
			context: 'embed',
			types: 'view'
		});
	});

	return (<>
			<p>
				<strong>
					{__('View', 'open source-event-calendar')}
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

			{attributes.view === 'agenda' && (<LimitBy
					defaultLimitBy={attributes.limitBy}
					defaultLimit={attributes.limit}
					onChange={(obj) => {
						if (obj.limitBy === 'days') {
							setAttributes({displayDateNavigation: false})
						}
						setAttributes(obj)
					}}
				/>)}
			<p>
				<strong>
					{__('Fixed calendar date', 'open source-event-calendar')}
				</strong>
				<br/>
				<DateAndTime
					id={'fixedDate'}
					labelText={'Selected date for fixed calendar start time'}
					onChange={(date) => {
						const timestamp = date ? '' + (date.getTime() / 1000) : null;
						setAttributes({
							fixedDate: timestamp
						})
					}}
					placeholder={'Defaults to current day'}
					defaultValue={attributes.fixedDate}
					isRequired={false}
					dateFormat={props.settings.dateFormat}
				/>
			</p>


			<p>
				<strong>
					{__('Filters', 'open source-event-calendar')}
				</strong>
			</p>

			{(taxonomies) && (<>
					{taxonomies.map((taxonomy) => {
						const defaultValue = attributes.taxonomies.filter(t => {
							return t.id === taxonomy.slug
						})
						const defaultValueFinal = (defaultValue && defaultValue[0]) ? defaultValue[0].value : [];
						return (<TaxonomySelect
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
							/>)
					})}
				</>)}

			<OsecEventsFilter
				onChange={(array) => {
					setAttributes({
						postIds: array
					})
				}}
				defaultValue={attributes.postIds}
			/>
			<p>
				<strong>
					{__('View settings', 'open source-event-calendar')}
				</strong>
			</p>
			<p>
				<BoolSwitch
					labelText={__('Display filters', 'open source-event-calendar')}
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
					labelText={__('Display view select', 'open source-event-calendar')}
					value={attributes.displayViewSwitch}
					onChange={(val) => {
						setAttributes({
							displayViewSwitch: val
						})
					}}
				/>
			</p>
			<p>
				<BoolSwitch
					labelText={__('Display date navigation', 'open source-event-calendar')}
					value={(attributes.limitBy !== 'days' && attributes.displayDateNavigation)}
					disabled={(attributes.view === 'agenda' && attributes.limitBy === 'days')}
					onChange={(val) => {
						setAttributes({
							displayDateNavigation: val
						})
					}}
				/>
			</p>
			<p>
				<BoolSwitch
					labelText={__('Display iCal Feeds', 'open source-event-calendar')}
					value={attributes.displaySubscribe}
					onChange={(val) => {
						setAttributes({
							displaySubscribe: val
						})
					}}
				/>
			</p>
		</>);
}
