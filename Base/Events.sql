-- DONNÉES DE TEST POUR ORGANISATEUR11@YOPMAIL.COM
-- 27 événements avec éléments complets
-- 30-45 billets par catégorie de billet par événement
-- Respect strict du nombre d'utilisateurs disponibles
-- Pas de survente
-- Liste d'attente pour événements en cours et à venir (stock épuisé uniquement)
-- ============================================================

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
    i INTEGER;
    j INTEGER;
    k INTEGER;
    v_date_creation TIMESTAMPTZ;
    v_date_debut TIMESTAMPTZ;
    v_date_fin TIMESTAMPTZ;
    v_date_debut_vente TIMESTAMPTZ;
    v_date_fin_vente TIMESTAMPTZ;
    v_now TIMESTAMPTZ := NOW();
    v_prix NUMERIC(12,2);
    v_statut_billet TEXT;
    v_id_utilisateur BIGINT;
    v_id_segment_adulte BIGINT;
    v_id_segment_enfant BIGINT;
    v_id_cat_standard BIGINT;
    v_id_cat_vip BIGINT;
    v_id_cat_early_bird BIGINT;
    v_id_cat_acces_coulisses BIGINT;
    v_id_langue_fr BIGINT;
    v_id_langue_mg BIGINT;
    v_id_langue_en BIGINT;
    v_id_type_access_general BIGINT;
    v_id_type_access_hearing BIGINT;
    v_id_type_access_visual BIGINT;
    v_id_type_access_parking BIGINT;
    v_id_type_access_toilet BIGINT;
    v_id_type_access_pets BIGINT;
    v_id_session BIGINT;
    v_lieux_ids BIGINT[];
    v_espaces_ids BIGINT[];
    v_id_code_promo BIGINT;
    v_id_mode_paiement_mvola BIGINT;
    v_id_mode_paiement_orange BIGINT;
    v_id_mode_paiement_airtel BIGINT;
    v_id_mode_paiement_visa BIGINT;
    v_nb_historique_prix INTEGER;
    v_prix_precedent NUMERIC(12,2);
    v_nb_utilisateurs_user INTEGER;
    v_quantite_totale INTEGER;
    v_quantite_vendue INTEGER;
    v_billets_par_categorie INTEGER;
    v_event_statut TEXT;
    v_nb_participants INTEGER;
    v_nb_vues INTEGER;
    v_nb_favoris INTEGER;
    v_utilisateurs_ayant_achete BIGINT[];
    v_utilisateurs_ayant_vu BIGINT[];
    v_utilisateurs_favoris BIGINT[];
    v_random_user BIGINT;
    v_id_liste_souhaits BIGINT;
    v_billets_valid_a_creer INTEGER;
    v_multiplicateur INTEGER;
    v_vues_calculees INTEGER;
    v_vues_generees INTEGER;
    v_tentatives_vues INTEGER;
    v_vues_minimum INTEGER;
    v_participants_max INTEGER;
    v_participants_actuels INTEGER;
    v_capacite_lieu INTEGER;
