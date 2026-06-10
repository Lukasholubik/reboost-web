# Reboost Web – Vývojový deník

> **Tento soubor je první co číst.** Každá změna přibyde sem – co bylo uděláno, proč a kde v kódu.
> Nové záznamy přidávej **na začátek** (nejnovější nahoře).

---

## Záznamy

### 2026-06-10 – Oprava ARIA accessibility chyby u Elementor Loop Carouselu

PageSpeed/Lighthouse hlásil chybu u prvků s `role="list"` (Elementor loop carousel,
např. "Případovka" karusel) – chybějící povinné `role="listitem"` u potomků kvůli
Swiperem vkládanému `.swiper-wrapper` bez role.

**Řešení:** přidán JS snippet ve `wp_footer` (functions.php), který u
`.elementor-loop-container.elementor-grid[role="list"]` nastaví `.swiper-wrapper`
na `role="presentation"` a každý `.swiper-slide` na `role="listitem"`. Funguje
i na slidy přidané dodatečně (Swiper loop duplikáty) díky `MutationObserver`.

**Kde:** `wp-content/themes/hello-elementor-child/functions.php` (sekce "PŘÍSTUPNOST").

---

### 2026-06-09 – PageSpeed průběh + pending úkoly

**Stav k 9.6.2026 (evening):**

| Test | Skóre mobil | FCP | LCP | TBT | CLS |
|------|-------------|-----|-----|-----|-----|
| Výchozí stav | 58 | ~4.1 s | ~7+ s | ~40 ms | 0.156 |
| Po self-hosted fontech | 63 | 4.1 s | 4.6 s | ~40 ms | 0.058 |
| Po Imagify + preloadech | ~68 | **1.8 s** | ~4.5 s | 860 ms | 0.059 |

**Co způsobilo skok FCP 4.1 → 1.8 s:** odstranění Google Fonts preconnect + font preload + blokování Elementor GF requestů.

**Nový bottleneck po zlepšení:** TBT 860 ms (JavaScript execution). Hlavní viníci:
- jQuery: 818 ms (nelze odstranit)
- Unattributable: 611 ms
- Google Tag Manager: 373 ms → **opraveno lazy loadem**
- jet-popup-frontend.js: 87 ms
- banner-1-optin.css: 75 ms
- swiper.min.js: 105 ms (22.2 KiB unused)

**Pending – co zbývá řešit (příští session):**
1. Nainstalovat **WP Fastest Cache** (free) nebo **WP Rocket** ($59/rok) – page cache + Critical CSS → odhadovaný gain +5–15 bodů
2. Ověřit zda Imagify převedl `lady-hero-1.png` na WebP → pokud ano, aktualizovat preload URL v functions.php na `.webp`
3. Podmíněně dequeue `jet-popup-frontend.js` na stránkách bez popupů (87 ms TBT)
4. Identifikovat `banner-1-optin.css?v=19` a dequeue kde není potřeba (75 ms)
5. Poppins (post-157/222/1091) + Inter (post-248) – změnit na Archivo Narrow v Elementoru nebo self-hostovat

**Doporučení pro caching plugin:**
- Server: **Apache** (ne LiteSpeed) → LiteSpeed Cache nevhodný
- Free: **WP Fastest Cache** – zapnout jen Cache + Minify HTML/CSS + Combine CSS, JS optimalizace VYPNOUT (máme v functions.php)
- Paid: **WP Rocket** – nejlepší volba, přidá Critical CSS a Remove Unused CSS (~+10–15 bodů navíc)

---

### 2026-06-09 – GTM lazy load (SmartEmailing Connect plugin)

**Soubory:** `wp-content/plugins/smartemailing-connect/includes/GTM/GTM.php`

**Co bylo uděláno:**
- `inject_head()` upraven na lazy load: GTM se načítá až po první interakci uživatele (scroll/click/touch/keydown) nebo po 3 sekundách
- `window.dataLayer` inicializován okamžitě – události pushnuté před načtením GTM se neztrácejí, GA4 data jsou zachována

**Proč:** GTM způsoboval 373 ms blokování hlavního vlákna (TBT) při startu stránky. Lazy load přesune tento čas mimo critical render path.

---

### 2026-06-09 – Performance: preload fontů + LCP hero obrázku + blokování GF requestů

**Soubory:** `wp-content/themes/hello-elementor-child/functions.php`

