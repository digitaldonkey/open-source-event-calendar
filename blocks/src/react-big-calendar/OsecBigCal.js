import React, {useMemo, useState, useEffect, useRef, useCallback} from 'react';
import { Calendar, dayjsLocalizer } from 'react-big-calendar';
import dayjs from "dayjs";
import weekday from 'dayjs/plugin/weekday';
import timezone from 'dayjs/plugin/timezone'
import DateCache from './DateCache';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import Toolbar from "react-big-calendar/lib/Toolbar";
import EventWrapper from "./EventWrapper";

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
	}, [props.view]);
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

const unixToJsDates = (events) => {

	if (!Array.isArray(events)) {
		return [];
	}

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

// const getDefaultDate = (fixedDate = null) => {
// 	if (fixedDate) {
// 		return dayjs.unix(parseInt(fixedDate)).toDate();
// 	}
// 	return dayjs().toDate(); // == new Date()
// }

export default function OsecBigCal(props) {

	const { fixedDate, defaultView } = transformProps(props);
	const localeId = props.locale.name;
	initDayJs(localeId);
	const [events, setEvents] = useState([]);
	const [view, setView] = useState(props.defaultView);
	const [selected, setSelected] = useState(null);

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

			// TODO
			//   Seems we do not update the EventWrapper hover actions in cache case.

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

	const { getNow, localizer, popoverBoundary } = useMemo(() => {
		return {
			getNow: () => dayjs().toDate(),
			localizer: dayjsLocalizer(dayjs),
			// scrollToTime: DateTime.local().toJSDate(),
			popoverBoundary: Element.prototype.querySelector.call(
				document.getElementById(props.id),
				['[class$="view"]']
			)
		}
	});

	// /**
	//  * Helps to keep Event Popup in Boundaries.
	//  */
	// const popoverBoundary = Element.prototype.querySelector.call(
	// 	document.getElementById(props.id),
	// 	['[class$="view"]']
	// );

	const components = useMemo(() => ({
		// Adds Event Popup on hover.
		eventWrapper: (props) => {
			// console.log('eventWrapper', props.selected)
			props.popoverBoundary = popoverBoundary;
			return (<EventWrapper {...props} setSelected={setSelected} />)
		},
		toolbar: InitialRangeChangeToolbar,
	}), [popoverBoundary])

	// HANDLE SELECT EVENTS
	// const clickRef = useRef(null)
	// useEffect(() => {
	// 	/**
	// 	 * What Is This?
	// 	 * This is to prevent a memory leak, in the off chance that you
	// 	 * teardown your interface prior to the timed method being called.
	// 	 */
	// 	return () => {
	// 		window.clearTimeout(clickRef?.current)
	// 	}
	// }, [])
	// const onSelectEvent = useCallback((event) => {
	// 	/**
	// 	 * Here we are waiting 250 milliseconds (use what you want) prior to firing
	// 	 * our method. Why? Because both 'click' and 'doubleClick'
	// 	 * would fire, in the event of a 'doubleClick'. By doing
	// 	 * this, the 'click' handler is overridden by the 'doubleClick'
	// 	 * action.
	// 	 */
	// 	window.clearTimeout(clickRef?.current)
	// 	clickRef.current = window.setTimeout(() => {
	// 		console.log(event, 'onSelectEvent')
	// 		setSelected(event)
	// 		// TODO
	// 		//   How to combine hover and select smartly?
	// 		//   - Disable hover on select until deselected?
	// 		//   - add a prop for selected event to the EventWrapper?
	//
	// 	}, 250)
	// }, [])

	return (
		<Calendar
			localizer={localizer}
			events={events}
			getNow={getNow}
			endAccessor="end"
			style={{ height: '90vh', fontSize: '.9rem' }}
			defaultView={defaultView}
			onRangeChange = {(newRange, newView) => {
				// var argArray = Array.prototype.slice.call( arguments );
				debug({newRange, view}, 'args@onRangeChange')
				setEvents([]);
				return loadRange(newRange, newView ?? view);
			}}
			onView ={ (newView) => {
				debug(newView, 'onView');
				setView(newView)
			}}
			components={components}
			onSelectEvent={setSelected}
			selected={selected}
			tooltip={ () => null }
			titleAccessor = {(event) => {
				if (view === 'month') {
					return `${event.title} ${dayjs(event.start).local().format('LT')}`;
				}
				return `${event.title}`;
			}}
			// dayLayoutAlgorithm={'no-overlap'}
		/>
	)
}
