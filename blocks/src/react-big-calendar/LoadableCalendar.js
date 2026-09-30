import {lazy, Suspense} from '@wordpress/element';
import {__} from '@wordpress/i18n';

/**
 * All dayjs locales, each loaded on demand as its own chunk.
 */
const dayjsLocales = import.meta.webpackContext('dayjs/locale', {
	recursive: false,
	regExp: /\.js$/,
	mode: 'lazy',
	chunkName: 'react-big-calendar/dayjs-locale/[request]',
});
const availableLocales = dayjsLocales.keys()
	.filter((key) => key.startsWith('./'))
	.map((key) => key.slice(2, -3));

/**
 * Load user locale based on browser settings.
 *
 * BigCal displays everything (like weekdays, Date formats etc based on browser Language.
 *
 * @returns {*|string|boolean}
 */
const getUserLocale = () => {
	const nav_langs = navigator.languages || (navigator.language ? [navigator.language] : false);
	if(!nav_langs || !nav_langs.length) {
		return 'en';
	}
	const check_locale = (locale) => {
		if(['en', 'en-us'].includes(locale)) return 'en';
		if(locale === 'zn') return 'zh-cn';
		if(locale === 'no') return 'nb';
		if(availableLocales.includes(locale)) {
			return locale
		}
		return false;
	}

	for (let lang of nav_langs) {
		lang = lang.toLowerCase();
		const returnValue = check_locale(lang) || (lang.includes('-') && check_locale(lang.split('-')[0]));
		if (returnValue) {
			return returnValue
		}
	}
	return 'en';
}

/**
 * The calendar and the visitor's dayjs locale, both loaded on demand.
 *
 * The locale files are CommonJS: the context resolves to the locale object itself.
 */
const OsecBigCal = lazy(() => Promise.all([
	import(
		/* webpackChunkName: "react-big-calendar/osec-big-cal" */
		'./OsecBigCal'
	),
	dayjsLocales(`./${getUserLocale()}.js`),
]).then(([calendar, locale]) => ({
	default: (props) => <calendar.default {...props} locale={locale} />,
})));

export function LoadableCalendar(props) {
	return (
		<Suspense fallback={<div>{__('Loading…', 'open-source-event-calendar')}</div>}>
			<OsecBigCal {...props} />
		</Suspense>
	);
}
