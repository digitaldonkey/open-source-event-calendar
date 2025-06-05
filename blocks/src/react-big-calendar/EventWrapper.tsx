import * as React from "react";
import { useState, useEffect } from 'react';
import { Popover } from 'react-tiny-popover';
import PopupWrapper from "./PopupWrapper";
import EventPopup from "./EventPopup";


export default function EventWrapper({event, children, popoverBoundary}) {

	const popupDelay = 600;
	const [showPopup, setShowPopUp] = useState(false);
	const [hovered, sethovered] = useState(false);

	const onTimeout = () => {
		// Do action
		setShowPopUp(true);
	};

	useEffect(() => {
		const timer = hovered && setTimeout(onTimeout, popupDelay);
		return () => {
			clearTimeout(timer);
		};
	}, [hovered]);


	const RefTarget = React.forwardRef(({ children }, ref) => {
		console.log(children, 'children@RefTarget')
		if (typeof ref === 'function') {
			ref(children); // Forward target to callback refs.
		}
		else {
			ref.current = children; // Forward target to object refs.

		}
		return null; // Don't render anything.
	});

	return (
		<div
			style={{
				position:"static",
				zIndex: 9999,
			}}
			ref={RefTarget}
			onMouseEnter={
				e => {
					// console.log('hover->in', e)
					sethovered(true)
					e.preventDefault();
				}
			}
			onMouseLeave={
				e => {
					// console.log('hover->out')
					sethovered(false)
					setShowPopUp(false);
					e.preventDefault();
				}
			}
		>
			<Popover
				parentElement={ popoverBoundary } // Sets boundaries
				boundaryInset={window.innerWidth > 400? 20 : 0 }
				isOpen={showPopup}
				positions={['bottom', 'right', 'left', 'top']} // preferred positions by priority
				reposition={true}
				content={ ( props) => {
					return(
						<PopupWrapper {...props}>
							<EventPopup event={event} />
						</PopupWrapper>
					)
				}}
			>
				{ children }
			</Popover>
		</div>
	);
}
