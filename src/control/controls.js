/**
 * Block controls for Block Control.
 */
const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { PanelBody, RadioControl, ToggleControl } = wp.components;
const { addFilter } = wp.hooks;
const { __ } = wp.i18n;

/**
 * Create HOC to add our controls to inspector controls of block.
 */
const addControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const {
			attributes: {
				hideDesktop,
				hideMobile,
				hideTablet,
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
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'addControls' );

addFilter( 'editor.BlockEdit', 'block-control/add-controls', addControls );
