import React, { useState, useEffect, useCallback } from 'react'
import makeAnimated from "react-select/animated";
import Select from 'react-select';

const animatedComponents = makeAnimated();

//
export default function TaxonomySelect ({taxonomy, defaultValue = [], onChange}) {

	const [options, setOptions] = useState([]);
	const [selectedOptions, setSelectedOptions] = useState(defaultValue);
	const url = taxonomy['_links']['wp:items'][0].href;

	const fetchData = useCallback(async()=> {
		const response = await fetch(url);
		if (!response.ok) {
			throw new Error(`Response status: ${response.status}`);
		}
		const terms = await response.json();
		const termsOptions = await terms.map(term => ({
			label: term.name,
			value: term.id,
		}));
		setOptions(
			termsOptions
		);

		const nextSelectedOptions = selectedOptions.map((option, key, options) => {
			if (!option.label) {
				const [label] = terms.filter((term) => {
					return term.id === option
				})

				if (label && label.name) {
					return {
						label: label.name,
						value: option,
					}
				}
				else {
					// Remove the term we don't know.
					return null;
				}
			}
		}).filter(Boolean); // Remove falsy Elements.
		setSelectedOptions(nextSelectedOptions);
	}, [setSelectedOptions])


	useEffect(() => {
		(async () => {
			fetchData()
		})()
	}, [fetchData]);

	const handleChange = (options) => {
		const value = options.map(k => k.value)
		setSelectedOptions(options);
		onChange({
			id: taxonomy.slug,
			value
		});
	}

	// @see https://react-select.com/home
	return (
		<p>
			<label>
				<small><strong>Filter by {taxonomy.name}</strong> ({taxonomy.slug})</small>
			</label>
			<Select
				isMulti
				components={animatedComponents}
				options={options}
				value={selectedOptions}
				onChange={handleChange}
			/>
		</p>
	);
}
