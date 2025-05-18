import * as dayjs from 'dayjs'

import { log } from "console";

// TODO MAYybe https://github.com/liesislukas/localstorage-ttl/

export interface Range {
	start: number;
	end: number;
}

class DateCache {
	dayjs: dayjs.Dayjs;
	cache: object;
	constructor(dayjs:dayjs.Dayjs) {
		this.dayjs = dayjs;
		this.cache = {}
	}

	getRange (startDay:Date, endDay:Date){
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
				range[timestamp] = true
			}
			else {
				range[timestamp] = false
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
				unix: start.unix(),
			},
			end: {
				date: end.toDate(),
				unix: end.unix(),
			},
			cached: found,
			missing: this.getMissingRanges(range),
		}
		// log(data);
		return data;
	}

	add (items:object):void {
		for (let timestamp of Object.keys(items)) {
			// @ts-expect-error
			if (this.dayjs.unix(parseInt(timestamp)).isValid()) {
				this.cache[timestamp] = items[timestamp];
			}
		}
	}

	purge ():void {
		for (const timestamp of Object.keys(this.cache)) {
			delete this.cache[timestamp]
		}
	}

	getCachedRange():Range {
		const allKeys = this.getDaysAsc();
		return {
			start: allKeys.at(0),
			end: allKeys.at(-1)
		}
	}
	getOldestDay():number {
		const { end } = this.getCachedRange();
		return end ? end : null;
	}

	getNewestDay ():number {
		const { start } = this.getCachedRange();
		return start ? start : null;
	}

	/**
	 * Get all ordered ascending/old to new.
	 */
	getDaysAsc():number[] {
		const keys = Object.keys(this.cache);
		keys.sort(function(a, b){
			return parseInt(b) - parseInt(a)
		});
		return  keys.length ? keys.map(Number) : [];
	}

	/**
	 * Extract start/end of missing sections in range.
	 * @param range
	 */
	getMissingRanges(range) {
		let isMissing:string, lastOne:string;
		const missed:Range[] = [];
		const timestamps = Object.keys(range);
		const lastElement= timestamps.at(-1);
		for (let timestamp of timestamps) {
			if (this.cache[timestamp]) {
				if (isMissing) {
					missed.push({
						start: parseInt(isMissing),
						end: parseInt(lastOne),
					});
					isMissing = null;
				} else {
					lastOne = timestamp;
				}
			}
			else {
				if (isMissing) {
					lastOne = timestamp;
				}
				else {
					isMissing = timestamp;
				}
			}
			if (isMissing && lastElement === timestamp) {
				missed.push({
					start: parseInt(isMissing),
					end: parseInt(timestamp),
				});
			}
		}
		return missed;
	}
}

export default DateCache;
