/**
 * Block controls for Block Control.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	CheckboxControl,
	Dashicon,
	DateTimePicker,
	Dropdown,
	PanelBody,
	RadioControl,
	ToggleControl,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { select } from '@wordpress/data';
import { getSettings, dateI18n } from '@wordpress/date';
import { addFilter } from '@wordpress/hooks';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { UNSUPPORTED_BLOCKS } from './unsupported-block';

const CONDITIONAL_TAGS = {
	is_home: __( 'Blog page', 'block-control' ),
	is_front_page: __( 'Front page', 'block-control' ),
	is_single: __( 'Single posts', 'block-control' ),
	is_sticky: __( 'Sticky posts', 'block-control' ),
	is_page: __( 'Pages', 'block-control' ),
	is_category: __( 'Categories', 'block-control' ),
	is_tag: __( 'Tag pages', 'block-control' ),
	is_tax: __( 'Taxonomy pages', 'block-control' ),
	is_archive: __( 'Archive pages', 'block-control' ),
	is_search: __( 'Search pages', 'block-control' ),
	is_404: __( '404 pages', 'block-control' ),
	is_paged: __( 'Paged pages', 'block-control' ),
	is_attachment: __( 'Attachment pages', 'block-control' ),
	is_singular: __( 'Any single post', 'block-control' ),
};

/**
 * Check if Block Control has an active filter.
 * 
 * @param	{object}	props The block properties
 * @return	{boolean} True if a filter is active, false otherwise
 */
const isActive = ( props ) => {
	const {
		attributes: {
			hideByDate,
			hideByDateEnd,
			hideByDateStart,
			hideConditionalTags,
			hideDesktop,
			hideMobile,
			hidePosts,
			hideRoles,
			loginStatus,
		},
	} = props;
	
	if (
		( hideByDate && ( hideByDateStart || hideByDateEnd ) )
		|| hideDesktop
		|| hideMobile
		|| loginStatus && loginStatus !== 'none'
	) {
		return true;
	}
	
	if ( typeof hideConditionalTags !== 'undefined' ) {
		for ( const tag in hideConditionalTags ) {
			if ( hideConditionalTags[ tag ] === true ) {
				return true;
			}
		}
	}
	
	if ( typeof hidePosts !== 'undefined' ) {
		for ( const posts in hidePosts ) {
			for ( const post in hidePosts[ posts ] ) {
				if ( hidePosts[ posts ][ post ] === true ) {
					return true;
				}
			}
		}
	}
	
	if ( typeof hideRoles !== 'undefined' ) {
		for ( const role in hideRoles ) {
			if ( hideRoles[ role ] === true ) {
				return true;
			}
		}
	}
	
	return false;
};

/**
 * Create HOC to add our controls to inspector controls of block.
 */
const addControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const [ isOpen, setIsOpen ] = useState( false );
		const {
			attributes: {
				hideConditionalTags,
				hideByDate,
				hideByDateEnd,
				hideByDateStart,
				hideDesktop,
				hideMobile,
				hidePosts,
				hideRoles,
				loginStatus,
			},
			name,
			setAttributes,
		} = props;
		
		if ( UNSUPPORTED_BLOCKS.includes( name ) ) {
			return ( <BlockEdit{ ...props } /> );
		}
		
		const postType = select( 'core/editor' )?.getCurrentPostType();
		const settings = getSettings();
		
		// ignore HTML block in widgets as their attributes are not stored
		// see: https://github.com/WordPress/gutenberg/issues/33832
		if ( postType === undefined && name === 'core/html' ) {
			return ( <BlockEdit{ ...props } /> );
		}
		
		// To know if the current timezone is a 12 hour time with look for "a" in the time format
		// We also make sure this a is not escaped by a "/"
		const is12HourTime = /a(?!\\)/i.test(
			settings.formats.time
				.toLowerCase() // Test only the lower case a
				.replace( /\\\\/g, '' ) // Replace "//" with empty strings
				.split( '' ).reverse().join( '' ) // Reverse the string and test for "a" not followed by a slash
		);
		
		// change the value if you click on a checkbox of the conditional tag hide checkboxes
		const onChangeConditionalTags = ( tag, value ) => {
			// make sure the value gets updated correctly
			// @see https://stackoverflow.com/questions/56452438/update-a-specific-property-of-an-object-attribute-in-a-wordpress-gutenberg-block#comment99517264_56459084
			const newValue = hideConditionalTags ? JSON.parse( JSON.stringify( hideConditionalTags ) ) : {};
			newValue[ tag ] = value;
			
			setAttributes( { hideConditionalTags: newValue } );
		};
		
		// change the value if you click on a checkbox of a posts hide checkboxes
		const onChangePosts = ( id, type, value ) => {
			// make sure the value gets updated correctly
			// @see https://stackoverflow.com/questions/56452438/update-a-specific-property-of-an-object-attribute-in-a-wordpress-gutenberg-block#comment99517264_56459084
			let newValue = hidePosts ? JSON.parse( JSON.stringify( hidePosts ) ) : {};
			
			if ( typeof newValue[ type ] === 'undefined' ) {
				newValue[ type ] = {};
			}
			
			newValue[ type ][ id ] = value;
			
			if ( ! value ) {
				newValue[ type ]['all'] = value;
			}
			else {
				let notProgressedItem;
				
				blockControlStore.posts[ type ]['items'].map( ( item ) => {
					if (
						typeof newValue[ type ][ item.ID ] === 'undefined'
						|| newValue[ type ][ item.ID ] === false
					) {
						notProgressedItem = item;
					}
					
					return null;
				} );
				
				newValue[ type ][ 'all' ] = ! notProgressedItem;
			}
			
			setAttributes( { hidePosts: newValue } );
		};
		
		// change the value if you click on the 'all' checkbox of a posts hide checkboxes
		const onChangePostsAll = ( id, type, value ) => {
			// make sure the value gets updated correctly
			// @see https://stackoverflow.com/questions/56452438/update-a-specific-property-of-an-object-attribute-in-a-wordpress-gutenberg-block#comment99517264_56459084
			let newValue = hidePosts ? JSON.parse( JSON.stringify( hidePosts ) ) : {};
			
			if ( typeof newValue[ type ] === 'undefined' ) {
				newValue[ type ] = { all: value };
			}
			else {
				newValue[ type ]['all'] = value;
			}
			
			blockControlStore.posts[ type ]['items'].map( ( item ) => {
				newValue[ type ][ item.ID ] = value;
			} );
			
			setAttributes( { hidePosts: newValue } );
		};
		
		// change the value if you click on a checkbox of the user role hide checkboxes
		const onChangeHideRoles = ( role, value ) => {
			// make sure the value gets updated correctly
			// @see https://stackoverflow.com/questions/56452438/update-a-specific-property-of-an-object-attribute-in-a-wordpress-gutenberg-block#comment99517264_56459084
			let newValue = hideRoles ? JSON.parse( JSON.stringify( hideRoles ) ) : {};
			newValue[ role ] = value;
			
			setAttributes( { hideRoles: newValue } );
		};
		
		const inner = <>
			<BlockEdit{ ...props } />
			
			<InspectorControls>
				<PanelBody
					title={ __( 'Visibility', 'block-control' ) }
					icon={ isActive( props ) ? <Dashicon icon="visibility" /> : null }
					initialOpen={ isOpen }
				>
					<div className="block-control-control-area block-control-device-area">
						<span className="components-base-control__label">{ __( 'Hide device types', 'block-control' ) }</span>
						<ToggleControl
							label={ __( 'Hide on smartphones', 'block-control' ) }
							value={ hideMobile || false }
							checked={ !! hideMobile }
							onChange={ ( value ) => setAttributes( { hideMobile: value } ) }
						/>
						<ToggleControl
							label={ __( 'Hide on desktops', 'block-control' ) }
							value={ hideDesktop || false }
							checked={ !! hideDesktop }
							onChange={ ( value ) => setAttributes( { hideDesktop: value } ) }
						/>
					</div>
					
					<div className="block-control-control-area">
						<RadioControl
							className="block-control-login-status"
							label={ __( 'Hide by login status', 'block-control' ) }
							selected={ loginStatus || 'none' }
							options={ [
								{ label: __( 'Show for all users', 'block-control' ), value: 'none' },
								{ label: __( 'Show for logged in users', 'block-control' ), value: 'logged-in' },
								{ label: __( 'Show for logged out users', 'block-control' ), value: 'logged-out' },
							] }
							onChange={ ( value ) => setAttributes( { loginStatus: value } ) }
						/>
					</div>
					
					<div className="block-control-control-area">
						<ToggleControl
							className="block-control-hide-by-date"
							label={ __( 'Hide by date', 'block-control' ) }
							value={ hideByDate || false }
							checked={ !! hideByDate }
							onChange={ ( value ) => setAttributes( { hideByDate: value } ) }
						/>
						{ hideByDate
							? <div>
								<div className="block-control-date block-control__date">
									<div className="block-control-date-label block-control__date--label">{ __( 'Hide date:', 'block-control' ) }</div>
									
									<div className="block-control__date--value">
										<Dropdown
											popoverProps={ { placement: 'bottom-end' } }
											renderToggle={ ( { isOpen, onToggle } ) => (
												<Button
													onClick={ onToggle }
													aria-expanded={ isOpen }
													className="components-button is-link"
												>
													{ hideByDateStart
														? dateI18n( settings.formats.datetimeAbbreviated, hideByDateStart )
														: __( 'Set date', 'block-control' )
													}
												</Button>
											) }
											renderContent={ () => (
												<div className="block-control-datetime-picker">
													<DateTimePicker
														currentDate={ hideByDateStart }
														onChange={ ( value ) => {
															setIsOpen( true );
															setAttributes( { hideByDateStart: value } );
														} }
														is12Hour={ is12HourTime }
													/>
												</div>
											) }
										/>
										{ hideByDateStart
											? <Button
												isDestructive={ true }
												onClick={ () => setAttributes( { hideByDateStart: '' } ) }
												size="compact"
												variant="secondary"
											>
												{ __( 'Remove', 'block-control' ) }
											</Button>
											: null
										}
									</div>
								</div>
								
								<div className="block-control-help">{ __( 'The date where the block starts to be hidden.', 'block-control' ) }</div>
								
								<div className="block-control-date block-control__date">
									<div className="block-control-date-label block-control__date--label">{ __( 'Display date:', 'block-control' ) }</div>
									
									<div className="block-control__date--value">
										<Dropdown
											popoverProps={ { placement: 'bottom-end' } }
											renderToggle={ ( { isOpen, onToggle } ) => (
												<Button
													onClick={ onToggle }
													aria-expanded={ isOpen }
													className="components-button is-link"
												>
													{ hideByDateEnd
														? dateI18n( settings.formats.datetimeAbbreviated, hideByDateEnd )
														: __( 'Set date', 'block-control' )
													}
												</Button>
											) }
											renderContent={ () => (
												<div className="block-control-datetime-picker">
													<DateTimePicker
														currentDate={ hideByDateEnd }
														onChange={ ( value ) => {
															setIsOpen( true );
															setAttributes( { hideByDateEnd: value } );
														} }
														is12Hour={ is12HourTime }
													/>
												</div>
											) }
										/>
										{ hideByDateEnd
											? <Button
												isDestructive={ true }
												onClick={ () => setAttributes( { hideByDateEnd: '' } ) }
												size="compact"
												variant="secondary"
											>
												{ __( 'Remove', 'block-control' ) }
											</Button>
											: null
										}
									</div>
								</div>
								
								<div className="block-control-help">{ __( 'The date where the block ends to be hidden.', 'block-control' ) }</div>
							</div>
							: null
						}
					</div>
					
					<div className="block-control-control-area block-control-control-hide-roles">
						<span className="components-base-control__label">{ __( 'Hide for user roles', 'block-control' ) }</span>
						
						<div className="block-control-checkbox-select">
							{ Object.keys( blockControlStore.roles ).map( ( role, index ) => {
								return ( <CheckboxControl
									key={ index }
									label={ blockControlStore.roles[ role ] }
									checked={ hideRoles && hideRoles[ role ] ? true : false }
									value={ role }
									onChange={ ( value ) => onChangeHideRoles( role, value ) }
								/> );
							} ) }
						</div>
					</div>
					
					<div className="block-control-control-area block-control-control-hide-conditional-tags">
						<span className="components-base-control__label">{ __( 'Hide for specific page types', 'block-control' ) }</span>
						
						<div className="block-control-checkbox-select">
							{ Object.keys( CONDITIONAL_TAGS ).map( ( tag, index ) => {
								return ( <CheckboxControl
									key={ index }
									label={ CONDITIONAL_TAGS[ tag ] }
									checked={ hideConditionalTags && hideConditionalTags[ tag ] ? true : false }
									value={ tag }
									onChange={ ( value ) => onChangeConditionalTags( tag, value ) }
								/> );
							} ) }
						</div>
					</div>
					
					{ Object.keys( blockControlStore.posts ).map( ( type, i ) => {
						// display this post type only
						// if we currently aren't in a specific post type
						if ( postType && postType !== type ) {
							return null;
						}
						
						return (
							<div className="block-control-control-area block-control-control-hide-posts" key={ type + i }>
								{/* translators: plural post type title */}
								<span className="components-base-control__label">{ sprintf( __( 'Hide for post type "%s"', 'block-control' ), blockControlStore.posts[ type ]['title'] ) }</span>
								
								<div className="block-control-checkbox-select">
									<CheckboxControl
										key={ type + i + 'all' }
										label={ __( 'All', 'block-control' ) }
										checked={ hidePosts && hidePosts[ type ] && hidePosts[ type ]['all'] ? hidePosts[ type ]['all'] : false }
										value="all"
										onChange={ ( value ) => onChangePostsAll( 'all', type, value ) }
									/>
									
									{ blockControlStore.posts[ type ]['items'].map( ( item, index ) => (
										<CheckboxControl
											key={ type + i + index }
											label={ item.post_title }
											checked={ hidePosts && hidePosts[ type ] && hidePosts[ type ][ item.ID ] ? hidePosts[ type ][ item.ID ] : ( hidePosts && hidePosts[ type ] && hidePosts[ type ]['all'] ? hidePosts[ type ]['all'] : false ) }
											value={ item.ID }
											onChange={ ( value ) => onChangePosts( item.ID, type, value ) }
										/>
									) ) }
								</div>
							</div>
						)
					} ) }
				</PanelBody>
			</InspectorControls>
		</>;
		
		if ( hideByDate && hideByDateStart ) {
			const now = new Date();
			const hideDateStart = new Date( hideByDateStart );
			
			// if block should be hidden
			if ( now.getTime() >= hideDateStart.getTime() ) {
				const hideDateEnd = new Date( hideByDateEnd );
				
				// if end date is reached
				if ( hideByDateEnd && now.getTime() > hideDateEnd.getTime() ) {
					return inner;
				}
				
				return ( <div className="block-control-wrapper block-control-is-hidden by-date">{ inner }</div> );
			}
		}
		
		return inner;
	};
}, 'addControls' );

addFilter( 'editor.BlockEdit', 'block-control/add-controls', addControls );
