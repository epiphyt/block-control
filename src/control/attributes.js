/**
 * Block settings for Block Control.
 */

import assign from 'lodash.assign';
const { addFilter } = wp.hooks;

const addControlAttribute = ( settings ) => {
	settings.attributes = assign( settings.attributes, {
		hideDesktop: {
			default: false,
			type: 'boolean',
		},
		hideMobile: {
			default: false,
			type: 'boolean',
		},
		loginStatus: {
			default: 'none',
			type: 'string',
		},
	} );
	
	return settings;
}

addFilter( 'blocks.registerBlockType', 'block-control/attributes', addControlAttribute );

const addCustomClasses = ( props, blockType, attributes ) => {
	const {
		hideDesktop,
		hideMobile,
		loginStatus,
	} = attributes;
	let classNames = ( typeof props.className === 'undefined' || ! props.className ) ? '' : props.className + ' ';
	
	if ( hideDesktop === true ) {
		classNames += 'block-control-hide-desktop ';
	}
	
	if ( hideMobile === true ) {
		classNames += 'block-control-hide-mobile ';
	}
	
	switch ( loginStatus ) {
		case 'logged-in':
			classNames += 'block-control-hide-logged-out ';
			break;
		case 'logged-out':
			classNames += 'block-control-hide-logged-in ';
			break;
	}
	
	if ( classNames.length ) {
		return Object.assign( props, { className: classNames.trim() } );
	}
	
	return props;
}

addFilter( 'blocks.getSaveContent.extraProps', 'block-control/classes', addCustomClasses );
