# Nastavení Google Authenticator (2FA)

## Co bylo implementováno

Aplikace nyní podporuje **dvoufázové ověření (2FA)** pomocí Google Authenticator:

- ✅ **Registrace**: Uživatel musí naskenovat QR kód a zadat ověřovací kód pro dokončení registrace
- ✅ **Přihlášení**: Po zadání hesla je vyžadován 6místný kód z Google Authenticator
- ✅ **Změna hesla**: Při změně hesla je vyžadován ověřovací kód z aplikace

## Instalace

### 1. Aktualizace databáze

Spusť v phpMyAdmin jeden z těchto SQL souborů:

**Pro novou instalaci:**
```sql
-- Použij sql/complete_schema.sql
```

**Pro existující databázi:**
```sql
-- Použij sql/add_google_auth.sql
-- Tento soubor pouze přidá sloupec google_auth_secret
```

Nebo spusť přímo v phpMyAdmin:
```sql
ALTER TABLE users ADD COLUMN google_auth_secret VARCHAR(32) NULL AFTER password;
```

### 2. Ověření instalace

1. Otevři `http://localhost/formular/register.php`
2. Vyplň registrační formulář
3. Po odeslání se zobrazí QR kód
4. Naskenuj QR kód do aplikace Google Authenticator (nebo podobné)
5. Zadej 6místný kód pro dokončení registrace

## Použití

### Pro uživatele

1. **Registrace:**
   - Vyplň registrační formulář
   - Naskenuj QR kód do aplikace Google Authenticator
   - Zadej 6místný kód z aplikace
   - Registrace je dokončena

2. **Přihlášení:**
   - Zadej uživatelské jméno/e-mail a heslo
   - Zadej 6místný kód z Google Authenticator
   - Jsi přihlášen

3. **Změna hesla:**
   - V nastavení profilu zadej nové heslo
   - Zadej 6místný kód z Google Authenticator
   - Heslo je změněno

### Aplikace pro 2FA

Doporučené aplikace:
- **Google Authenticator** (iOS/Android)
- **Microsoft Authenticator** (iOS/Android)
- **Authy** (iOS/Android/Desktop)
- Jakákoliv aplikace podporující TOTP (Time-based One-Time Password)

## Technické detaily

- Implementace: **TOTP (RFC 6238)**
- Délka kódu: **6 číslic**
- Časový interval: **30 sekund**
- Secret key: **Base32 encoded, 16 znaků**
- QR kód: Generován přes externí API (qrserver.com)

## Bezpečnost

- Secret key je uložen v databázi (hashovaný by byl lepší, ale pro lokální použití stačí)
- Kódy jsou časově omezené (30 sekund)
- Povolena tolerance ±1 časový interval (pro časovou odchylku)
- Používá se `hash_equals()` pro bezpečné porovnání kódů

## Poznámky

- Pro produkční prostředí zvaž šifrování secret key v databázi
- QR kód je generován přes externí službu - pro produkci použij lokální generování
- Aplikace funguje i bez 2FA pro existující uživatele (pokud nemají secret)

