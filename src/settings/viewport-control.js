/**
 * Control to hide a block at certain viewports.
 */
import { speak } from '@wordpress/a11y';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { closeSmall, plus, settings } from '@wordpress/icons';

import { store as viewportStore } from './store';

const MAX_CONDITION_LENGTH = 200;

/**
 * Normalize a media condition.
 *
 * @param	{string}	condition The media condition to normalize
 * @return	{string} The normalized media condition
 */
export const normalizeCondition = ( condition ) => {
	if ( typeof condition !== 'string' ) {
		return '';
	}

	const normalized = condition.trim().replace( /\s+/g, ' ' );

	// allow pasting a complete media query
	return /^@media/i.test( normalized )
		? normalized.slice( 6 ).trim()
		: normalized;
};

/**
 * Check whether a media condition is valid.
 *
 * @param	{string}	condition The media condition to check
 * @return	{boolean} Whether the media condition is valid
 */
export const isValidCondition = ( condition ) => {
	if ( ! condition || condition.length > MAX_CONDITION_LENGTH ) {
		return false;
	}

	if ( ! condition.includes( '(' ) ) {
		return false;
	}

	if (
		( condition.match( /\(/g ) || [] ).length !==
		( condition.match( /\)/g ) || [] ).length
	) {
		return false;
	}

	if ( ! /^[a-zA-Z0-9\s():,./<>=-]+$/.test( condition ) ) {
		return false;
	}

	// browsers normalize a query they cannot parse to 'not all'
	return window.matchMedia( condition ).media !== 'not all';
};

/**
 * Get the label of a preset.
 *
 * @param	{object}	preset The preset
 * @return	{string} The label of the preset
 */
const getPresetLabel = ( preset ) =>
	sprintf(
		/* translators: 1: viewport name, 2: media condition */
		__( '%1$s (%2$s)', 'block-control' ),
		preset.label,
		normalizeCondition( preset.media_query )
	);

/**
 * Get the rows of a list of attribute entries.
 *
 * @param	{string[]}	entries The list of preset slugs and media conditions
 * @param	{object}	presets The available presets
 * @param	{object}	lastId Reference to the last used row ID
 * @return	{object[]} The list of rows
 */
const getRows = ( entries, presets, lastId ) =>
	entries.map( ( entry ) => {
		lastId.current += 1;

		return {
			id: lastId.current,
			isCustom: ! presets[ entry ],
			value: entry,
		};
	} );

/**
 * Input of a custom media condition.
 *
 * The condition is only committed once it is valid, so that an incomplete
 * condition doesn't end up as a block attribute while it is being typed.
 *
 * @param	{object}	props Component props
 * @return	{Element} The input element
 */
const CustomCondition = ( { id, label, onChange, value } ) => {
	const [ draft, setDraft ] = useState( value );
	const isInvalid = draft !== '' && ! isValidCondition( draft );

	useEffect( () => {
		setDraft( value );
	}, [ value ] );

	const commit = () => {
		const condition = normalizeCondition( draft );

		if ( condition === value || ! isValidCondition( condition ) ) {
			return;
		}

		setDraft( condition );
		onChange( condition );
	};

	return (
		<TextControl
			__next40pxDefaultSize
			className="block-control__viewport--value"
			help={
				isInvalid
					? __( 'This is not a valid media query.', 'block-control' )
					: undefined
			}
			hideLabelFromVision
			id={ id }
			label={ label }
			onBlur={ commit }
			onChange={ setDraft }
			onKeyDown={ ( event ) => {
				if ( event.key !== 'Enter' ) {
					return;
				}

				event.preventDefault();
				commit();
			} }
			placeholder={ __( '(min-width: 1200px)', 'block-control' ) }
			value={ draft }
		/>
	);
};

