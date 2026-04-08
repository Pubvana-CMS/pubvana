<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Lithuanian)
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

    'home'              => 'Pagrindinis',
    'blog'              => 'Tinklaraštis',
    'readMore'          => 'Skaityti daugiau',
    'viewAll'           => 'Peržiūrėti viską',
    'noPostsYet'        => 'Dar nėra įrašų. Grįžkite vėliau!',
    'search'            => 'Paieška',
    'searchPlaceholder' => 'Ieškoti…',
    'searchPostsPlaceholder' => 'Ieškoti įrašų…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Svetainės žemėlapis',
    'allRightsReserved' => 'Visos teisės saugomos.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Naujausi įrašai',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Paskelbta',
    'views'             => '{0} peržiūrų',
    'readingTime'       => '{0} min skaitymo',
    'publishedBy'       => 'Autorius',
    'inCategory'        => 'kategorijoje',
    'tags'              => 'Žymos',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Peržiūros režimas – šis įrašas nėra viešai matomas',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Premium turinys',
    'paywallMessage'        => 'Šis turinys prieinamas premium prenumeratoriams.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => 'Apie autorių',
    'unknownAuthor'     => 'Nežinomas autorius',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Kategorija: {0}',
    'noPostsInCategory' => 'Šioje kategorijoje dar nėra įrašų.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Žyma: {0}',
    'noPostsWithTag'    => 'Nėra įrašų su šia žyma.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archyvas: {0}',
    'noPostsInPeriod'   => 'Šiuo laikotarpiu nėra įrašų.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Paieškos rezultatai',
    'searchShowingFor'      => 'Rodomi rezultatai: {0}',
    'searchNoResults'       => 'Nerasta įrašų „{0}".',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Komentarai ({0})',
    'commentsClosed'        => 'Komentarai uždaryti.',
    'commentFormTitle'      => 'Palikti komentarą',
    'commentLabel'          => 'Komentaras *',
    'commentPostBtn'        => 'Paskelbti komentarą',
    'commentModerated'      => 'Komentarai moderuojami prieš pasirodant.',
    'commentLoginRequired'  => 'palikti komentarą.',
    'commentLoginLink'      => 'Prisijungti',
    'commentAwaitModeration'=> 'Jūsų komentaras laukia moderavimo.',
    'commentPosted'         => 'Jūsų komentaras paskelbtas.',
    'commentLoginToComment' => 'Norėdami komentuoti, turite būti prisijungę.',
    'commentTooFast'        => 'Komentuojate per greitai. Palaukite keletą minučių prieš bandydami dar kartą.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Kontaktai',
    'contactName'           => 'Vardas',
    'contactEmail'          => 'El. paštas',
    'contactMessage'        => 'Žinutė',
    'contactSendBtn'        => 'Siųsti žinutę',
    'contactSent'           => 'Jūsų žinutė išsiųsta!',
    'contactCaptchaFail'    => 'Captcha patvirtinimas nepavyko. Bandykite dar kartą.',
    'contactSubject'        => 'Kontaktų forma: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Puslapio naršymas',
    'prevPage'          => 'Ankstesnis',
    'nextPage'          => 'Kitas',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Puslapis nerastas.',
    'pageNotFoundTitle' => '404 – Puslapis nerastas',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'Techninė priežiūra',
    'maintenanceBody'   => 'Atliekame suplanuotus techninius darbus. Greitai grįšime – dėkojame už kantrybę!',

    // Language
    'language'          => 'Kalba',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Mano profilis',
    'profileBasicInfo'        => 'Pagrindinė informacija',
    'profileUsername'          => 'Naudotojo vardas',
    'profileEmail'            => 'El. paštas',
    'profilePassword'         => 'Slaptažodis',
    'profilePasswordConfirm'  => 'Patvirtinkite slaptažodį',
    'profilePasswordHelp'     => 'Palikite tuščią, kad nepakeisite slaptažodžio.',
    'profileSave'             => 'Išsaugoti pakeitimus',
    'profileUpdated'          => 'Profilis sėkmingai atnaujintas.',
    'profileUsernameRequired' => 'Naudotojo vardas yra privalomas.',
    'profileUsernameTaken'    => 'Šis naudotojo vardas jau užimtas.',
    'profileEmailRequired'    => 'El. paštas yra privalomas.',
    'profileEmailTaken'       => 'Šis el. paštas jau naudojamas.',
    'profilePasswordMismatch' => 'Slaptažodžiai nesutampa.',
    'profilePasswordTooShort' => 'Slaptažodis turi būti bent 8 simbolių.',

    'profileAuthorInfo'       => 'Autoriaus profilis',
    'profileDisplayName'      => 'Rodomas vardas',
    'profileBio'              => 'Biografija',
    'profileAvatar'           => 'Avataras',
    'profileAvatarChange'     => 'Keisti avataras',
    'profileAvatarUpload'     => 'Įkelti',
    'profileWebsite'          => 'Svetainė',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avataras sėkmingai atnaujintas.',
    'profileAvatarInvalid'    => 'Netinkamas failo įkėlimas.',
    'profileAvatarTypeError'  => 'Priimami tik JPEG, PNG, WebP ir GIF vaizdai.',
    'profileAvatarTooLarge'   => 'Avataras turi būti ne didesnis nei 2 MB.',
    'profileAvatarNotAllowed' => 'Avatarų įkėlimas pasiekiamas autoriams ir aukštesnio lygio naudotojams.',

    'login'                         => 'Prisijungti',
    'adminPanel'                    => 'Administravimo skydelis',

    'profileUpdatedRelogin'         => 'Profilis atnaujintas. Prisijunkite iš naujo.',
    'profileUsernameChangedSubject' => 'Jūsų naudotojo vardas buvo pakeistas',
    'profileUsernameChangedBody'    => 'Jūsų naudotojo vardas buvo pakeistas iš „{0}" į „{1}". Jei šio pakeitimo nedarėte, nedelsiant susisiekite su svetainės administratoriumi.',
    'profileEmailChangedSubject'    => 'Jūsų el. pašto adresas buvo pakeistas',
    'profileEmailChangedBody'       => 'Jūsų el. pašto adresas buvo pakeistas iš „{0}" į „{1}". Jei šio pakeitimo nedarėte, nedelsiant susisiekite su svetainės administratoriumi.',
    'profilePasswordChangedSubject' => 'Jūsų slaptažodis buvo pakeistas',
    'profilePasswordChangedBody'    => 'Jūsų slaptažodis neseniai buvo pakeistas. Jei šio pakeitimo nedarėte, nedelsiant susisiekite su svetainės administratoriumi.',

];
