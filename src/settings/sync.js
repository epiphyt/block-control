/**
 * Synchronization of newly created media queries.
 *
 * Media queries that were created in the current editor instance are stored
 * site-wide once the post is saved, which makes them available to every block
 * on every other post afterwards.
 */
import apiFetch from '@wordpress/api-fetch';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

import { store as viewportStore } from './store';

const ViewportSync = () => {
	const { isAutosaving, isSaving } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			isAutosaving: !! editor?.isAutosavingPost(),
			isSaving: !! editor?.isSavingPost(),
		};
	}, [] );
	const pending = useSelect(
		( select ) => select( viewportStore ).getPendingViewports(),
		[]
	);
	const { markSynced } = useDispatch( viewportStore );
	const wasSaving = useRef( false );
	const wasAutosaving = useRef( false );
	// the pending media queries are read on save only, so a change of them
	// should not restart the synchronization
	const pendingRef = useRef( pending );

	pendingRef.current = pending;

	useEffect( () => {
		const isSaved =
			wasSaving.current && ! isSaving && ! wasAutosaving.current;

		wasAutosaving.current = isAutosaving;
		wasSaving.current = isSaving;

		if ( ! isSaved || ! pendingRef.current.length ) {
			return;
		}

		const viewports = pendingRef.current;

		apiFetch( {
			data: { viewports },
			method: 'POST',
			path: '/block-control/v1/viewports',
		} )
			.then( ( response ) => {
				markSynced( response?.viewports || viewports );
			} )
			.catch( () => {
				// keep the media queries pending to retry on the next save
			} );
	}, [ isAutosaving, isSaving, markSynced ] );

	return null;
};

registerPlugin( 'block-control-viewport-sync', {
	render: ViewportSync,
} );