BEGIN
    SET search_path TO aiolia;
    
    -- Récupérer le nombre total d'utilisateurs avec le rôle 'user'
    SELECT COUNT(*) INTO v_nb_utilisateurs_user
    FROM utilisateurs
    WHERE role = 'user';
    
    IF v_nb_utilisateurs_user IS NULL OR v_nb_utilisateurs_user = 0 THEN
        RAISE EXCEPTION 'Aucun utilisateur avec le rôle user trouvé. Veuillez exécuter data.sql avant Events.sql';
    END IF;

    -- Récupérer l'ID de l'utilisateur organisateur
    SELECT id INTO v_id_utilisateur_org 
    FROM utilisateurs 
    WHERE email = 'organisateur11@yopmail.com';

    IF v_id_utilisateur_org IS NULL THEN
        RAISE EXCEPTION 'Utilisateur organisateur11@yopmail.com non trouvé. Veuillez exécuter data.sql avant Events.sql';
    END IF;

    -- Récupérer le profil organisateur
    SELECT id INTO v_id_profil_org 
    FROM profils_organisateurs 
    WHERE id_utilisateur = v_id_utilisateur_org;

    IF v_id_profil_org IS NULL THEN
        INSERT INTO profils_organisateurs (
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
            NOW() - INTERVAL '90 days'
        ) RETURNING id INTO v_id_profil_org;
    END IF;

    -- Créer des catégories d'événements
    INSERT INTO categories_evenements (slug, libelle, description, nom_icone, ordre_affichage)
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
    INSERT INTO types_evenements (slug, libelle, description)
    VALUES 
        ('concert', 'Concert', 'Concert live'),
        ('festival', 'Festival', 'Festival multi-artistes'),
        ('conference', 'Conférence', 'Conférence professionnelle'),
        ('atelier', 'Atelier', 'Atelier pratique'),
        ('exposition', 'Exposition', 'Exposition artistique'),
        ('spectacle', 'Spectacle', 'Spectacle vivant'),
        ('projection', 'Projection', 'Projection cinéma')
    ON CONFLICT (slug) DO NOTHING;

    -- Créer les langues
    INSERT INTO langues (code, libelle, est_actif)
    VALUES 
        ('mg', 'Malagasy', TRUE),
        ('fr', 'Français', TRUE),
        ('en', 'Anglais', TRUE)
    ON CONFLICT (code) DO NOTHING;

    -- Créer les types d'accessibilité
    INSERT INTO types_accessibilite (code, libelle, url_image, ordre_affichage, est_actif)
    VALUES 
        ('general', 'Accessible tous publics', 'images/acces1.svg', 1, TRUE),
        ('hearing', 'Accessible aux malentendants', 'images/acces2.svg', 2, TRUE),
        ('visual', 'Accessible aux malvoyants', 'images/acces3.svg', 3, TRUE),
        ('pets', 'Animaux acceptés', 'images/acces5.svg', 4, TRUE),
        ('parking', 'Parking accessible', 'images/acces6.svg', 5, TRUE),
        ('toilet', 'WC accessibles', 'images/acces4.svg', 6, TRUE)
    ON CONFLICT (code) DO UPDATE SET url_image = EXCLUDED.url_image, ordre_affichage = EXCLUDED.ordre_affichage;

    -- Récupérer les IDs
    SELECT id INTO v_id_langue_fr FROM langues WHERE code = 'fr';
    SELECT id INTO v_id_langue_mg FROM langues WHERE code = 'mg';
    SELECT id INTO v_id_langue_en FROM langues WHERE code = 'en';
    SELECT id INTO v_id_type_access_general FROM types_accessibilite WHERE code = 'general';
    SELECT id INTO v_id_type_access_hearing FROM types_accessibilite WHERE code = 'hearing';
    SELECT id INTO v_id_type_access_visual FROM types_accessibilite WHERE code = 'visual';
    SELECT id INTO v_id_type_access_pets FROM types_accessibilite WHERE code = 'pets';
    SELECT id INTO v_id_type_access_parking FROM types_accessibilite WHERE code = 'parking';
    SELECT id INTO v_id_type_access_toilet FROM types_accessibilite WHERE code = 'toilet';

    -- Créer les modes de paiement
    INSERT INTO modes_paiement (code, libelle, description, est_actif, ordre_affichage)
    VALUES 
        ('mvola', 'MVola', 'Paiement mobile MVola', TRUE, 1),
        ('orange', 'Orange Money', 'Paiement mobile Orange Money', TRUE, 2),
        ('airtel', 'Airtel Money', 'Paiement mobile Airtel Money', TRUE, 3),
        ('espace', 'Espace', 'Paiement par espace', TRUE, 4)
    ON CONFLICT (code) DO NOTHING;

    SELECT id INTO v_id_mode_paiement_mvola FROM modes_paiement WHERE code = 'mvola';
    SELECT id INTO v_id_mode_paiement_orange FROM modes_paiement WHERE code = 'orange';
    SELECT id INTO v_id_mode_paiement_airtel FROM modes_paiement WHERE code = 'airtel';
    SELECT id INTO v_id_mode_paiement_visa FROM modes_paiement WHERE code = 'espace';

    -- Configuration des segments de billets
    INSERT INTO configuration_segments_billets (nom, age_min, age_max)
    VALUES 
        ('adulte', 18, NULL),
        ('enfant', 0, 12)
    ON CONFLICT (nom) DO NOTHING;

    SELECT id INTO v_id_segment_adulte FROM configuration_segments_billets WHERE nom = 'adulte';
    SELECT id INTO v_id_segment_enfant FROM configuration_segments_billets WHERE nom = 'enfant';

    -- Configuration des catégories de billets
    INSERT INTO configuration_categories_billets (nom, description)
    VALUES
        ('standard', 'Billet standard'),
        ('vip', 'Billet VIP avec avantages'),
        ('prevente', 'Billet Prevente'),
        ('acces_coulisses', 'Billet acces_coulisses')
    ON CONFLICT (nom) DO NOTHING;
    SELECT id INTO v_id_cat_standard FROM configuration_categories_billets WHERE nom = 'standard';
    SELECT id INTO v_id_cat_vip FROM configuration_categories_billets WHERE nom = 'vip';
    SELECT id INTO v_id_cat_early_bird FROM configuration_categories_billets WHERE nom = 'prevente';
    SELECT id INTO v_id_cat_acces_coulisses FROM configuration_categories_billets WHERE nom = 'acces_coulisses';

    -- ============================================================
    -- CRÉER 5 LIEUX DIFFÉRENTS AVEC COORDONNÉES GPS RÉALISTES
    -- ============================================================
    FOR i IN 1..5 LOOP
        -- Définir la capacité pour chaque lieu
        v_capacite_lieu := (ARRAY[800, 500, 2000, 300, 10000])[i];
        
        INSERT INTO lieux (
            id_profil_organisateur, nom, slug, description,
            ligne_adresse_1, ville, code_postal, code_pays,
            latitude, longitude, fuseau_horaire,
            email_contact, telephone_contact, capacite
        ) VALUES (
            v_id_profil_org,
            (ARRAY['Centre Culturel Ivandry', 'Salle des Fêtes Ankorondrano', 'Parc Tsimbazaza', 'Hôtel Carlton', 'Stade Mahamasina'])[i],
            'lieu-' || i || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
            'Lieu ' || i || ' - Description complète',
            (ARRAY['Rue de l''Independence, Ivandry', 'Avenue de l''Independence, Ankorondrano', 'Route de l''Université', 'Centre-ville', 'Route Circulaire'])[i],
            'Antananarivo',
            '101',
            'MG',
            (ARRAY[-18.8792, -18.9100, -18.9286, -18.9136, -18.9250])[i],
            (ARRAY[47.5079, 47.5200, 47.5314, 47.5250, 47.5400])[i],
            'Indian/Antananarivo',
            'contact@lieu' || i || '.mg',
            '+26134000000' || i,
            v_capacite_lieu
        ) RETURNING id INTO v_id_lieu;
        
        v_lieux_ids := array_append(v_lieux_ids, v_id_lieu);

        FOR j IN 1..2 LOOP
            INSERT INTO espaces_lieux (id_lieu, nom, description, capacite, est_par_defaut)
            VALUES (
                v_id_lieu,
                'Espace ' || j,
                'Espace ' || j || ' du lieu ' || i,
                v_capacite_lieu / 2,
                j = 1
            ) RETURNING id INTO v_id_espace;
            
            v_espaces_ids := array_append(v_espaces_ids, v_id_espace);
        END LOOP;
    END LOOP;

    -- ============================================================
    -- CRÉER 21 ÉVÉNEMENTS
    -- 5 passés (juillet-août 2025), 4 archivés (septembre-octobre 2025), 5 en cours (novembre-décembre 2025), 7 à venir (janvier-février 2026)
    -- ============================================================
    FOR i IN 1..21 LOOP
        -- Réinitialiser les tableaux pour chaque événement
        v_utilisateurs_ayant_achete := ARRAY[]::BIGINT[];
        v_utilisateurs_ayant_vu := ARRAY[]::BIGINT[];
        v_utilisateurs_favoris := ARRAY[]::BIGINT[];
        
        -- Initialiser la limite de participants (85% du nombre total d'utilisateurs)
        v_participants_max := floor(v_nb_utilisateurs_user * 0.85)::INTEGER;
        
        -- Définir les dates selon la catégorie (juillet 2025 - février 2026)
        IF i <= 5 THEN
            -- Événements passés (juillet-août 2025 - les plus anciens)
            v_date_debut := '2025-07-01'::TIMESTAMPTZ + (INTERVAL '10 days' * (i - 1));
            v_event_statut := 'published';
        ELSIF i <= 9 THEN
            -- Événements archivés (septembre-octobre 2025)
            v_date_debut := '2025-09-01'::TIMESTAMPTZ + (INTERVAL '8 days' * (i - 5));
            v_event_statut := 'archived';
        ELSIF i <= 14 THEN
            -- Événements en cours (novembre-décembre 2025)
            v_date_debut := '2025-11-01'::TIMESTAMPTZ + (INTERVAL '10 days' * (i - 9));
            v_event_statut := 'published';
        ELSE
            -- Événements à venir (janvier-février 2026)
            v_date_debut := '2026-01-01'::TIMESTAMPTZ + (INTERVAL '8 days' * (i - 14));
            v_event_statut := 'published';
        END IF;

        v_date_creation := GREATEST('2025-06-01'::TIMESTAMPTZ, v_date_debut - (INTERVAL '45 days'));
        v_date_fin := v_date_debut + INTERVAL '15 days';

        -- Dates de ventes : début 30 jours avant l'événement, fin 1 heure avant la fin de l'événement
        v_date_debut_vente := GREATEST('2025-06-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '30 days');
        v_date_fin_vente := GREATEST(v_date_debut_vente + INTERVAL '1 day', v_date_fin - INTERVAL '1 hour');

        SELECT id INTO v_id_categorie FROM categories_evenements ORDER BY RANDOM() LIMIT 1;
        SELECT id INTO v_id_type_event FROM types_evenements ORDER BY RANDOM() LIMIT 1;
        v_id_lieu := v_lieux_ids[1 + (i % 5)];
        
        -- Récupérer la capacité du lieu pour l'utiliser pour l'événement
        SELECT capacite INTO v_capacite_lieu FROM lieux WHERE id = v_id_lieu;
        
        SELECT id INTO v_id_espace FROM espaces_lieux WHERE id_lieu = v_id_lieu ORDER BY RANDOM() LIMIT 1;

        -- Créer l'événement avec la capacité du lieu
        INSERT INTO evenements (
            id_profil_organisateur, id_categorie_principale, id_type_evenement,
            id_lieu, id_espace_principal, slug, titre, sous_titre, resume, description,
            url_image_couverture, statut, visibilite, format_evenement,
            capacite, commence_le, se_termine_le,
            ventes_commencent_le, ventes_se_terminent_le,
            restriction_age, est_en_vedette, est_mis_en_avant, tarif_unique,
            cree_le, modifie_le
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
            v_event_statut::event_status_enum,
            'public'::event_visibility_enum,
            (CASE WHEN i % 3 = 0 THEN 'online' WHEN i % 3 = 1 THEN 'hybrid' ELSE 'in_person' END)::event_format_enum,
            v_capacite_lieu,
            v_date_debut,
            v_date_fin,
            v_date_debut_vente,
            v_date_fin_vente,
            CASE WHEN i % 4 = 0 THEN '18+' ELSE NULL END,
            i % 5 = 0,
            i % 7 = 0,
            FALSE,
            v_date_creation,
            v_date_creation
        ) RETURNING id INTO v_id_event;

        -- Ajouter des tags
        FOR j IN 1..3 LOOP
            INSERT INTO tags_evenements (slug, libelle)
            VALUES (
                'tag-' || i || '-' || j,
                (ARRAY['Premium', 'Exclusif', 'Tendance', 'Populaire', 'Familial', 'VIP', 'Unique'])[1 + ((i + j) % 7)]
            ) ON CONFLICT (slug) DO NOTHING;

            INSERT INTO liens_tags_evenements (id_evenement, id_tag)
            SELECT v_id_event, id FROM tags_evenements WHERE slug = 'tag-' || i || '-' || j
            ON CONFLICT DO NOTHING;
        END LOOP;

        -- Ajouter des médias
        INSERT INTO medias_evenements (id_evenement, type_media, url, texte_alternatif, ordre_affichage, est_affiche_principale)
        VALUES 
            (v_id_event, 'image', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200', 'Image principale', 0, TRUE),
            (v_id_event, 'image', 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=800', 'Image secondaire', 1, FALSE);

        -- Ajouter langues
        INSERT INTO liens_langues_evenements (id_evenement, id_langue)
        VALUES 
            (v_id_event, v_id_langue_fr),
            (v_id_event, v_id_langue_mg),
            (v_id_event, v_id_langue_en)
        ON CONFLICT DO NOTHING;

        -- Ajouter accessibilité
        INSERT INTO liens_accessibilite_evenements (id_evenement, id_type_accessibilite, description)
        VALUES 
            (v_id_event, v_id_type_access_general, 'Événement accessible à tous les publics'),
            (v_id_event, v_id_type_access_hearing, 'Boucle magnétique disponible pour malentendants'),
            (v_id_event, v_id_type_access_visual, 'Assistance et équipements pour malvoyants'),
            (v_id_event, v_id_type_access_parking, 'Parking accessible avec places réservées'),
            (v_id_event, v_id_type_access_toilet, 'WC accessibles et adaptés')
        ON CONFLICT DO NOTHING;

        IF i % 2 = 0 THEN
            INSERT INTO liens_accessibilite_evenements (id_evenement, id_type_accessibilite, description)
            VALUES (v_id_event, v_id_type_access_pets, 'Animaux de compagnie acceptés')
            ON CONFLICT DO NOTHING;
        END IF;

        -- ============================================================
        -- CRÉER LES BILLETS PAR CATÉGORIE ET SEGMENT
        -- ============================================================
        FOR j IN 1..4 LOOP
            DECLARE
                v_id_cat_billet BIGINT := CASE 
                    WHEN j = 1 THEN v_id_cat_standard
                    WHEN j = 2 THEN v_id_cat_early_bird
                    WHEN j = 3 THEN v_id_cat_vip
                    ELSE v_id_cat_acces_coulisses
                END;
                v_prix_base NUMERIC(12,2);
                v_nom_categorie TEXT := CASE 
                    WHEN j = 1 THEN 'Standard'
                    WHEN j = 2 THEN 'Prevente'
                    WHEN j = 3 THEN 'VIP'
                    ELSE 'Acces coulisses'
                END;
                v_total_categorie INTEGER;
                v_billets_par_segment INTEGER;
            BEGIN
                -- Nombre total de billets pour cette catégorie
                v_total_categorie := 20 + floor(random() * 11)::INTEGER; -- 20 à 30
                v_billets_par_segment := v_total_categorie / 2;
                
                FOR segment_idx IN 1..2 LOOP
                    DECLARE
                        v_id_seg_billet BIGINT;
                        v_segment_nom TEXT;
                    BEGIN
                        v_prix_base := CASE 
                            WHEN j = 1 THEN 12000 + floor(random() * 6000)::INTEGER::NUMERIC
                            WHEN j = 2 THEN 20000 + floor(random() * 10000)::INTEGER::NUMERIC
                            WHEN j = 3 THEN 50000 + floor(random() * 20000)::INTEGER::NUMERIC
                            ELSE 80000 + floor(random() * 40000)::INTEGER::NUMERIC
                        END;
                        v_prix_base := round(v_prix_base / 100) * 100;

                        IF segment_idx = 1 THEN
                            v_id_seg_billet := v_id_segment_adulte;
                            v_segment_nom := '';
                            v_prix := v_prix_base;
                        ELSE
                            v_id_seg_billet := v_id_segment_enfant;
                            v_segment_nom := ' Enfant';
                            v_prix := round((v_prix_base * 0.5) / 100) * 100;
                        END IF;
                        
                        v_billets_par_categorie := v_billets_par_segment;
                        
                        -- Créer le type de billet
                        INSERT INTO types_billets (
                            id_evenement, id_configuration_categorie, id_configuration_segment,
                            nom, description, devise, prix_de_base,
                            frais_service, taux_tva,
                            ventes_commencent_le, ventes_se_terminent_le,
                            minimum_par_commande, maximum_par_commande,
                            cree_le, modifie_le
                        ) VALUES (
                            v_id_event,
                            v_id_cat_billet,
                            v_id_seg_billet,
                            v_nom_categorie || v_segment_nom,
                            'Billet ' || v_nom_categorie || v_segment_nom,
                            'MGA',
                            v_prix,
                            v_prix * 0.1,
                            20.0,
                            v_date_debut_vente,
                            v_date_fin_vente,
                            1,
                            CASE WHEN j >= 3 THEN 4 ELSE 10 END,
                            v_date_creation,
                            v_date_creation
                        ) RETURNING id INTO v_id_type_billet;
                        
                        -- Définir quantité vendue selon le statut de l'événement
                        IF v_event_statut = 'archived' THEN
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.7 + random() * 0.2))::INTEGER;
                        ELSIF v_event_statut = 'published' AND v_date_debut < NOW() THEN
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.6 + random() * 0.25))::INTEGER;
                        ELSIF v_event_statut = 'published' AND v_date_debut >= NOW() AND v_date_debut <= NOW() + INTERVAL '90 days' THEN
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.3 + random() * 0.3))::INTEGER;
                        ELSE
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.1 + random() * 0.2))::INTEGER;
                        END IF;

                        -- Forcer 4 à 8 billets disponibles par catégorie/segment
                        DECLARE
                            v_quantite_dispo_cible INTEGER;
                        BEGIN
                            v_quantite_dispo_cible := 4 + floor(random() * 5)::INTEGER;
                            IF v_quantite_dispo_cible > v_billets_par_categorie THEN
                                v_quantite_dispo_cible := LEAST(v_billets_par_categorie, 4);
                            END IF;
                            v_quantite_vendue := GREATEST(0, v_billets_par_categorie - v_quantite_dispo_cible);
                        END;

                        INSERT INTO inventaire_billets (id_type_billet, quantite_totale, quantite_reservee, quantite_vendue)
                        VALUES (
                            v_id_type_billet,
                            v_billets_par_categorie,
                            CASE WHEN v_event_statut = 'published' THEN LEAST(5, v_billets_par_categorie - v_quantite_vendue) ELSE 0 END,
                            v_quantite_vendue
                        );

                        -- Ajouter historiques de prix
                        v_nb_historique_prix := floor(random() * 5)::INTEGER;
                        v_prix_precedent := v_prix;
                        
                        FOR k IN 1..v_nb_historique_prix LOOP
                            v_prix_precedent := v_prix_precedent * (0.85 + (random() * 0.2));
                            v_prix_precedent := round(v_prix_precedent / 100) * 100;
                            
                            INSERT INTO historique_prix_billets (
                                id_type_billet, modifie_par, prix_precedent, nouveau_prix, 
                                raison, modifie_le
                            ) VALUES (
                                v_id_type_billet,
                                v_id_utilisateur_org,
                                v_prix_precedent,
                                CASE WHEN k = v_nb_historique_prix THEN v_prix ELSE round((v_prix_precedent * 1.1) / 100) * 100 END,
                                (ARRAY['Ajustement initial', 'Promotion temporaire', 'Ajustement marché', 'Correction tarifaire'])[1 + (k % 4)],
                                v_date_creation + (INTERVAL '1 day' * k)
                            );
                        END LOOP;

                        -- ============================================================
                        -- CRÉER LES BILLETS VENDUS
                        -- (Sauter pour événements en cours et à venir - géré par la nouvelle logique)
                        -- ============================================================
                        -- Ne créer les billets que pour les événements passés et archivés
                        IF NOT ((v_event_statut = 'published' AND v_date_debut < NOW() AND v_date_debut >= NOW() - INTERVAL '15 days') 
                               OR (v_event_statut = 'published' AND v_date_debut >= NOW())) THEN
                        v_participants_max := floor(v_nb_utilisateurs_user * 0.85)::INTEGER;
                        v_participants_actuels := COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0);
                        
                        DECLARE
                            v_nb_participants_categorie INTEGER;
                            v_billets_restants INTEGER;
                            v_index_participant INTEGER;
                            v_compteur_billets INTEGER;
                        BEGIN
                            v_nb_participants_categorie := LEAST(
                                v_quantite_vendue,
                                GREATEST(1, v_participants_max - v_participants_actuels)
                            );
                            
                            v_billets_restants := v_quantite_vendue;
                            v_compteur_billets := 0;
                            
                            -- Créer les participants uniques
                            FOR k IN 1..v_nb_participants_categorie LOOP
                                IF v_billets_restants <= 0 THEN
                                    EXIT;
                                END IF;
                                
                                SELECT id INTO v_id_utilisateur 
                                FROM utilisateurs 
                                WHERE role = 'user'
                                    AND NOT (id = ANY(v_utilisateurs_ayant_achete))
                                ORDER BY RANDOM() 
                                LIMIT 1;
                                
                                IF v_id_utilisateur IS NULL THEN
                                    IF array_length(v_utilisateurs_ayant_achete, 1) > 0 THEN
                                        v_index_participant := 1 + floor(random() * array_length(v_utilisateurs_ayant_achete, 1))::INTEGER;
                                        v_id_utilisateur := v_utilisateurs_ayant_achete[v_index_participant];
                                    ELSE
                                        EXIT;
                                    END IF;
                                ELSE
                                    v_participants_actuels := COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0);
                                    IF v_participants_actuels < v_participants_max THEN
                                        v_utilisateurs_ayant_achete := array_append(v_utilisateurs_ayant_achete, v_id_utilisateur);
                                    END IF;
                                END IF;
                                
                                v_compteur_billets := v_compteur_billets + 1;
                                v_billets_restants := v_billets_restants - 1;
                                
                                -- Déterminer le statut du billet selon le type d'événement
                                IF v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()) THEN
                                    -- Événements passés ou archivés : billets utilisés
                                    v_statut_billet := 'used';
                                ELSE
                                    -- Événements en cours ou à venir : billets valides
                                    v_statut_billet := 'valid';
                                END IF;

                                -- Créer le billet avec transaction
                                DECLARE
                                    v_date_achat TIMESTAMPTZ;
                                    v_duree_vente INTERVAL;
                                BEGIN
                                    v_duree_vente := v_date_fin_vente - v_date_debut_vente;
                                    v_date_achat := v_date_debut_vente + (v_duree_vente * random());
                                    
                                    INSERT INTO paniers (id_utilisateur, statut, devise, montant_total, expire_le, cree_le)
                                    VALUES (v_id_utilisateur, 'converted'::cart_status_enum, 'MGA', 0, v_date_fin_vente, v_date_achat)
                                    RETURNING id INTO v_id_panier;

                                    INSERT INTO commandes (id_utilisateur, id_panier, statut, montant_total, devise, cree_le)
                                    VALUES (v_id_utilisateur, v_id_panier, 'paid'::order_status_enum, v_prix, 'MGA', v_date_achat)
                                    RETURNING id INTO v_id_commande;

                                    INSERT INTO elements_commandes (id_commande, id_type_billet, quantite, prix_unitaire, frais_service, montant_tva, montant_total)
                                    VALUES (v_id_commande, v_id_type_billet, 1, v_prix, v_prix * 0.1, v_prix * 0.2, v_prix * 1.3)
                                    RETURNING id INTO v_id_element_commande;

                                    -- Vérifier s'il y a encore des billets disponibles avant de créer le billet avec QR code
                                    -- Pour les événements en cours ou à venir uniquement
                                    DECLARE
                                        v_billets_disponibles INTEGER;
                                    BEGIN
                                        -- Récupérer le nombre de billets disponibles dans l'inventaire
                                        SELECT (quantite_totale - quantite_vendue - quantite_reservee) INTO v_billets_disponibles
                                        FROM inventaire_billets
                                        WHERE id_type_billet = v_id_type_billet;
                                        
                                        -- Pour les événements en cours ou à venir : créer le billet avec QR code seulement s'il y a des billets disponibles
                                        -- Pour les événements passés/archivés : créer toujours le billet
                                        IF (v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()))
                                           OR (v_event_statut = 'published' AND v_date_debut >= NOW() AND v_billets_disponibles > 0) THEN
                                            -- Créer le billet avec QR code et tous les détails
                                            DECLARE
                                                v_code_qr_unique TEXT;
                                                v_checksum_qr_unique TEXT;
                                            BEGIN
                                                v_code_qr_unique := 'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                                v_checksum_qr_unique := md5(v_code_qr_unique);
                                                
                                                INSERT INTO billets (
                                                    id_element_commande, id_type_billet, id_utilisateur_proprietaire, 
                                                    statut, code_qr, checksum_qr, emis_le, metadonnees
                                                ) VALUES (
                                                    v_id_element_commande, 
                                                    v_id_type_billet, 
                                                    v_id_utilisateur, 
                                                    v_statut_billet::ticket_status_enum, 
                                                    v_code_qr_unique,
                                                    v_checksum_qr_unique,
                                                    v_date_achat,
                                                    jsonb_build_object(
                                                        'evenement_id', v_id_event,
                                                        'categorie', j,
                                                        'segment', segment_idx,
                                                        'numero_billet', v_compteur_billets,
                                                        'statut', v_statut_billet,
                                                        'date_emission', v_date_achat::TEXT
                                                    )
                                                );
                                            END;
                                            
                                            -- Mettre à jour l'inventaire (incrémenter quantite_vendue)
                                            UPDATE inventaire_billets
                                            SET quantite_vendue = quantite_vendue + 1
                                            WHERE id_type_billet = v_id_type_billet;
                                            
                                            -- Créer la facture pour ce billet
                                            INSERT INTO factures_billets (id_commande, id_client, id_mode_paiement, devise, montant_sous_total, montant_tva, montant_total, montant_ht, montant_tva_detail, montant_ttc, statut, emise_le, payee_le)
                                            VALUES (
                                                v_id_commande, 
                                                v_id_utilisateur, 
                                                (ARRAY[v_id_mode_paiement_mvola, v_id_mode_paiement_orange, v_id_mode_paiement_airtel, v_id_mode_paiement_visa])[1 + (v_compteur_billets % 4)], 
                                                'MGA', 
                                                v_prix, 
                                                0, 
                                                v_prix, 
                                                v_prix, 
                                                0, 
                                                v_prix, 
                                                'paid', 
                                                v_date_achat, 
                                                v_date_achat + INTERVAL '2 hours'
                                            )
                                            RETURNING id INTO v_id_facture;

                                            INSERT INTO historique_paiements_billets (id_facture, statut_de, statut_vers, modifie_le, metadonnees)
                                            VALUES (
                                                v_id_facture, 
                                                NULL, 
                                                'paid'::payment_status_enum, 
                                                v_date_achat + INTERVAL '2 hours',
                                                jsonb_build_object(
                                                    'reference', 'REF-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                                                    'montant', v_prix * 1.3,
                                                    'devise', 'MGA'
                                                )
                                            );
                                        ELSE
                                            -- Pour les événements en cours/à venir sans billets disponibles : ne pas créer de billet avec QR code
                                            -- La commande existe mais pas de billet physique (sera géré par liste d'attente)
                                            RAISE NOTICE 'Billet non créé pour événement % - catégorie % - segment % : stock épuisé (disponible: %)', 
                                                v_id_event, j, segment_idx, v_billets_disponibles;
                                        END IF;
                                    END;
                                END;
                            END LOOP;
                            
                            -- Distribuer les billets restants parmi les participants existants
                            WHILE v_billets_restants > 0 AND array_length(v_utilisateurs_ayant_achete, 1) > 0 LOOP
                                v_index_participant := 1 + floor(random() * array_length(v_utilisateurs_ayant_achete, 1))::INTEGER;
                                v_id_utilisateur := v_utilisateurs_ayant_achete[v_index_participant];
                                
                                v_compteur_billets := v_compteur_billets + 1;
                                v_billets_restants := v_billets_restants - 1;
                                
                                -- Déterminer le statut du billet selon le type d'événement
                                IF v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()) THEN
                                    -- Événements passés ou archivés : billets utilisés
                                    v_statut_billet := 'used';
                                ELSE
                                    -- Événements en cours ou à venir : billets valides
                                    v_statut_billet := 'valid';
                                END IF;

                                DECLARE
                                    v_date_achat_supp TIMESTAMPTZ;
                                    v_duree_vente_supp INTERVAL;
                                BEGIN
                                    v_duree_vente_supp := v_date_fin_vente - v_date_debut_vente;
                                    v_date_achat_supp := v_date_debut_vente + (v_duree_vente_supp * random());
                                    
                                    INSERT INTO paniers (id_utilisateur, statut, devise, montant_total, expire_le, cree_le)
                                    VALUES (v_id_utilisateur, 'converted'::cart_status_enum, 'MGA', 0, v_date_fin_vente, v_date_achat_supp)
                                    RETURNING id INTO v_id_panier;

                                    INSERT INTO commandes (id_utilisateur, id_panier, statut, montant_total, devise, cree_le)
                                    VALUES (v_id_utilisateur, v_id_panier, 'paid'::order_status_enum, v_prix, 'MGA', v_date_achat_supp)
                                    RETURNING id INTO v_id_commande;

                                    INSERT INTO elements_commandes (id_commande, id_type_billet, quantite, prix_unitaire, frais_service, montant_tva, montant_total)
                                    VALUES (v_id_commande, v_id_type_billet, 1, v_prix, v_prix * 0.1, v_prix * 0.2, v_prix * 1.3)
                                    RETURNING id INTO v_id_element_commande;

                                    -- Vérifier s'il y a encore des billets disponibles avant de créer le billet avec QR code
                                    -- Pour les événements en cours ou à venir uniquement
                                    DECLARE
                                        v_billets_disponibles_supp INTEGER;
                                    BEGIN
                                        -- Récupérer le nombre de billets disponibles dans l'inventaire
                                        SELECT (quantite_totale - quantite_vendue - quantite_reservee) INTO v_billets_disponibles_supp
                                        FROM inventaire_billets
                                        WHERE id_type_billet = v_id_type_billet;
                                        
                                        -- Pour les événements en cours ou à venir : créer le billet avec QR code seulement s'il y a des billets disponibles
                                        -- Pour les événements passés/archivés : créer toujours le billet
                                        IF (v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()))
                                           OR (v_event_statut = 'published' AND v_date_debut >= NOW() AND v_billets_disponibles_supp > 0) THEN
                                            -- Créer le billet avec QR code et tous les détails
                                            DECLARE
                                                v_code_qr_unique_supp TEXT;
                                                v_checksum_qr_unique_supp TEXT;
                                            BEGIN
                                                v_code_qr_unique_supp := 'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                                v_checksum_qr_unique_supp := md5(v_code_qr_unique_supp);
                                                
                                                INSERT INTO billets (
                                                    id_element_commande, id_type_billet, id_utilisateur_proprietaire, 
                                                    statut, code_qr, checksum_qr, emis_le, metadonnees
                                                ) VALUES (
                                                    v_id_element_commande, 
                                                    v_id_type_billet, 
                                                    v_id_utilisateur, 
                                                    v_statut_billet::ticket_status_enum, 
                                                    v_code_qr_unique_supp,
                                                    v_checksum_qr_unique_supp,
                                                    v_date_achat_supp,
                                                    jsonb_build_object(
                                                        'evenement_id', v_id_event,
                                                        'categorie', j,
                                                        'segment', segment_idx,
                                                        'numero_billet', v_compteur_billets,
                                                        'statut', v_statut_billet,
                                                        'date_emission', v_date_achat_supp::TEXT
                                                    )
                                                );
                                            END;
                                            
                                            -- Mettre à jour l'inventaire (incrémenter quantite_vendue)
                                            UPDATE inventaire_billets
                                            SET quantite_vendue = quantite_vendue + 1
                                            WHERE id_type_billet = v_id_type_billet;
                                            
                                            -- Créer la facture pour ce billet
                                        INSERT INTO factures_billets (id_commande, id_client, id_mode_paiement, devise, montant_sous_total, montant_tva, montant_total, montant_ht, montant_tva_detail, montant_ttc, statut, emise_le, payee_le)
                                        VALUES (
                                            v_id_commande, 
                                            v_id_utilisateur, 
                                            (ARRAY[v_id_mode_paiement_mvola, v_id_mode_paiement_orange, v_id_mode_paiement_airtel, v_id_mode_paiement_visa])[1 + (v_compteur_billets % 4)], 
                                            'MGA', 
                                            v_prix, 
                                            0, 
                                            v_prix, 
                                            v_prix, 
                                            0, 
                                            v_prix, 
                                            'paid', 
                                            v_date_achat_supp, 
                                            v_date_achat_supp + INTERVAL '2 hours'
                                        )
                                        RETURNING id INTO v_id_facture;

                                            INSERT INTO historique_paiements_billets (id_facture, statut_de, statut_vers, modifie_le, metadonnees)
                                            VALUES (
                                                v_id_facture, 
                                                NULL, 
                                                'paid'::payment_status_enum, 
                                                v_date_achat_supp + INTERVAL '2 hours',
                                                jsonb_build_object(
                                                    'reference', 'REF-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                                                    'montant', v_prix * 1.3,
                                                    'devise', 'MGA'
                                                )
                                            );
                                        ELSE
                                            -- Pour les événements en cours/à venir sans billets disponibles : ne pas créer de billet avec QR code
                                            -- La commande existe mais pas de billet physique
                                            RAISE NOTICE 'Billet non créé pour événement % - catégorie % - segment % : stock épuisé (disponible: %)', 
                                                v_id_event, j, segment_idx, v_billets_disponibles_supp;
                                        END IF;
                                    END;
                                END;
                            END LOOP;
                        END;
                        
                        -- ============================================================
                        -- CRÉER DES BILLETS RESTANTS (disponibles)
                        -- ============================================================
                        IF v_quantite_vendue < v_billets_par_categorie THEN
                            DECLARE
                                v_billets_restants INTEGER;
                                v_billets_dispo_a_creer INTEGER;
                                v_billets_valid_a_creer INTEGER;
                            BEGIN
                                v_billets_restants := v_billets_par_categorie - v_quantite_vendue;
                                
                                IF v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()) THEN
                                    v_billets_valid_a_creer := LEAST(
                                        floor(v_billets_restants * (0.05 + random() * 0.15))::INTEGER,
                                        v_billets_restants,
                                        v_participants_max - COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0)
                                    );
                                    v_billets_dispo_a_creer := v_billets_restants - v_billets_valid_a_creer;
                                ELSE
                                    v_billets_valid_a_creer := 0;
                                    v_billets_dispo_a_creer := v_billets_restants;
                                END IF;
                                
                                -- Créer les billets 'valid' (achetés mais non utilisés) pour événements passés
                                IF v_billets_valid_a_creer > 0 THEN
                                    FOR k IN 1..v_billets_valid_a_creer LOOP
                                        SELECT id INTO v_id_utilisateur 
                                        FROM utilisateurs 
                                        WHERE role = 'user'
                                        ORDER BY RANDOM() 
                                        LIMIT 1;
                                        
                                        IF v_id_utilisateur IS NOT NULL THEN
                                            v_participants_actuels := COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0);
                                            IF NOT (v_id_utilisateur = ANY(v_utilisateurs_ayant_achete)) AND v_participants_actuels < v_participants_max THEN
                                                v_utilisateurs_ayant_achete := array_append(v_utilisateurs_ayant_achete, v_id_utilisateur);
                                            END IF;
                                            
                                            DECLARE
                                                v_date_achat_valid_final TIMESTAMPTZ;
                                                v_duree_vente_valid_final INTERVAL;
                                            BEGIN
                                                v_duree_vente_valid_final := v_date_fin_vente - v_date_debut_vente;
                                                v_date_achat_valid_final := v_date_debut_vente + (v_duree_vente_valid_final * random());
                                                
                                                INSERT INTO paniers (
                                                    id_utilisateur, statut, devise, montant_total, expire_le, cree_le
                                                ) VALUES (
                                                    v_id_utilisateur,
                                                    'converted'::cart_status_enum,
                                                    'MGA',
                                                    0,
                                                    v_date_fin_vente,
                                                    v_date_achat_valid_final
                                                ) RETURNING id INTO v_id_panier;

                                                INSERT INTO commandes (
                                                    id_utilisateur, id_panier, statut, montant_total, devise, cree_le
                                                ) VALUES (
                                                    v_id_utilisateur,
                                                    v_id_panier,
                                                    'paid'::order_status_enum,
                                                    v_prix,
                                                    'MGA',
                                                    v_date_achat_valid_final
                                                ) RETURNING id INTO v_id_commande;

                                                INSERT INTO elements_commandes (
                                                    id_commande, id_type_billet, quantite,
                                                    prix_unitaire, frais_service, montant_tva, montant_total
                                                ) VALUES (
                                                    v_id_commande,
                                                    v_id_type_billet,
                                                    1,
                                                    v_prix,
                                                    v_prix * 0.1,
                                                    v_prix * 0.2,
                                                    v_prix * 1.3
                                                ) RETURNING id INTO v_id_element_commande;

                                                -- Créer le billet avec tous les détails
                                                DECLARE
                                                    v_code_qr_valid TEXT;
                                                    v_checksum_qr_valid TEXT;
                                                BEGIN
                                                    v_code_qr_valid := 'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-V' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                                    v_checksum_qr_valid := md5(v_code_qr_valid);
                                                    
                                                    INSERT INTO billets (
                                                        id_element_commande, id_type_billet,
                                                        id_utilisateur_proprietaire, statut,
                                                        code_qr, checksum_qr, emis_le, metadonnees
                                                    ) VALUES (
                                                        v_id_element_commande,
                                                        v_id_type_billet,
                                                        v_id_utilisateur,
                                                        'valid'::ticket_status_enum,
                                                        v_code_qr_valid,
                                                        v_checksum_qr_valid,
                                                        v_date_achat_valid_final,
                                                        jsonb_build_object(
                                                            'evenement_id', v_id_event,
                                                            'categorie', j,
                                                            'segment', segment_idx,
                                                            'numero_billet', k,
                                                            'statut', 'valid',
                                                            'date_emission', v_date_achat_valid_final::TEXT
                                                        )
                                                    );
                                                END;
                                                
                                                -- Mettre à jour l'inventaire (incrémenter quantite_vendue)
                                                UPDATE inventaire_billets
                                                SET quantite_vendue = quantite_vendue + 1
                                                WHERE id_type_billet = v_id_type_billet;

                                                INSERT INTO factures_billets (
                                                    id_commande, id_client, id_mode_paiement, devise,
                                                    montant_sous_total, montant_tva, montant_total,
                                                    montant_ht, montant_tva_detail, montant_ttc,
                                                    statut, emise_le, payee_le
                                                ) VALUES (
                                                    v_id_commande,
                                                    v_id_utilisateur,
                                                    (ARRAY[v_id_mode_paiement_mvola, v_id_mode_paiement_orange, v_id_mode_paiement_airtel, v_id_mode_paiement_visa])[1 + (k % 4)],
                                                    'MGA',
                                                    v_prix,
                                                    0, 
                                                    v_prix, 
                                                    v_prix, 
                                                    0, 
                                                    v_prix, 
                                                    'paid',
                                                    v_date_achat_valid_final,
                                                    v_date_achat_valid_final + INTERVAL '2 hours'
                                                ) RETURNING id INTO v_id_facture;

                                                INSERT INTO historique_paiements_billets (
                                                    id_facture, statut_de, statut_vers, modifie_le, metadonnees
                                                ) VALUES (
                                                    v_id_facture,
                                                    NULL,
                                                    'paid'::payment_status_enum,
                                                    v_date_achat_valid_final + INTERVAL '2 hours',
                                                    jsonb_build_object(
                                                        'reference', 'REF-' || v_id_event || '-' || j || '-' || segment_idx || '-V' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                                                        'montant', v_prix * 1.3,
                                                        'devise', 'MGA'
                                                    )
                                                );
                                            END;
                                        END IF;
                                    END LOOP;
                                END IF;
                            END;
                        END IF;
                    END IF; -- Fin de la condition pour événements passés/archivés uniquement
                        
                        -- ============================================================
                        -- CRÉER TOUS LES BILLETS 'DISPO' POUR CE TYPE DE BILLET
                        -- Basé sur l'inventaire réel : quantite_totale - quantite_vendue - quantite_reservee
                        -- S'applique à TOUS les événements et TOUS les types de billets
                        -- ============================================================
                        DECLARE
                            v_billets_dispo_restants INTEGER;
                            v_billets_dispo_existants INTEGER;
                        BEGIN
                            -- Vérifier le nombre de billets disponibles dans l'inventaire
                            SELECT (quantite_totale - quantite_vendue - quantite_reservee) INTO v_billets_dispo_restants
                            FROM inventaire_billets
                            WHERE id_type_billet = v_id_type_billet;
                            
                            -- Compter les billets 'dispo' déjà créés pour ce type de billet
                            SELECT COUNT(*) INTO v_billets_dispo_existants
                            FROM billets
                            WHERE id_type_billet = v_id_type_billet
                                AND statut = 'dispo'
                                AND id_utilisateur_proprietaire IS NULL;
                            
                            -- Calculer le nombre de billets 'dispo' à créer
                            v_billets_dispo_restants := GREATEST(0, v_billets_dispo_restants - COALESCE(v_billets_dispo_existants, 0));
                            
                            -- Créer exactement le nombre de billets disponibles selon l'inventaire avec tous les détails
                            IF v_billets_dispo_restants > 0 THEN
                                FOR k IN 1..v_billets_dispo_restants LOOP
                                    DECLARE
                                        v_code_qr_dispo TEXT;
                                        v_checksum_qr_dispo TEXT;
                                    BEGIN
                                        v_code_qr_dispo := 'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-D' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                        v_checksum_qr_dispo := md5(v_code_qr_dispo);
                                        
                                        INSERT INTO billets (
                                            id_element_commande, id_type_billet,
                                            id_utilisateur_proprietaire, statut,
                                            code_qr, checksum_qr, emis_le, metadonnees
                                        ) VALUES (
                                            NULL,
                                            v_id_type_billet,
                                            NULL,
                                            'dispo'::ticket_status_enum,
                                            v_code_qr_dispo,
                                            v_checksum_qr_dispo,
                                            v_date_creation,
                                            jsonb_build_object(
                                                'evenement_id', v_id_event,
                                                'categorie', j,
                                                'segment', segment_idx,
                                                'numero_billet', k,
                                                'statut', 'dispo',
                                                'date_emission', v_date_creation::TEXT
                                            )
                                        );
                                    END;
                                END LOOP;
                                
                                RAISE NOTICE 'Billets "dispo" créés pour événement % - catégorie % - segment % : % billets', 
                                    v_id_event, j, segment_idx, v_billets_dispo_restants;
                            END IF;
                        END;
                    END;
                END LOOP;
            END;
        END LOOP;

        -- ============================================================
        -- CRÉER LES BILLETS POUR ÉVÉNEMENTS EN COURS ET À VENIR
        -- 50-80 billets avec tous les détails (QR, checksum, métadonnées)
        -- ============================================================
        IF (v_event_statut = 'published' AND v_date_debut < NOW() AND v_date_debut >= NOW() - INTERVAL '15 days') 
           OR (v_event_statut = 'published' AND v_date_debut >= NOW()) THEN
            DECLARE
                v_nb_billets_total INTEGER;
                v_nb_billets_valid INTEGER;
                v_nb_billets_used INTEGER;
                v_nb_billets_dispo INTEGER;
                v_nb_types_billets INTEGER;
                v_type_billet_cursor CURSOR FOR
                    SELECT id, nom, id_configuration_categorie, id_configuration_segment
                    FROM types_billets
                    WHERE id_evenement = v_id_event
                    ORDER BY id_configuration_categorie, id_configuration_segment;
                v_current_type_billet_id BIGINT;
                v_current_type_billet_nom TEXT;
                v_current_cat_id BIGINT;
                v_current_seg_id BIGINT;
                v_billets_par_type INTEGER;
                v_compteur_billet INTEGER := 0;
            BEGIN
                -- Nombre total de billets pour cet événement (50-80)
                v_nb_billets_total := 50 + floor(random() * 31)::INTEGER; -- 50 à 80
                
                -- Compter le nombre de types de billets
                SELECT COUNT(*) INTO v_nb_types_billets
                FROM types_billets
                WHERE id_evenement = v_id_event;
                
                IF v_nb_types_billets > 0 THEN
                    v_billets_par_type := GREATEST(1, v_nb_billets_total / v_nb_types_billets);
                ELSE
                    v_billets_par_type := 0;
                END IF;
                
                -- Répartition selon le type d'événement
                IF v_date_debut < NOW() THEN
                    -- Événement en cours : valid, used, dispo
                    v_nb_billets_valid := floor(v_nb_billets_total * (0.3 + random() * 0.2))::INTEGER; -- 30-50%
                    v_nb_billets_used := floor(v_nb_billets_total * (0.2 + random() * 0.2))::INTEGER; -- 20-40%
                    v_nb_billets_dispo := v_nb_billets_total - v_nb_billets_valid - v_nb_billets_used;
                ELSE
                    -- Événement à venir : valid, dispo (pas de used)
                    v_nb_billets_valid := floor(v_nb_billets_total * (0.2 + random() * 0.3))::INTEGER; -- 20-50%
                    v_nb_billets_used := 0;
                    v_nb_billets_dispo := v_nb_billets_total - v_nb_billets_valid;
                END IF;
                
                -- Parcourir tous les types de billets et créer les billets
                OPEN v_type_billet_cursor;
                LOOP
                    FETCH v_type_billet_cursor INTO v_current_type_billet_id, v_current_type_billet_nom, 
                                                     v_current_cat_id, v_current_seg_id;
                    EXIT WHEN NOT FOUND;
                    
                    -- Calculer le nombre de billets à créer pour ce type
                    DECLARE
                        v_nb_billets_type INTEGER;
                        v_nb_valid_type INTEGER;
                        v_nb_used_type INTEGER;
                        v_nb_dispo_type INTEGER;
                        v_quantite_totale_type INTEGER;
                        v_quantite_vendue_type INTEGER;
                    BEGIN
                        -- Répartir les billets entre les types de manière équitable
                        v_nb_billets_type := GREATEST(1, floor(v_nb_billets_total / v_nb_types_billets)::INTEGER);
                        
                        -- Calculer la répartition pour ce type
                        IF v_date_debut < NOW() THEN
                            -- Événement en cours
                            -- Pour la catégorie VIP, épuiser complètement (100% vendu) pour créer des listes d'attente
                            -- Pour les autres catégories, garder un mixte
                            IF v_current_cat_id = (SELECT id FROM configuration_categories_billets WHERE nom = 'vip' LIMIT 1) THEN
                                -- Catégorie VIP : 100% vendu (50% valid, 50% used, 0% dispo)
                                v_nb_valid_type := floor(v_nb_billets_type * 0.5)::INTEGER;
                                v_nb_used_type := v_nb_billets_type - v_nb_valid_type;
                                v_nb_dispo_type := 0;
                            ELSE
                                -- Autres catégories : mixte normal
                                v_nb_valid_type := floor(v_nb_billets_type * (0.3 + random() * 0.2))::INTEGER;
                                v_nb_used_type := floor(v_nb_billets_type * (0.2 + random() * 0.2))::INTEGER;
                                v_nb_dispo_type := v_nb_billets_type - v_nb_valid_type - v_nb_used_type;
                            END IF;
                        ELSE
                            -- Événement à venir
                            v_nb_valid_type := floor(v_nb_billets_type * (0.2 + random() * 0.3))::INTEGER;
                            v_nb_used_type := 0;
                            v_nb_dispo_type := v_nb_billets_type - v_nb_valid_type;
                        END IF;
                        
                        -- Mettre à jour l'inventaire
                        v_quantite_totale_type := v_nb_billets_type;
                        v_quantite_vendue_type := v_nb_valid_type + v_nb_used_type;
                        
                        -- S'assurer que quantite_vendue ne dépasse jamais quantite_totale
                        IF v_quantite_vendue_type > v_quantite_totale_type THEN
                            v_quantite_vendue_type := v_quantite_totale_type;
                            -- Ajuster v_nb_dispo_type en conséquence
                            v_nb_dispo_type := 0;
                        END IF;
                        
                        UPDATE inventaire_billets
                        SET quantite_totale = v_quantite_totale_type,
                            quantite_vendue = v_quantite_vendue_type,
                            quantite_reservee = 0
                        WHERE id_type_billet = v_current_type_billet_id;
                            
                            -- Créer les billets 'valid'
                            FOR k IN 1..v_nb_valid_type LOOP
                                IF v_compteur_billet >= v_nb_billets_valid THEN
                                    EXIT;
                                END IF;
                                
                                SELECT id INTO v_id_utilisateur 
                                FROM utilisateurs 
                                WHERE role = 'user'
                                    AND NOT (id = ANY(v_utilisateurs_ayant_achete))
                                ORDER BY RANDOM() 
                                LIMIT 1;
                                
                                IF v_id_utilisateur IS NULL AND array_length(v_utilisateurs_ayant_achete, 1) > 0 THEN
                                    v_id_utilisateur := v_utilisateurs_ayant_achete[1 + floor(random() * array_length(v_utilisateurs_ayant_achete, 1))::INTEGER];
                                END IF;
                                
                                IF v_id_utilisateur IS NOT NULL THEN
                                    IF NOT (v_id_utilisateur = ANY(v_utilisateurs_ayant_achete)) THEN
                                        v_utilisateurs_ayant_achete := array_append(v_utilisateurs_ayant_achete, v_id_utilisateur);
                                    END IF;
                                    
                                    v_compteur_billet := v_compteur_billet + 1;
                                    
                                    DECLARE
                                        v_date_achat_valid TIMESTAMPTZ;
                                        v_code_qr_valid TEXT;
                                        v_checksum_qr_valid TEXT;
                                    BEGIN
                                        v_date_achat_valid := v_date_debut_vente + ((v_date_fin_vente - v_date_debut_vente) * random());
                                        v_code_qr_valid := 'QR-' || v_id_event || '-' || v_current_cat_id || '-' || v_current_seg_id || '-V' || v_compteur_billet || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                        v_checksum_qr_valid := md5(v_code_qr_valid);
                                        
                                        INSERT INTO paniers (id_utilisateur, statut, devise, montant_total, expire_le, cree_le)
                                        VALUES (v_id_utilisateur, 'converted'::cart_status_enum, 'MGA', 0, v_date_fin_vente, v_date_achat_valid)
                                        RETURNING id INTO v_id_panier;
                                        
                                        SELECT prix_de_base INTO v_prix FROM types_billets WHERE id = v_current_type_billet_id;
                                        
                                        INSERT INTO commandes (id_utilisateur, id_panier, statut, montant_total, devise, cree_le)
                                        VALUES (v_id_utilisateur, v_id_panier, 'paid'::order_status_enum, v_prix, 'MGA', v_date_achat_valid)
                                        RETURNING id INTO v_id_commande;
                                        
                                        INSERT INTO elements_commandes (id_commande, id_type_billet, quantite, prix_unitaire, frais_service, montant_tva, montant_total)
                                        VALUES (v_id_commande, v_current_type_billet_id, 1, v_prix, v_prix * 0.1, v_prix * 0.2, v_prix * 1.3)
                                        RETURNING id INTO v_id_element_commande;
                                        
                                        INSERT INTO billets (
                                            id_element_commande, id_type_billet, id_utilisateur_proprietaire, 
                                            statut, code_qr, checksum_qr, emis_le, metadonnees
                                        ) VALUES (
                                            v_id_element_commande, 
                                            v_current_type_billet_id, 
                                            v_id_utilisateur, 
                                            'valid'::ticket_status_enum, 
                                            v_code_qr_valid,
                                            v_checksum_qr_valid,
                                            v_date_achat_valid,
                                            jsonb_build_object(
                                                'evenement_id', v_id_event,
                                                'categorie', v_current_cat_id,
                                                'segment', v_current_seg_id,
                                                'numero_billet', v_compteur_billet,
                                                'statut', 'valid',
                                                'date_emission', v_date_achat_valid::TEXT
                                            )
                                        );
                                    END;
                                END IF;
                            END LOOP;
                            
                            -- Créer les billets 'used' (uniquement pour événements en cours)
                            IF v_date_debut < NOW() THEN
                                FOR k IN 1..v_nb_used_type LOOP
                                    IF v_compteur_billet >= (v_nb_billets_valid + v_nb_billets_used) THEN
                                        EXIT;
                                    END IF;
                                    
                                    SELECT id INTO v_id_utilisateur 
                                    FROM utilisateurs 
                                    WHERE role = 'user'
                                    ORDER BY RANDOM() 
                                    LIMIT 1;
                                    
                                    IF v_id_utilisateur IS NOT NULL THEN
                                        v_compteur_billet := v_compteur_billet + 1;
                                        
                                        DECLARE
                                            v_date_achat_used TIMESTAMPTZ;
                                            v_code_qr_used TEXT;
                                            v_checksum_qr_used TEXT;
                                        BEGIN
                                            v_date_achat_used := v_date_debut_vente + ((v_date_fin_vente - v_date_debut_vente) * random());
                                            v_code_qr_used := 'QR-' || v_id_event || '-' || v_current_cat_id || '-' || v_current_seg_id || '-U' || v_compteur_billet || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                            v_checksum_qr_used := md5(v_code_qr_used);
                                            
                                            INSERT INTO paniers (id_utilisateur, statut, devise, montant_total, expire_le, cree_le)
                                            VALUES (v_id_utilisateur, 'converted'::cart_status_enum, 'MGA', 0, v_date_fin_vente, v_date_achat_used)
                                            RETURNING id INTO v_id_panier;
                                            
                                            SELECT prix_de_base INTO v_prix FROM types_billets WHERE id = v_current_type_billet_id;
                                            
                                            INSERT INTO commandes (id_utilisateur, id_panier, statut, montant_total, devise, cree_le)
                                            VALUES (v_id_utilisateur, v_id_panier, 'paid'::order_status_enum, v_prix, 'MGA', v_date_achat_used)
                                            RETURNING id INTO v_id_commande;
                                            
                                            INSERT INTO elements_commandes (id_commande, id_type_billet, quantite, prix_unitaire, frais_service, montant_tva, montant_total)
                                            VALUES (v_id_commande, v_current_type_billet_id, 1, v_prix, v_prix * 0.1, v_prix * 0.2, v_prix * 1.3)
                                            RETURNING id INTO v_id_element_commande;
                                            
                                            INSERT INTO billets (
                                                id_element_commande, id_type_billet, id_utilisateur_proprietaire, 
                                                statut, code_qr, checksum_qr, emis_le, metadonnees
                                            ) VALUES (
                                                v_id_element_commande, 
                                                v_current_type_billet_id, 
                                                v_id_utilisateur, 
                                                'used'::ticket_status_enum, 
                                                v_code_qr_used,
                                                v_checksum_qr_used,
                                                v_date_achat_used,
                                                jsonb_build_object(
                                                    'evenement_id', v_id_event,
                                                    'categorie', v_current_cat_id,
                                                    'segment', v_current_seg_id,
                                                    'numero_billet', v_compteur_billet,
                                                    'statut', 'used',
                                                    'date_emission', v_date_achat_used::TEXT
                                                )
                                            );
                                        END;
                                    END IF;
                                END LOOP;
                            END IF;
                            
                            -- Créer les billets 'dispo' (disponibles, non achetés)
                            FOR k IN 1..v_nb_dispo_type LOOP
                                IF v_compteur_billet >= v_nb_billets_total THEN
                                    EXIT;
                                END IF;
                                
                                v_compteur_billet := v_compteur_billet + 1;
                                
                                DECLARE
                                    v_code_qr_dispo TEXT;
                                    v_checksum_qr_dispo TEXT;
                                BEGIN
                                    v_code_qr_dispo := 'QR-' || v_id_event || '-' || v_current_cat_id || '-' || v_current_seg_id || '-D' || v_compteur_billet || '-' || EXTRACT(EPOCH FROM NOW())::TEXT || '-' || floor(random() * 1000000)::TEXT;
                                    v_checksum_qr_dispo := md5(v_code_qr_dispo);
                                    
                                    INSERT INTO billets (
                                        id_element_commande, id_type_billet, id_utilisateur_proprietaire, 
                                        statut, code_qr, checksum_qr, emis_le, metadonnees
                                    ) VALUES (
                                        NULL,
                                        v_current_type_billet_id,
                                        NULL,
                                        'dispo'::ticket_status_enum,
                                        v_code_qr_dispo,
                                        v_checksum_qr_dispo,
                                        v_date_creation,
                                        jsonb_build_object(
                                            'evenement_id', v_id_event,
                                            'categorie', v_current_cat_id,
                                            'segment', v_current_seg_id,
                                            'numero_billet', v_compteur_billet,
                                            'statut', 'dispo',
                                            'date_emission', v_date_creation::TEXT
                                        )
                                    );
                                END;
                            END LOOP;
                        END;
                END LOOP;
                CLOSE v_type_billet_cursor;
                
                RAISE NOTICE 'Billets créés pour événement % (en cours/à venir): % billets (valid: %, used: %, dispo: %)', 
                    v_id_event, v_nb_billets_total, v_nb_billets_valid, v_nb_billets_used, v_nb_billets_dispo;
            END;
        END IF;

        -- ============================================================
        -- AJOUTER DES VUES (TOUJOURS > participants, 2-4x le nombre de participants)
        -- ============================================================
        v_nb_participants := array_length(v_utilisateurs_ayant_achete, 1);
        IF v_nb_participants IS NULL THEN v_nb_participants := 0; END IF;
        
        IF v_nb_participants > 0 THEN
            v_multiplicateur := 2 + floor(random() * 3)::INTEGER;
            v_vues_calculees := v_nb_participants * v_multiplicateur;
            
            IF v_nb_participants >= v_nb_utilisateurs_user THEN
                v_nb_vues := v_nb_utilisateurs_user;
            ELSE
                v_nb_vues := LEAST(v_nb_utilisateurs_user, v_vues_calculees);
            END IF;
            
            IF v_nb_vues <= v_nb_participants THEN
                IF v_nb_participants >= v_nb_utilisateurs_user THEN
                    v_nb_vues := v_nb_utilisateurs_user;
                ELSE
                    v_nb_vues := v_nb_participants + 1;
                END IF;
            END IF;
        ELSE
            v_nb_vues := LEAST(
                v_nb_utilisateurs_user,
                10 + floor(random() * 21)::INTEGER
            );
        END IF;
        
        IF v_nb_vues IS NULL OR v_nb_vues < 1 THEN
            v_nb_vues := LEAST(1, v_nb_utilisateurs_user);
        END IF;
        
        v_vues_generees := 0;
        v_tentatives_vues := 0;
        
        IF v_nb_participants > 0 THEN
            v_vues_minimum := v_nb_participants + 1;
            v_nb_vues := GREATEST(v_nb_vues, v_vues_minimum);
        END IF;
        
        WHILE v_vues_generees < v_nb_vues AND v_tentatives_vues < (v_nb_utilisateurs_user * 3) LOOP
            v_tentatives_vues := v_tentatives_vues + 1;
            
            SELECT id INTO v_random_user 
            FROM utilisateurs 
            WHERE role = 'user'
            ORDER BY RANDOM() 
            LIMIT 1;
            
            IF NOT (v_random_user = ANY(v_utilisateurs_ayant_vu)) THEN
                v_utilisateurs_ayant_vu := array_append(v_utilisateurs_ayant_vu, v_random_user);
                v_vues_generees := v_vues_generees + 1;
                
                INSERT INTO vues_evenements (
                    id_evenement,
                    id_utilisateur,
                    adresse_ip,
                    user_agent,
                    referer,
                    type_vue,
                    duree_vue_secondes,
                    cree_le
                ) VALUES (
                    v_id_event,
                    CASE WHEN random() < 0.7 THEN v_random_user ELSE NULL END,
                    ('192.168.' || floor(random() * 255)::INTEGER || '.' || floor(random() * 255)::INTEGER)::INET,
                    (ARRAY['Mozilla/5.0', 'Chrome/120.0', 'Safari/17.0', 'Firefox/121.0'])[1 + floor(random() * 4)::INTEGER],
                    CASE 
                        WHEN random() < 0.4 THEN 'https://mg/events'
                        WHEN random() < 0.7 THEN 'https://mg/search'
                        ELSE NULL
                    END,
                    (ARRAY['page', 'listing', 'search', 'share']::VARCHAR[])[1 + floor(random() * 4)::INTEGER],
                    CASE 
                        WHEN random() < 0.2 THEN NULL
                        ELSE 30 + floor(random() * 300)::INTEGER
                    END,
                    v_date_creation + (INTERVAL '1 day' * floor(random() * 30)::INTEGER)
                );
            END IF;
        END LOOP;
        
        IF v_nb_participants > 0 THEN
            WHILE v_vues_generees <= v_nb_participants AND v_tentatives_vues < (v_nb_utilisateurs_user * 5) LOOP
                v_tentatives_vues := v_tentatives_vues + 1;
                
                SELECT id INTO v_random_user 
                FROM utilisateurs 
                WHERE role = 'user'
                ORDER BY RANDOM() 
                LIMIT 1;
                
                IF NOT (v_random_user = ANY(v_utilisateurs_ayant_vu)) THEN
                    v_utilisateurs_ayant_vu := array_append(v_utilisateurs_ayant_vu, v_random_user);
                    v_vues_generees := v_vues_generees + 1;
                    
                    INSERT INTO vues_evenements (
                        id_evenement,
                        id_utilisateur,
                        adresse_ip,
                        user_agent,
                        referer,
                        type_vue,
                        duree_vue_secondes,
                        cree_le
                    ) VALUES (
                        v_id_event,
                        CASE WHEN random() < 0.7 THEN v_random_user ELSE NULL END,
                        ('192.168.' || floor(random() * 255)::INTEGER || '.' || floor(random() * 255)::INTEGER)::INET,
                        (ARRAY['Mozilla/5.0', 'Chrome/120.0', 'Safari/17.0', 'Firefox/121.0'])[1 + floor(random() * 4)::INTEGER],
                        CASE 
                            WHEN random() < 0.4 THEN 'https://mg/events'
                            WHEN random() < 0.7 THEN 'https://mg/search'
                            ELSE NULL
                        END,
                        (ARRAY['page', 'listing', 'search', 'share']::VARCHAR[])[1 + floor(random() * 4)::INTEGER],
                        CASE 
                            WHEN random() < 0.2 THEN NULL
                            ELSE 30 + floor(random() * 300)::INTEGER
                        END,
                        v_date_creation + (INTERVAL '1 day' * floor(random() * 30)::INTEGER)
                    );
                END IF;
            END LOOP;
        END IF;
        
        v_nb_vues := v_vues_generees;

        -- ============================================================
        -- AJOUTER DES FAVORIS (10-30% des vues)
        -- ============================================================
        v_nb_favoris := LEAST(
            COALESCE(array_length(v_utilisateurs_ayant_vu, 1), 0),
            floor(COALESCE(array_length(v_utilisateurs_ayant_vu, 1), 0) * (0.1 + random() * 0.2))::INTEGER
        );
        
        IF v_nb_favoris IS NULL THEN
            v_nb_favoris := 0;
        END IF;
        
        FOR j IN 1..v_nb_favoris LOOP
            IF array_length(v_utilisateurs_ayant_vu, 1) > 0 THEN
                v_random_user := v_utilisateurs_ayant_vu[1 + floor(random() * array_length(v_utilisateurs_ayant_vu, 1))::INTEGER];
                
                IF NOT (v_random_user = ANY(v_utilisateurs_favoris)) THEN
                    v_utilisateurs_favoris := array_append(v_utilisateurs_favoris, v_random_user);
                    
                    SELECT id INTO v_id_liste_souhaits
                    FROM listes_souhaits
                    WHERE id_utilisateur = v_random_user AND est_par_defaut = TRUE;
                    
                    IF v_id_liste_souhaits IS NULL THEN
                        INSERT INTO listes_souhaits (id_utilisateur, titre, est_par_defaut)
                        VALUES (v_random_user, 'Mes Favoris', TRUE)
                        RETURNING id INTO v_id_liste_souhaits;
                    END IF;
                    
                    INSERT INTO elements_listes_souhaits (id_liste_souhaits, id_evenement, ajoute_le)
                    VALUES (
                        v_id_liste_souhaits,
                        v_id_event,
                        v_date_creation + (INTERVAL '1 day' * floor(random() * 30)::INTEGER)
                    )
                    ON CONFLICT DO NOTHING;
                END IF;
            END IF;
        END LOOP;

        RAISE NOTICE 'Événement % créé - Participants: %, Vues: %, Favoris: %', 
            i, 
            v_nb_participants, 
            COALESCE(array_length(v_utilisateurs_ayant_vu, 1), 0), 
            COALESCE(array_length(v_utilisateurs_favoris, 1), 0);
    END LOOP;

    -- ============================================================
    -- CRÉER LA TABLE DE LISTES D'ATTENTE SI ELLE N'EXISTE PAS
    -- ============================================================
    CREATE TABLE IF NOT EXISTS listes_attente_billets (
        id BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
        id_evenement BIGINT NOT NULL REFERENCES evenements(id) ON DELETE CASCADE,
        id_type_billet BIGINT NOT NULL REFERENCES types_billets(id) ON DELETE CASCADE,
        id_utilisateur BIGINT NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
        quantite_demandee INTEGER NOT NULL CHECK (quantite_demandee > 0),
        statut VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (statut IN ('pending', 'notified', 'fulfilled', 'cancelled')),
        position INTEGER,
        cree_le TIMESTAMPTZ NOT NULL DEFAULT now(),
        notifie_le TIMESTAMPTZ,
        remplie_le TIMESTAMPTZ,
        UNIQUE (id_evenement, id_type_billet, id_utilisateur)
    );

    -- Vider la table existante pour repartir à zéro
    DELETE FROM listes_attente_billets;

