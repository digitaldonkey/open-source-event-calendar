
const localesFolder = './node_modules/dayjs/locale';
const fs = require('fs');

/**
 * Provide array of dayjs-locales.
 *
 * Providing an upgrade-proof way to load dayjs-locales
 * on frontend.
 * @see webpack.config.js CopyWebpackPlugin dayjs/locale.
 */
fs.readdir(localesFolder, (err, files) => {
	const locales = [];
	files.forEach(file => {
		locales.push(file.replace('.js', ''))
	});
	fs.writeFile('./src/react-big-calendar/datejs-locales.json', JSON.stringify(locales), 'utf8', function(err) {
			if (err) throw err;
			console.log('Created dayjs locales data');
		}
	)
});
