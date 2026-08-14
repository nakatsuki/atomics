# Audit complet homepage Atomics

**Data auditului:** 13 august 2026  
**Pagină analizată:** `index.htm`, servită local prin HTTP  
**Obiectiv evaluat:** claritate, diferențiere, încredere, conversie și vizibilitate organică în Moldova și pe piețe relevante din afara țării

## Metodă și limite

Auditul combină inspecția vizuală, testarea interacțiunilor și analiza directă a HTML/CSS/JS și a activelor.

Condiții verificate:

- 1440×900, 1280×800, 768×1024, 390×844 și 360×800;
- navigare exclusiv din tastatură;
- meniu mobil, Escape și starea ARIA;
- fallback fără JavaScript, prin încărcarea paginii într-un context care blochează scripturile;
- conexiune lentă simulată cu aproximativ 150 ms latență pe resursă și 200 KB/s per conexiune;
- reflow echivalent unui zoom de 200%, prin viewport CSS 720×450 pentru un ecran fizic de 1440×900;
- `prefers-reduced-motion` prin inspecția ramurilor CSS/JS; mediul de test nu a permis comutarea sigură a preferinței OS în runtime;
- structura semantică, metadata, schema, `robots.txt`, `sitemap.xml`, linkurile și activele locale.

Rezultatele Core Web Vitals din teren nu pot fi deduse doar din surse locale. Pragurile actuale sunt LCP ≤ 2,5 s, INP ≤ 200 ms și CLS ≤ 0,1 la percentila 75, iar verdictul final trebuie luat din CrUX/Search Console sau RUM, nu doar din Lighthouse. [Documentația oficială Web Vitals](https://web.dev/articles/vitals)

## 1. Rezumat executiv

1. **P0 — conținutul util apare mult prea târziu.** Preloaderul a ținut pagina acoperită aproximativ 12–16 secunde chiar pe server local; în simularea lentă, hero-ul a devenit vizibil după circa 36,5 secunde și aproape stabil după circa 49,7 secunde.
2. **P0 — mesajul principal este acoperit pe mobil.** La 390×844 și 360×800, planeta de 324 px, cu `z-index: 10`, se așază peste H1 și paragraf; fragmente de cuvinte dispar exact în zona care trebuie să explice oferta.
3. Identitatea spațială este memorabilă și bine executată vizual, dar decorul domină produsul: utilizatorul vede o experiență cinematografică înainte să vadă pentru cine lucrează Atomics, ce rezultate urmărește și de ce ar trebui ales.
4. Scroll-ul virtual transformă secțiunile și ascunde overflow-ul pe `html` și `body`. În test, gesturi normale au sărit peste conținut, au lăsat titluri sub header și au făcut ca focusul de tastatură să avanseze către CTA-uri invizibile fără ca ecranul să le urmărească.
5. Homepage-ul nu oferă nicio dovadă comercială: nu există studii de caz, rezultate verificabile, testimoniale, logo-uri de clienți, proces, garanții limitate sau cifre. Afirmațiile „platforme care vând”, „rezultate măsurabile” și „profit” rămân nedemonstrate.
6. Arhitectura informației și etichetele meniului sunt nealiniate. „Servicii” duce la o pagină WordPress intitulată în engleză, iar „Soluții” duce la „WordPress Emergency Help”; traseul nu corespunde așteptării create de etichetă.
7. CTA-urile sunt inconsistente și uneori spun două lucruri diferite în același control. De exemplu, masca vizuală afișează „Comandă acum”, iar linkul accesibil este „Începe acum” sau „Rezervă o sesiune de strategie”. Toate CTA-urile importante trimit la pagina de contact, fără formular sau calificare pe homepage.
8. Încărcarea este disproporționată: 151 imagini în DOM, zero `loading="lazy"`, zero `fetchpriority="high"`, aproximativ 8,07 MB numai în imaginile `<img>` referite, un bundle JS de 1,43 MB și CSS de 402 KB. DOM-ul ajunge la 1.108 elemente, dintre care 407 sunt span-uri `.char` create pentru animația textului.
9. Baza SEO este decentă: canonical, meta description, Open Graph, Twitter Card, `Organization`, robots și sitemap există. Totuși, title-ul are 69 de caractere, descrierea 186, schema este prea subțire, iar homepage-ul nu are pagini dedicate pentru AI, SEO și branding către care să distribuie relevanța.
10. Fără JavaScript, conținutul și scroll-ul nativ rămân vizibile și chiar mai lizibile; însă meniul mobil și acordeoanele din footer nu pot fi deschise. Acesta este un fallback incomplet, nu o degradare progresivă completă.

## 2. Scoruri

| Arie | Scor | Verdict scurt |
|---|---:|---|
| Design | 6,5/10 | Memorabil și coerent, dar prea mult decor concurează cu mesajul și dovada. |
| UI/UX | 4/10 | Navigarea vizuală funcționează după încărcare, însă scroll-ul, headerul și meniul produc fricțiune. |
| Conversie | 3/10 | CTA-uri multe, dar fără proof, ofertă clară, formular scurt sau reducerea riscului. |
| Copywriting | 4/10 | Ton modern, dar general, repetitiv și slab susținut prin exemple sau date. |
| SEO | 6/10 | Fundamente bune; intenția locală, paginile de serviciu, schema și linkingul intern sunt insuficiente. |
| Accesibilitate | 3/10 | Landmarks și unele ARIA există, dar focusul, meniul, motion-ul și textul fragmentat au defecte serioase. |
| Performanță | 2/10 | Preloader foarte lung, toate imaginile eager, bundle mare și multă muncă de animație. |
| Animații | 3,5/10 | Tehnic ambițioase, însă prea multe, prea lungi și uneori ascund sau sar peste mesaj. |
| Mobile | 3/10 | Layout fără overflow orizontal, dar hero-ul este acoperit, meniul are defecte și conținutul este greu de parcurs. |

## 3. Tabelul problemelor

### Probleme confirmate

| Prioritate | Secțiune/element | Problemă | Dovadă | Impact | Recomandare | Efort |
|---|---|---|---|---|---|---|
| P0 | Global / `.js-preloader` | Preloaderul blochează complet conținutul. | 12–16 s până la hero stabil pe server local; ~36,5 s până la conținut vizibil și ~49,7 s până la hero aproape stabil în simularea lentă. `body.is-loading` și overlay fix peste viewport. | Abandon, LCP perceput extrem de slab, CTA indisponibil, crawl/render mai costisitor. | Eliminați preloaderul blocant. Afișați HTML-ul imediat și încărcați decorul progresiv după primul paint. Dacă păstrați o tranziție, maximum 300–500 ms și fără a ascunde H1/CTA. | M |
| P0 | Hero mobil / planeta mare | Planeta acoperă H1 și paragraful. | La 360×800: planeta are 324×324 px, `x:72`, `y:40`, `z-index:10`; H1 începe la `y:170` și nu are z-index. Capturile arată litere și rânduri dispărute. Aceeași problemă la 390×844. | Oferta nu poate fi citită pe cele mai importante viewport-uri mobile. | Puneți conținutul hero într-un stacking context deasupra decorului (`position:relative; z-index:20`) sau mutați/reduceți planeta sub 40% din lățime. Testați 320–430 px. | S |
| P1 | Scroll global / Locomotive-style virtual scroll | Scroll-ul poate sări peste blocuri și nu respectă comportamentul nativ. | `html` și `body` au `overflow:hidden`; secțiunile sunt mutate cu `transform`. Un scroll de 900–1500 px a sărit peste conținut, iar tranzițiile au afișat viewport-uri aproape goale. | Descoperire slabă a serviciilor, frustrare, dificultăți pentru tastatură, ancore, istoric și capturi. | Folosiți scroll nativ. Păstrați parallax-ul doar pe 2–3 straturi decorative și doar pe desktop capabil. | L |
| P1 | Tastatură / ordinea focusului | Focusul avansează în CTA-uri din secțiuni nevizibile fără ca viewport-ul virtual să le urmărească. | După navigația headerului, Tab a ajuns în CTA-urile tuturor secțiunilor, în timp ce captura a rămas pe hero. | Utilizatorii de tastatură nu văd controlul activ și nu știu unde se află. | La `focusin`, asigurați scroll nativ către element; ideal eliminați scrollerul virtual. Nu folosiți transformări pe containerul principal. | L |
| P1 | Header fix / titluri de secțiune | Titlurile intră sub header sau sunt tăiate la începutul secțiunii. | Capturi din secțiunile AI, website, SEO și branding arată începutul H2 deasupra viewport-ului ori sub headerul de 75–77 px. | Utilizatorul pierde contextul secțiunii; experiență mai slabă la ancore și focus. | `scroll-margin-top: calc(var(--header-h) + 24px)` pe heading/section; spațiere reală înainte de H2; nu poziționați textul prin transform parallax. | S–M |
| P1 | Meniu mobil / hamburger | Starea și comportamentul accesibil nu sunt actualizate. | După deschidere, butonul păstrează `aria-expanded="false"` și `aria-label="Deschide meniul"`. Escape nu elimină clasele `is-open`. Codul nu implementează Escape sau focus trap. | Numele/starea anunțată cititorului de ecran este falsă; utilizatorul nu poate închide meniul prin convenția standard. | Actualizați `aria-expanded` și eticheta, închideți la Escape, mutați focusul în meniu, blocați focusul în overlay și returnați-l la buton. | M |
| P1 | Fallback fără JS / navigație mobilă și footer | Conținutul este vizibil, dar navigația mobilă și acordeoanele nu pot fi deschise. | Testul fără script arată hamburgerul închis și footerul în mod acordeon; nu există control funcțional. | Utilizatorii fără JS nu pot accesa meniul și o parte din linkurile footerului. | În `.no-js`, afișați lista de navigație și toate listele footerului static; ascundeți hamburgerul. | S |
| P1 | CTA-uri animate | Textul vizual și numele linkului sunt diferite. | Website: masca „Comandă acum”, linkul „Începe acum”; SEO și branding: masca „Comandă acum”, linkul „Rezervă o sesiune de strategie”. Doar unele măști au `aria-hidden="true"`. | Confuzie, așteptări greșite, risc de nume accesibil duplicat sau inconsistent. | Un singur nod textual per CTA. Dacă masca este necesară, copia vizuală trebuie să fie identică și masca să aibă mereu `aria-hidden="true"`. Preferabil animați un pseudo-element. | S |
| P1 | Dovezi comerciale / homepage | Nu există proof verificabil. | Căutarea în homepage nu găsește testimoniale, studii de caz, portofoliu, rezultate, clienți, proces sau garanții. | Afirmațiile sunt greu de crezut, mai ales pentru proiecte cu buget mare; conversie mică. | Adăugați 2–3 studii de caz, rezultate cu context, testimoniale aprobate, logo-uri cu permisiune și un proces în 4 pași. | L |
| P1 | Meniu / arhitectură informațională | Etichetele nu corespund destinațiilor. | „Servicii” → `/solutions.html`, pagină „WordPress - find suitable solution for you”; „Soluții” → `/emergency-help.html`, pagină „WordPress Emergency Help”. Majoritatea paginilor secundare au title/H1 în engleză. | Dezorientare, bounce, semnale lingvistice și tematice amestecate. | Schimbați meniul în „Servicii”, „Proiecte”, „Despre”, „Prețuri”, „Contact”; legați fiecare etichetă de o pagină care răspunde exact intenției și localizați integral paginile românești. | M–L |
| P1 | Conversie / CTA și formular | Toate CTA-urile principale duc la pagina de contact; homepage-ul nu are formular. | `document.forms.length === 0`; CTA-urile serviciilor trimit toate la `/contact.html`. | Un pas suplimentar și pierderea contextului serviciului; calificare slabă a leadului. | Formular scurt în CTA-ul final: nume, email/telefon, serviciu, mesaj/opțional buget. Păstrați CTA contextual pe fiecare serviciu cu parametru sau câmp preselectat. | M |
| P1 | Active / imagini | Toate cele 151 de imagini sunt încărcate eager. | În DOM: 151 imagini, 0 `loading="lazy"`, 0 `fetchpriority="high"`, toate 151 decodate în testul rapid. | Concurență pentru resursele above-the-fold, memorie și consum de date, LCP mai slab. | Nu aplicați lazy imaginii LCP. Pentru restul imaginilor sub primul viewport: `loading="lazy" decoding="async"`; încărcați decorurile secțiunii când se apropie de viewport. | M |
| P1 | Payload / bundle | Pagina este foarte grea. | 8.069.102 bytes în imaginile `<img>` unice; `app-ebea275ace.js` 1.426.150 bytes; `app.css` 401.618 bytes; HTML 153.849 bytes; footer GIF 345.566 bytes. Total local minim ~10,4 MB înainte de fonturi și fără a conta toate resursele runtime. | LCP, TBT/INP, memorie și cost de date, mai ales pe mobil. | Buget inițial: ≤1,5 MB total pentru prima vizualizare mobilă; amânați decorul. Convertiți PNG-urile mari în AVIF/WebP și împărțiți bundle-ul. | L |
| P1 | DOM / text split | Textul este împărțit în sute de caractere. | 1.108 elemente totale și 407 `.char`; snapshotul accesibil după JS expune heading-uri cu spații între fiecare literă. | Mai mult layout/style work, accesibilitate imprevizibilă, citire fragmentată de unele tehnologii asistive. | Nu modificați textul semantic. Animați blocul/linia cu `clip-path`, `opacity` și `transform` pe wrapper; păstrați textul intact în DOM. | M |
| P1 | Motion / reduced motion | Suportul pentru reduced motion este incomplet. | CSS global dezactivează doar `transition`; regula Animate.css scurtează doar `.animate__animated`. Chevroanele au animație infinită 2,1 s, twinkling 200 s, iar alte keyframes nu sunt oprite. JS evită WebGL în reduced motion, dar nu există o regulă unică pentru toate efectele. | Risc de amețeală, consum CPU/GPU și nerespectarea preferinței utilizatorului. | În reduced motion: scroll nativ, fără parallax, fără mouse-follow, fără infinite loops, text vizibil din primul frame. | M |
| P1 | Focus / linkurile headerului | Indicatorul de focus are contrast insuficient pe fundal întunecat. | `outline: 2px solid #4f3a5a`; contrast măsurat față de `#0d0e10`: ~1,92:1, sub schimbarea de contrast 3:1 recomandată pentru focus. [WCAG Focus Appearance](https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance) | Focusul există, dar este greu de observat. | Folosiți outline dublu sau o culoare deschisă, de exemplu `#fff` + `#111`, 2–3 px, `outline-offset: 3px`. | S |
| P1 | CTA roz | Contrastul textului alb pe roz este sub AA pentru text normal. | Fundal computed `rgb(234,40,102)` și alb: ~4,21:1; butoanele au 14–16 px și greutate 500. | Text mai greu de citit; eșec WCAG AA pentru text normal. | Închideți rozul spre `#D41457` sau folosiți text aproape negru dacă designul permite; verificați toate stările. | S |
| P2 | Hero / value proposition | H1-ul nu spune serviciile, publicul sau avantajul concret. | „Creăm experiențe digitale memorabile” ar putea aparține aproape oricărei agenții. | În primele secunde, vizitatorul nu poate decide dacă Atomics este relevant. | H1 cu servicii și public: website-uri, automatizări AI și SEO pentru afaceri. Eyebrow local „Agenție digitală din Chișinău”. | S |
| P2 | Copy / credibilitate | Sunt folosite afirmații fără mecanism sau dovadă. | „platforme care vând”, „rezultate măsurabile și profit”, „pregătite pentru viitor”, „recunoaștere instantanee”. | Reduce credibilitatea și crește scepticismul. | Înlocuiți cu rezultate demonstrabile sau cu mecanismul de lucru. Marcați cifrele ce trebuie furnizate de proprietar. | M |
| P2 | Copy / densitate | Paragrafele serviciilor sunt lungi și listele sunt înghesuite în text. | Secțiunea website pune „Ce oferim” și nouă servicii într-un singur paragraf; pe mobil ajunge un bloc dens. | Scanare dificilă, mai ales pe telefon. | Folosiți 2–3 propoziții + 3 bullets + CTA contextual. Lățime de text 45–70 caractere. | S–M |
| P2 | Ritm vertical / tranziții | Există viewport-uri întregi dominate de decor fără informație. | În scroll au apărut ecrane aproape goale cu asteroizi, valuri și stele între secțiuni. | Mărește timpul până la servicii și proof; utilizatorul poate crede că pagina s-a terminat. | Reduceți înălțimea totală și păstrați tranzițiile la 15–25% din viewport, nu 100%+. | M–L |
| P2 | SEO / title și description | Snippet-ul este lung și prea general. | Title 69 caractere; meta description 186. | Trunchiere probabilă și accent insuficient pe AI/SEO/serviciile diferențiatoare. | Folosiți varianta recomandată din secțiunea SEO: 55 și 134 caractere. | S |
| P2 | SEO / schema | `Organization` conține doar nume, URL, logo, limbă și adresă minimală. | Lipsesc `@id`, email, contactPoint, servicii, WebSite/WebPage și profiluri verificate. | Înțelegere mai slabă a entității și serviciilor; nu este o garanție de rich result. | Extindeți cu date reale și testate. Adăugați `ProfessionalService` numai dacă locația și serviciul local pot fi confirmate. | M |
| P2 | SEO local | Chișinău/Moldova apar în metadata și footer, dar nu structurează oferta și proof-ul. | H1 și H2 nu conțin context local; nu există pagini dedicate „automatizări AI Moldova”, „SEO Chișinău” etc. | Relevanță locală comercială limitată. | Integrați natural localitatea în hero/intro, studii de caz și pagini de serviciu; completați profilurile locale reale. | M–L |
| P2 | SEO / linking intern | Homepage-ul nu distribuie intenția către pagini dedicate AI, SEO și branding. | Serviciile au CTA-uri către contact, nu către pagini de detaliu. Nu există pagini locale dedicate pentru cele trei subiecte în sitemap. | Relevanță tematică și capacitate de ranking reduse; utilizatorul nu poate aprofunda. | Creați pagini de serviciu și linkuri descriptive: „Automatizări AI și CRM”, „SEO tehnic”, „Branding”. | L |
| P2 | Open Graph | Imaginea socială este corect dimensionată, dar grea. | `img/og.png` are 1200×630 și 566.210 bytes. | Share preview mai lent; impact mic asupra paginii, dar optimizabil. | Comprimați la aproximativ 150–250 KB fără pierdere vizuală evidentă. | S |
| P2 | Imagini / alt | Majoritatea decorurilor sunt corect goale, dar unele decoruri au alt inutil. | 136/151 imagini au `alt=""`; în snapshot apar „beautiful background”, „stars”, „big planet”, „small planet” și „preloader”. | Zgomot pentru cititorul de ecran; unele alt-uri informative nu adaugă informație utilă. | Decorurile: `alt=""` și, dacă sunt SVG inline, `aria-hidden="true"`. Păstrați alt descriptiv doar dacă imaginea transmite informație absentă din text. | S |
| P2 | Contact / consimțământ | Formularul separat cere un checkbox obligatoriu formulat ca acord pentru mesaje de marketing. | `/contact.html`: „Sunt de acord să primesc mesaje pe email de la Atomics”, marcat prin validare obligatorie. | Fricțiune și posibilă problemă de consimțământ; necesită revizie juridică locală. | Separați acordul necesar procesării cererii de abonarea opțională la marketing; adăugați link la politica de confidențialitate. | M |
| P2 | Footer / încredere și legal | Footerul nu expune linkuri legale sau identificarea completă a companiei. | Sunt afișate doar Chișinău, contact și navigație. | Încredere și transparență mai mici; cerințele exacte depind de forma juridică și piețe. | Adăugați denumire juridică, date de contact reale, Politica de confidențialitate, Cookies și Termeni, după validare juridică. | M |

### Probleme potențiale care necesită testare suplimentară

| Prioritate | Secțiune/element | Problemă de verificat | Dovadă actuală | Impact posibil | Recomandare | Efort |
|---|---|---|---|---|---|---|
| P1 | Producție / Core Web Vitals | Valorile reale LCP, INP și CLS nu sunt disponibile în sursele locale. | Testul de laborator arată blocare severă, dar nu există CrUX/RUM. | Poate afecta conversia și semnalele de experiență. | Verificați PageSpeed Insights, Search Console și RUM separat pe mobil/desktop, p75. | S–M |
| P1 | Producție / cache și compresie | Nu s-au putut confirma Brotli/Gzip, CDN, HTTP/2/3 și Cache-Control pe domeniul live. | Serverul local nu reproduce infrastructura de producție. | Transfer și repeat visit mai slabe. | Audit de headere live pentru HTML, CSS, JS, fonturi și imagini. | S |
| P1 | Cititoare de ecran | Pronunția exactă a textului împărțit în caractere trebuie validată în NVDA, JAWS și VoiceOver. | Arborele accesibil arată câte un generic pentru fiecare caracter. | Heading-uri citite literă cu literă sau cu pauze. | Test manual; remedierea recomandată rămâne păstrarea textului intact. | M |
| P1 | `prefers-reduced-motion` runtime | Ramura simplificată există, dar nu a fost emulată în browserul disponibil. | Inspecția arată că WebGL este evitat, dar mai multe animații CSS infinite rămân active. | Mișcare nedorită și conținut ascuns. | Test OS real în Windows/macOS și DevTools Rendering. | S |
| P2 | Zoom real 200% | Reflow-ul CSS a fost testat echivalent la 720×450, nu prin zoom browser real. | Fără overflow orizontal, dar H1 este acoperit și CTA cade sub primul viewport. | Probleme suplimentare de clipping sau focus pot apărea. | Retest Chrome/Firefox/Safari la 200% și 400%. | S |
| P2 | `hreflang` | Necesitatea depinde de existența versiunilor lingvistice reale. | Homepage română, multe pagini secundare engleză, fără structură clară de limbă. | Google poate servi limba greșită; utilizatorul ajunge într-o experiență mixtă. | Alegeți: site integral română sau URL-uri separate `/ro/` și `/en/`, cu `hreflang="ro-MD"`, `en` și `x-default`. | L |
| P2 | Target size / linkuri text | Unele ancore au 16–18 px înălțime; excepția de spațiere trebuie măsurată. | Inventarul mobil găsește multe ancore sub 24 px înălțime. | Atingeri greșite, mai ales în footer. | Măriți zona de click prin padding la minimum 44×44 px recomandat; validați WCAG 2.5.8. | S |
| P2 | Formular / livrare | Endpointul PHP, anti-spam, mesajele de eroare și succesul nu au fost trimise, pentru a evita efecte externe. | Formularul postează la `/mailing/mail.php`; nu s-a făcut submit. | Leaduri pierdute sau feedback slab. | Test controlat în staging: validare, retry, spam, timeout, confirmare și analytics. | M |
| P2 | LCP exact | Elementul LCP nu a putut fi extras fiabil prin API-ul browserului disponibil. | H1 și planeta sunt candidați; ambele apar după preloader/animații. | Prioritizare greșită a activelor. | Trace Lighthouse/DevTools și RUM; nu aplicați lazy candidatului LCP. | S |

### Preferințe estetice, nu defecte în sine

| Prioritate | Secțiune/element | Preferință | Dovadă | Impact | Recomandare | Efort |
|---|---|---|---|---|---|---|
| P3 | Direcție vizuală | Tema spațială este foarte dominantă. | Fiecare secțiune conține planete, astronauți, asteroizi și iconuri plutitoare. | Poate diferenția brandul sau poate părea juvenil, în funcție de public. | Validați prin interviuri și test A/B cu decidenți B2B; păstrați tema, dar reduceți densitatea. | M |
| P3 | Paletă CTA | Rozul și movul oferă energie, dar creează două identități pentru același control. | Linkul este roz, masca este mov; în no-JS rămâne movul. | Coerență vizuală mai slabă. | Alegeți o singură culoare primară accesibilă și o variantă secundară clară. | S |
| P3 | Titluri foarte mari | Tipografia editorială este spectaculoasă, dar produce 4–5 rânduri pe mobil. | H1 40 px, H2 de branding ocupă aproape tot viewport-ul. | Poate fi expresiv sau prea teatral pentru o ofertă complexă. | Păstrați greutatea vizuală, reduceți la `clamp(2.25rem, 7vw, 4.5rem)` și limitați lățimea. | S |

## 4. Cele mai importante 10 schimbări, în ordinea implementării

1. **Eliminați preloaderul blocant** și faceți H1, paragraful și CTA-ul vizibile din HTML/CSS la primul paint.
2. **Reparați hero-ul mobil:** conținut deasupra planetei, decor redus și verificare la 320, 360, 375, 390 și 430 px.
3. **Treceți la scroll nativ**; dezactivați parallax-ul și transformările de container pe mobil, tastatură și reduced motion.
4. **Reduceți payload-ul above-the-fold:** încărcați doar activele hero necesare, convertiți imaginile mari, lazy-load pentru sub-fold și împărțiți JS-ul.
5. **Înlocuiți value proposition-ul generic** cu un H1 care spune serviciile, publicul și localizarea.
6. **Introduceți dovada imediat după hero:** logo-uri aprobate, 2–3 rezultate contextualizate și linkuri spre studii de caz.
7. **Standardizați CTA-urile** și adăugați un formular scurt contextual pe homepage.
8. **Corectați meniul și limbile:** destinații congruente, pagini românești complete și o decizie explicită pentru versiunea engleză.
9. **Remediați accesibilitatea de bază:** skip link, focus cu contrast, meniu ARIA/Escape/focus trap, text intact în DOM și fallback no-JS.
10. **Extindeți ecosistemul SEO:** pagini AI/SEO/branding, linking intern, metadata scurtă, schema validată și monitorizare CWV/RUM.

## 5. Structura recomandată a homepage-ului

Ordinea optimizată:

1. **Header simplu** — Servicii, Proiecte, Despre, Resurse/FAQ, Contact; CTA „Solicită o discuție”.
2. **Hero** — localizare + H1 clar + beneficiu + CTA principal + CTA secundar.
3. **Trust strip** — logo-uri de clienți/parteneri aprobate sau, dacă nu există, trei fapte verificabile despre echipă și livrare.
4. **Rezultate / studii de caz scurte** — problemă, intervenție, rezultat și link spre detalii. Fără cifre până nu sunt furnizate dovezi.
5. **Problemele pe care le rezolvă Atomics** — procese manuale, site lent/neclar, vizibilitate slabă, brand inconsistent.
6. **Servicii principale, overview** — AI & CRM, Web & e-commerce, SEO & content, Branding; fiecare cu pagină dedicată.
7. **AI și automatizare** — cazuri de utilizare, integrare, ce date sunt necesare, CTA contextual.
8. **Website-uri și e-commerce** — WordPress, Shopify, UX, performanță, integrări.
9. **SEO și creștere organică** — tehnic, local, content, măsurare și limite realiste.
10. **Branding** — strategie, identitate, sistem și aplicare.
11. **Procesul în 4 pași** — Descoperire → Plan → Implementare → Măsurare/optimizare.
12. **De ce Atomics** — diferențiatori verificați, model de colaborare, comunicare, ownership.
13. **Testimoniale și clienți** — numai cu permisiune și identitate reală.
14. **FAQ** — buget, durată, ownership, mentenanță, conținut, limbă și piață.
15. **CTA final + formular scurt** — serviciu preselectabil și așteptări despre următorul pas.
16. **Footer complet** — servicii, companie, date legale/contact, politici, limbi.

## 6. Copy rescris integral

### 6.1 Hero

#### Varianta recomandată

**Eyebrow:** Agenție digitală din Chișinău, Moldova  
**H1:** Website-uri, automatizări AI și SEO pentru afaceri care vor să crească  
**Paragraf:** Planificăm, proiectăm și implementăm soluții digitale — de la automatizări și CRM la WordPress, Shopify, SEO și branding — pentru companii care vor procese mai simple și o prezență online mai eficientă.  
**CTA principal:** Solicită o discuție de 30 de minute  
**CTA secundar:** Vezi serviciile

#### Varianta directă și comercială

**Eyebrow:** Atomics — strategie, design și implementare  
**H1:** Automatizări AI și website-uri care susțin vânzările  
**Paragraf:** Spune-ne ce proces consumă timp sau ce nu funcționează în prezența ta online. Îți propunem o soluție clară, o implementăm și stabilim cum îi măsurăm efectul.  
**CTA principal:** Cere o estimare  
**CTA secundar:** Vezi ce putem construi

**Motivul modificării:** Actualul „Creăm experiențe digitale memorabile” nu definește oferta sau publicul. Noua formulare aduce serviciile prioritare, locația și un rezultat realist, fără promisiuni imposibil de demonstrat.

### 6.2 Introducerea despre Atomics

#### Varianta recomandată

**H2:** Un singur partener pentru tehnologie, vizibilitate și brand

Atomics este o agenție digitală din Chișinău. Conectăm strategia cu implementarea, astfel încât website-ul, automatizările, SEO-ul și identitatea vizuală să lucreze împreună, nu ca proiecte separate.

- **AI și automatizare:** organizăm lead-uri, eliminăm pași manuali și conectăm instrumentele echipei.
- **Web și e-commerce:** proiectăm și dezvoltăm website-uri WordPress și Shopify cu accent pe claritate, viteză și conversie.
- **SEO și brand:** construim o prezență coerentă, ușor de găsit și ușor de recunoscut.

**Dovadă de adăugat:** `[DE CERUT PROPRIETARULUI: număr de proiecte, ani de experiență, piețe deservite, certificări/parteneriate și 2–3 rezultate verificabile.]`

#### Varianta directă și comercială

**H2:** De la proces blocat la soluție lansată

Nu ai nevoie de cinci furnizori care lucrează separat. Atomics poate acoperi analiza, designul, dezvoltarea, integrarea și optimizarea, cu un plan comun și un punct clar de responsabilitate.

**CTA:** Vezi cum lucrăm

**Motivul modificării:** Textul actual încearcă să acopere totul prin superlative. Varianta nouă explică modelul de lucru și diferențiatorul operațional. Afirmația trebuie susținută ulterior prin proces și studii de caz.

### 6.3 AI și automatizare

#### Varianta recomandată

**H2:** Automatizări AI și CRM care reduc munca repetitivă

Mapăm procesul actual, identificăm blocajele și conectăm instrumentele pe care echipa le folosește deja. Putem automatiza distribuirea lead-urilor, taskurile de follow-up, actualizarea datelor și rapoartele, inclusiv prin integrarea amoCRM și a unor componente AI potrivite cazului de utilizare.

Începem cu un flux clar și măsurabil, nu cu AI adăugat doar pentru efect.

**CTA:** Discută un proces de automatizat

#### Varianta directă și comercială

**H2:** Nu mai pierde lead-uri între tabele, inboxuri și taskuri manuale

Centralizăm lead-urile, automatizăm pașii repetitivi și oferim echipei o imagine mai clară asupra următoarei acțiuni. Spune-ne cum lucrezi acum; îți arătăm unde merită automatizat și unde nu.

**CTA:** Cere o analiză a fluxului

**Motivul modificării:** „Aceste probleme devin istorie” este absolut și nedemonstrabil. Noua versiune explică mecanismul, limitează promisiunea și oferă un next step relevant.

### 6.4 Creare website

#### Varianta recomandată

**H2:** Website-uri WordPress și Shopify construite pentru viteză și conversie

Transformăm obiectivele de business într-o structură clară, un design coerent și o implementare ușor de administrat. Construim website-uri corporate și magazine online, integrăm instrumentele necesare și urmărim performanța înainte și după lansare.

- Strategie, arhitectură informațională și UX/UI
- WordPress, Shopify și integrări cu servicii terțe
- Performanță, analytics, SEO tehnic și mentenanță

**CTA:** Cere o estimare pentru website

#### Varianta directă și comercială

**H2:** Lansează un website pe care clienții îl înțeleg și echipa îl poate administra

Primești strategie, design, dezvoltare și integrare într-un singur proiect. Fără funcții adăugate inutil și fără promisiunea vagă că site-ul este „pregătit pentru viitor”.

**CTA:** Spune-ne ce vrei să lansezi

**Motivul modificării:** Secțiunea actuală repetă „memorabil” și comprimă nouă servicii într-un paragraf. Varianta nouă este scanabilă și explică livrabilele.

### 6.5 SEO

#### Varianta recomandată

**H2:** SEO tehnic și conținut pentru vizibilitate în Google și răspunsurile AI

Audităm structura, performanța, indexarea și conținutul website-ului. Apoi prioritizăm schimbările care ajută motoarele de căutare și sistemele AI să înțeleagă cine ești, ce oferi și pentru ce căutări ești relevant.

Pentru afacerile din Moldova, includem și semnalele locale: pagini de serviciu, localitate, profiluri și conținut adaptat intenției reale de căutare.

**CTA:** Solicită un audit SEO

#### Varianta directă și comercială

**H2:** Fii găsit pentru serviciile pe care vrei să le vinzi

Corectăm problemele tehnice, construim paginile care lipsesc și transformăm expertiza echipei în conținut util. Nu promitem poziții; îți oferim priorități, implementare și măsurare.

**CTA:** Vezi unde pierzi vizibilitate

**Motivul modificării:** Actualul text combină SEO, „algoritmi” și AI/LLM fără să explice livrabilele. Noua variantă diferențiază SEO tehnic, conținutul și localul și evită garanțiile de ranking.

### 6.6 Branding

#### Varianta recomandată

**H2:** O identitate de brand coerentă, de la strategie la implementare

Clarificăm poziționarea și personalitatea brandului, apoi le transformăm într-un sistem vizual care poate fi folosit consecvent. Livrabilele pot include naming, logo, paletă, tipografie, reguli de utilizare și aplicații pentru website și materiale comerciale.

**CTA:** Discută proiectul de branding

#### Varianta directă și comercială

**H2:** Nu lăsa brandul să pară improvizat

Construim un sistem vizual recognoscibil și ușor de aplicat de echipă, de la website la prezentări și materiale de vânzare.

**CTA:** Cere o propunere de branding

**Motivul modificării:** Textul actual este tradus rigid și folosește promisiuni precum „recunoaștere instantanee”. Varianta nouă definește procesul și livrabilele.

### 6.7 CTA final

#### Varianta recomandată

**H2:** Ai un proiect sau un proces care trebuie îmbunătățit?

Într-o discuție inițială clarificăm obiectivul, contextul și următorul pas potrivit. Dacă există compatibilitate, revenim cu întrebările, aria de lucru și o estimare adaptată proiectului.

**CTA principal:** Programează discuția inițială  
**CTA secundar:** Scrie-ne la contact@atomics.md

#### Varianta directă și comercială

**H2:** Spune-ne ce vrei să schimbi

Trimite-ne contextul proiectului. Îți răspundem cu întrebările necesare și pașii următori, fără prezentări generale.

**CTA principal:** Trimite detaliile proiectului  
**CTA secundar:** Vezi întrebările frecvente

**Motivul modificării:** „Începe un proiect!” nu reduce incertitudinea. Noul CTA explică ce se întâmplă după click și oferă o alternativă de contact.

## 7. Recomandări SEO și metadata rescrisă

### Intenția de căutare

Homepage-ul trebuie să acopere o intenție comercială mixtă:

- agenție digitală locală în Chișinău/Moldova;
- furnizor de website, e-commerce și WordPress;
- automatizări AI/CRM pentru procese și lead-uri;
- SEO tehnic/local și branding.

Nu încercați să rankați homepage-ul în profunzime pentru fiecare serviciu. Homepage-ul captează categoria și distribuie autoritate către pagini dedicate.

### Metadata recomandată

**Title — 55 caractere**  
`Agenție digitală în Chișinău | Web, AI și SEO | Atomics`

**Meta description — 134 caractere**  
`Automatizări AI, website-uri WordPress și Shopify, SEO și branding pentru afaceri din Moldova. Discută cu echipa Atomics din Chișinău.`

**H1**  
`Website-uri, automatizări AI și SEO pentru afaceri care vor să crească`

Canonicalul existent `https://atomics.md/` este corect pentru homepage dacă aceasta este singura versiune canonică.

### Structura H2/H3 recomandată

- H1: Website-uri, automatizări AI și SEO pentru afaceri care vor să crească
  - H2: Rezultate construite pentru obiective reale
    - H3: Studiu de caz 1
    - H3: Studiu de caz 2
    - H3: Studiu de caz 3
  - H2: Servicii digitale conectate într-un singur plan
    - H3: Automatizări AI și integrare CRM
    - H3: Website-uri WordPress și Shopify
    - H3: SEO tehnic, local și content
    - H3: Strategie și identitate de brand
  - H2: Cum lucrăm
    - H3: Descoperire
    - H3: Plan și prioritizare
    - H3: Implementare
    - H3: Măsurare și optimizare
  - H2: De ce Atomics
  - H2: Întrebări frecvente
  - H2: Hai să discutăm proiectul tău

### Cuvinte-cheie principale

- agenție digitală Chișinău;
- agenție digitală Moldova;
- web design Chișinău;
- creare site Moldova;
- dezvoltare WordPress Moldova;
- automatizări AI Moldova;
- integrare CRM / amoCRM Moldova;
- SEO Chișinău;
- agenție branding Moldova.

### Cuvinte-cheie secundare

- magazin online Shopify Moldova;
- dezvoltare website corporate;
- SEO tehnic și local;
- optimizare website;
- integrare AI în afaceri;
- automatizare lead-uri;
- identitate vizuală și naming;
- mentenanță și migrare WordPress;
- agenție web pentru companii B2B.

Folosiți termenii numai unde răspund intenției; nu urmăriți densitate artificială.

### Entități și subiecte asociate

Atomics, Chișinău, Republica Moldova, WordPress, Shopify, amoCRM, CRM, inteligență artificială, automatizare de procese, lead management, UX/UI, e-commerce, SEO tehnic, SEO local, structured data, Core Web Vitals, content strategy, branding, naming, identitate vizuală, analytics și conversie.

### JSON-LD recomandat

Exemplul de mai jos folosește numai date observate în proiect. `ProfessionalService`, telefonul, strada, programul, coordonatele și profilurile sociale trebuie adăugate numai după confirmare. Schema.org definește tipurile [LocalBusiness](https://schema.org/LocalBusiness) și [Service](https://schema.org/Service), dar schema nu trebuie să conțină date inventate.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://atomics.md/#organization",
      "name": "Atomics",
      "url": "https://atomics.md/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://atomics.md/img/logo.png"
      },
      "email": "contact@atomics.md",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Chișinău",
        "addressCountry": "MD"
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
      "name": "Agenție digitală în Chișinău | Web, AI și SEO | Atomics",
      "inLanguage": "ro-MD",
      "isPartOf": { "@id": "https://atomics.md/#website" },
      "about": { "@id": "https://atomics.md/#organization" }
    },
    {
      "@type": "ItemList",
      "@id": "https://atomics.md/#services",
      "name": "Servicii Atomics",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "item": {
            "@type": "Service",
            "name": "Automatizări AI și integrare CRM",
            "provider": { "@id": "https://atomics.md/#organization" },
            "areaServed": { "@type": "Country", "name": "Moldova" }
          }
        },
        {
          "@type": "ListItem",
          "position": 2,
          "item": {
            "@type": "Service",
            "name": "Dezvoltare website WordPress și Shopify",
            "provider": { "@id": "https://atomics.md/#organization" },
            "areaServed": { "@type": "Country", "name": "Moldova" }
          }
        },
        {
          "@type": "ListItem",
          "position": 3,
          "item": {
            "@type": "Service",
            "name": "SEO tehnic și local",
            "provider": { "@id": "https://atomics.md/#organization" },
            "areaServed": { "@type": "Country", "name": "Moldova" }
          }
        },
        {
          "@type": "ListItem",
          "position": 4,
          "item": {
            "@type": "Service",
            "name": "Branding și identitate vizuală",
            "provider": { "@id": "https://atomics.md/#organization" },
            "areaServed": { "@type": "Country", "name": "Moldova" }
          }
        }
      ]
    }
  ]
}
</script>
```

După implementare: Schema Markup Validator, Rich Results Test și URL Inspection. Google recomandă ca informația importantă să existe în HTML-ul vizibil și subliniază că nu toate boturile execută JavaScript; pre-renderingul/HTML-ul static rămâne util. [Google Search Central — JavaScript SEO](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics)

### Plan de legături interne

| Sursă homepage | Anchor recomandat | Destinație |
|---|---|---|
| Card AI | Automatizări AI și integrare CRM | `/automatizari-ai/` — pagină nouă |
| Card Web | Website-uri WordPress și Shopify | `/website-and-e-commerce.html` sau URL românesc nou cu redirect |
| Card SEO | SEO tehnic și local | `/seo/` — pagină nouă |
| Card Branding | Strategie și identitate de brand | `/branding/` — pagină nouă |
| Proof | Vezi studiul de caz [nume] | `/proiecte/[slug]/` |
| WordPress | Dezvoltare WordPress | `/solutions.html`, după localizare și redenumire |
| Mentenanță | Ajutor WordPress de urgență | `/emergency-help.html`, etichetă exactă |
| Proces | Cum lucrăm | `/despre/` sau secțiune `#proces` |
| CTA final | Discută proiectul tău | `/contact.html?serviciu=...` |

