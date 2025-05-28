import Overlay from '@restart/ui/Overlay';

const Popup = (props)=> {
	console.log(props)
	return (
		<div>Hello</div>
	);
}

export default function EventPopup(props) {
	console.log(props)
	return (
		<Overlay
			show={true}
			// rootClose
			// offset={[0, 10]}
			// placement={placement}
			// container={containerRef}
			//target={}
	    >
			{(props, { arrowProps, popper, show }) => (
				<div {...props} className="absolute">
					N.A.
				</div>
			)}
		</Overlay>
	);
}
