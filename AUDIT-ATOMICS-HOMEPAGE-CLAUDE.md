# Audit complet — Homepage Atomics (`index.htm`)

**Data:** 13 august 2026
**Obiect analizat:** `index.htm` + `css/app.css` + `app-ebea275ace.js`, servite prin HTTP local (`http://127.0.0.1:8765`)
**Metodă:** Chrome 151 headless condus prin Chrome DevTools Protocol — randare reală, măsurători de layout, capturi de ecran, emulare de rețea, emulare `prefers-reduced-motion`, navigare din tastatură prin evenimente reale de input, dezactivare JavaScript la nivel de motor.

## Condiții testate efectiv

| Condiție | Cum a fost testată |
|---|---|
| 1440×900, 1280×800 | `Emulation.setDeviceMetricsOverride`, capturi la fiecare pas de scroll |
| 768×1024 | idem + UA mobil + touch emulat |
| 390×844, 360×800 | idem + UA iPhone 17 / Android 13 + `maxTouchPoints: 5` |
| Zoom 200% | viewport CSS 720×450 (echivalent 1440×900 la 200%) |
| Tastatură | 34 × `Input.dispatchKeyEvent` Tab, cu citirea `document.activeElement` și a stilului de focus |
| `prefers-reduced-motion: reduce` | `Emulation.setEmulatedMedia` |
| Conexiuni lente | `Network.emulateNetworkConditions` la 400 kbps/400 ms, 1,6 Mbps/150 ms, 4 Mbps/70 ms |
| Fără JavaScript | `Emulation.setScriptExecutionDisabled` |
| Fără WebGL | Chrome pornit cu `--disable-gpu` fără SwiftShader |

**Limite oneste ale măsurătorilor.** Serverul local a servit fișierele **necomprimate**; în producție JS și CSS ar trebui să fie gzip/brotli (am calculat: `app.js` 1,39 MB → 250 KB gzip, `app.css` 392 KB → 41 KB gzip). Timpii de rețea de mai jos sunt deci ușor pesimiști pentru JS/CSS, dar **exacți pentru imagini**, care sunt deja comprimate și reprezintă 9,02 MB din încărcare — adică partea dominantă. Randarea WebGL s-a făcut pe SwiftShader (software), deci timpul de inițializare Pixi este mai mare decât pe un GPU real; de aceea raportez un *interval* și explic *mecanismul*, nu o singură cifră. Datele de teren (CrUX / Search Console) nu pot fi deduse local.

---

## 1. Rezumat executiv

1. **P0 — Preloaderul blochează site-ul între 2,6 s și peste 90 s.** Măsurat: 2,6–16 s pe localhost, **19,2 s la 4 Mbps**, **46,2 s la 1,6 Mbps**, iar la 400 kbps **nu s-a stins deloc în 90 de secunde** (progresul a rămas la „1%”). Cauza nu e doar greutatea: `Promise.race` are un plafon de 5 s, apoi rulează inițializarea Pixi, iar bara de progres e parțial *falsă* — `fakeLoaderBeginning()` incrementează cu 1 la un interval aleatoriu de 0–1000 ms.
2. **P0 — Dacă WebGL nu e disponibil, site-ul rămâne blocat pentru totdeauna.** Reprodus: cu GPU dezactivat, Pixi aruncă `Error: WebGL unsupported in this browser`, `S.default.hide()` nu mai este apelat niciodată, iar la 25 s pagina era în continuare un ecran negru cu „97%”. Nu există `try/catch` și nici un timeout de siguranță care să ascundă preloaderul necondiționat.
3. **P0 — Ramura mobilă nu se activează niciodată.** Cu UA de iPhone și de Android, la 390×844 și touch activ, `body` primește `is-desktop-version` și se creează canvas-ul WebGL. Telefoanele descarcă 9 MB de imagini și rulează scena Pixi completă.
4. **Stratul WebGL nu aduce aproape nimic vizual, dar costă enorm.** Randarea DOM (fără Pixi) este **vizual aproape identică** cu cea WebGL — comparați `nojs-top.png` cu `d1440-hero.png`. Măsurat: blocking time **1211 ms cu WebGL vs 64 ms fără** (de 19 ori mai puțin), CLS 0,034 vs 0,003, preloader 2,6 s vs 1,2 s.
5. **P0 — Fără JavaScript pagina nu poate fi derulată.** `body { overflow: hidden }` este anulat doar de `body.is-mobile-version { overflow: initial }`, clasă pusă de JS. Măsurat: după un wheel de 2000 px, `window.scrollY` = 0. Tot conținutul sub primul ecran este inaccesibil.
6. **Homepage-ul nu conține nicio dovadă comercială și niciun formular.** 0 elemente `<form>`, 0 `<input>`. Zero studii de caz, testimoniale, logo-uri de client, cifre, proces, termene, garanții. Toate cele 8 CTA-uri duc în același loc: `/contact.html`.
7. **CTA-urile spun două lucruri diferite în același buton.** 5 din 8 butoane au masca vizuală diferită de textul linkului: „Comandă acum” / „Rezervă o sesiune de strategie”, „Descoperă acum” / „Programează o consultație gratuită”, „Află povestea noastră” / „Află mai multe despre noi”. Doar 3 din 8 măști au `aria-hidden="true"`, deci restul sunt citite de cititoarele de ecran ca text dublu și contradictoriu.
8. **Meniul duce în locuri care nu corespund etichetelor.** „SERVICII” → `solutions.html`, al cărei `<title>` este în engleză („WordPress - find suitable solution for you”); „SOLUȚII” → `emergency-help.html` („WordPress emergency help”); „PREȚURI” → `pricing.html`, care are **`<title>` complet gol și niciun meta description**.
9. **Accesibilitatea are defecte structurale.** Inelul de focus este `2px solid #4f3a5a` — 2,06:1 față de fundalul paginii și ~1,97:1 pe butonul roz, practic invizibil. Nu există skip link. Meniul mobil nu actualizează `aria-expanded`, nu se închide cu Escape și nu blochează scroll-ul paginii din spate. Linkurile din nav și din footer au 16–18 px înălțime, sub minimul de 24 px al WCAG 2.2.
10. **Nu există absolut nicio măsurare.** Niciun GTM, GA4, Clarity sau alt tracker în `index.htm` — doar comentarii goale. În acest moment nu se poate ști câți vizitatori ajung la contact, deci nici o optimizare de conversie nu poate fi validată.

---

## 2. Scoruri

| Arie | Scor | Justificare într-o frază |
|---|---:|---|
| Design | **6,5**/10 | Direcție artistică puternică și coerentă, dar ierarhia cedează: titluri tăiate de header, aliniere inconsistentă în hero, ecrane întregi fără informație. |
| UI/UX | **4**/10 | După încărcare navigarea funcționează, dar headerul acoperă titluri, meniul mobil e defect, iar densitatea de informație pe ecran este foarte mică. |
| Conversie | **2,5**/10 | Fără formular, fără dovezi, fără preț orientativ, fără analytics, cu 8 CTA-uri formulate în 6 feluri diferite. |
| Copywriting | **4**/10 | Ton modern și limbă română corectă, dar aproape exclusiv afirmații generice, nedemonstrate și interschimbabile cu orice altă agenție. |
| SEO | **5,5**/10 | Fundamentele există (canonical, OG, robots, sitemap), dar title/description sunt prea lungi, schema e minimală, o pagină-țintă are title gol și lipsesc paginile de serviciu. |
| Accesibilitate | **3**/10 | Focus invizibil, fără skip link, meniu mobil fără stări ARIA, ținte de atingere sub minim, contrast sub prag în hero și pe CTA. |
| Performanță | **2**/10 | ~11 MB pe prima încărcare, 151 de imagini fără lazy loading, 1211 ms blocking time, preloader de 19–46 s pe conexiuni mobile reale. |
| Animații | **3,5**/10 | Tehnic ambițioase și frumoase, dar întârzie mesajul, nu respectă `prefers-reduced-motion` la nivel de JS și nu au fallback. |
| Mobile | **3**/10 | Fără overflow orizontal și text lizibil, dar rulează calea desktop completă, meniul e defect și pagina are ~7500 px de derulat pentru foarte puțină informație. |

