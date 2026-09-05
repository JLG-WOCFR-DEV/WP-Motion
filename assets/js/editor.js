(function (wp) {
    'use strict';

    if (!wp || !wp.hooks || !wp.element || !wp.components) {
        return;
    }

    var addFilter = wp.hooks.addFilter;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var InspectorControls = (wp.blockEditor && wp.blockEditor.InspectorControls) || (wp.editor && wp.editor.InspectorControls);
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var __ = wp.i18n.__;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;

    var PARTICIPATE_BLOCKS = ['core/image', 'core/cover', 'core/heading', 'core/post-featured-image', 'core/post-title'];
    var SCENE_BLOCKS = ['core/group', 'core/heading'];

    addFilter('blocks.registerBlockType', 'wp-gsap/attributes', function (settings, name) {
        if (PARTICIPATE_BLOCKS.indexOf(name) === -1 && SCENE_BLOCKS.indexOf(name) === -1) {
            return settings;
        }
        settings.attributes = Object.assign({}, settings.attributes, {
            wpGsapParticipate: { type: 'boolean' },
            wpGsapScene: { type: 'string', default: '' },
        });
        return settings;
    });

    var withInspector = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            var name = props.name;
            var attrs = props.attributes || {};
            var showParticipate = PARTICIPATE_BLOCKS.indexOf(name) !== -1;
            var showScene = SCENE_BLOCKS.indexOf(name) !== -1;

            if (!showParticipate && !showScene) {
                return el(BlockEdit, props);
            }

            return el(
                Fragment,
                {},
                el(BlockEdit, props),
                InspectorControls && el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('WP-GSAP', 'wp-gsap'), initialOpen: false },
                        showParticipate && el(ToggleControl, {
                            label: __('Participe à la transition de page', 'wp-gsap'),
                            checked: !!attrs.wpGsapParticipate,
                            onChange: function (value) {
                                props.setAttributes({ wpGsapParticipate: value });
                            },
                            help: __('Donne un nom View Transition stable (image, cover ou titre).', 'wp-gsap'),
                        }),
                        showScene && el(SelectControl, {
                            label: __('Scène in-page', 'wp-gsap'),
                            value: attrs.wpGsapScene || '',
                            options: [
                                { label: __('Aucune', 'wp-gsap'), value: '' },
                                { label: __('Apparition (fondu)', 'wp-gsap'), value: 'fade-in' },
                                { label: __('Apparition (glissement)', 'wp-gsap'), value: 'slide-in' },
                                { label: __('Stagger des enfants', 'wp-gsap'), value: 'stagger-children' },
                                { label: __('SplitText (GSAP)', 'wp-gsap'), value: 'split-text' },
                                { label: __('Section pinnée (GSAP)', 'wp-gsap'), value: 'pin' },
                                { label: __('Parallax (GSAP)', 'wp-gsap'), value: 'parallax' },
                            ],
                            onChange: function (value) {
                                props.setAttributes({ wpGsapScene: value });
                            },
                            help: __('SplitText, pin et parallax chargent GSAP depuis le CDN, uniquement sur cette page.', 'wp-gsap'),
                        })
                    )
                )
            );
        };
    }, 'withWpGsapInspector');

    addFilter('editor.BlockEdit', 'wp-gsap/inspector', withInspector);
})(window.wp);
