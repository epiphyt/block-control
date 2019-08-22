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
		login_status: {
			default: 'none',
			type: 'string',
		},
	} );
	
	return settings;
}

addFilter( 'blocks.registerBlockType', 'block-control/attributes', addControlAttribute );

const addCustomClasses = ( props, blockType, attributes ) => {
	const {
		hide_desktop,
		hide_mobile,
		hide_tablet,
		login_status,
	} = attributes;
	let classNames = ( typeof props.className === 'undefined' || ! props.className ) ? '' : props.className + ' ';
	
	if ( hide_desktop === true ) {
		classNames += 'block-control-hide-desktop ';
	}
	
	if ( hide_mobile === true ) {
		classNames += 'block-control-hide-mobile ';
	}
	
	if ( hide_tablet === true ) {
		classNames += 'block-control-hide-tablet ';
	}
	
	switch ( login_status ) {
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