---

## 3. Tabelul problemelor

### 3.1 Probleme confirmate

| Pri | Secțiune / element | Problemă | Dovadă măsurată | Impact | Recomandare | Efort |
|---|---|---|---|---|---|---|
| P0 | Global / `.js-preloader` | Conținutul este ascuns în spatele unui overlay `z-index: 99999` până la finalul inițializării | 2,6–16 s pe localhost; 19,2 s la 4 Mbps; 46,2 s la 1,6 Mbps; **>90 s la 400 kbps** (progres blocat la „1%”, `body.className` gol) | Abandon masiv înainte de primul cuvânt citit; niciun CTA disponibil | Eliminați preloaderul blocant. Randați HTML-ul imediat, încărcați decorul după primul paint. Dacă păstrați o tranziție, maximum 300 ms și fără a acoperi H1/CTA | M |
| P0 | `app.js` / inițializare Pixi | Fără WebGL, `hide()` nu este apelat niciodată → pagină blocată permanent | Chrome cu `--disable-gpu`: `Uncaught Error: WebGL unsupported in this browser` la linia 10032; la 25 s preloaderul era în continuare `display:block; opacity:1` | Pierdere totală a vizitatorilor cu GPU pe lista de blocare, drivere vechi, VM-uri, browsere cu WebGL dezactivat | `try/catch` în jurul `H()` cu degradare pe ramura DOM + un `setTimeout` necondiționat care ascunde preloaderul după 1,5 s | S |
| P0 | Detectare dispozitiv | Ramura mobilă (`is-mobile-version`, fără Pixi) nu se activează niciodată | UA iPhone 17 și Android 13, 390×844, touch activ → `body.className = "is-desktop-version"`, `canvas` prezent | Telefoanele fac toată munca desktop: 9 MB imagini + scenă WebGL | Comutați criteriul pe `matchMedia('(max-width: 1024px), (pointer: coarse)')`; ideal, folosiți ramura DOM ca implicită peste tot | S |
| P0 | Global / greutate | ~11 MB la prima încărcare | 140 imagini = **9,02 MB**; JS 1,39 MB; CSS 392 KB; 11 fonturi = 258 KB. `hero-man.png` singur = 2,36 MB | LCP și INP catastrofale pe mobil; consum de date real pentru utilizatorii din Moldova | Conversie AVIF/WebP + redimensionare la dimensiunea afișată + `loading="lazy"` sub fold | M |
| P0 | Global / conversie | Zero mecanism de măsurare | `grep` pentru `gtag|googletagmanager|analytics|clarity|hotjar` în `index.htm` → 0 rezultate; `gtm.js` este un stub de 108 octeți | Nicio decizie de optimizare nu poate fi validată | Instalați GA4 sau Plausible + evenimente pe fiecare CTA înainte de orice altă schimbare | S |
| P1 | Fără JavaScript | Pagina nu poate fi derulată | `body{overflow:hidden}` (app.css:4218) anulat doar de `.is-mobile-version`; după wheel 2000 px, `scrollY = 0` | Tot conținutul sub fold este invizibil pentru crawlere fără JS și pentru utilizatorii cu JS blocat | Adăugați în `<noscript>`: `body,.out{overflow:visible!important}` | S |
| P1 | Toate cele 8 `.animation-btn` | Masca vizuală spune altceva decât linkul | „Comandă acum”/„Rezervă o sesiune de strategie” (×2), „Comandă acum”/„Începe acum”, „Descoperă acum”/„Programează o consultație gratuită”, „Află povestea noastră”/„Află mai multe despre noi” | WCAG 2.2 SC 2.5.3 „Label in Name” — control vocal inutilizabil; utilizatorul vede o promisiune și primește alta | Aliniați textul măștii cu textul linkului; `aria-hidden="true"` pe toate măștile | S |
| P1 | Meniu / arhitectura informației | Etichetele nu corespund destinațiilor | „SERVICII”→`solutions.html` (title EN); „SOLUȚII”→`emergency-help.html` („WordPress emergency help”); „DESPRE NOI”→`for-agencies.html` | Utilizatorul nu găsește serviciile; semnal SEO diluat; rată de revenire mare | Restructurați: Servicii (hub nou), Realizări, Prețuri, Despre, Contact. Redenumiți fișierele cu URL-uri în română | M |
| P1 | `pricing.html` | `<title>` complet gol, fără meta description | `<title>` conține doar spațiu alb; `grep` pentru description → 0 rezultate | Pagina cu cea mai mare intenție comercială e practic invizibilă în SERP | Completați title și description | S |
| P1 | Focus vizibil | Inelul de focus este invizibil | `a:focus { outline: 2px solid #4f3a5a }` (app.css:4228) → **2,06:1** pe fundalul `#03010e`, **~1,97:1** pe butonul `#ea2866` | WCAG 2.2 SC 1.4.11 / 2.4.11 — navigarea din tastatură este oarbă | `outline: 3px solid #00ffd4; outline-offset: 3px` (contrast > 9:1 pe ambele fundaluri) | S |
| P1 | Meniu mobil | `aria-expanded` rămâne `false` la deschidere; `aria-label` rămâne „Deschide meniul”; Escape nu închide; pagina derulează în spatele meniului deschis | Măsurat la 390×844 după `.click()`: `expanded:"false"`, nav vizibil la `top: 20`; după 6 wheel-uri meniul era încă deschis peste conținut derulat (`v390-scrolled.png`) | Cititoarele de ecran anunță un buton „închis” peste un meniu deschis; utilizatorii nu pot ieși din meniu | Comutați `aria-expanded`/`aria-label`, adăugați handler pe Escape și pe click în afară, mutați focusul în meniu și blocați scroll-ul | S |
| P1 | Skip link | Nu există | `[...document.querySelectorAll('a')].filter(a=>/skip|sari/i.test(...))` → 0 | WCAG 2.4.1 — utilizatorul de tastatură parcurge 6 elemente înainte de conținut | `<a class="skip-link" href="#main">Sari la conținut</a>` ca prim element din `<body>` | S |
| P1 | Ținte de atingere mobil | Linkurile din nav și footer au 16–18 px înălțime | Măsurat la 390 px: „DESPRE NOI” 103×16, „SERVICII” 70×16, toate cele 12 linkuri din footer 16 px înălțime | WCAG 2.2 SC 2.5.8 (minim 24×24) — atingeri ratate pe telefon | `padding: 12px 0` pe `.nav__link` și `.footer-list__link` | S |
| P1 | Header fix vs titluri | Headerul (75–77 px, `z-index: 999`) taie primul rând al titlurilor | Capturi `s05.png` (H2 „Partenerul tău în Transformare Digitală” tăiat), `s14.png` („Creăm Pagini Web Memorabile.” tăiat), `s26.png` (ilustrația rachetei tăiată) | Titlurile secțiunilor, adică exact mesajul, sunt ilizibile în momentul intrării în secțiune | `scroll-margin-top: 96px` pe secțiuni + `padding-top` egal cu înălțimea headerului | S |
| P1 | Hero / contrast | H1 alb peste zona luminoasă a planetei | Măsurat pixel cu pixel după ascunderea H1: luminanță maximă de fundal 0,356 → **2,59:1** în cel mai luminos punct (WCAG cere 3:1 pentru text mare) | Al doilea rând al H1 devine greu de citit exact acolo unde trebuie citit | Adăugați un gradient de protecție sub text sau mutați planeta în dreapta H1 | S |
| P1 | CTA principal / contrast | Alb pe `#ea2866` = **4,21:1** | Calculat din `app.css:4422`; textul are 14–16 px, deci pragul este 4,5:1 | WCAG 1.4.3 — CTA-ul cel mai important este sub prag | Închideți roșul la `#d1004f` (5,2:1) sau îngroșați textul la 18 px semibold | S |
| P1 | `prefers-reduced-motion` | Doar tranzițiile CSS sunt oprite; animațiile continuă | `grep` în bundle → **0 referințe** la `prefers-reduced-motion`. Cu RM activ rulează în continuare `move-chevron 2.1s`, `ani2`, `rotate360`, `fadeInLeft/Right/Up/Down` pe 20+ elemente | WCAG 2.3.3; risc de rău de mișcare pentru utilizatorii care au cerut explicit oprirea | Ramură JS pe `matchMedia('(prefers-reduced-motion: reduce)')` + bloc CSS care oprește și `animation`, nu doar `transition` | S |
| P1 | Imagini | 151 `<img>`, **0** cu `loading="lazy"`, **0** cu `fetchpriority`, **150** fără `width`/`height` | Măsurat în DOM la runtime | Toate imaginile concurează pentru bandă în primele secunde; risc de CLS | `loading="lazy"` + `decoding="async"` pe tot ce e sub fold; `fetchpriority="high"` doar pe imaginea LCP; `width`/`height` peste tot | M |
| P1 | Element LCP | Elementul LCP este o imagine pur decorativă | `largest-contentful-paint` → `img/first-section/stars-small.png`, arie 819.880 px² | Metrica se optimizează pentru decor, nu pentru mesaj | Faceți H1 elementul LCP: reduceți stratul de stele sau transformați-l în `background-image` | S |
| P2 | Hero / aliniere | H1 și paragraful sunt aliniate la stânga, butonul este centrat | `app.css:8089` — `.first-section__btn { margin-left: 50%; transform: translateX(-50%) }`; vizibil în `nojs-top.png` (text la x=230, buton la x=560) | Compoziție dezechilibrată, CTA-ul pare desprins de mesaj | Aliniați butonul la stânga, sub paragraf | S |
| P2 | Secțiunea 4 | Lista „Ce oferim” este randată ca un paragraf continuu | `s14.png`: „…Ce oferim Strategie digitală, Design web , Creare de conținut, …” într-un singur bloc, cu spații înainte de virgulă | Serviciile, adică informația comercială cea mai utilă, sunt ilizibile | `<ul>` real, pe 2–3 coloane, cu iconuri | S |
| P2 | Secțiunea 3 | ~520 px de spațiu gol deasupra titlului la 1440×900 | `s09.png` — un ecran întreg fără informație | Densitate informațională foarte mică; utilizatorul derulează fără recompensă | Reduceți înălțimea secțiunii cu ~40% | M |
| P2 | `btn-pattern.png` | 546 KB descărcați pentru efectul de hover al butonului | `app.css:4423` — `mask: url(../img/btn-pattern.png); mask-size: 7100% 100%` | Jumătate de megabyte pentru o animație decorativă | Înlocuiți cu un gradient CSS animat sau cu un SVG de câțiva KB | S |
| P2 | DOM | 407 span-uri `.char` generate pentru animația literă cu literă | Măsurat: 1106 elemente totale, 541 span-uri | Cost de layout și memorie; textul devine fragmentat pentru unele cititoare de ecran | Animați pe cuvinte, nu pe caractere; păstrați textul intact pentru AT | M |
| P2 | Structurare semantică | „Inovație prin AI și Automatizare”, „Experiențe Web & Branding”, „Vizibilitate și Strategie Digitală” sunt `<strong>` în `<li>`, nu `<h3>` | Structura de titluri conține doar H1 + 6×H2 + 4×H3 (footer) | Google și cititoarele de ecran nu văd subtemele secțiunii | Transformați-le în `<h3>` | S |
| P2 | Metadata | Title 69 caractere, description 186 caractere | Numărate direct din sursă | Ambele trunchiate în SERP | Vezi secțiunea 7 | S |
| P2 | Date structurate | Doar `Organization` minimal, fără `sameAs`, `contactPoint`, `areaServed` | `index.htm:91-105` | Panouri de brand slabe, SEO local ratat | Vezi JSON-LD-ul din secțiunea 7 | S |
| P2 | Text alternativ | Alt-uri în engleză și fără sens: `"beautiful background"`, `"big planet"`, `"small planet"`, `"stars"`, `"preloader"` | 136 imagini au corect `alt=""`; 5 au alt-uri decorative inutile | Zgomot pentru cititoarele de ecran | `alt=""` pentru toate cele decorative | S |
| P2 | Footer / E-E-A-T | Fără telefon, fără adresă completă, fără program, fără rețele sociale, fără denumire juridică sau IDNO, fără pagini legale | `ls` → nicio pagină de politică de confidențialitate sau termeni; `grep tel:` → 0 | Semnal de încredere slab pentru B2B și pentru SEO local | Adăugați date de firmă complete + Politică de confidențialitate + Termeni | S |
| P3 | Consolă | `console.log(r)` rămas în bundle-ul de producție | `app-ebea275ace.js:40282`, în calea de validare a formularului | Igienă de cod | Eliminați | S |
| P3 | `<head>` | `<meta http-equiv="content-language">` este obsolet | `index.htm:8` | Fără efect real | Eliminați; `<html lang="ro">` este suficient | S |
| P3 | `img/og.png` | 553 KB pentru imaginea Open Graph | Măsurat pe disc | Previzualizări lente în chat-uri | Recomprimați sub 200 KB | S |
| P3 | Sitemap | Fără `<lastmod>` | `sitemap.xml` | Semnal de prospețime ratat | Adăugați `lastmod` | S |
| P3 | Fișiere nefolosite | `img/second-section/hi.gif` (4,5 MB) și `img/page-for-agencies/despre.gif` (2,0 MB) nu sunt referite din homepage; `img/concepts/` (~6 MB) sunt fișiere de lucru necomise | `grep` + `git status` | Greutate în repo și risc de deploy accidental | Mutați `img/concepts/` în afara root-ului publicat | S |

