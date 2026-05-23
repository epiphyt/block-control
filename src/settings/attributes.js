import { addFilter } from '@wordpress/hooks';

import meta from './block.json';
import { UNSUPPORTED_BLOCKS } from './unsupported-block';

const addControlAttribute = (settings) => {
	if (UNSUPPORTED_BLOCKS.includes(settings.name)) {
		return settings;
	}

	// don't add settings twice if they're already added via PHP
	if (typeof settings.attributes.hideByDate !== 'undefined') {
		return settings;
	}

	settings.attributes = Object.assign(settings.attributes, meta.attributes);

	return settings;
};

addFilter(
	'blocks.registerBlockType',
	'block-control/attributes',
	addControlAttribute
);
