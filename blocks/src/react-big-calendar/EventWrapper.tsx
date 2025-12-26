import * as React from "react";
import { useState, useEffect } from 'react';
import { Popover } from 'react-tiny-popover';
import PopupWrapper from "./PopupWrapper";
import EventPopup from "./EventPopup";


export default function EventWrapper(props) {
	const {event, children, boundaryElement, selected, setSelected} = props;
	// console.log(props, 'props@EventWrapper')
	const hoverDelay = 250;
	const [showHovered, setShowHovered] = useState(false);
	const [hovered, setHovered] = useState(false);

	// TODO
	// - If selected: No hover interaction.

	useEffect(() => {
		if(!hovered) return;
		const timer = setTimeout( () => {
			// Do action
			setShowHovered(true);
		}, hoverDelay);
		return () => {
			clearTimeout(timer);
		};
	}, [hovered]);

	// useEffect(() => {
	// 	if (!selected) {
	// 		return;
	// 	}
	// 	return () => {
	// 		// setShowPopUp(false)
	// 		setHovered(false)
	// 	};
	// }, [selected]);

	const RefTarget = React.forwardRef(({ children }, ref) => {
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
				// fontSize: '.7em', // TODO DOES NOT ADAPT CELL HEIGHT
				// fontFamily: '"Helvetica Neue", Helvetica, Arial, sans-serif'
			}}
			ref={RefTarget}
			onMouseEnter={
				e => {
					// console.log('hover->in', e)
					setHovered(true)
					e.preventDefault();
				}
			}
			onMouseLeave={
				e => {
					// console.log('hover->out')
					setHovered(false)
					setShowHovered(false);
					// setSelected(null)
					e.preventDefault();
				}
			}
		>
			<Popover
				parentElement={ boundaryElement } // Sets boundaries
				boundaryInset={window.innerWidth > 400? 20 : 0 }
				isOpen={ selected || showHovered }
				positions={['right', 'left', 'top', 'bottom']} // preferred positions by priority
				reposition={true}
				onClickOutside={() => setSelected(null)} // handle click events outside of the popover/target here!
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
