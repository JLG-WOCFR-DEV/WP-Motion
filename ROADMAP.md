# Roadmap — WP-GSAP

**Statut 1.0.0 :** phases 0 à 5 implémentées (socle, View Transitions, éléments partagés, graphe de routes, scènes Gutenberg, Woo / tokens / import-export). Le front reste inactif tant que « Activer les transitions » n’est pas coché.

Plugin WordPress **gratuit, GPL, indépendant**. Objectif : des transitions de pages modernes (type Webflow) sur un vrai site WordPress, sans le transformer en SPA.

Réinventer la roue est assumé. La différenciation n’est pas « encore un GSAP », c’est **la chorégraphie entre templates WordPress** (archive → article, fiche produit, header qui reste, image qui morph).

Le plugin reste un dépôt séparé. Pas de fusion avec Supersede, Visi-Bloc, etc.

---

## Principes non négociables

1. **View Transitions d’abord.** Le navigateur anime le changement d’URL. Pas de Barba / Swup / Highway en défaut.
2. **GSAP en option, jamais le produit.** Chargé seulement si une page utilise Flip, ScrollTrigger, SplitText, etc.
3. **Presets + paramètres, pas de timeline canvas.** Même en open source, la licence GSAP (Webflow) interdit un builder visuel qui concurrence Interactions. On écrit à GSAP avant toute UI type After Effects.
4. **WordPress reste WordPress.** URLs réelles, cache, formulaires, WooCommerce, nonces, `target="_blank"`, POST.
5. **Accessibilité par défaut.** `prefers-reduced-motion` = cut ou fondu court. Annonce du titre de page. Focus géré.
6. **Pas de GitHub Actions.** Tests et lint en local (`php -l`, PHPUnit Docker), comme les autres plugins du portfolio.
7. **GSAP n’est pas GPL.** On ne le bundle pas dans le zip wordpress.org. CDN officiel, ou « fournir le fichier », avec notice de licence. Le code *du plugin* est GPL-2.0-or-later.

---

## Hors scope (volontaire)

- Clone de Webflow Interactions (timeline, keyframes libres, 15 triggers).
- Router JS qui hijack tous les clics.
- Smooth scroll comme feature principale.
- Three.js / WebGL.
- Add-on Elementor / Divi only. Cible = thèmes classiques **et** block themes (FSE).
- Fusion avec Supersede CSS. Un futur *hook* de tokens motion est possible, pas un package commun.

---

## Phase 0 — Socle (semaine 1)

Repo vide aujourd’hui (`f0aa584`). Avant toute animation :

| Livrable | Détail |
|---|---|
| Bootstrap plugin | `wp-gsap.php`, autoload, `uninstall.php`, text domain `wp-gsap` |
| Admin wp-admin | Une page Réglages, look natif (`wrap`, `form-table`, `nav-tab-wrapper`) |
| Feature flag | Front inactif tant que l’utilisateur n’a pas coché « Activer les transitions » |
| Exclusions hardcodées | `wp-admin`, `wp-login.php`, `wp-cron.php`, REST, feeds, previews |
| A11y | Respect `prefers-reduced-motion` dès le premier CSS |
| Licence | `LICENSE` GPL-2.0, `readme.txt`, notice GSAP dans l’admin si/quand CDN |

**Sortie :** plugin installable qui ne casse rien, zéro animation.

---

## Phase 1 — Transitions MPA (V1 publique)

Le wow minimum, sans GSAP.

| Livrable | Détail |
|---|---|
| Opt-in CSS | `@view-transition { navigation: auto; }` injecté sur le front seulement |
| 4 presets globaux | `fade`, `slide` (haut/bas ou gauche/droite), `wipe`, `none` |
| Durée + easing | 2–3 réglages, pas 40 |
| Reduced motion | Force `none` ou fade 80 ms |
| Exclusions utilisateur | Chemins / slugs : panier, checkout, compte, `/wp-json/` |
| Pas d’animation si | POST, download, `_blank`, modificateur clavier, same-URL hash only |
| Fallback | Navigateur sans View Transitions = navigation normale, pas de JS polyfill lourd |

**Critère de succès :** sur un thème block (Twenty Twenty-Five) et un thème classique, Accueil → Article = fondu/slide propre. Checkout = cut net.

**Pas dans la V1 :** GSAP, WooCommerce, éditeur Gutenberg, graphe de routes.

---

## Phase 2 — Éléments partagés (le vrai effet Webflow)

C’est la feature qui justifie le plugin.

| Livrable | Détail |
|---|---|
| Noms stables | `view-transition-name` dérivés d’IDs WP : `wpgsap-post-{ID}-image`, `wpgsap-post-{ID}-title`, `wpgsap-site-logo`, `wpgsap-site-header` |
| Auto | Image mise en avant + titre sur les boucles et le template Single |
| Gutenberg | Attribut sur Image, Cover, Titre : « Participe à la transition de page » |
| Template part header | Option « le header reste » (FSE) |
| Preview admin | Bouton « Simuler Accueil → cet article » (deux URLs, iframe ou onglet) |