Nu trimiteți toate linkurile comerciale direct la contact. O pagină de serviciu trebuie să răspundă obiecțiilor înainte de conversie.

### Pagini și conținut lipsă

1. Automatizări AI și CRM: cazuri de utilizare, integrare, securitate, date necesare și limite.
2. SEO tehnic și local în Moldova: audit, implementare, conținut, raportare.
3. Branding: proces, livrabile, aplicații și exemple.
4. Proiecte/studii de caz: problemă, context, intervenție, rezultat, stack și testimonial.
5. Proces și model de colaborare.
6. Industrii sau tipuri de companie doar dacă există experiență reală.
7. FAQ comercial.
8. Resurse/editorial: automatizare, WordPress, Shopify, SEO local și performance.
9. Politici legale și de confidențialitate.
10. Versiune engleză completă, numai dacă piața internațională este prioritară.

### `hreflang`

Nu adăugați `hreflang` doar pentru că unele pagini sunt în engleză. Mai întâi creați versiuni echivalente și URL-uri stabile. Apoi fiecare pereche trebuie să se refere reciproc, de exemplu:

```html
<link rel="alternate" hreflang="ro-MD" href="https://atomics.md/ro/" />
<link rel="alternate" hreflang="en" href="https://atomics.md/en/" />
<link rel="alternate" hreflang="x-default" href="https://atomics.md/" />
```

