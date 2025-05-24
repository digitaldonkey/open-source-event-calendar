import * as dayjs from 'dayjs'


// import { log } from "console";

// TODO Maybe https://github.com/liesislukas/localstorage-ttl/

export interface DateRange {
	start: Date;
	end: Date;
}

export interface UnixRange {
	start: String;
	end: String;
}

class DateCache {
	dayjs:dayjs.Dayjs;
	cache: object;
	constructor(dayjs:dayjs.Dayjs) {
		this.dayjs = dayjs;
		this.cache = {}
	}

	getRange (range:DateRange){

		if (!range.start||!range.end) {
			throw new Error('invalid argument type');
		}

		// @ts-expect-error
		let start = this.dayjs(range.start).startOf('day');
		// @ts-expect-error
		let end = this.dayjs(range.end).add(1, 'day').startOf('day');
		// Ensure order.
		if (end.isBefore(start)) {
			[end, start] = [start, end];
		}

		// @ts-expect-error
		let currentDate = this.dayjs(start);
		const missing:number[] = [];
		const temp = {};
		let found = [];

		while (currentDate.isBefore(end) || currentDate.isSame(end)) {
			const timestamp = currentDate.unix();
			// Array might be empty, but It won't requerry.
			if (Array.isArray(this.cache[String(timestamp)])) {
				found = found.concat(this.cache[String(timestamp)])
				temp[String(timestamp)] = true;
			}
			else if(this.cache[String(timestamp)] === null) {
				this.cache[String(timestamp)] = [];
			}
			else {
				missing.push(timestamp)
				// Save empty timestamps?
				// Yes. We don't want to requerry on every calendar gap.
				this.cache[String(timestamp)] = null;
				temp[String(timestamp)] = false;
			}
			// Add a day.
			currentDate = currentDate.add(1, 'day');
		}

		const data = {
			temp,
			start: {
				date: start.local().format(),
				unix: start.unix(),
			},
			end: {
				date: end.local().format(),
				unix: end.unix(),
			},
			cached: found,
			missing: this.getMissingRanges(missing),
		}
		// log(data);
		return data;
	}

	// TODO SOMEHOW WE NEED TO ADD EMPTY DAYS OR WE WILL GET ONE REQUEST PER GAP-DAY
	addRequest(data: {start: string }[]) {
		// Split Events into days.
		data.map((item)=> {
			const dayStart =  String(dayjs(item.start).startOf('day').unix());
			if (!Array.isArray(this.cache[dayStart])) {
				this.cache[dayStart] = [];
			}
			this.cache[dayStart].push(item);
		});
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

	getCachedRange():UnixRange {
		const allKeys = this.getDaysAsc();
		return {
			start: allKeys.at(0),
			end: allKeys.at(-1)
		}
	}
	getOldestDay():String {
		const { end } = this.getCachedRange();
		return end ? end : null;
	}

	getNewestDay ():String {
		const { start } = this.getCachedRange();
		return start ? start : null;
	}

	/**
	 * Get all ordered ascending/old to new.
	 */
	getDaysAsc():String[] {
		const keys = Object.keys(this.cache);
		keys.sort(function(a, b){
			return parseInt(b) - parseInt(a)
		});
		return  keys.length ? keys.map(String) : [];
	}

	/**
	 * Extract start/end of missing sections in range.
	 *
	 * @param unOrderedRange
	 */
	getMissingRanges (unOrderedRange:Array<number>):UnixRange[] {
		const range = new Int32Array(unOrderedRange).sort();
		if (!range.length) {
			return [];
		}
		const missingRanges:UnixRange[] = [];
		const oneDay = 24*60*60;
		let firstInRange = range.at(0);
		let lastInRange:number = null;

		range.forEach((timestamp) => {
			const isNextDay:Boolean = (
				lastInRange && (lastInRange + oneDay) === timestamp
			);
			if (!isNextDay && lastInRange){
				missingRanges.push({
					start: String(firstInRange),
					end: String(lastInRange + oneDay),
				});
				firstInRange = timestamp;
			}
			lastInRange = timestamp;
		});
		missingRanges.push({
			start: String(firstInRange),
			end: String((range.at(-1) + oneDay)),
		});
		return missingRanges;
	}
}

export default DateCache;
