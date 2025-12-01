-- Přidání sloupce pro Google Authenticator secret
ALTER TABLE users ADD COLUMN google_auth_secret VARCHAR(32) NULL AFTER password;

