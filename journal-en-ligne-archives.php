<?php
function display_journalenligne_archives() {

    // Obtenir l'utilisateur actuel
    $current_user = wp_get_current_user();

    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        echo '<div class="alert alert-warning" role="alert">' . esc_html__('Veuillez vous connecter pour accéder à cette page.', 'paysan_breton_archives') . '</div>';
        
        // Afficher le formulaire de connexion
        wp_login_form(array(
            'redirect' => esc_url(get_permalink()), // Rediriger vers la même page après connexion
            'label_username' => __('Nom d\'utilisateur ou adresse e-mail', 'paysan_breton_archives'),
            'label_password' => __('Mot de passe', 'paysan_breton_archives'),
            'label_remember' => __('Se souvenir de moi', 'paysan_breton_archives'),
            'label_log_in'   => __('Se connecter', 'paysan_breton_archives'),
            'remember'       => true,
        ));
        return;
    }

    // Récupérer les départements associés à l'utilisateur
    $send_modes = array_merge(
        get_user_meta($current_user->ID, 'paysan_breton_aboweb_send_mode', false),
        get_user_meta($current_user->ID, 'paysan_breton_aboweb_send_mode_others', false)
    );
    error_log('Send Modes: ' . print_r($send_modes, true)); // Vérifier les départements associés

    // Tableau associatif pour les noms des départements
    $departements = [
        '22' => "Côtes d'Armor",
        '29' => 'Finistère',
        '35' => 'Ille-et-Vilaine',
        '56' => 'Morbihan',
    ];

    echo '<div class="journal-en-ligne--container woocommerce-page">';

    // Vérifier si le paramètre de requête 'archives' est présent
    if (isset($_GET['archives']) && $_GET['archives'] == 'true') {
        $selected_departement = isset($_GET['departement']) ? sanitize_text_field($_GET['departement']) : null;
        error_log('Selected Departement: ' . $selected_departement); // Vérifier le département sélectionné

        if ($selected_departement && isset($departements[$selected_departement])) {
            echo '<h2 class="text-uppercase">Archives édition ' . esc_html($departements[$selected_departement]) . '</h2>';

            // Récupérer la page actuelle
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            error_log('Paged: ' . $paged); // Vérifier la page actuelle

            // Exclure le dernier post
            $excluded_posts = [];
            if ($latest_post = get_posts(['post_type' => 'journalenligne', 'posts_per_page' => 1])) {
                $excluded_posts[] = $latest_post[0]->ID;
                error_log('Excluded Posts: ' . print_r($excluded_posts, true)); // Vérifier les posts exclus
            }

            // Arguments de la requête
            $query = [
                'post_type'      => ['journalenligne', 'magazine'], // Inclure les deux types de contenu
                'posts_per_page' => 51,
                'paged'          => $paged,
                'post__not_in'   => $excluded_posts, // Exclure les posts
            ];

            $loop = new WP_Query($query);

            if ($loop->have_posts()) {
                while ($loop->have_posts()) {
                    $loop->the_post();
                    $post_id = get_the_ID();
                    $title = get_the_title();
                    $url = get_permalink($post_id);

                    // Vérifier le type de contenu
                    $post_type = get_post_type($post_id);

                    if ($post_type === 'journalenligne') {
                        // Gestion des journaux en ligne
                        $edition_departements = [
                            '22' => get_field('version_pdf_cotes_armor', $post_id),
                            '29' => get_field('version_pdf_finistere', $post_id),
                            '35' => get_field('version_pdf_ille_et_vilaine', $post_id),
                            '56' => get_field('version_pdf_morbihan', $post_id),
                        ];

                        // Vérifier si un fichier PDF est disponible pour le département sélectionné
                        if (!empty($edition_departements[$selected_departement])) {
                            $version_pdf = $edition_departements[$selected_departement];
                            echo '<div class="row clearfix">
                                <div class="media col-md-3">
                                    <a class="content row" href="' . esc_url($url) . '" target="_blank" rel="noopener">' .
                                        get_the_post_thumbnail($post_id, 'couv-journal-papier') .
                                    '</a>
                                </div>
                                <div class="media-body col-md-9">
                                    <h3>' . esc_html($title) . '</h3>
                                    <p>
                                        <a href="' . esc_url($version_pdf) . '" class="btn btn-outline-primary" target="_blank" rel="noopener">
                                           Je consulte mon journal d\'archives
                                        </a>
                                    </p>
                                </div>
                            </div>';
                        }
                    } elseif ($post_type === 'magazine') {
                        // Gestion des magazines
                        $url_flipbook_magazine = get_field('url_flipbook_magazine');

                        echo '<div class="row clearfix">
                            <div class="media col-md-3">
                                <a class="content row" href="' . esc_url($url_flipbook_magazine) . '" target="_blank" rel="noopener">' .
                                    get_the_post_thumbnail($post_id, 'couv-journal-papier') .
                                '</a>
                            </div>
                            <div class="media-body col-md-9">
                                <h3>' . esc_html($title) . '</h3>
                                <p>
                                    <a href="' . esc_url($url_flipbook_magazine) . '" class="btn btn-outline-primary" target="_blank" rel="noopener">
                                       Je consulte mes pages spéciales d\'archive
                                    </a>
                                </p>
                            </div>
                        </div>';
                    }
                }

                // Ajouter la pagination
                echo '<div class="pagination">';
                wp_pagenavi(['query' => $loop]); // Utiliser PageNavi avec la requête personnalisée
                echo '</div>';
            } else {
                echo '<p>Aucun résultat trouvé.</p>';
            }

            wp_reset_postdata();
        } else {
            echo '<div class="alert alert-warning" role="alert">' . esc_html__("Aucun département sélectionné ou département invalide.", 'paysan_breton_archives') . '</div>';
        }
    } else {
        // Afficher le dernier journal
        $loop_this_week = new WP_Query([
            'post_type'      => 'journalenligne',
            'posts_per_page' => 1,
        ]);

        if ($loop_this_week->have_posts()) {
            while ($loop_this_week->have_posts()) {
                $loop_this_week->the_post();
                $post_id = get_the_ID();
                $url = get_permalink();
                $title = get_the_title();

                // Récupérer les PDF pour chaque département
                $edition_departements = [
                    '22' => get_field('version_pdf_cotes_armor', $post_id),
                    '29' => get_field('version_pdf_finistere', $post_id),
                    '35' => get_field('version_pdf_ille_et_vilaine', $post_id),
                    '56' => get_field('version_pdf_morbihan', $post_id),
                ];
                error_log('Edition Departements (This Week): ' . print_r($edition_departements, true)); // Vérifier les PDF de la semaine

                echo '<div class="row clearfix intro-header">
                <div class="tie-col-md-12">
                <h3 class="welcome-title">Bonjour</h3>
                <p>La version numérique du journal Paysan Breton de la semaine est en ligne.</p>
                </div>
                </div>';

                // Afficher la miniature et le titre une seule fois
                echo '<div class="row clearfix">
                    <div class="media col-md-3">
                        ' . get_the_post_thumbnail($post_id, 'couv-journal-papier') . '
                    </div>
                    <div class="media-body col-md-9">
                        <h3>' . $title . '</h3>';
                        // Afficher les boutons pour chaque PDF existant
                        foreach ($send_modes as $mode) {
                            error_log('Current Mode: ' . $mode);

                            // Vérifier si le département existe et si un PDF est disponible
                            if (isset($edition_departements[$mode]) && !empty($edition_departements[$mode])) {
                                $version_pdf = $edition_departements[$mode];
                                error_log('PDF found for mode: ' . $mode);

                                echo '<p>
                                    <a href="' . esc_url($version_pdf) . '" class="btn btn-outline-primary" target="_blank" rel="noopener">
                                        ' . __("Je consulte mon journal de la semaine (Edition " . $departements[$mode] . ")", 'paysan_breton_archives') . '
                                    </a>
                                </p>';
                            } else {
                                error_log('No PDF available for mode: ' . $mode);

                                // Afficher un message si aucun PDF n'est disponible
                                echo '<p class="text-danger">
                                    ' . __("Aucun PDF disponible pour l'édition " . $departements[$mode], 'paysan_breton_archives') . '
                                </p>';
                            }
                        }

                echo '</div>
                </div>';
            }
            wp_reset_postdata();
        }

        echo '<div class="row clearfix">
        <div class="media col-md-3"></div>
        <div class="media-body col-md-9">
            <h3>Semaines précédentes</h3>';

        // Boutons de lien vers les archives
        foreach ($send_modes as $mode) {
            if (isset($departements[$mode])) {
                // Générer l'URL vers le listing des archives pour le département
                $archives_url = wc_get_endpoint_url(
                    'archive-journal-en-ligne-test',
                    '',
                    wc_get_page_permalink('myaccount')
                );

                // Ajouter les paramètres de requête
                $archives_url = add_query_arg(
                    [
                        'archives' => 'true',
                        'departement' => $mode,
                    ],
                    $archives_url
                );

                echo '<p>
                        <a href="' . esc_url($archives_url) . '" class="btn btn-outline-primary text-uppercase">
                            ' . __("Je consulte mes archives (Edition " . esc_html($departements[$mode]) . ")", 'paysan_breton_archives') . '
                        </a>
                    </p>';
            }
        }
        echo '</div>
            </div>';
        // Insertion de la ligne de séparation
        echo '<hr class="bg-primary">';
    }
    echo '</div>';
}

add_shortcode('journalenligne_archives', 'display_journalenligne_archives');
