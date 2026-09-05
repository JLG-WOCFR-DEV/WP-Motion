=== WP-GSAP ===
Contributors: jlg
Tags: animation, view transitions, gsap, page transitions, motion
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transitions de pages natives (View Transitions) et scènes de mouvement optionnelles. GSAP n’est jamais bundlé.

== Description ==

WP-GSAP chorégraphie la navigation WordPress sans transformer le site en SPA.

* Presets fade / slide / wipe entre documents (View Transitions API)
* Image mise en avant et titre partagés (archive → article, boutique → produit)
* Graphe de routes par couple de templates (checkout = aucune animation)
* Scènes in-page sur les blocs Groupe et Titre
* GSAP chargé depuis un CDN uniquement si SplitText, pin ou parallax est utilisé
* `prefers-reduced-motion` respecté
* Import / export JSON des réglages

GSAP reste sous licence Standard Webflow (pas GPL). Le code de ce plugin est GPL-2.0-or-later.

== Installation ==

1. Copier le dossier du plugin dans `wp-content/plugins/`.
2. Activer WP-GSAP.
3. Réglages → WP-GSAP : cocher « Activer les transitions ».

== Changelog ==

= 1.0.0 =
* Première version : View Transitions, éléments partagés, graphe de routes, scènes Gutenberg, WooCommerce, import/export.
