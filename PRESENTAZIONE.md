# WooCommerce Address Book

## Panoramica

**WooCommerce Address Book** e un plugin per WordPress/WooCommerce che consente ai clienti di salvare piu indirizzi di fatturazione e spedizione nel proprio account e di selezionarli rapidamente durante il checkout.

Sviluppato da **CrossPeak Software** | Versione **3.0.3** | Licenza GPLv2+

---

## Problema che risolve

Nell'e-commerce, i clienti spesso spediscono a indirizzi diversi: casa, ufficio, familiari, sedi aziendali. WooCommerce di base permette di salvare un solo indirizzo di fatturazione e uno di spedizione.

**WooCommerce Address Book** elimina questa limitazione, permettendo ai clienti di gestire una rubrica completa di indirizzi e scegliere quello giusto al momento del pagamento con un solo click.

---

## Funzionalita principali

### Per il cliente

- **Rubrica indirizzi multipli** - Salva piu indirizzi di fatturazione e spedizione dal proprio account
- **Selezione rapida al checkout** - Scelta dell'indirizzo tramite menu a tendina o pulsanti radio durante il pagamento
- **Soprannomi indirizzi** - Assegna nomi identificativi come "Casa", "Ufficio", "Magazzino" per riconoscere gli indirizzi facilmente
- **Indirizzo predefinito** - Imposta un indirizzo come predefinito per pre-compilare il checkout
- **Modifica e cancellazione** - Gestione completa degli indirizzi dal pannello "Il mio account"
- **Importazione CSV** - Importa indirizzi in blocco da file CSV
- **Esportazione CSV** - Esporta tutti gli indirizzi salvati per backup o migrazione

### Per l'amministratore

- **Abilita/Disabilita separatamente** - Controlla indipendentemente la rubrica per fatturazione e spedizione
- **Limite indirizzi** - Imposta un numero massimo di indirizzi salvabili per utente (o illimitato)
- **Soprannome al checkout** - Scegli se permettere la creazione del soprannome gia al checkout
- **"Aggiungi nuovo" come default** - Opzione per mostrare un form vuoto al checkout invece dell'indirizzo predefinito
- **Modalita selezione** - Scelta tra menu a tendina (select) o pulsanti radio per la selezione indirizzo
- **Blocco campi readonly** - Impedisce la modifica di campi protetti durante il cambio indirizzo
- **Importazione/Esportazione** - Attivabile/disattivabile dal pannello impostazioni

---

## Architettura tecnica

### Requisiti

| Requisito | Versione minima |
|-----------|----------------|
| WordPress | 6.0+ |
| WooCommerce | Testato fino a 8.9.1 |
| PHP | 7.4+ |

### Compatibilita

- **HPOS** (High-Performance Order Storage) - Pienamente compatibile
- **WooCommerce Subscriptions** - Supporto integrato per rinnovi abbonamenti
- **Campi personalizzati** - Compatibile con checkout field editor e filtri standard WooCommerce
- **Temi** - Template sovrascrivibili dal tema (`yourtheme/woocommerce/myaccount/`)

### Struttura del plugin

```
woo-address-book/
├── woocommerce-address-book.php   # Bootstrap e controllo dipendenze
├── includes/
│   ├── class-address-book.php     # Classe principale Address_Book (CRUD)
│   ├── address-book.php           # Integrazione checkout e My Account
│   ├── ajax.php                   # Handler AJAX (elimina, predefinito, checkout)
│   ├── api.php                    # REST API (GET/POST/PUT/DELETE)
│   ├── general.php                # Script, stili, localizzazione
│   ├── settings.php               # Pagina impostazioni admin
│   ├── validation.php             # Validazione campi indirizzo
│   ├── nickname.php               # Gestione soprannomi
│   ├── import.php                 # Importazione CSV
│   ├── export.php                 # Esportazione CSV
│   └── subscriptions.php          # Supporto WooCommerce Subscriptions
├── templates/myaccount/
│   ├── my-address-book.php        # Template rubrica indirizzi
│   └── add-address-button.php     # Template pulsante aggiungi
├── assets/
│   ├── css/style.css              # Stili rubrica
│   └── js/scripts.js              # Logica frontend (AJAX, selezione)
└── languages/                     # File di traduzione (.pot, .po, .mo)
```

### REST API

Il plugin espone endpoint RESTful sotto `wc/v3/`:

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `GET` | `/customers/{id}/addresses` | Lista tutti gli indirizzi |
| `GET` | `/customers/{id}/addresses/{type}` | Lista indirizzi per tipo |
| `POST` | `/customers/{id}/addresses/{type}` | Crea nuovo indirizzo |
| `PUT` | `/customers/{id}/addresses/{type}/{id}` | Modifica indirizzo |
| `DELETE` | `/customers/{id}/addresses/{type}/{id}` | Elimina indirizzo |

### Internazionalizzazione

- **72 stringhe traducibili** con text domain `woo-address-book`
- Traduzioni incluse: **Inglese** (en_US), **Italiano** (it_IT)
- Compatibile con il sistema di traduzione WordPress.org (GlotPress)
- Localizzazione JavaScript tramite `wp_localize_script()`

---

## Configurazione

Le impostazioni si trovano in **WooCommerce > Impostazioni > Address Book**.

### Impostazioni disponibili

| Impostazione | Default | Descrizione |
|-------------|---------|-------------|
| Rubrica fatturazione | Attiva | Abilita la rubrica per gli indirizzi di fatturazione |
| Rubrica spedizione | Attiva | Abilita la rubrica per gli indirizzi di spedizione |
| "Aggiungi nuovo" al checkout | Disattiva | Mostra form vuoto come opzione predefinita |
| Soprannome al checkout | Disattiva | Permette di aggiungere soprannomi direttamente al checkout |
| Limite indirizzi fatturazione | 0 (illimitato) | Numero massimo di indirizzi fatturazione |
| Limite indirizzi spedizione | 0 (illimitato) | Numero massimo di indirizzi spedizione |
| Strumenti import/export | Disattiva | Mostra strumenti di importazione/esportazione nell'account |
| Blocca campi readonly | Disattiva | Impedisce la modifica di campi protetti |
| Input radio | Disattiva | Usa pulsanti radio al posto del menu a tendina |

---

## Installazione

1. Caricare il file `woo-address-book.zip` da **Plugin > Aggiungi nuovo > Carica plugin**
2. Verificare che WooCommerce sia installato e attivo
3. Attivare **WooCommerce Address Book** dalla pagina Plugin
4. Configurare le opzioni in **WooCommerce > Impostazioni > Address Book**

---

## Versione PRO

E disponibile una versione professionale con funzionalita aggiuntive:

- **Supporto backoffice** - Selezione dalla rubrica del cliente durante la creazione ordini da admin
- **Gestione indirizzi da admin** - Modifica la rubrica indirizzi dei clienti direttamente dal loro profilo utente

---

## Risorse

- **Demo**: https://woo-address-book.crosspeak.dev
- **Codice sorgente**: https://github.com/crosspeaksoftware/woo-address-book
- **Segnalazione bug**: https://github.com/crosspeaksoftware/woo-address-book/issues
