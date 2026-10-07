import {useEffect, useRef} from 'react'

// Opens and closes its children with a height transition (see editor.scss). They
// stay rendered while closed, so they are made inert: no focus, no screen reader.
export default function Reveal({show, children}) {
	const ref = useRef(null);

	useEffect(() => {
		if (show) {
			ref.current.removeAttribute('inert');
		} else {
			ref.current.setAttribute('inert', '');
		}
	}, [show]);

	return (
		<div className={'osec-reveal' + (show ? ' is-open' : '')} ref={ref}>
			<div className="osec-reveal__inner">
				{children}
			</div>
		</div>
	);
}
