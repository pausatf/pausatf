/**
 * Club Directory block — editor registration
 *
 * Registered as a dynamic block; output is rendered server-side via the
 * [pausatf_clubs] shortcode logic in PAUSATF_CPT_Club::render_shortcode().
 *
 * Usage in the block editor:
 *   Insert → "Club Directory" → optionally set Order / Per Page attributes.
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType( 'pausatf-membership/club-directory', {
    title:       __( 'Club Directory', 'pausatf-membership' ),
    description: __( 'Display the PA/USATF club directory.', 'pausatf-membership' ),
    icon:        'groups',
    category:    'widgets',
    keywords:    [ 'clubs', 'pausatf', 'directory' ],

    attributes: {
        orderby:  { type: 'string', default: 'title' },
        order:    { type: 'string', default: 'ASC' },
        per_page: { type: 'string', default: '-1' },
    },

    edit( { attributes, setAttributes } ) {
        const { orderby, order, per_page } = attributes;

        return wp.element.createElement(
            wp.element.Fragment,
            null,

            wp.element.createElement(
                InspectorControls,
                null,
                wp.element.createElement(
                    PanelBody,
                    { title: __( 'Display Settings', 'pausatf-membership' ) },

                    wp.element.createElement( SelectControl, {
                        label:    __( 'Order by', 'pausatf-membership' ),
                        value:    orderby,
                        options:  [
                            { label: __( 'Title', 'pausatf-membership' ),   value: 'title' },
                            { label: __( 'Date',  'pausatf-membership' ),   value: 'date'  },
                        ],
                        onChange: ( val ) => setAttributes( { orderby: val } ),
                    } ),

                    wp.element.createElement( SelectControl, {
                        label:    __( 'Order', 'pausatf-membership' ),
                        value:    order,
                        options:  [
                            { label: __( 'Ascending',  'pausatf-membership' ), value: 'ASC'  },
                            { label: __( 'Descending', 'pausatf-membership' ), value: 'DESC' },
                        ],
                        onChange: ( val ) => setAttributes( { order: val } ),
                    } ),

                    wp.element.createElement( TextControl, {
                        label:    __( 'Max clubs (−1 = all)', 'pausatf-membership' ),
                        value:    per_page,
                        type:     'number',
                        onChange: ( val ) => setAttributes( { per_page: val } ),
                    } )
                )
            ),

            wp.element.createElement(
                'div',
                { className: 'pausatf-block-club-directory-preview' },
                wp.element.createElement( 'p', null, __( '[Club Directory — rendered on the front end]', 'pausatf-membership' ) )
            )
        );
    },

    // Dynamic block — rendered server-side; save() returns null.
    save() {
        return null;
    },
} );