const ViewportControl = ( { onChange, value } ) => {
	const instanceId = useInstanceId(
		ViewportControl,
		'block-control-viewport'
	);
	const { custom, pending, presets } = useSelect( ( select ) => {
		const { getCustomViewports, getPendingViewports, getPresets } =
			select( viewportStore );

		return {
			custom: getCustomViewports(),
			pending: getPendingViewports(),
			presets: getPresets(),
		};
	}, [] );
	const { addCustomViewport } = useDispatch( viewportStore );
	const conditions = useMemo(
		() => [ ...new Set( [ ...custom, ...pending ] ) ],
		[ custom, pending ]
	);
	const entries = useMemo(
		() => ( Array.isArray( value ) ? value : [] ),
		[ value ]
	);
	const lastId = useRef( 0 );
	const addButton = useRef( null );
	const removeButtons = useRef( {} );
	const selects = useRef( {} );
	const [ rows, setRows ] = useState( () =>
		getRows( entries, presets, lastId )
	);
	const selected = rows.map( ( row ) => row.value ).filter( Boolean );
	// a string is used to compare the entries by value instead of by identity
	const selectedKey = selected.join( '\n' );
	const entriesKey = entries.join( '\n' );

	// rows without an entry only exist locally, which makes the entries the
	// source of truth for every change from outside, like switching to another
	// block or undoing a change
	useEffect( () => {
		if ( entriesKey === selectedKey ) {
			return;
		}

		setRows( getRows( entries, presets, lastId ) );
		// `entries` and `selected` are compared by their key
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ entriesKey, selectedKey, presets ] );

	const update = ( newRows ) => {
		setRows( newRows );
		onChange( newRows.map( ( row ) => row.value ).filter( Boolean ) );
	};
	const setRow = ( id, changes ) =>
		update(
			rows.map( ( row ) =>
				row.id === id ? { ...row, ...changes } : row
			)
		);
	const addRow = () => {
		lastId.current += 1;

		const id = lastId.current;

		setRows( [ ...rows, { id, isCustom: false, value: '' } ] );

		// wait for the re-render, as the new row doesn't exist yet
		window.setTimeout( () => {
			selects.current[ id ]?.focus();
		} );
	};
	const removeRow = ( id ) => {
		const index = rows.findIndex( ( row ) => row.id === id );
		const newRows = rows.filter( ( row ) => row.id !== id );
		// the row that takes the place of the removed one, or the new last one
		const nextRow = newRows[ index ] || newRows[ newRows.length - 1 ];

		update( newRows );
		speak( __( 'Media query removed.', 'block-control' ) );

		// wait for the re-render, as the button removes itself and would
		// otherwise leave the focus behind
		window.setTimeout( () => {
			const nextButton = nextRow
				? removeButtons.current[ nextRow.id ]
				: null;

			if ( nextButton?.isConnected ) {
				nextButton.focus();
			} else {
				addButton.current?.focus();
			}
		} );
	};
	// an entry that is already used in another row would only generate the
	// very same styles again
	const isAvailable = ( entry, row ) =>
		entry === row.value || ! selected.includes( entry );

	return (
		<div className="block-control__viewports">
			{ rows.map( ( row, index ) => {
				const rowId = instanceId + '-' + row.id;
				const label = sprintf(
					/* translators: media query number */
					__( 'Media query %d', 'block-control' ),
					index + 1
				);

				return (
					<div className="block-control__viewport" key={ row.id }>
						{ row.isCustom ? (
							<CustomCondition
								id={ rowId }
								label={ label }
								onChange={ ( condition ) => {
									addCustomViewport( condition );
									setRow( row.id, { value: condition } );
								} }
								value={ row.value }
							/>
						) : (
							<SelectControl
								className="block-control__viewport--value"
								hideLabelFromVision
								id={ rowId }
								label={ label }
								onChange={ ( newValue ) =>
									setRow( row.id, { value: newValue } )
								}
								options={ [
									{
										label: __(
											'Select a media query',
											'block-control'
										),
										value: '',
									},
									...Object.keys( presets )
										.filter( ( slug ) =>
											isAvailable( slug, row )
										)
										.map( ( slug ) => ( {
											label: getPresetLabel(
												presets[ slug ]
											),
											value: slug,
										} ) ),
									...conditions
										.filter( ( condition ) =>
											isAvailable( condition, row )
										)
										.map( ( condition ) => ( {
											label: condition,
											value: condition,
										} ) ),
								] }
								ref={ ( element ) => {
									if ( element ) {
										selects.current[ row.id ] = element;
									} else {
										delete selects.current[ row.id ];
									}
								} }
								value={ row.value }
							/>
						) }

						<Button
							icon={ settings }
							isPressed={ row.isCustom }
							label={
								row.isCustom
									? __(
											'Use a predefined media query',
											'block-control'
									  )
									: __(
											'Use a custom media query',
											'block-control'
									  )
							}
							onClick={ () =>
								setRow( row.id, {
									isCustom: ! row.isCustom,
									value: '',
								} )
							}
							size="small"
						/>
						<Button
							icon={ closeSmall }
							isDestructive
							label={ __(
								'Remove media query',
								'block-control'
							) }
							onClick={ () => removeRow( row.id ) }
							ref={ ( element ) => {
								if ( element ) {
									removeButtons.current[ row.id ] = element;
								} else {
									delete removeButtons.current[ row.id ];
								}
							} }
							size="small"
						/>
					</div>
				);
			} ) }

			<Button
				className="block-control__viewports--add"
				icon={ plus }
				onClick={ addRow }
				ref={ addButton }
				size="compact"
				variant="secondary"
			>
				{ __( 'Add media query', 'block-control' ) }
			</Button>
		</div>
	);
};

export default ViewportControl;
