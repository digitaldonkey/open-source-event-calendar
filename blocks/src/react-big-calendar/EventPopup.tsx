import * as dayjs from 'dayjs';


export default function EventPopup({event}) {

	const endDate = event.end ? `End: ${ event.end.toLocaleDateString() } — ${ event.end.toLocaleTimeString() }`:''
	return (
		<div>
			{event.image && <img src={event.image.url} alt={event.alt} />}
			<h3 style={{margin: '0 0 .5em'}}>{event.title}</h3>
			<p>
				Start: {event.start.toLocaleDateString() } — {event.start.toLocaleTimeString() }
				<br />
				{endDate}
			</p>
		</div>
	);
}