-- ============================================================
-- CRÉER DES LISTES D'ATTENTE COHÉRENTES AVEC INVENTAIRE
-- POUR TOUS LES ÉVÉNEMENTS EN COURS/À VENIR AVEC STOCK ÉPUISÉ
-- ============================================================
    DECLARE
        v_event_attente_record RECORD;
        v_categorie_record RECORD;
        v_users_utilises_event BIGINT[];
        v_type_billet_epuise_id BIGINT;
        v_type_billet_nom TEXT;
        v_categorie_nom TEXT;
        v_categorie_id BIGINT;
        v_inventaire_record RECORD;
        v_quantite_disponible INTEGER;
        v_quantite_demandee INTEGER;
        v_nb_billets_attente_total INTEGER := 0;
        v_nb_listes_attente_creees INTEGER := 0;
        v_current_billet_id BIGINT;
        v_current_billet_nom TEXT;
        v_current_segment_id BIGINT;
        v_nb_listes_attente_event INTEGER;
        v_statut_event TEXT;
        v_nb_events_traites INTEGER := 0;
    BEGIN
        RAISE NOTICE '============================================';
        RAISE NOTICE 'CRÉATION DES LISTES D''ATTENTE';
        RAISE NOTICE '============================================';
        
        -- Parcourir UNIQUEMENT les 2 premiers événements en cours
        FOR v_event_attente_record IN 
            SELECT 
                e.id as event_id,
                e.titre,
                CASE 
                    WHEN e.commence_le < NOW() AND e.se_termine_le >= NOW() THEN 'en_cours'
                    WHEN e.commence_le > NOW() THEN 'a_venir'
                    ELSE 'autre'
                END as statut_event
            FROM evenements e
            WHERE e.id_profil_organisateur = v_id_profil_org
                AND e.statut = 'published'
                AND (e.commence_le < NOW() AND e.se_termine_le >= NOW()) -- UNIQUEMENT en cours
            ORDER BY e.commence_le
            LIMIT 2 -- UNIQUEMENT les 2 premiers événements en cours
        LOOP
            v_nb_events_traites := v_nb_events_traites + 1;
            v_statut_event := v_event_attente_record.statut_event;
            
            RAISE NOTICE 'Traitement de l''événement % (%): %', 
                v_event_attente_record.event_id, v_statut_event, v_event_attente_record.titre;

            -- Toujours 2 utilisateurs dans la liste d'attente pour les événements en cours
            v_nb_listes_attente_event := 2;

            -- Réinitialiser la liste des utilisateurs utilisés pour cet événement
            v_users_utilises_event := ARRAY[]::BIGINT[];

            -- Parcourir les catégories de billets pour trouver celles qui sont COMPLÈTEMENT épuisées
            -- (tous les types de billets de la catégorie doivent être épuisés)
            FOR v_categorie_record IN 
                SELECT DISTINCT
                    tb.id_configuration_categorie,
                    cc.nom as categorie_nom,
                    CASE cc.nom 
                        WHEN 'vip' THEN 1
                        WHEN 'standard' THEN 2
                        WHEN 'prevente' THEN 3
                        ELSE 4
                    END as ordre_priorite
                FROM types_billets tb
                JOIN configuration_categories_billets cc ON tb.id_configuration_categorie = cc.id
                WHERE tb.id_evenement = v_event_attente_record.event_id
                    -- Vérifier que TOUS les types de billets de cette catégorie sont épuisés
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM types_billets tb2
                        JOIN inventaire_billets ib2 ON tb2.id = ib2.id_type_billet
                        WHERE tb2.id_evenement = v_event_attente_record.event_id
                            AND tb2.id_configuration_categorie = tb.id_configuration_categorie
                            AND (ib2.quantite_totale - ib2.quantite_vendue - ib2.quantite_reservee) > 0
                    )
                    -- Vérifier qu'au moins un type de billet existe pour cette catégorie
                    AND EXISTS (
                        SELECT 1 
                        FROM types_billets tb3
                        JOIN inventaire_billets ib3 ON tb3.id = ib3.id_type_billet
                        WHERE tb3.id_evenement = v_event_attente_record.event_id
                            AND tb3.id_configuration_categorie = tb.id_configuration_categorie
                            AND (ib3.quantite_totale - ib3.quantite_vendue - ib3.quantite_reservee) <= 0
                    )
                ORDER BY ordre_priorite
                LIMIT 2 -- Maximum 2 catégories par événement
            LOOP
                v_categorie_id := v_categorie_record.id_configuration_categorie;
                v_categorie_nom := v_categorie_record.categorie_nom;
                
                RAISE NOTICE '  - Catégorie épuisée trouvée: %', v_categorie_nom;

                -- Vérifier que la catégorie est bien épuisée pour tous ses types de billets
                DECLARE
                    v_billet_cursor CURSOR FOR
                        SELECT tb.id, tb.nom, tb.id_configuration_segment,
                               ib.quantite_totale, ib.quantite_vendue, ib.quantite_reservee,
                               (ib.quantite_totale - ib.quantite_vendue - ib.quantite_reservee) as quantite_disponible
                        FROM types_billets tb
                        JOIN inventaire_billets ib ON tb.id = ib.id_type_billet
                        WHERE tb.id_evenement = v_event_attente_record.event_id
                            AND tb.id_configuration_categorie = v_categorie_id;
                    v_billets_epuises INTEGER := 0;
                    v_billets_avec_stock INTEGER := 0;
                    v_quantite_totale_billet INTEGER;
                    v_quantite_vendue_billet INTEGER;
                    v_quantite_reservee_billet INTEGER;
                BEGIN
                    OPEN v_billet_cursor;
                    LOOP
                        FETCH v_billet_cursor INTO v_current_billet_id, v_current_billet_nom, v_current_segment_id,
                                                     v_quantite_totale_billet, v_quantite_vendue_billet, 
                                                     v_quantite_reservee_billet, v_quantite_disponible;
                        EXIT WHEN NOT FOUND;
                        
                        IF v_quantite_disponible <= 0 THEN
                            v_billets_epuises := v_billets_epuises + 1;
                            RAISE NOTICE '    ✓ Billet % épuisé (vendu: %/%)', 
                                v_current_billet_nom, 
                                v_quantite_vendue_billet,
                                v_quantite_totale_billet;
                        ELSE
                            v_billets_avec_stock := v_billets_avec_stock + 1;
                            RAISE NOTICE '    ⚠️ Billet % a encore % billets disponibles', 
                                v_current_billet_nom, v_quantite_disponible;
                        END IF;
                    END LOOP;
                    CLOSE v_billet_cursor;
                    
                    -- Si tous les billets de cette catégorie sont épuisés, créer des listes d'attente
                    IF v_billets_epuises > 0 AND v_billets_avec_stock = 0 THEN
                        RAISE NOTICE '  ✅ Catégorie % complètement épuisée - Création de listes d''attente', v_categorie_nom;
                        
                        -- Sélectionner le type de billet Adulte de cette catégorie pour les listes d'attente
                        SELECT tb.id, tb.nom
                        INTO v_type_billet_epuise_id, v_type_billet_nom
                        FROM types_billets tb
                        WHERE tb.id_evenement = v_event_attente_record.event_id
                            AND tb.id_configuration_categorie = v_categorie_id
                            AND tb.id_configuration_segment = v_id_segment_adulte
                        LIMIT 1;

                        IF v_type_billet_epuise_id IS NOT NULL THEN
                            RAISE NOTICE '    - Type de billet pour listes d''attente: %', v_type_billet_nom;

                            -- Pour chaque liste d'attente à créer pour cette catégorie
                            FOR liste_idx IN 1..v_nb_listes_attente_event LOOP
                                -- Sélectionner un utilisateur aléatoire
                                DECLARE
                                    v_id_user_attente BIGINT;
                                    v_user_exists BOOLEAN;
                                    v_attempts INTEGER := 0;
                                    v_max_attempts INTEGER := 20;
                                BEGIN
                                    v_user_exists := FALSE;
                                    
                                    WHILE NOT v_user_exists AND v_attempts < v_max_attempts LOOP
                                        v_attempts := v_attempts + 1;
                                        
                                        -- Sélectionner un utilisateur aléatoire non utilisé pour cet événement
                                        SELECT id INTO v_id_user_attente
                                        FROM utilisateurs
                                        WHERE role = 'user'
                                            AND NOT (id = ANY(v_users_utilises_event))
                                        ORDER BY RANDOM()
                                        LIMIT 1;

                                        -- Vérifier qu'il n'a pas déjà une demande pour ce type de billet
                                        IF v_id_user_attente IS NOT NULL THEN
                                            SELECT NOT EXISTS(
                                                SELECT 1 FROM listes_attente_billets lab
                                                WHERE lab.id_evenement = v_event_attente_record.event_id
                                                    AND lab.id_type_billet = v_type_billet_epuise_id
                                                    AND lab.id_utilisateur = v_id_user_attente
                                                    AND lab.statut IN ('pending', 'notified')
                                            ) INTO v_user_exists;

                                            IF v_user_exists THEN
                                                v_users_utilises_event := array_append(v_users_utilises_event, v_id_user_attente);
                                            END IF;
                                        END IF;
                                    END LOOP;

                                    IF v_user_exists AND v_id_user_attente IS NOT NULL THEN
                                        -- Déterminer la quantité demandée (2-3 billets)
                                        v_quantite_demandee := 2 + floor(random() * 2)::INTEGER; -- 2 à 3

                                        -- Calculer la position dans la liste d'attente
                                        DECLARE
                                            v_position INTEGER;
                                        BEGIN
                                            SELECT COALESCE(MAX(position), 0) + 1 INTO v_position
                                            FROM listes_attente_billets
                                            WHERE id_evenement = v_event_attente_record.event_id
                                                AND id_type_billet = v_type_billet_epuise_id;

                                            -- Créer l'entrée dans la liste d'attente
                                            INSERT INTO listes_attente_billets (
                                                id_evenement,
                                                id_type_billet,
                                                id_utilisateur,
                                                quantite_demandee,
                                                statut,
                                                position,
                                                cree_le
                                            ) VALUES (
                                                v_event_attente_record.event_id,
                                                v_type_billet_epuise_id,
                                                v_id_user_attente,
                                                v_quantite_demandee,
                                                'pending',
                                                v_position,
                                                NOW() - INTERVAL '1 day' * floor(random() * 7)::INTEGER
                                            )
                                            ON CONFLICT (id_evenement, id_type_billet, id_utilisateur) DO NOTHING;

                                            IF FOUND THEN
                                                v_nb_listes_attente_creees := v_nb_listes_attente_creees + 1;
                                                v_nb_billets_attente_total := v_nb_billets_attente_total + v_quantite_demandee;
                                                
                                                RAISE NOTICE '      ✓ Liste d''attente créée: position %, user %, quantité %',
                                                    v_position, v_id_user_attente, v_quantite_demandee;
                                            END IF;
                                        END;
                                    END IF;
                                END;
                            END LOOP;
                        ELSE
                            RAISE NOTICE '    - Aucun billet Adulte trouvé dans cette catégorie';
                        END IF;
                    ELSE
                        RAISE NOTICE '  - Catégorie % a encore du stock disponible, pas de liste d''attente', v_categorie_nom;
                    END IF;
                END;
            END LOOP; -- Fin boucle catégories
        END LOOP; -- Fin boucle événements
        
        RAISE NOTICE '============================================';
        RAISE NOTICE 'Événements traités: %', v_nb_events_traites;
        RAISE NOTICE 'Listes d''attente créées: %', v_nb_listes_attente_creees;
        RAISE NOTICE 'Billets demandés au total: %', v_nb_billets_attente_total;

        -- Vérification finale de cohérence
        RAISE NOTICE '============================================';
        RAISE NOTICE 'VÉRIFICATION DE COHÉRENCE COMPLÈTE';
        RAISE NOTICE '============================================';
        
        -- Vérifier que TOUS les billets des catégories avec listes d'attente sont épuisés
        DECLARE
            v_problemes INTEGER := 0;
            v_verification RECORD;
        BEGIN
            FOR v_verification IN 
                SELECT 
                    e.titre as evenement,
                    cc.nom as categorie,
                    COUNT(DISTINCT tb.id) as nb_types_billets,
                    SUM(CASE WHEN ib.quantite_totale - ib.quantite_vendue > 0 THEN 1 ELSE 0 END) as billets_avec_stock,
                    SUM(ib.quantite_totale - ib.quantite_vendue) as total_disponible,
                    COUNT(lab.id) as nb_listes_attente
                FROM evenements e
                JOIN types_billets tb ON e.id = tb.id_evenement
                JOIN configuration_categories_billets cc ON tb.id_configuration_categorie = cc.id
                JOIN inventaire_billets ib ON tb.id = ib.id_type_billet
                LEFT JOIN listes_attente_billets lab ON tb.id = lab.id_type_billet
                WHERE EXISTS (
                    SELECT 1 FROM listes_attente_billets 
                    WHERE id_evenement = e.id
                )
                GROUP BY e.id, e.titre, cc.nom
                HAVING COUNT(lab.id) > 0
                ORDER BY e.titre, cc.nom
            LOOP
                IF v_verification.total_disponible > 0 THEN
                    RAISE WARNING 'INCOHÉRENCE: % - catégorie % a encore % billets disponibles mais a % listes d''attente!',
                        v_verification.evenement,
                        v_verification.categorie,
                        v_verification.total_disponible,
                        v_verification.nb_listes_attente;
                    RAISE NOTICE '  Détails: % types de billets, % avec stock disponible',
                        v_verification.nb_types_billets,
                        v_verification.billets_avec_stock;
                    v_problemes := v_problemes + 1;
                ELSE
                    RAISE NOTICE '✅ OK: % - catégorie %: COMPLÈTEMENT épuisée, listes d''attente: %',
                        v_verification.evenement,
                        v_verification.categorie,
                        v_verification.nb_listes_attente;
                END IF;
            END LOOP;
            
            IF v_problemes = 0 THEN
                RAISE NOTICE '✅ Toutes les catégories avec listes d''attente sont COMPLÈTEMENT épuisées';
            ELSE
                RAISE WARNING '⚠️  % catégories ont encore du stock disponible malgré les listes d''attente', v_problemes;
            END IF;
        END;

        RAISE NOTICE '============================================';
        RAISE NOTICE 'RÉSUMÉ FINAL';
        RAISE NOTICE '============================================';
        RAISE NOTICE 'Total listes d''attente créées: %', v_nb_listes_attente_creees;
        RAISE NOTICE 'Total billets demandés: %', v_nb_billets_attente_total;
        RAISE NOTICE 'Événements avec liste d''attente: %', (SELECT COUNT(DISTINCT id_evenement) FROM listes_attente_billets);
        
        -- Requête pour vérifier manuellement
        RAISE NOTICE '============================================';
        RAISE NOTICE 'POUR VÉRIFIER MANUELLEMENT, EXÉCUTEZ:';
        RAISE NOTICE '============================================';
        RAISE NOTICE 'SELECT e.titre as evenement, cc.nom as categorie, tb.nom as type_billet, ib.quantite_totale, ib.quantite_vendue, (ib.quantite_totale - ib.quantite_vendue) as disponible, COUNT(lab.id) as nb_listes_attente FROM evenements e JOIN types_billets tb ON e.id = tb.id_evenement JOIN configuration_categories_billets cc ON tb.id_configuration_categorie = cc.id JOIN inventaire_billets ib ON tb.id = ib.id_type_billet LEFT JOIN listes_attente_billets lab ON tb.id = lab.id_type_billet WHERE EXISTS (SELECT 1 FROM listes_attente_billets WHERE id_evenement = e.id) GROUP BY e.id, e.titre, cc.nom, tb.id, tb.nom, ib.quantite_totale, ib.quantite_vendue ORDER BY e.titre, cc.nom, tb.nom;';
    END;
    -- ============================================================
    -- CRÉER 7 CODES PROMOTIONNELS
    -- ============================================================
    DECLARE
        v_codes_promo_ids BIGINT[];
        v_utilisateurs_avec_promo BIGINT[];
        v_nb_utilisations_par_code INTEGER[];
        v_utilisations_totales INTEGER := 0;
        v_commande_avec_promo RECORD;
        v_montant_remise NUMERIC(12,2);
    BEGIN
        FOR i IN 1..7 LOOP
            DECLARE
                v_date_debut TIMESTAMPTZ;
                v_date_fin TIMESTAMPTZ;
                v_type_promo promotion_type_enum;
                v_valeur_promo NUMERIC(12,2);
            BEGIN
                IF i <= 3 THEN
                    v_date_debut := NOW() - INTERVAL '90 days';
                    v_date_fin := NOW() - INTERVAL '10 days';
                ELSIF i <= 5 THEN
                    v_date_debut := NOW() - INTERVAL '30 days';
                    v_date_fin := NOW() + INTERVAL '3 days';
                ELSE
                    v_date_debut := NOW() - INTERVAL '15 days';
                    v_date_fin := NOW() + INTERVAL '60 days';
                END IF;

                v_type_promo := (ARRAY['percent', 'amount']::promotion_type_enum[])[1 + (i % 2)];
                v_valeur_promo := CASE 
                    WHEN v_type_promo = 'percent' THEN (10 + (i % 3) * 5)::NUMERIC
                    ELSE (5000 + (i % 3) * 5000)::NUMERIC
                END;

                INSERT INTO codes_promotionnels (
                    id_profil_organisateur, code,
                    type_promotion, valeur,
                    utilisation_maximale_totale, utilisation_maximale_par_utilisateur,
                    commence_le, se_termine_le
                ) VALUES (
                    v_id_profil_org,
                    'PROMO' || LPAD(i::TEXT, 4, '0'),
                    v_type_promo,
                    v_valeur_promo,
                    100,
                    3,
                    v_date_debut,
                    v_date_fin
                ) RETURNING id INTO v_id_code_promo;

                v_codes_promo_ids := array_append(v_codes_promo_ids, v_id_code_promo);
                
                IF i = 1 THEN
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 25);
                ELSIF i = 2 THEN
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 20);
                ELSIF i = 3 THEN
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 20);
                ELSIF i = 4 THEN
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 20);
                ELSIF i = 5 THEN
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 15);
                ELSIF i = 6 THEN
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 10);
                ELSE
                    v_nb_utilisations_par_code := array_append(v_nb_utilisations_par_code, 10);
                END IF;
            END;
        END LOOP;

        FOR i IN 1..7 LOOP
            FOR j IN 1..v_nb_utilisations_par_code[i] LOOP
                SELECT c.id, c.id_utilisateur, c.montant_total, c.cree_le
                INTO v_commande_avec_promo
                FROM commandes c
                WHERE c.statut = 'paid'
                    AND NOT EXISTS (
                        SELECT 1 FROM applications_promotions ap 
                        WHERE ap.id_commande = c.id
                    )
                ORDER BY RANDOM()
                LIMIT 1;

                IF v_commande_avec_promo.id IS NOT NULL THEN
                    DECLARE
                        v_type_promo_local promotion_type_enum;
                        v_valeur_promo_local NUMERIC(12,2);
                    BEGIN
                        SELECT type_promotion, valeur INTO v_type_promo_local, v_valeur_promo_local
                        FROM codes_promotionnels
                        WHERE id = v_codes_promo_ids[i];

                        IF v_type_promo_local = 'percent' THEN
                            v_montant_remise := v_commande_avec_promo.montant_total * (v_valeur_promo_local / 100);
                        ELSE
                            v_montant_remise := LEAST(v_valeur_promo_local, v_commande_avec_promo.montant_total * 0.3);
                        END IF;
                    END;

                    IF NOT EXISTS (
                        SELECT 1 
                        FROM applications_promotions ap
                        WHERE ap.id_promotion = v_codes_promo_ids[i]
                            AND ap.id_utilisateur = v_commande_avec_promo.id_utilisateur
                    ) THEN
                        INSERT INTO applications_promotions (
                            id_promotion, id_commande, id_utilisateur, montant_remise, applique_le
                        ) VALUES (
                            v_codes_promo_ids[i],
                            v_commande_avec_promo.id,
                            v_commande_avec_promo.id_utilisateur,
                            v_montant_remise,
                            v_commande_avec_promo.cree_le
                        )
                        ON CONFLICT DO NOTHING;

                        IF NOT (v_commande_avec_promo.id_utilisateur = ANY(v_utilisateurs_avec_promo)) THEN
                            v_utilisateurs_avec_promo := array_append(v_utilisateurs_avec_promo, v_commande_avec_promo.id_utilisateur);
                        END IF;

                        v_utilisations_totales := v_utilisations_totales + 1;
                    END IF;
                END IF;
            END LOOP;
        END LOOP;

        UPDATE factures_billets fb
        SET montant_sous_total = GREATEST(fb.montant_sous_total - ap.montant_remise, 0),
            montant_total      = GREATEST(fb.montant_total - ap.montant_remise, 0),
            montant_ttc        = GREATEST(fb.montant_ttc - ap.montant_remise, 0)
        FROM applications_promotions ap
        WHERE ap.id_commande = fb.id_commande;

        RAISE NOTICE '✅ Codes promotionnels créés : 7 codes (3 expirés, 2 bientôt expirés, 2 actifs)';
        RAISE NOTICE '   - Utilisations créées : %', v_utilisations_totales;
        RAISE NOTICE '   - Utilisateurs uniques ayant utilisé un code promo : %', array_length(v_utilisateurs_avec_promo, 1);
    END;

    RAISE NOTICE '✅ Données de test créées avec succès pour organisateur11@yopmail.com';
    RAISE NOTICE '   - 21 événements créés (juillet 2025 - février 2026)';
    RAISE NOTICE '     * 5 passés (juillet-août 2025)';
    RAISE NOTICE '     * 4 archivés (septembre-octobre 2025)';
    RAISE NOTICE '     * 5 en cours (novembre-décembre 2025)';
    RAISE NOTICE '     * 7 à venir (janvier-février 2026)';
    RAISE NOTICE '   - 5 lieux différents';
    RAISE NOTICE '   - Capacité des événements basée sur celle du lieu';
    RAISE NOTICE '   - Même lieu = même capacité pour tous les événements';
    RAISE NOTICE '   - 20-30 billets par catégorie (répartis équitablement entre adulte et enfant)';
    RAISE NOTICE '   - Prix variés par catégorie et par événement (variation ±20%%)';
    RAISE NOTICE '   - Pas de survente (quantité vendue ≤ quantité totale)';
    RAISE NOTICE '   - Événements passés: billets vendus (60-85%%)';
    RAISE NOTICE '   - Événements archivés: billets vendus (70-90%%)';
    RAISE NOTICE '   - Événements en cours: billets vendus (30-60%%) - reste disponible';
    RAISE NOTICE '   - Événements à venir: billets vendus (10-30%%) - reste disponible';
    RAISE NOTICE '   - Listes d''attente: UNIQUEMENT pour les billets épuisés des événements en cours/à venir';
    RAISE NOTICE '   - Vues: 2-4x le nombre de participants (limité au nb total users)';
    RAISE NOTICE '   - Favoris: 10-30%% des vues';
    RAISE NOTICE '   - Tous les utilisateurs existent réellement dans la BD';
    RAISE NOTICE '   - Billets annulés présents dans tous les événements';
    RAISE NOTICE '   - 7 codes promo (3 expirés, 2 bientôt expirés, 2 actifs)';

END $$;