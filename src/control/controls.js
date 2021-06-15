/**
 * Block controls for Block Control.
 */
const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { Dashicon, IconButton, PanelBody, RadioControl, ToggleControl, Tooltip } = wp.components;
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
			loginStatus,
		},
	} = props;
	
	if (
		hideDesktop
		|| hideMobile
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
				hideDesktop,
				hideMobile,
				loginStatus,
			},
			setAttributes,
		} = props;
		
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
