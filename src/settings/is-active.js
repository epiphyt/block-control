import { __ } from '@wordpress/i18n';

/**
 * The description of a block an active filter applies to.
 *
 * @since	2.0.0
 */
export const ACTIVE_LABEL = __(
	'Visibility settings apply to this block.',
	'block-control'
);

/**
 * Check if Block Control has an active filter.
 *
 * @param	{object}	attributes The block attributes
 * @return	{boolean} True if a filter is active, false otherwise
 */
export const isActive = ( attributes ) => {
	const {
		hideByDate,
		hideByDateEnd,
		hideByDateStart,
		hideConditionalTags,
		hideDesktop,
		hideFeed,
		hideMobile,
		hideNumberedPages,
		hidePosts,
		hideRoles,
		hideScreenReader,
		hideViewports,
		loginStatus,
	} = attributes || {};

	if (
		( hideByDate && ( hideByDateStart || hideByDateEnd ) ) ||
		hideDesktop ||
		hideFeed ||
		hideMobile ||
		hideScreenReader ||
		hideViewports?.length ||
		( loginStatus && loginStatus !== 'none' )
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

	if ( typeof hideNumberedPages !== 'undefined' ) {
		for ( const page in hideNumberedPages ) {
			if ( !! hideNumberedPages[ page ] ) {
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
