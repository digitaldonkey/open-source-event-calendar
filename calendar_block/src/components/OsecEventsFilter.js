import React, {useCallback, useEffect, useState} from 'react'
import AsyncSelect from 'react-select/async';
import {__} from "@wordpress/i18n";
import makeAnimated from "react-select/animated";
import apiFetch from '@wordpress/api-fetch';
import {decodeEntities} from '@wordpress/html-entities';

const animatedComponents = makeAnimated();

export default function OsecEventsFilter ({defaultValue = [], onChange}) {

	const [selectedOptions, setSelectedOptions] = useState([]);

	// Get the defaultValues from IDs
	// .../wp-json/wp/v2/osec_event?include=185,225
	const fetchData = useCallback(async()=> {
		if (defaultValue && defaultValue.length) {
			const url =  'wp/v2/osec_event?include=' + defaultValue.join(',');
			const events = await apiFetch( { path:url } );
			const labels = events.map(e =>{
				return { value: e.id, label: decodeEntities(e.title.rendered) }
			})
			setSelectedOptions(labels);
		}
	}, [setSelectedOptions])

	useEffect(() => {
		(async () => {
			fetchData()
		})()
	}, [fetchData]);


	const handleChange = (options) => {
		const value = options.map(k => k.value)
		setSelectedOptions(options);
		onChange(value);
	}

	const promiseOptions = (inputValue) => {
		// TODO
		//  This URL is not subdir capable
		// /wp-json/wp/v2/osec_event?search=
		return apiFetch( { path: '/wp/v2/osec_event?search=' + encodeURIComponent(inputValue) } )
			.then( ( events ) => {
				return events.map(e =>{
					return { value: e.id, label: decodeEntities(e.title.rendered) }
			});
		} );
	};

	// Labels are decoded titles: "&lt;b&gt;" is "<b>" now. Only ever render them as text.
	// @see https://react-select.com/home
	return (
		<p>
			<label>
				<small><strong>{__('Filter by Events', 'open-source-event-calendar')}</strong></small>
			</label>
			<AsyncSelect
				isMulti={true}
				loadOptions={promiseOptions}
				components={animatedComponents}
				cacheOptions
				value={selectedOptions}
				placeholder={<div>{__('Type to search events', 'open-source-event-calendar')}</div>}
				onChange={handleChange}
			/>
		</p>
	);
}
