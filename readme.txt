=== WP Motion ===
Contributors: jlg
Tags: animation, view transitions, motion, page transitions
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transitions de pages natives (View Transitions) chorégraphiées avec Motion (MIT). Open source, sans GSAP.

== Description ==

WP Motion chorégraphie la navigation WordPress sans transformer le site en SPA.

* Presets fade / slide / wipe entre documents (View Transitions API)
* Leave / enter Motion : stagger des cartes, fondu du contenu
* Image mise en avant et titre partagés (archive → article, boutique → produit)
* Graphe de routes par couple de templates (checkout = aucune animation)
* Scènes in-page sur les blocs Groupe et Titre (Motion + splitter GPL)
* `prefers-reduced-motion` respecté
* Import / export JSON des réglages

Motion est bundlé sous licence MIT (`assets/vendor/`). Le plugin est GPL-2.0-or-later.

== Installation ==

1. Copier le dossier dans `wp-content/plugins/`.
2. Activer WP Motion.
3. Cocher « Activer les transitions ».

== Changelog ==

= 1.1.1 =
* FIX: ne plus faire disparaître l’image partagée avant la navigation (ça cassait le morph View Transitions).
* IMPROVEMENT: leave/enter Motion uniquement sur le contenu non partagé ; relais Motion si le navigateur n’a pas de transitions multi-pages.
* FIX: plus d’animation d’entrée sur le premier chargement de page.

= 1.1.0 =
* Renommage WP-GSAP → WP Motion.
* Motion 13.2.0 MIT bundlé. GSAP retiré.
* Chorégraphie leave/enter (interception de clic, navigation réelle ensuite).

= 1.0.0 =
* Première version View Transitions / routes / scènes.