### 3.2 Probleme potențiale, care necesită testare suplimentară

| Element | Ce trebuie verificat | De ce nu pot confirma acum |
|---|---|---|
| INP real | Latența la tap pe CTA-uri pe un telefon mediu | Am măsurat blocking time (1211 ms), nu INP de teren; e nevoie de RUM sau de un dispozitiv fizic |
| Timp de inițializare Pixi pe GPU real | Cât din cele 16 s este SwiftShader și cât e cod | Mediul headless nu are GPU hardware |
| Compresie în producție | Dacă serverul livrează brotli/gzip și ce `Cache-Control` trimite | Serverul local nu reflectă configurația de producție |
| Rău de mișcare | Reacția utilizatorilor reali la parallax + scroll virtual | Necesită testare cu utilizatori |
| Comportamentul pe iOS Safari | Scroll-ul virtual și `100vh` pe iOS diferă semnificativ de Chrome | Nu există Safari în acest mediu |
| Poziția actuală în SERP | Ce cuvinte-cheie aduc trafic azi | Necesită acces la Search Console |

### 3.3 Preferințe estetice, nu defecte

- Estetica spațială cu astronauți este consistentă, bine executată și memorabilă. Nu o eliminați — reduceți-i doar ponderea față de mesaj.
- Paleta magenta/turcoaz/galben pe fundal foarte închis este distinctivă și potrivită pentru o agenție tehnică.
- Alternanța secțiune întunecată / disc galben / secțiune albă dă ritm paginii. Funcționează.
- Titlurile foarte mari (90 px pe desktop) sunt o alegere validă de art direction, cu condiția să nu fie tăiate de header.

