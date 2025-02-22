import React, {useEffect, useState} from 'react'
import {__} from "@wordpress/i18n";
import Switch from "react-switch";


//
export default function BoolSwitch ({
	defaultValue = true,
	value,
	onChange,
	labelText,
	disabled,

}) {

	const [checked, setChecked] = useState(defaultValue);
	useEffect((e) => {
		setChecked(value);
	}, [value]);

	const handleChange = (checked) => {
		setChecked(checked);
		onChange(checked);
	}

	return (
		<label>
			<Switch
				onChange={handleChange}
				checked={checked}
				disabled={disabled}
			/>
			<span style={{
				display: 'table-cell',
				verticalAlign: 'middle',
				paddingLeft: '.5em'
			}}>{labelText}</span>
		</label>
	);
}