## 8. Recomandări pentru animații

### Inventar și verdict

| Animație | Rol actual | Verdict | Acțiune |
|---|---|---|---|
| Preloader cu rachetă/procent | Maschează încărcarea activelor | Distrage și blochează; procentul nu reflectă clar starea reală, rămânând la 78–97% când overlay-ul dispare | Eliminați overlay-ul blocant. Dacă rămâne, non-modal, max. 500 ms. |
| Reveal literă cu literă | Intrare dramatică pentru H1/H2/paragraf | Întârzie citirea și adaugă 407 noduri; pe mobil mesajul apare fragmentat | Înlocuiți cu fade/clip pe linie sau pe întregul bloc, 250–450 ms. |
| Smooth/virtual scroll | Senzație cinematografică și parallax | Produce sărituri, focus offscreen, titluri tăiate și scroll non-standard | Eliminați pe toate dispozitivele; cel puțin opriți pe mobil, tastatură și reduced motion. |
| Parallax planete/asteroizi | Creează profunzime | Util în hero, excesiv în tranziții; consum GPU și afectează stackingul | Păstrați maximum 2–3 straturi desktop, amplitudine mică. |
| Mouse-follow pe sprite-uri | Răspuns la cursor | Decorativ și costisitor, inutil pe touch | Doar desktop cu pointer fin; opriți sub 1024 px. |
| WOW fades 1–2,5 s | Introduce elemente la scroll | Prea lent; unele elemente pornesc invizibile | 180–350 ms, o singură dată, fallback vizibil. |
| CTA mask 70 steps / 0,3 s | Feedback hover | Acceptabil ca durată, dar duplică textul și schimbă culoarea/copia | Un singur label; animați pseudo-elementul, nu textul. |
| Chevroane infinite 2,1 s | Sugerează scroll | Utile o singură dată, apoi devin zgomot | Opriți după 2 cicluri; ascundeți `aria-hidden="true"`. |
| Stele/twinkling 200 s și rotații infinite | Ambient | Consum continuu fără informație | Reduceți la un fundal static sau animație foarte rară; opriți în reduced motion/tab ascuns. |
| GIF/footer și ilustrații animate | Atmosferă | Poate distra lângă CTA și adaugă transfer | Poster static; video WebM/MP4 controlat doar dacă valoarea vizuală este demonstrată. |

