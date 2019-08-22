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
				hide_desktop,
				hide_mobile,
				hide_tablet,
				login_status,
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
						<ToggleControl
							label={ __( 'Hide on mobile devices', 'block-control' ) }
							value={ hide_mobile }
							checked={ !! hide_mobile }
							onChange={ ( value ) => setAttributes( { hide_mobile: value } ) }
						/>
						<ToggleControl
							label={ __( 'Hide on tablets', 'block-control' ) }
							value={ hide_tablet }
							checked={ !! hide_tablet }
							onChange={ ( value ) => setAttributes( { hide_tablet: value } ) }
						/>
						<ToggleControl
							label={ __( 'Hide on desktops', 'block-control' ) }
							value={ hide_desktop }
							checked={ !! hide_desktop }
							onChange={ ( value ) => setAttributes( { hide_desktop: value } ) }
						/>
						<RadioControl
							label={ __( 'Login status', 'block-control' ) }
							selected={ login_status }
							options={ [
								{ label: __( 'Show for all users', 'block-control' ), value: 'none' },
								{ label: __( 'Show for logged in users', 'block-control' ), value: 'logged-in' },
								{ label: __( 'Show for logged out users', 'block-control' ), value: 'logged-out' },
							] }
							onChange={ ( value ) => setAttributes( { login_status: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'addControls' );

addFilter( 'editor.BlockEdit', 'block-control/add-controls', addControls );
