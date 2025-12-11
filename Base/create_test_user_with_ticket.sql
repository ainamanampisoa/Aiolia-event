-- Création d'un utilisateur de test avec un billet pour "Music on Sunday"
-- Pour tester les notifications de rappel

BEGIN;

SET search_path TO aiolia, public;

-- 1. Créer un utilisateur de test (ou utiliser un existant)
INSERT INTO users (
    email,
    login_identifier,
    login_method,
    password_hash,
    first_name,
    last_name,
    phone,
    country_code,
    language_code,
    timezone,
    role,
    status,
    auth_provider,
    is_email_verified,
    is_phone_verified,
    created_at,
    updated_at
)
VALUES (
    'test@aiolia.com',
    'test@aiolia.com',
    'password',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
    'Test',
    'User',
    '+261320000001',
    'MG',
    'fr-FR',
    'Indian/Antananarivo',
    'user',
    1,
    'password',
    TRUE,
    FALSE,
    now(),
    now()
)
ON CONFLICT (login_identifier, login_method) DO UPDATE
SET 
    email = EXCLUDED.email,
    is_email_verified = TRUE,
    updated_at = now()
RETURNING id;

-- 2. Récupérer l'ID de l'utilisateur (créé ou existant)
WITH test_user AS (
    SELECT id FROM users WHERE email = 'test@aiolia.com'
),
event_data AS (
    SELECT id FROM events WHERE slug = 'concert-music-sunday'
),
ticket_type_data AS (
    SELECT id FROM ticket_types 
    WHERE event_id = (SELECT id FROM event_data)
    LIMIT 1
)
-- 3. Créer une commande payée
INSERT INTO orders (
    user_id,
    status,
    total_amount,
    discount_amount,
    currency,
    created_at,
    updated_at
)
SELECT 
    tu.id,
    'paid',
    84000, -- 80000 + 4000 (service fee)
    0,
    'MGA',
    now(),
    now()
FROM test_user tu
WHERE NOT EXISTS (
    SELECT 1 FROM orders o 
    WHERE o.user_id = tu.id 
    AND o.status = 'paid'
    AND EXISTS (
        SELECT 1 FROM order_items oi
        JOIN ticket_types tt ON tt.id = oi.ticket_type_id
        JOIN events e ON e.id = tt.event_id
        WHERE oi.order_id = o.id
        AND e.slug = 'concert-music-sunday'
    )
)
RETURNING id;

-- 4. Créer un item de commande
WITH test_user AS (
    SELECT id FROM users WHERE email = 'test@aiolia.com'
),
event_data AS (
    SELECT id FROM events WHERE slug = 'concert-music-sunday'
),
ticket_type_data AS (
    SELECT id FROM ticket_types 
    WHERE event_id = (SELECT id FROM event_data)
    LIMIT 1
),
order_data AS (
    SELECT o.id 
    FROM orders o
    JOIN test_user tu ON tu.id = o.user_id
    WHERE o.status = 'paid'
    AND NOT EXISTS (
        SELECT 1 FROM order_items oi
        JOIN ticket_types tt ON tt.id = oi.ticket_type_id
        JOIN events e ON e.id = tt.event_id
        WHERE oi.order_id = o.id
        AND e.slug = 'concert-music-sunday'
    )
    ORDER BY o.created_at DESC
    LIMIT 1
)
INSERT INTO order_items (
    order_id,
    ticket_type_id,
    quantity,
    unit_price,
    service_fee,
    vat_amount,
    total_amount,
    created_at
)
SELECT 
    od.id,
    tt.id,
    1,
    80000,
    4000,
    16800, -- 20% VAT sur 84000
    84000,
    now()
FROM order_data od
CROSS JOIN ticket_type_data tt
WHERE NOT EXISTS (
    SELECT 1 FROM order_items oi
    WHERE oi.order_id = od.id
    AND oi.ticket_type_id = tt.id
);

-- 5. Créer un billet valide
WITH test_user AS (
    SELECT id FROM users WHERE email = 'test@aiolia.com'
),
order_item_data AS (
    SELECT oi.id, oi.ticket_type_id
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN test_user tu ON tu.id = o.user_id
    JOIN ticket_types tt ON tt.id = oi.ticket_type_id
    JOIN events e ON e.id = tt.event_id
    WHERE e.slug = 'concert-music-sunday'
    AND o.status = 'paid'
    AND NOT EXISTS (
        SELECT 1 FROM tickets t WHERE t.order_item_id = oi.id
    )
    LIMIT 1
)
INSERT INTO tickets (
    order_item_id,
    ticket_type_id,
    owner_user_id,
    status,
    qr_code,
    qr_checksum,
    issued_at,
    metadata
)
SELECT 
    oid.id,
    oid.ticket_type_id,
    tu.id,
    'valid',
    'TICKET_' || gen_random_uuid()::text || '_' || encode(gen_random_bytes(8), 'hex'),
    encode(digest('TICKET_' || oid.id::text || '_' || tu.id::text, 'sha256'), 'hex'),
    now(),
    '{"test": true}'::jsonb
FROM order_item_data oid
CROSS JOIN test_user tu;

-- 6. Mettre à jour l'inventaire des billets
UPDATE ticket_inventory
SET sold_quantity = (
    SELECT COUNT(*)
    FROM tickets t
    JOIN order_items oi ON oi.id = t.order_item_id
    JOIN ticket_types tt ON tt.id = t.ticket_type_id
    WHERE tt.id = ticket_inventory.ticket_type_id
    AND t.status = 'valid'
)
WHERE ticket_type_id IN (
    SELECT id FROM ticket_types 
    WHERE event_id = (SELECT id FROM events WHERE slug = 'concert-music-sunday')
);

COMMIT;

-- Vérification
SELECT 
    u.email,
    u.first_name,
    u.last_name,
    e.title AS event_title,
    e.starts_at,
    t.status AS ticket_status,
    o.status AS order_status
FROM users u
JOIN orders o ON o.user_id = u.id
JOIN order_items oi ON oi.order_id = o.id
JOIN tickets t ON t.order_item_id = oi.id
JOIN ticket_types tt ON tt.id = t.ticket_type_id
JOIN events e ON e.id = tt.event_id
WHERE u.email = 'test@aiolia.com'
AND e.slug = 'concert-music-sunday';

