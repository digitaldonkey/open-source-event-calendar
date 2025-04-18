import {useEffect, useState, useMemo} from 'react';
import { Calendar, luxonLocalizer } from 'react-big-calendar'
import { DateTime, Settings } from 'luxon'
import './view.scss';



/**
 * Maybe we won't need to do this if we have a dedicated Form later.
 * For now: Here is the place if Edit Form props and calendar props do not match.
 * @param attributes
 * @returns {*}
 */
const transformAttributes = (attributes) => {
	// View name "oneday" matches "day".
	if (attributes.view) {
		attributes.view === 'oneday' ? 'day' : attributes.view
	}
	if (attributes.fixedDate) {
		attributes.fixedDate = DateTime.fromSeconds(parseInt(attributes.fixedDate)).toJSDate();
	}
	return attributes;
}

const firstDayVisible = (date, view, firstDayOfWeek) => {
	let dateVal = Object.prototype.toString.call(date) === '[object Date]' ? date : new Date();

	const localWeekday = DateTime.fromJSDate(dateVal);
	console.log({localWeekday, date}, 'firstDayVisible')
	switch (view) {
		case 'month':
		case 'week':
			dateVal = DateTime.fromJSDate(dateVal).startOf('week', {firstDayOfWeek: firstDayOfWeek, useLocaleWeeks: true}).toJSDate();
			break;
		case 'agenda':
		case 'day':
		default:
			dateVal = DateTime.fromJSDate(dateVal).startOf('day', {firstDayOfWeek: firstDayOfWeek, useLocaleWeeks: true}).toJSDate();
			break;
	}
	console.log({dateVal, date, view})
	return dateVal;
}

export default function OsecBigCal(props) {
	// console.log(props)
	const { fixedDate, view } = transformAttributes(props);

	const firstDayOfWeek = 7;

	console.log(Settings)
	// Settings.defaultWeekSettings = {
	// 	firstDay: firstDayOfWeek,
	// 	minimalDays: 4,
	// 	weekend: [6, 7],
	// };

	const firstDayInView = firstDayVisible(fixedDate, view, firstDayOfWeek);
	// fixedDate should be relative to the
	// timespan start (e.g 1 Week Day at Day/Month views. Not on agenda).
	// Like firstVisibleDay to query Events.
	console.log({fixedDate, view, firstDayInView})


	const defaultTZ = DateTime.local().zoneName
	const [timezone, setTimezone] = useState(defaultTZ);
	const getDate = (date, DateTime) => {
		return DateTime.fromSeconds(parseInt(date)).toJSDate()
	}

	const { defaultDate, getNow, localizer, myEvents, scrollToTime } =
		useMemo(() => {
			Settings.defaultZone = timezone
			return {
				defaultDate: getDate(fixedDate, DateTime),
				getNow: () => DateTime.local().toJSDate(),
				localizer: luxonLocalizer(DateTime, { firstDayOfWeek}),
				// myEvents: [...events],
				scrollToTime: DateTime.local().toJSDate(),
			}
		}, [timezone]);

	useEffect(() => {
		return () => {
			Settings.defaultZone = defaultTZ // reset to browser TZ on unmount
		}
	}, []);




	return (
		<>
			<Calendar
				localizer={localizer}
				events={[
					{
						title: 'My nice Event',
						start: new Date(1744369157 * 1000),
						end: new Date(1744541957 * 1000),
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
