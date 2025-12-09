-- DONNÉES DE TEST POUR ORGANISATEUR11@YOPMAIL.COM
-- 27 événements avec éléments complets
-- 45+ utilisateurs par événement, 15 billets annulés au total
-- 30+ utilisateurs utilisant des codes promo
-- 5 lieux différents
-- 3+ types d'accessibilité par événement
-- Historique de prix (0-4 changements par type de billet)
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
    v_id_paiement BIGINT;
    i INTEGER;
    j INTEGER;
    k INTEGER;
    v_date_creation TIMESTAMPTZ;
    v_date_debut TIMESTAMPTZ;
    v_date_fin TIMESTAMPTZ;
    v_prix NUMERIC(12,2);
    v_statut_billet TEXT;
    v_id_utilisateur BIGINT;
    v_compteur_billets INTEGER := 0;
    v_billets_annules_total INTEGER := 0;
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
    v_id_type_access_visual BIGINT;
    v_id_type_access_mobility BIGINT;
    v_id_type_access_cognitive BIGINT;
    v_id_session BIGINT;
    v_lieux_ids BIGINT[];
    v_espaces_ids BIGINT[];
    v_id_code_promo BIGINT;
    v_utilisateurs_promo INTEGER := 0;
    v_nb_historique_prix INTEGER;
    v_prix_precedent NUMERIC(12,2);
    v_total_participants INTEGER;
    v_total_vues INTEGER;
