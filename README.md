# Modern Auth – Stylový login & registrace v PHP

![Logo](img/logo.png)

**Modern Auth** je čistá, rychlá a vizuálně působivá ukázka moderního přihlašovacího a registračního systému v PHP + MySQL. Vyniká temným cyberpunkovým designem, falešnou Cloudflare captchou a perfektně hladkou uživatelskou zkušeností.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0+-purple.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)](https://www.mysql.com/)

## Co to umí

- Plně funkční registrace s ověřením hesla a kontrolou podmínek  
- Bezpečné přihlášení přes session  
- Hashování hesel pomocí `password_hash()` / `password_verify()`  
- Fake Cloudflare captcha (jen pro design – po kliknutí se aktivuje tlačítko a už nejde odškrtnout)  
- Krásný temný design s červenými akcenty  
- 100 % responzivní – vypadá skvěle na mobilu i počítači  
- Čistý kód s PDO a prepared statements  
- Jednoduchá homepage pro přihlášené uživatele  

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
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
5. Otevři v prohlížeči:  
   `http://localhost/register.php`

Hotovo! Můžeš se registrovat a přihlašovat.

## Struktura projektu

```
formular/
├── inc/
│   └── config.php
├── style/
│   └── style.css
├── script/
│   └── script.js
├── img/
│   └── logo.png
├── register.php
├── login.php
├── index.php        ← homepage po přihlášení
├── logout.php
└── README.md
```

## Proč je to cool?

- Žádný Bootstrap – všechno ručně napsané CSS  
- Perfektně fungující fake captcha (nelze odškrtnout zpět)  
- Moderní vzhled, který zaujme na první pohled  
- Ideální jako základ pro školní projekt, portfolio nebo inspirace  

## Autor

**Vita Phone**  
Frontend | Backend | Design  
[![GitHub](https://img.shields.io/badge/GitHub-000000?style=flat&logo=github&logoColor=white)](https://github.com/VitaPhoneCZ)  

> Fake Cloudflare captcha? Ano. Ale funguje to skvěle.

---

**Líbí se ti tenhle styl? Dej hvězdičku – uděláš mi radost!**

Made with passion in Czech Republic
