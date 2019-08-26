/**
 * Block controls for Block Control.
 */
const { createHigherOrderComponent } = wp.compose;
const { __experimentalGetSettings, dateI18n } = wp.date;
const { Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { Button, CheckboxControl, Dashicon, DateTimePicker, Dropdown, IconButton, PanelBody, RadioControl, ToggleControl, Tooltip } = wp.components;
const { addFilter } = wp.hooks;
const { __ } = wp.i18n;

/**
 * Check if Block Control has an active filter.
 * 
 * @param	{object}	props The block properties
 * @return	{boolean} True if a filter is active, false otherwise
 */
const isActive = ( props ) => {
	const {
		attributes: {
			hideDesktop,
			hideMobile,
			hideTablet,
			loginStatus,
		},
	} = props;
	
	if (
		hideDesktop
		|| hideMobile
		|| hideTablet
		|| loginStatus !== 'none'
	) {
		return true;
	}
	
	return false;
};

/**
 * Create HOC to add our controls to inspector controls of block.
 */
const addControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const {
			attributes: {
				hideByDate,
				hideByDateEnd,
				hideByDateStart,
				hideDesktop,
				hideMobile,
				hideRoles,
				hideTablet,
				loginStatus,
			},
			setAttributes,
		} = props;
		const settings = __experimentalGetSettings();
		// To know if the current timezone is a 12 hour time with look for "a" in the time format
		// We also make sure this a is not escaped by a "/"
		const is12HourTime = /a(?!\\)/i.test(
			settings.formats.time
				.toLowerCase() // Test only the lower case a
				.replace( /\\\\/g, '' ) // Replace "//" with empty strings
				.split( '' ).reverse().join( '' ) // Reverse the string and test for "a" not followed by a slash
		);
		// change the value if you click on a checkbox of the user role hide checkboxes
		const onChangeHideRoles = ( role, value ) => {
			// make sure the value gets updated correctly
			// @see https://stackoverflow.com/questions/56452438/update-a-specific-property-of-an-object-attribute-in-a-wordpress-gutenberg-block#comment99517264_56459084
			let newValue = JSON.parse( JSON.stringify( hideRoles ) );
			newValue[ role ] = value;
			
			setAttributes( { hideRoles: newValue } );
		}
		
		return (
			<Fragment>
				<BlockEdit { ...props } />
				
				<InspectorControls>
					<PanelBody
						title={ __( 'Visibility', 'block-control' ) }
						icon={ isActive( props ) ? <Dashicon icon="visibility" /> : null }
						initialOpen={ false }
					>
						<div className="block-control-control-area">
							<ToggleControl
								label={ __( 'Hide on smartphones', 'block-control' ) }
								value={ hideMobile }
								checked={ !! hideMobile }
								onChange={ ( value ) => setAttributes( { hideMobile: value } ) }
							/>
							<ToggleControl
								label={ __( 'Hide on tablets', 'block-control' ) }
								value={ hideTablet }
								checked={ !! hideTablet }
								onChange={ ( value ) => setAttributes( { hideTablet: value } ) }
							/>
							<ToggleControl
								label={ __( 'Hide on desktops', 'block-control' ) }
								value={ hideDesktop }
								checked={ !! hideDesktop }
								onChange={ ( value ) => setAttributes( { hideDesktop: value } ) }
							/>
						</div>
						
						<div className="block-control-control-area">
							<RadioControl
								label={ __( 'Login status', 'block-control' ) }
								selected={ loginStatus }
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
								label={ __( 'Hide by date', 'block-control' ) }
								value={ hideByDate }
								checked={ !! hideByDate }
								onChange={ ( value ) => setAttributes( { hideByDate: value } ) }
							/>
							<div className="block-control-date">
								{ hideByDate && <div className="block-control-date-label"> { __( 'Start date:', 'block-control' ) } </div> }
								{ hideByDate && hideByDateStart && <Tooltip text={ __( 'Reset date', 'block-control' ) }>
									<Button
										onClick={ () => setAttributes( { hideByDateStart: '' } ) }
									>
										<Dashicon
											className="block-control-date-reset"
											icon="no-alt"
										/>
									</Button>
								</Tooltip> }
								{ hideByDate && <Dropdown
									position="bottom right"
									renderToggle={ ( { isOpen, onToggle } ) => (
										<Button
											onClick={ onToggle }
											aria-expanded={ isOpen }
											className="components-button is-link"
										>
											{ hideByDateStart && dateI18n( settings.formats.datetime, hideByDateStart ) || __( 'Set date', 'block-control' ) }
										</Button>
									) }
									renderContent={ () => (
										<div className="block-control-datetime-picker">
											<DateTimePicker
												currentDate={ hideByDateStart }
												onChange={ ( value ) => setAttributes( { hideByDateStart: value } ) }
												is12Hour={ is12HourTime }
											/>
										</div>
									) }
								/> }
							</div>
							
							<div className="block-control-date">
								{ hideByDate && <div className="block-control-date-label"> { __( 'End date:', 'block-control' ) } </div> }
								{ hideByDate && hideByDateEnd && <Tooltip text={ __( 'Reset date', 'block-control' ) }>
									<Button
										onClick={ () => setAttributes( { hideByDateEnd: '' } ) }
									>
										<Dashicon
											className="block-control-date-reset"
											icon="no-alt"
										/>
									</Button>
								</Tooltip>}
								{ hideByDate && <Dropdown
									position="bottom right"
									renderToggle={ ( { isOpen, onToggle } ) => (
										<Button
											onClick={ onToggle }
											aria-expanded={ isOpen }
											className="components-button is-link"
										>
											{ hideByDateEnd && dateI18n( settings.formats.datetime, hideByDateEnd ) || __( 'Set date', 'block-control' ) }
										</Button>
									) }
									renderContent={ () => (
										<div className="block-control-datetime-picker">
											<DateTimePicker
												currentDate={ hideByDateEnd }
												onChange={ ( value ) => setAttributes( { hideByDateEnd: value } ) }
												is12Hour={ is12HourTime }
											/>
										</div>
									) }
								/> }
							</div>
						</div>
						
						<div className="block-control-control-area">
							{ Object.keys( blockControlStore.roles ).map( ( role, index ) => {
								return ( <CheckboxControl
									label={ blockControlStore.roles[ role ] }
									heading={ index === 0 && __( 'Hide for user roles:', 'block-control' ) || '' }
									checked={ hideRoles[ role ] }
									value={ role }
									onChange={ ( value ) => onChangeHideRoles( role, value ) }
								/> );
							} ) }
						</div>
					</PanelBody>
				</InspectorControls>
				
				{ isActive( props ) ? <Tooltip
					aria-label={ __( 'Block Control active', 'block-control' ) }
					text={ __( 'Block Control active', 'block-control' ) }
				>
					<IconButton
						className="block-control-dashicon"
						icon="visibility"
					/>
				</Tooltip> : '' }
			</Fragment>
		);
	};
}, 'addControls' );

addFilter( 'editor.BlockEdit', 'block-control/add-controls', addControls );
