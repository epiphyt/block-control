/**
 * Visibility indicator for the document overview.
 *
 * The document overview (list view) has no extension point to add an indicator
 * to a row, so the indicator is rendered through a portal into a container
 * that gets appended to the row of every block visibility settings apply to.
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { Tooltip, VisuallyHidden } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createPortal, useEffect, useMemo, useState } from '@wordpress/element';
import { Icon, unseen } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

import { ACTIVE_LABEL, isActive } from './is-active';

const INDICATOR_CLASS = 'block-control-list-view-indicator';
const LABEL_SELECTOR =
	'.block-editor-list-view-block-select-button__label-wrapper';
const TREE_SELECTOR = '.block-editor-list-view-tree';

/**
 * The indicator of a row, telling that the block is not always visible.
 *
 * @return	{Element} The indicator element
 */
const Indicator = () => (
	<Tooltip text={ ACTIVE_LABEL }>
		<span className={ INDICATOR_CLASS + '__icon' }>
			<Icon icon={ unseen } />
			<VisuallyHidden as="span">{ ACTIVE_LABEL }</VisuallyHidden>
		</span>
	</Tooltip>
);

/**
 * Get the container of every row an indicator belongs to.
 *
 * Containers are reused so that the rendered indicators are not
 * unmounted and mounted again on every change of the document overview.
 *
 * @param	{string[]}	clientIds The client IDs of the blocks to indicate
 * @return	{object[]} List of client IDs and their container element
 */
const getContainers = ( clientIds ) => {
	const containers = [];

	for ( const tree of document.querySelectorAll( TREE_SELECTOR ) ) {
		for ( const clientId of clientIds ) {
			const label = tree.querySelector(
				`[data-block="${ clientId }"] ${ LABEL_SELECTOR }`
			);

			if ( ! label ) {
				continue;
			}

			let container = label.querySelector( '.' + INDICATOR_CLASS );

			if ( ! container ) {
				container = document.createElement( 'span' );
				container.className = INDICATOR_CLASS;
				label.append( container );
			}

			containers.push( { clientId, container } );
		}
	}

	return containers;
};

/**
 * Add an indicator to every row of the document overview representing a block
 * visibility settings apply to.
 *
 * @return	{Element} The indicator elements
 */
const ListViewIndicators = () => {
	const hiddenBlocks = useSelect( ( select ) => {
		const { getBlockAttributes, getClientIdsWithDescendants } =
			select( blockEditorStore );

		return getClientIdsWithDescendants()
			.filter( ( clientId ) =>
				isActive( getBlockAttributes( clientId ) )
			)
			.join( ' ' );
	}, [] );
	// a string is used as the selected value to keep the identity stable
	const clientIds = useMemo(
		() => ( hiddenBlocks ? hiddenBlocks.split( ' ' ) : [] ),
		[ hiddenBlocks ]
	);
	const [ containers, setContainers ] = useState( [] );

	useEffect( () => {
		if ( ! clientIds.length ) {
			setContainers( [] );

			return;
		}

		const sync = () => {
			const current = getContainers( clientIds );

			setContainers( ( previous ) =>
				previous.length === current.length &&
				previous.every(
					( item, index ) =>
						item.container === current[ index ].container
				)
					? previous
					: current
			);
		};
		let frame = null;
		// the document overview is rendered and updated outside of our
		// control, so we need to watch the DOM for rows to appear
		const observer = new window.MutationObserver( () => {
			if ( frame !== null ) {
				return;
			}

			frame = window.requestAnimationFrame( () => {
				frame = null;

				sync();
			} );
		} );

		sync();
		observer.observe( document.body, { childList: true, subtree: true } );

		return () => {
			observer.disconnect();

			if ( frame !== null ) {
				window.cancelAnimationFrame( frame );
			}

			for ( const container of document.querySelectorAll(
				'.' + INDICATOR_CLASS
			) ) {
				container.remove();
			}
		};
	}, [ clientIds ] );

	return (
		<>
			{ containers.map( ( { clientId, container } ) =>
				createPortal( <Indicator />, container, clientId )
			) }
		</>
	);
};

registerPlugin( 'block-control-list-view-indicators', {
	render: ListViewIndicators,
} );
