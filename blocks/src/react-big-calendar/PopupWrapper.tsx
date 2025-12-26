import { ArrowContainer } from 'react-tiny-popover';
import * as React from "react";
import './popover.scss'

export default function PopupWrapper({ position, childRect, popoverRect, children }):React.ReactElement {
	const backgroundColor = 'rgba(255,255,255,.95)';
	const arrowColor = backgroundColor;
	const arrowBorderColor = 'rgb(144,144,144)'
	const arrowSize = 8

	const popupStyle = {
		backgroundColor,
		padding: '.5em',
		border: `1px solid ${arrowBorderColor}`,
		borderRadius: '5px',
	};

	function getArrowStyle(position?: string, arrowSize = 10) {
		switch (position) {
			case 'left':
				return {
					right: 1,
					borderLeft: `${arrowSize}px solid ${arrowColor}`,
					zIndex: 1
				}
			case 'right':
				return {
					left: 1,
					borderRight: `${arrowSize}px solid ${arrowColor}`,
					zIndex: 1
				}
			case 'bottom':
				return {
					top: 1,
					borderBottom: `${arrowSize}px solid ${arrowColor}`,
					zIndex: 1
				}
			default:
				return {
					bottom: 1,
					borderTop: `${arrowSize}px solid ${arrowColor}`,
					zIndex: 1
				}
		}
	}

	function getArrowBorderStyle(position?: string, arrowSize = 10) {
		switch (position) {
			case 'left':
				return {
					right: 1,
				}
			case 'right':
				return {
					left: 1,
				}
			case 'bottom':
				return {
					top: 0,
					// bottom: 0,
				}
			default:
				return {
					bottom: 0,
				}
		}
	}

	return (
		<ArrowContainer // if you'd like an arrow, you can import the ArrowContainer!
			position={position}
			childRect={childRect}
			popoverRect={popoverRect}
			arrowSize={arrowSize}
			className='popover-arrow-container'
			arrowClassName='popover-arrow'
			style={{ zIndex: 1, overflow: "visible" }}
			arrowColor={arrowBorderColor}
			arrowStyle={getArrowBorderStyle(position, arrowSize)}
		>
			<ArrowContainer
				position={position}
				childRect={childRect}
				popoverRect={popoverRect}
				arrowSize={arrowSize}
				style={{
					paddingLeft: 0,
					paddingTop: 0,
					paddingBottom: 0,
					paddingRight: 0
				}}
				arrowColor={arrowColor}
				arrowStyle={getArrowStyle(position, arrowSize)}
			>
				<div
					style={popupStyle}
					// onClick={() => setIsPopoverOpen(!isPopoverOpen)}
				>
					{ children }
					<p style={{marginTop: '4em'}} >I'm Event popup content (position: {position}).</p>
				</div>
			</ArrowContainer>
		</ArrowContainer>
	);
}
