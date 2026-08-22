/**
 * Store of the viewports available to every block of the current editor.
 *
 * Custom media queries are added to the store as soon as they are used, which
 * makes them selectable for every other block right away.
 */
import { createReduxStore, register } from '@wordpress/data';

export const STORE_NAME = 'block-control/viewports';

const DEFAULT_STATE = {
	custom: [],
	pending: [],
	presets: {},
};

const actions = {
	/**
	 * Add a custom media condition.
	 *
	 * @param	{string}	condition The media condition to add
	 * @return	{object} The action
	 */
	addCustomViewport( condition ) {
		return {
			condition,
			type: 'ADD_CUSTOM_VIEWPORT',
		};
	},

	/**
	 * Mark media conditions as stored site-wide.
	 *
	 * @param	{string[]}	conditions The media conditions that are stored
	 * @return	{object} The action
	 */
	markSynced( conditions ) {
		return {
			conditions,
			type: 'MARK_SYNCED',
		};
	},
};

const selectors = {
	/**
	 * Get the media conditions that are stored site-wide.
	 *
	 * @param	{object}	state The current state
	 * @return	{string[]} The list of media conditions
	 */
	getCustomViewports( state ) {
		return state.custom;
	},

	/**
	 * Get the media conditions that are not stored site-wide yet.
	 *
	 * @param	{object}	state The current state
	 * @return	{string[]} The list of media conditions
	 */
	getPendingViewports( state ) {
		return state.pending;
	},

	/**
	 * Get the viewport presets of the current theme.
	 *
	 * @param	{object}	state The current state
	 * @return	{object} The presets by their slug
	 */
	getPresets( state ) {
		return state.presets;
	},
};

const reducer = ( state = DEFAULT_STATE, action = {} ) => {
	switch ( action.type ) {
		case 'ADD_CUSTOM_VIEWPORT':
			if (
				! action.condition ||
				state.custom.includes( action.condition ) ||
				state.pending.includes( action.condition )
			) {
				return state;
			}

			return {
				...state,
				pending: [ ...state.pending, action.condition ],
			};
		case 'MARK_SYNCED':
			return {
				...state,
				custom: [
					...new Set( [ ...state.custom, ...action.conditions ] ),
				],
				pending: state.pending.filter(
					( condition ) => ! action.conditions.includes( condition )
				),
			};
		default:
			return state;
	}
};

export const store = createReduxStore( STORE_NAME, {
	actions,
	initialState: {
		...DEFAULT_STATE,
		// the localized data is read from the window, as an undeclared global
		// would throw while the module is evaluated
		custom: window.blockControlStore?.viewports?.custom || [],
		presets: window.blockControlStore?.viewports?.presets || {},
	},
	reducer,
	selectors,
} );

register( store );
