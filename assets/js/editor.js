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

    addFilter('blocks.registerBlockType', 'wp-motion/attributes', function (settings, name) {
        if (PARTICIPATE_BLOCKS.indexOf(name) === -1 && SCENE_BLOCKS.indexOf(name) === -1) {
            return settings;
        }
        settings.attributes = Object.assign({}, settings.attributes, {
            wpMotionParticipate: { type: 'boolean' },
            wpMotionScene: { type: 'string', default: '' },
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
                        { title: __('Motion', 'wp-motion'), initialOpen: false },
                        showParticipate && el(ToggleControl, {
                            label: __('Continuer sur la page suivante', 'wp-motion'),
                            checked: !!attrs.wpMotionParticipate,
                            onChange: function (value) {
                                props.setAttributes({ wpMotionParticipate: value });
                            },
                            help: __('L’élément morph d’une page à l’autre (comme une image de carte qui devient le hero).', 'wp-motion'),
                        }),
                        showScene && el(SelectControl, {
                            label: __('Quand il entre à l’écran', 'wp-motion'),
                            value: attrs.wpMotionScene || '',
                            options: [
                                { label: __('Rien', 'wp-motion'), value: '' },
                                { label: __('Fondu', 'wp-motion'), value: 'fade-in' },
                                { label: __('Glissement', 'wp-motion'), value: 'slide-in' },
                                { label: __('Un enfant après l’autre', 'wp-motion'), value: 'stagger-children' },
                                { label: __('Mot à mot', 'wp-motion'), value: 'split-text' },
                                { label: __('Rester épinglé au scroll', 'wp-motion'), value: 'pin' },
                                { label: __('Parallax léger', 'wp-motion'), value: 'parallax' },
                            ],
                            onChange: function (value) {
                                props.setAttributes({ wpMotionScene: value });
                            },
                            help: __('Déclencheur au scroll, comme une apparition Webflow. Désactivé si le visiteur demande moins de mouvement.', 'wp-motion'),
                        })
                    )
                )
            );
        };
    }, 'withWpGsapInspector');

    addFilter('editor.BlockEdit', 'wp-motion/inspector', withInspector);
})(window.wp);