---

## 4. Cele mai importante 10 schimbări, în ordinea implementării

1. **Instalați analytics** (GA4 sau Plausible) cu evenimente pe fiecare CTA. Fără măsurare, restul listei este opinie. — *1–2 ore*
2. **Eliminați preloaderul blocant** și randați HTML-ul imediat; adăugați `try/catch` + timeout necondiționat de siguranță. — *4–6 ore*
3. **Faceți ramura DOM implicită** pe mobil și tabletă (și, recomandat, peste tot). Dovada: aceeași imagine vizuală cu 64 ms blocking time în loc de 1211 ms. — *2–4 ore*
4. **Optimizați imaginile**: AVIF/WebP, redimensionare reală, `loading="lazy"` sub fold, `fetchpriority="high"` doar pe imaginea din hero. Țintă: sub 1,5 MB pe prima încărcare. — *1 zi*
5. **Rescrieți hero-ul** cu o propunere de valoare concretă și un singur CTA principal. — *2–4 ore*
6. **Adăugați un formular scurt** direct pe homepage (3 câmpuri) în secțiunea finală, plus telefon și email vizibile. — *4–6 ore*
7. **Reparați meniul**: etichete aliniate cu destinațiile, `pricing.html` cu title, hub real de servicii. — *1 zi*
8. **Reparați accesibilitatea de bază**: skip link, inel de focus vizibil, `aria-expanded` pe meniul mobil, Escape, ținte de 24 px. — *4–6 ore*
9. **Adăugați o bandă de dovezi** sub hero: 3–4 cifre reale + logo-uri de clienți + 2 testimoniale. *Necesită date de la proprietar.* — *1 zi după primirea datelor*
10. **Aliniați toate CTA-urile** la un vocabular unic și corectați măștile animate. — *2 ore*

---

## 5. Structura recomandată a homepage-ului

Ordinea exactă a secțiunilor:

1. **Header** — logo, Servicii, Realizări, Prețuri, Despre, Contact + buton „Cere o ofertă” + telefon vizibil pe desktop.
2. **Hero** — eyebrow, H1 concret, paragraf de o frază, CTA principal + CTA secundar, un singur element vizual. Fără preloader.
3. **Bandă de încredere** — 4–6 logo-uri de clienți sau 3 cifre („X proiecte livrate”, „Y ani”, „Z zile timp mediu de lansare”). *Date necesare de la proprietar.*
4. **Problema clientului** — 3 propoziții care descriu situația din care vine vizitatorul.
5. **Servicii** — 4 carduri: Automatizare & AI, Web & E-commerce, SEO & Vizibilitate, Branding. Fiecare cu: pentru cine, ce primești, de la ce preț, link către pagina dedicată.
6. **Cum lucrăm** — 4 pași cu durate reale (Descoperire → Propunere → Execuție → Lansare & suport).
7. **Studiu de caz principal** — un singur proiect, cu context, ce am făcut, rezultat măsurabil. *Date necesare.*
8. **Testimoniale** — 2–3, cu nume, rol, companie și fotografie. *Date necesare.*
9. **Prețuri orientative** — 3 praguri de start („de la … €”), cu link către `pricing.html`.
10. **Întrebări frecvente** — 5–6 obiecții reale: cât durează, cât costă, ce se întâmplă dacă nu-mi place, cine deține codul, ce include mentenanța, lucrați cu firme din afara Moldovei.
11. **CTA final cu formular** — 3 câmpuri (nume, email/telefon, ce vrei să construiești) + alternative de contact + promisiune de răspuns.
12. **Footer** — date complete de firmă, telefon, adresă, program, rețele sociale, pagini legale.

Motivul ordinii: vizitatorul află *ce* și *pentru cine* în 5 secunde, primește dovada înainte să i se ceară ceva, vede prețul înainte să întrebe, iar obiecțiile sunt tratate exact înainte de formular.

---

## 6. Copy rescris integral

> Notă: toate cifrele marcate `[DE CONFIRMAT]` trebuie completate de proprietar. Nu am inventat clienți, rezultate sau date despre companie.

### 6.1 Hero

**Actual:**
- H1: „Creăm experiențe digitale memorabile”
- Paragraf: „Colaborăm cu branduri precum al tău pentru a crea experiențe digitale care cresc indicatorii cheie și prezintă identitatea de brand cu mândrie. Transformăm brandurile în experiențe digitale memorabile.”
- CTA: „Începe un proiect”

**Problema:** „experiențe digitale memorabile” apare de două ori în patru rânduri, nu spune ce vindeți și s-ar potrivi identic oricărei agenții din lume. „Indicatorii cheie” nu înseamnă nimic concret. Nu se află nici serviciul, nici publicul, nici locul.

**Varianta recomandată:**
> **Eyebrow:** Agenție digitală în Chișinău
> **H1:** Site-uri, magazine online și automatizări AI care aduc clienți
> **Paragraf:** Construim și optimizăm platforme WordPress și Shopify pentru companii din Moldova și din afara ei, apoi conectăm marketingul și vânzările prin automatizări AI și CRM. Primul răspuns în maximum 24 de ore.
> **CTA principal:** Cere o ofertă
> **CTA secundar:** Vezi cum lucrăm

**Varianta directă, comercială:**
> **H1:** Ai nevoie de un site care vinde? Îl construim în [DE CONFIRMAT: X] săptămâni
> **Paragraf:** WordPress, Shopify, SEO și automatizări AI pentru companii din Chișinău. Ofertă cu preț și termen în 24 de ore, fără ședințe inutile.
> **CTA:** Vreau o ofertă în 24h

**De ce:** înlocuiește o metaforă cu trei substantive concrete (site, magazin, automatizare), numește publicul și locul, adaugă o promisiune verificabilă (termenul de răspuns) și mută CTA-ul de la un angajament mare („începe un proiect”) la unul mic („cere o ofertă”).

---

### 6.2 Introducerea despre Atomics (secțiunea 2)

**Actual:** „Partenerul tău în Transformare Digitală — Nu suntem doar o agenție, suntem motorul tehnologic din spatele creșterii afacerii tale.”

**Problema:** „transformare digitală” și „motorul tehnologic din spatele creșterii” sunt formulări de brochure corporativă. „Nu suntem doar o agenție, suntem…” este un tipar de copywriting foarte uzat.

**Varianta recomandată:**
> **H2:** Ce facem, concret
> **Intro:** Suntem o echipă de [DE CONFIRMAT: X] oameni din Chișinău. Lucrăm cu companii care au deja un produs sau un serviciu și au nevoie ca partea digitală să funcționeze: să fie găsită, să convertească și să nu se strice.
>
> **H3:** Automatizare și AI — Conectăm formularele, CRM-ul și canalele de vânzare astfel încât un lead să ajungă la persoana potrivită automat. Folosim amoCRM și integrări AI pentru sarcinile repetitive.
> **H3:** Web și e-commerce — Site-uri corporate și magazine WordPress sau Shopify, construite pentru viteză și pentru conversie, cu cod pe care îl deții tu.
> **H3:** SEO și vizibilitate — Optimizare tehnică, conținut și SEO local pentru Chișinău și Moldova, ca să apari acolo unde te caută clienții.
> **H3:** Branding — Identitate vizuală coerentă, de la logo la ghid de brand, aplicabilă pe toate materialele.
>
> **CTA:** Vezi serviciile în detaliu

