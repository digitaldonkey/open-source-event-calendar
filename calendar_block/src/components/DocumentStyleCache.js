import React, {useMemo} from 'react'
import {CacheProvider} from '@emotion/react';
import createCache from '@emotion/cache';

/**
 * Makes react-select write its styles into the document the block renders in.
 *
 * Emotion injects into the document the script runs in by default, which is the
 * outer admin page. The block editor renders blocks in an iframe, so the styles
 * never reached the controls.
 */
export default function DocumentStyleCache({ownerDocument, children}) {
	const cache = useMemo(
		() => ownerDocument ? createCache({key: 'osec', container: ownerDocument.head}) : null,
		[ownerDocument]
	);
	if (!cache) {
		return null;
	}
	return (
		<CacheProvider value={cache}>
			{children}
		</CacheProvider>
	);
}
