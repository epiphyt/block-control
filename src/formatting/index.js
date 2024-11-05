import { __ } from '@wordpress/i18n';
import { registerFormatType } from '@wordpress/rich-text';
import FormattingToolbarButton from './toolbar-button';

registerFormatType( 'block-control/screen-reader-text', {
	className: 'block-control__screen-reader-text',
	edit: FormattingToolbarButton,
	tagName: 'span',
	title: __( 'Screen Reader Text', 'block-control' ),
} );
