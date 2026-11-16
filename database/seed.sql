USE voting_system
INSERT INTO users (name,email,password_hash,role)
VALUES ('Admin','admin@example.com','$2y$12$ZIzrpHIU28d4F.Zkl2Tb3OzsCRAvRQ9mNHdBeMO1BwpkXz5W050/a','admin');

INSERT INTO elections (title,start_at,end_at,status)
VALUES ('Student Council 2026','2026-11-23 08:00','2026-11-23 17:00','open');

INSERT INTO positions (election_id,title) VALUES (1,'President'),(1,'Secretary');
INSERT INTO candidates (position_id,name) VALUES
 (1,'Amina Otieno'),(1,'Brian Kamau'),(2,'Carol Njeri'),(2,'David Mwangi');