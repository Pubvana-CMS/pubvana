<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Czech)
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

    'home'              => 'Domů',
    'blog'              => 'Blog',
    'readMore'          => 'Číst dále',
    'viewAll'           => 'Zobrazit vše',
    'noPostsYet'        => 'Zatím žádné příspěvky. Brzy se vraťte!',
    'search'            => 'Hledat',
    'searchPlaceholder' => 'Hledat…',
    'searchPostsPlaceholder' => 'Hledat příspěvky…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Mapa webu',
    'allRightsReserved' => 'Všechna práva vyhrazena.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Nejnovější příspěvky',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Zveřejněno',
    'views'             => '{0} zobrazení',
    'readingTime'       => '{0} min čtení',
    'publishedBy'       => 'Autor',
    'inCategory'        => 'v',
    'tags'              => 'Štítky',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Režim náhledu – Tento příspěvek není veřejně viditelný',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Prémiový obsah',
    'paywallMessage'        => 'Tento obsah je dostupný pouze prémiovým předplatitelům.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => 'O autorovi',
    'unknownAuthor'     => 'Neznámý autor',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Kategorie: {0}',
    'noPostsInCategory' => 'V této kategorii zatím nejsou žádné příspěvky.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Štítek: {0}',
    'noPostsWithTag'    => 'Žádné příspěvky s tímto štítkem.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archiv: {0}',
    'noPostsInPeriod'   => 'V tomto období nejsou žádné příspěvky.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Výsledky vyhledávání',
    'searchShowingFor'      => 'Výsledky pro: {0}',
    'searchNoResults'       => 'Nenalezeny žádné příspěvky pro „{0}".',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Komentáře ({0})',
    'commentsClosed'        => 'Komentáře jsou uzavřeny.',
    'commentFormTitle'      => 'Zanechat komentář',
    'commentLabel'          => 'Komentář *',
    'commentPostBtn'        => 'Odeslat komentář',
    'commentModerated'      => 'Komentáře jsou moderovány před zveřejněním.',
    'commentLoginRequired'  => 'pro zanechání komentáře.',
    'commentLoginLink'      => 'Přihlásit se',
    'commentAwaitModeration'=> 'Váš komentář čeká na schválení.',
    'commentPosted'         => 'Váš komentář byl zveřejněn.',
    'commentLoginToComment' => 'Chcete-li komentovat, musíte být přihlášeni.',
    'commentTooFast'        => 'Komentujete příliš rychle. Před dalším pokusem chvíli počkejte.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Kontakt',
    'contactName'           => 'Jméno',
    'contactEmail'          => 'E-mail',
    'contactMessage'        => 'Zpráva',
    'contactSendBtn'        => 'Odeslat zprávu',
    'contactSent'           => 'Vaše zpráva byla odeslána!',
    'contactCaptchaFail'    => 'Ověření captcha selhalo. Zkuste to znovu.',
    'contactSubject'        => 'Kontaktní formulář: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Navigace stránek',
    'prevPage'          => 'Předchozí',
    'nextPage'          => 'Další',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Stránka nenalezena.',
    'pageNotFoundTitle' => '404 – Stránka nenalezena',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'Údržba',
    'maintenanceBody'   => 'Provádíme plánovanou údržbu. Brzy budeme zpět – děkujeme za trpělivost!',

    // Language
    'language'          => 'Jazyk',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Můj profil',
    'profileBasicInfo'        => 'Základní informace',
    'profileUsername'          => 'Uživatelské jméno',
    'profileEmail'            => 'E-mail',
    'profilePassword'         => 'Heslo',
    'profilePasswordConfirm'  => 'Potvrdit heslo',
    'profilePasswordHelp'     => 'Ponechte prázdné pro zachování aktuálního hesla.',
    'profileSave'             => 'Uložit změny',
    'profileUpdated'          => 'Profil byl úspěšně aktualizován.',
    'profileUsernameRequired' => 'Uživatelské jméno je povinné.',
    'profileUsernameTaken'    => 'Toto uživatelské jméno je již obsazeno.',
    'profileEmailRequired'    => 'E-mail je povinný.',
    'profileEmailTaken'       => 'Tato e-mailová adresa je již používána.',
    'profilePasswordMismatch' => 'Hesla se neshodují.',
    'profilePasswordTooShort' => 'Heslo musí mít alespoň 8 znaků.',

    'profileAuthorInfo'       => 'Profil autora',
    'profileDisplayName'      => 'Zobrazované jméno',
    'profileBio'              => 'Životopis',
    'profileAvatar'           => 'Avatar',
    'profileAvatarChange'     => 'Změnit avatar',
    'profileAvatarUpload'     => 'Nahrát',
    'profileWebsite'          => 'Webová stránka',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avatar byl úspěšně aktualizován.',
    'profileAvatarInvalid'    => 'Neplatný soubor.',
    'profileAvatarTypeError'  => 'Přijímány jsou pouze obrázky JPEG, PNG, WebP a GIF.',
    'profileAvatarTooLarge'   => 'Avatar musí být menší než 2 MB.',
    'profileAvatarNotAllowed' => 'Nahrávání avatarů je dostupné pro autory a výše.',

    'login'                         => 'Přihlásit se',
    'adminPanel'                    => 'Administrace',

    'profileUpdatedRelogin'         => 'Profil aktualizován. Prosím, přihlaste se znovu.',
    'profileUsernameChangedSubject' => 'Vaše uživatelské jméno bylo změněno',
    'profileUsernameChangedBody'    => 'Vaše uživatelské jméno bylo změněno z „{0}" na „{1}". Pokud jste tuto změnu neprovedli, okamžitě kontaktujte správce webu.',
    'profileEmailChangedSubject'    => 'Vaše e-mailová adresa byla změněna',
    'profileEmailChangedBody'       => 'Vaše e-mailová adresa byla změněna z „{0}" na „{1}". Pokud jste tuto změnu neprovedli, okamžitě kontaktujte správce webu.',
    'profilePasswordChangedSubject' => 'Vaše heslo bylo změněno',
    'profilePasswordChangedBody'    => 'Vaše heslo bylo nedávno změněno. Pokud jste tuto změnu neprovedli, okamžitě kontaktujte správce webu.',

];
