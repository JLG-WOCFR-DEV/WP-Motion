(function () {
    'use strict';

    var config = window.WPMOTION || {};
    var hardcoded = ['/wp-admin', '/wp-login.php', '/wp-cron.php', '/wp-json/', '/xmlrpc.php', '/feed'];
    var leaving = false;
    var entered = false;

    function motionApi() {
        return window.Motion || null;
    }

    function prefersReduced() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function durationSec() {
        return Math.max(0.08, (config.durationMs || 400) / 1000);
    }

    function normalizePath(url) {
        try {
            var parsed = new URL(url, window.location.origin);
            var path = parsed.pathname || '/';
            if (path !== '/' && path.slice(-1) === '/') {
                path = path.slice(0, -1);
            }
            return path.toLowerCase();
        } catch (e) {
            return '/';
        }
    }

    function pathMatches(path, pattern) {
        pattern = normalizePath(pattern);
        if (path === pattern) {
            return true;
        }
        return pattern !== '/' && path.indexOf(pattern + '/') === 0;
    }

    function isExcluded(url) {
        var path = normalizePath(url);
        var i;
        for (i = 0; i < hardcoded.length; i += 1) {
            if (pathMatches(path, hardcoded[i])) {
                return true;
            }
        }
        var user = config.excludePaths || [];
        for (i = 0; i < user.length; i += 1) {
            if (pathMatches(path, user[i])) {
                return true;
            }
        }
        return false;
    }

    function sameOrigin(url) {
        try {
            return new URL(url, window.location.origin).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function resolveTemplate(url) {
        var path = normalizePath(url);
        var known = config.known || {};
        var best = '';
        var bestLen = -1;
        Object.keys(known).forEach(function (template) {
            var knownPath = normalizePath(known[template]);
            if (knownPath === '/') {
                if (path === '/' && 1 > bestLen) {
                    best = template;
                    bestLen = 1;
                }
                return;
            }
            if ((path === knownPath || path.indexOf(knownPath + '/') === 0) && knownPath.length > bestLen) {
                best = template;
                bestLen = knownPath.length;
            }
        });
        if (best) {
            return best;
        }
        return path === '/' ? 'home' : 'unknown';
    }

    function sideMatches(actual, rule) {
        if (rule === '*' || rule === actual) {
            return true;
        }
        return rule === 'singular' && ['single', 'page', 'product', 'singular'].indexOf(actual) !== -1;
    }

    function matchRoute(from, to) {
        var routes = config.routes || [];
        var best = null;
        var bestScore = -1;
        var fallback = config.preset || 'fade';
        routes.forEach(function (route, index) {
            if (!route || !sideMatches(from, route.from) || !sideMatches(to, route.to)) {
                return;
            }
            var score = 0;
            if (route.from === from) {
                score += 2;
            }
            if (route.to === to) {
                score += 2;
            }
            score = (score * 1000) - index;
            if (score > bestScore) {
                bestScore = score;
                best = route;
            }
        });
        return best || { from: '*', to: '*', preset: fallback, shared: false };
    }

    function applyTypes(viewTransition, from, to, route) {
        if (!viewTransition || !viewTransition.types || !viewTransition.types.add) {
            return;
        }
        if (prefersReduced()) {
            if ((config.reducedMotion || 'fade') === 'none') {
                if (typeof viewTransition.skipTransition === 'function') {
                    viewTransition.skipTransition();
                }
                return;
            }
            viewTransition.types.add('reduced');
            viewTransition.types.add('fade');
            return;
        }
        var preset = route && route.preset ? route.preset : (config.preset || 'fade');
        if (preset === 'none') {
            if (typeof viewTransition.skipTransition === 'function') {
                viewTransition.skipTransition();
            }
            return;
        }
        viewTransition.types.add(preset);
        viewTransition.types.add('from-' + from);
        viewTransition.types.add('to-' + to);
    }

    function onSwap(event) {
        if (!event.viewTransition) {
            return;
        }
        var dest = event.activation && event.activation.entry ? event.activation.entry.url : '';
        if (!dest || !sameOrigin(dest) || isExcluded(dest)) {
            event.viewTransition.skipTransition();
            return;
        }
        var from = (config.current && config.current.template) || 'unknown';
        var to = resolveTemplate(dest);
        applyTypes(event.viewTransition, from, to, matchRoute(from, to));
    }

    function onReveal(event) {
        if (event.viewTransition) {
            var fromUrl = event.activation && event.activation.from ? event.activation.from.url : '';
            var from = fromUrl ? resolveTemplate(fromUrl) : 'unknown';
            var to = (config.current && config.current.template) || 'unknown';
            if (fromUrl && isExcluded(window.location.href)) {
                event.viewTransition.skipTransition();
            } else {
                applyTypes(event.viewTransition, from, to, matchRoute(from, to));
            }
        }
        announcePage();
        playEnter();
    }

    function announcePage() {
        var live = document.getElementById('wpmotion-sr-status');
        if (!live) {
            live = document.createElement('div');
            live.id = 'wpmotion-sr-status';
            live.className = 'wpmotion-sr-status';
            live.setAttribute('role', 'status');
            live.setAttribute('aria-live', 'polite');
            document.body.appendChild(live);
        }
        var title = document.title || '';
        var template = (config.i18n && config.i18n.pageReady) || ('Page chargée : ' + title);
        live.textContent = template;
    }

    function leaveTargets() {
        var nodes = document.querySelectorAll('main, .wp-site-blocks, #content, .site-content');
        var extra = document.querySelectorAll('[data-wpmotion-shared], main .wp-block-post, main h1, main .wp-block-heading');
        var list = [];
        extra.forEach(function (node) { list.push(node); });
        if (!list.length && nodes.length) {
            list.push(nodes[0]);
        }
        return list;
    }

    function playLeave() {
        var api = motionApi();
        if (!api || prefersReduced() || (config.preset === 'none')) {
            return Promise.resolve();
        }
        var targets = leaveTargets();
        if (!targets.length) {
            return Promise.resolve();
        }
        var anim = api.animate(
            targets,
            { opacity: 0, y: -12 },
            { duration: durationSec() * 0.55, easing: config.easing || [0.22, 1, 0.36, 1], delay: api.stagger(0.025) }
        );
        return anim && anim.finished ? anim.finished.catch(function () {}) : Promise.resolve();
    }

    function playEnter() {
        if (entered) {
            return;
        }
        entered = true;
        var api = motionApi();
        if (!api || prefersReduced()) {
            return;
        }
        var main = document.querySelector('main') || document.querySelector('.wp-site-blocks') || document.querySelector('#content');
        if (main) {
            api.animate(main, { opacity: [0, 1], y: [20, 0] }, {
                duration: durationSec(),
                easing: [0.22, 1, 0.36, 1],
            });
        }
        var cards = document.querySelectorAll('main .wp-block-post, main ul.products > li, main .wp-block-column');
        if (cards.length) {
            api.animate(cards, { opacity: [0, 1], y: [16, 0] }, {
                duration: durationSec() * 0.85,
                delay: api.stagger(0.045),
                easing: [0.22, 1, 0.36, 1],
            });
        }
    }

    function shouldIntercept(anchor, event) {
        if (!anchor || leaving) {
            return false;
        }
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return false;
        }
        if (anchor.target && anchor.target !== '' && anchor.target !== '_self') {
            return false;
        }
        if (anchor.hasAttribute('download')) {
            return false;
        }
        var href = anchor.href;
        if (!href || !sameOrigin(href) || isExcluded(href)) {
            return false;
        }
        var dest = new URL(href, window.location.origin);
        if (dest.pathname === window.location.pathname && dest.search === window.location.search) {
            return false;
        }
        var to = resolveTemplate(href);
        var from = (config.current && config.current.template) || 'unknown';
        var route = matchRoute(from, to);
        if (route.preset === 'none') {
            return false;
        }
        return true;
    }

    function onClick(event) {
        var anchor = event.target && event.target.closest ? event.target.closest('a[href]') : null;
        if (!shouldIntercept(anchor, event)) {
            return;
        }
        event.preventDefault();
        leaving = true;
        var href = anchor.href;
        var timeout = window.setTimeout(function () {
            window.location.href = href;
        }, 900);
        playLeave().then(function () {
            window.clearTimeout(timeout);
            window.location.href = href;
        });
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function splitWords(el) {
        if (el.getAttribute('data-wpmotion-split') === '1') {
            return el.querySelectorAll('.wpmotion-word');
        }
        if (el.querySelector('img, svg, input, button, a')) {
            return [];
        }
        var text = (el.textContent || '').trim();
        if (!text) {
            return [];
        }
        el.setAttribute('aria-label', text);
        var parts = text.split(/(\s+)/);
        el.innerHTML = parts.map(function (part) {
            if (part === '' || /^\s+$/.test(part)) {
                return part;
            }
            return '<span class="wpmotion-word" aria-hidden="true">' + escapeHtml(part) + '</span>';
        }).join('');
        el.setAttribute('data-wpmotion-split', '1');
        return el.querySelectorAll('.wpmotion-word');
    }

    function initScenes() {
        var api = motionApi();
        var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-wpmotion-scene]'));
        if (!nodes.length) {
            return;
        }

        nodes.forEach(function (node) {
            var scene = node.getAttribute('data-wpmotion-scene');
            if (scene === 'pin') {
                node.classList.add('is-visible');
                return;
            }
            if (prefersReduced()) {
                node.classList.add('is-visible');
                return;
            }
            if (!api) {
                node.classList.add('is-visible');
                return;
            }

            if (scene === 'parallax' && api.scroll) {
                api.scroll(api.animate(node, { y: [0, -40] }, { easing: 'linear' }), { target: node });
                node.classList.add('is-visible');
                return;
            }

            var run = function () {
                if (scene === 'split-text') {
                    var words = splitWords(node);
                    if (words.length && api.stagger) {
                        api.animate(words, { opacity: [0, 1], y: [12, 0] }, {
                            duration: durationSec() * 0.7,
                            delay: api.stagger(0.035),
                            easing: [0.22, 1, 0.36, 1],
                        });
                    }
                    node.classList.add('is-visible');
                    return;
                }
                if (scene === 'stagger-children') {
                    var children = node.children;
                    if (children.length) {
                        api.animate(children, { opacity: [0, 1], y: [16, 0] }, {
                            duration: durationSec() * 0.8,
                            delay: api.stagger(0.05),
                            easing: [0.22, 1, 0.36, 1],
                        });
                    }
                    node.classList.add('is-visible');
                    return;
                }
                api.animate(node, {
                    opacity: [0, 1],
                    y: scene === 'slide-in' ? [24, 0] : [0, 0],
                }, {
                    duration: durationSec(),
                    easing: [0.22, 1, 0.36, 1],
                });
                node.classList.add('is-visible');
            };

            if (api.inView) {
                api.inView(node, function () {
                    run();
                    return false;
                }, { amount: 0.2 });
            } else {
                run();
            }
        });
    }

    window.addEventListener('pageswap', onSwap);
    window.addEventListener('pagereveal', onReveal);
    document.addEventListener('click', onClick, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.navigation) {
                playEnter();
            }
            initScenes();
        });
    } else {
        if (!window.navigation) {
            playEnter();
        }
        initScenes();
    }
})();
