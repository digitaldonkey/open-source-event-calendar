import React, {useMemo, useState, useEffect, useCallback} from 'react';
import { Calendar, dayjsLocalizer } from 'react-big-calendar';
import dayjs from "dayjs";
import weekday from 'dayjs/plugin/weekday';
import timezone from 'dayjs/plugin/timezone'
import DateCache, {DateRange} from './DateCache';
import './style.scss';
import {Range} from './DateCache';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import Toolbar from "react-big-calendar/lib/Toolbar";

const dateCache = new DateCache(dayjs);

const doDebug = false;
let debug = x => {};
if( (doDebug && typeof console != 'undefined')) {
	debug = console.log.bind(console);
}

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
 * Ensure to fire onRangeChange on init.
 *
 * The calendar does not provide any way to receive the range of dates that
 * are visible except when they change. This is the cleanest way I could find
 * to extend it to provide the _initial_ range (`onView` calls `onRangeChange`).
 * @see https://github.com/jquense/react-big-calendar/issues/1752#issuecomment-761051235
 *
 * @param props
 * @returns {Element}
 * @constructor
 */
const InitialRangeChangeToolbar = (props) => {
	useEffect(() => {
		props.onView(props.view);
	}, []);
	return <Toolbar {...props} />;
}

/**
 * Preprocess props.
 *
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

/**
 * TODO Delete
 *
 * @param defaultDate
 * @param view
 * @returns {{start: null, end: null}}
 */
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

const unixToJsDates = (events) => {
	return events.map((event) => {
		return {
			...event,
			...{
				start: dayjs.unix(parseInt(event.start)).toDate(),
				end: dayjs.unix(parseInt(event.end)).toDate(),
			},
		};
	});
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

	const { fixedDate, defaultView } = transformProps(props);
	const localeId = props.locale.name;
	initDayJs(localeId);
	const localizer = dayjsLocalizer(dayjs);
	const [events, setEvents] = useState([]);
	const [view, setView] = useState(props.defaultView);

	/**
	 * Loading Events
	 * @type {(function(*): Promise<void>)|*}
	 */
	const loadEvents = useCallback(async(range)=> {

		const theRange = {
			start: dayjs(range.start * 1000).local().toDate(),
			end: dayjs(range.end * 1000).local().endOf('day').toDate(),
		};
		debug({
			...theRange,
			range
		}, 'range@loadEvents');

		const cacheData = dateCache.getRange(theRange);

		if (cacheData.cached.length) {
			setEvents(cacheData.cached);
			debug(cacheData.cached, 'FROM CACHE')
		}

		// TODO
		//   - WHY ARE WE ON DAY OFF?
		//   - unixToJsDates should only be called once on every event


		// Fetch what is missing.
		const url = '/osec/v1/days';
		if (cacheData.missing.length) {
 			for (const currenttRange of cacheData.missing) {
				debug({
					start: dayjs(parseInt(currenttRange.start) * 1000).local().toString(),
					end: dayjs(parseInt(currenttRange.end) * 1000).local().toString()
				}, 'MISSING RANGE')

				const path = addQueryArgs( url, currenttRange );
				const fetched = await apiFetch( { path } );
				const newEvents = unixToJsDates(fetched.events);
				setEvents(events => [...events, ...newEvents]);
				dateCache.addRequest(newEvents)
				debug({newEvents, cache: dateCache}, 'FETCHED TO CACHE')
			}
		}
	}, [setEvents])

	/**
	 * Load Events based on date range and view
	 *
	 * Handles inconsistent BigCal ranges.
	 *
	 * @param range
	 * @param view
	 * @returns {Promise<void>}
	 */
	const loadRange = (range, view) => {
		let newRange = range;
		switch (view) {
			case 'week':
				newRange = {
					start: range[0],
					end: range[6],
				}
				break;
			case 'agenda':
				// TODO There are options to change default agenda Range.
				/// Agenda.range = (start, { length = Agenda.defaultProps.length })
				newRange = {
					start: range.start,
					// TODO For now we load one month.
					end: dayjs(range.start).endOf('month').toDate(),
				}
				break;
			case 'day':
				newRange = {
					start: range[0],
					end: dayjs(range[0]).endOf('day').toDate(),
				}
				break
			case 'month':
			// default:
		}

		debug({
			start: dayjs(newRange.start).toDate() ,
			end: dayjs(newRange.end).toDate(),
			view
		}, `loadRange for view: "${view}"`);

		return loadEvents({
			start: dayjs(newRange.start).unix(),
			end: dayjs(newRange.end).unix(),
		});
	}

	const { getNow } = useMemo(() => {
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
			style={{ height: '100vh', fontSize: '.9rem' }}
			defaultView={defaultView}
			onRangeChange = {(newRange, newView) => {

				// var argArray = Array.prototype.slice.call( arguments );
				debug({newRange, view}, 'args@onRangeChange')
				return loadRange(newRange, newView ?? view);
			}}
			// onRangeChange = {loadRange}
			onView ={ (newView) => {
				debug(newView, 'onView');
				setView(newView)
			}}
			components={{toolbar: InitialRangeChangeToolbar}}
		/>
	)
}
