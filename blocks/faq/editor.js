( function () {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
	const { PanelBody, SelectControl, TextControl, Spinner } = wp.components;
	const { useSelect } = wp.data;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	registerBlockType( 'all-in-one-faq/faq', {
		edit( props ) {
			const { attributes, setAttributes } = props;
			const { title, groupId } = attributes;
			const blockProps = useBlockProps( {
				className: 'aio-faq-block-wrapper',
			} );

			const { groups, isLoading } = useSelect(
				( select ) => {
					const coreData = select( 'core' );
					if ( ! coreData || ! coreData.getEntityRecords ) {
						return { groups: [], isLoading: false };
					}

					const query = {
						per_page: -1,
						_fields: [ 'id', 'title' ],
					};

					const selectorArgs = [ 'postType', 'aio_faq_group', query ];
					const records = coreData.getEntityRecords( ...selectorArgs );
					const isResolving = coreData.isResolving( 'getEntityRecords', selectorArgs );

					return {
						groups: records || [],
						isLoading: isResolving,
					};
				},
				[]
			);

			const groupOptions = [
				{ label: __( 'All FAQs (default)', 'all-in-one-faq' ), value: 0 },
				...( groups || [] ).map( ( g ) => ( {
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
							value: title || '',
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						isLoading
							? el( 'div', { style: { padding: '10px 0' } }, el( Spinner ) )
							: el( SelectControl, {
								label: __( 'FAQ Group', 'all-in-one-faq' ),
								value: groupId || 0,
								options: groupOptions,
								onChange: function ( value ) {
									setAttributes( { groupId: parseInt( value || 0, 10 ) || 0 } );
								},
							} )
					)
				),
				el(
					'div',
					blockProps,
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
				)
			);
		},

		save() {
			// Dynamic block; rendered in PHP via render_callback.
			return null;
		},
	} );
} )();