**Co bylo uděláno:**
1. Odstraněn `preconnect` na `fonts.googleapis.com` + `fonts.gstatic.com` – fonty jsou self-hosted, zbytečné TCP spojení
2. Přidán `<link rel="preload">` pro ArchivoNarrow TTF – browser stáhne font ihned z `<head>` místo až po parsování CSS
3. Přidán `<link rel="preload">` pro LCP obrázek `uploads/2026/04/lady-hero-1.png` – pouze na homepage (`is_front_page()`). Obrázek je CSS `::before` background, browser ho normálně objeví až po parsování CSS → preload zkrátí LCP
4. Přidán `add_filter('elementor/frontend/print_google_fonts', '__return_false')` – zabrání Elementoru generovat `<link>` na fonts.googleapis.com pro Poppins/Inter

**Proč:**
- LCP byl 4.6 s (červená). CSS background obrázky jsou "late-discovered" resources – browser neví o nich dokud neparsuje CSS. Preload to řeší.
- Font preload zkrátí FCP (první obsah na stránce).
- Elementor filter jako pojistka: i když jsou Poppins/Inter změněny na lokální, Elementor mohl stále posílat GF request.

**LCP element:** `body.home > div.elementor > div.elementor-element > ::before` → `uploads/2026/04/lady-hero-1.png` (ověřeno v PageSpeed Insights – zvýrazněno žlutě)

---

## Repozitáře projektu

| Repozitář | URL | Co trackuje |
|-----------|-----|-------------|
| **Web (tento repo)** | `https://github.com/Lukasholubik/reboost-website` | Child theme, .htaccess, docs, site config |
| Emailing Calculator | `https://github.com/Lukasholubik/emailing-calculator` | ROI kalkulačka plugin |
| SmartEmailing Connect | `https://github.com/Lukasholubik/smartemailing-connect` | SE napojení plugin |

---

## Workflow spolupráce

### Git & větve

- **`main`** = stabilní kód – vždy funkční, vždy prošel bezpečnostním auditem. Odpovídá live webu.
- **`dev`** = vývojová větev – sem jdou rozpracované změny před mergem do main.
- **Feature větve** pro větší funkce: `feature/nazev`, `fix/nazev`, `refactor/nazev`
  - Merge do `dev` po otestování, merge do `main` před nasazením na live.
  - Drobné opravy textu/CSS → přímý commit do `dev`, pak merge do `main`.

### Push příkaz

Napíše-li uživatel **"push"** (nebo "pošli", "pushni"), provést bez ptaní:
1. Bezpečnostní audit změněného kódu (viz checklist níže)
2. Opravit všechny nalezené problémy
3. `git add` + `git commit` + `git push`

### Nasazení na live

Napíše-li uživatel **"nasaď na live"**, **"commitni do live"** nebo podobně:
1. Merge `dev` → `main`
2. Push `main` na GitHub
3. Synchronizace s live serverem (postup viz sekce Deployment)

### Bezpečnostní audit před každým pushem

Před každým `git push` zkontrolovat změněné soubory:
- **PHP:** SQL injection (bez `$wpdb->prepare()`), XSS (bez `esc_*`), auth/nonce na AJAX/REST
- **Citlivá data:** žádné API klíče, hesla, secrets v plaintextu (wp-config.php je v gitignore)
- **CSS/JS:** žádné inline eventy nebo XSS vektory
- Pokud najdu problém → opravím před pushem, zapíši do dev-logu

### CSS

**Tailwind CSS** – veškeré nové styly v Tailwindu. Vlastní třídy jen pokud Tailwind nestačí. Inline `style=""` jen pro dynamické hodnoty.

---

## Struktura webu

| Soubor/složka | Popis |
|---------------|-------|
| `wp-content/themes/hello-elementor-child/` | Náš custom child theme (Hello Elementor Child) |
| `.htaccess` | WordPress URL pravidla + vlastní rewrites |
| `wp-config-sample.php` | Šablona konfigurace (bez hesel) |
| `docs/` | Tato dokumentace |

### Pluginy (vlastní – vlastní repos)

