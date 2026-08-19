<?php

/**
 * Pubvana CMS - Admin language strings (Italian)
 *
 * Convention: snake_case v1 key → camelCase CI4 key
 * Keys are grouped by feature section with comment headers.
 *
 * Usage: lang('Admin.keyName')
 */

return [

    // =========================================================================
    // Common UI - buttons, labels, confirmations, status badges
    // =========================================================================

    // Buttons
    'save'              => 'Salva',
    'saveChanges'       => 'Salva modifiche',
    'cancel'            => 'Annulla',
    'edit'              => 'Modifica',
    'delete'            => 'Elimina',
    'create'            => 'Crea',
    'add'               => 'Aggiungi',
    'back'              => 'Indietro',
    'view'              => 'Visualizza',
    'apply'             => 'Applica',
    'install'           => 'Installa',
    'update'            => 'Aggiorna',
    'refresh'           => 'Aggiorna',
    'activate'          => 'Attiva',
    'deactivate'        => 'Disattiva',
    'enable'            => 'Abilita',
    'disable'           => 'Disabilita',
    'disabled'          => 'Disabilitato',
    'approve'           => 'Approva',
    'spam'              => 'Spam',
    'trash'             => 'Cestino',
    'restore'           => 'Ripristina',
    'dismiss'           => 'Ignora',
    'recheck'           => 'Ricontrolla',
    'clickToCopy'       => 'Clicca per copiare',
    'download'          => 'Scarica',
    'upload'            => 'Carica',
    'import'            => 'Importa',
    'export'            => 'Esporta',
    'publish'           => 'Pubblica',
    'unpublish'         => 'Rimuovi pubblicazione',
    'logout'            => 'Disconnetti',
    'viewSite'          => 'Visualizza sito',
    'newPost'           => 'Nuovo articolo',
    'buyNow'            => 'Acquista ora',
    'visitStore'        => 'Visita il negozio',
    'loadMore'          => 'Carica altro',

    // Table headers / labels
    'title'             => 'Titolo',
    'name'              => 'Nome',
    'slug'              => 'Slug',
    'status'            => 'Stato',
    'date'              => 'Data',
    'actions'           => 'Azioni',
    'author'            => 'Autore',
    'views'             => 'Visualizzazioni',
    'type'              => 'Tipo',
    'url'               => 'URL',
    'description'       => 'Descrizione',
    'role'              => 'Ruolo',
    'email'             => 'Email',
    'username'          => 'Nome utente',
    'active'            => 'Attivo',
    'version'           => 'Versione',
    'size'              => 'Dimensione',
    'clicks'            => 'Clic',
    'total'             => 'Totale',
    'platform'          => 'Piattaforma',
    'label'             => 'Etichetta',
    'order'             => 'Ordine',
    'source'            => 'Sorgente',
    'content'           => 'Contenuto',
    'excerpt'           => 'Estratto',
    'details'           => 'Dettagli',
    'contentType'       => 'Tipo di contenuto',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta titolo',
    'metaDescription'   => 'Meta descrizione',

    // Status badges
    'published'         => 'Pubblicato',
    'draft'             => 'Bozza',
    'scheduled'         => 'Pianificato',
    'pending'           => 'In attesa',
    'safe'              => 'Sicuro',
    'notSafe'           => 'Non sicuro',
    'malicious'         => 'Dannoso',
    'safetyUnknown'     => 'Sconosciuto',
    'inactive'          => 'Inattivo',
    'installed'         => 'Installato',
    'free'              => 'Gratuito',
    'premium'           => 'Premium',
    'all'               => 'Tutti',

    // Confirmations
    'confirmDelete'         => 'Sei sicuro di voler eliminare questo elemento?',
    'confirmDeletePost'     => 'Eliminare questo articolo?',
    'confirmDeletePage'     => 'Eliminare questa pagina?',
    'confirmDeleteComment'  => 'Eliminare definitivamente questo commento?',
    'confirmDeleteUser'     => 'Eliminare questo utente?',
    'confirmDeleteMedia'    => 'Eliminare?',
    'confirmDeleteBackup'   => 'Eliminare questo file di backup?',
    'confirmBulkAction'     => 'Applicare l\'azione in blocco agli articoli selezionati?',

    // Empty states
    'noPostsYet'        => 'Nessun articolo ancora. {0}',
    'noResultsFound'    => 'Nessun risultato trovato.',
    'noCommentsYet'     => 'Nessun commento in attesa.',
    'noMediaYet'        => 'Nessun media ancora.',
    'noItemsFound'      => 'Nessun elemento trovato nel marketplace.',
    'noCategoriesYet'   => 'Nessuna categoria ancora.',
    'noTagsYet'         => 'Nessun tag ancora.',
    'noRevisionsYet'    => 'Nessuna revisione trovata.',

    // Misc common
    'permissionDenied'  => 'Permesso negato.',
    'notFound'          => 'Record non trovato.',
    'commasSeparated'   => 'Separati da virgola',
    'optional'          => 'Facoltativo',
    'required'          => 'Obbligatorio',
    'enabled'           => 'Abilitato',
    'selected'          => '{0} articolo/i selezionato/i',
    'published_count'   => '{0} pubblicato/i',
    'pending_count'     => '{0} in attesa',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Dashboard',
    'navContent'        => 'Contenuti',
    'navAppearance'     => 'Aspetto',
    'navUsersAndSite'   => 'Utenti e sito',
    'navTools'          => 'Strumenti',
    'navMarketplace'    => 'Marketplace',
    'navPlugins'        => 'Plugin',
    'navPosts'          => 'Articoli',
    'navSchedule'       => 'Pianificazione',
    'navPages'          => 'Pagine',
    'navCategories'     => 'Categorie',
    'navTags'           => 'Tag',
    'navComments'       => 'Commenti',
    'navMedia'          => 'Media',
    'navImport'         => 'Importa',
    'navThemes'         => 'Temi',
    'navWidgets'        => 'Widget',
    'navNavigation'     => 'Navigazione',
    'navUsers'          => 'Utenti',
    'navSocialLinks'    => 'Link social',
    'navRedirects'      => 'Reindirizzamenti',
    'navLanguages'      => 'Lingue',
    'navSettings'       => 'Impostazioni',
    'navAnalytics'      => 'Analisi',
    'navAffiliates'     => 'Link affiliati',
    'navBrokenLinks'    => 'Link non funzionanti',
    'navActivityLog'    => 'Registro attività',
    'navBackup'         => 'Backup ed esportazione',
    'navUpdates'        => 'Aggiornamenti',
    'navBrowse'         => 'Sfoglia',
    'navLicenses'       => 'Licenze',
    'navPubvanaStore'   => 'Negozio Pubvana',
    'navUpdateAvailable'=> 'Aggiornamento disponibile',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Pronto ad uscire?',
    'logoutModalBody'   => 'Seleziona "Disconnetti" qui sotto per terminare la sessione.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Dashboard',
    'dashStats'             => 'Statistiche',
    'dashPosts'             => 'Articoli',
    'dashPages'             => 'Pagine',
    'dashComments'          => 'Commenti',
    'dashUsers'             => 'Utenti',
    'dashRecentPosts'       => 'Articoli recenti',
    'dashPendingComments'   => 'Commenti in attesa',
    'dashViewAll'           => 'Vedi tutti',
    'dashCreateOne'         => 'Creane uno!',
    'dashNoPosts'           => 'Nessun articolo ancora.',
    'dashNoPendingComments' => 'Nessun commento in attesa.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Articoli',
    'newPostTitle'          => 'Nuovo articolo',
    'editPostTitle'         => 'Modifica articolo: {0}',
    'copyPreviewLink'       => 'Copia link di anteprima',
    'backToPosts'           => 'Torna agli articoli',
    'postTitleField'        => 'Titolo *',
    'postEditor'            => 'Editor',
    'postHtmlEditor'        => 'Editor HTML',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Estratto',
    'postExcerptPlaceholder'=> 'Breve riepilogo opzionale...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta titolo',
    'postMetaDescription'   => 'Meta descrizione',
    'postPublishSection'    => 'Pubblica',
    'postStatus'            => 'Stato',
    'postStatusDraft'       => 'Bozza',
    'postStatusPublished'   => 'Pubblicato',
    'postStatusScheduled'   => 'Pianificato',
    'postScheduledAt'       => 'Data e ora pianificate',
    'postFeatured'          => 'Articolo in evidenza',
    'postMembersOnly'       => 'Solo per membri',
    'postShareOnPublish'    => 'Condividi sui social alla pubblicazione',
    'postSaveBtn'           => 'Salva articolo',
    'postFeaturedImage'     => 'Immagine in evidenza',
    'postFeaturedImagePlaceholder' => 'URL o percorso di caricamento…',
    'postCategories'        => 'Categorie',
    'postTags'              => 'Tag',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'Revisioni',
    'postRevisionCount'     => '{0} revisione/i',
    'postPreview'           => 'Anteprima',
    'postBulkAction'        => '- Seleziona azione -',
    'postBulkPublish'       => 'Pubblica',
    'postBulkUnpublish'     => 'Rimuovi pubblicazione (imposta come bozza)',
    'postBulkDelete'        => 'Elimina',

    // Post flash messages
    'postCreated'           => 'Articolo creato con successo.',
    'postUpdated'           => 'Articolo aggiornato.',
    'scheduledDateMustBeFuture' => 'La data pianificata deve essere nel futuro.',
    'postDeleted'           => 'Articolo eliminato.',
    'postBulkUpdated'       => '{0} articolo/i aggiornato/i.',
    'postBulkInvalid'       => 'Azione in blocco non valida.',
    'postPermission'        => 'Puoi modificare solo i tuoi articoli.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revisioni: {0}',
    'revisionTitle'         => 'Revisione — {0}',
    'revisionShowTitle'     => 'Revisione',
    'revisionsBackToPost'   => "Torna all'articolo",
    'revisionsBackToList'   => 'Torna alle revisioni',
    'revisionRestored'      => 'Articolo ripristinato alla revisione del {0}.',
    'revisionRestoreBtn'    => 'Ripristina questa revisione',
    'revisionSaved'         => 'Salvato',
    'revisionBy'            => 'Di',
    'revisionOn'            => 'Il',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Pagine',
    'newPageTitle'          => 'Nuova pagina',
    'editPageTitle'         => 'Modifica pagina',
    'pageSlugInUse'         => "Lo slug '{0}' è già in uso.",
    'pageCannotDelete'      => 'Impossibile eliminare questa pagina.',
    'slugAutoGenHint'       => 'generato automaticamente dal titolo se lasciato vuoto',
    'slugCannotChange'      => 'non modificabile',
    'colSystem'             => 'Sistema',
    'system'                => 'Sistema',

    // Page flash messages
    'pageCreated'           => 'Pagina creata.',
    'pageUpdated'           => 'Pagina aggiornata.',
    'pageDeleted'           => 'Pagina eliminata.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Categorie',
    'newCategoryTitle'      => 'Nuova categoria',
    'editCategoryTitle'     => 'Modifica categoria',
    'categoryName'          => 'Nome',
    'categoryDescription'   => 'Descrizione',
    'categoryPostCount'     => 'Numero di articoli',

    // Category flash messages
    'categoryCreated'       => 'Categoria creata.',
    'categoryUpdated'       => 'Categoria aggiornata.',
    'categoryDeleted'       => 'Categoria eliminata.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Tag',
    'tagPostCount'          => 'Numero di articoli',

    // Tag flash messages
    'tagDeleted'            => 'Tag eliminato.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Commenti',
    'commentAuthor'         => 'Autore',
    'commentContent'        => 'Commento',
    'commentPost'           => 'Articolo',
    'commentDate'           => 'Data',
    'commentStatusFilter'   => 'Filtra per stato',

    // Comment flash messages
    'commentApproved'       => 'Commento approvato.',
    'commentSpam'           => 'Contrassegnato come spam.',
    'commentTrashed'        => 'Commento nel cestino.',
    'commentDeleted'        => 'Commento eliminato definitivamente.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Libreria media',
    'mediaTitle'            => 'Titolo',
    'mediaAltText'          => 'Testo alternativo',
    'mediaAltPlaceholder'   => "Descrivi l'immagine per l'accessibilità",
    'mediaTitlePlaceholder' => 'Titolo immagine opzionale',
    'mediaImageDetails'     => "Dettagli dell'immagine",
    'mediaSaved'            => 'Salvato!',
    'mediaNoSelection'      => 'Nessuna immagine selezionata',
    'mediaBrowse'           => 'Sfoglia media',
    'mediaRemove'           => 'Rimuovi',
    'mediaUseImage'         => 'Usa questa immagine',
    'mediaDropzone'         => "Trascina e rilascia l'immagine qui o clicca per sfogliare",
    'mediaLoading'          => 'Caricamento media…',
    'mediaEmpty'            => 'Nessun media caricato ancora.',
    'mediaUpload'           => 'Carica media',
    'mediaDragDrop'         => 'Trascina e rilascia i file qui, oppure',
    'mediaChooseFiles'      => 'Scegli file',
    'mediaUploading'        => 'Caricamento in corso…',
    'mediaFilename'         => 'Nome file',
    'mediaSize'             => 'Dimensione',
    'mediaUploadFailed'     => 'Caricamento fallito: {0}',
    'mediaUploadError'      => 'Errore di caricamento: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Media eliminato.',
    'mediaNoValidFile'      => 'Nessun file valido caricato.',
    'mediaUploadSuccess'    => 'File caricato con successo.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navigazione',
    'navQuickAdd'           => 'Aggiunta rapida',
    'navQuickAddPlaceholder' => 'Cerca pagine, categorie, plugin...',
    'navItemLabel'          => 'Etichetta',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Destinazione',
    'navItemOrder'          => 'Ordine',
    'navGroupPrimary'       => 'Principale',
    'navGroupFooter'        => 'Footer',
    'navSelectGroup'        => 'Seleziona gruppo di navigazione:',
    'navParent'             => 'Genitore',
    'navTopLevel'           => '— Livello superiore —',
    'navSameWindow'         => 'Stessa finestra',
    'navNewWindow'          => 'Nuova finestra',
    'navMenuItems'          => 'Voci di menu',
    'navNoItems'            => 'Nessuna voce in questo menu.',
    'dragToReorder'         => 'Trascina per riordinare',

    // Navigation flash messages
    'navItemAdded'          => 'Voce di navigazione aggiunta.',
    'navItemRemoved'        => 'Voce di navigazione rimossa.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Temi',
    'themeOptions'          => 'Opzioni tema',
    'themeActivate'         => 'Attiva',
    'themeOptionsBtn'       => 'Opzioni',
    'themeActive'           => 'Attivo',
    'themeBy'               => 'Di',
    'themeSupport'          => 'Supporto',
    'themeVersion'          => 'Versione',
    'themeSaveOptions'      => 'Salva opzioni',
    'themeInvalidLicense'   => 'Impossibile attivare il tema - licenza non valida. Reinstalla o contatta il supporto.',
    'themeValidationFailed' => 'Il tema contiene codice PHP e non può essere attivato.',
    'noThemesInstalled'     => 'Nessun tema installato. Visita il Marketplace per ottenere temi.',
    'themeUnapprovedTitle'  => 'Attivare tema non approvato?',
    'themeNotApproved'      => 'Questo tema non è stato approvato da Pubvana.',
    'themeUnapprovedRisk'   => "L'attivazione di temi non approvati può introdurre rischi di sicurezza o problemi di compatibilità.",
    'themeActivateConfirm'  => 'Sei sicuro di volerlo attivare comunque?',
    'themeActivateAnyway'   => 'Attiva comunque',
    'themeNoOptions'        => 'Questo tema non ha opzioni configurabili.',
    'themeCustomize'        => 'Personalizza tema',

    // Theme flash messages
    'themeActivated'        => 'Tema attivato.',
    'themeOptionsSaved'     => 'Opzioni salvate.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Licenziato',
    'licenseCheckNow'        => 'Verifica ora',
    'licenseExpired'         => 'Scaduta',
    'licenseEnterKey'        => 'Inserisci chiave',
    'licenseChangeKey'       => 'Cambia',
    'licenseRenew'           => 'Rinnova',
    'licenseThirdParty'      => 'Terze parti',
    'unchecked'              => 'Non verificato',
    'safetyLabel'            => 'Sicurezza:',
    'recheckBtn'             => 'Ricontrolla',
    'recheckSuccess'         => 'Controllo di sicurezza aggiornato.',
    'recheckFailed'          => 'Impossibile raggiungere il server di verifica. Riprova più tardi.',
    'recheckNotFound'        => 'Elemento non trovato.',
    'widgetBlockedMalicious' => '{0} è stato segnalato come dannoso e non può essere aggiunto.',
    'licenseNoStoreProduct'  => 'Questo elemento non è collegato a un prodotto dello store. Se hai acquistato questo elemento, reinstallalo dal marketplace per abilitare la licenza.',
    'securityWarning'        => 'Avviso di sicurezza:',
    'licenseModalTitle'      => 'Inserisci chiave di licenza',
    'licenseModalBody'       => 'Incolla la tua chiave di licenza qui sotto.',
    'licenseModalSave'       => 'Salva',
    'licenseSaved'           => 'Chiave di licenza salvata e validata.',
    'licenseInvalid'         => 'La chiave di licenza non è valida.',
    'licenseKeyRequired'     => 'La chiave di licenza e il prodotto sono obbligatori.',
    'licenseCheckFailed'     => 'Impossibile raggiungere il server di licenza. Riprova più tardi.',
    'licenseProductNotFound' => 'Impossibile trovare questo elemento nel negozio.',
    'btnCancel'              => 'Annulla',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widget',
    'widgetConfigureTitle'  => 'Configura widget',
    'widgetAreas'           => 'Aree widget',
    'widgetAvailable'       => 'Widget disponibili',
    'widgetAddToArea'       => "Aggiungi all'area",
    'widgetArea'            => 'Area',
    'widgetNoOptions'       => 'Nessuna opzione.',
    'widgetSaveConfig'      => 'Salva configurazione',
    'widgetConfigure'       => 'Configura',
    'widgetNoAreas'         => 'Nessuna area widget trovata. Attiva un tema per abilitare le aree widget.',
    'widgetAreaEmpty'       => "Nessun widget in quest'area. Aggiungine uno dall'elenco →",

    // Widget flash messages
    'widgetAdded'           => 'Widget aggiunto.',
    'widgetRemoved'         => 'Widget rimosso.',
    'widgetConfigured'      => 'Widget configurato.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Marketplace',
    'marketplaceRefresh'    => 'Aggiorna',
    'marketplaceVisitStore' => 'Visita il negozio',
    'marketplaceAll'        => 'Tutti',
    'marketplaceThemes'     => 'Temi',
    'marketplaceWidgets'    => 'Widget',
    'marketplacePlugins'    => 'Plugin',
    'marketplaceUpdatesAvailable' => '{0} aggiornamento/i disponibile/i.',
    'marketplaceBy'         => 'Di',
    'marketplaceFree'       => 'Gratuito',
    'marketplaceInstalled'  => 'Installato',
    'marketplaceInstall'    => 'Installa',
    'marketplaceBuyNow'     => 'Acquista ora',
    'marketplaceNoItems'    => 'Nessun elemento trovato nel marketplace.',
    'marketplaceInstalledVersion' => 'v{0} installata',
    'marketplaceLoadError'  => 'Impossibile caricare i prodotti dal negozio. Riprova più tardi.',
    'byAuthor'              => 'Di {0}',
    'unknown'               => 'Sconosciuto',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} installato con successo.',
    'marketplaceInstallFail'    => 'Installazione fallita. Controlla i log.',
    'marketplaceUpdateSuccess'  => 'Aggiornato con successo.',
    'marketplaceUpdateFail'     => 'Aggiornamento fallito.',
    'marketplaceCacheRefreshed' => 'Cache del marketplace aggiornata.',
    'marketplaceInvalidRequest' => 'Richiesta di installazione non valida.',
    'marketplaceCannotUpdate'   => 'Impossibile aggiornare questo elemento.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licenze',
    'licensesNone'                => 'Nessuna licenza',
    'licensesProduct'             => 'Prodotto',
    'licensesKey'                 => 'Chiave di licenza',
    'licensesStatus'              => 'Stato',
    'licensesType'                => 'Tipo',
    'licensesExpires'             => 'Scadenza',
    'licensesDomain'              => 'Dominio',
    'licensesInstalled'           => 'Installato',
    'licensesLastChecked'         => 'Ultima verifica',
    'licensesActions'             => 'Azioni',
    'licensesStatusValid'         => 'Valida',
    'licensesStatusInvalid'       => 'Non valida',
    'licensesStatusExpired'       => 'Scaduta',
    'licensesStatusSubExpired'    => 'Abbonamento scaduto',
    'licensesStatusUnchecked'     => 'Non verificata',
    'licensesSubscription'        => 'Abbonamento',
    'licensesOneTime'             => 'Pagamento unico',
    'licensesPerpetual'           => 'Perpetua',
    'licensesNotInstalled'        => 'Non installato',
    'licensesNever'               => 'Mai',
    'licensesRevalidate'          => 'Rivalida',
    'licenseKeyPlaceholder'       => 'Inserisci chiave di licenza...',
    'marketplaceLicensesEmpty'    => 'I prodotti con licenza appariranno qui dopo l\'installazione.',
    'typeTheme'                   => 'Tema',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Plugin',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Licenza validata con successo.',
    'licenseRevalidateInvalid'     => 'Licenza non valida o scaduta.',
    'licenseRevalidateUnreachable' => 'Impossibile raggiungere il server di licenza. Riprova più tardi.',
    'licenseRevalidateSkipped'     => 'Verifica licenza saltata (modalità sviluppo).',
    'licenseRevalidateNotFound'    => 'Licenza non trovata.',

    // License warning banners
    'licenseWarningTitle'   => 'Problemi con la licenza',
    'licenseWarningInvalid' => 'la licenza non è valida o è scaduta',
    'licenseWarningManage'  => 'Gestisci licenze',

    // Plugin license
    'pluginInvalidLicense' => 'Questo plugin ha una licenza non valida o scaduta e non può essere attivato.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Chiave di licenza',
    'storeBrowseFull'       => 'Sfoglia il negozio completo',
    'storeBackToMarketplace'=> 'Torna al Marketplace',
    'storeNoProducts'       => 'Nessun prodotto disponibile.',
    'storeViewInStore'      => 'Visualizza nel negozio',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Utenti',
    'editUserTitle'         => 'Modifica utente',
    'createUserTitle'       => 'Crea utente',
    'authorProfileTitle'    => 'Profilo autore',
    'userRoleLabel'         => 'Ruolo',
    'userActiveLabel'       => 'Attivo',
    'userPasswordLabel'     => 'Password',
    'userPasswordOptional'  => 'Lascia vuoto per mantenere la password attuale',
    'userDisplayName'       => 'Nome visualizzato',
    'userBio'               => 'Biografia',
    'userWebsite'           => 'Sito web',
    'userTwitter'           => 'Twitter / X Handle',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Salva profilo',
    'userSaveChanges'       => 'Salva modifiche',
    'userCannotDeleteSelf'  => 'Non puoi eliminare te stesso.',
    'userCannotDeleteOwner' => "L'account del proprietario del sito non può essere eliminato.",
    'userOwnerCannotModify' => "L'account del proprietario del sito non può essere modificato.",

    // User flash messages
    'userCreated'           => 'Utente creato.',
    'userUpdated'           => 'Utente aggiornato.',
    'userDeleted'           => 'Utente eliminato.',
    'userBanned'            => "L'utente è stato bannato.",
    'userUnbanned'          => "Il ban dell'utente è stato rimosso.",
    'userCannotBanSelf'     => 'Non puoi bannare te stesso o il proprietario del sito.',
    'banStatus'             => 'Stato ban',
    'banned'                => 'Bannato',
    'ban'                   => 'Banna utente',
    'unban'                 => 'Rimuovi ban',
    'banReasonRequired'     => 'È necessario un motivo per il ban.',
    'banReasonPlaceholder'  => 'Motivo del ban...',
    'confirmBanUser'        => 'Sei sicuro di voler bannare questo utente?',
    'userProfileSaved'      => 'Profilo salvato.',
    'userAvatarUploadFail'  => 'Caricamento avatar fallito: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Configurazione 2FA',
    'tfaSetupHeading'       => 'Configura l\'autenticazione a due fattori',
    'tfaScanQr'             => 'Scansiona il codice QR qui sotto con la tua app di autenticazione (es. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Oppure inserisci manualmente la chiave segreta:',
    'tfaEnterCode'          => 'Inserisci il codice a 6 cifre dalla tua app per confermare:',
    'tfaCodeLabel'          => 'Codice di autenticazione',
    'tfaConfirmBtn'         => 'Conferma e abilita 2FA',
    'tfaDisableBtn'         => 'Disabilita 2FA',
    'tfaDisableConfirm'     => 'Inserisci il tuo codice 2FA attuale per disabilitare:',
    'tfaEnabled'            => "L'autenticazione a due fattori è stata abilitata.",
    'tfaDisabled'           => "L'autenticazione a due fattori è stata disabilitata.",
    'tfaInvalidCode'        => 'Codice non valido - scansiona il codice QR e riprova.',
    'tfaInvalidDisable'     => 'Codice non valido - 2FA non è stato disabilitato.',
    'tfaSessionExpired'     => 'Sessione di configurazione scaduta - ricomincia.',
    'tfaNotEnabled'         => '2FA non è attualmente abilitato.',
    'tfaCantScan'           => 'Non riesci a scansionare? Inserisci questo codice manualmente:',
    'tfaWarning'            => 'Conserva questa chiave segreta in un luogo sicuro. Ne avrai bisogno per recuperare l\'accesso se perdi il tuo dispositivo autenticatore.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Link social',
    'socialPlatform'           => 'Piattaforma',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Icona',
    'socialSortOrder'          => 'Ordine',
    'socialIconPackInfo'       => 'Il tema attuale <strong>{0}</strong> usa <strong>{1}</strong> (v{2}) per le icone. Qui sotto puoi scegliere le icone disponibili che verranno visualizzate per la funzionalità Social Links di questo sito.',
    'socialSearchPlaceholder'  => 'Cerca piattaforme...',
    'socialIconDisclaimer'     => "Queste icone sono solo una rappresentazione dell'icona che verrà utilizzata. L'icona effettiva può variare a seconda del pacchetto icone del tema attivo.",

    // Social flash messages
    'socialLinkAdded'       => 'Link social aggiunto.',
    'socialLinkUpdated'     => 'Link aggiornato.',
    'socialLinkDeleted'     => 'Link eliminato.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Reindirizzamenti',
    'redirectFrom'          => 'URL di origine',
    'redirectTo'            => 'URL di destinazione',
    'redirectType'          => 'Tipo',
    'redirectAdd'           => 'Aggiungi reindirizzamento',
    'redirectFromHint'      => '(relativo, es. /vecchia-pagina)',
    'redirect301'           => '301 Permanente',
    'redirect302'           => '302 Temporaneo',
    'redirectInvalidDest'   => 'URL di destinazione del reindirizzamento non valido.',

    // Redirect flash messages
    'redirectAdded'         => 'Reindirizzamento aggiunto.',
    'redirectDeleted'       => 'Reindirizzamento eliminato.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Impostazioni',
    'settingsGeneral'       => 'Generali',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'Email',
    'settingsSocialLogin'   => 'Accesso social',
    'settingsSocialSharing' => 'Condivisione social',
    'settingsSpam'          => 'Protezione spam',

    'generalSettingsHeading'    => 'Impostazioni generali',
    'generalSiteName'           => 'Nome del sito',
    'generalTagline'            => 'Slogan',
    'generalAdminEmail'         => 'Email amministratore',
    'generalPostsPerPage'       => 'Articoli per pagina',
    'generalComments'           => 'Commenti',
    'generalCommentsEnable'     => 'Abilita commenti',
    'generalCommentModeration'  => 'Richiedi moderazione prima della pubblicazione',
    'generalMaintenanceMode'    => 'Modalità manutenzione',
    'generalMaintenanceEnable'  => 'Abilita modalità manutenzione',
    'generalMaintenanceHelp'    => 'I visitatori vedono una pagina "Torneremo presto". Gli amministratori possono ancora accedere al sito.',
    'generalFrontPage'          => 'Pagina principale',
    'generalFrontPageBlog'      => 'Indice blog (articoli recenti)',
    'generalFrontPageStatic'    => 'Pagina statica:',
    'generalFrontPagePlugin'    => 'Pagina plugin:',
    'generalSelectPage'         => '- Seleziona una pagina -',
    'generalSelectRoute'        => '- Seleziona un percorso -',
    'generalFrontPageNoPlugins' => 'Nessun percorso plugin disponibile',
    'generalPageCacheTtl'       => 'TTL cache pagina',
    'settingsCacheTtlHint'      => 'Secondi. 0 = disabilitato.',
    'generalSaveBtn'            => 'Salva impostazioni generali',

    // General flash messages
    'generalSettingsSaved'      => 'Impostazioni generali salvate.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'Impostazioni SEO',
    'seoMetaDescription'        => 'Meta descrizione',
    'seoGoogleAnalytics'        => 'ID Google Analytics',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Mappa del sito',
    'seoSitemapEnable'          => 'Abilita sitemap.xml',
    'seoSitemapHelp'            => 'Mappa del sito standard per tutti gli articoli e le pagine pubblicati.',
    'seoNewsSitemap'            => 'Abilita news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Mappa del sito Google News - elenca gli articoli pubblicati nelle ultime 48 ore.',
    'seoSaveBtn'                => 'Salva impostazioni SEO',
    'seoSettingsSaved'          => 'Impostazioni SEO salvate.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Impostazioni email',
    'emailFromName'             => 'Nome mittente',
    'emailFromAddress'          => 'Indirizzo mittente',
    'emailProtocol'             => 'Protocollo',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'Host SMTP',
    'emailSmtpPort'             => 'Porta SMTP',
    'emailSmtpEncryption'       => 'Crittografia',
    'emailSmtpEncryptionNone'   => 'Nessuna',
    'emailSmtpUsername'         => 'Nome utente SMTP',
    'emailSmtpPassword'         => 'Password SMTP',
    'emailSaveBtn'              => 'Salva impostazioni email',
    'emailSettingsSaved'        => 'Impostazioni email salvate.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Accesso social (OAuth)',
    'socialLoginHelp'           => 'Le credenziali vengono salvate nel file .env. Registra la tua app su Google e Facebook per ottenere ID client e segreti.',
    'socialLoginGoogleId'       => 'ID client',
    'socialLoginGoogleSecret'   => 'Segreto client',
    'socialLoginFbAppId'        => 'ID app',
    'socialLoginFbAppSecret'    => 'Segreto app',
    'socialLoginPlaceholderSecret' => '(lascia vuoto per mantenere quello esistente)',
    'socialLoginSaveBtn'        => 'Salva impostazioni accesso social',
    'socialLoginSettingsSaved'  => 'Impostazioni accesso social salvate.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Condivisione automatica sui social alla pubblicazione',
    'socialSharingHelp'         => 'Quando un articolo viene pubblicato con "Condividi alla pubblicazione" selezionato, Pubvana pubblicherà automaticamente sugli account social configurati.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Ottieni le chiavi su developer.twitter.com → La tua app → Chiavi e token.',
    'socialSharingApiKey'       => 'Chiave API',
    'socialSharingApiSecret'    => 'Segreto API',
    'socialSharingAccessToken'  => 'Token di accesso',
    'socialSharingAccessSecret' => 'Segreto di accesso',
    'socialSharingFbPage'       => 'Pagina Facebook',
    'socialSharingFbPageHelp'   => 'Richiede un token di accesso alla pagina con permesso pages_manage_posts.',
    'socialSharingFbPageId'     => 'ID pagina',
    'socialSharingFbPageToken'  => 'Token di accesso alla pagina',
    'socialSharingSaveBtn'      => 'Salva impostazioni di condivisione',
    'socialSharingSettingsSaved'=> 'Impostazioni di condivisione social salvate.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Protezione spam (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana usa hCaptcha (rispettoso della privacy, non Google) per proteggere i moduli di commento e di contatto dai bot spam.',
    'spamHcaptchaFree'          => 'hCaptcha è gratuito per la maggior parte dei siti. Registrati su hcaptcha.com, crea un sito e inserisci le tue chiavi qui sotto.',
    'spamHcaptchaSiteKey'       => 'Chiave del sito',
    'spamHcaptchaSecretKey'     => 'Chiave segreta',
    'spamHcaptchaNote'          => 'Se queste chiavi non sono impostate, hCaptcha viene silenziosamente ignorato — sicuro per lo sviluppo locale. Una volta salvate, il widget appare automaticamente nel modulo dei commenti e nella pagina dei contatti.',
    'spamSettingsSaved'         => 'Impostazioni protezione spam salvate.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Lingue',
    'languageCode'              => 'Codice',
    'languageName'              => 'Nome',
    'languageDefault'           => 'Predefinita',
    'languageEnabled'           => 'Abilitata',
    'languageMakeDefault'       => 'Imposta come predefinita',
    'languageSetAsDefault'      => '{0} impostata come lingua predefinita.',
    'languageEnabled_msg'       => '{0} abilitata.',
    'languageDisabled_msg'      => '{0} disabilitata.',
    'languageNotFound'          => 'Lingua non trovata.',
    'languageCannotDisable'     => 'Impossibile disabilitare la lingua predefinita.',
    'languageDirection'         => 'Direzione',
    'languageNativeName'        => 'Nome nativo',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analisi',
    'analyticsTotalViews'       => 'Visualizzazioni totali',
    'analyticsTopPosts'         => 'Articoli più visti',
    'analyticsReferrers'        => 'Principali referrer',
    'analyticsLast7'            => 'Ultimi 7 giorni',
    'analyticsLast30'           => 'Ultimi 30 giorni',
    'analyticsLast90'           => 'Ultimi 90 giorni',
    'analyticsChartTitle'       => 'Visualizzazioni di pagina',
    'analyticsNoData'           => 'Nessun dato di analisi per questo periodo.',
    'analyticsDomain'           => 'Dominio',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Link affiliati',
    'newAffiliateLinkTitle'     => 'Nuovo link affiliato',
    'editAffiliateLinkTitle'    => 'Modifica link affiliato',
    'affiliateName'             => 'Nome',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'URL di destinazione',
    'affiliateActive'           => 'Attivo',
    'affiliateClicks'           => 'Clic',
    'affiliateClicksTitle'      => 'Clic - {0}',
    'affiliateTotal'            => 'Totale',
    'affiliateViewClicks'       => 'Visualizza clic',

    // Affiliate flash messages
    'affiliateCreated'          => 'Link affiliato creato.',
    'affiliateUpdated'          => 'Link affiliato aggiornato.',
    'affiliateDeleted'          => 'Link affiliato eliminato.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Link non funzionanti',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'Stato HTTP',
    'brokenLinkError'           => 'Errore',
    'brokenLinkSource'          => 'Sorgente',
    'brokenLinkShowDismissed'   => 'Mostra ignorati',
    'brokenLinkHideDismissed'   => 'Nascondi ignorati',
    'brokenLinkTimeout'         => 'Timeout',
    'brokenLinkBroken'          => 'non funzionante',
    'brokenLinkNone'            => 'Nessun link non funzionante rilevato.',
    'brokenLinkNowReachable'    => 'Il link è ora raggiungibile - rimosso dai risultati.',
    'brokenLinkStillBroken'     => 'Il link è ancora non funzionante ({0}).',
    'brokenLinkDismissed'       => 'Link ignorato.',
    'brokenLinksCliHint'        => 'Esegui una scansione completa dalla riga di comando per popolare questo report: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} problema/i trovato/i',
    'brokenLinksCount'          => '{0} non funzionante/i',
    'brokenLinksRecheck'        => 'Ricontrolla questo URL',
    'brokenLinksDismiss'        => 'Ignora (nascondi dai risultati)',
    'brokenLinksRunScan'        => 'Esegui scansione',
    'brokenLinksScanComplete'   => 'Scansione completata: {0} link controllati, {1} non funzionanti.',
    'timeout'                   => 'Timeout',
    'typePost'                  => 'Articolo',
    'typePage'                  => 'Pagina',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Registro attività',
    'activityLogType'           => 'Tipo',
    'activityLogAction'         => 'Azione',
    'activityLogUser'           => 'Utente',
    'activityLogDate'           => 'Data',
    'activityLogNote'           => 'Nota',
    'activityLogFilterAll'      => 'Tutti i tipi',
    'activityLogEmpty'          => 'Nessuna attività registrata ancora.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Backup ed esportazione',
    'backupDownload'            => 'Crea e scarica backup',
    'backupFiles'               => 'Backup disponibili',
    'backupFilename'            => 'Nome file',
    'backupSize'                => 'Dimensione',
    'backupDate'                => 'Creato',
    'backupGenerating'          => 'Generazione backup in corso…',
    'backupNoFiles'             => 'Nessun backup salvato.',
    'backupFailed'              => 'Backup fallito: {0}',
    'backupDeleted'             => 'Backup eliminato.',
    'backupCannotDelete'        => 'Impossibile eliminare il backup.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'Gli IP sono memorizzati come hash SHA-256 — nessun dato personale grezzo registrato.',
    'colTime'                   => 'Ora',
    'colIpHash'                 => 'Hash IP',
    'colReferrer'               => 'Referrer',
    'affiliateDirectReferrer'   => 'Diretto',
    'affiliateNameHint'         => 'Etichetta interna — non mostrata ai visitatori.',
    'affiliateSlugHint'         => 'Solo lettere, numeri, trattini e underscore. Non modificabile una volta condivisi i link.',
    'affiliateDestHint'         => 'Deve includere https://. I visitatori verranno reindirizzati qui con un 301.',
    'affiliateInactiveHint'     => 'I link inattivi restituiscono un 404.',
    'affiliateLinkCount'        => '{0} link',
    'colDomain'                 => 'Dominio',
    'commentAll'                => 'Tutti',
    'commentPending'            => 'In attesa',
    'commentTrash'              => 'Cestino',
    'commentsNone'              => 'Nessun commento {0}.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Crea backup',
    'backupStarting'            => 'Avvio backup in corso...',
    'backupNoneYet'             => 'Nessun backup ancora. Clicca "Crea backup" per crearne uno.',
    'backupsTitle'              => 'Backup',
    'backupRetentionNote'       => 'Massimo 15 backup conservati — i più vecchi vengono eliminati automaticamente.',
    'backupRestoreConfirm'      => 'Ripristinare questo backup? Prima verrà creato un backup dello stato attuale.',
    'backupDeleteConfirm'       => 'Eliminare questo backup?',
    'colFilename'               => 'Nome file',
    'colVersion'                => 'Versione',
    'colTrigger'                => 'Trigger',
    'colSize'                   => 'Dimensione',
    'colDate'                   => 'Data',
    'colActions'                => 'Azioni',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Importa',
    'importWpHeading'           => 'Importa da WordPress',
    'importWpHelp'              => 'Esporta il tuo sito WordPress tramite Strumenti → Esporta, poi carica il file .xml qui sotto.',
    'importChooseFile'          => 'Scegli file WXR (.xml)',
    'importDryRun'              => 'Prova (solo anteprima - niente viene salvato)',
    'importRunBtn'              => 'Esegui importazione',
    'importNoValidFile'         => 'Carica un file di esportazione WXR di WordPress valido.',
    'importOnlyXml'             => 'Sono accettati solo file .xml.',
    'importFileTooLarge'        => 'File di importazione troppo grande. La dimensione massima è 50 MB.',
    'importResultsHeading'      => 'Risultati importazione',
    'importDryRunNote'          => 'Prova - nessun dato salvato.',
    'importDryRunLabel'         => '(Prova — nessun dato scritto)',
    'importComplete'            => 'Importazione completata',
    'importCreated'             => 'creato',
    'importSkipped'             => 'saltato',
    'importErrors'              => 'Errori:',
    'importInstructions'        => 'Esporta i contenuti WordPress da <strong>Strumenti → Esporta → Tutto il contenuto</strong> e carica il file <code>.xml</code> qui. Pubvana importerà articoli, pagine, categorie, tag, autori e commenti.',
    'importCliTitle'            => 'Importazione CLI',
    'importCliHint'             => 'Puoi anche eseguire l\'importazione dalla riga di comando:',
    'importCliDryRunHint'       => 'Il flag <code>--dry-run</code> mostra cosa verrebbe importato senza scrivere nel database.',
    'importWhatTitle'           => 'Cosa viene importato',
    'importItemPosts'           => 'Articoli (titolo, contenuto, estratto, slug, stato)',
    'importItemPages'           => 'Pagine',
    'importItemCategories'      => 'Categorie (con gerarchia)',
    'importItemTags'            => 'Tag',
    'importItemAuthors'         => 'Autori (creati come account abbonati)',
    'importItemComments'        => 'Commenti',
    'importItemMedia'           => 'File media (URL preservati nel contenuto)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Aggiornamenti',
    'updatesCurrentVersion'     => 'Versione corrente',
    'updatesLatestVersion'      => 'Ultima versione',
    'updatesUpToDate'           => 'Pubvana è aggiornato.',
    'updatesAvailable'          => 'Aggiornamento disponibile: {0}',
    'updatesCheckBtn'           => 'Verifica aggiornamenti',
    'updatesReleaseNotes'       => 'Note di rilascio',
    'updatesHowToApply'         => 'Come applicare un aggiornamento',
    'updatesCacheCleared'       => 'Cache aggiornamenti azzerata - verifica in corso.',
    'updatesExtCapped'          => 'Aggiornamento disponibile: {0} (compatibile con addon)',
    'updatesNewerAvailable'     => 'Pubvana {0} è disponibile - aggiorna gli addon elencati di seguito per sbloccarlo.',

    // Addon Updates
    'updatesExtTitle'               => 'Addon',
    'updatesExtCheckAll'            => 'Verifica tutti',
    'updatesExtUpdateAll'           => 'Aggiorna tutti',
    'updatesExtCheckAllType'        => 'Verifica tutti i {0}',
    'updatesExtUpdateAllType'       => 'Aggiorna tutti i {0}',
    'updatesExtNoInstalled'         => 'Nessun {0} installato.',
    'updatesExtColName'             => 'Nome',
    'updatesExtColVersion'          => 'Versione',
    'updatesExtColLatest'           => 'Ultima',
    'updatesExtColAutoUpdate'       => 'Aggiornamento automatico',
    'updatesExtColStatus'           => 'Stato',
    'updatesExtColActions'          => 'Azioni',
    'updatesExtBundled'             => 'Incluso nel core',
    'updatesExtNoSource'            => 'Nessuna sorgente di aggiornamento',
    'updatesExtFailed'              => 'Fallito',
    'updatesExtUpdatedAt'           => 'Aggiornato {0}',
    'updatesExtAvailable'           => 'Aggiornamento disponibile',
    'updatesExtUpToDate'            => 'Aggiornato',
    'updatesExtUpdate'              => 'Aggiorna',
    'updatesExtChecking'            => 'Verifica in corso...',
    'updatesExtUpdating'            => 'Aggiornamento in corso...',
    'updatesExtUpdated'             => 'Aggiornato',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Conferma aggiornamento',
    'updatesConfirmBody'            => 'Verrà eseguito un backup del sito, scaricato e applicato l\'aggiornamento.',
    'updatesConfirmSafe'            => 'I file <code>.env</code>, <code>App.php</code> e <code>Database.php</code> non vengono mai sovrascritti.',
    'updatesConfirmBtn'             => 'Aggiorna ora',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Aggiorna tutti gli addon',
    'updatesExtAllBody'             => 'Verranno aggiornati tutti gli addon con aggiornamenti in sospeso.',
    'updatesExtAllNote'             => 'Saranno aggiornati anche gli addon con aggiornamento automatico disabilitato.',
    'updatesExtAllBtn'              => 'Aggiorna tutti',

    'updatesExtBadge'               => 'Aggiornamento: v{0}',
    'updatesExtGoToUpdates'         => 'Aggiornamenti',

    // Update Settings
    'updatesSettingsTitle'          => 'Impostazioni aggiornamenti',
    'updatesAutoUpdateLabel'        => 'Aggiornamento automatico Pubvana',
    'updatesAutoUpdateManual'       => 'Manuale',
    'updatesAutoUpdateAuto'         => 'Automatico',
    'updatesAutoUpdateHelp'         => 'Se abilitato, gli aggiornamenti Pubvana senza modifiche incompatibili vengono applicati automaticamente.',
    'updatesCheckMethodLabel'       => 'Metodo di verifica aggiornamenti',
    'updatesCheckMethodPageload'    => 'Caricamento pagina',
    'updatesCheckMethodCron'        => 'Cron Job',
    'updatesCheckMethodHelp'        => 'Il caricamento pagina verifica ad ogni richiesta (cache 24h). Cron richiede un cron job sul server.',
    'updatesCronCommand'            => 'Comando Cron',
    'updatesCronHelp'               => 'Aggiungi questo al crontab del server per eseguire la verifica degli aggiornamenti ogni giorno:',
    'updatesSettingsSaved'          => 'Impostazioni aggiornamenti salvate.',

    // Compatibility
    'compatWarningTitle'            => 'Avviso di compatibilità',
    'compatNotCompatible'           => 'Alcuni addon installati non sono compatibili con questa versione.',
    'compatRequiresUpdate'          => 'ma richiede che i seguenti addon vengano aggiornati prima:',
    'compatSupportsUpTo'            => 'supporta fino a {0}',
    'compatRequiresMin'             => 'richiede Pubvana {0}+',
    'compatNotDeclared'             => 'I seguenti addon non hanno dichiarato compatibilità con Pubvana {0}. Potrebbero smettere di funzionare dopo l\'aggiornamento:',
    'compatColType'                 => 'Tipo',
    'compatColName'                 => 'Nome',
    'compatColVersion'              => 'Compatibilità',
    'compatRemoveHint'              => 'Puoi rimuovere gli addon incompatibili o passare al tema predefinito in caso di problemi. Un backup viene creato prima di ogni aggiornamento.',
    'compatMaxVersion'              => 'Versione massima compatibile: {0}',
    'compatMinVersion'              => 'Richiede Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Pianificazione articoli',
    'scheduleNoScheduled'       => 'Nessun articolo pianificato.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revisioni - {0}',
    'revisionPageTitle'         => 'Revisione - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Devi essere connesso per accedere al pannello di amministrazione.',
    'dirNotWritable'            => 'La directory non è scrivibile: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} è configurato in modo errato. Se sei l\'utente finale, contatta lo sviluppatore. Se sei lo sviluppatore, consulta la documentazione.',
    'addonMisconfiguredLink'    => '{0} è configurato in modo errato. Se sei l\'utente finale <a href="{1}">contatta lo sviluppatore</a>. Se sei lo sviluppatore <a href="https://github.com/Pubvana-CMS/pubvana">consulta la documentazione</a>.',
    'licenseExpiringSoon'       => 'La licenza di {0} scade il {1}. {0} verrà disattivato alla scadenza della licenza.',
    'licenseExpiredDeactivated' => '{0} è stato disattivato perché la licenza è scaduta.',
    'addonDeactivated'          => '{0} è stato disattivato. Motivo: {1}.',
    'widgetValidationFailed'    => "Il widget ''{0}'' non è stato convalidato. Contatta lo sviluppatore o rimuovi l'addon.",
    'widgetValidationFailedLink' => "Il widget ''{0}'' non è stato convalidato. <a href=\"{1}\">Contatta lo sviluppatore</a> o rimuovi l'addon.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Disattivato: licenza scaduta',
    'addonDeactivatedTampered'  => 'Disattivato: configurazione errata',
    'addonDeactivatedNoLicense' => 'Disattivato: nessuna licenza valida',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Disabilitato',
    'addonDisabledInvalidJson'  => 'Sistema: {0} ha un {1} non valido o illeggibile.',
    'addonDisabledMissingFields' => 'Sistema: {0} manca dei campi obbligatori: {1}.',
    'addonDisabledPhpFiles'     => 'Sistema: {0} contiene file PHP. I widget devono essere solo JSON + template.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'È necessaria una licenza valida per attivare {0}.',
    'licenseInvalidActivation'  => 'Convalida della licenza fallita per {0}. Controlla la tua chiave di licenza.',
    'licenseExpiredActivation'  => 'La licenza di {0} è scaduta. Rinnova per attivare.',
    'licenseCheckUnreachable'   => 'Impossibile verificare la licenza di {0}. Il server di licenza non è raggiungibile. Riprova più tardi.',
    'activationBlockedTampered' => '{0} non può essere attivato perché è configurato in modo errato.',
    'activationBlockedBundled'  => '{0} non può essere attivato: solo gli addon Pubvana possono essere contrassegnati come inclusi.',
    'activationBlockedNoUrls'   => '{0} non può essere attivato: gli addon a pagamento devono includere URL di verifica della licenza.',
    'activationBlockedFreeFlag' => '{0} non può essere attivato: gli addon Pubvana non possono essere contrassegnati come gratuiti.',
    'activationBlockedDisabled' => '{0} non può essere attivato perché contiene errori di configurazione. Controlla il file info.',

    // Third-party license
    'licenseThirdPartyLabel'    => '3a Parte',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Avvio aggiornamento...',
    'updateCheckLabel'           => 'Verifica aggiornamento:',
    'updateAvailable'            => 'Pubvana {0} è disponibile!',
    'updateRunning'              => 'Stai eseguendo {0}.',
    'updateBreakingChanges'      => 'Modifiche incompatibili',
    'updateMigrationNotes'       => 'Note di migrazione',
    'updateNotices'              => 'Avvisi',
    'updatePreflightTitle'       => 'Verifiche preliminari',
    'updateToVersion'            => 'Aggiorna a Pubvana {0}',
    'updatePreflightFailed'      => 'Una o più verifiche preliminari obbligatorie sono fallite. Risolvile prima di aggiornare.',
    'updateUpToDate'             => 'Pubvana è aggiornato. Stai eseguendo la versione {0}.',
    'updateAnyway'               => 'Aggiorna comunque',
    'updateAvailableTooltip'     => 'Pubvana {0} disponibile',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(tu)',
    'usersNone'                  => 'Nessun utente trovato.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Account attivo',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Dettagli profilo',
    'profileDisplayNameHint'     => 'Mostrato negli articoli pubblicati al posto del nome utente.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP o GIF. Max 10 MB.',
    'profileSocialHandles'       => 'Handle social',
    'preview'                    => 'Anteprima',
    'website'                    => 'Sito web',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Autenticazione a due fattori',
    'totpActiveDesc'             => "L'autenticazione a due fattori TOTP è attiva sul tuo account. Ti verrà chiesto un codice a 6 cifre dalla tua app di autenticazione ad ogni accesso.",
    'totpCurrentCode'            => 'Codice attuale',
    'totpInactiveDesc'           => 'Aggiungi un ulteriore livello di sicurezza al tuo account. Una volta abilitato, dovrai inserire un codice dalla tua app di autenticazione ad ogni accesso.',
    'totpEnable'                 => "Abilita l'autenticazione a due fattori",
    'totpScanInstructions'       => 'Apri la tua app di autenticazione (Google Authenticator, Authy, 1Password, ecc.) e scansiona questo codice QR.',
    'totpManualEntry'            => 'Non riesci a scansionare? Inserisci questo codice manualmente:',
    'totpConfirmInstructions'    => 'Dopo la scansione, inserisci il codice a 6 cifre mostrato nella tua app per confermare la configurazione.',
    'totpRecoveryWarning'        => 'Conserva i tuoi codici di recupero. Se perdi l\'accesso alla tua app di autenticazione, non potrai accedere. Contatta l\'amministratore del sito per reimpostare il 2FA.',

];
