import {useState} from 'react'
import Switch from "react-switch";
import {__} from "@wordpress/i18n";

export default function BoolSwitch({defaultLimitBy, defaultLimit, onChange}) {

	const [limitBy, setLimitBy] = useState(defaultLimitBy);
	const [limit, setLimit] = useState(parseInt(defaultLimit));

	const handleChange = (val) => {
		if ('limitBy' in val) {
			setLimitBy(val.limitBy);
		}
		if ('limit' in val) {
			setLimit(parseInt(val.limit));
		}
		onChange(val);
	}

	const labelText = () => {
		if (limitBy === 'days') {
			return __(
				'Limit by number of days (Disables date navigation)',
				'open source-event-calendar'
			)
		}
		return __(
			'Limit by event count (pager)',
			'open source-event-calendar'
		)
	}
	const labelTextCounter = () => {
		if (limitBy === 'days') {
			return __(
				'days',
				'open source-event-calendar'
			)
		}
		return __(
			'events',
			'open source-event-calendar'
		)
	}

	return (
		<>
			<p>
				<label style={{display: 'flex'}}>
					<Switch
						checked={limitBy === 'days'}
						onChange={(e) => {
							const val = e ? 'days' : 'events';
							handleChange({limitBy: val})
						}}
						uncheckedIcon={false}
						checkedIcon={false}
						onColor="#888888"
						onHandleColor="#ffffff"
					/>
					<span style={{
						display: 'table-cell',
						verticalAlign: 'middle',
						paddingLeft: '1em',
						paddingRight: '1em',
					}}>{labelText()}</span>
				</label>
			</p>
			<p>
				<label style={{
					display: 'flex',
					alignItems: 'center',
				}}>
					<input
						type="number"
						name={'limit'}
						onChange={(e) => {
							const val =Number(e.target.value)
							if(parseInt(val) > 0) {
								handleChange({limit: val})
							}
						}}
						required={true}
						pattern={"\d*"}
						placeholder={defaultLimit}
						min={1}
						max={1000}
						value={ limit }
						style={{
							width: '4.5rem',
							display: 'inline-block',
							fontSize: '1em',
							lineHeight: '2',
							marginRight: '1em'
						}}
						inputMode={'numeric'}
					/>
					{labelTextCounter()}
				</label>
			</p>
		</>
	);
}
