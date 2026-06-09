# Reboost Web – Přehled architektury

> Přehled celého webu: technologie, pluginy, theme, struktura repozitářů.

---

## Tech stack

| Vrstva | Technologie |
|--------|-------------|
| CMS | WordPress (nejnovější stabilní) |
| Builder | Elementor Pro |
| Theme | Hello Elementor + Hello Elementor Child (náš custom) |
| E-mailing | SmartEmailing (napojení přes SmartEmailing Connect plugin) |
| Bezpečnost | Wordfence, Really Simple SSL, Complianz GDPR |
| Cache | WP Fastest Cache |
| Zálohy | UpdraftPlus |
| SEO | Rank Math SEO |

---

## Repozitáře

```
reboost-website (TENTO REPO)
│
├── wp-content/themes/hello-elementor-child/   ← Custom child theme
├── .htaccess                                   ← URL pravidla
├── wp-config-sample.php                        ← Šablona config
└── docs/                                       ← Dokumentace

Separátní repozitáře:
├── emailing-calculator    → github.com/Lukasholubik/emailing-calculator
└── smartemailing-connect  → github.com/Lukasholubik/smartemailing-connect
```

---

## Větve (branching strategie)

```
main ────────────────────────────► live web
  ↑ merge po otestování
dev ─────────────────────────────► vývoj
  ↑ merge po dokončení funkce
feature/nazev ──────────────────► izolovaná funkce / větší změna
```

- **`main`** – vždy stabilní, nasazený na live serveru
- **`dev`** – aktivní vývoj, testováno lokálně
- **feature větve** – pro větší změny, merge do `dev`

---

## Hello Elementor Child – co tam máme

| Soubor | Popis |
|--------|-------|
| `style.css` | Deklarace child theme + vlastní CSS override |
| `functions.php` | Vlastní PHP funkce, enqueue skriptů, háčky |
| `screenshot.png` | Náhled tématu v WP adminu |

---

## Instalované pluginy (přehled)

### Vlastní pluginy (trackované v separátních repos)

| Plugin | Verze | Popis |
|--------|-------|-------|
| Emailing Calculator | 1.4.0 | ROI kalkulačka pro e-shopy, lead gen |
| SmartEmailing Connect | 1.0.0 | Napojení na SmartEmailing API |

### Pluginy třetích stran (instalovány přes WP Admin, netrackujeme)

| Plugin | Popis |
|--------|-------|
| Elementor | Page builder |
| Elementor Pro | Pro funkce, formuláře |
| Jet Engine | Dynamická data, CPT, relace |
| Jet Blocks | Header/Footer builder |
| Jet Popup | Popup systém |
| Jet Search | Pokročilé vyhledávání |
| Jet Smart Filters | Filtry |
| Jet Theme Core | Podmínky zobrazení |
| Wordfence | Security scanner, firewall |
| Really Simple SSL | HTTPS přesměrování |
| Complianz GDPR | Cookie consent |
| Rank Math SEO | SEO optimalizace |
| WP Fastest Cache | Caching |
| UpdraftPlus | Zálohy |
| WPS Hide Login | Změna URL loginu |
| Duplicate Page | Duplikace stránek |

---

## Deployment na live server

> TODO – doplnit po nastavení produkčního serveru
> (FTP/SFTP přístup, nebo git pull na serveru)

---

## wp-config.php

Soubor `wp-config.php` je v `.gitignore` – obsahuje DB přihlašovací údaje, auth klíče a salts.

Pro produkci: nakopírovat `wp-config-sample.php`, vyplnit hodnoty.

Příklady citlivých konstant které **nikdy nejdou do gitu:**
- `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`
- `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, ...
- `WP_DEBUG` (může odhalovat cesty a chyby)
