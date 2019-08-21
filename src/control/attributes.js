/**
 * Block settings for Block Control.
 */

import './style.scss';
import './editor.scss';

import assign from 'lodash.assign';
const { addFilter } = wp.hooks;

const addControlAttribute = ( settings ) => {
	settings.attributes = assign( settings.attributes, {
		hide_desktop: {
			default: false,
			type: 'boolean',
		},
		hide_mobile: {
			default: false,
			type: 'boolean',
		},
		hide_tablet: {
			default: false,
			type: 'boolean',
		},
	} );
	
	return settings;
}

addFilter( 'blocks.registerBlockType', 'block-control/attributes', addControlAttribute );
