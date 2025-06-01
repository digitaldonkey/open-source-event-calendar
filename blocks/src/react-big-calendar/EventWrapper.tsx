import { useRef } from 'react';


// @ts-ignore
export default function EventWrapper({event, children, popupHandler}) {
	console.log({event, children, popupHandler})
	// const wrapperRef = useRef(null);

	return (
		<div
			// ref={wrapperRef}
			onMouseEnter={
				e => {

					// const eventEl = e.currentTarget?.querySelector(".rbc-event>div");
					//
					console.log('hover->in', e)

					popupHandler({
						event,
						target: children,
						show: true,
					}, 'show');
					e.preventDefault();
				}
			}
			onMouseLeave={
				e => {
					console.log('hover->out')
					popupHandler({
						event: null,
						target: null,
						show: false,
					}, 'hide');
					e.preventDefault();
				}
			}
			style={{position:"relative"}}
		>
			{children}
		</div>
	);

}
