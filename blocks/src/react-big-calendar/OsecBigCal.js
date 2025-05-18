import React, { useMemo, useState, useEffect } from 'react';
import { Calendar, dayjsLocalizer } from 'react-big-calendar';
import dayjs from "dayjs";
import weekday from 'dayjs/plugin/weekday';
import timezone from 'dayjs/plugin/timezone'
import './style.scss';
import {Range} from './DateCache';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

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
	if (props.view && props.view === 'oneday') {
		props.view = 'day';
	}
	if (props.view) {
		props.defaultView = props.view;
		delete props.view;
	}
	if (props.fixedDate) {
		props.fixedDate = dayjs.unix(parseInt(props.fixedDate)).valueOf();
	}
	return props;
}

const initialRange = (defaultDate = null, view) => {
	const date = Object.prototype.toString.call(defaultDate) === '[object Date]' ? dayjs(defaultDate) : dayjs();
	const returnVal = {start: null, end: null};
	switch (view) {
		case 'month':
			returnVal.start = date.startOf('month').startOf('week').unix();
			returnVal.end = date.endOf('month').endOf('week').unix();
			break;
		case 'week':
			returnVal.start = date.startOf('week').unix();
			returnVal.end = date.endOf('week').unix();
			break;
		case 'agenda':
		case 'day':
		default:
			returnVal.start = date.unix();
	}
	return returnVal;
}

const transformRange = (range, view) => {
	console.log({range, view}, 'range (UNTRANSFORMED)');
	switch (view) {
		case 'week':
			return {
				start: range[0],
				end: range[6],
			}
		case 'agenda':
			// TODO There are options to change default agenda Range.
			/// Agenda.range = (start, { length = Agenda.defaultProps.length })
			return {
				start: range.start,
				end: null,
			}
		case 'day':
			return {
				start: range[0],
				end: null,
			}
		case 'month':
		default:
			return range;
	}
}

const getDefaultDate = (fixedDate = null) => {
	if (fixedDate) {
		return dayjs.unix(parseInt(fixedDate)).toDate();
	}
	return dayjs().toDate(); // == new Date()
}

/**
 *
 * @param  range Range
 * @param setEvents
 * @returns {Promise<void>}
 */
const loadEvents = async (range, setEvents) => {
	const url = '/osec/v1/days';
	const path = addQueryArgs( url, range );
	// const {events} =
	// console.log({events} );
	const res = await apiFetch({path});
	const myReturn = res.events ? res.events : [];
	console.log(myReturn, '@loadEvents');
	return myReturn;
}

export default function OsecBigCal(props) {
	// console.log(props, 'props@OsecBigCal')
	const { fixedDate, defaultView } = transformProps(props);
	const localeId = props.locale.name;
	initDayJs(localeId);
	const localizer = dayjsLocalizer(dayjs);
	const [events, setEvents] = useState([{
		"title": "Daily",
		"start": new Date("2025-04-29T15:16:00.000Z"),
		"end": new Date("2025-04-29T16:16:00.000Z"),
		"allDay": false,
		"resource": "any"
	}]);

	// const [view, setView] = useState(defaultView);

	// TODO
	//  This can be converted to a appropriate star Range
	//   --> loadEvents should have a filter to unify the renges
	//       Day/Week/Month/Agenda(?Pager, Initial set number or Autoload?)
	//
	 const loadRange = initialRange(fixedDate, defaultView);
	// fixedDate should be relative to the
	// timespan start (e.g 1 Week Day at Day/Month views. Not on agenda).
	// Like firstVisibleDay to query Events.
	// console.log({loadRange })
	// ;
	const { getNow} = useMemo(() => {
		return {
			defaultDate: getDefaultDate(fixedDate),
			getNow: () => dayjs().toDate(),
			localizer,
			// loadRange: initialRange(fixedDate, defaultView),
			// scrollToTime: DateTime.local().toJSDate(),
		}
	});

	useEffect(() => {
		async function loadPosts(loadRange) {
			const url = '/osec/v1/days';
			const path = addQueryArgs( url, loadRange );
			// const {events} =
			// console.log({events} );
			const response = await apiFetch({path});

			if(response.events) {
				const events = response.events.map(event => {
					// console.log(dayjs.unix(parseInt(event.start)).toDate())
					return {
						...event,
						...{
							start: dayjs.unix(parseInt(event.start)).toDate(),
							end: dayjs.unix(parseInt(event.end)).toDate(),
						}
					}
				});
				console.log(events, '@loadEvents');
				setEvents(events);
			}
		}
		loadPosts(loadRange);
	}, [loadRange, setEvents])

	// useEffect(async () => {
	// 	const eventsRaw = await loadEvents(loadRange);
	// 	const events = eventsRaw.map(event => {
	// 		// console.log(dayjs.unix(parseInt(event.start)).toDate())
	// 		return {
	// 			...event,
	// 			...{
	// 				start: dayjs.unix(parseInt(event.start)).toDate(),
	// 				end: dayjs.unix(parseInt(event.end)).toDate(),
	// 			}
	// 		}
	// 	});
	// 	console.log(events, '@useEffect');
	// 	setEvents(events);
	// }, [events, loadRange]);

	return (
		<Calendar
			localizer={localizer}
			events={events}
			// date={  }
			getNow={getNow}
			endAccessor="end"
			style={{ height: '80vh' }}
			defaultView={defaultView}
			onRangeChange = {(newRange, currentView) => {
				const range = transformRange(newRange, currentView)
				console.log({range}, 'newRange (TRANSFORMED)')
			}}
			onNavigate ={(newDate) => {
				// Does not seem to fire.
				console.log({newDate}, 'newDate')
			}}
			onView ={(newView) => {
				// setView(newView);
				console.log({newView}, 'newView')
			}}
		/>
	)
}
