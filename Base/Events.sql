-- DONNÉES DE TEST POUR ORGANISATEUR11@YOPMAIL.COM
-- 27 événements avec éléments complets
-- 140 billets (10 annulés, 105 vendus, 15 en attente, 10 utilisés)
-- 10 utilisateurs en liste d'attente (utilisateur10 à utilisateur20)
-- 27 codes promotionnels (7 expirant bientôt)
-- ============================================================

-- Récupération de l'ID de l'organisateur
DO $$
DECLARE
    v_id_utilisateur_org BIGINT;
    v_id_profil_org BIGINT;
    v_id_lieu BIGINT;
    v_id_espace BIGINT;
    v_id_categorie BIGINT;
    v_id_type_event BIGINT;
    v_id_event BIGINT;
    v_id_type_billet BIGINT;
    v_id_commande BIGINT;
    v_id_element_commande BIGINT;
    v_id_panier BIGINT;
    v_id_facture BIGINT;
    v_id_paiement BIGINT;
    i INTEGER;
    j INTEGER;
    k INTEGER;
    v_date_debut TIMESTAMPTZ;
    v_date_fin TIMESTAMPTZ;
    v_prix NUMERIC(12,2);
    v_statut_billet TEXT;
    v_id_utilisateur BIGINT;
    v_compteur_billets INTEGER := 0;
    v_billets_annules INTEGER := 0;
    v_billets_vendus INTEGER := 0;
    v_billets_attente INTEGER := 0;
    v_billets_utilises INTEGER := 0;
    v_id_segment_adulte BIGINT;
    v_id_segment_enfant BIGINT;
    v_id_segment_tous BIGINT;
    v_id_cat_tous BIGINT;
    v_id_cat_standard BIGINT;
    v_id_cat_vip BIGINT;
    v_id_cat_gratuit BIGINT;
    v_id_cat_promo BIGINT;
    v_id_langue_fr BIGINT;
    v_id_langue_mg BIGINT;
    v_id_langue_en BIGINT;
    v_id_type_access_wheelchair BIGINT;
    v_id_type_access_hearing BIGINT;
    v_id_session BIGINT;
