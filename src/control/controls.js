/**
 * Block controls for Block Control.
 */
const { createHigherOrderComponent } = wp.compose;
const { __experimentalGetSettings, dateI18n } = wp.date;
const { Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { Button, Dashicon, DateTimePicker, Dropdown, PanelBody, RadioControl, ToggleControl, Tooltip } = wp.components;
const { addFilter } = wp.hooks;
const { __ } = wp.i18n;

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
		
		return (
			<Fragment>
				<BlockEdit { ...props } />
				
				<InspectorControls>
					<PanelBody
						title={ __( 'Visibility', 'block-control' ) }
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
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'addControls' );

addFilter( 'editor.BlockEdit', 'block-control/add-controls', addControls );
