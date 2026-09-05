# WP-GSAP

Plugin WordPress **gratuit, GPL**, indépendant. Transitions de pages type Webflow sur un vrai site multi-pages : **View Transitions** en premier, **GSAP** seulement si une scène l’exige.

Voir [ROADMAP.md](ROADMAP.md) pour le produit. Voir [docs/gsap-license.md](docs/gsap-license.md) pour la licence GSAP.

## Ce que ça fait

- Fondu / glissement / balayage entre pages (pas de Barba, pas de router)
- L’image mise en avant d’une carte peut **morpher** vers le hero de l’article
- Règles par templates (`archive → single` ≠ checkout)
- Blocs Groupe / Titre : fade, slide, stagger (CSS) ; SplitText, pin, parallax (GSAP CDN)
- WooCommerce : image produit partagée ; panier / commande exclus par défaut

## Tests locaux

```bash
composer install
composer test
```

Ou depuis le dossier portfolio : `./run-tests.sh WP-GSAP`

Pas de GitHub Actions.