**Critère de succès :** cliquer une carte d’archive, l’image **devient** le hero de l’article. Retour navigateur : l’inverse.

Si View Transitions ne morph pas assez bien (ratio différent, object-fit) : **GSAP Flip en fallback ciblé**, chargé uniquement sur les pages qui ont un shared element. CDN + notice licence.

---

## Phase 3 — Graphe de routes

Un effet global ne suffit pas. On chorégraphie **par couple de templates**.

| Couple | Comportement par défaut |
|---|---|
| `home` / `archive` → `single` | Shared image + fade contenu |
| `single` → `single` (prev/next) | Slide horizontal |
| `page` → `page` | Fade court |
| `shop` → `product` | Shared image produit (si Woo actif) |
| `*` → checkout / cart / account / login | `none` |

Admin : table simple (origine, destination, preset, shared on/off). Pas un canvas.

**Critère de succès :** le blog a une transition, la boutique une autre, le tunnel d’achat zéro animation. Ajouter une règle ne casse pas les autres.

---

## Phase 4 — Scènes dans la page (GSAP optionnel)

Après que les transitions tiennent la route.

Presets sur bloc **Group** (et éventuellement Titre) :

- Apparition au scroll (fade / slide / stagger enfants)
- SplitText sur un titre
- Section pinnée (une « slide » de storytelling)
- Parallax léger, `transform` only

Règles :

- GSAP + plugins chargés **uniquement** si au moins un preset GSAP est présent dans le HTML publié
- `prefers-reduced-motion` désactive pin/parallax, garde un fade
- Pas de smooth-scroll forcé

**Critère de succès :** page d’accueil animée au scroll ; page Légal = pas un octet de GSAP.

---

## Phase 5 — Durcissement écosystème

| Sujet | Travail |
|---|---|
| WooCommerce | `shop`/`product`/`cart`/`checkout` testés ; images produit en shared |
| Cache | Compatible LiteSpeed / WP Rocket (CSS + attributs, pas de routeur) |
| Multisite | Réglages par site |
| i18n | FR + pot |
| Tokens motion | Durée / easing / distance filtrables (`wpgsap_motion_tokens`). Plus tard, un thème ou Supersede peut les override — sans dépendance dure |
| Export / import | JSON des règles de routes (site staging → prod) |

---

## Découpage technique (tous les phases)

```
wp-gsap/
  wp-gsap.php
  includes/
    class-plugin.php
    class-settings.php
    class-exclusions.php
    class-view-transitions.php   # CSS + meta names
    class-shared-elements.php
    class-routes.php
    class-assets.php             # enqueue conditionnel GSAP
  src/blocks/                    # attributs Image/Cover/Group
  assets/
    css/front-transitions.css
    js/front-reduced-motion.js   # filet, pas un router
    js/admin-preview.js
  tests/phpunit/
  uninstall.php
  readme.txt
```

Front JS volontairement minuscule. La magie est dans le CSS View Transitions + les noms stables. GSAP n’entre que par `class-assets.php` si le markup le demande.

Tests locaux, pas de CI GitHub :

- PHPUnit : exclusions, génération des `view-transition-name`, graphe de routes
- `php -l` Docker
- Vérif manuelle : thème block + thème classique + Woo si dispo

---

## Versions cibles

| Version | Phase | Ce qu’on peut montrer |
|---|---|---|
| **0.1.0** | 0 | Plugin inerte, réglages |
| **0.2.0** | 1 | Fade/slide entre pages |
| **0.5.0** | 2 | Morph image archive → single |
| **0.8.0** | 3 | Règles par templates |
| **1.0.0** | 4 | V1 « complète » : transitions + scènes scroll optionnelles |
| **1.1.0** | 5 | Woo, cache, tokens, i18n |

La **1.0** n’attend pas Woo ni un builder visuel. Elle attend que archive → article soit beau, accessible, et inoffensif sur le checkout.

---

## Décision licence GSAP (à faire en Phase 2, avant Flip)

1. Rédiger 10 lignes : presets Gutenberg, pas de timeline, OSS GPL, GSAP via CDN.
2. Envoyer via [gsap.com/contact](https://gsap.com/contact) (Licensing Question).
3. Conserver la réponse dans `docs/gsap-license.md`.

Si Webflow dit non au Flip : on reste 100 % View Transitions pour le morph, GSAP seulement pour SplitText/ScrollTrigger… ou on bascule ces scènes sur **Motion** (MIT) pour wordpress.org.

---

## Ordre d’exécution immédiat

1. Phase 0 (socle)
2. Phase 1 (4 presets View Transitions)
3. Phase 2 (shared featured image) ← première démo « wow »
4. Stop, usage réel, puis Phase 3–4

On ne commence pas par GSAP, ni par un éditeur visuel.
