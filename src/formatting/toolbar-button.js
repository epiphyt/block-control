import { RichTextToolbarButton } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { unseen } from '@wordpress/icons';
import { toggleFormat } from '@wordpress/rich-text';

export default function FormattingToolbarButton( { isActive, onChange, value } ) {
	return (
		<RichTextToolbarButton
			icon={ unseen }
			isActive={ isActive }
			onClick={ () => {
				onChange(
					toggleFormat( value, {
						type: 'block-control/screen-reader-text',
					} )
				);
			} }
			title={ __( 'Screen Reader Text', 'block-control' ) }
		/>
	);
}