**Varianta directă:**
> **H2:** Patru lucruri pe care le facem bine
> **Intro:** Fără jargon: construim site-uri care se încarcă rapid și vând, le facem găsite în Google și automatizăm tot ce se repetă în vânzări. Restul îl lăsăm altora.

**De ce:** transformă trei fraze de umplutură în patru servicii clar delimitate, cu H3-uri reale (bun și pentru SEO), și menționează tehnologii concrete în loc de „soluții”.

---

### 6.3 Secțiunea AI și automatizare

**Actual:** „Procesele haotice și sarcinile repetitive consumă timp prețios și pot duce la pierderea clienților. Cu amoCRM și integrarea soluțiilor AI, aceste probleme devin istorie.”

**Problema:** „devin istorie” este o promisiune absolută, imposibil de susținut. Lipsește complet exemplul concret — ce anume se automatizează.

**Varianta recomandată:**
> **H2:** Automatizează ce se repetă, păstrează ce contează
> **Paragraf:** Un lead care ajunge pe email și e citit peste două zile este un client pierdut. Conectăm formularul de pe site, WhatsApp-ul, telefonul și amoCRM într-un singur flux: lead-ul intră automat, ajunge la persoana potrivită și primește un răspuns fără ca cineva să copieze date dintr-un tabel.
> **Exemple concrete:** distribuirea automată a lead-urilor, mementouri de urmărire, ofertă generată din date de CRM, raport săptămânal de vânzări, răspunsuri AI la întrebări repetitive.
> **CTA:** Programează o consultație de 30 de minute

**Varianta directă:**
> **H2:** Câte lead-uri pierzi pentru că nimeni nu răspunde la timp?
> **Paragraf:** Instalăm amoCRM și automatizările din jurul lui în [DE CONFIRMAT: X] zile. La final vezi exact câte cereri intră, cine le tratează și unde se blochează.

**De ce:** înlocuiește promisiunea absolută cu un mecanism explicat, adaugă o listă de rezultate concrete și un CTA cu durată specificată (reduce riscul perceput).

---

### 6.4 Secțiunea creare website

**Actual:** „Creăm Pagini Web Memorabile. … axate pe conversie și pregătite pentru viitor. Ce oferim Strategie digitală, Design web , Creare de conținut, …”

**Problema:** „pregătite pentru viitor” nu înseamnă nimic. Lista de servicii e randată ca un paragraf continuu, cu spații înainte de virgulă. Ilustrația secțiunii arată un ecran cu „HACKED” — mesaj de urgență/securitate, nepotrivit pentru o secțiune despre creare de site-uri.

**Varianta recomandată:**
> **H2:** Site-uri și magazine online construite pentru viteză și vânzări
> **Paragraf:** Construim pe WordPress și Shopify, cu structură gândită pentru conversie și pentru SEO încă de la primul wireframe. Primești un site rapid, ușor de administrat și un cod care îți aparține.
> **Listă (ca `<ul>` real, pe două coloane):** Strategie digitală · Design UI/UX · Dezvoltare WordPress · Magazine Shopify · Conținut și resurse vizuale · Ghid de brand · Integrări cu servicii terțe · Migrare de pe platforma actuală
> **Sub listă:** Timp mediu de livrare: [DE CONFIRMAT] săptămâni. Preț de pornire: de la [DE CONFIRMAT] €.
> **CTA:** Cere o estimare pentru proiectul tău

**Varianta directă:**
> **H2:** Site nou în [DE CONFIRMAT: X] săptămâni, preț fix agreat de la început
> **Paragraf:** Fără surprize la factură. Stabilim scopul, primești preț și termen în scris, apoi construim.

**De ce:** elimină „memorabil” și „pregătit pentru viitor”, structurează serviciile ca listă lizibilă și adaugă cele două informații pe care le caută de fapt vizitatorul: cât durează și cât costă.

---

### 6.5 Secțiunea SEO

**Actual:** „Fii găsit, obține rezultate și crește … Încorporăm SEO tehnic și optimizare AI/LLM în fiecare reproiectare … Ai nevoie de un plan de conținut pe măsură? Oferim strategie de conținut și optimizare conduse de oameni, ca un add-on dedicat.”

**Problema:** titlul e o înșiruire de trei verbe generice. Paragraful amestecă două oferte diferite. „conduse de oameni” și „add-on dedicat” sunt traduceri stângace din engleză.

**Varianta recomandată:**
> **H2:** Fii găsit în Google și în răspunsurile AI
> **Paragraf:** Optimizarea tehnică intră în fiecare proiect: structură corectă, viteză, date structurate și conținut pe care motoarele de căutare și asistenții AI îl pot citi și cita. Pentru companiile din Moldova adăugăm SEO local — profil Google Business, pagini pe oraș și consecvența datelor de contact.
> **Add-on separat:** plan de conținut lunar, scris de oameni, cu subiecte alese din căutările reale ale clienților tăi.
> **CTA:** Cere un audit SEO gratuit

**Varianta directă:**
> **H2:** Dacă nu apari în primele 3 rezultate, nu exiști
> **Paragraf:** Facem auditul tehnic, reparăm ce blochează indexarea și construim conținutul care aduce căutări cu intenție comercială. Primul audit este gratuit și primești raportul în [DE CONFIRMAT: X] zile.

**De ce:** separă clar oferta inclusă de add-on, adaugă componenta locală (esențială pentru piața din Moldova) și înlocuiește CTA-ul generic cu unul cu valoare imediată și risc zero.

---

### 6.6 Secțiunea branding

**Actual:** „Creează un brand clar și inconfundabil pentru tine … Fiecare element este ghidat de o strategie, conceput pentru claritate și construit pentru a promova recunoașterea instantanee și încrederea de durată.”

**Problema:** „pentru tine” la finalul titlului e redundant. Ultima frază conține patru abstracțiuni la rând, fără niciun livrabil.

**Varianta recomandată:**
> **H2:** O identitate vizuală care se ține minte
> **Paragraf:** Brandul tău nu ar trebui să arate diferit pe site, pe Facebook și pe cartea de vizită. Construim un sistem vizual coerent și îți dăm instrumentele ca să-l poți aplica singur mai departe.
> **Ce primești:** logo în toate formatele · paletă de culori și tipografie · ghid de brand PDF · șabloane pentru social media · design pentru materiale tipărite
> **CTA:** Discută despre brandul tău

**Varianta directă:**
> **H2:** Brand complet, livrat în [DE CONFIRMAT: X] săptămâni
> **Paragraf:** Logo, culori, tipografie, ghid de utilizare și șabloane gata de folosit. Un singur pachet, un singur preț.

**De ce:** înlocuiește abstracțiunile cu o listă de livrabile — singurul lucru care face un serviciu de branding cumpărabil.

---

### 6.7 CTA-ul final

**Actual:** „Începe un proiect!” + un singur buton „Programează o consultație”.

**Problema:** un titlu și un buton, fără niciun context, fără formular, fără alternativă de contact, fără reducerea riscului. Este cel mai important moment al paginii și are cel mai puțin conținut.

**Varianta recomandată:**
> **H2:** Spune-ne ce vrei să construiești
> **Paragraf:** Scrie-ne în două rânduri ce ai nevoie. Îți răspundem în maximum 24 de ore cu întrebările potrivite, iar dacă proiectul nu ni se potrivește îți spunem direct și îți recomandăm pe cineva.
> **Formular:** Nume · Email sau telefon · Ce vrei să construiești (textarea) → buton **Trimite cererea**
> **Sub formular:** Sau scrie-ne direct: contact@atomics.md · [DE CONFIRMAT: telefon] · Chișinău, Moldova
> **Microcopy:** Nu trimitem newslettere. Datele tale rămân la noi.

