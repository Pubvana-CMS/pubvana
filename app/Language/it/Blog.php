<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Italian)
 *
 * Covers: blog listing, post detail, category, tag, archive, search,
 *         contact form, comments, pagination, paywall, preview mode,
 *         author card, and shared public UI elements.
 *
 * Also incorporates the single key from the old pages_lang.php (readMore).
 *
 * Usage: lang('Blog.keyName')
 */

return [

    // =========================================================================
    // Common public UI
    // =========================================================================

    'home'              => 'Home',
    'blog'              => 'Blog',
    'readMore'          => 'Leggi di più',
    'viewAll'           => 'Vedi tutto',
    'noPostsYet'        => 'Nessun articolo ancora. Torna presto!',
    'search'            => 'Cerca',
    'searchPlaceholder' => 'Cerca…',
    'searchPostsPlaceholder' => 'Cerca articoli…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Mappa del sito',
    'allRightsReserved' => 'Tutti i diritti riservati.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Articoli recenti',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Pubblicato il',
    'views'             => '{0} visualizzazioni',
    'readingTime'       => '{0} min di lettura',
    'publishedBy'       => 'Di',
    'inCategory'        => 'in',
    'tags'              => 'Tag',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Modalità anteprima - Questo articolo non è visibile al pubblico',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Contenuto premium',
    'paywallMessage'        => 'Questo contenuto è disponibile per gli abbonati premium.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => "Sull'autore",
    'unknownAuthor'     => 'Autore sconosciuto',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Categoria: {0}',
    'noPostsInCategory' => 'Nessun articolo in questa categoria.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Tag: {0}',
    'noPostsWithTag'    => 'Nessun articolo con questo tag.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archivio: {0}',
    'noPostsInPeriod'   => 'Nessun articolo in questo periodo.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Risultati della ricerca',
    'searchShowingFor'      => 'Risultati per: {0}',
    'searchNoResults'       => 'Nessun articolo trovato per "{0}".',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Commenti ({0})',
    'commentsClosed'        => 'I commenti sono chiusi.',
    'commentFormTitle'      => 'Lascia un commento',
    'commentLabel'          => 'Commento *',
    'commentPostBtn'        => 'Pubblica commento',
    'commentModerated'      => 'I commenti vengono moderati prima della pubblicazione.',
    'commentLoginRequired'  => 'per lasciare un commento.',
    'commentLoginLink'      => 'Accedi',
    'commentAwaitModeration'=> 'Il tuo commento è in attesa di moderazione.',
    'commentPosted'         => 'Il tuo commento è stato pubblicato.',
    'commentLoginToComment' => 'Devi essere connesso per commentare.',
    'commentTooFast'        => 'Stai commentando troppo velocemente. Attendi qualche minuto prima di riprovare.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Contatti',
    'contactName'           => 'Nome',
    'contactEmail'          => 'Email',
    'contactMessage'        => 'Messaggio',
    'contactSendBtn'        => 'Invia messaggio',
    'contactSent'           => 'Il tuo messaggio è stato inviato!',
    'contactCaptchaFail'    => 'Verifica captcha fallita. Riprova.',
    'contactSubject'        => 'Modulo di contatto: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Navigazione pagina',
    'prevPage'          => 'Precedente',
    'nextPage'          => 'Successivo',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Pagina non trovata.',
    'pageNotFoundTitle' => '404 - Pagina non trovata',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'In manutenzione',
    'maintenanceBody'   => 'Stiamo eseguendo una manutenzione programmata. Torneremo presto - grazie per la pazienza!',

    // Language
    'language'          => 'Lingua',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Il mio profilo',
    'profileBasicInfo'        => 'Informazioni di base',
    'profileUsername'          => 'Nome utente',
    'profileEmail'            => 'Email',
    'profilePassword'         => 'Password',
    'profilePasswordConfirm'  => 'Conferma password',
    'profilePasswordHelp'     => 'Lascia vuoto per mantenere la password attuale.',
    'profileSave'             => 'Salva modifiche',
    'profileUpdated'          => 'Profilo aggiornato con successo.',
    'profileUsernameRequired' => 'Il nome utente è obbligatorio.',
    'profileUsernameTaken'    => 'Questo nome utente è già in uso.',
    'profileEmailRequired'    => 'L\'email è obbligatoria.',
    'profileEmailTaken'       => 'Questa email è già in uso.',
    'profilePasswordMismatch' => 'Le password non corrispondono.',
    'profilePasswordTooShort' => 'La password deve essere di almeno 8 caratteri.',

    'profileAuthorInfo'       => 'Profilo autore',
    'profileDisplayName'      => 'Nome visualizzato',
    'profileBio'              => 'Biografia',
    'profileAvatar'           => 'Avatar',
    'profileAvatarChange'     => 'Cambia avatar',
    'profileAvatarUpload'     => 'Carica',
    'profileWebsite'          => 'Sito web',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avatar aggiornato con successo.',
    'profileAvatarInvalid'    => 'Caricamento file non valido.',
    'profileAvatarTypeError'  => 'Sono accettate solo immagini JPEG, PNG, WebP e GIF.',
    'profileAvatarTooLarge'   => "L'avatar deve essere di 2 MB o meno.",
    'profileAvatarNotAllowed' => 'Il caricamento di avatar è disponibile per autori e ruoli superiori.',

    'login'                         => 'Accedi',
    'adminPanel'                    => 'Pannello di amministrazione',

    'profileUpdatedRelogin'         => 'Profilo aggiornato. Effettua di nuovo l\'accesso.',
    'profileUsernameChangedSubject' => 'Il tuo nome utente è stato modificato',
    'profileUsernameChangedBody'    => 'Il tuo nome utente è stato cambiato da "{0}" a "{1}". Se non hai effettuato questa modifica, contatta immediatamente l\'amministratore del sito.',
    'profileEmailChangedSubject'    => 'Il tuo indirizzo email è stato modificato',
    'profileEmailChangedBody'       => 'Il tuo indirizzo email è stato cambiato da "{0}" a "{1}". Se non hai effettuato questa modifica, contatta immediatamente l\'amministratore del sito.',
    'profilePasswordChangedSubject' => 'La tua password è stata modificata',
    'profilePasswordChangedBody'    => 'La tua password è stata modificata di recente. Se non hai effettuato questa modifica, contatta immediatamente l\'amministratore del sito.',

];
