/**
 * Member Lookup block — editor registration
 *
 * Registered as a dynamic block; output is rendered server-side via the
 * [pausatf_members] shortcode logic in PAUSATF_CPT_Member::render_shortcode().
 *
 * Requires the visitor to be logged in (enforced server-side).
 *
 * Usage in the block editor:
 *   Insert → "Member Lookup" → optionally filter by Club, set Per Page.
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType( 'pausatf-membership/member-lookup', {
    title:       __( 'Member Lookup', 'pausatf-membership' ),
    description: __( 'Display the PA/USATF member roster (login required).', 'pausatf-membership' ),
    icon:        'id',
    category:    'widgets',
    keywords:    [ 'members', 'pausatf', 'roster' ],

    attributes: {
        club:     { type: 'string', default: '' },
        per_page: { type: 'string', default: '50' },
    },

    edit( { attributes, setAttributes } ) {
        const { club, per_page } = attributes;

        return wp.element.createElement(
            wp.element.Fragment,
            null,

            wp.element.createElement(
                InspectorControls,
                null,
                wp.element.createElement(
                    PanelBody,
                    { title: __( 'Display Settings', 'pausatf-membership' ) },

                    wp.element.createElement( TextControl, {
                        label:    __( 'Club ID or slug (leave blank for all)', 'pausatf-membership' ),
                        value:    club,
                        onChange: ( val ) => setAttributes( { club: val } ),
                    } ),

                    wp.element.createElement( TextControl, {
                        label:    __( 'Per page (default 50)', 'pausatf-membership' ),
                        value:    per_page,
                        type:     'number',
                        onChange: ( val ) => setAttributes( { per_page: val } ),
                    } )
                )
            ),

            wp.element.createElement(
                'div',
                { className: 'pausatf-block-member-lookup-preview' },
                wp.element.createElement( 'p', null, __( '[Member Lookup — login required; rendered on the front end]', 'pausatf-membership' ) )
            )
        );
    },

    // Dynamic block — rendered server-side; save() returns null.
    save() {
        return null;
    },
} );