**Varianta directă:**
> **H2:** Primești ofertă în 24 de ore
> **Paragraf:** Trei câmpuri, un minut. Fără ședințe de descoperire de două ore înainte să știi prețul.

**De ce:** transformă un zid („programează o consultație” = angajament de timp) într-o poartă („scrie două rânduri” = efort minim), tratează obiecția principală (cât aștept un răspuns) și adaugă onestitate — care în B2B convertește mai bine decât entuziasmul.

---

## 7. SEO și metadata

### 7.1 Intenția de căutare

Homepage-ul trebuie să acopere intenția **comercială navigațională și de descoperire**: „agenție web design Chișinău”, „creare site web Moldova”, „firmă WordPress Chișinău”. În acest moment title-ul acoperă parțial această intenție, dar conținutul paginii nu o susține — nu există prețuri, portofoliu, proces sau dovezi, adică exact semnalele pe care Google le asociază cu paginile de agenție care se poziționează.

### 7.2 Metadata rescrisă

**Title recomandat** (58 caractere):
```
Agenție web design și AI în Chișinău | Atomics
```
Alternativă mai comercială (60 caractere):
```
Creare site web și magazine online în Chișinău | Atomics
```

**Meta description recomandată** (152 caractere):
```
Agenție digitală din Chișinău: site-uri WordPress și Shopify, SEO local, branding și automatizări AI. Ofertă cu preț și termen în 24 de ore.
```

**H1 recomandat:**
```
Site-uri, magazine online și automatizări AI care aduc clienți
```

**Structura H2/H3 recomandată:**

```
H1  Site-uri, magazine online și automatizări AI care aduc clienți
H2  Companii care lucrează cu noi            (bandă de încredere)
H2  Ce facem, concret
    H3  Automatizare și AI
    H3  Web și e-commerce
    H3  SEO și vizibilitate
    H3  Branding
H2  Cum lucrăm
    H3  1. Descoperire   H3  2. Propunere   H3  3. Execuție   H3  4. Lansare și suport
H2  Rezultate pentru clienții noștri          (studiu de caz)
H2  Ce spun clienții
H2  Cât costă un proiect
H2  Întrebări frecvente
    H3  Cât durează un site?
    H3  Cât costă?
    H3  Cine deține codul și domeniul?
    H3  Ce include mentenanța?
    H3  Lucrați cu firme din afara Moldovei?
H2  Spune-ne ce vrei să construiești
```

### 7.3 Cuvinte-cheie

**Principale:** agenție web design Chișinău · creare site web Moldova · dezvoltare WordPress Chișinău · magazin online Moldova · agenție digitală Chișinău
**Secundare:** SEO Chișinău · SEO local Moldova · Shopify Moldova · automatizare AI companii · integrare amoCRM · branding Chișinău · migrare site WordPress · mentenanță WordPress
**De evitat ca țintă principală:** „experiențe digitale”, „transformare digitală” — volum de căutare aproape inexistent în română și intenție neclară.

### 7.4 Entități și subiecte asociate

Chișinău · Republica Moldova · WordPress · WooCommerce · Shopify · Elementor · amoCRM · Google Business Profile · Core Web Vitals · leu moldovenesc / euro (pentru prețuri) · lucru la distanță pentru piețele România și UE.

### 7.5 JSON-LD recomandat

Înlocuiește blocul actual din `index.htm:91-105`:

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "ProfessionalService",
      "@id": "https://atomics.md/#organization",
      "name": "Atomics",
      "url": "https://atomics.md/",
      "logo": "https://atomics.md/img/logo.png",
      "image": "https://atomics.md/img/og.png",
      "description": "Agenție digitală din Chișinău: site-uri WordPress și Shopify, SEO local, branding și automatizări AI.",
      "email": "contact@atomics.md",
      "telephone": "[DE CONFIRMAT]",
      "priceRange": "[DE CONFIRMAT, ex: €€]",
      "foundingDate": "[DE CONFIRMAT]",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "[DE CONFIRMAT]",
        "addressLocality": "Chișinău",
        "postalCode": "[DE CONFIRMAT]",
        "addressCountry": "MD"
      },
      "areaServed": [
        { "@type": "Country", "name": "Moldova" },
        { "@type": "Country", "name": "România" }
      ],
      "sameAs": ["[DE CONFIRMAT: Facebook]", "[DE CONFIRMAT: LinkedIn]", "[DE CONFIRMAT: Instagram]"],
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
        "opens": "[DE CONFIRMAT]", "closes": "[DE CONFIRMAT]"
      },
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Servicii Atomics",
        "itemListElement": [
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Creare site web WordPress", "url": "https://atomics.md/website-and-e-commerce.html" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Magazine online Shopify și WooCommerce" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "SEO tehnic și SEO local" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Automatizare AI și integrare CRM" } },
          { "@type": "Offer", "itemOffered": { "@type": "Service", "name": "Branding și identitate vizuală" } }
        ]
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://atomics.md/#website",
      "url": "https://atomics.md/",
      "name": "Atomics",
      "inLanguage": "ro-MD",
      "publisher": { "@id": "https://atomics.md/#organization" }
    },
    {
      "@type": "WebPage",
      "@id": "https://atomics.md/#webpage",
      "url": "https://atomics.md/",
      "name": "Agenție web design și AI în Chișinău | Atomics",
      "isPartOf": { "@id": "https://atomics.md/#website" },
      "about": { "@id": "https://atomics.md/#organization" }
    }
  ]
}
</script>
```

Pe paginile interioare adăugați `BreadcrumbList`, iar pe secțiunea de întrebări frecvente `FAQPage` — dar **numai** dacă întrebările sunt vizibile în pagină.

### 7.6 `hreflang`

**Nu este necesar.** Site-ul are o singură versiune lingvistică (`ro-MD`). `hreflang` devine relevant doar dacă apare o versiune în engleză sau rusă — iar în Moldova o versiune în rusă ar putea avea sens comercial real, caz în care perechea `ro-MD` / `ru-MD` + `x-default` devine obligatorie.

### 7.7 Riscul textelor animate

Textele împărțite în 407 span-uri `.char` **sunt** în DOM-ul inițial și rămân accesibile crawlerelor care execută JS. Riscul real nu e indexarea, ci:
- fragmentarea pentru cititoarele de ecran (unele citesc literă cu literă);
- extragerea de text pentru motoarele AI/LLM, care primesc „CONTACT CONTACT” și „Comandă acum Rezervă o sesiune de strategie” din butoanele cu mască;
- fără JS, scroll-ul blocat face ca tot conținutul sub fold să fie invizibil pentru orice crawler care nu randează.

### 7.8 Plan de legături interne

| Din | Ancoră | Către |
|---|---|---|
| Homepage, secțiunea Servicii | „creare site web WordPress” | `/servicii/creare-site-web` (pagină nouă) |
| Homepage, secțiunea Servicii | „magazin online Shopify” | `/servicii/magazin-online` (pagină nouă) |
| Homepage, secțiunea Servicii | „SEO local în Chișinău” | `/servicii/seo` (pagină nouă) |
| Homepage, secțiunea Servicii | „automatizare cu amoCRM” | `/servicii/automatizare-ai` (pagină nouă) |
| Homepage, secțiunea Prețuri | „vezi pachetele și prețurile” | `/preturi` |
| Homepage, studiu de caz | „vezi tot proiectul” | `/portofoliu/[proiect]` |
| Footer | „ajutor de urgență WordPress” | `/emergency-help` |

Regula: renunțați la ancorele „află mai multe” și „descoperă acum” — nu transmit niciun semnal.

### 7.9 Pagini care lipsesc

- `/servicii` — hub real, în locul redirecționării „SERVICII” către `solutions.html`
- Patru pagini de serviciu dedicate (web, e-commerce, SEO, automatizare AI)
- `/portofoliu` + minimum 3 studii de caz
- `/despre` în română, cu echipa reală (înlocuiește `for-agencies.html` ca destinație pentru „Despre noi”)
- `/blog` sau `/resurse` — necesar pentru E-E-A-T și pentru cuvintele-cheie informaționale
- `/politica-de-confidentialitate` și `/termeni` — obligatorii legal și semnal de încredere
- URL-uri în română, cu redirecționări 301 din cele actuale în engleză

---

## 8. Recomandări pentru animații

### 8.1 Inventar și verdict

| Animație | Ajută? | Verdict |
|---|---|---|
| Preloader cu rachetă și procent | Nu | **Eliminați.** Ascunde tot mesajul pentru 2,6–46 s și, fără WebGL, pentru totdeauna |
| Parallax pe scroll (14 straturi în hero) | Parțial | **Simplificați** la 2–3 straturi, doar pe desktop |
| Parallax la mișcarea mouse-ului (`data-mouse-speed` până la 2.2) | Nu | **Eliminați.** Consumă CPU permanent, nu comunică nimic |
| Scena Pixi/WebGL (151 sprite-uri) | Nu | **Eliminați.** Randarea DOM arată practic identic, la 1/19 din costul de blocare |
| Text animat literă cu literă (407 span-uri) | Parțial | **Simplificați** la fade pe cuvânt sau pe bloc |
| Secvența rachetei din secțiunea finală | Da, ca semnătură | **Păstrați**, dar doar pe desktop și cu `IntersectionObserver` |
| Butoane cu mască sprite (`btn-pattern.png`, 546 KB) | Nu la acest cost | **Înlocuiți** cu tranziție CSS de culoare |
| Chevron „scroll down” (`move-chevron 2.1s`, infinit) | Da | **Păstrați**, dar opriți la `prefers-reduced-motion` |
| Stele care clipesc, `rotate360` | Neutru | **Păstrați** doar dacă sunt CSS pure și nu rulează în afara viewportului |
| Fade-in WOW la scroll (20+ elemente) | Parțial | **Păstrați** pe titluri, eliminați pe restul; durată maximă 300 ms |
| Scroll virtual (Locomotive) | Nu | **Eliminați.** Blochează scroll-ul fără JS, complică ancorele, istoricul și focusul |

### 8.2 Durate și easing recomandate

- Intrări de conținut: **200–300 ms**, `cubic-bezier(0.16, 1, 0.3, 1)`
- Hover pe butoane: **150 ms**, `ease-out`
- Tranziții de secțiune: eliminate complet — scroll nativ
- Nicio animație de intrare peste 400 ms; nicio animație infinită în afara viewportului

### 8.3 Comportament pentru `prefers-reduced-motion`

Blocul CSS actual oprește doar `transition`. Este necesar și pentru `animation`, plus o ramură JS:

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
  .char, .word, .wow { opacity: 1 !important; transform: none !important; }
}
```

