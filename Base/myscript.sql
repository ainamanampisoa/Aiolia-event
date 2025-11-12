--Data
psql -h localhost -U aiolia_user -d aiolia_event -f /home/aina/Documents/MyProject/Aiolia-event/Base/mydata.sql
---Requete

psql -h localhost -U aiolia_user -d aiolia_event -c "SELECT id, email, created_at FROM aiolia.users ORDER BY id LIMIT 5;"
psql -h localhost -U aiolia_user -d aiolia_event -c "SELECT COUNT(*) FROM aiolia.events;"

SELECT op.id, u.email, op.display_name
FROM aiolia.organizer_profiles op
JOIN aiolia.users u ON u.id = op.user_id
ORDER BY op.id DESC LIMIT 5;