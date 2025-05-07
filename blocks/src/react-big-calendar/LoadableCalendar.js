import Loadable from 'react-loadable';
import availableLocales from './datejs-locales.json';

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

export const LoadableCalendar = Loadable.Map({
	loader: {
		OsecBigCal: () => import(
			// TODO chunck name is not applied :/
			/* webpackChunkName: "osec-big-cal.bundle.js" */
			'./OsecBigCal'
		),
		// i18n: () => fetch('./i18n/bar.json').then(res => res.json()),
		locale: ()  => {
			const locale = getUserLocale();
			console.info(`Loading locale ${locale}`)
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
