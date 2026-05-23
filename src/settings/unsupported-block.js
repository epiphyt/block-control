import { applyFilters } from '@wordpress/hooks';

/**
 * List of unsupported blocks. Block Control doesn't work here.
 *
 * @since	1.1.5
 *
 * @param	{Array}	unsupportedBlocks List of unsupported blocks
 */
export const UNSUPPORTED_BLOCKS = applyFilters(
	'blockControl.unsupportedBlocks',
	[]
);
