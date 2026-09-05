(function () {
    'use strict';

    var config = window.WPGSAP || {};
    var hardcoded = ['/wp-admin', '/wp-login.php', '/wp-cron.php', '/wp-json/', '/xmlrpc.php', '/feed'];

    function prefersReduced() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
        if (pattern !== '/' && (path === pattern || path.indexOf(pattern + '/') === 0)) {
            return true;
        }
        return false;
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
        if (rule === 'singular' && ['single', 'page', 'product', 'singular'].indexOf(actual) !== -1) {
            return true;
        }
        return false;
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
        if (!event.viewTransition) {
            return;
        }
        var fromUrl = event.activation && event.activation.from ? event.activation.from.url : '';
        var from = fromUrl ? resolveTemplate(fromUrl) : 'unknown';
        var to = (config.current && config.current.template) || 'unknown';
        if (fromUrl && isExcluded(window.location.href)) {
            event.viewTransition.skipTransition();
            return;
        }
        applyTypes(event.viewTransition, from, to, matchRoute(from, to));
        announcePage();
    }

    function announcePage() {
        var live = document.getElementById('wpgsap-sr-status');
        if (!live) {
            live = document.createElement('div');
            live.id = 'wpgsap-sr-status';
            live.className = 'wpgsap-sr-status';
            live.setAttribute('role', 'status');
            live.setAttribute('aria-live', 'polite');
            document.body.appendChild(live);
        }
        var title = document.title || '';
        var template = (config.i18n && config.i18n.pageReady) || ('Page chargée : ' + title);
        live.textContent = template;
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[src="' + src + '"]');
            if (existing) {
                existing.addEventListener('load', function () { resolve(); });
                if (existing.getAttribute('data-loaded') === '1') {
                    resolve();
                }
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = function () {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function loadGsap() {
        if (window.gsap) {
            return Promise.resolve(window.gsap);
        }
        if ((config.gsapSource || 'cdn') === 'none' || !config.gsap) {
            return Promise.reject(new Error('gsap-disabled'));
        }
        return loadScript(config.gsap.core)
            .then(function () { return loadScript(config.gsap.scrollTrigger); })
            .then(function () { return loadScript(config.gsap.splitText); })
            .then(function () {
                if (window.gsap && window.ScrollTrigger) {
                    window.gsap.registerPlugin(window.ScrollTrigger);
                }
                if (window.gsap && window.SplitText) {
                    window.gsap.registerPlugin(window.SplitText);
                }
                return window.gsap;
            });
    }

    function initCssScenes(nodes) {
        if (!('IntersectionObserver' in window)) {
            nodes.forEach(function (node) {
                node.classList.add('is-visible');
            });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        nodes.forEach(function (node) {
            observer.observe(node);
        });
    }

    function initGsapScenes(nodes) {
        loadGsap().then(function (gsap) {
            var reduce = prefersReduced();
            nodes.forEach(function (node) {
                var scene = node.getAttribute('data-wpgsap-scene');
                if (scene === 'split-text' && window.SplitText && !reduce) {
                    var split = new window.SplitText(node, { type: 'chars,words' });
                    gsap.from(split.chars, {
                        yPercent: 80,
                        opacity: 0,
                        stagger: 0.02,
                        duration: (config.durationMs || 400) / 1000,
                        ease: 'power3.out',
                        scrollTrigger: { trigger: node, start: 'top 85%' },
                    });
                    return;
                }
                if (scene === 'pin' && window.ScrollTrigger && !reduce) {
                    window.ScrollTrigger.create({
                        trigger: node,
                        start: 'top top',
                        end: '+=100%',
                        pin: true,
                        pinSpacing: true,
                    });
                    return;
                }
                if (scene === 'parallax' && !reduce) {
                    gsap.to(node, {
                        yPercent: -12,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: node,
                            start: 'top bottom',
                            end: 'bottom top',
                            scrub: true,
                        },
                    });
                }
            });
        }).catch(function () {
            initCssScenes(nodes);
        });
    }

    function initScenes() {
        var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-wpgsap-scene]'));
        if (!nodes.length) {
            return;
        }
        var cssScenes = [];
        var gsapScenes = [];
        nodes.forEach(function (node) {
            var scene = node.getAttribute('data-wpgsap-scene');
            if (['split-text', 'pin', 'parallax'].indexOf(scene) !== -1) {
                gsapScenes.push(node);
            } else {
                cssScenes.push(node);
            }
        });
        if (cssScenes.length) {
            initCssScenes(cssScenes);
        }
        if (gsapScenes.length) {
            if (prefersReduced()) {
                initCssScenes(gsapScenes);
            } else {
                initGsapScenes(gsapScenes);
            }
        }
    }

    window.addEventListener('pageswap', onSwap);
    window.addEventListener('pagereveal', onReveal);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScenes);
    } else {
        initScenes();
    }
})();
