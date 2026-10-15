import React, {useState} from 'react'
import Select from "react-select";
import makeAnimated from "react-select/animated";
import {__} from "@wordpress/i18n";

const animatedComponents = makeAnimated();

//
export default function ViewSelect ({defaultValue = [], onChange}) {

	const [options, setOptions] = useState([
		{
			label: __("Agenda", 'open-source-event-calendar'),
			value: "agenda"
		},
		{
			label: __("Monthly", 'open-source-event-calendar'),
			value: "month"
		},
		{
			label: __("Weekly", 'open-source-event-calendar'),
			value: "week"
		},
		{
			label: __("One Day", 'open-source-event-calendar'),
			value: "oneday"
		},
	]);
	const [selectedOptions, setSelectedOptions] = useState(
		options.filter(o => o.value === defaultValue)
	);

	const handleChange = (options) => {
		// const value = options.map(k => k.value)
		setSelectedOptions(options);
		onChange(options.value);
	}

	// @see https://react-select.com/home
	return (
		<Select
			options={options}
			value={selectedOptions}
			onChange={handleChange}
			components={animatedComponents}
		/>
	);
}