### Durate și easing recomandate

- hover/focus: 120–180 ms, `cubic-bezier(.2,.8,.2,1)`;
- intrare bloc de conținut: 250–450 ms, aceeași curbă;
- tranziție secțiune: maximum 500–700 ms, fără a bloca scroll-ul;
- parallax: legat de scroll nativ, amplitudine 8–24 px, fără inerție lungă;
- zero delay pe H1, paragraf și CTA;
- nicio animație esențială cu durată 1,5–2,5 s.

### Reduced motion și fallback

```css
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto !important; }

  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }

  .char,
  .word,
  [data-is-animation],
  .js-scrollable-img-wrapper {
    opacity: 1 !important;
    transform: none !important;
  }

  .scroll-down-anim-icon,
  .main-canvas {
    display: none !important;
  }
}

.no-js .js-preloader,
.no-js .main-canvas,
.no-js .mobile-hamburger {
  display: none !important;
}

.no-js .nav,
.no-js .footer-list__links {
  display: block !important;
  transform: none !important;
  visibility: visible !important;
  opacity: 1 !important;
}
```

WCAG cere ca mișcarea declanșată de interacțiune să poată fi dezactivată când nu este esențială. [W3C — Animation from Interactions](https://www.w3.org/WAI/WCAG22/Understanding/animation-from-interactions.html)

## 9. Recomandări tehnice cu exemple HTML/CSS/JS

### 9.1 Skip link și focus robust

```html
<body>
  <a class="skip-link" href="#main-content">Sari la conținut</a>
  <header>...</header>
  <main id="main-content">...</main>
</body>
```

```css
.skip-link {
  position: fixed;
  inset: 8px auto auto 8px;
  z-index: 100000;
  transform: translateY(-150%);
  padding: 12px 16px;
  color: #101018;
  background: #fff;
}

.skip-link:focus { transform: none; }

:focus-visible {
  outline: 3px solid #fff;
  outline-offset: 3px;
  box-shadow: 0 0 0 6px #101018;
}

section, h1, h2, h3 { scroll-margin-top: 105px; }
```

### 9.2 CTA fără text duplicat

```html
<a class="cta cta--primary" href="/contact.html?serviciu=website">
  Cere o estimare pentru website
</a>
```

```css
.cta {
  position: relative;
  display: inline-flex;
  min-height: 48px;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  border-radius: 999px;
  overflow: hidden;
}

.cta::before {
  content: "";
  position: absolute;
  inset: 0;
  transform: translateX(-101%);
  transition: transform 160ms cubic-bezier(.2,.8,.2,1);
  background: #7c05e2;
}

.cta:hover::before,
.cta:focus-visible::before { transform: none; }

.cta { isolation: isolate; }
.cta::before { z-index: -1; }
```

### 9.3 Meniu mobil accesibil

```js
const toggle = document.querySelector('.mobile-hamburger');
const nav = document.querySelector('#main-nav');
const main = document.querySelector('main');

function setMenu(open) {
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'Închide meniul' : 'Deschide meniul');
  nav.classList.toggle('is-open', open);
  main.inert = open;

  if (open) {
    nav.querySelector('a')?.focus();
  } else {
    main.inert = false;
    toggle.focus();
  }
}

toggle.addEventListener('click', () => {
  setMenu(toggle.getAttribute('aria-expanded') !== 'true');
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
    setMenu(false);
  }
});
```

Pentru producție, completați cu focus trap în nav și închidere la activarea unui link.

### 9.4 Strategie pentru imaginea LCP și imaginile sub-fold

Nu aplicați lazy loading imaginii LCP. Identificați întâi elementul prin trace. Dacă planeta sau fundalul hero este LCP:

```html
<picture>
  <source type="image/avif" srcset="/img/hero/planet-640.avif 640w, /img/hero/planet-960.avif 960w">
  <source type="image/webp" srcset="/img/hero/planet-640.webp 640w, /img/hero/planet-960.webp 960w">
  <img
    src="/img/hero/planet-960.png"
    alt=""
    width="960"
    height="960"
    sizes="(max-width: 767px) 70vw, 48vw"
    fetchpriority="high"
    decoding="async"
  >
</picture>
```

Pentru imagine sub-fold:

```html
<img
  src="/img/services/automation.webp"
  alt=""
  width="720"
  height="900"
  loading="lazy"
  decoding="async"
>
```

### 9.5 Bugete și impact estimat

| Optimizare | Impact probabil | Estimare orientativă |
|---|---|---|
| Eliminare preloader | Foarte mare | Conținut util cu zeci de secunde mai devreme în testul lent; LCP perceput schimbat radical. |
| Lazy pentru imaginile sub-fold | Foarte mare | 70–90% mai puține imagini cerute înainte de interacțiune, dacă sunt păstrate doar activele hero. |
| PNG mari → AVIF/WebP | Mare | 50–85% reducere pentru imaginile raster, în funcție de alpha și calitate. |
| Scoaterea `hero-man.png` de 2,42 MB din încărcarea inițială | Mare | Peste 2 MB eliminați din competiția inițială. |
| Scroll nativ + reducere Pixi/GSAP | Mare | JS, CPU/GPU și memorie mai mici; INP și bateria mobilă probabil mai bune. |
| Eliminarea a 407 `.char` | Mediu | DOM și style/layout work mai mici; accesibilitate mai predictibilă. |
| CSS purge și split per pagină | Mediu–mare | 30–70% reducere posibilă din 402 KB, de validat după coverage. |
| JS code-splitting | Mediu–mare | 30–60% reducere posibilă din 1,43 MB dacă motoarele de animație sunt condiționale. |
| WOFF2 only + subset română | Mediu | 100–250 KB posibil eliminați; mai puține requesturi și swap mai scurt. |
| OG compression | Mic | 300–400 KB economisiți la fetch-ul social. |

### 9.6 Core Web Vitals

- **LCP:** preloaderul și concurența celor 151 de imagini sunt riscul principal. H1 trebuie să fie randat imediat; imaginea LCP nu se lazy-load-uiește.
- **CLS:** imaginile trebuie să aibă `width`/`height` sau `aspect-ratio`. Transformările nu produc mereu layout shift în metrică, dar produc instabilitate vizuală reală.
- **INP:** bundle-ul mare, WebGL/canvas, mouse-follow, listeners de scroll și GSAP pot bloca main thread-ul. Măsurați long tasks pe un telefon mid-range.
- **RUM:** instalați `web-vitals` și trimiteți LCP/INP/CLS cu context de rută și dispozitiv. Laboratorul nu măsoară INP real; TBT este doar proxy. [Web Vitals](https://web.dev/articles/vitals)

### 9.7 Cache și livrare

- fișiere fingerprinted, fără dependență principală de querystring pentru invalidare;
- `Cache-Control: public, max-age=31536000, immutable` pentru active hash-uite;
- HTML cu TTL scurt și revalidare;
- Brotli pentru HTML/CSS/JS/SVG, Gzip fallback;
- HTTP/2 sau HTTP/3;
- CDN pentru imagini și variante responsive;
- preload numai pentru fonturile și activul LCP cu adevărat critice;
- nu preîncărcați toate ilustrațiile.

## 10. Plan de implementare

### Quick wins — 1–2 zile

1. Scoateți preloaderul blocant sau faceți-l non-modal și sub 500 ms.
2. Corectați stackingul hero pe mobil și verificați 360/390 px.
3. Adăugați skip link, focus vizibil și `scroll-margin-top`.
4. Actualizați hamburgerul: ARIA, Escape și etichetă.
5. Faceți textele vizuale și accesibile ale CTA-urilor identice.
6. Standardizați CTA-urile: „Solicită o discuție”, „Vezi serviciul”, „Cere un audit”.
7. Implementați metadata recomandată.
8. Adăugați `loading="lazy"` și `decoding="async"` imaginilor sub-fold; nu atingeți LCP înainte de măsurare.
9. În `.no-js`, afișați nav/footer și ascundeți hamburgerul.
10. Reparați contrastul butoanelor și focusului.

### Îmbunătățiri într-o săptămână

1. Înlocuiți text splitting cu animații pe bloc/linie.
2. Dezactivați scroll-ul virtual pe mobil și reduced motion; pregătiți migrarea completă la scroll nativ.
3. Optimizați cele mai grele 10 imagini și generați AVIF/WebP responsive.
4. Împărțiți bundle-ul și încărcați modulele de animație numai unde sunt necesare.
5. Implementați noul hero, intro și secțiunile de servicii cu copy-ul rescris.
6. Adăugați formularul scurt în CTA-ul final și tracking de conversie.
7. Corectați meniul și localizați paginile care primesc trafic din homepage.
8. Introduceți procesul în 4 pași și un FAQ minim.
9. Extindeți JSON-LD cu date confirmate și validați-l.
10. Rulați axe/Lighthouse, NVDA și testare pe telefon mid-range.

### Schimbări strategice ulterioare

1. Studii de caz complete și sistem de colectare a testimonialelor.
2. Pagini dedicate AI, SEO și branding, cu intenție locală și internațională separată.
3. Strategie lingvistică `/ro/` și `/en/` cu redirecturi și hreflang.
4. Sistem de design cu reguli clare pentru CTA, culori, iconuri și motion.
5. RUM pentru Core Web Vitals, funnel analytics și evenimente CTA/formular.
6. Program editorial bazat pe întrebările reale din vânzări și proiecte.
7. Test A/B între hero-ul cinematic redus și o variantă axată pe proof.

### Informații de cerut proprietarului

- segmentele de clienți prioritare și piețele țintă;
- proiectele și rezultatele ce pot fi publicate, cu sursa cifrelor;
- testimonialele și permisiunile pentru logo-uri;
- forma juridică, adresa completă, telefonul și programul;
- parteneriate/certificări reale, inclusiv statutul amoCRM dacă există;
- procesul real, intervalele de buget și durata tipică;
- limbile și țările în care echipa livrează;
- calendarul/linkul de programare și timpul real de răspuns;
- politica de mentenanță, ownership și suport;
- baseline-ul actual: trafic, leaduri, rata de conversie și sursele lor.

## 11. Checklist final înainte de lansare

### Conținut și conversie

- [ ] H1 explică oferta, publicul și/sau contextul local.
- [ ] CTA principal este unic și consecvent în hero.
- [ ] Fiecare serviciu are CTA contextual și pagină dedicată.
- [ ] Există minimum două dovezi verificabile înainte de al doilea scroll major.
- [ ] Cifrele, clienții și testimonialele au aprobare și sursă.
- [ ] Formularul spune clar ce urmează după trimitere.
- [ ] Acordul de marketing este opțional și separat de procesarea cererii.

### Responsive și UX

- [ ] 320, 360, 375, 390, 430, 768, 1024, 1280 și 1440 px verificate.
- [ ] Niciun decor nu acoperă H1, copy sau CTA.
- [ ] Nicio secțiune nu începe sub header.
- [ ] Nu există ecrane goale între secțiuni.
- [ ] Scroll-ul este nativ și ancorele/Back/Forward funcționează.
- [ ] Meniul se deschide/închide, Escape funcționează și focusul revine.

### Accesibilitate WCAG 2.2 AA

- [ ] Skip link funcțional.
- [ ] Focus vizibil cu contrast suficient pe orice fundal.
- [ ] Ordinea Tab urmează ordinea vizuală și elementul focalizat intră în viewport.
- [ ] Meniul are `aria-expanded`, nume corect și focus trap.
- [ ] CTA-urile au un singur nume accesibil.
- [ ] Decorativele au `alt=""`/`aria-hidden`; imaginile informative au alt util.
- [ ] Contrast text normal ≥4,5:1; text mare ≥3:1.
- [ ] Zonele interactive respectă minimumul și au spacing adecvat.
- [ ] Reflow la 200% și 400% fără scroll în două direcții pentru conținut normal. [W3C Reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html)
- [ ] NVDA, VoiceOver și tastatură testate manual.

### Motion și fallback

- [ ] `prefers-reduced-motion` oprește parallax, scroll virtual și loop-uri.
- [ ] Conținutul este vizibil înainte ca animația să pornească.
- [ ] Fără JS: conținut, nav, footer și formular accesibile.
- [ ] Animațiile nu depășesc 500–700 ms fără motiv funcțional.
- [ ] Pagina nu consumă CPU continuu când nu există interacțiune.

### Performanță

- [ ] Elementul LCP identificat și nu este lazy-loaded.
- [ ] Imaginile sub-fold sunt lazy și responsive.
- [ ] Dimensiuni explicite pentru toate imaginile.
- [ ] AVIF/WebP cu fallback unde aduc reducere reală.
- [ ] CSS/JS coverage analizat și codul neutilizat eliminat.
- [ ] Fonturile critice WOFF2 și subsetate; număr minim de greutăți.
- [ ] Brotli/Gzip, cache immutable și CDN verificate live.
- [ ] Lighthouse mobil repetat de minimum trei ori.
- [ ] RUM: LCP, INP, CLS la p75 pe mobil și desktop.

### SEO

- [ ] Title, description, canonical și robots corecte pe fiecare URL.
- [ ] Un singur H1 și ierarhie H2/H3 logică.
- [ ] `robots.txt` și sitemap returnează 200 și URL-uri canonice.
- [ ] Linkurile interne au anchor descriptiv și destinație congruentă.
- [ ] Pagini românești și engleze separate și complete înainte de hreflang.
- [ ] JSON-LD valid și bazat numai pe date reale.
- [ ] OG image comprimată și testată în share debuggers.
- [ ] Search Console: URL Inspection pe homepage și servicii.
- [ ] Nu există resurse esențiale blocate sau conținut rămas permanent la `opacity:0`.

## Verdict

Homepage-ul are un concept vizual diferențiator, dar în forma actuală pune spectacolul înaintea clarității și încărcarea înaintea accesului. Prioritatea nu este o redesenare completă, ci recuperarea conținutului: afișare imediată, scroll nativ, hero mobil lizibil, proof și CTA-uri coerente. După aceste remedieri, tema spațială poate rămâne un avantaj de brand în loc să fie principala sursă de fricțiune.
