( function () {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls } = wp.blockEditor || wp.editor;
	const { PanelBody, SelectControl, TextControl } = wp.components;
	const { useSelect } = wp.data;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType( 'all-in-one-faq/faq', {
		edit( props ) {
			const { attributes, setAttributes } = props;
			const { title, groupId } = attributes;

			const groups = useSelect(
				( select ) => {
					const core = select( 'core' );
					if ( ! core || ! core.getEntityRecords ) {
						return [];
					}

					return (
						core.getEntityRecords( 'postType', 'aio_faq_group', {
							per_page: -1,
							_fields: [ 'id', 'title' ],
						} ) || []
					);
				},
				[]
			);

			const groupOptions = [
				{ label: __( 'All FAQs (default)', 'all-in-one-faq' ), value: 0 },
				...groups.map( ( g ) => ( {
					label: g.title && g.title.rendered ? g.title.rendered : __( '(no title)', 'all-in-one-faq' ),
					value: g.id,
				} ) ),
			];

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'FAQ Settings', 'all-in-one-faq' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Title', 'all-in-one-faq' ),
							value: title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'FAQ Group', 'all-in-one-faq' ),
							value: groupId,
							options: groupOptions,
							onChange: function ( value ) {
								setAttributes( { groupId: parseInt( value || 0, 10 ) || 0 } );
							},
						} )
					)
				),
				el(
					'div',
					{ className: 'aio-faq aio-faq--editor-placeholder' },
					el(
						'h2',
						{ className: 'aio-faq__title' },
						title || __( 'Frequently Asked Questions', 'all-in-one-faq' )
					),
					el(
						'p',
						null,
						groupId
							? __( 'This block will display the selected FAQ group on the front-end.', 'all-in-one-faq' )
							: __( 'No specific group selected. Default FAQs will be shown.', 'all-in-one-faq' )
					)
				)
			);
		},

		save() {
			// Dynamic block; rendered in PHP via render_callback.
			return null;
		},
	} );
} )();


