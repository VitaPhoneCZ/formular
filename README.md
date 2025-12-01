# Modern Auth – Stylový login & registrace v PHP

![Logo](img/logo.png)

**Modern Auth** je čistá, rychlá a vizuálně působivá ukázka moderního přihlašovacího a registračního systému v PHP + MySQL. Vyniká temným cyberpunkovým designem, **skutečným Google Authenticator 2FA** a perfektně hladkou uživatelskou zkušeností.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0+-purple.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)](https://www.mysql.com/)

## Co to umí

- **Plně funkční registrace** s ověřením hesla, e-mailu a kontrolou podmínek  
- **Bezpečné přihlášení** přes session s podporou uživatelského jména nebo e-mailu  
- **Google Authenticator 2FA** – dvoufázové ověření pomocí TOTP (RFC 6238)  
- **Hashování hesel** pomocí `password_hash()` / `password_verify()`  
- **Dashboard** s přehledem statistik, e-mailu a bezpečnostních informací  
- **Správa profilu** – úprava jména, e-mailu, hesla a avatara  
- **Automatické generování avatarů** pomocí DiceBear API  
- **Fake Cloudflare captcha** (jen pro design – po kliknutí se aktivuje tlačítko)  
- **Krásný temný design** s červenými akcenty  
- **100 % responzivní** – vypadá skvěle na mobilu i počítači  
- **Čistý kód** s PDO a prepared statements  

## Instalace (XAMPP / lokální server)

1. Naklonuj nebo stáhni projekt  
2. Rozbal do složky v `htdocs` (např. `formular`)  
3. Spusť XAMPP → Apache + MySQL  
4. V phpMyAdmin spusť tento SQL:

```sql
CREATE DATABASE IF NOT EXISTS user_system;
USE user_system;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    google_auth_secret VARCHAR(32) NULL,
    avatar_url VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
5. Otevři v prohlížeči:  
   `http://localhost/formular/register.php`

**Poznámka:** Při registraci budeš muset naskenovat QR kód do aplikace Google Authenticator (nebo podobné) a zadat 6místný ověřovací kód pro dokončení registrace.

Hotovo! Můžeš se registrovat a přihlašovat.

## Struktura projektu

```
formular/
├── inc/
│   ├── config.php
│   └── GoogleAuthenticator.php
├── style/
│   └── style.css
├── script/
│   └── script.js
├── img/
│   └── logo.png
├── register.php      ← registrace s 2FA
├── login.php         ← přihlášení s 2FA
├── index.php         ← dashboard po přihlášení
├── profile.php       ← správa profilu
├── logout.php
└── README.md
```

## Proč je to cool?

- **Žádný Bootstrap** – všechno ručně napsané CSS  
- **Skutečná 2FA** – Google Authenticator integrace s TOTP  
- **Kompletní dashboard** – statistiky, e-mail, bezpečnostní informace  
- **Správa profilu** – úprava všech údajů včetně avatara  
- **Moderní vzhled** – temný cyberpunkový design, který zaujme  
- **Bezpečnost na prvním místě** – hashování hesel, 2FA, prepared statements  
- **Ideální jako základ** pro školní projekt, portfolio nebo inspirace  

## Autor

**Vita Phone**  
Frontend | Backend | Design  
[![GitHub](https://img.shields.io/badge/GitHub-000000?style=flat&logo=github&logoColor=white)](https://github.com/VitaPhoneCZ)  

> Fake Cloudflare captcha? Ano. Ale funguje to skvěle.

---

**Líbí se ti tenhle styl? Dej hvězdičku – uděláš mi radost!**

Made with passion in Czech Republic
