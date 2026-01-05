-- Migration pour le système de budget mensuel
-- Date: 2025-12-31

-- Vérifier si la table user_preferences existe, sinon la créer
CREATE TABLE IF NOT EXISTS aiolia.user_preferences (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id) 
        REFERENCES aiolia.users(id) ON DELETE CASCADE,
    CONSTRAINT unique_user_preference UNIQUE (user_id, preference_key)
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_user_preferences_user_id ON aiolia.user_preferences(user_id);
CREATE INDEX IF NOT EXISTS idx_user_preferences_key ON aiolia.user_preferences(preference_key);

-- Commentaires
COMMENT ON TABLE aiolia.user_preferences IS 'Préférences utilisateur incluant le budget mensuel';
COMMENT ON COLUMN aiolia.user_preferences.preference_key IS 'Clé de la préférence (ex: monthly_budget, notifications, theme)';
COMMENT ON COLUMN aiolia.user_preferences.preference_value IS 'Valeur de la préférence (peut être JSON pour structures complexes)';

-- Exemples de préférences budget pour tests (optionnel)
-- INSERT INTO aiolia.user_preferences (user_id, preference_key, preference_value)
-- VALUES 
--     (1, 'monthly_budget', '500000'),
--     (2, 'monthly_budget', '1000000')
-- ON CONFLICT (user_id, preference_key) DO NOTHING;

-- Vérification
SELECT 
    'user_preferences table created successfully' as status,
    COUNT(*) as existing_preferences
FROM aiolia.user_preferences;


