import Loadable from 'react-loadable';
import availableLocales from './datejs-locales.json';

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
		if(['en', 'en-us'].includes(locale)) return locale;
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
 * Using a `Loadable` to load things we need.
 */
export const LoadableCalendar = Loadable.Map({
	loader: {
		OsecBigCal: () => import(
			// TODO chunck name is not applied in js :/
			/* webpackChunkName: "osec-big-cal.bundle.js" */
			'./OsecBigCal'
		),
		// i18n: () => fetch('./i18n/bar.json').then(res => res.json()),
		locale: ()  => {
			const locale = getUserLocale();
			const uri = osecSettings.dayjsLocaleUri + locale + '.js';

			console.info(`Loading locale "${locale}". Want to load ${uri}`)
			// return (
			// 	import(/* webpackIgnore: true */ uri ) Would require CORS to be enabled :/
			// );
			// Why adding '.js'?
			//  It leads to stupid xyzjs.js chunc names.
			//  But avoids webpack errors
			//  @see https://github.com/iamkun/dayjs/issues/792#issuecomment-639961997
			//  It is also PR'd to save chuncks in current folder, which would be much nicer.
			///  @see https://github.com/webpack/webpack/pull/11258
			//
			// TODO
			//  We may also use blocks/build/react-big-calendar/dayjs-locales
			//  created by CopyWebpackPlugin to keep our build/ folder more tidy.
			//  Decide later....
			// dayjsLocalesUri
			return import(
				/* webpackChunkName: "[request]" */
				`dayjs/locale/${locale}.js`
			);
		}
	},
	loading() {
		return <div>Loading...</div>
	},
	render(loaded, props) {
		const OsecBigCal = loaded.OsecBigCal.default;
		return <OsecBigCal {...props} locale={loaded.locale.default} />;
	},
});
