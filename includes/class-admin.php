<?php

declare(strict_types=1);

final class WpGsap_Admin
{
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_notices', [$this, 'notices']);
    }

    public function menu(): void
    {
        add_menu_page(
            __('WP-GSAP', 'wp-gsap'),
            __('WP-GSAP', 'wp-gsap'),
            'manage_options',
            'wp-gsap',
            [$this, 'render'],
            'dashicons-leftright',
            58
        );
    }

    public function register(): void
    {
        register_setting('wp_gsap', WpGsap_Settings::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [WpGsap_Settings::class, 'sanitize'],
            'default' => WpGsap_Settings::defaults(),
        ]);
    }

    public function assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_wp-gsap') {
            return;
        }

        wp_enqueue_style('wpgsap-admin', WPGSAP_URL . 'assets/css/admin.css', [], WPGSAP_VERSION);
        wp_enqueue_script('wpgsap-admin', WPGSAP_URL . 'assets/js/admin.js', [], WPGSAP_VERSION, true);
        wp_localize_script('wpgsap-admin', 'WPGSAP_ADMIN', [
            'templates' => WpGsap_Routes::TEMPLATES,
            'presets' => WpGsap_Settings::PRESETS,
            'i18n' => [
                'remove' => __('Supprimer', 'wp-gsap'),
                'from' => __('Origine', 'wp-gsap'),
                'to' => __('Destination', 'wp-gsap'),
                'preset' => __('Preset', 'wp-gsap'),
                'shared' => __('Éléments partagés', 'wp-gsap'),
            ],
        ]);
    }

    public function notices(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page !== 'wp-gsap') {
            return;
        }
        $flag = isset($_GET['wpgsap']) ? sanitize_key((string) $_GET['wpgsap']) : '';
        if ($flag === 'imported') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Réglages importés.', 'wp-gsap') . '</p></div>';
        }
        if ($flag === 'import-invalid' || $flag === 'import-missing') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Import impossible : fichier JSON invalide ou manquant.', 'wp-gsap') . '</p></div>';
        }
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'general';
        $allowed = ['general', 'routes', 'preview', 'tools'];
        if (!in_array($tab, $allowed, true)) {
            $tab = 'general';
        }

        $settings = WpGsap_Settings::get();
        $base = admin_url('admin.php?page=wp-gsap');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('WP-GSAP', 'wp-gsap') . '</h1>';
        echo '<p class="description">' . esc_html__('Transitions de pages natives (View Transitions). GSAP n’est chargé que si une scène in-page l’exige.', 'wp-gsap') . '</p>';

        echo '<nav class="nav-tab-wrapper wp-clearfix">';
        $this->tab_link($base, 'general', $tab, __('Général', 'wp-gsap'));
        $this->tab_link($base, 'routes', $tab, __('Routes', 'wp-gsap'));
        $this->tab_link($base, 'preview', $tab, __('Aperçu', 'wp-gsap'));
        $this->tab_link($base, 'tools', $tab, __('Outils', 'wp-gsap'));
        echo '</nav>';

        if ($tab === 'tools') {
            $this->render_tools();
            echo '</div>';
            return;
        }

        if ($tab === 'preview') {
            $this->render_preview();
            echo '</div>';
            return;
        }

        echo '<form action="' . esc_url(admin_url('options.php')) . '" method="post">';
        settings_fields('wp_gsap');

        if ($tab === 'routes') {
            $this->render_routes($settings);
        } else {
            $this->render_general($settings);
        }

        submit_button(__('Enregistrer les modifications', 'wp-gsap'));
        echo '</form></div>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function render_general(array $settings): void
    {
        echo '<table class="form-table" role="presentation">';

        $this->row(
            __('Activer les transitions', 'wp-gsap'),
            $this->checkbox('enabled', !empty($settings['enabled']), __('Injecter View Transitions sur le front. Inactif tant que cette case n’est pas cochée.', 'wp-gsap'))
        );

        $this->row(
            __('Preset par défaut', 'wp-gsap'),
            $this->select('preset', (string) $settings['preset'], [
                'fade' => __('Fondu', 'wp-gsap'),
                'slide' => __('Glissement', 'wp-gsap'),
                'wipe' => __('Balayage', 'wp-gsap'),
                'none' => __('Aucune', 'wp-gsap'),
            ])
        );

        $this->row(
            __('Durée (ms)', 'wp-gsap'),
            '<input name="' . esc_attr(WpGsap_Settings::OPTION) . '[duration_ms]" type="number" class="small-text" min="80" max="1200" step="10" value="' . esc_attr((string) $settings['duration_ms']) . '">'
        );

        $easing_labels = [
            'ease' => 'ease',
            'ease-out' => 'ease-out',
            'ease-in-out' => 'ease-in-out',
            'linear' => 'linear',
            'snappy' => __('Snappy', 'wp-gsap'),
            'cinematic' => __('Cinématique', 'wp-gsap'),
        ];
        $this->row(__('Easing', 'wp-gsap'), $this->select('easing', (string) $settings['easing'], $easing_labels));

        $this->row(
            __('Mouvement réduit', 'wp-gsap'),
            $this->select('reduced_motion', (string) $settings['reduced_motion'], [
                'fade' => __('Fondu court (80 ms)', 'wp-gsap'),
                'none' => __('Aucune animation', 'wp-gsap'),
            ])
        );

        $this->row(
            __('Chemins exclus', 'wp-gsap'),
            '<textarea name="' . esc_attr(WpGsap_Settings::OPTION) . '[exclude_paths]" class="large-text code" rows="6">' . esc_textarea(implode("\n", $settings['exclude_paths'])) . '</textarea>'
            . '<p class="description">' . esc_html__('Un chemin par ligne. wp-admin, wp-login, REST et feeds sont toujours exclus.', 'wp-gsap') . '</p>'
        );

        $this->row(
            __('Header persistant', 'wp-gsap'),
            $this->checkbox('header_persistent', !empty($settings['header_persistent']), __('Le header (logo / barre) reste en place pendant la transition.', 'wp-gsap'))
            . '<p><input name="' . esc_attr(WpGsap_Settings::OPTION) . '[header_selector]" type="text" class="regular-text code" value="' . esc_attr((string) $settings['header_selector']) . '"></p>'
            . '<p class="description">' . esc_html__('Sélecteur CSS du header (thèmes classiques). Les template parts « header » FSE sont gérés automatiquement.', 'wp-gsap') . '</p>'
        );

        $this->row(
            __('Image mise en avant partagée', 'wp-gsap'),
            $this->checkbox('shared_featured_image', !empty($settings['shared_featured_image']), __('L’image d’une carte d’archive morph vers le hero de l’article (et l’image produit WooCommerce).', 'wp-gsap'))
        );

        $this->row(
            __('Titre partagé', 'wp-gsap'),
            $this->checkbox('shared_title', !empty($settings['shared_title']), __('Le titre du bloc « Titre de la publication » continue d’une vue à l’autre.', 'wp-gsap'))
        );

        $this->row(
            __('Source GSAP', 'wp-gsap'),
            $this->select('gsap_source', (string) $settings['gsap_source'], [
                'cdn' => __('CDN jsDelivr (uniquement si une scène l’exige)', 'wp-gsap'),
                'none' => __('Ne jamais charger GSAP (scènes CSS seulement)', 'wp-gsap'),
            ])
        );

        echo '</table>';

        echo '<p class="description">' . esc_html__('Les scènes in-page (fade, slide, stagger, SplitText, pin, parallax) se règlent sur les blocs Groupe et Titre dans l’éditeur.', 'wp-gsap') . '</p>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function render_routes(array $settings): void
    {
        echo '<p>' . esc_html__('Première règle la plus spécifique gagne. * = n’importe quel template. Le checkout / panier / compte sont en « none » par défaut.', 'wp-gsap') . '</p>';
        echo '<table class="widefat striped" id="wpgsap-routes">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Origine', 'wp-gsap') . '</th>';
        echo '<th>' . esc_html__('Destination', 'wp-gsap') . '</th>';
        echo '<th>' . esc_html__('Preset', 'wp-gsap') . '</th>';
        echo '<th>' . esc_html__('Partagés', 'wp-gsap') . '</th>';
        echo '<th></th>';
        echo '</tr></thead><tbody>';

        $routes = is_array($settings['routes'] ?? null) ? $settings['routes'] : [];
        foreach ($routes as $index => $route) {
            $this->route_row((int) $index, $route);
        }

        echo '</tbody></table>';
        echo '<p><button type="button" class="button" id="wpgsap-add-route">' . esc_html__('Ajouter une règle', 'wp-gsap') . '</button></p>';

        foreach (['enabled', 'preset', 'duration_ms', 'easing', 'reduced_motion', 'header_persistent', 'header_selector', 'shared_featured_image', 'shared_title', 'gsap_source'] as $keep) {
            $value = $settings[$keep] ?? '';
            if (is_bool($value)) {
                echo '<input type="hidden" name="' . esc_attr(WpGsap_Settings::OPTION . '[' . $keep . ']') . '" value="' . ($value ? '1' : '0') . '">';
            } else {
                echo '<input type="hidden" name="' . esc_attr(WpGsap_Settings::OPTION . '[' . $keep . ']') . '" value="' . esc_attr((string) $value) . '">';
            }
        }
        echo '<input type="hidden" name="' . esc_attr(WpGsap_Settings::OPTION) . '[exclude_paths]" value="' . esc_attr(implode("\n", $settings['exclude_paths'])) . '">';
    }

    /**
     * @param array<string, mixed> $route
     */
    private function route_row(int $index, array $route): void
    {
        $option = WpGsap_Settings::OPTION;
        $from = (string) ($route['from'] ?? '*');
        $to = (string) ($route['to'] ?? '*');
        $preset = (string) ($route['preset'] ?? 'fade');
        $shared = !empty($route['shared']);

        echo '<tr class="wpgsap-route">';
        echo '<td>' . $this->select_named($option . '[routes][' . $index . '][from]', $from, $this->template_labels()) . '</td>';
        echo '<td>' . $this->select_named($option . '[routes][' . $index . '][to]', $to, $this->template_labels()) . '</td>';
        echo '<td>' . $this->select_named($option . '[routes][' . $index . '][preset]', $preset, $this->preset_labels()) . '</td>';
        echo '<td><label><input type="checkbox" name="' . esc_attr($option . '[routes][' . $index . '][shared]') . '" value="1"' . checked($shared, true, false) . '> ' . esc_html__('Oui', 'wp-gsap') . '</label></td>';
        echo '<td><button type="button" class="button-link-delete wpgsap-remove-route">' . esc_html__('Supprimer', 'wp-gsap') . '</button></td>';
        echo '</tr>';
    }

    private function render_preview(): void
    {
        $home = home_url('/');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="wpgsap-from">' . esc_html__('Page d’origine', 'wp-gsap') . '</label></th>';
        echo '<td><input id="wpgsap-from" class="regular-text code" type="url" value="' . esc_attr($home) . '"></td></tr>';
        echo '<tr><th scope="row"><label for="wpgsap-to">' . esc_html__('Page de destination', 'wp-gsap') . '</label></th>';
        echo '<td><input id="wpgsap-to" class="regular-text code" type="url" value="" placeholder="https://"></td></tr>';
        echo '</table>';
        echo '<p><button type="button" class="button button-primary" id="wpgsap-open-from">' . esc_html__('Ouvrir la page d’origine', 'wp-gsap') . '</button></p>';
        echo '<p class="description">' . esc_html__('Les View Transitions se jouent dans le navigateur, entre deux documents. Ouvrez l’origine, puis cliquez un lien vers la destination (image mise en avant, carte d’archive, produit).', 'wp-gsap') . '</p>';
        echo '<p class="description">' . esc_html__('Activez d’abord les transitions dans l’onglet Général. Le checkout, le panier et wp-admin ne s’animent jamais.', 'wp-gsap') . '</p>';
    }

    private function render_tools(): void
    {
        echo '<h2>' . esc_html__('Exporter', 'wp-gsap') . '</h2>';
        echo '<p>' . esc_html__('Télécharge les règles et réglages en JSON (staging → production).', 'wp-gsap') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="wp_gsap_export">';
        wp_nonce_field('wp_gsap_export');
        submit_button(__('Télécharger JSON', 'wp-gsap'), 'secondary', 'submit', false);
        echo '</form>';

        echo '<h2>' . esc_html__('Importer', 'wp-gsap') . '</h2>';
        echo '<p>' . esc_html__('Remplace les réglages actuels par un fichier exporté.', 'wp-gsap') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action" value="wp_gsap_import">';
        wp_nonce_field('wp_gsap_import');
        echo '<input type="file" name="wp_gsap_import" accept="application/json,.json" required>';
        submit_button(__('Importer', 'wp-gsap'), 'primary', 'submit', false);
        echo '</form>';
    }

    private function tab_link(string $base, string $tab, string $current, string $label): void
    {
        $class = 'nav-tab' . ($tab === $current ? ' nav-tab-active' : '');
        echo '<a class="' . esc_attr($class) . '" href="' . esc_url(add_query_arg('tab', $tab, $base)) . '">' . esc_html($label) . '</a>';
    }

    private function row(string $label, string $field): void
    {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . $field . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function checkbox(string $key, bool $checked, string $label): string
    {
        $name = WpGsap_Settings::OPTION . '[' . $key . ']';
        return '<label><input type="hidden" name="' . esc_attr($name) . '" value="0"><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($checked, true, false) . '> ' . esc_html($label) . '</label>';
    }

    /**
     * @param array<string, string> $choices
     */
    private function select(string $key, string $value, array $choices): string
    {
        return $this->select_named(WpGsap_Settings::OPTION . '[' . $key . ']', $value, $choices);
    }

    /**
     * @param array<string, string> $choices
     */
    private function select_named(string $name, string $value, array $choices): string
    {
        $html = '<select name="' . esc_attr($name) . '">';
        foreach ($choices as $val => $label) {
            $html .= '<option value="' . esc_attr((string) $val) . '"' . selected($value, (string) $val, false) . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * @return array<string, string>
     */
    private function template_labels(): array
    {
        return [
            '*' => '*',
            'home' => __('Accueil', 'wp-gsap'),
            'archive' => __('Archive / blog', 'wp-gsap'),
            'single' => __('Article', 'wp-gsap'),
            'page' => __('Page', 'wp-gsap'),
            'singular' => __('Tout contenu unique', 'wp-gsap'),
            'search' => __('Recherche', 'wp-gsap'),
            '404' => '404',
            'shop' => __('Boutique', 'wp-gsap'),
            'product' => __('Produit', 'wp-gsap'),
            'cart' => __('Panier', 'wp-gsap'),
            'checkout' => __('Commande', 'wp-gsap'),
            'account' => __('Compte', 'wp-gsap'),
            'unknown' => __('Autre', 'wp-gsap'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function preset_labels(): array
    {
        return [
            'fade' => __('Fondu', 'wp-gsap'),
            'slide' => __('Glissement', 'wp-gsap'),
            'wipe' => __('Balayage', 'wp-gsap'),
            'none' => __('Aucune', 'wp-gsap'),
        ];
    }
}
