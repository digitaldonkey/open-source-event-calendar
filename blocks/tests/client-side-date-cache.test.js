import dayjs from "dayjs";
import { log } from "console";

import DateCache from '../src/react-big-calendar/DateCache';

const dateCache = new DateCache(dayjs);

/**
 * Helps to reset the cache data between tests.
 * @param dataSet
 */
const resetCache = (dataSet= null) => {
	const defaultData = {
		'1746568800': {default: true},
		'1746655200': {default: true},
		'1746741600': {default: true},
	}
	const data = dataSet ?? defaultData;
	dateCache.add(data);
}

test('getOldestDay()', () => {
	resetCache();
	expect(dateCache.getOldestDay()).toBe(1746568800);
});

test('getNewestDay()', () => {
	resetCache();
	expect(dateCache.getNewestDay()).toBe(1746741600);
});

test('add() Object', () => {
	resetCache();
	expect(dateCache.getNewestDay()).toBe(1746741600);
	dateCache.add({
		1747000800: {b: true},
	})
	expect(dateCache.getNewestDay()).toBe(1747000800);
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
		1746741600,
		1746655200,
		1746568800,
	]);
});

test('getRange() middle hit', () => {
	resetCache({
		'1746568800': {a: true},
		'1746655200': {b: true},
		'1746741600': {c: true},
	});
	const result = dateCache.getRange(
		new Date(1748642400*1000),
		new Date(1746050400*1000)
	);
	expect(result.cached).toEqual([
		{ a: true },
		{ b: true },
		{ c: true },
	]);
	expect(result.missing).toEqual([
		{ start: 1746050400, end: 1746482400 },
		{ start: 1746828000, end: 1748642400 }
	])
});

test('getRange() start hit', () => {
	resetCache({
		'1746568800': {a: true},
		'1746655200': {b: true},
		'1746741600': {c: true},
	});
	const result = dateCache.getRange(
		new Date(1746655200*1000),
		new Date(1748642400*1000)
	);
	expect(result.cached).toEqual([
		{ b: true },
		{ c: true }
	]);
	expect(result.missing).toEqual([
		{ start: 1746828000, end: 1748642400 }
	])
});

test('getRange() end hit', () => {
	resetCache({
		'1746568800': {a: true},
		'1746655200': {b: true},
		'1746741600': {c: true},
	});
	const result = dateCache.getRange(
		new Date(1746050400*1000),
		new Date(1746655200*1000)
	);
	expect(result.cached).toEqual([
		{ a: true },
		{ b: true }
	]);
	expect(result.missing).toEqual([
		{
			"start": 1746050400,
			"end": 1746482400,
		}
	])
});