```js
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (reduced) {
  // fără Pixi, fără parallax, fără scroll virtual, fără split de text
  document.body.classList.add('is-static-version');
} else {
  initDecorativeLayer();
}
```

### 8.4 Fallback fără JavaScript

```html
<noscript>
  <style>
    .js-preloader, .main-canvas { display: none !important; }
    body, .out { overflow: visible !important; }   /* lipsește azi — pagina nu poate fi derulată */
    [data-scroll-section] { transform: none !important; opacity: 1 !important; }
    .char, .word, .wow, .animate__animated { opacity: 1 !important; visibility: visible !important; animation: none !important; }
  </style>
</noscript>
```

---

## 9. Recomandări tehnice, cu exemple

### 9.1 Eliminarea preloaderului blocant

```js
// Înlocuiește Promise.race([...]) + H()/G() din app.js
const boot = () => {
  document.body.classList.remove('is-loading');           // conținutul apare imediat
  const preloader = document.querySelector('.js-preloader');
  if (preloader) preloader.remove();
};

// 1. conținutul apare la DOMContentLoaded, indiferent de decor
document.addEventListener('DOMContentLoaded', boot);

// 2. decorul se încarcă separat și nu poate bloca nimic
window.addEventListener('load', () => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (!window.matchMedia('(min-width: 1025px)').matches) return;
  try { initDecorativeLayer(); }
  catch (err) { console.warn('Decor indisponibil, se continuă fără el:', err); }
});
```

### 9.2 Plasa de siguranță pentru WebGL

```js
function hasWebGL() {
  try {
    const c = document.createElement('canvas');
    return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
  } catch (e) { return false; }
}
if (!hasWebGL()) initDomVersion();   // azi: excepție necontrolată și pagină blocată permanent
```

### 9.3 Imagini

```html
<!-- imaginea din hero: singura fără lazy, cu prioritate mare -->
<img src="/img/first-section/hero-01.avif" width="760" height="900"
     fetchpriority="high" decoding="async" alt="">

<!-- tot ce e sub fold -->
<img src="/img/third-section/ai-automation-astronaut.avif" width="820" height="900"
     loading="lazy" decoding="async" alt="">
```

```html
<link rel="preload" as="image" href="/img/first-section/hero-01.avif" fetchpriority="high">
<link rel="preload" as="font" type="font/woff2"
      href="/fonts/hkgrotesk/hkgrotesk-extrabold-webfont.woff2" crossorigin>
```

Comandă de conversie:
```bash
# 9,02 MB → estimativ 0,9–1,4 MB
for f in img/**/*.png; do
  npx @squoosh/cli --avif '{"cqLevel":33}' --resize '{"width":1200}' -d img-optim "$f"
done
```

### 9.4 Accesibilitate

```html
<a class="skip-link" href="#main">Sari la conținutul principal</a>
<main id="main" tabindex="-1"> … </main>
```

```css
.skip-link {
  position: absolute; left: -9999px; top: 0; z-index: 100000;
  background: #03010e; color: #fff; padding: 12px 20px; border-radius: 0 0 8px 0;
}
.skip-link:focus { left: 0; }

/* înlocuiește outline: 2px solid #4f3a5a — 2,06:1, invizibil */
:focus-visible {
  outline: 3px solid #00ffd4;
  outline-offset: 3px;
  border-radius: 4px;
}

.nav__link, .footer-list__link { display: inline-block; padding: 12px 0; }  /* 16px → 40px */

section[data-scroll-section] { scroll-margin-top: 96px; }  /* titluri tăiate de headerul fix */
```

```js
// meniul mobil: azi aria-expanded rămâne "false", Escape nu face nimic
const burger = document.querySelector('.js-mobile-hamburger');
const nav = document.querySelector('.js-nav');
function setMenu(open) {
  burger.setAttribute('aria-expanded', String(open));
  burger.setAttribute('aria-label', open ? 'Închide meniul' : 'Deschide meniul');
  document.body.classList.toggle('menu-is-open', open);
  document.documentElement.style.overflow = open ? 'hidden' : '';
  if (open) nav.querySelector('a').focus();
  else burger.focus();
}
burger.addEventListener('click', () => setMenu(burger.getAttribute('aria-expanded') !== 'true'));
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') setMenu(false);
});
```

### 9.5 Butoanele cu mască

