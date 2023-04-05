/**
 * Block settings for Block Control.
 */

import assign from 'lodash.assign';
import { addFilter } from '@wordpress/hooks';

import { UNSUPPORTED_BLOCKS } from './unsupported-block';

const addControlAttribute = ( settings ) => {
	if ( UNSUPPORTED_BLOCKS.includes( settings.name ) ) {
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
		hidePosts: {
			default: {},
			type: 'object',
		},
		hideRoles: {
			default: {},
			type: 'object',
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
	if ( UNSUPPORTED_BLOCKS.includes( props.name ) ) {
		return props;
	}
	
	const {
		hideByDate,
		hideConditionalTags,
		hideDesktop,
		hideMobile,
		hidePosts,
		hideRoles,
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
	
	if ( hideByDate === true ) {
		classNames += 'block-control-hide-by-date ';
	}
	
	if ( hideConditionalTags.length ) {
		classNames += 'block-control-hide-by-conditional-tag ';
	}
	
	if ( hidePosts.length ) {
		classNames += 'block-control-hide-by-post ';
	}
	
	Object.keys( blockControlStore.roles ).map( ( role ) => {
		if ( typeof hideRoles !== 'undefined' && hideRoles[ role ] === true ) {
			classNames += 'block-control-hide-' + role + ' ';
		}
	} );
	
	if ( classNames.length ) {
		return Object.assign( props, { className: classNames.trim() } );
	}
	
	return props;
}

addFilter( 'blocks.getSaveContent.extraProps', 'block-control/classes', addCustomClasses );