BEGIN
    -- Récupérer l'ID de l'utilisateur organisateur
    SELECT id INTO v_id_utilisateur_org 
    FROM aiolia.utilisateurs 
    WHERE email = 'organisateur11@yopmail.com';

    IF v_id_utilisateur_org IS NULL THEN
        RAISE EXCEPTION 'Utilisateur organisateur11@yopmail.com non trouvé. Veuillez exécuter data.sql avant Events.sql';
    END IF;

    -- Récupérer le profil organisateur
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
            NOW() - INTERVAL '90 days'
        ) RETURNING id INTO v_id_profil_org;
    END IF;

    -- Créer des catégories d'événements
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

    -- Créer les langues
    INSERT INTO aiolia.langues (code, libelle, est_actif)
    VALUES 
        ('mg', 'Malagasy', TRUE),
        ('fr', 'Français', TRUE),
        ('en', 'Anglais', TRUE)
    ON CONFLICT (code) DO NOTHING;

    -- Créer les types d'accessibilité
    INSERT INTO aiolia.types_accessibilite (code, libelle, est_actif)
    VALUES 
        ('wheelchair', 'Accès fauteuil roulant', TRUE),
        ('hearing', 'Accessible aux malentendants', TRUE),
        ('visual', 'Accessible aux malvoyants', TRUE),
        ('mobility', 'Accessible mobilité réduite', TRUE),
        ('cognitive', 'Accessible troubles cognitifs', TRUE),
        ('other', 'Autre', TRUE)
    ON CONFLICT (code) DO NOTHING;

    -- Récupérer les IDs
    SELECT id INTO v_id_langue_fr FROM aiolia.langues WHERE code = 'fr';
    SELECT id INTO v_id_langue_mg FROM aiolia.langues WHERE code = 'mg';
    SELECT id INTO v_id_langue_en FROM aiolia.langues WHERE code = 'en';
    SELECT id INTO v_id_type_access_wheelchair FROM aiolia.types_accessibilite WHERE code = 'wheelchair';
    SELECT id INTO v_id_type_access_hearing FROM aiolia.types_accessibilite WHERE code = 'hearing';
    SELECT id INTO v_id_type_access_visual FROM aiolia.types_accessibilite WHERE code = 'visual';
    SELECT id INTO v_id_type_access_mobility FROM aiolia.types_accessibilite WHERE code = 'mobility';
    SELECT id INTO v_id_type_access_cognitive FROM aiolia.types_accessibilite WHERE code = 'cognitive';

    -- Configuration des segments de billets
    INSERT INTO aiolia.configuration_segments_billets (nom, age_min, age_max)
    VALUES 
        ('adulte', 18, NULL),
        ('enfant', 0, 12),
        ('tous', NULL, NULL)
    ON CONFLICT (nom) DO NOTHING;

    SELECT id INTO v_id_segment_adulte FROM aiolia.configuration_segments_billets WHERE nom = 'adulte';
    SELECT id INTO v_id_segment_enfant FROM aiolia.configuration_segments_billets WHERE nom = 'enfant';
    SELECT id INTO v_id_segment_tous FROM aiolia.configuration_segments_billets WHERE nom = 'tous';

    -- Configuration des catégories de billets
    INSERT INTO aiolia.configuration_categories_billets (nom, description)
    VALUES
        ('tous', 'Catégorie par défaut'),
        ('standard', 'Billet standard'),
        ('vip', 'Billet VIP avec avantages'),
        ('gratuit', 'Billet gratuit'),
        ('promo', 'Billet promotionnel')
    ON CONFLICT (nom) DO NOTHING;

    SELECT id INTO v_id_cat_tous FROM aiolia.configuration_categories_billets WHERE nom = 'tous';
    SELECT id INTO v_id_cat_standard FROM aiolia.configuration_categories_billets WHERE nom = 'standard';
    SELECT id INTO v_id_cat_vip FROM aiolia.configuration_categories_billets WHERE nom = 'vip';
    SELECT id INTO v_id_cat_gratuit FROM aiolia.configuration_categories_billets WHERE nom = 'gratuit';
    SELECT id INTO v_id_cat_promo FROM aiolia.configuration_categories_billets WHERE nom = 'promo';

    -- ============================================================
    -- CRÉER 5 LIEUX DIFFÉRENTS
    -- ============================================================
    FOR i IN 1..5 LOOP
        INSERT INTO aiolia.lieux (
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
            -18.8792 + (i * 0.01),
            47.5079 + (i * 0.01),
            'Indian/Antananarivo',
            'contact@lieu' || i || '.mg',
            '+26134000000' || i,
            (ARRAY[800, 500, 2000, 300, 10000])[i]
        ) RETURNING id INTO v_id_lieu;
        
        v_lieux_ids := array_append(v_lieux_ids, v_id_lieu);

        -- Créer 2-3 espaces par lieu
        FOR j IN 1..2 LOOP
            INSERT INTO aiolia.espaces_lieux (id_lieu, nom, description, capacite, est_par_defaut)
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
    -- CRÉER 27 ÉVÉNEMENTS
    -- ============================================================
    FOR i IN 1..27 LOOP
        -- Définir les dates (date_creation <= date_debut, toutes après juin 2025)
        -- Date de base : 1er juillet 2025
        IF i <= 9 THEN
            -- Événements juillet-septembre 2025
            v_date_debut := '2025-07-01'::TIMESTAMPTZ + (INTERVAL '7 days' * (i - 1));
            v_date_creation := GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - (INTERVAL '45 days'));
        ELSIF i <= 18 THEN
            -- Événements octobre-décembre 2025
            v_date_debut := '2025-10-01'::TIMESTAMPTZ + (INTERVAL '7 days' * (i - 9));
            v_date_creation := GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - (INTERVAL '30 days'));
        ELSE
            -- Événements 2026
            v_date_debut := '2026-01-01'::TIMESTAMPTZ + (INTERVAL '7 days' * (i - 18));
            v_date_creation := GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - (INTERVAL '60 days'));
        END IF;
        v_date_fin := v_date_debut + INTERVAL '4 hours';

        -- Sélectionner catégorie et type
        SELECT id INTO v_id_categorie FROM aiolia.categories_evenements ORDER BY RANDOM() LIMIT 1;
        SELECT id INTO v_id_type_event FROM aiolia.types_evenements ORDER BY RANDOM() LIMIT 1;

        -- Sélectionner un lieu et espace aléatoire
        v_id_lieu := v_lieux_ids[1 + (i % 5)];
        SELECT id INTO v_id_espace FROM aiolia.espaces_lieux WHERE id_lieu = v_id_lieu ORDER BY RANDOM() LIMIT 1;

        -- Créer l'événement
        INSERT INTO aiolia.evenements (
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
            GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '30 days'),
            GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '1 hour'),
            CASE WHEN i % 4 = 0 THEN '18+' ELSE NULL END,
            i % 5 = 0,
            i % 7 = 0,
            FALSE,
            v_date_creation,
            v_date_creation
        ) RETURNING id INTO v_id_event;

        -- Ajouter des tags
        FOR j IN 1..3 LOOP
            INSERT INTO aiolia.tags_evenements (slug, libelle)
            VALUES (
                'tag-' || i || '-' || j,
                (ARRAY['Premium', 'Exclusif', 'Tendance', 'Populaire', 'Familial', 'VIP', 'Unique'])[1 + ((i + j) % 7)]
            ) ON CONFLICT (slug) DO NOTHING;

            INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
            SELECT v_id_event, id FROM aiolia.tags_evenements WHERE slug = 'tag-' || i || '-' || j
            ON CONFLICT DO NOTHING;
        END LOOP;

        -- Ajouter des médias
        INSERT INTO aiolia.medias_evenements (id_evenement, type_media, url, texte_alternatif, ordre_affichage, est_affiche_principale)
        VALUES 
            (v_id_event, 'image', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200', 'Image principale', 0, TRUE),
            (v_id_event, 'image', 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=800', 'Image secondaire', 1, FALSE);

        -- Ajouter modes de paiement
        INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif)
        VALUES 
            (v_id_event, 'mvola', TRUE),
            (v_id_event, 'orange', TRUE),
            (v_id_event, 'airtel', TRUE),
            (v_id_event, 'visa', TRUE);

        -- Ajouter 3 langues
        INSERT INTO aiolia.liens_langues_evenements (id_evenement, id_langue)
        VALUES 
            (v_id_event, v_id_langue_fr),
            (v_id_event, v_id_langue_mg),
            (v_id_event, v_id_langue_en)
        ON CONFLICT DO NOTHING;

        -- Ajouter 3-5 types d'accessibilité
        INSERT INTO aiolia.liens_accessibilite_evenements (id_evenement, id_type_accessibilite, description)
        VALUES 
            (v_id_event, v_id_type_access_wheelchair, 'Accès complet pour fauteuils roulants'),
            (v_id_event, v_id_type_access_hearing, 'Boucle magnétique disponible'),
            (v_id_event, v_id_type_access_visual, 'Assistance pour malvoyants'),
            (v_id_event, v_id_type_access_mobility, 'Rampes et ascenseurs disponibles')
        ON CONFLICT DO NOTHING;

        IF i % 2 = 0 THEN
            INSERT INTO aiolia.liens_accessibilite_evenements (id_evenement, id_type_accessibilite, description)
            VALUES (v_id_event, v_id_type_access_cognitive, 'Personnel formé disponible')
            ON CONFLICT DO NOTHING;
        END IF;

        -- ============================================================
        -- CRÉER 3-4 TYPES DE BILLETS PAR ÉVÉNEMENT
        -- ============================================================
        FOR j IN 1..4 LOOP
            v_prix := (ARRAY[15000, 30000, 60000, 100000])[j];
            
            INSERT INTO aiolia.types_billets (
                id_evenement, id_configuration_categorie, id_configuration_segment,
                nom, description, devise, prix_de_base,
                frais_service, taux_tva,
                ventes_commencent_le, ventes_se_terminent_le,
                minimum_par_commande, maximum_par_commande,
                cree_le, modifie_le
            ) VALUES (
                v_id_event,
                CASE 
                    WHEN j = 1 THEN v_id_cat_standard
                    WHEN j = 2 THEN v_id_cat_standard
                    WHEN j = 3 THEN v_id_cat_vip
                    ELSE v_id_cat_vip
                END,
                CASE WHEN j <= 2 THEN v_id_segment_tous ELSE v_id_segment_adulte END,
                (ARRAY['Standard', 'Premium', 'VIP', 'Platinum'])[j],
                'Billet ' || (ARRAY['Standard', 'Premium', 'VIP', 'Platinum'])[j],
                'MGA',
                v_prix,
                v_prix * 0.1,
                20.0,
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '30 days'),
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '1 hour'),
                1,
                CASE WHEN j >= 3 THEN 4 ELSE 10 END,
                v_date_creation,
                v_date_creation
            ) RETURNING id INTO v_id_type_billet;

            INSERT INTO aiolia.inventaire_billets (id_type_billet, quantite_totale, quantite_reservee, quantite_vendue)
            VALUES (
                v_id_type_billet,
                (ARRAY[200, 150, 80, 40])[j],
                CASE WHEN i <= 18 THEN (5 + j) ELSE 0 END,
                CASE WHEN i <= 18 THEN (50 + (j * 10)) ELSE 0 END
            );

            -- Ajouter 0-4 historiques de prix
            v_nb_historique_prix := floor(random() * 5)::INTEGER;
            v_prix_precedent := v_prix;
            
            FOR k IN 1..v_nb_historique_prix LOOP
                v_prix_precedent := v_prix_precedent * (0.85 + (random() * 0.2));
                
                INSERT INTO aiolia.historique_prix_billets (
                    id_type_billet, modifie_par, prix_precedent, nouveau_prix, 
                    raison, modifie_le
                ) VALUES (
                    v_id_type_billet,
                    v_id_utilisateur_org,
                    v_prix_precedent,
                    CASE WHEN k = v_nb_historique_prix THEN v_prix ELSE v_prix_precedent * 1.1 END,
                    (ARRAY['Ajustement initial', 'Promotion temporaire', 'Ajustement marché', 'Correction tarifaire'])[1 + (k % 4)],
                    v_date_creation + (INTERVAL '1 day' * k)
                );
            END LOOP;

            -- Ajouter une règle de tarification pour certains billets
            IF j = 1 THEN
                INSERT INTO aiolia.regles_tarification (
                    id_type_billet, type_regle, valeur_seuil, valeur,
                    commence_le, se_termine_le
                ) VALUES (
                    v_id_type_billet,
                    'time_window'::pricing_rule_type_enum,
                    NULL,
                    v_prix * 0.8,
                    GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '30 days'),
                    GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '7 days')
                );
            END IF;
        END LOOP;

        -- ============================================================
        -- CRÉER 45-50 BILLETS PAR ÉVÉNEMENT
        -- ============================================================
        v_total_participants := 45 + floor(random() * 6)::INTEGER;
        
        FOR j IN 1..v_total_participants LOOP
            -- Statut des billets (15 annulés au total pour tous les événements)
            IF v_billets_annules_total < 15 AND random() < 0.05 THEN
                v_statut_billet := 'cancelled';
                v_billets_annules_total := v_billets_annules_total + 1;
            ELSIF i <= 9 THEN
                v_statut_billet := 'used';
            ELSE
                v_statut_billet := 'valid';
            END IF;

            -- Sélectionner un utilisateur aléatoire
            SELECT id INTO v_id_utilisateur 
            FROM aiolia.utilisateurs 
            WHERE role = 'user' AND email LIKE 'utilisateur%@yopmail.com'
            ORDER BY RANDOM() 
            LIMIT 1;

            -- Sélectionner un type de billet pour cet événement
            SELECT id INTO v_id_type_billet 
            FROM aiolia.types_billets 
            WHERE id_evenement = v_id_event
            ORDER BY RANDOM() 
            LIMIT 1;

            -- Créer un panier
            INSERT INTO aiolia.paniers (
                id_utilisateur, statut, devise, montant_total, expire_le, cree_le
            ) VALUES (
                v_id_utilisateur,
                'converted'::cart_status_enum,
                'MGA',
                0,
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '5 days'),
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '10 days')
            ) RETURNING id INTO v_id_panier;

            -- Créer la commande
            INSERT INTO aiolia.commandes (
                id_utilisateur, id_panier, statut, montant_total, devise, cree_le
            ) VALUES (
                v_id_utilisateur,
                v_id_panier,
                (CASE WHEN v_statut_billet = 'cancelled' THEN 'cancelled' ELSE 'paid' END)::order_status_enum,
                (SELECT prix_de_base FROM aiolia.types_billets WHERE id = v_id_type_billet),
                'MGA',
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '8 days')
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
                'QR-' || v_id_event || '-' || j || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                md5('QR-' || v_id_event || '-' || j || '-' || EXTRACT(EPOCH FROM NOW())::TEXT),
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '8 days')
            );

            -- Créer facture et paiement pour les billets payés
            IF v_statut_billet != 'cancelled' THEN
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
                    (ARRAY['mvola', 'orange', 'airtel', 'visa'])[1 + (j % 4)],
                    'paid',
                    GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '8 days'),
                    GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '8 days') + INTERVAL '2 hours'
                ) RETURNING id INTO v_id_facture;

                INSERT INTO aiolia.paiements_billets (
                    id_facture, fournisseur, reference_fournisseur,
                    statut, montant, devise, paye_le
                ) VALUES (
                    v_id_facture,
                    (ARRAY['mvola', 'orange', 'airtel', 'visa'])[1 + (j % 4)],
                    'REF-' || v_id_event || '-' || j || '-' || EXTRACT(EPOCH FROM NOW())::TEXT,
                    'paid',
                    (SELECT prix_de_base * 1.3 FROM aiolia.types_billets WHERE id = v_id_type_billet),
                    'MGA',
                    GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - INTERVAL '8 days') + INTERVAL '2 hours'
                );
            END IF;
        END LOOP;

        -- Ajouter 50+ vues pour l'événement dans la table vues_evenements
        v_total_vues := 50 + floor(random() * 30)::INTEGER;
        FOR j IN 1..v_total_vues LOOP
            SELECT id INTO v_id_utilisateur 
            FROM aiolia.utilisateurs 
            WHERE role = 'user' 
            ORDER BY RANDOM() 
            LIMIT 1;

            INSERT INTO aiolia.vues_evenements (
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
                CASE WHEN random() < 0.3 THEN NULL ELSE v_id_utilisateur END,
                ('192.168.' || floor(random() * 255)::INTEGER || '.' || floor(random() * 255)::INTEGER)::INET,
                (ARRAY['Mozilla/5.0', 'Chrome/120.0', 'Safari/17.0', 'Firefox/121.0'])[1 + floor(random() * 4)::INTEGER],
                CASE 
                    WHEN random() < 0.4 THEN 'https://aiolia.mg/events'
                    WHEN random() < 0.7 THEN 'https://aiolia.mg/search'
                    ELSE NULL
                END,
                (ARRAY['page', 'listing', 'search', 'share']::VARCHAR[])[1 + floor(random() * 4)::INTEGER],
                CASE 
                    WHEN random() < 0.2 THEN NULL
                    ELSE 30 + floor(random() * 300)::INTEGER
                END,
                GREATEST('2025-07-01'::TIMESTAMPTZ, v_date_debut - (INTERVAL '1 day' * floor(random() * 20)::INTEGER))
            );
        END LOOP;
    END LOOP;

    -- ============================================================
    -- CRÉER 27 CODES PROMOTIONNELS (30+ utilisateurs par promo)
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
                WHEN i % 2 = 0 THEN (10 + (i % 3) * 5)::NUMERIC
                ELSE (5000 + (i % 4) * 5000)::NUMERIC
            END,
            50 + floor(random() * 51)::INTEGER,
            2 + floor(random() * 3)::INTEGER,
            '2025-07-01'::TIMESTAMPTZ - INTERVAL '30 days',
            CASE 
                WHEN i <= 7 THEN '2025-07-15'::TIMESTAMPTZ
                WHEN i <= 20 THEN '2025-12-31'::TIMESTAMPTZ
                ELSE '2026-06-30'::TIMESTAMPTZ
            END
        ) RETURNING id INTO v_id_code_promo;

        -- Ajouter 30-40 utilisations par code promo
        FOR j IN 1..(30 + floor(random() * 11)::INTEGER) LOOP
            SELECT id INTO v_id_utilisateur 
            FROM aiolia.utilisateurs 
            WHERE role = 'user'
            ORDER BY RANDOM() 
            LIMIT 1;

            SELECT id INTO v_id_commande 
            FROM aiolia.commandes 
            WHERE id_utilisateur = v_id_utilisateur 
            ORDER BY RANDOM() 
            LIMIT 1;

            IF v_id_commande IS NOT NULL THEN
                INSERT INTO aiolia.applications_promotions (
                    id_promotion, id_commande, id_utilisateur, montant_remise, applique_le
                ) VALUES (
                    v_id_code_promo,
                    v_id_commande,
                    v_id_utilisateur,
                    CASE 
                        WHEN i % 2 = 0 THEN (SELECT montant_total * 0.1 FROM aiolia.commandes WHERE id = v_id_commande)
                        ELSE 5000
                    END,
                    '2025-07-01'::TIMESTAMPTZ + (INTERVAL '1 day' * floor(random() * 180)::INTEGER)
                )
                ON CONFLICT DO NOTHING;
            END IF;
        END LOOP;
    END LOOP;

    -- ============================================================
    -- AJOUTER 37 UTILISATEURS EN LISTE D'ATTENTE POUR NOVEMBRE ET DÉCEMBRE
    -- ============================================================
    FOR i IN 1..37 LOOP
        -- Sélectionner un événement de novembre ou décembre
        SELECT id INTO v_id_event 
        FROM aiolia.evenements 
        WHERE id_profil_organisateur = v_id_profil_org 
            AND EXTRACT(MONTH FROM commence_le) IN (11, 12)
            AND EXTRACT(YEAR FROM commence_le) = EXTRACT(YEAR FROM NOW())
        ORDER BY RANDOM() 
        LIMIT 1;

        -- Sélectionner un utilisateur aléatoire
        SELECT id INTO v_id_utilisateur 
        FROM aiolia.utilisateurs 
        WHERE role = 'user' AND email LIKE 'utilisateur%@yopmail.com'
        ORDER BY RANDOM() 
        LIMIT 1;

        IF v_id_utilisateur IS NOT NULL AND v_id_event IS NOT NULL THEN
            -- Créer la liste de souhaits si elle n'existe pas
            INSERT INTO aiolia.listes_souhaits (id_utilisateur, titre, est_par_defaut)
            VALUES (v_id_utilisateur, 'Mes Favoris', TRUE)
            ON CONFLICT DO NOTHING;

            -- Ajouter l'événement à la liste d'attente
            INSERT INTO aiolia.elements_listes_souhaits (id_liste_souhaits, id_evenement, ajoute_le)
            SELECT ls.id, v_id_event, '2025-07-01'::TIMESTAMPTZ + (INTERVAL '1 day' * floor(random() * 180)::INTEGER)
            FROM aiolia.listes_souhaits ls
            WHERE ls.id_utilisateur = v_id_utilisateur AND ls.est_par_defaut = TRUE
            ON CONFLICT DO NOTHING;
        END IF;
    END LOOP;

    RAISE NOTICE '✅ Données de test créées avec succès pour organisateur11@yopmail.com';
    RAISE NOTICE '   - 27 événements créés (date_creation <= date_debut)';
    RAISE NOTICE '   - 5 lieux différents';
    RAISE NOTICE '   - 3-5 types d''accessibilité par événement';
    RAISE NOTICE '   - 45-50 participants par événement';
    RAISE NOTICE '   - 50-80 vues par événement (enregistrées dans vues_evenements)';
    RAISE NOTICE '   - 15 billets annulés au total';
    RAISE NOTICE '   - 27 codes promo avec 30+ utilisations chacun';
    RAISE NOTICE '   - 0-4 historiques de prix par type de billet';
    RAISE NOTICE '   - 37 utilisateurs en liste d''attente pour événements de novembre et décembre';

END $$;