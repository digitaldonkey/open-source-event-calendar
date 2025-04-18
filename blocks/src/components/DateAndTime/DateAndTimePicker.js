import React, {useState} from 'react'
import DatePicker from "react-datepicker";
import "./scss/datepicker.scss";
export default function DateAndTimePicker ({
	defaultValue,
	onChange,
	labelText,
	placeholder,
 	isRequired,
	dateFormat,
	id
}) {
	const defaultDate = defaultValue ? new Date(parseInt(defaultValue) * 1000) : null;
	const [ date, setDate ] = useState(defaultDate);
	const handleChange = (checked) => {
		setDate(checked);
		onChange(checked);
	}
	// @see https://reactdatepicker.com/
	return (
		<p>
			<DatePicker
				id={id}
				placeholderText={placeholder}
				selected={date}
				closeOnScroll={(e) => e.target === document}
				onChange={handleChange}
				showIcon
				isClearable={!isRequired}
				showYearDropdown
				toggleCalendarOnIconClick
				showMonthDropdown
				dateFormat={dateFormat.inputDateFormat}
				calendarStartDay={dateFormat.weekStart}
				yearDropdownItemNumber={15}
				scrollableYearDropdown
				popperPlacement="bottom"
				// withPortal={ 400 > Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0) }
				changeYear
				changeMonth
				decreaseMonth
				increaseMonth
				todayButton="Today"

				icon={<svg version="1.1"
						   xmlns="http://www.w3.org/2000/svg"
						   xmlnsXlink="http://www.w3.org/1999/xlink"
						   width="20"
						   height="20"
						   viewBox="0 0 20 20"
				>
					<path
						d="M15 4h3v15h-16v-15h3v-1q0-0.6 0.44-1.060 0.44-0.44 1.060-0.44t1.060 0.44q0.44 0.46 0.44 1.060v1h4v-1q0-0.6 0.44-1.060 0.44-0.44 1.060-0.44t1.060 0.44q0.44 0.46 0.44 1.060v1zM6 3v2.5q0 0.21 0.15 0.36 0.14 0.14 0.35 0.14t0.35-0.14q0.15-0.15 0.15-0.36v-2.5q0-0.22-0.15-0.35-0.13-0.15-0.35-0.15t-0.35 0.15q-0.15 0.13-0.15 0.35zM13 3v2.5q0 0.2 0.14 0.36 0.16 0.14 0.36 0.14t0.36-0.14q0.14-0.16 0.14-0.36v-2.5q0-0.21-0.14-0.35-0.15-0.15-0.36-0.15t-0.36 0.15q-0.14 0.14-0.14 0.35zM17 18v-10h-14v10h14zM7 9v2h-2v-2h2zM9 9h2v2h-2v-2zM13 11v-2h2v2h-2zM7 12v2h-2v-2h2zM9 12h2v2h-2v-2zM13 14v-2h2v2h-2zM7 15v2h-2v-2h2zM11 17h-2v-2h2v2zM15 17h-2v-2h2v2z"
						fill="#7f7f7f">
					</path>
				</svg>}
				popperModifiers={{
					preventOverflow: {
						enabled: true,
					},
				}}
			/>
			<label htmlFor={id}>
				<small>{labelText}</small>
			</label>
		</p>
	);
}
