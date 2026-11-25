/**
 * Karina Star Ratings block (editor). Written in plain ES5 so no build step is required.
 *
 * @package Karina_Star_Ratings
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var blockEditor = wp.blockEditor;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;

	/**
	 * ID of the post being edited, 0 in the Site Editor or the widgets screen.
	 *
	 * @return {number} Post ID.
	 */
	function editorPostId() {
		try {
			var editor = wp.data.select( 'core/editor' );
			var id = editor && editor.getCurrentPostId ? editor.getCurrentPostId() : 0;
			return typeof id === 'number' ? id : 0;
		} catch ( e ) {
			return 0;
		}
	}

	wp.blocks.registerBlockType( 'ratingpress/rating', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = blockEditor.useBlockProps ? blockEditor.useBlockProps() : {};
			var contextPostId = props.context && props.context.postId ? parseInt( props.context.postId, 10 ) || 0 : 0;

			// Inside a Query Loop the preview must show the post of each loop item. The
			// block context is not sent to the server-side renderer, so pass it explicitly.
			var previewAttributes = attributes;
			if ( ! attributes.postId && contextPostId ) {
				previewAttributes = {};
				Object.keys( attributes ).forEach( function ( key ) {
					previewAttributes[ key ] = attributes[ key ];
				} );
				previewAttributes.postId = contextPostId;
			}

			var inspector = el(
				blockEditor.InspectorControls,
				null,
				el(
					components.PanelBody,
					{ title: __( 'Rating settings', 'karina-star-ratings' ), initialOpen: true },
					el( components.TextControl, {
						label: __( 'Post ID', 'karina-star-ratings' ),
						help: __( 'Leave 0 to use the current post.', 'karina-star-ratings' ),
						type: 'number',
						min: 0,
						value: attributes.postId,
						onChange: function ( value ) {
							setAttributes( { postId: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el( components.TextControl, {
						label: __( 'Heading', 'karina-star-ratings' ),
						value: attributes.heading,
						onChange: function ( value ) {
							setAttributes( { heading: value } );
						}
					} ),
					el( components.SelectControl, {
						label: __( 'Alignment', 'karina-star-ratings' ),
						value: attributes.alignment,
						options: [
							{ label: __( 'Default', 'karina-star-ratings' ), value: '' },
							{ label: __( 'Start', 'karina-star-ratings' ), value: 'start' },
							{ label: __( 'Center', 'karina-star-ratings' ), value: 'center' },
							{ label: __( 'End', 'karina-star-ratings' ), value: 'end' }
						],
						onChange: function ( value ) {
							setAttributes( { alignment: value } );
						}
					} ),
					el( components.RangeControl, {
						label: __( 'Icon size (px)', 'karina-star-ratings' ),
						help: __( '0 uses the global setting.', 'karina-star-ratings' ),
						min: 0,
						max: 72,
						value: attributes.size,
						onChange: function ( value ) {
							setAttributes( { size: value || 0 } );
						}
					} ),
					el( components.ToggleControl, {
						label: __( 'Show legend', 'karina-star-ratings' ),
						checked: attributes.showLegend,
						onChange: function ( value ) {
							setAttributes( { showLegend: value } );
						}
					} ),
					el( components.ToggleControl, {
						label: __( 'Read only', 'karina-star-ratings' ),
						checked: attributes.readonly,
						onChange: function ( value ) {
							setAttributes( { readonly: value } );
						}
					} )
				)
			);

			return el(
				'div',
				blockProps,
				inspector,
				el( ServerSideRender, {
					block: 'ratingpress/rating',
					attributes: previewAttributes,
					EmptyResponsePlaceholder: function () {
						return el(
							'p',
							{ className: 'ratingpress-block-placeholder' },
							previewAttributes.postId || editorPostId() ?
								__( 'Karina Star Ratings: ratings are disabled for this content.', 'karina-star-ratings' ) :
								__( 'Karina Star Ratings: the rating of the current post will be displayed here.', 'karina-star-ratings' )
						);
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
}( window.wp ) );
