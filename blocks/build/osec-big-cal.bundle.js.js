"use strict";
(globalThis["webpackChunkcalendar_block"] = globalThis["webpackChunkcalendar_block"] || []).push([["osec-big-cal.bundle.js"],{

/***/ "./src/react-big-calendar/DateCache.ts":
/*!*********************************************!*\
  !*** ./src/react-big-calendar/DateCache.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// TODO MAYybe https://github.com/liesislukas/localstorage-ttl/

class DateCache {
  constructor(dayjs) {
    this.dayjs = dayjs;
    this.cache = {};
  }
  getRange(startDay, endDay) {
    // @ts-expect-error
    let start = this.dayjs(startDay).startOf('day');
    // +1 day? to fill
    //   -> No: the API should return Events from start to end of a day.
    //      So there might just be a List of Events for a
    //      Unix time + 24 hours Range.
    //      - Will invalidate on timezone/locale change on frontend.
    // @ts-expect-error
    let end = this.dayjs(endDay).startOf('day');
    // Ensure order.
    if (end.isBefore(start)) {
      [end, start] = [start, end];
    }
    // @ts-expect-error
    let currentDate = this.dayjs(start);
    const range = {};
    const found = [];
    while (currentDate.isBefore(end) || currentDate.isSame(end)) {
      const timestamp = String(currentDate.unix());
      if (this.cache[timestamp]) {
        found.push(this.cache[timestamp]);
        range[timestamp] = true;
      } else {
        range[timestamp] = false;
      }
      // Add a day.
      currentDate = currentDate.add(1, 'day');
    }
    //  Alle Tage generieren?
    //  Wie matchen?
    //  Wie fetchen?

    const data = {
      range,
      start: {
        date: start.toDate(),
        unix: start.unix()
      },
      end: {
        date: end.toDate(),
        unix: end.unix()
      },
      cached: found,
      missing: this.getMissingRanges(range)
    };
    // log(data);
    return data;
  }
  add(items) {
    for (let timestamp of Object.keys(items)) {
      // @ts-expect-error
      if (this.dayjs.unix(parseInt(timestamp)).isValid()) {
        this.cache[timestamp] = items[timestamp];
      }
    }
  }
  purge() {
    for (const timestamp of Object.keys(this.cache)) {
      delete this.cache[timestamp];
    }
  }
  getCachedRange() {
    const allKeys = this.getDaysAsc();
    return {
      start: allKeys.at(0),
      end: allKeys.at(-1)
    };
  }
  getOldestDay() {
    const {
      end
    } = this.getCachedRange();
    return end ? end : null;
  }
  getNewestDay() {
    const {
      start
    } = this.getCachedRange();
    return start ? start : null;
  }

  /**
   * Get all ordered ascending/old to new.
   */
  getDaysAsc() {
    const keys = Object.keys(this.cache);
    keys.sort(function (a, b) {
      return parseInt(b) - parseInt(a);
    });
    return keys.length ? keys.map(Number) : [];
  }

  /**
   * Extract start/end of missing sections in range.
   * @param range
   */
  getMissingRanges(range) {
    let isMissing, lastOne;
    const missed = [];
    const timestamps = Object.keys(range);
    const lastElement = timestamps.at(-1);
    for (let timestamp of timestamps) {
      if (this.cache[timestamp]) {
        if (isMissing) {
          missed.push({
            start: parseInt(isMissing),
            end: parseInt(lastOne)
          });
          isMissing = null;
        } else {
          lastOne = timestamp;
        }
      } else {
        if (isMissing) {
          lastOne = timestamp;
        } else {
          isMissing = timestamp;
        }
      }
      if (isMissing && lastElement === timestamp) {
        missed.push({
          start: parseInt(isMissing),
          end: parseInt(timestamp)
        });
      }
    }
    return missed;
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (DateCache);

/***/ }),

/***/ "./src/react-big-calendar/OsecBigCal.js":
/*!**********************************************!*\
  !*** ./src/react-big-calendar/OsecBigCal.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ OsecBigCal)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_big_calendar__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-big-calendar */ "./node_modules/react-big-calendar/dist/react-big-calendar.esm.js");
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! dayjs */ "./node_modules/dayjs/dayjs.min.js");
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(dayjs__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var dayjs_plugin_weekday__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! dayjs/plugin/weekday */ "./node_modules/dayjs/plugin/weekday.js");
/* harmony import */ var dayjs_plugin_weekday__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(dayjs_plugin_weekday__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var dayjs_plugin_timezone__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! dayjs/plugin/timezone */ "./node_modules/dayjs/plugin/timezone.js");
/* harmony import */ var dayjs_plugin_timezone__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(dayjs_plugin_timezone__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _DateCache__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./DateCache */ "./src/react-big-calendar/DateCache.ts");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__);









const initDayJs = localeId => {
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
  dayjs__WEBPACK_IMPORTED_MODULE_2___default().extend((dayjs_plugin_weekday__WEBPACK_IMPORTED_MODULE_3___default()));
  dayjs__WEBPACK_IMPORTED_MODULE_2___default().extend((dayjs_plugin_timezone__WEBPACK_IMPORTED_MODULE_4___default()));
  if (localeId !== 'en') {
    dayjs__WEBPACK_IMPORTED_MODULE_2___default().locale(localeId);
  }
};