BEGIN
    -- Récupérer l'ID de l'utilisateur organisateur (créé par data.sql)
    SELECT id INTO v_id_utilisateur_org 
    FROM aiolia.utilisateurs 
    WHERE email = 'organisateur11@yopmail.com';

    IF v_id_utilisateur_org IS NULL THEN
        RAISE EXCEPTION 'Utilisateur organisateur11@yopmail.com non trouvé. Veuillez exécuter data.sql avant Events.sql';
    END IF;

    -- Créer ou récupérer le profil organisateur
    SELECT id INTO v_id_profil_org 
    FROM aiolia.profils_organisateurs 
    WHERE id_utilisateur = v_id_utilisateur_org;

    IF v_id_profil_org IS NULL THEN
        INSERT INTO aiolia.profils_organisateurs (
            id_utilisateur, nom_affichage, nom_legal, email_support, 
            telephone_support, type_organisation, statut_verification,
            onboarding_termine_le
        ) VALUES (
            v_id_utilisateur_org, 
            'Organisateur Premium Events', 
            'Premium Events SARL',
            'contact@premiumevents.mg',
            '+261340000001',
            'company',
            'verified',
            NOW() - INTERVAL '30 days'
        ) RETURNING id INTO v_id_profil_org;
    END IF;

    -- Créer des catégories d'événements si elles n'existent pas
    INSERT INTO aiolia.categories_evenements (slug, libelle, description, nom_icone, ordre_affichage)
    VALUES 
        ('musique', 'Musique', 'Concerts et festivals musicaux', 'music', 1),
        ('sport', 'Sport', 'Événements sportifs', 'trophy', 2),
        ('conference', 'Conférence', 'Conférences et séminaires', 'users', 3),
        ('theatre', 'Théâtre', 'Pièces de théâtre et spectacles', 'drama', 4),
        ('cinema', 'Cinéma', 'Projections et avant-premières', 'film', 5),
        ('art', 'Art', 'Expositions et vernissages', 'palette', 6),
        ('gastronomie', 'Gastronomie', 'Événements culinaires', 'utensils', 7)
    ON CONFLICT (slug) DO NOTHING;

    -- Créer des types d'événements
    INSERT INTO aiolia.types_evenements (slug, libelle, description)
    VALUES 
        ('concert', 'Concert', 'Concert live'),
        ('festival', 'Festival', 'Festival multi-artistes'),
        ('conference', 'Conférence', 'Conférence professionnelle'),
        ('atelier', 'Atelier', 'Atelier pratique'),
        ('exposition', 'Exposition', 'Exposition artistique'),
        ('spectacle', 'Spectacle', 'Spectacle vivant'),
        ('projection', 'Projection', 'Projection cinéma')
    ON CONFLICT (slug) DO NOTHING;

    -- ============================================================
    -- CRÉER LES LANGUES DE RÉFÉRENCE
    -- ============================================================
    INSERT INTO aiolia.langues (code, libelle, est_actif)
    VALUES 
        ('mg', 'Malagasy', TRUE),
        ('fr', 'Français', TRUE),
        ('en', 'Anglais', TRUE)
    ON CONFLICT (code) DO NOTHING;

    -- ============================================================
    -- CRÉER LES TYPES D'ACCESSIBILITÉ DE RÉFÉRENCE
    -- ============================================================
    INSERT INTO aiolia.types_accessibilite (code, libelle, est_actif)
    VALUES 
        ('wheelchair', 'Accès fauteuil roulant', TRUE),
        ('hearing', 'Accessible aux malentendants', TRUE),
        ('visual', 'Accessible aux malvoyants', TRUE),
        ('mobility', 'Accessible mobilité réduite', TRUE),
        ('cognitive', 'Accessible troubles cognitifs', TRUE),
        ('other', 'Autre', TRUE)
    ON CONFLICT (code) DO NOTHING;

    -- Récupérer les IDs des langues et types d'accessibilité
    SELECT id INTO v_id_langue_fr FROM aiolia.langues WHERE code = 'fr';
    SELECT id INTO v_id_langue_mg FROM aiolia.langues WHERE code = 'mg';
    SELECT id INTO v_id_langue_en FROM aiolia.langues WHERE code = 'en';
    SELECT id INTO v_id_type_access_wheelchair FROM aiolia.types_accessibilite WHERE code = 'wheelchair';
    SELECT id INTO v_id_type_access_hearing FROM aiolia.types_accessibilite WHERE code = 'hearing';

    -- Créer un lieu principal
    INSERT INTO aiolia.lieux (
        id_profil_organisateur, nom, slug, description,
        ligne_adresse_1, ville, code_postal, code_pays,
        latitude, longitude, fuseau_horaire,
        email_contact, telephone_contact, capacite
    ) VALUES (
        v_id_profil_org,
        'Centre Culturel Ivandry',
        'centre-culturel-ivandry',
        'Centre culturel moderne avec plusieurs salles',
        'Rue de l''Independence, Ivandry',
        'Antananarivo',
        '101',
        'MG',
        -18.8792,
        47.5079,
        'Indian/Antananarivo',
        'contact@centreivandry.mg',
        '+261340000002',
        1500
    ) RETURNING id INTO v_id_lieu;

    -- Créer des espaces dans le lieu
    INSERT INTO aiolia.espaces_lieux (id_lieu, nom, description, capacite, est_par_defaut)
    VALUES 
        (v_id_lieu, 'Grande Salle', 'Salle principale de 800 places', 800, TRUE)
    RETURNING id INTO v_id_espace;

    INSERT INTO aiolia.espaces_lieux (id_lieu, nom, description, capacite)
    VALUES 
        (v_id_lieu, 'Petite Salle', 'Salle intime de 150 places', 150),
        (v_id_lieu, 'Salle de Conférence', 'Salle équipée pour conférences', 300),
        (v_id_lieu, 'Espace Extérieur', 'Espace en plein air', 500);

    -- ============================================================
    -- CONFIGURATION DES SEGMENTS DE BILLETS
    -- (adulte / enfant / tous publics)
    -- ============================================================
    INSERT INTO aiolia.configuration_segments_billets (nom, age_min, age_max)
    VALUES 
        ('adulte', 18, NULL),
        ('enfant', 0, 12),
        ('tous', NULL, NULL)
    ON CONFLICT (nom) DO NOTHING;

    SELECT id INTO v_id_segment_adulte
    FROM aiolia.configuration_segments_billets
    WHERE nom = 'adulte';

    SELECT id INTO v_id_segment_enfant
    FROM aiolia.configuration_segments_billets
    WHERE nom = 'enfant';

    SELECT id INTO v_id_segment_tous
    FROM aiolia.configuration_segments_billets
    WHERE nom = 'tous';

    -- Sécurité : vérifier que les segments existent bien
    IF v_id_segment_adulte IS NULL OR v_id_segment_enfant IS NULL OR v_id_segment_tous IS NULL THEN
        RAISE EXCEPTION 'Les segments de billets (adulte/enfant/tous) ne sont pas correctement configurés';
    END IF;

    -- ============================================================
    -- CONFIGURATION DES CATÉGORIES DE BILLETS
    -- (tous / standard / vip / gratuit / promo)
    -- ============================================================
    INSERT INTO aiolia.configuration_categories_billets (nom, description)
    VALUES
        ('tous', 'Catégorie par défaut, tous types de billets'),
        ('standard', 'Billet standard'),
        ('vip', 'Billet VIP avec avantages'),
        ('gratuit', 'Billet gratuit'),
        ('promo', 'Billet promotionnel')
    ON CONFLICT (nom) DO NOTHING;

    SELECT id INTO v_id_cat_tous
    FROM aiolia.configuration_categories_billets
    WHERE nom = 'tous';

    SELECT id INTO v_id_cat_standard
    FROM aiolia.configuration_categories_billets
    WHERE nom = 'standard';

    SELECT id INTO v_id_cat_vip
    FROM aiolia.configuration_categories_billets
    WHERE nom = 'vip';

    SELECT id INTO v_id_cat_gratuit
    FROM aiolia.configuration_categories_billets
    WHERE nom = 'gratuit';

    SELECT id INTO v_id_cat_promo
    FROM aiolia.configuration_categories_billets
    WHERE nom = 'promo';

    IF v_id_cat_tous IS NULL
       OR v_id_cat_standard IS NULL
       OR v_id_cat_vip IS NULL
       OR v_id_cat_gratuit IS NULL
       OR v_id_cat_promo IS NULL THEN
        RAISE EXCEPTION 'Les catégories de billets ne sont pas correctement configurées';
    END IF;

    -- ============================================================
    -- CRÉATION DES 27 ÉVÉNEMENTS
    -- ============================================================
    FOR i IN 1..27 LOOP
        -- Définir les dates (mix d'événements passés, présents et futurs)
        IF i <= 9 THEN
            -- Événements passés
            v_date_debut := NOW() - (INTERVAL '30 days' * (10 - i));
        ELSIF i <= 18 THEN
            -- Événements récents/en cours
            v_date_debut := NOW() - (INTERVAL '5 days') + (INTERVAL '2 days' * (i - 9));
        ELSE
            -- Événements futurs
            v_date_debut := NOW() + (INTERVAL '7 days' * (i - 18));
        END IF;
        v_date_fin := v_date_debut + INTERVAL '4 hours';

        -- Sélectionner catégorie et type aléatoirement
        SELECT id INTO v_id_categorie 
        FROM aiolia.categories_evenements 
        ORDER BY RANDOM() 
        LIMIT 1;

        SELECT id INTO v_id_type_event 
        FROM aiolia.types_evenements 
        ORDER BY RANDOM() 
        LIMIT 1;

        -- Créer l'événement
        INSERT INTO aiolia.evenements (
            id_profil_organisateur, id_categorie_principale, id_type_evenement,
            id_lieu, id_espace_principal, slug, titre, sous_titre, resume, description,
            url_image_couverture, statut, visibilite, format_evenement,
            capacite, commence_le, se_termine_le,
            ventes_commencent_le, ventes_se_terminent_le,
            restriction_age, est_en_vedette, est_mis_en_avant, tarif_unique
        ) VALUES (
            v_id_profil_org,
            v_id_categorie,
            v_id_type_event,
            v_id_lieu,
            v_id_espace,
            'event-' || i || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
            'Événement #' || i || ' - ' || (ARRAY['Concert Exceptionnel', 'Festival d''Été', 'Conférence Tech', 'Spectacle Musical', 'Exposition Art', 'Soirée Gala', 'Atelier Créatif'])[1 + (i % 7)],
            'Une expérience unique à ne pas manquer',
            'Résumé captivant de l''événement #' || i,
            'Description complète et détaillée de l''événement #' || i || '. Cet événement promet d''être mémorable avec des performances exceptionnelles et une ambiance unique.',
            'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800',
            (CASE 
                WHEN i <= 9 THEN 'archived'
                WHEN i <= 18 THEN 'published'
                ELSE 'published'
            END)::event_status_enum,
            'public'::event_visibility_enum,
            (CASE WHEN i % 3 = 0 THEN 'online' WHEN i % 3 = 1 THEN 'hybrid' ELSE 'in_person' END)::event_format_enum,
            (ARRAY[500, 800, 300, 1000, 150])[1 + (i % 5)],
            v_date_debut,
            v_date_fin,
            v_date_debut - INTERVAL '30 days',
            v_date_debut - INTERVAL '1 hour',
            CASE WHEN i % 4 = 0 THEN '18+' ELSE NULL END,
            i % 5 = 0,
            i % 7 = 0,
            FALSE
        ) RETURNING id INTO v_id_event;

        -- Ajouter des tags
        FOR j IN 1..3 LOOP
            INSERT INTO aiolia.tags_evenements (slug, libelle)
            VALUES (
                'tag-' || i || '-' || j,
                (ARRAY['Premium', 'Exclusif', 'Tendance', 'Populaire', 'Familial', 'VIP', 'Unique'])[1 + ((i + j) % 7)]
            )
            ON CONFLICT (slug) DO NOTHING;

            INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
            SELECT v_id_event, id 
            FROM aiolia.tags_evenements 
            WHERE slug = 'tag-' || i || '-' || j
            ON CONFLICT DO NOTHING;
        END LOOP;

        -- Ajouter des médias
        INSERT INTO aiolia.medias_evenements (id_evenement, type_media, url, texte_alternatif, ordre_affichage, est_affiche_principale)
        VALUES 
            (v_id_event, 'image', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200', 'Image principale', 0, TRUE),
            (v_id_event, 'image', 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=800', 'Image secondaire', 1, FALSE),
            (v_id_event, 'video', 'https://youtube.com/watch?v=example' || i, 'Bande annonce', 2, FALSE);

        -- Ajouter modes de paiement
        INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif)
        VALUES 
            (v_id_event, 'mvola', TRUE),
            (v_id_event, 'orange', TRUE),
            (v_id_event, 'airtel', i % 2 = 0),
            (v_id_event, 'visa', TRUE),
            (v_id_event, 'mastercard', TRUE);

        -- Ajouter langues (via table de liaison)
        IF v_id_langue_fr IS NOT NULL THEN
            INSERT INTO aiolia.liens_langues_evenements (id_evenement, id_langue)
            VALUES (v_id_event, v_id_langue_fr)
            ON CONFLICT DO NOTHING;
        END IF;
        IF v_id_langue_mg IS NOT NULL THEN
            INSERT INTO aiolia.liens_langues_evenements (id_evenement, id_langue)
            VALUES (v_id_event, v_id_langue_mg)
            ON CONFLICT DO NOTHING;
        END IF;
        IF v_id_langue_en IS NOT NULL THEN
            INSERT INTO aiolia.liens_langues_evenements (id_evenement, id_langue)
            VALUES (v_id_event, v_id_langue_en)
            ON CONFLICT DO NOTHING;
        END IF;

        -- Ajouter accessibilité (via table de liaison)
        IF i % 3 = 0 THEN
            IF v_id_type_access_wheelchair IS NOT NULL THEN
                INSERT INTO aiolia.liens_accessibilite_evenements (id_evenement, id_type_accessibilite, description)
                VALUES (v_id_event, v_id_type_access_wheelchair, 'Accès complet pour fauteuils roulants')
                ON CONFLICT DO NOTHING;
            END IF;
            IF v_id_type_access_hearing IS NOT NULL THEN
                INSERT INTO aiolia.liens_accessibilite_evenements (id_evenement, id_type_accessibilite, description)
                VALUES (v_id_event, v_id_type_access_hearing, 'Boucle magnétique disponible')
                ON CONFLICT DO NOTHING;
            END IF;
        END IF;

        -- Créer des sessions pour certains événements (1 session sur 3)
        IF i % 3 = 1 THEN
            INSERT INTO aiolia.sessions_evenements (
                id_evenement, id_espace_lieu, titre, description,
                commence_le, se_termine_le, capacite
            ) VALUES (
                v_id_event,
                v_id_espace,
                'Session principale',
                'Session principale de l''événement',
                v_date_debut,
                v_date_fin,
                (ARRAY[500, 800, 300])[1 + (i % 3)]
            ) RETURNING id INTO v_id_session;
        END IF;

        -- ============================================================
        -- CRÉER 3-6 TYPES DE BILLETS PAR ÉVÉNEMENT
        -- Avec différents scénarios de tarification
        -- ============================================================
        
        -- Scénario 1: Événements avec tarif unique (même prix pour tous)
        IF i % 4 = 0 THEN
            FOR j IN 1..3 LOOP
                v_prix := (ARRAY[10000, 25000, 50000])[j];
                
                INSERT INTO aiolia.types_billets (
                    id_evenement, id_configuration_categorie, id_configuration_segment,
                    nom, description, devise, prix_de_base,
                    frais_service, taux_tva,
                    ventes_commencent_le, ventes_se_terminent_le,
                    minimum_par_commande, maximum_par_commande
                ) VALUES (
                    v_id_event,
                    CASE 
                        WHEN j = 1 THEN v_id_cat_standard
                        ELSE v_id_cat_vip
                    END,
                    v_id_segment_tous,
                    (ARRAY['Standard', 'VIP', 'Premium'])[j],
                    'Billet ' || (ARRAY['Standard', 'VIP', 'Premium'])[j] || ' - Tarif unique',
                    'MGA',
                    v_prix,
                    v_prix * 0.1,
                    20.0,
                    v_date_debut - INTERVAL '30 days',
                    v_date_debut - INTERVAL '1 hour',
                    1,
                    10
                ) RETURNING id INTO v_id_type_billet;

                INSERT INTO aiolia.inventaire_billets (id_type_billet, quantite_totale, quantite_reservee, quantite_vendue)
                VALUES (
                    v_id_type_billet,
                    (ARRAY[100, 50, 30])[j],
                    CASE WHEN i <= 18 THEN (3 + j) ELSE 0 END,
                    CASE WHEN i <= 18 THEN (15 + (j * 5)) ELSE 0 END
                );

                -- Ajouter une règle de tarification pour certains billets
                IF j = 1 AND i % 5 = 0 THEN
                    INSERT INTO aiolia.regles_tarification (
                        id_type_billet, type_regle, valeur_seuil, valeur,
                        commence_le, se_termine_le
                    ) VALUES (
                        v_id_type_billet,
                        'time_window'::pricing_rule_type_enum,
                        NULL,
                        v_prix * 0.8, -- Réduction de 20%
                        v_date_debut - INTERVAL '30 days',
                        v_date_debut - INTERVAL '7 days'
                    );
                END IF;

                -- Ajouter un historique de prix si le prix a changé
                IF j > 1 THEN
                    INSERT INTO aiolia.historique_prix_billets (
                        id_type_billet, modifie_par, prix_precedent, nouveau_prix, raison
                    ) VALUES (
                        v_id_type_billet,
                        v_id_utilisateur_org,
                        v_prix * 0.9,
                        v_prix,
                        'Ajustement tarifaire initial'
                    );
                END IF;
            END LOOP;

        -- Scénario 2: Événements avec tarification différenciée adulte/enfant
        ELSIF i % 4 = 1 THEN
            -- Billets Standard Adulte/Enfant
            v_prix := 15000;
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_standard, v_id_segment_adulte,
                'Standard - Adulte', 'Billet Standard pour adultes', 'MGA',
                v_prix, v_prix * 0.1, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 10
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 120, 8, 40);

            -- Ajouter une règle de tarification pour les billets adulte
            INSERT INTO aiolia.regles_tarification (
                id_type_billet, type_regle, valeur_seuil, valeur,
                commence_le, se_termine_le
            ) VALUES (
                v_id_type_billet,
                'tier'::pricing_rule_type_enum,
                50,
                v_prix * 0.9, -- Réduction de 10% après 50 billets
                v_date_debut - INTERVAL '30 days',
                v_date_debut - INTERVAL '1 hour'
            );

            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_standard, v_id_segment_enfant,
                'Standard - Enfant', 'Billet Standard pour enfants (-12 ans)', 'MGA',
                v_prix * 0.6, v_prix * 0.06, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 10
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 80, 5, 25);

            -- Billets VIP Adulte/Enfant
            v_prix := 50000;
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_vip, v_id_segment_adulte,
                'VIP - Adulte', 'Billet VIP pour adultes avec avantages exclusifs', 'MGA',
                v_prix, v_prix * 0.1, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 5
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 40, 3, 15);

            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_vip, v_id_segment_enfant,
                'VIP - Enfant', 'Billet VIP pour enfants (-12 ans)', 'MGA',
                v_prix * 0.7, v_prix * 0.07, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 5
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 30, 2, 10);

        -- Scénario 3: Événements avec tarif flexible (prix de base, segment "tous")
        ELSIF i % 4 = 2 THEN
            FOR j IN 1..4 LOOP
                v_prix := (ARRAY[8000, 20000, 45000, 80000])[j];
                
                INSERT INTO aiolia.types_billets (
                    id_evenement, id_configuration_categorie, id_configuration_segment,
                    nom, description, devise, prix_de_base,
                    frais_service, taux_tva,
                    ventes_commencent_le, ventes_se_terminent_le,
                    minimum_par_commande, maximum_par_commande
                ) VALUES (
                    v_id_event,
                    CASE 
                        WHEN j = 1 THEN v_id_cat_promo
                        WHEN j = 2 THEN v_id_cat_standard
                        ELSE v_id_cat_vip
                    END,
                    v_id_segment_tous,
                    (ARRAY['Early Bird', 'Standard', 'VIP', 'Platinum'])[j],
                    'Billet ' || (ARRAY['Early Bird', 'Standard', 'VIP', 'Platinum'])[j] || ' - Tarif adulte/enfant différencié',
                    'MGA',
                    v_prix,
                    v_prix * 0.1,
                    20.0,
                    v_date_debut - INTERVAL '30 days',
                    v_date_debut - INTERVAL '1 hour',
                    1,
                    CASE WHEN j >= 3 THEN 4 ELSE 10 END
                ) RETURNING id INTO v_id_type_billet;

                INSERT INTO aiolia.inventaire_billets (id_type_billet, quantite_totale, quantite_reservee, quantite_vendue)
                VALUES (
                    v_id_type_billet,
                    (ARRAY[150, 100, 50, 20])[j],
                    CASE WHEN i <= 18 THEN (2 + j) ELSE 0 END,
                    CASE WHEN i <= 18 THEN (20 + (j * 3)) ELSE 0 END
                );

                -- Ajouter des règles de tarification pour les billets Early Bird
                IF j = 1 THEN
                    INSERT INTO aiolia.regles_tarification (
                        id_type_billet, type_regle, valeur_seuil, valeur,
                        commence_le, se_termine_le
                    ) VALUES (
                        v_id_type_billet,
                        'time_window'::pricing_rule_type_enum,
                        NULL,
                        v_prix * 0.7, -- Réduction de 30% pour Early Bird
                        v_date_debut - INTERVAL '30 days',
                        v_date_debut - INTERVAL '14 days'
                    );
                END IF;
            END LOOP;

        -- Scénario 4: Événements avec catégories multiples et tarifs variés
        ELSE
            -- Gratuit pour enfants
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_gratuit, v_id_segment_enfant,
                'Gratuit - Enfant', 'Entrée gratuite pour enfants (-5 ans)', 'MGA',
                0, 0, 0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 4
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 50, 2, 15);

            -- Standard avec tarif unique (tous publics)
            v_prix := 12000;
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_standard, v_id_segment_tous,
                'Standard', 'Billet Standard - Tarif unique', 'MGA',
                v_prix, v_prix * 0.1, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 8
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 200, 10, 60);

            -- Ajouter une règle de tarification pour les billets standard
            INSERT INTO aiolia.regles_tarification (
                id_type_billet, type_regle, valeur_seuil, valeur,
                commence_le, se_termine_le
            ) VALUES (
                v_id_type_billet,
                'promo'::pricing_rule_type_enum,
                NULL,
                v_prix * 0.85, -- Réduction de 15% avec code promo
                v_date_debut - INTERVAL '30 days',
                v_date_debut - INTERVAL '1 hour'
            );

            -- VIP Adulte uniquement
            v_prix := 60000;
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_vip, v_id_segment_adulte,
                'VIP - Adulte', 'Billet VIP réservé aux adultes (18+)', 'MGA',
                v_prix, v_prix * 0.1, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 4
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 30, 2, 10);

            -- Premium (tous publics, positionné en VIP)
            v_prix := 35000;
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_vip, v_id_segment_tous,
                'Premium', 'Billet Premium avec tarif réduit enfant', 'MGA',
                v_prix, v_prix * 0.1, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 6
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 80, 5, 30);

            -- Gold - Tarif unique premium (tous publics, VIP)
            v_prix := 100000;
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande
            ) VALUES (
                v_id_event, v_id_cat_vip, v_id_segment_tous,
                'Gold', 'Billet Gold - Expérience ultime', 'MGA',
                v_prix, v_prix * 0.1, 20.0,
                v_date_debut - INTERVAL '30 days', v_date_debut - INTERVAL '1 hour', 1, 2
            ) RETURNING id INTO v_id_type_billet;
            INSERT INTO aiolia.inventaire_billets VALUES (v_id_type_billet, 15, 1, 5);
        END IF;
    END LOOP;

    -- ============================================================
    -- CRÉER 140 BILLETS AVEC DIFFÉRENTS STATUTS
    -- ============================================================
    FOR i IN 1..140 LOOP
        -- Déterminer le statut du billet selon la distribution demandée
        IF v_billets_annules < 10 THEN
            v_statut_billet := 'cancelled';
            v_billets_annules := v_billets_annules + 1;
        ELSIF v_billets_vendus < 105 THEN
            v_statut_billet := 'valid';
            v_billets_vendus := v_billets_vendus + 1;
        ELSIF v_billets_attente < 15 THEN
            v_statut_billet := 'valid'; -- En attente (commande en pending)
            v_billets_attente := v_billets_attente + 1;
        ELSE
            v_statut_billet := 'used';
            v_billets_utilises := v_billets_utilises + 1;
        END IF;

        -- Sélectionner un utilisateur aléatoire (utilisateur10 à utilisateur50)
        SELECT id INTO v_id_utilisateur 
        FROM aiolia.utilisateurs 
        WHERE email = 'utilisateur' || (10 + (i % 41)) || '@yopmail.com'
        LIMIT 1;

        -- Sélectionner un type de billet aléatoire
        SELECT id INTO v_id_type_billet 
        FROM aiolia.types_billets 
        WHERE id_evenement IN (SELECT id FROM aiolia.evenements WHERE id_profil_organisateur = v_id_profil_org)
        ORDER BY RANDOM() 
        LIMIT 1;

        -- Créer un panier
        INSERT INTO aiolia.paniers (
            id_utilisateur, statut, devise, montant_total,
            expire_le
        ) VALUES (
            v_id_utilisateur,
            (CASE WHEN v_statut_billet = 'valid' AND v_billets_attente <= 15 THEN 'active' ELSE 'converted' END)::cart_status_enum,
            'MGA',
            0,
            NOW() + INTERVAL '30 minutes'
        ) RETURNING id INTO v_id_panier;

        -- Créer la commande
        INSERT INTO aiolia.commandes (
            id_utilisateur, id_panier,
            statut, montant_total, devise
        ) VALUES (
            v_id_utilisateur,
            v_id_panier,
            (CASE 
                WHEN v_statut_billet = 'cancelled' THEN 'cancelled'
                WHEN v_statut_billet = 'valid' AND v_billets_attente <= 15 THEN 'pending'
                ELSE 'paid'
            END)::order_status_enum,  -- ⬅️ AJOUT DU CAST ICI
            (SELECT prix_de_base FROM aiolia.types_billets WHERE id = v_id_type_billet),
            'MGA'
        ) RETURNING id INTO v_id_commande;

        -- Créer l'élément de commande
        INSERT INTO aiolia.elements_commandes (
            id_commande, id_type_billet, quantite,
            prix_unitaire, frais_service, montant_tva, montant_total
        ) VALUES (
            v_id_commande,
            v_id_type_billet,
            1,
            (SELECT prix_de_base FROM aiolia.types_billets WHERE id = v_id_type_billet),
            (SELECT frais_service FROM aiolia.types_billets WHERE id = v_id_type_billet),
            (SELECT prix_de_base * 0.2 FROM aiolia.types_billets WHERE id = v_id_type_billet),
            (SELECT prix_de_base * 1.3 FROM aiolia.types_billets WHERE id = v_id_type_billet)
        ) RETURNING id INTO v_id_element_commande;

        -- Créer le billet
        INSERT INTO aiolia.billets (
            id_element_commande, id_type_billet,
            id_utilisateur_proprietaire, statut,
            code_qr, checksum_qr, emis_le
        ) VALUES (
            v_id_element_commande,
            v_id_type_billet,
            v_id_utilisateur,
            v_statut_billet::ticket_status_enum,
            'QR-' || i || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
            md5('QR-' || i || '-' || EXTRACT(EPOCH FROM NOW())::TEXT),
            NOW() - (INTERVAL '1 day' * (140 - i))
        );

        -- Créer facture et paiement pour les billets payés
        IF v_statut_billet IN ('valid', 'used') AND v_billets_attente > 15 THEN
            INSERT INTO aiolia.factures_billets (
                id_commande, id_client, devise,
                montant_sous_total, montant_tva, montant_total,
                montant_ht, montant_tva_detail, montant_ttc,
                methode_paiement, statut, emise_le, payee_le
            ) VALUES (
                v_id_commande,
                v_id_utilisateur,
                'MGA',
                (SELECT prix_de_base FROM aiolia.types_billets WHERE id = v_id_type_billet),
                (SELECT prix_de_base * 0.2 FROM aiolia.types_billets WHERE id = v_id_type_billet),
                (SELECT prix_de_base * 1.3 FROM aiolia.types_billets WHERE id = v_id_type_billet),
                (SELECT prix_de_base FROM aiolia.types_billets WHERE id = v_id_type_billet),
                (SELECT prix_de_base * 0.2 FROM aiolia.types_billets WHERE id = v_id_type_billet),
                (SELECT prix_de_base * 1.3 FROM aiolia.types_billets WHERE id = v_id_type_billet),
                (ARRAY['mvola', 'orange', 'airtel', 'visa', 'mastercard'])[1 + (i % 5)],
                'paid',
                NOW() - (INTERVAL '1 day' * (140 - i)),
                NOW() - (INTERVAL '1 day' * (140 - i)) + INTERVAL '2 hours'
            ) RETURNING id INTO v_id_facture;

            INSERT INTO aiolia.paiements_billets (
                id_facture, fournisseur, reference_fournisseur,
                statut, montant, devise, paye_le
            ) VALUES (
                v_id_facture,
                (ARRAY['mvola', 'orange', 'airtel', 'visa', 'mastercard'])[1 + (i % 5)],
                'REF-' || i || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                'paid',
                (SELECT prix_de_base * 1.3 FROM aiolia.types_billets WHERE id = v_id_type_billet),
                'MGA',
                NOW() - (INTERVAL '1 day' * (140 - i)) + INTERVAL '2 hours'
            );
        END IF;
    END LOOP;

    -- ============================================================
    -- CRÉER 27 CODES PROMOTIONNELS (7 expirant bientôt)
    -- ============================================================
    FOR i IN 1..27 LOOP
        INSERT INTO aiolia.codes_promotionnels (
            id_profil_organisateur, code,
            type_promotion, valeur,
            utilisation_maximale_totale, utilisation_maximale_par_utilisateur,
            commence_le, se_termine_le
        ) VALUES (
            v_id_profil_org,
            'PROMO' || LPAD(i::TEXT, 4, '0'),
            (ARRAY['percent', 'amount']::promotion_type_enum[])[1 + (i % 2)],
            CASE 
                WHEN i % 2 = 0 THEN (i * 5)::NUMERIC  -- Pourcentage
                ELSE (i * 5000)::NUMERIC  -- Montant fixe
            END,
            CASE WHEN i % 3 = 0 THEN 100 ELSE NULL END,
            CASE WHEN i % 4 = 0 THEN 1 ELSE 5 END,
            NOW() - INTERVAL '15 days',
            CASE 
                WHEN i <= 7 THEN NOW() + INTERVAL '3 days'  -- 7 codes expirant bientôt
                WHEN i <= 20 THEN NOW() + INTERVAL '30 days'
                ELSE NOW() + INTERVAL '90 days'
            END
        );
    END LOOP;

    -- ============================================================
    -- AJOUTER 10 UTILISATEURS EN LISTE D'ATTENTE
    -- (utilisateur10 à utilisateur20 pour un événement populaire)
    -- ============================================================
    -- Sélectionner un événement futur populaire
    SELECT id INTO v_id_event 
    FROM aiolia.evenements 
    WHERE id_profil_organisateur = v_id_profil_org 
        AND commence_le > NOW()
    ORDER BY est_en_vedette DESC, commence_le ASC
    LIMIT 1;

    -- Créer la liste d'attente (wishlist)
    FOR i IN 10..20 LOOP
        SELECT id INTO v_id_utilisateur 
        FROM aiolia.utilisateurs 
        WHERE email = 'utilisateur' || i || '@yopmail.com'
        LIMIT 1;

        IF v_id_utilisateur IS NOT NULL AND v_id_event IS NOT NULL THEN
            -- Créer ou récupérer la liste de souhaits par défaut
            INSERT INTO aiolia.listes_souhaits (id_utilisateur, titre, est_par_defaut)
            VALUES (v_id_utilisateur, 'Mes Favoris', TRUE)
            ON CONFLICT DO NOTHING;

            -- Ajouter l'événement à la liste de souhaits
            INSERT INTO aiolia.elements_listes_souhaits (id_liste_souhaits, id_evenement, ajoute_le)
            SELECT ls.id, v_id_event, NOW() - (INTERVAL '1 day' * (20 - i))
            FROM aiolia.listes_souhaits ls
            WHERE ls.id_utilisateur = v_id_utilisateur AND ls.est_par_defaut = TRUE
            ON CONFLICT DO NOTHING;
        END IF;
    END LOOP;

    RAISE NOTICE '✅ Données de test créées avec succès pour organisateur11@yopmail.com';
    RAISE NOTICE '   - Profil organisateur ID: %', v_id_profil_org;
    RAISE NOTICE '   - 27 événements créés';
    RAISE NOTICE '   - 140 billets créés (10 annulés, 105 vendus, 15 en attente, 10 utilisés)';
    RAISE NOTICE '   - 27 codes promotionnels créés (7 expirant bientôt)';
    RAISE NOTICE '   - 11 utilisateurs en liste d''attente (utilisateur10 à utilisateur20)';

END $$;