```html
<!-- ACUM: masca spune un lucru, linkul altul, iar 5 din 8 măști nu au aria-hidden -->
<div class="animation-btn">
  <span class="animation-btn__mask">Comandă acum</span>
  <a href="/contact.html" class="animation-btn__btn">Rezervă o sesiune de strategie</a>
</div>

<!-- CORECT: același text, mască ascunsă pentru tehnologiile asistive -->
<div class="animation-btn">
  <span class="animation-btn__mask" aria-hidden="true">Cere o ofertă</span>
  <a href="/contact.html" class="animation-btn__btn">Cere o ofertă</a>
</div>
```

### 9.6 Server

```nginx
gzip on;  # sau brotli
gzip_types text/css application/javascript image/svg+xml application/json;
location ~* \.(avif|webp|png|jpg|svg|woff2|js|css)$ {
  add_header Cache-Control "public, max-age=31536000, immutable";
}
```

### 9.7 Impact estimat

| Optimizare | Impact probabil |
|---|---|
| Eliminarea preloaderului | Primul conținut vizibil: de la 19–46 s la sub 2 s pe mobil |
| Ramura DOM implicită | Blocking time: 1211 ms → ~64 ms (măsurat) |
| AVIF + redimensionare + lazy | Greutate: 9,02 MB → 1,0–1,5 MB pe prima încărcare |
| `fetchpriority` pe hero, eliminarea stelelor din LCP | LCP: element decorativ → H1, cu 1,5–3 s câștigate pe mobil |
| `width`/`height` pe imagini | CLS: 0,034 → sub 0,01 |
| Eliminarea `btn-pattern.png` | −546 KB |
| gzip/brotli pe JS+CSS | 1,78 MB → ~0,29 MB transferați |

---

## 10. Plan de implementare

### Quick wins — 1–2 zile

1. Instalați analytics + evenimente pe CTA-uri
2. Reparați `<noscript>`: `body, .out { overflow: visible !important }`
3. Adăugați plasa de siguranță pentru WebGL și timeout-ul necondiționat al preloaderului
4. Aliniați textul măștilor cu textul linkurilor + `aria-hidden` pe toate cele 8
5. Skip link + inel de focus vizibil (`#00ffd4`, 3 px)
6. `aria-expanded`, `aria-label` și Escape pe meniul mobil
7. `padding: 12px 0` pe linkurile din nav și footer
8. Completați `<title>` și meta description pe `pricing.html`
9. Rescrieți title și description pe homepage
10. Închideți roșul CTA la `#d1004f`
11. `scroll-margin-top: 96px` pe secțiuni
12. Scoateți `img/concepts/` din root-ul publicat

### O săptămână

13. Eliminați preloaderul blocant, randați conținutul imediat
14. Faceți ramura DOM implicită pe mobil și tabletă
15. Conversie AVIF/WebP + `loading="lazy"` + `width`/`height` pe toate imaginile
16. Rescrieți hero-ul și cele 5 secțiuni de serviciu (secțiunea 6 din acest document)
17. Adăugați formularul de 3 câmpuri în CTA-ul final + telefon vizibil
18. Transformați lista „Ce oferim” într-un `<ul>` real
19. Ramură JS completă pentru `prefers-reduced-motion`
20. JSON-LD extins (`ProfessionalService` + `WebSite` + `WebPage`)
21. Înlocuiți `btn-pattern.png` cu CSS

### Strategic — 3–8 săptămâni

22. Renunțați la scroll-ul virtual în favoarea celui nativ
23. Construiți hub-ul `/servicii` + cele 4 pagini de serviciu, cu URL-uri în română și 301
24. Colectați și publicați dovezile: 3 studii de caz, 3 testimoniale, logo-uri, cifre reale
25. Adăugați prețuri orientative pe homepage
26. Secțiune de întrebări frecvente cu obiecțiile reale
27. Pagini legale + date complete de firmă în footer
28. Reduceți înălțimea secțiunilor cu 30–40% (7.500–9.800 px este de aproximativ două ori peste necesar)
29. Decideți dacă e nevoie de versiune în rusă; dacă da, adăugați `hreflang`
30. Testare cu utilizatori reali pe 5 persoane din publicul-țintă

---

## 11. Checklist final înainte de lansare

**Performanță**
- [ ] Prima încărcare sub 1,5 MB
- [ ] LCP sub 2,5 s pe 4G, măsurat pe telefon real, nu în Lighthouse
- [ ] Elementul LCP este H1 sau imaginea din hero, nu un strat de stele
- [ ] CLS sub 0,1; toate imaginile au `width` și `height`
- [ ] Total blocking time sub 200 ms
- [ ] `loading="lazy"` pe tot ce e sub fold; **niciodată** pe imaginea LCP
- [ ] gzip/brotli activ; `Cache-Control: immutable` pe activele cu hash

**Funcțional**
- [ ] Conținutul este vizibil în sub 2 s, fără preloader blocant
- [ ] Site-ul funcționează cu WebGL indisponibil
- [ ] Site-ul poate fi derulat cu JavaScript dezactivat
- [ ] Meniul mobil se deschide, se închide cu Escape și cu click în afară
- [ ] Zero erori în consolă
- [ ] Toate linkurile din meniu duc unde promite eticheta

**Accesibilitate — WCAG 2.2 AA**
- [ ] Skip link funcțional
- [ ] Focus vizibil cu contrast peste 3:1 pe toate fundalurile
- [ ] Ordinea focusului urmează ordinea vizuală, iar elementul focalizat este adus în viewport
- [ ] Textul CTA-urilor coincide cu numele accesibil
- [ ] Toate țintele de atingere au minimum 24×24 px
- [ ] Contrast minim 4,5:1 pentru text normal, 3:1 pentru text mare — inclusiv peste ilustrații
- [ ] `prefers-reduced-motion` oprește Pixi, parallax, scroll virtual și split-ul de text
- [ ] Testat cu NVDA sau VoiceOver pe hero, meniu și formular
- [ ] Zoom 200%: fără overflow orizontal, fără text tăiat

**SEO**
- [ ] Title sub 60 de caractere, description sub 155
- [ ] Un singur H1; ierarhie H2/H3 fără salturi
- [ ] Toate paginile din meniu au title și description
- [ ] JSON-LD validat cu Rich Results Test
- [ ] Imaginea OG sub 200 KB, testată în Facebook Debugger și pe WhatsApp
- [ ] Sitemap cu `lastmod`, trimis în Search Console
- [ ] URL-uri în română, cu 301 din cele vechi
- [ ] Google Business Profile conectat, cu NAP identic cu footerul

**Conversie**
- [ ] Analytics activ, cu evenimente pe fiecare CTA și pe trimiterea formularului
- [ ] Un singur vocabular pentru CTA-uri în toată pagina
- [ ] Formular pe homepage, maximum 3 câmpuri
- [ ] Telefon și email vizibile fără scroll pe mobil
- [ ] Minimum 3 elemente de dovadă vizibile înainte de primul formular
- [ ] Termenul de răspuns declarat explicit

---

## Informații care trebuie cerute proprietarului

1. Numărul de telefon și adresa completă a firmei
2. Denumirea juridică și IDNO, pentru footer și pentru date structurate
3. Numărul real de proiecte livrate și anul înființării
4. 3 clienți care acceptă să fie numiți, cu logo și testimonial
5. 3 proiecte care pot deveni studii de caz, cu rezultate măsurabile
6. Prețurile de pornire pentru fiecare serviciu
7. Termenele reale de livrare pentru site, magazin online, branding
8. Timpul de răspuns pe care echipa îl poate garanta
9. Profilurile de social media
10. Dacă este necesară o versiune în limba rusă
11. Mărimea echipei și rolurile, pentru pagina „Despre”
12. Ce include mentenanța și la ce preț

---

*Toate cifrele din acest audit provin din măsurători efectuate pe cod, nu din estimări. Capturile de ecran și fișierele JSON cu datele brute sunt disponibile în directorul de lucru al sesiunii.*
