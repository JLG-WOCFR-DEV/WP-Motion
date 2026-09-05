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
    var SelectControl = wp.components.SelectControl;
    var __ = wp.i18n.__;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var editorConfig = window.WPMOTION_EDITOR || {};

    var PARTICIPATE_BLOCKS = ['core/image', 'core/cover', 'core/heading', 'core/post-featured-image', 'core/post-title'];
    var SCENE_BLOCKS = ['core/group', 'core/heading'];
    var AUTO_BLOCKS = {
        'core/post-featured-image': 'featured',
        'core/post-title': 'title',
    };

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

    function participateSelectValue(attrs) {
        if (attrs.wpMotionParticipate === true) {
            return 'yes';
        }
        if (attrs.wpMotionParticipate === false) {
            return 'no';
        }
        return 'inherit';
    }

    function inheritLabel(blockName) {
        if (blockName === 'core/post-featured-image') {
            return editorConfig.autoFeatured
                ? __('Comme le site (oui, image mise en avant)', 'wp-motion')
                : __('Comme le site (non)', 'wp-motion');
        }
        if (blockName === 'core/post-title') {
            return editorConfig.autoTitle
                ? __('Comme le site (oui, titre)', 'wp-motion')
                : __('Comme le site (non)', 'wp-motion');
        }
        return __('Comme le site (non, sauf si vous forcez)', 'wp-motion');
    }

    function participateHelp(blockName, value) {
        if (value === 'inherit' && AUTO_BLOCKS[blockName] && editorConfig.autoFeatured && blockName === 'core/post-featured-image') {
            return __('Déjà actif dans les réglages du site. Choisissez « Jamais » pour ce bloc seulement.', 'wp-motion');
        }
        if (value === 'inherit' && blockName === 'core/post-title' && editorConfig.autoTitle) {
            return __('Déjà actif dans les réglages du site. Choisissez « Jamais » pour ce bloc seulement.', 'wp-motion');
        }
        return __('L’élément morph d’une page à l’autre (carte → hero). « Comme le site » suit WP Motion → Réglages.', 'wp-motion');
    }

    var withInspector = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            var name = props.name;
            var attrs = props.attributes || {};
            var showParticipate = PARTICIPATE_BLOCKS.indexOf(name) !== -1;
            var showScene = SCENE_BLOCKS.indexOf(name) !== -1;

            if (!showParticipate && !showScene) {
                return el(BlockEdit, props);
            }

            var participateValue = participateSelectValue(attrs);

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
                        showParticipate && el(SelectControl, {
                            label: __('Continuer sur la page suivante', 'wp-motion'),
                            value: participateValue,
                            options: [
                                { label: inheritLabel(name), value: 'inherit' },
                                { label: __('Toujours', 'wp-motion'), value: 'yes' },
                                { label: __('Jamais', 'wp-motion'), value: 'no' },
                            ],
                            onChange: function (value) {
                                if (value === 'inherit') {
                                    props.setAttributes({ wpMotionParticipate: undefined });
                                    return;
                                }
                                props.setAttributes({ wpMotionParticipate: value === 'yes' });
                            },
                            help: participateHelp(name, participateValue),
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
                            help: __('Déclencheur au scroll. Désactivé si le visiteur demande moins de mouvement.', 'wp-motion'),
                        })
                    )
                )
            );
        };
    }, 'withWpMotionInspector');

    addFilter('editor.BlockEdit', 'wp-motion/inspector', withInspector);
})(window.wp);
