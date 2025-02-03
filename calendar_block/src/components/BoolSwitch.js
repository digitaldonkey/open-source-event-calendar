import React, {useState} from 'react'
import Select from "react-select";
import {__} from "@wordpress/i18n";
import Switch from "react-switch";


//
export default function BoolSwitch ({
	defaultValue = true,
	onChange,
	labelText
}) {

	const [checked, setSetChecked] = useState(defaultValue);

	const handleChange = (checked) => {
		console.log(checked, 'onCHANGE checked')
		setSetChecked(checked);
		onChange(checked);
	}

	// @see https://react-select.com/home
	return (
		<label>
			<Switch
				onChange={handleChange}
				checked={checked}
			/>
			<span style={{
				display: 'table-cell',
				verticalAlign: 'middle',
				paddingLeft: '.5em'
			}}>{labelText}</span>
		</label>
	);
}
