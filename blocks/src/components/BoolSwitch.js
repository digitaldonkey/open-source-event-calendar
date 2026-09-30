import React, {useEffect, useState} from 'react'
import {ToggleControl} from '@wordpress/components';


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
		<ToggleControl
			__nextHasNoMarginBottom
			label={labelText}
			checked={checked}
			disabled={disabled}
			onChange={handleChange}
		/>
	);
}
