<?php

declare(strict_types=1);

final class WpMotion_Admin
{
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_notices', [$this, 'notices']);
        add_action('admin_bar_menu', [$this, 'admin_bar'], 80);
        add_action('admin_post_wpmotion_toggle', [$this, 'toggle']);
    }

    public function menu(): void
    {
        add_menu_page(
            __('WP Motion', 'wp-motion'),
            __('WP Motion', 'wp-motion'),
            'manage_options',
            'wp-motion',
            [$this, 'render'],
            'dashicons-leftright',
            58
        );
    }

    public function register(): void
    {
        register_setting('wp_motion', WpMotion_Settings::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [WpMotion_Settings::class, 'sanitize'],
            'default' => WpMotion_Settings::defaults(),
        ]);
    }

    public function assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_wp-motion') {
            return;
        }

        wp_enqueue_style('wpmotion-admin', WPMOTION_URL . 'assets/css/admin.css', [], WPMOTION_VERSION);
        wp_enqueue_script('wpmotion-admin', WPMOTION_URL . 'assets/js/admin.js', [], WPMOTION_VERSION, true);
        wp_localize_script('wpmotion-admin', 'WPMOTION_ADMIN', [
            'templates' => $this->template_labels(),
            'presets' => $this->preset_labels(),
            'i18n' => [
                'remove' => __('Supprimer', 'wp-motion'),
                'shared' => __('Morph image', 'wp-motion'),
            ],
        ]);
    }

    public function notices(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page !== 'wp-motion') {
            return;
        }
        $flag = isset($_GET['wpmotion']) ? sanitize_key((string) $_GET['wpmotion']) : '';
        if ($flag === 'imported') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Réglages importés.', 'wp-motion') . '</p></div>';
        }
        if ($flag === 'import-invalid' || $flag === 'import-missing') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Import impossible : fichier JSON invalide ou manquant.', 'wp-motion') . '</p></div>';
        }
    }

    /**
     * @param \WP_Admin_Bar $bar
     */
    public function admin_bar($bar): void
    {
        if (!current_user_can('manage_options') || !is_admin_bar_showing()) {
            return;
        }

        $settings = WpMotion_Settings::get();
        $enabled = !empty($settings['enabled']);
        $toggle = wp_nonce_url(admin_url('admin-post.php?action=wpmotion_toggle'), 'wpmotion_toggle');

        $bar->add_node([
            'id' => 'wpmotion',
            'title' => $enabled
                ? esc_html__('Motion : on', 'wp-motion')
                : esc_html__('Motion : off', 'wp-motion'),
            'href' => admin_url('admin.php?page=wp-motion'),
        ]);
        $bar->add_node([
            'id' => 'wpmotion-toggle',
            'parent' => 'wpmotion',
            'title' => $enabled
                ? esc_html__('Désactiver les transitions', 'wp-motion')
                : esc_html__('Activer les transitions', 'wp-motion'),
            'href' => $toggle,
        ]);

        if (!is_admin()) {
            $debug = add_query_arg('wpmotion_debug', '1');
            $bar->add_node([
                'id' => 'wpmotion-debug',
                'parent' => 'wpmotion',
                'title' => esc_html__('Voir les éléments partagés', 'wp-motion'),
                'href' => $debug,
            ]);
        } else {
            $bar->add_node([
                'id' => 'wpmotion-preview',
                'parent' => 'wpmotion',
                'title' => esc_html__('Tester (aperçu)', 'wp-motion'),
                'href' => admin_url('admin.php?page=wp-motion&tab=preview'),
            ]);
        }
    }

    public function toggle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Droits insuffisants.', 'wp-motion'), '', ['response' => 403]);
        }
        check_admin_referer('wpmotion_toggle');

        $settings = WpMotion_Settings::get();
        $settings['enabled'] = empty($settings['enabled']);
        update_option(WpMotion_Settings::OPTION, WpMotion_Settings::sanitize($settings));
        WpMotion_Settings::flush();

        $target = wp_get_referer();
        if (!is_string($target) || $target === '') {
            $target = admin_url('admin.php?page=wp-motion');
        }
        wp_safe_redirect($target);
        exit;
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

        $settings = WpMotion_Settings::get();
        $base = admin_url('admin.php?page=wp-motion');
        $enabled = !empty($settings['enabled']);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('WP Motion', 'wp-motion') . '</h1>';

        if ($enabled) {
            echo '<div class="notice notice-success inline"><p>';
            echo esc_html__('Les transitions de pages sont actives sur le front.', 'wp-motion');
            echo ' <a href="' . esc_url(add_query_arg('tab', 'preview', $base)) . '">' . esc_html__('Tester', 'wp-motion') . '</a>';
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html__('Les transitions sont désactivées. Rien n’est injecté sur le site tant que vous ne les activez pas.', 'wp-motion');
            echo '</p></div>';
        }

        echo '<nav class="nav-tab-wrapper wp-clearfix">';
        $this->tab_link($base, 'general', $tab, __('Réglages', 'wp-motion'));
        $this->tab_link($base, 'routes', $tab, __('De → vers', 'wp-motion'));
        $this->tab_link($base, 'preview', $tab, __('Tester', 'wp-motion'));
        $this->tab_link($base, 'tools', $tab, __('Outils', 'wp-motion'));
        echo '</nav>';

        if ($tab === 'tools') {
            $this->render_tools();
            echo '</div>';
            return;
        }

        if ($tab === 'preview') {
            $this->render_preview($settings);
            echo '</div>';
            return;
        }

        echo '<form action="' . esc_url(admin_url('options.php')) . '" method="post">';
        settings_fields('wp_motion');

        if ($tab === 'routes') {
            $this->render_routes($settings);
        } else {
            $this->render_general($settings);
        }

        submit_button(__('Enregistrer les modifications', 'wp-motion'));
        echo '</form></div>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function render_general(array $settings): void
    {
        echo '<h2>' . esc_html__('1. Activer', 'wp-motion') . '</h2>';
        echo '<p class="description">' . esc_html__('Rien n’est chargé sur le site tant que cette case est décochée.', 'wp-motion') . '</p>';
        echo '<table class="form-table" role="presentation">';
        $this->row(
            __('Transitions de pages', 'wp-motion'),
            $this->checkbox('enabled', !empty($settings['enabled']), __('Activer sur le front', 'wp-motion'))
        );
        echo '</table>';

        echo '<h2>' . esc_html__('2. Comment les pages changent', 'wp-motion') . '</h2>';
        echo '<p class="description">' . esc_html__('Effet par défaut, comme un déclencheur « chargement / sortie de page ». Les règles De → vers peuvent le remplacer.', 'wp-motion') . '</p>';
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row">' . esc_html__('Effet', 'wp-motion') . '</th><td>';
        $this->preset_cards((string) $settings['preset']);
        echo '</td></tr>';
        $this->row(
            __('Durée', 'wp-motion'),
            '<input name="' . esc_attr(WpMotion_Settings::OPTION) . '[duration_ms]" type="number" class="small-text" min="80" max="1200" step="10" value="' . esc_attr((string) $settings['duration_ms']) . '"> <span class="description">' . esc_html__('ms. 300–600 ms pour un changement de page ; au-delà, ça bloque.', 'wp-motion') . '</span>'
        );
        $this->row(
            __('Courbe', 'wp-motion'),
            $this->select('easing', (string) $settings['easing'], [
                'snappy' => __('Vif (recommandé)', 'wp-motion'),
                'cinematic' => __('Cinématique', 'wp-motion'),
                'ease-out' => __('Ease-out', 'wp-motion'),
                'ease-in-out' => __('Ease-in-out', 'wp-motion'),
                'ease' => __('Ease', 'wp-motion'),
                'linear' => __('Linéaire', 'wp-motion'),
            ])
        );
        echo '</table>';

        echo '<h2>' . esc_html__('3. Ce qui reste à l’écran', 'wp-motion') . '</h2>';
        echo '<p class="description">' . esc_html__('Comme un header Webflow « persistent » : ces éléments morphent au lieu de disparaître.', 'wp-motion') . '</p>';
        echo '<table class="form-table" role="presentation">';
        $this->row(
            __('Header', 'wp-motion'),
            $this->checkbox('header_persistent', !empty($settings['header_persistent']), __('Le header reste en place', 'wp-motion'))
            . '<p><input name="' . esc_attr(WpMotion_Settings::OPTION) . '[header_selector]" type="text" class="regular-text code" value="' . esc_attr((string) $settings['header_selector']) . '" aria-label="' . esc_attr__('Sélecteur CSS du header', 'wp-motion') . '"></p>'
            . '<p class="description">' . esc_html__('Thèmes classiques : sélecteur CSS. Thèmes de blocs : le template part « header » est détecté tout seul.', 'wp-motion') . '</p>'
        );
        $this->row(
            __('Image mise en avant', 'wp-motion'),
            $this->checkbox('shared_featured_image', !empty($settings['shared_featured_image']), __('La carte d’archive morph vers le hero (et l’image produit WooCommerce)', 'wp-motion'))
        );
        $this->row(
            __('Titre', 'wp-motion'),
            $this->checkbox('shared_title', !empty($settings['shared_title']), __('Le bloc « Titre de la publication » continue d’une vue à l’autre', 'wp-motion'))
        );
        echo '</table>';

        echo '<h2>' . esc_html__('4. Accessibilité', 'wp-motion') . '</h2>';
        echo '<table class="form-table" role="presentation">';
        $this->row(
            __('Mouvement réduit', 'wp-motion'),
            $this->select('reduced_motion', (string) $settings['reduced_motion'], [
                'fade' => __('Fondu court (80 ms) — recommandé', 'wp-motion'),
                'none' => __('Couper toute animation', 'wp-motion'),
            ])
            . '<p class="description">' . esc_html__('Respecte le réglage système « réduire les animations ». Ne pas contourner.', 'wp-motion') . '</p>'
        );
        echo '</table>';

        echo '<h2>' . esc_html__('5. Où ça ne joue pas', 'wp-motion') . '</h2>';
        echo '<p class="description">' . esc_html__('Panier, commande et compte doivent rester instantanés. wp-admin, login, REST et feeds sont toujours exclus.', 'wp-motion') . '</p>';
        echo '<table class="form-table" role="presentation">';
        $this->row(
            __('Chemins exclus', 'wp-motion'),
            '<textarea name="' . esc_attr(WpMotion_Settings::OPTION) . '[exclude_paths]" class="large-text code" rows="6">' . esc_textarea(implode("\n", $settings['exclude_paths'])) . '</textarea>'
            . '<p class="description">' . esc_html__('Un chemin par ligne, ex. /checkout/ ou /commande/.', 'wp-motion') . '</p>'
        );
        echo '</table>';

        echo '<h2>' . esc_html__('Dans l’éditeur de blocs', 'wp-motion') . '</h2>';
        echo '<p>' . esc_html__('Sur Image, Cover, Titre : « Continuer sur la page suivante » (morph). Sur Groupe / Titre : apparition au scroll (fondu, glissement, stagger, split mots, pin, parallax).', 'wp-motion') . '</p>';
    }

    private function preset_cards(string $current): void
    {
        $presets = [
            'fade' => [__('Fondu', 'wp-motion'), __('Discret, sûr partout.', 'wp-motion')],
            'slide' => [__('Glissement', 'wp-motion'), __('Article suivant / précédent.', 'wp-motion')],
            'wipe' => [__('Balayage', 'wp-motion'), __('Plus marqué, type rideau.', 'wp-motion')],
            'none' => [__('Aucun', 'wp-motion'), __('Couper sauf règles De → vers.', 'wp-motion')],
        ];
        $name = WpMotion_Settings::OPTION . '[preset]';
        echo '<div class="wpmotion-presets">';
        foreach ($presets as $value => $meta) {
            echo '<label class="wpmotion-preset">';
            echo '<input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . checked($current, $value, false) . '>';
            echo '<span class="wpmotion-preset__card">';
            echo '<span class="wpmotion-preset__stage wpmotion-preset__stage--' . esc_attr($value) . '" aria-hidden="true"><span></span></span>';
            echo '<strong>' . esc_html($meta[0]) . '</strong>';
            echo '<span class="description">' . esc_html($meta[1]) . '</span>';
            echo '</span></label>';
        }
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function render_routes(array $settings): void
    {
        echo '<p>' . esc_html__('Ciblage par type de page (comme le « page scoping » Webflow). La règle la plus précise gagne. * = n’importe laquelle.', 'wp-motion') . '</p>';
        echo '<p class="description">' . esc_html__('Panier, commande et compte sont en « Aucun » par défaut — ne les animez pas.', 'wp-motion') . '</p>';
        echo '<table class="widefat striped" id="wpmotion-routes">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('De', 'wp-motion') . '</th>';
        echo '<th>' . esc_html__('Vers', 'wp-motion') . '</th>';
        echo '<th>' . esc_html__('Effet', 'wp-motion') . '</th>';
        echo '<th>' . esc_html__('Morph image', 'wp-motion') . '</th>';
        echo '<th><span class="screen-reader-text">' . esc_html__('Actions', 'wp-motion') . '</span></th>';
        echo '</tr></thead><tbody>';

        $routes = is_array($settings['routes'] ?? null) ? $settings['routes'] : [];
        foreach ($routes as $index => $route) {
            $this->route_row((int) $index, $route);
        }

        echo '</tbody></table>';
        echo '<p><button type="button" class="button" id="wpmotion-add-route">' . esc_html__('Ajouter une règle', 'wp-motion') . '</button></p>';

        foreach (['enabled', 'preset', 'duration_ms', 'easing', 'reduced_motion', 'header_persistent', 'header_selector', 'shared_featured_image', 'shared_title'] as $keep) {
            $value = $settings[$keep] ?? '';
            if (is_bool($value)) {
                echo '<input type="hidden" name="' . esc_attr(WpMotion_Settings::OPTION . '[' . $keep . ']') . '" value="' . ($value ? '1' : '0') . '">';
            } else {
                echo '<input type="hidden" name="' . esc_attr(WpMotion_Settings::OPTION . '[' . $keep . ']') . '" value="' . esc_attr((string) $value) . '">';
            }
        }
        echo '<input type="hidden" name="' . esc_attr(WpMotion_Settings::OPTION) . '[exclude_paths]" value="' . esc_attr(implode("\n", $settings['exclude_paths'])) . '">';
    }

    /**
     * @param array<string, mixed> $route
     */
    private function route_row(int $index, array $route): void
    {
        $option = WpMotion_Settings::OPTION;
        $from = (string) ($route['from'] ?? '*');
        $to = (string) ($route['to'] ?? '*');
        $preset = (string) ($route['preset'] ?? 'fade');
        $shared = !empty($route['shared']);

        echo '<tr class="wpmotion-route">';
        echo '<td>' . $this->select_named($option . '[routes][' . $index . '][from]', $from, $this->template_labels()) . '</td>';
        echo '<td>' . $this->select_named($option . '[routes][' . $index . '][to]', $to, $this->template_labels()) . '</td>';
        echo '<td>' . $this->select_named($option . '[routes][' . $index . '][preset]', $preset, $this->preset_labels()) . '</td>';
        echo '<td><label><input type="checkbox" name="' . esc_attr($option . '[routes][' . $index . '][shared]') . '" value="1"' . checked($shared, true, false) . '> ' . esc_html__('Oui', 'wp-motion') . '</label></td>';
        echo '<td><button type="button" class="button-link-delete wpmotion-remove-route">' . esc_html__('Supprimer', 'wp-motion') . '</button></td>';
        echo '</tr>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function render_preview(array $settings): void
    {
        $home = home_url('/');
        $debug_home = add_query_arg('wpmotion_debug', '1', $home);
        $enabled = !empty($settings['enabled']);

        echo '<ol class="wpmotion-steps">';
        echo '<li><strong>' . esc_html__('Activer', 'wp-motion') . '</strong> — ';
        if ($enabled) {
            echo esc_html__('déjà fait.', 'wp-motion');
        } else {
            echo '<a href="' . esc_url(admin_url('admin.php?page=wp-motion')) . '">' . esc_html__('cochez « Activer sur le front »', 'wp-motion') . '</a>';
        }
        echo '</li>';
        echo '<li><strong>' . esc_html__('Vérifier les noms', 'wp-motion') . '</strong> — ';
        echo '<a href="' . esc_url($debug_home) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('ouvrir l’accueil en debug', 'wp-motion') . '</a>. ';
        echo esc_html__('Chaque carte doit avoir une seule image nommée (wpmotion-post-…-image).', 'wp-motion');
        echo '</li>';
        echo '<li><strong>' . esc_html__('Jouer la transition', 'wp-motion') . '</strong> — ';
        echo esc_html__('cliquez une carte (image mise en avant) vers un article. Le checkout et le panier ne s’animent jamais.', 'wp-motion');
        echo '</li>';
        echo '</ol>';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="wpmotion-from">' . esc_html__('Page d’origine', 'wp-motion') . '</label></th>';
        echo '<td><input id="wpmotion-from" class="regular-text code" type="url" value="' . esc_attr($home) . '"></td></tr>';
        echo '<tr><th scope="row"><label for="wpmotion-to">' . esc_html__('Page de destination', 'wp-motion') . '</label></th>';
        echo '<td><input id="wpmotion-to" class="regular-text code" type="url" value="" placeholder="https://"></td></tr>';
        echo '</table>';
        echo '<p><button type="button" class="button button-primary" id="wpmotion-open-from">' . esc_html__('Ouvrir la page d’origine', 'wp-motion') . '</button></p>';
    }

    private function render_tools(): void
    {
        echo '<h2>' . esc_html__('Exporter', 'wp-motion') . '</h2>';
        echo '<p>' . esc_html__('Télécharge les règles et réglages en JSON (staging → production).', 'wp-motion') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="wp_motion_export">';
        wp_nonce_field('wp_motion_export');
        submit_button(__('Télécharger JSON', 'wp-motion'), 'secondary', 'submit', false);
        echo '</form>';

        echo '<h2>' . esc_html__('Importer', 'wp-motion') . '</h2>';
        echo '<p>' . esc_html__('Remplace les réglages actuels par un fichier exporté.', 'wp-motion') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action" value="wp_motion_import">';
        wp_nonce_field('wp_motion_import');
        echo '<input type="file" name="wp_motion_import" accept="application/json,.json" required>';
        submit_button(__('Importer', 'wp-motion'), 'primary', 'submit', false);
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
        $name = WpMotion_Settings::OPTION . '[' . $key . ']';
        return '<label><input type="hidden" name="' . esc_attr($name) . '" value="0"><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($checked, true, false) . '> ' . esc_html($label) . '</label>';
    }

    /**
     * @param array<string, string> $choices
     */
    private function select(string $key, string $value, array $choices): string
    {
        return $this->select_named(WpMotion_Settings::OPTION . '[' . $key . ']', $value, $choices);
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
            '*' => __('N’importe laquelle', 'wp-motion'),
            'home' => __('Accueil', 'wp-motion'),
            'archive' => __('Archive / blog', 'wp-motion'),
            'single' => __('Article', 'wp-motion'),
            'page' => __('Page', 'wp-motion'),
            'singular' => __('Tout contenu unique', 'wp-motion'),
            'search' => __('Recherche', 'wp-motion'),
            '404' => '404',
            'shop' => __('Boutique', 'wp-motion'),
            'product' => __('Produit', 'wp-motion'),
            'cart' => __('Panier', 'wp-motion'),
            'checkout' => __('Commande', 'wp-motion'),
            'account' => __('Compte', 'wp-motion'),
            'unknown' => __('Autre', 'wp-motion'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function preset_labels(): array
    {
        return [
            'fade' => __('Fondu', 'wp-motion'),
            'slide' => __('Glissement', 'wp-motion'),
            'wipe' => __('Balayage', 'wp-motion'),
            'none' => __('Aucun', 'wp-motion'),
        ];
    }
}