/**
 * Maybe we won't need to do this if we have a dedicated Form later.
 * For now: Here is the place if Edit Form props and calendar props do not match.
 * @param props
 * @returns {*}
 */
const transformProps = props => {
  // View name "oneday" matches "day".
  if (props.view && props.view === 'oneday') {
    props.view = 'day';
  }
  if (props.view) {
    props.defaultView = props.view;
    delete props.view;
  }
  if (props.fixedDate) {
    props.fixedDate = dayjs__WEBPACK_IMPORTED_MODULE_2___default().unix(parseInt(props.fixedDate)).valueOf();
  }
  return props;
};
const initialRange = (defaultDate = null, view) => {
  const date = Object.prototype.toString.call(defaultDate) === '[object Date]' ? dayjs__WEBPACK_IMPORTED_MODULE_2___default()(defaultDate) : dayjs__WEBPACK_IMPORTED_MODULE_2___default()();
  const returnVal = {
    start: null,
    end: null
  };
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
};
const transformRange = (range, view) => {
  console.log({
    range,
    view
  }, 'range (UNTRANSFORMED)');
  switch (view) {
    case 'week':
      return {
        start: range[0],
        end: range[6]
      };
    case 'agenda':
      // TODO There are options to change default agenda Range.
      /// Agenda.range = (start, { length = Agenda.defaultProps.length })
      return {
        start: range.start,
        end: null
      };
    case 'day':
      return {
        start: range[0],
        end: null
      };
    case 'month':
    default:
      return range;
  }
};
const getDefaultDate = (fixedDate = null) => {
  if (fixedDate) {
    return dayjs__WEBPACK_IMPORTED_MODULE_2___default().unix(parseInt(fixedDate)).toDate();
  }
  return dayjs__WEBPACK_IMPORTED_MODULE_2___default()().toDate(); // == new Date()
};

/**
 *
 * @param  range Range
 * @param setEvents
 * @returns {Promise<void>}
 */
const loadEvents = async (range, setEvents) => {
  const url = '/osec/v1/days';
  const path = (0,_wordpress_url__WEBPACK_IMPORTED_MODULE_7__.addQueryArgs)(url, range);
  // const {events} =
  // console.log({events} );
  const res = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
    path
  });
  const myReturn = res.events ? res.events : [];
  console.log(myReturn, '@loadEvents');
  return myReturn;
};
function OsecBigCal(props) {
  // console.log(props, 'props@OsecBigCal')
  const {
    fixedDate,
    defaultView
  } = transformProps(props);
  const localeId = props.locale.name;
  initDayJs(localeId);
  const localizer = (0,react_big_calendar__WEBPACK_IMPORTED_MODULE_1__.dayjsLocalizer)((dayjs__WEBPACK_IMPORTED_MODULE_2___default()));
  const [events, setEvents] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([{
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
  const {
    getNow
  } = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(() => {
    return {
      defaultDate: getDefaultDate(fixedDate),
      getNow: () => dayjs__WEBPACK_IMPORTED_MODULE_2___default()().toDate(),
      localizer
      // loadRange: initialRange(fixedDate, defaultView),
      // scrollToTime: DateTime.local().toJSDate(),
    };
  });
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    async function loadPosts(loadRange) {
      const url = '/osec/v1/days';
      const path = (0,_wordpress_url__WEBPACK_IMPORTED_MODULE_7__.addQueryArgs)(url, loadRange);
      // const {events} =
      // console.log({events} );
      const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
        path
      });
      if (response.events) {
        const events = response.events.map(event => {
          // console.log(dayjs.unix(parseInt(event.start)).toDate())
          return {
            ...event,
            ...{
              start: dayjs__WEBPACK_IMPORTED_MODULE_2___default().unix(parseInt(event.start)).toDate(),
              end: dayjs__WEBPACK_IMPORTED_MODULE_2___default().unix(parseInt(event.end)).toDate()
            }
          };
        });
        console.log(events, '@loadEvents');
        setEvents(events);
      }
    }
    loadPosts(loadRange);
  }, [loadRange, setEvents]);

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

  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_big_calendar__WEBPACK_IMPORTED_MODULE_1__.Calendar, {
    localizer: localizer,
    events: events
    // date={  }
    ,
    getNow: getNow,
    endAccessor: "end",
    style: {
      height: '80vh'
    },
    defaultView: defaultView,
    onRangeChange: (newRange, currentView) => {
      const range = transformRange(newRange, currentView);
      console.log({
        range
      }, 'newRange (TRANSFORMED)');
    },
    onNavigate: newDate => {
      // Does not seem to fire.
      console.log({
        newDate
      }, 'newDate');
    },
    onView: newView => {
      // setView(newView);
      console.log({
        newView
      }, 'newView');
    }
  });
}

/***/ })

}]);
//# sourceMappingURL=osec-big-cal.bundle.js.js.map?ver=a1597072a7d9520f8ce0