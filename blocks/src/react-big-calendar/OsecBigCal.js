import React, { useMemo } from 'react';
import { Calendar, dayjsLocalizer } from 'react-big-calendar';
import dayjs from "dayjs";
import weekday from 'dayjs/plugin/weekday';
import timezone from 'dayjs/plugin/timezone'
import './view.scss';


const initDayJs = (localeId) => {
	/**
	 * Note that the dayjsLocalizer extends Day.js with the following plugins:
	 *  IsBetween
	 * 	IsSameOrAfter
	 * 	IsSameOrBefore
	 * 	LocaleData
	 * 	LocalizedFormat
	 * 	MinMax
	 * 	UTC
	 */
	dayjs.extend(weekday);
	dayjs.extend(timezone);
	if (localeId !== 'en') {
		dayjs.locale(localeId)
	}
}

/**
 * Maybe we won't need to do this if we have a dedicated Form later.
 * For now: Here is the place if Edit Form props and calendar props do not match.
 * @param props
 * @returns {*}
 */
const transformProps = (props) => {
	// View name "oneday" matches "day".
	if (props.view) {
		props.view === 'oneday' ? 'day' : props.view
	}
	if (props.fixedDate) {
		props.fixedDate = dayjs.unix(parseInt(props.fixedDate)).valueOf();
	}
	return props;
}

const firstDayVisible = (defaultDate = null, view) => {
	const date = Object.prototype.toString.call(defaultDate) === '[object Date]' ? dayjs(defaultDate) : dayjs();
	switch (view) {
		case 'month':
			const firstInMonth = date.startOf('month').startOf('week').toDate();
			// console.log(firstInMonth,`First day of MONTH`)
			return firstInMonth;
		case 'week':
				// console.log(date.startOf('week').toDate(),`First day of WEEK`)
				return date.startOf('week').toDate();
		case 'agenda':
		case 'day':
		default:
				return date.toDate()
	}
}

export default function OsecBigCal(props) {
	console.log(props, 'props@OsecBigCal')

	const { fixedDate, view} = transformProps(props);
	const localeId = props.locale.name;
	initDayJs(localeId);
	const localizer = dayjsLocalizer(dayjs);

	const getDefaultDate = (fixedDate = null) => {
		if (fixedDate) {
			return dayjs.unix(parseInt(fixedDate)).toDate();
		}
		return dayjs().toDate(); // == new Date()
	}

	const firstDayInView = firstDayVisible(fixedDate, view);
	// fixedDate should be relative to the
	// timespan start (e.g 1 Week Day at Day/Month views. Not on agenda).
	// Like firstVisibleDay to query Events.
	console.log({view, date: getDefaultDate(), firstDayInView})

	const { defaultDate, getNow, myEvents, scrollToTime } =
		useMemo(() => {
			return {
				defaultDate: getDefaultDate(fixedDate),
				getNow: () => dayjs().toDate(),
				localizer,
				// myEvents: [...events],
				// scrollToTime: DateTime.local().toJSDate(),
			}
		});

	// Just to have a temporary date for now.
	const todaysEventStart = new Date();
	const todaysEventEnd = dayjs(todaysEventStart).add(1, 'hour').toDate();

	return (
		<>
			<Calendar
				localizer={localizer}
				events={[
					{
						title: 'My nice Event',
						start: todaysEventStart,
						end: todaysEventEnd,
						allDay: false,
						resource: 'any',
					}
				]}
				// date={  }
				getNow={getNow}
				endAccessor="end"
				style={{ height: '100vh' }}
				defaultView={view}
			/>
		</>
	)
}
