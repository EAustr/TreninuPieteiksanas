# Volejbola treniņu plānošanas sistēma

Šis projekts ir tīmekļa lietotne, kas izstrādāta, lai atvieglotu un organizētu volejbola treniņu plānošanu. Lietotne ļauj pārskatīt, izveidot un pārvaldīt treniņu sesijas, piešķirt trenerus un nodrošināt pārskatāmu kalendāru lietotājiem.

## Izmantotās tehnoloģijas

- **Laravel** – PHP ietvarprogramma servera puses loģikas izstrādei, datu apstrādei, autentifikācijai un maršrutēšanai.
- **Vue.js** – moderna JavaScript ietvarprogramma lietotāja saskarnes veidošanai, kas ļauj izstrādāt dinamiskas SPA (Single Page Applications).
- **Inertia.js** – rīks, kas savieno Laravel un Vue.js, padarot iespējamu viena lapas lietotni bez nepieciešamības veidot API.
- **Tailwind CSS** – utilītbāzēts CSS ietvars, kas ļauj ātri un elastīgi izstrādāt mūsdienīgu un pielāgojamu dizainu.
- **SQLite** – viegla un ērta relāciju datubāze, piemērota izstrādes un neliela apjoma lietojumiem.
- **TablePlus** – grafisks datubāzu pārvaldības rīks, kas izmantots datu struktūru pārskatīšanai, rediģēšanai un SQL vaicājumu izpildei.

## Funkcionalitāte

- Treniņu kalendārs ar pārskatāmu skatu uz gaidāmajiem un iepriekšējiem treniņiem.
- Iespēja izveidot jaunu treniņu, norādot:
  - **Datumu un laiku**
  - **Aprakstu**
  - **Treneri**, kurš vadīs konkrēto treniņu
- Treniņu plānošana ierobežota līdz **30 dienām uz priekšu**, lai nodrošinātu strukturētu grafiku.
- Lomu bāzēta piekļuves kontrole – tikai autorizēti lietotāji var veidot un rediģēt treniņus.

## Sistēmas palaišanas vadlīnijas
```
git clone <repo-url>
cd <project-folder>
composer install
npm install 
php artisan migrate
composer run dev
```

## Sistēmas prasības (Lietas kurām jābūt ieinstelētām lai sistēmu varētu palaist uz datora)
- PHP 8.1 vai jaunāks
- Composer 
- Node.js un npm 
- Teksta redaktors(VSCode, PHPStorm, u.c.)
- (Papildus) TablePlus datubāzes pārlūkošanai
- (Papildus) Git versiju kontrole


## Testēšana
- php artisan test
Komanda palaidīs visus automatīskos testus kuri ir izveidoti