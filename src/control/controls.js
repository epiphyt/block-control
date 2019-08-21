/**
 * Block controls for Block Control.
 */
const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { PanelBody, ToggleControl } = wp.components;
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
			},
			setAttributes,
		} = props;
		
		return (
			<Fragment>
				<BlockEdit { ...props } />
				
				<InspectorControls>
					<PanelBody
						title={ __( 'Visibility', 'block-control' ) }
					>
						<ToggleControl
							label={ __( 'Hide on Mobile', 'block-control' ) }
							value={ hide_mobile }
							checked={ !! hide_mobile }
							onChange={ ( value ) => setAttributes( { hide_mobile: value } ) }
						/>
						<ToggleControl
							label={ __( 'Hide on Tablets', 'block-control' ) }
							value={ hide_tablet }
							checked={ !! hide_tablet }
							onChange={ ( value ) => setAttributes( { hide_tablet: value } ) }
						/>
						<ToggleControl
							label={ __( 'Hide on Desktop', 'block-control' ) }
							value={ hide_desktop }
							checked={ !! hide_desktop }
							onChange={ ( value ) => setAttributes( { hide_desktop: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'addControls' );

addFilter( 'editor.BlockEdit', 'block-control/add-controls', addControls );
