-- DONNÉES DE TEST POUR ORGANISATEUR11@YOPMAIL.COM
-- 27 événements avec éléments complets
-- 30-45 billets par catégorie de billet par événement
-- Respect strict du nombre d'utilisateurs disponibles
-- Pas de survente
-- Liste d'attente pour événements en cours
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
    v_billets_annules_total INTEGER := 0;
    v_id_segment_adulte BIGINT;
    v_id_segment_enfant BIGINT;
    v_id_cat_standard BIGINT;
    v_id_cat_vip BIGINT;
    v_id_cat_early_bird BIGINT;
    v_id_cat_backstage BIGINT;
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

    -- Créer les types d'accessibilité (correspondant aux icônes dans l'ordre de l'image)
    -- Ordre selon l'image : 1=fauteuil(tous publics), 2=oreille(malentendants), 3=œil(malvoyants), 4=patte(animaux), 5=toilette(parking), 6=P(WC)
    -- Note: Les fichiers SVG sont inversés - acces5.svg contient l'icône toilette mais correspond à "Parking accessible"
    --       et acces6.svg contient l'icône P mais correspond à "WC accessibles"
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
        ('carte_bancaire', 'Carte bancaire', 'Paiement par carte bancaire', TRUE, 4)
    ON CONFLICT (code) DO NOTHING;

    SELECT id INTO v_id_mode_paiement_mvola FROM modes_paiement WHERE code = 'mvola';
    SELECT id INTO v_id_mode_paiement_orange FROM modes_paiement WHERE code = 'orange';
    SELECT id INTO v_id_mode_paiement_airtel FROM modes_paiement WHERE code = 'airtel';
    SELECT id INTO v_id_mode_paiement_visa FROM modes_paiement WHERE code = 'carte_bancaire';

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
        ('early_bird', 'Billet early bird'),
        ('backstage', 'Billet backstage')
    ON CONFLICT (nom) DO NOTHING;
    SELECT id INTO v_id_cat_standard FROM configuration_categories_billets WHERE nom = 'standard';
    SELECT id INTO v_id_cat_vip FROM configuration_categories_billets WHERE nom = 'vip';
    SELECT id INTO v_id_cat_early_bird FROM configuration_categories_billets WHERE nom = 'early_bird';
    SELECT id INTO v_id_cat_backstage FROM configuration_categories_billets WHERE nom = 'backstage';

    -- ============================================================
    -- CRÉER 5 LIEUX DIFFÉRENTS AVEC COORDONNÉES GPS RÉALISTES
    -- ============================================================
    FOR i IN 1..5 LOOP
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
            (ARRAY[800, 500, 2000, 300, 10000])[i]
        ) RETURNING id INTO v_id_lieu;
        
        v_lieux_ids := array_append(v_lieux_ids, v_id_lieu);

        FOR j IN 1..2 LOOP
            INSERT INTO espaces_lieux (id_lieu, nom, description, capacite, est_par_defaut)
            VALUES (
                v_id_lieu,
                'Espace ' || j,
                'Espace ' || j || ' du lieu ' || i,
                (ARRAY[300, 500, 800, 200, 5000])[i] / 2,
                j = 1
            ) RETURNING id INTO v_id_espace;
            
            v_espaces_ids := array_append(v_espaces_ids, v_id_espace);
        END LOOP;
    END LOOP;

    -- ============================================================
    -- CRÉER 21 ÉVÉNEMENTS
    -- 5 passés (juin 2025), 4 archivés, 5 en cours, 7 à venir
    -- ============================================================
    FOR i IN 1..21 LOOP
        -- Réinitialiser les tableaux pour chaque événement
        v_utilisateurs_ayant_achete := ARRAY[]::BIGINT[];
        v_utilisateurs_ayant_vu := ARRAY[]::BIGINT[];
        v_utilisateurs_favoris := ARRAY[]::BIGINT[];
        
        -- Initialiser la limite de participants (85% du nombre total d'utilisateurs)
        -- Cela garantit qu'il y aura toujours au moins 15% d'utilisateurs disponibles pour les vues
        v_participants_max := floor(v_nb_utilisateurs_user * 0.85)::INTEGER;
        
        -- Définir les dates selon la catégorie
        IF i <= 5 THEN
            -- Événements passés (juin 2025 - les plus anciens)
            v_date_debut := '2025-06-01'::TIMESTAMPTZ + (INTERVAL '5 days' * (i - 1));
            v_event_statut := 'published';
        ELSIF i <= 9 THEN
            -- Événements archivés (juillet 2025)
            v_date_debut := '2025-07-01'::TIMESTAMPTZ + (INTERVAL '5 days' * (i - 5));
            v_event_statut := 'archived';
        ELSIF i <= 14 THEN
            -- Événements en cours : démarrent autour d'aujourd'hui et durent 15 jours
            v_date_debut := v_now - INTERVAL '2 days' + (INTERVAL '1 day' * (i - 9));
            v_event_statut := 'published';
        ELSE
            -- Événements à venir : à partir de dans 15 jours
            v_date_debut := v_now + INTERVAL '15 days' + (INTERVAL '5 days' * (i - 14));
            v_event_statut := 'published';
        END IF;

        v_date_creation := GREATEST('2025-05-01'::TIMESTAMPTZ, v_date_debut - (INTERVAL '45 days'));
        v_date_fin := v_date_debut + INTERVAL '15 days';

        -- Dates de ventes : début 30 jours avant l'événement, fin 1 heure avant la fin de l'événement
        v_date_debut_vente := GREATEST('2025-05-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '30 days');
        v_date_fin_vente := GREATEST(v_date_debut_vente + INTERVAL '1 day', v_date_fin - INTERVAL '1 hour');

        SELECT id INTO v_id_categorie FROM categories_evenements ORDER BY RANDOM() LIMIT 1;
        SELECT id INTO v_id_type_event FROM types_evenements ORDER BY RANDOM() LIMIT 1;
        v_id_lieu := v_lieux_ids[1 + (i % 5)];
        SELECT id INTO v_id_espace FROM espaces_lieux WHERE id_lieu = v_id_lieu ORDER BY RANDOM() LIMIT 1;

        -- Créer l'événement
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
            (ARRAY[500, 800, 300, 1000, 150])[1 + (i % 5)],
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

        -- Note: Les modes de paiement sont maintenant gérés via la table modes_paiement
        -- et peuvent être associés aux factures via id_mode_paiement

        -- Ajouter langues
        INSERT INTO liens_langues_evenements (id_evenement, id_langue)
        VALUES 
            (v_id_event, v_id_langue_fr),
            (v_id_event, v_id_langue_mg),
            (v_id_event, v_id_langue_en)
        ON CONFLICT DO NOTHING;

        -- Ajouter accessibilité (correspondant aux icônes)
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
        -- Pour chaque catégorie : créer 2 types (adulte + enfant)
        -- Nombre total par catégorie : 20-30 billets (répartis équitablement)
        -- Exemple: VIP = 20 billets dont 10 adultes + 10 enfants
        -- ============================================================
        FOR j IN 1..4 LOOP
            -- j = 1: standard, j = 2: early_bird, j = 3: vip, j = 4: backstage
            DECLARE
                v_id_cat_billet BIGINT := CASE 
                    WHEN j = 1 THEN v_id_cat_standard
                    WHEN j = 2 THEN v_id_cat_early_bird
                    WHEN j = 3 THEN v_id_cat_vip
                    ELSE v_id_cat_backstage
                END;
                -- Varier les prix selon la catégorie et l'événement (avec variation aléatoire)
                -- Base: Standard=15000, Early Bird=25000, VIP=60000, Backstage=100000
                -- Variation: ±20% selon l'événement pour rendre les prix uniques
                v_prix_base NUMERIC(12,2);
                v_nom_categorie TEXT := CASE 
                    WHEN j = 1 THEN 'Standard'
                    WHEN j = 2 THEN 'Early Bird'
                    WHEN j = 3 THEN 'VIP'
                    ELSE 'Backstage'
                END;
                -- Nombre total de billets pour cette catégorie (20-30)
                v_total_categorie INTEGER;
                -- Nombre de billets par segment (adulte et enfant) = total / 2
                v_billets_par_segment INTEGER;
            BEGIN
                -- Nombre total de billets pour cette catégorie
                v_total_categorie := 20 + floor(random() * 11)::INTEGER; -- 20 à 30
                -- Répartir équitablement entre adulte et enfant
                v_billets_par_segment := v_total_categorie / 2; -- Division entière automatique
                
                -- Boucle pour créer les 2 types de billets : adulte puis enfant
                FOR segment_idx IN 1..2 LOOP
                    DECLARE
                        v_id_seg_billet BIGINT;
                        v_segment_nom TEXT;
                    BEGIN
                        -- Calculer le prix de base et l'arrondir à 100
                        v_prix_base := CASE 
                            WHEN j = 1 THEN 12000 + floor(random() * 6000)::INTEGER::NUMERIC  -- Standard: 12000-18000
                            WHEN j = 2 THEN 20000 + floor(random() * 10000)::INTEGER::NUMERIC  -- Early Bird: 20000-30000
                            WHEN j = 3 THEN 50000 + floor(random() * 20000)::INTEGER::NUMERIC  -- VIP: 50000-70000
                            ELSE 80000 + floor(random() * 40000)::INTEGER::NUMERIC             -- Backstage: 80000-120000
                        END;
                        v_prix_base := round(v_prix_base / 100) * 100;

                        -- Définir le segment (1=adulte, 2=enfant)
                        IF segment_idx = 1 THEN
                            v_id_seg_billet := v_id_segment_adulte;
                            v_segment_nom := '';
                            v_prix := v_prix_base;
                        ELSE
                            v_id_seg_billet := v_id_segment_enfant;
                            v_segment_nom := ' Enfant';
                            -- 50% pour enfants, arrondi au multiple de 100
                            v_prix := round((v_prix_base * 0.5) / 100) * 100;
                        END IF;
                        
                        v_billets_par_categorie := v_billets_par_segment;
                        
                        -- Créer le type de billet pour ce segment (adulte ou enfant)
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
                            -- Événements archivés : 70-90% des billets vendus
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.7 + random() * 0.2))::INTEGER;
                        ELSIF v_event_statut = 'published' AND v_date_debut < NOW() THEN
                            -- Événements passés (published mais date passée) : 60-85% vendus
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.6 + random() * 0.25))::INTEGER;
                        ELSIF v_event_statut = 'published' AND v_date_debut >= NOW() AND v_date_debut <= NOW() + INTERVAL '90 days' THEN
                            -- Événements en cours : 30-60% vendus (le reste sera disponible, non rattaché à un utilisateur)
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.3 + random() * 0.3))::INTEGER;
                        ELSE
                            -- Événements à venir : 10-30% vendus (le reste sera disponible, non rattaché à un utilisateur)
                            v_quantite_vendue := floor(v_billets_par_categorie * (0.1 + random() * 0.2))::INTEGER;
                        END IF;

                        -- Forcer 4 à 8 billets disponibles (aléatoire) par catégorie/segment
                        DECLARE
                            v_quantite_dispo_cible INTEGER;
                        BEGIN
                            v_quantite_dispo_cible := 4 + floor(random() * 5)::INTEGER; -- entre 4 et 8
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

                        -- Ajouter historiques de prix pour ce type de billet
                        v_nb_historique_prix := floor(random() * 5)::INTEGER;
                        v_prix_precedent := v_prix;
                        
                        FOR k IN 1..v_nb_historique_prix LOOP
                        v_prix_precedent := v_prix_precedent * (0.85 + (random() * 0.2));
                        v_prix_precedent := round(v_prix_precedent / 100) * 100; -- Arrondir au multiple de 100
                            
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
                        -- CRÉER LES BILLETS SELON LA QUANTITÉ VENDUE (pour ce type de billet)
                        -- IMPORTANT : Limiter le nombre total de participants pour laisser des utilisateurs disponibles pour les vues
                        -- LOGIQUE : Si billets vendus > participants, certains participants achètent plusieurs billets
                        -- ============================================================
                        -- Limiter le nombre de participants à maximum 85% du nombre total d'utilisateurs
                        -- Cela garantit qu'il y aura toujours au moins 15% d'utilisateurs disponibles pour les vues
                        v_participants_max := floor(v_nb_utilisateurs_user * 0.85)::INTEGER;  -- Maximum 85% des utilisateurs
                        v_participants_actuels := COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0);
                        
                        -- Calculer le nombre de participants uniques pour cette catégorie/segment
                        -- Si v_quantite_vendue > participants_max, on distribue les billets supplémentaires
                        DECLARE
                            v_nb_participants_categorie INTEGER;
                            v_billets_restants INTEGER;
                            v_index_participant INTEGER;
                            v_compteur_billets INTEGER;
                        BEGIN
                            -- Calculer le nombre de participants uniques pour cette catégorie/segment
                            -- Minimum : 1 participant, Maximum : v_participants_max - v_participants_actuels
                            v_nb_participants_categorie := LEAST(
                                v_quantite_vendue,  -- Au moins 1 billet = 1 participant
                                GREATEST(1, v_participants_max - v_participants_actuels)  -- Mais limité par le max global
                            );
                            
                            -- Si on a plus de billets que de participants, certains participants achètent plusieurs billets
                            v_billets_restants := v_quantite_vendue;
                            v_compteur_billets := 0;
                            
                            -- Étape 1 : Créer les participants uniques (1 billet chacun)
                            FOR k IN 1..v_nb_participants_categorie LOOP
                    IF v_billets_restants <= 0 THEN
                        EXIT;
                    END IF;
                    
                    -- Sélectionner un utilisateur aléatoire qui n'est pas déjà participant
                    SELECT id INTO v_id_utilisateur 
                    FROM utilisateurs 
                    WHERE role = 'user'
                        AND NOT (id = ANY(v_utilisateurs_ayant_achete))
                    ORDER BY RANDOM() 
                    LIMIT 1;
                    
                    -- Si on ne trouve pas d'utilisateur disponible, utiliser un participant existant
                    IF v_id_utilisateur IS NULL THEN
                        -- Utiliser un participant existant (il achètera un billet supplémentaire)
                        IF array_length(v_utilisateurs_ayant_achete, 1) > 0 THEN
                            v_index_participant := 1 + floor(random() * array_length(v_utilisateurs_ayant_achete, 1))::INTEGER;
                            v_id_utilisateur := v_utilisateurs_ayant_achete[v_index_participant];
                        ELSE
                            -- Aucun participant disponible, arrêter
                            EXIT;
                        END IF;
                    ELSE
                        -- Ajouter le nouvel utilisateur à la liste des participants
                        v_participants_actuels := COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0);
                        IF v_participants_actuels < v_participants_max THEN
                            v_utilisateurs_ayant_achete := array_append(v_utilisateurs_ayant_achete, v_id_utilisateur);
                        END IF;
                    END IF;
                    
                                -- Créer le billet pour ce participant
                                v_compteur_billets := v_compteur_billets + 1;
                                v_billets_restants := v_billets_restants - 1;
                                
                                -- Statut du billet selon le type d'événement
                                -- IMPORTANT : Les billets 'dispo' ne sont PAS achetés (pas d'utilisateur, pas de facture)
                                IF v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()) THEN
                                    -- Passé/archivé : ~15% annulés, sinon utilisés (tous achetés)
                                    IF random() < 0.15 THEN
                                        v_statut_billet := 'cancelled';
                                        v_billets_annules_total := v_billets_annules_total + 1;
                                    ELSE
                                        v_statut_billet := 'used';
                                    END IF;
                                ELSE
                                    -- En cours / à venir : ~5% annulés, sinon 'valid' (achetés mais pas encore utilisés)
                                    -- Les billets 'dispo' seront créés séparément sans utilisateur
                                    IF random() < 0.05 THEN
                                        v_statut_billet := 'cancelled';
                                        v_billets_annules_total := v_billets_annules_total + 1;
                                    ELSE
                                        v_statut_billet := 'valid';
                                    END IF;
                                END IF;

                                -- Calculer une date aléatoire dans la période de vente
                                DECLARE
                                    v_date_achat TIMESTAMPTZ;
                                    v_duree_vente INTERVAL;
                                BEGIN
                                    v_duree_vente := v_date_fin_vente - v_date_debut_vente;
                                    v_date_achat := v_date_debut_vente + (v_duree_vente * random());
                                    
                                    -- Créer panier, commande, billet, facture et paiement (pour billets achetés uniquement)
                                    INSERT INTO paniers (id_utilisateur, statut, devise, montant_total, expire_le, cree_le)
                                    VALUES (v_id_utilisateur, 'converted'::cart_status_enum, 'MGA', 0, v_date_fin_vente, v_date_achat)
                                    RETURNING id INTO v_id_panier;

                                    INSERT INTO commandes (id_utilisateur, id_panier, statut, montant_total, devise, cree_le)
                                    VALUES (v_id_utilisateur, v_id_panier, (CASE WHEN v_statut_billet = 'cancelled' THEN 'cancelled' ELSE 'paid' END)::order_status_enum, v_prix, 'MGA', v_date_achat)
                                    RETURNING id INTO v_id_commande;

                                    INSERT INTO elements_commandes (id_commande, id_type_billet, quantite, prix_unitaire, frais_service, montant_tva, montant_total)
                                    VALUES (v_id_commande, v_id_type_billet, 1, v_prix, v_prix * 0.1, v_prix * 0.2, v_prix * 1.3)
                                    RETURNING id INTO v_id_element_commande;

                                    INSERT INTO billets (id_element_commande, id_type_billet, id_utilisateur_proprietaire, statut, code_qr, checksum_qr, emis_le)
                                    VALUES (v_id_element_commande, v_id_type_billet, v_id_utilisateur, v_statut_billet::ticket_status_enum, 'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT, md5('QR-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT), v_date_achat);

                                    IF v_statut_billet != 'cancelled' THEN
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
                                    END IF;
                                END;
                            END LOOP;
                            
                            -- Étape 2 : Distribuer les billets restants parmi les participants existants
                            -- (certains participants achètent plusieurs billets)
                            WHILE v_billets_restants > 0 AND array_length(v_utilisateurs_ayant_achete, 1) > 0 LOOP
                                -- Sélectionner un participant aléatoire existant
                                v_index_participant := 1 + floor(random() * array_length(v_utilisateurs_ayant_achete, 1))::INTEGER;
                                v_id_utilisateur := v_utilisateurs_ayant_achete[v_index_participant];
                                
                                -- Créer un billet supplémentaire pour ce participant
                                v_compteur_billets := v_compteur_billets + 1;
                                v_billets_restants := v_billets_restants - 1;
                                
                                -- Statut du billet - Toujours avoir des billets annulés
                                IF v_billets_annules_total < 21 AND random() < 0.05 THEN
                                    v_statut_billet := 'cancelled';
                                    v_billets_annules_total := v_billets_annules_total + 1;
                                ELSIF v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()) THEN
                                    v_statut_billet := 'used';
                                ELSE
                                    v_statut_billet := 'valid';
                                END IF;

                                -- Calculer une date aléatoire dans la période de vente
                                DECLARE
                                    v_date_achat_supp TIMESTAMPTZ;
                                    v_duree_vente_supp INTERVAL;
                                BEGIN
                                    v_duree_vente_supp := v_date_fin_vente - v_date_debut_vente;
                                    v_date_achat_supp := v_date_debut_vente + (v_duree_vente_supp * random());
                                    
                                    -- Créer panier, commande, billet, facture et paiement
                                    INSERT INTO paniers (id_utilisateur, statut, devise, montant_total, expire_le, cree_le)
                                    VALUES (v_id_utilisateur, 'converted'::cart_status_enum, 'MGA', 0, v_date_fin_vente, v_date_achat_supp)
                                    RETURNING id INTO v_id_panier;

                                    INSERT INTO commandes (id_utilisateur, id_panier, statut, montant_total, devise, cree_le)
                                    VALUES (v_id_utilisateur, v_id_panier, (CASE WHEN v_statut_billet = 'cancelled' THEN 'cancelled' ELSE 'paid' END)::order_status_enum, v_prix, 'MGA', v_date_achat_supp)
                                    RETURNING id INTO v_id_commande;

                                    INSERT INTO elements_commandes (id_commande, id_type_billet, quantite, prix_unitaire, frais_service, montant_tva, montant_total)
                                    VALUES (v_id_commande, v_id_type_billet, 1, v_prix, v_prix * 0.1, v_prix * 0.2, v_prix * 1.3)
                                    RETURNING id INTO v_id_element_commande;

                                    INSERT INTO billets (id_element_commande, id_type_billet, id_utilisateur_proprietaire, statut, code_qr, checksum_qr, emis_le)
                                    VALUES (v_id_element_commande, v_id_type_billet, v_id_utilisateur, v_statut_billet::ticket_status_enum, 'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT, md5('QR-' || v_id_event || '-' || j || '-' || segment_idx || '-' || v_compteur_billets || '-' || EXTRACT(EPOCH FROM NOW())::TEXT), v_date_achat_supp);

                                    IF v_statut_billet != 'cancelled' THEN
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
                                    END IF;
                                END;
                            END LOOP;
                        END;
                        
                        -- ============================================================
                        -- CRÉER DES BILLETS RESTANTS SELON LE TYPE D'ÉVÉNEMENT
                        -- IMPORTANT : Les billets 'dispo' ne sont PAS achetés (pas d'utilisateur, pas de facture)
                        -- ============================================================
                        IF v_quantite_vendue < v_billets_par_categorie THEN
                            DECLARE
                                v_billets_restants INTEGER;
                                v_billets_dispo_a_creer INTEGER;
                                v_billets_valid_a_creer INTEGER;
                            BEGIN
                                v_billets_restants := v_billets_par_categorie - v_quantite_vendue;
                                
                                IF v_event_statut = 'archived' OR (v_event_statut = 'published' AND v_date_debut < NOW()) THEN
                                    -- Événements passés et archivés : 
                                    -- - 5-20% des billets restants comme 'valid' (achetés mais non utilisés)
                                    -- - Le reste comme 'dispo' (jamais vendus, restent disponibles)
                                    v_billets_valid_a_creer := LEAST(
                                        floor(v_billets_restants * (0.05 + random() * 0.15))::INTEGER,  -- 5-20% des billets restants
                                        v_billets_restants,
                                        v_participants_max - COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0)  -- Respecter la limite de participants
                                    );
                                    v_billets_dispo_a_creer := v_billets_restants - v_billets_valid_a_creer; -- Le reste reste disponible (non vendu)
                                ELSE
                                    -- Événements en cours et à venir : créer des billets 'dispo' (non achetés, sans utilisateur)
                                    v_billets_valid_a_creer := 0;
                                    v_billets_dispo_a_creer := v_billets_restants; -- Tous les billets restants sont disponibles
                                END IF;
                                
                                -- Créer les billets 'valid' (achetés mais non utilisés) pour événements passés
                                IF v_billets_valid_a_creer > 0 THEN
                                    FOR k IN 1..v_billets_valid_a_creer LOOP
                                        -- Sélectionner un utilisateur aléatoire
                                        SELECT id INTO v_id_utilisateur 
                                        FROM utilisateurs 
                                        WHERE role = 'user'
                                        ORDER BY RANDOM() 
                                        LIMIT 1;
                                        
                                        IF v_id_utilisateur IS NOT NULL THEN
                                            -- Ajouter à la liste des acheteurs si pas déjà présent
                                            v_participants_actuels := COALESCE(array_length(v_utilisateurs_ayant_achete, 1), 0);
                                            IF NOT (v_id_utilisateur = ANY(v_utilisateurs_ayant_achete)) AND v_participants_actuels < v_participants_max THEN
                                                v_utilisateurs_ayant_achete := array_append(v_utilisateurs_ayant_achete, v_id_utilisateur);
                                            END IF;
                                            
                                            -- Calculer une date aléatoire dans la période de vente
                                            DECLARE
                                                v_date_achat_valid_final TIMESTAMPTZ;
                                                v_duree_vente_valid_final INTERVAL;
                                            BEGIN
                                                v_duree_vente_valid_final := v_date_fin_vente - v_date_debut_vente;
                                                v_date_achat_valid_final := v_date_debut_vente + (v_duree_vente_valid_final * random());
                                                
                                                -- Créer panier
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

                                                -- Créer commande
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

                                                -- Créer élément de commande
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

                                                -- Créer billet avec statut 'valid' (acheté mais non utilisé)
                                                INSERT INTO billets (
                                                    id_element_commande, id_type_billet,
                                                    id_utilisateur_proprietaire, statut,
                                                    code_qr, checksum_qr, emis_le
                                                ) VALUES (
                                                    v_id_element_commande,
                                                    v_id_type_billet,
                                                    v_id_utilisateur,
                                                    'valid'::ticket_status_enum,  -- Acheté mais non utilisé
                                                    'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-V' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                                                    md5('QR-' || v_id_event || '-' || j || '-' || segment_idx || '-V' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT),
                                                    v_date_achat_valid_final
                                                );

                                                -- Créer facture et historique de paiement
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
                                            
                                            -- Mettre à jour la quantité vendue dans l'inventaire
                                            UPDATE inventaire_billets
                                            SET quantite_vendue = quantite_vendue + 1
                                            WHERE id_type_billet = v_id_type_billet;
                                        END IF;
                                    END LOOP;
                                END IF;
                                
                                -- Créer les billets 'dispo' (non achetés, sans utilisateur) pour événements en cours/à venir
                                IF v_billets_dispo_a_creer > 0 THEN
                                    FOR k IN 1..v_billets_dispo_a_creer LOOP
                                        -- Créer billet 'dispo' SANS utilisateur, SANS commande, SANS facture
                                        INSERT INTO billets (
                                            id_element_commande, id_type_billet,
                                            id_utilisateur_proprietaire, statut,
                                            code_qr, checksum_qr, emis_le
                                        ) VALUES (
                                            NULL,  -- Pas d'élément de commande
                                            v_id_type_billet,
                                            NULL,  -- Pas d'utilisateur propriétaire
                                            'dispo'::ticket_status_enum,  -- Disponible à la vente
                                            'QR-' || v_id_event || '-' || j || '-' || segment_idx || '-D' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                                            md5('QR-' || v_id_event || '-' || j || '-' || segment_idx || '-D' || k || '-' || EXTRACT(EPOCH FROM NOW())::TEXT),
                                            v_date_creation  -- Date de création de l'événement
                                        );
                                        -- Note : Pas de mise à jour de quantite_vendue car ces billets ne sont pas vendus
                                    END LOOP;
                                END IF;
                            END;
                        END IF; -- Fin IF v_quantite_vendue < v_billets_par_categorie
                    END; -- Fin du bloc DECLARE pour le segment
                END LOOP; -- Fin de la boucle segment (adulte/enfant)
            END; -- Fin du bloc DECLARE pour la catégorie
        END LOOP; -- Fin de la boucle catégorie (standard, early_bird, vip, backstage)

        -- ============================================================
        -- AJOUTER DES VUES (TOUJOURS > participants, 2-4x le nombre de participants, max = nb users)
        -- ============================================================
        v_nb_participants := array_length(v_utilisateurs_ayant_achete, 1);
        IF v_nb_participants IS NULL THEN v_nb_participants := 0; END IF;
        
        -- Calculer le nombre de vues (TOUJOURS supérieur aux participants)
        -- RÈGLE ABSOLUE : v_nb_vues > v_nb_participants
        IF v_nb_participants > 0 THEN
            -- Si il y a des participants, générer 2-4x plus de vues
            v_multiplicateur := 2 + floor(random() * 3)::INTEGER;  -- 2, 3 ou 4
            v_vues_calculees := v_nb_participants * v_multiplicateur;
            
            -- Calculer les vues en tenant compte de la limite d'utilisateurs
            -- MAIS on s'assure d'abord qu'on a assez d'utilisateurs pour avoir plus de vues que de participants
            IF v_nb_participants >= v_nb_utilisateurs_user THEN
                -- Cas limite : tous les utilisateurs (ou presque) sont participants
                -- On utilise tous les utilisateurs disponibles comme vues
                v_nb_vues := v_nb_utilisateurs_user;
            ELSE
                -- Cas normal : on calcule les vues normalement
                v_nb_vues := LEAST(v_nb_utilisateurs_user, v_vues_calculees);
            END IF;
            
            -- VÉRIFICATION CRITIQUE : s'assurer que v_nb_vues est TOUJOURS > v_nb_participants
            -- Cette vérification est absolue et doit toujours être respectée
            IF v_nb_vues <= v_nb_participants THEN
                -- Si on a moins d'utilisateurs que de participants (ne devrait pas arriver),
                -- on utilise tous les utilisateurs disponibles
                IF v_nb_participants >= v_nb_utilisateurs_user THEN
                    v_nb_vues := v_nb_utilisateurs_user;
                ELSE
                    -- On force au minimum participants + 1
                    v_nb_vues := v_nb_participants + 1;
                END IF;
            END IF;
        ELSE
            -- Pour les événements sans participants, générer un minimum de vues (10-30)
            v_nb_vues := LEAST(
                v_nb_utilisateurs_user,
                10 + floor(random() * 21)::INTEGER  -- 10 à 30 vues minimum
            );
        END IF;
        
        -- Vérification finale de sécurité : s'assurer que v_nb_vues n'est pas NULL et est au moins 1
        IF v_nb_vues IS NULL OR v_nb_vues < 1 THEN
            v_nb_vues := LEAST(1, v_nb_utilisateurs_user);
        END IF;
        
        -- DERNIÈRE VÉRIFICATION ABSOLUE : v_nb_vues DOIT être > v_nb_participants
        -- Cette vérification finale garantit que même si quelque chose a mal tourné, on corrige
        -- On utilise RAISE pour détecter les cas où c'est impossible (pour debug)
        IF v_nb_participants > 0 AND v_nb_vues <= v_nb_participants THEN
            -- Forcer au minimum participants + 1
            IF v_nb_participants < v_nb_utilisateurs_user THEN
                v_nb_vues := v_nb_participants + 1;
            ELSE
                -- Cas exceptionnel : tous les utilisateurs sont participants
                -- On utilise tous les utilisateurs comme vues (mais cela viole la règle)
                -- Dans ce cas, on génère un avertissement mais on continue
                v_nb_vues := v_nb_utilisateurs_user;
                RAISE WARNING 'Événement % : Impossible d''avoir plus de vues que de participants (participants: %, users: %)', 
                    v_id_event, v_nb_participants, v_nb_utilisateurs_user;
            END IF;
        END IF;
        
        -- Générer les vues en s'assurant qu'on en génère au moins v_nb_participants + 1
        -- RÈGLE ABSOLUE : v_vues_generees > v_nb_participants
        v_vues_generees := 0;
        v_tentatives_vues := 0;
        
        -- Calculer le nombre minimum de vues à générer (toujours > participants)
        IF v_nb_participants > 0 THEN
            -- On doit générer au moins participants + 1 vues
            v_vues_minimum := v_nb_participants + 1;
            -- Mais on prend le maximum entre v_nb_vues calculé et v_vues_minimum
            v_nb_vues := GREATEST(v_nb_vues, v_vues_minimum);
        END IF;
        
        -- On fait une boucle jusqu'à ce qu'on ait généré suffisamment de vues
        -- On limite le nombre de tentatives pour éviter une boucle infinie
        WHILE v_vues_generees < v_nb_vues AND v_tentatives_vues < (v_nb_utilisateurs_user * 3) LOOP
            v_tentatives_vues := v_tentatives_vues + 1;
            
            SELECT id INTO v_random_user 
            FROM utilisateurs 
            WHERE role = 'user'
            ORDER BY RANDOM() 
            LIMIT 1;
            
            -- Éviter les doublons
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
        
        -- VÉRIFICATION FINALE ABSOLUE : s'assurer qu'on a généré au moins participants + 1 vues
        -- Cette vérification est critique et doit toujours être respectée
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
        
        -- Mettre à jour v_nb_vues avec le nombre réellement généré
        v_nb_vues := v_vues_generees;

        -- ============================================================
        -- AJOUTER DES FAVORIS (10-30% des vues)
        -- ============================================================
        v_nb_favoris := LEAST(
            COALESCE(array_length(v_utilisateurs_ayant_vu, 1), 0),
            floor(COALESCE(array_length(v_utilisateurs_ayant_vu, 1), 0) * (0.1 + random() * 0.2))::INTEGER
        );
        
        -- S'assurer que v_nb_favoris n'est pas NULL
        IF v_nb_favoris IS NULL THEN
            v_nb_favoris := 0;
        END IF;
        
        FOR j IN 1..v_nb_favoris LOOP
            -- Sélectionner parmi ceux qui ont vu l'événement
            IF array_length(v_utilisateurs_ayant_vu, 1) > 0 THEN
                v_random_user := v_utilisateurs_ayant_vu[1 + floor(random() * array_length(v_utilisateurs_ayant_vu, 1))::INTEGER];
                
                IF NOT (v_random_user = ANY(v_utilisateurs_favoris)) THEN
                    v_utilisateurs_favoris := array_append(v_utilisateurs_favoris, v_random_user);
                    
                    -- Créer ou récupérer la liste de souhaits
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

    -- ============================================================
    -- CRÉER DES LISTES D'ATTENTE POUR 8 ÉVÉNEMENTS
    -- 4-8 billets manquants par catégorie/segment, plusieurs utilisateurs possibles
    -- ============================================================
    DECLARE
        v_id_event_attente BIGINT;
        v_id_type_billet_attente BIGINT;
        v_quantite_manquante INTEGER;
        v_nb_demandes INTEGER;
        v_id_user_attente BIGINT;
        v_quantite_user INTEGER;
        v_position INTEGER;
        v_events_avec_attente BIGINT[];
        v_event_counter INTEGER;
    BEGIN
        -- Sélectionner 8 événements aléatoires (parmi les événements en cours ou à venir)
        SELECT array_agg(id)
        INTO v_events_avec_attente
        FROM (
            SELECT id
            FROM evenements
            WHERE id_profil_organisateur = v_id_profil_org
                AND statut = 'published'
                AND (commence_le >= NOW() OR (commence_le < NOW() AND se_termine_le >= NOW()))
            ORDER BY RANDOM()
            LIMIT 8
        ) sub;

        -- Si on a moins de 8 événements, prendre tous ceux disponibles
        IF array_length(v_events_avec_attente, 1) IS NULL OR array_length(v_events_avec_attente, 1) = 0 THEN
            SELECT array_agg(id)
            INTO v_events_avec_attente
            FROM (
                SELECT id
                FROM evenements
                WHERE id_profil_organisateur = v_id_profil_org
                ORDER BY RANDOM()
                LIMIT 8
            ) sub;
        END IF;

        -- Pour chaque événement sélectionné
        FOR v_event_counter IN 1..LEAST(COALESCE(array_length(v_events_avec_attente, 1), 0), 8) LOOP
            DECLARE
                v_event_idx INTEGER;
            BEGIN
                v_event_idx := v_event_counter;
                v_id_event_attente := v_events_avec_attente[v_event_idx];
                
                IF v_id_event_attente IS NULL THEN
                    CONTINUE;
                END IF;

                -- Sélectionner 1-3 types de billets aléatoirement pour cet événement
                -- Prioriser VIP et Early Bird car ce sont les plus demandés
                FOR j IN 1..(1 + floor(random() * 3)::INTEGER) LOOP
                -- Sélectionner un type de billet aléatoire (prioriser VIP enfant et adulte)
                SELECT tb.id INTO v_id_type_billet_attente
                FROM types_billets tb
                WHERE tb.id_evenement = v_id_event_attente
                    AND tb.id_configuration_categorie IN (v_id_cat_vip, v_id_cat_early_bird, v_id_cat_backstage, v_id_cat_standard)
                ORDER BY CASE 
                    WHEN tb.id_configuration_categorie = v_id_cat_vip AND tb.id_configuration_segment = v_id_segment_enfant THEN 1
                    WHEN tb.id_configuration_categorie = v_id_cat_vip THEN 2
                    WHEN tb.id_configuration_categorie = v_id_cat_early_bird THEN 3
                    ELSE 4
                END, RANDOM()
                LIMIT 1;

                -- Si pas de VIP, prendre n'importe quel type de billet
                IF v_id_type_billet_attente IS NULL THEN
                    SELECT tb.id INTO v_id_type_billet_attente
                    FROM types_billets tb
                    WHERE tb.id_evenement = v_id_event_attente
                    ORDER BY RANDOM()
                    LIMIT 1;
                END IF;

                IF v_id_type_billet_attente IS NULL THEN
                    CONTINUE;
                END IF;

                -- Calculer le nombre de billets manquants (4-8)
                v_quantite_manquante := 4 + floor(random() * 5)::INTEGER; -- 4 à 8

                -- Récupérer l'inventaire pour vérifier les billets réellement disponibles
                DECLARE
                    v_quantite_totale INTEGER;
                    v_quantite_vendue INTEGER;
                    v_quantite_disponible INTEGER;
                BEGIN
                    SELECT ib.quantite_totale, ib.quantite_vendue
                    INTO v_quantite_totale, v_quantite_vendue
                    FROM inventaire_billets ib
                    WHERE ib.id_type_billet = v_id_type_billet_attente;

                    IF v_quantite_totale IS NULL THEN
                        CONTINUE;
                    END IF;

                    v_quantite_disponible := v_quantite_totale - COALESCE(v_quantite_vendue, 0);
                    
                    -- Si des billets sont disponibles, simuler qu'ils sont manquants
                    -- en créant des demandes pour plus que le disponible
                    IF v_quantite_disponible >= v_quantite_manquante THEN
                        -- Augmenter les demandes pour simuler une liste d'attente
                        v_quantite_manquante := v_quantite_disponible + v_quantite_manquante;
                    END IF;

                    -- Répartir la demande entre 2-5 utilisateurs
                    v_nb_demandes := 2 + floor(random() * 4)::INTEGER; -- 2 à 5 utilisateurs
                    v_quantite_manquante := GREATEST(v_quantite_manquante, v_nb_demandes); -- Au moins 1 billet par utilisateur
                    
                    v_position := 0;

                    -- Créer les demandes pour chaque utilisateur
                    FOR k IN 1..v_nb_demandes LOOP
                        v_position := v_position + 1;
                        
                        -- Calculer la quantité demandée par cet utilisateur (1-3 billets)
                        -- S'assurer qu'on ne dépasse pas la quantité totale manquante
                        IF v_position < v_nb_demandes THEN
                            -- Pas le dernier utilisateur, utiliser la quantité calculée (1-3 billets)
                            -- Mais s'assurer qu'il reste assez pour les autres utilisateurs
                            v_quantite_user := LEAST(
                                1 + floor(random() * 3)::INTEGER, -- 1 à 3 billets
                                GREATEST(1, v_quantite_manquante - (v_nb_demandes - v_position)) -- Laisser au moins 1 pour chaque utilisateur restant
                            );
                            v_quantite_manquante := v_quantite_manquante - v_quantite_user;
                        ELSE
                            -- Dernier utilisateur, prendre le reste (minimum 1)
                            v_quantite_user := GREATEST(1, v_quantite_manquante);
                            v_quantite_manquante := 0; -- Réinitialiser pour éviter les erreurs
                        END IF;
                        
                        -- S'assurer que v_quantite_user est toujours >= 1
                        IF v_quantite_user < 1 THEN
                            v_quantite_user := 1;
                        END IF;

                        -- Sélectionner un utilisateur aléatoire qui n'a pas déjà une demande pour ce type de billet
                        SELECT u.id INTO v_id_user_attente
                        FROM utilisateurs u
                        WHERE u.role = 'user'
                            AND NOT EXISTS (
                                SELECT 1 FROM listes_attente_billets lab
                                WHERE lab.id_evenement = v_id_event_attente
                                    AND lab.id_type_billet = v_id_type_billet_attente
                                    AND lab.id_utilisateur = u.id
                            )
                        ORDER BY RANDOM()
                        LIMIT 1;

                        -- Si on n'en trouve pas, prendre n'importe quel utilisateur
                        IF v_id_user_attente IS NULL THEN
                            SELECT u.id INTO v_id_user_attente
                            FROM utilisateurs u
                            WHERE u.role = 'user'
                            ORDER BY RANDOM()
                            LIMIT 1;
                        END IF;

                        IF v_id_user_attente IS NOT NULL THEN
                            -- Insérer la demande dans la liste d'attente
                            INSERT INTO listes_attente_billets (
                                id_evenement,
                                id_type_billet,
                                id_utilisateur,
                                quantite_demandee,
                                statut,
                                position,
                                cree_le
                            ) VALUES (
                                v_id_event_attente,
                                v_id_type_billet_attente,
                                v_id_user_attente,
                                v_quantite_user,
                                'pending',
                                v_position,
                                NOW() - INTERVAL '1 day' * floor(random() * 7)::INTEGER -- Date aléatoire dans les 7 derniers jours
                            )
                            ON CONFLICT (id_evenement, id_type_billet, id_utilisateur) DO NOTHING;
                        END IF;
                    END LOOP;
                END;
                END LOOP; -- Fin boucle j (types de billets)
            END; -- Fin bloc DECLARE pour v_event_idx
        END LOOP; -- Fin boucle v_event_counter (événements)

        RAISE NOTICE '✅ Listes d''attente créées pour % événements', 
            LEAST(COALESCE(array_length(v_events_avec_attente, 1), 0), 8);
    END;

    -- ============================================================
    -- CRÉER 7 CODES PROMOTIONNELS
    -- 3 expirés, 2 bientôt expirés, 2 actifs
    -- ============================================================
    DECLARE
        v_codes_promo_ids BIGINT[];
        v_utilisateurs_avec_promo BIGINT[];
        v_nb_utilisations_par_code INTEGER[];
        v_utilisations_totales INTEGER := 0;
        v_commande_avec_promo RECORD;
        v_montant_remise NUMERIC(12,2);
    BEGIN
        -- Créer les 7 codes promo
        FOR i IN 1..7 LOOP
            DECLARE
                v_date_debut TIMESTAMPTZ;
                v_date_fin TIMESTAMPTZ;
                v_type_promo promotion_type_enum;
                v_valeur_promo NUMERIC(12,2);
            BEGIN
                -- Définir les dates selon le type de code
                IF i <= 3 THEN
                    -- 3 codes EXPIRÉS (i=1,2,3)
                    v_date_debut := NOW() - INTERVAL '90 days';
                    v_date_fin := NOW() - INTERVAL '10 days'; -- Expiré il y a 10 jours
                ELSIF i <= 5 THEN
                    -- 2 codes BIENTÔT EXPIRÉS (i=4,5)
                    v_date_debut := NOW() - INTERVAL '30 days';
                    v_date_fin := NOW() + INTERVAL '3 days'; -- Expire dans 3 jours
                ELSE
                    -- 2 codes ACTIFS (i=6,7)
                    v_date_debut := NOW() - INTERVAL '15 days';
                    v_date_fin := NOW() + INTERVAL '60 days'; -- Expire dans 60 jours
                END IF;

                -- Définir le type et la valeur
                v_type_promo := (ARRAY['percent', 'amount']::promotion_type_enum[])[1 + (i % 2)];
                v_valeur_promo := CASE 
                    WHEN v_type_promo = 'percent' THEN (10 + (i % 3) * 5)::NUMERIC  -- 10%, 15%, 20%
                    ELSE (5000 + (i % 3) * 5000)::NUMERIC  -- 5000, 10000, 15000 MGA
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
                    100, -- Limite maximale totale
                    3,   -- Limite par utilisateur
                    v_date_debut,
                    v_date_fin
                ) RETURNING id INTO v_id_code_promo;

                v_codes_promo_ids := array_append(v_codes_promo_ids, v_id_code_promo);
                
                -- Distribuer les utilisations : objectif 120 utilisations au total (et 120 utilisateurs distincts si possible)
                -- Répartition fixée : [25, 20, 20, 20, 15, 10, 10] = 120
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

        -- Distribuer les 120 utilisations parmi les 7 codes promo
        v_utilisations_totales := 0;
        FOR i IN 1..7 LOOP
            FOR j IN 1..v_nb_utilisations_par_code[i] LOOP
                -- Sélectionner une commande payée aléatoire qui n'a pas déjà de promo
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
                        -- Calculer le montant de la remise selon le type de promo
                        SELECT type_promotion, valeur INTO v_type_promo_local, v_valeur_promo_local
                        FROM codes_promotionnels
                        WHERE id = v_codes_promo_ids[i];

                        IF v_type_promo_local = 'percent' THEN
                            v_montant_remise := v_commande_avec_promo.montant_total * (v_valeur_promo_local / 100);
                        ELSE
                            v_montant_remise := LEAST(v_valeur_promo_local, v_commande_avec_promo.montant_total * 0.3); -- Max 30% de remise pour les montants fixes
                        END IF;
                    END;

                    -- Vérifier que l'utilisateur n'a pas déjà utilisé ce code plus que la limite
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
                            v_commande_avec_promo.cree_le -- Date d'application = date de la commande
                        )
                        ON CONFLICT DO NOTHING;

                        -- Ajouter l'utilisateur à la liste si pas déjà présent
                        IF NOT (v_commande_avec_promo.id_utilisateur = ANY(v_utilisateurs_avec_promo)) THEN
                            v_utilisateurs_avec_promo := array_append(v_utilisateurs_avec_promo, v_commande_avec_promo.id_utilisateur);
                        END IF;

                        v_utilisations_totales := v_utilisations_totales + 1;
                    END IF;
                END IF;
            END LOOP;
        END LOOP;

        -- Répercuter les remises sur les factures
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
    RAISE NOTICE '   - 21 événements créés (5 passés, 4 archivés, 5 en cours, 7 à venir)';
    RAISE NOTICE '   - 5 lieux différents';
    RAISE NOTICE '   - 20-30 billets par catégorie (répartis équitablement entre adulte et enfant)';
    RAISE NOTICE '   - Prix variés par catégorie et par événement (variation ±20%%)';
    RAISE NOTICE '   - Pas de survente (quantité vendue ≤ quantité totale)';
    RAISE NOTICE '   - Événements passés: billets vendus (60-85%%)';
    RAISE NOTICE '   - Événements archivés: billets vendus (70-90%%)';
    RAISE NOTICE '   - Événements en cours: billets vendus (30-60%%) - reste disponible';
    RAISE NOTICE '   - Événements à venir: billets vendus (10-30%%) - reste disponible';
    RAISE NOTICE '   - Listes d''attente: 8 événements avec 4-8 billets manquants par catégorie/segment';
    RAISE NOTICE '   - Vues: 2-4x le nombre de participants (limité au nb total users)';
    RAISE NOTICE '   - Favoris: 10-30%% des vues';
    RAISE NOTICE '   - Tous les utilisateurs existent réellement dans la BD';
    RAISE NOTICE '   - Billets annulés présents dans tous les événements';
    RAISE NOTICE '   - 7 codes promo (3 expirés, 2 bientôt expirés, 2 actifs) utilisés par 23 utilisateurs';

END $$;