| Plugin | Složka | GitHub |
|--------|--------|--------|
| Emailing Calculator | `wp-content/plugins/emailing-calculator/` | [github.com/Lukasholubik/emailing-calculator](https://github.com/Lukasholubik/emailing-calculator) |
| SmartEmailing Connect | `wp-content/plugins/smartemailing-connect/` | [github.com/Lukasholubik/smartemailing-connect](https://github.com/Lukasholubik/smartemailing-connect) |

### Pluginy (třetí strany – netrackujeme)

Instalovány přes WP Admin, verze viz `docs/overview.md`.

---

## Šablona záznamu

```
### RRRR-MM-DD – Stručný popis změny

**Soubory:** `wp-content/themes/hello-elementor-child/functions.php`
**Co bylo uděláno:** ...
**Proč:** ...
**Pozor na:** ... (volitelné)
```

---

## Záznamy

### 2026-06-09 – Self-hosting fontů: ArchivoNarrow + Instrument Sans

**Větev:** `feature/self-hosted-fonts`
**Soubory:** `wp-content/themes/hello-elementor-child/functions.php`

**Co bylo uděláno:**
1. Nahrány variable TTF fonty přes **Elementor → Vlastní Elementy → Fonts**:
   - `ArchivoNarrow`: `ArchivoNarrow-VariableFont_wght.ttf` + `ArchivoNarrow-Italic-VariableFont_wght.ttf`
   - `Instrument Sans`: `InstrumentSans-VariableFont_wdthwght.ttf` + `InstrumentSans-Italic-VariableFont_wdthwght.ttf` (staženy z Google Fonts GitHub)
2. Přidána sekce 7 do `functions.php` – inline `<style id="reboost-local-fonts">` s @font-face pravidly pro:
   - `ArchivoNarrow` (custom font název) + `Archivo Narrow` (Google Fonts název s mezerou) → oba vedou na stejný lokální soubor
   - `Instrument Sans` → lokální TTF
   - Každý font má 3 varianty: normal / italic / oblique, font-weight: 100–900

**Proč:**
- Elementor Custom Fonts generuje @font-face bez `font-weight` range → browser defaultuje na 400, h1–h6 (weight 700 + oblique) se renderovaly špatně
- Globální typografické presety Elementoru používají `"Archivo Narrow"` (s mezerou), náš custom font je `"ArchivoNarrow"` (bez mezery) → přidán @font-face pro oba názvy
- Instrument Sans se načítal z Google Fonts (render-blocking) → nahrazen lokálním souborem

**Stav Google Fonts:** Poppins a Inter zůstávají na Google Fonts (záměrné rozhodnutí, řeší se v budoucnu).

**Bezpečnostní audit:** Prošel – žádné SQL injection, XSS, nebo leaky citlivých dat.

**Pozor na:** Cesta k fontům je hardcoded na `wp-content/uploads/2026/06/` – při migraci nutno zkontrolovat.

---


### 2026-06-09 – Performance optimalizace – mobil (PageSpeed 64 → cíl 85+)

**Soubory:** `.htaccess`, `wp-content/themes/hello-elementor-child/functions.php`
**Co bylo uděláno:**
1. **`.htaccess`** – přidána gzip/deflate komprese (úspora 758 KB na mobilu), browser caching (obrázky 1 rok, CSS/JS 1 měsíc), Keep-Alive, bezpečnostní hlavičky (X-Frame-Options, X-Content-Type-Options)
2. **`functions.php`** – sada výkonnostních optimalizací:
   - Odstraněny emoji skripty (~20 KB JS + CSS zbytečně)
   - Preconnect + DNS prefetch pro Google Fonts, Trustindex CDN
   - Defer non-critical JS (22 skriptů) – vynechány jQuery, Elementor core
   - Odstraněny query strings ze statických souborů (lepší browser cache)
   - Silver partner badge (wp-image-570, 800×800px) – odstraněn `loading="lazy"`, přidán `fetchpriority="high"` (byl LCP kandidát na mobilu)
   - Odstraněny zbytečné WP hlavičky (shortlink, generator, wlwmanifest)

**Proč:** PageSpeed Insights – mobil skóre 64, LCP 6.7s. Největší problémy: chybějící gzip (758KB úspora), render-blocking JS, lazy loading na LCP obrázku.
**Pozor na:** Defer JS – pokud se nějaký plugin rozbije (JS chyby v konzoli), přidat handle do pole `$no_defer` ve functions.php.

---

### 2026-06-09 – Inicializace git repozitáře pro celý web

**Soubory:** `.gitignore`, `docs/dev-log.md`, `docs/overview.md`
**Co bylo uděláno:** Vytvořen git repozitář v kořeni WordPress instalace (`/public`). Nastaveny větve `main` (live) a `dev` (vývoj). Vytvořena dokumentace. Pluginy emailing-calculator a smartemailing-connect zůstávají ve vlastních repozitářích – nejsou součástí web repo.
**Proč:** Umožnit verzování a přehled změn celého webu – nejen pluginů. Child theme a site config dosud nebyly verzovány.
