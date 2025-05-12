import assign from 'lodash.assign';
import { addFilter } from '@wordpress/hooks';

import { UNSUPPORTED_BLOCKS } from './unsupported-block';

const addControlAttribute = ( settings ) => {
	if ( UNSUPPORTED_BLOCKS.includes( settings.name ) ) {
		return settings;
	}

	// don't add settings twice if they're already added via PHP
	if ( typeof settings.attributes.hideByDate !== 'undefined' ) {
		return settings;
	}

	settings.attributes = assign( settings.attributes, {
		hideByDate: {
			default: false,
			type: 'boolean',
		},
		hideByDateEnd: {
			default: '',
			type: 'string',
		},
		hideByDateStart: {
			default: '',
			type: 'string',
		},
		hideConditionalTags: {
			default: {},
			type: 'object',
		},
		hideDesktop: {
			default: false,
			type: 'boolean',
		},
		hideMobile: {
			default: false,
			type: 'boolean',
		},
		hideNumberedPages: {
			default: {},
			type: 'object',
		},
		hidePosts: {
			default: {},
			type: 'object',
		},
		hideRoles: {
			default: {},
			type: 'object',
		},
		hideScreenReader: {
			default: false,
			type: 'boolean',
		},
		loginStatus: {
			default: 'none',
			type: 'string',
		},
	} );

	return settings;
};

addFilter(
	'blocks.registerBlockType',
	'block-control/attributes',
	addControlAttribute
);
