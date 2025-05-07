// Things can be loaded in too many ways...
// @see https://css-tricks.com/adding-a-custom-welcome-guide-to-the-wordpress-block-editor/

/* eslint-disable no-console */
import {createRoot} from '@wordpress/element';
import {LoadableCalendar} from "./LoadableCalendar";

window.addEventListener("load", (event) => {
	const containers = document.getElementsByClassName("osec-react-big-calendar");
	for (const container of containers) {
		const domElement = document.getElementById(container.id);
		const props = JSON.parse(domElement.dataset.props);
		const root = createRoot(domElement);
		root.render(<LoadableCalendar {...props}/>);
	}
});

