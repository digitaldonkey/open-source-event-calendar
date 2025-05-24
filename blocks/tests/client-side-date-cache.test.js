import dayjs from "dayjs";
import { log } from "console";
import DateCache from '../src/react-big-calendar/DateCache';
import utc from "dayjs/plugin/utc";

// UTC is always available in OsecBigCal.js
dayjs.extend(utc);
const dateCache = new DateCache(dayjs);

/**
 * Helps to reset the cache data between tests.
 * @param dataSet
 */
const resetCache = (dataSet= null) => {
	const defaultData = {
		'1746568800': [{default: true}],
		'1746655200': [{default: true}],
		'1746741600': [{default: true}],
	}
	const data = (dataSet !== null) ? dataSet : defaultData;
	dateCache.add(data);
}

test('getOldestDay()', () => {
	resetCache();
	expect(dateCache.getOldestDay()).toBe('1746568800');
});

test('getNewestDay()', () => {
	resetCache();
	expect(dateCache.getNewestDay()).toBe('1746741600');
});

test('add() Object', () => {
	resetCache();
	expect(dateCache.getNewestDay()).toBe('1746741600');
	dateCache.add({
		'1747000800': {b: true},
	})
	expect(dateCache.getNewestDay()).toBe('1747000800');
});

test('purge()', () => {
	dateCache.purge();
	expect(dateCache.getNewestDay()).toBe(null);
	expect(dateCache.getOldestDay()).toBe(null);
});

test('add()/getDaysAsc() equality', () => {
	resetCache({
		'1746568800': {add: true},
		'1746655200': {add: true},
		'1746741600': {add: true},
	});
	// const t = () => {
	// 	dateCache.add(data);
	// };
	// expect(t).toThrow('Add() must receive an object');
	expect(dateCache.getDaysAsc()).toEqual([
		'1746741600',
		'1746655200',
		'1746568800',
	]);
});

test('getRange() middle hit', () => {
	resetCache({
		'1746568800': [{a: true}, {b: true}],
		'1746655200': [{c: true}],
		'1746741600': [{d: true}],
	});
	const result = dateCache.getRange({
		start: new Date(1748642400 * 1000),
		end: new Date(1746050400 * 1000)
	});
	expect(result.cached).toEqual([
		{ a: true },
		{ b: true },
		{ c: true },
		{ d: true },
	]);
	expect(result.missing).toEqual([
		{ start: '1746050400', end: '1746482400' },
		{ start: '1746828000', end: '1748642400' }
	])
});

test('getRange() start hit', () => {
	resetCache({
		'1746568800': {a: true},
		'1746655200': {b: true},
		'1746741600': {c: true},
	});
	const result = dateCache.getRange({
		start: new Date(1746655200*1000),
		end: new Date(1748642400*1000),
	});
	expect(result.cached).toEqual([
		{ b: true },
		{ c: true }
	]);
	expect(result.missing).toEqual([
		{ start: '1746828000', end: '1748642400' }
	])
});

test('getRange() end hit', () => {
	resetCache({
		'1746568800': [{a: true}],
		'1746655200': [{b: true}],
		'1746741600': [{c: true}],
	});
	const result = dateCache.getRange({
		start: new Date(1746050400*1000),
		end: new Date(1746655200*1000),
	});
	expect(result.cached).toEqual([
		{ a: true },
		{ b: true }
	]);
	expect(result.missing).toEqual([
		{
			"start": '1746050400',
			"end": '1746482400',
		}
	])
});

test('addRequest() Object', () => {
	dateCache.purge();
	dateCache.addRequest([
		{"title":"All days can be very long in these Events","start":"1747864800","end":"1747951200","allDay":true,"resource":"any"},
		{"title":"All days can be very long in these Events","start":"1748037600","end":"1748124000","allDay":true,"resource":"any"},
		{"title":"All days can be very long in these Events","start":"1748124000","end":"1748210400","allDay":true,"resource":"any"},
		{"title":"All days can be very long in these Events","start":"1748296800","end":"1748383200","allDay":true,"resource":"any"},
		{"title":"All days can be very long in these Events","start":"1748469600","end":"1748556000","allDay":true,"resource":"any"},
		{"title":"All days can be very long in these Events","start":"1748642400","end":"1748728800","allDay":true,"resource":"any"},
		{"title":"Daily","start":"1747930860","end":"1747934460","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748017260","end":"1748020860","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748103660","end":"1748107260","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748190060","end":"1748193660","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748276460","end":"1748280060","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748362860","end":"1748366460","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748449260","end":"1748452860","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748535660","end":"1748539260","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748622060","end":"1748625660","allDay":false,"resource":"any"},
		{"title":"Daily","start":"1748708460","end":"1748712060","allDay":false,"resource":"any"}
	]);
	expect(dateCache.getDaysAsc()).toEqual([
		'1748642400', '1748556000',
		'1748469600', '1748383200',
		'1748296800', '1748210400',
		'1748124000', '1748037600',
		'1747951200', '1747864800'
	]);
	const result = dateCache.getRange({
		start: new Date(1748642400*1000),
		end: new Date(1748210400*1000)
	});
	expect(result.cached).toEqual([
		{
			title: 'Daily',
			start: '1748276460',
			end: '1748280060',
			allDay: false,
			resource: 'any'
		},
		{
			title: 'All days can be very long in these Events',
			start: '1748296800',
			end: '1748383200',
			allDay: true,
			resource: 'any'
		},
		{
			title: 'Daily',
			start: '1748362860',
			end: '1748366460',
			allDay: false,
			resource: 'any'
		},
		{
			title: 'Daily',
			start: '1748449260',
			end: '1748452860',
			allDay: false,
			resource: 'any'
		},
		{
			title: 'All days can be very long in these Events',
			start: '1748469600',
			end: '1748556000',
			allDay: true,
			resource: 'any'
		},
		{
			title: 'Daily',
			start: '1748535660',
			end: '1748539260',
			allDay: false,
			resource: 'any'
		},
		{
			title: 'Daily',
			start: '1748622060',
			end: '1748625660',
			allDay: false,
			resource: 'any'
		},
		{
			title: 'All days can be very long in these Events',
			start: '1748642400',
			end: '1748728800',
			allDay: true,
			resource: 'any'
		},
		{
			title: 'Daily',
			start: '1748708460',
			end: '1748712060',
			allDay: false,
			resource: 'any'
		}
	]);
});
