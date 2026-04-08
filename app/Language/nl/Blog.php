<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Dutch)
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
    'readMore'          => 'Lees meer',
    'viewAll'           => 'Alles bekijken',
    'noPostsYet'        => 'Nog geen berichten. Kom later terug!',
    'search'            => 'Zoeken',
    'searchPlaceholder' => 'Zoeken…',
    'searchPostsPlaceholder' => 'Berichten zoeken…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Sitemap',
    'allRightsReserved' => 'Alle rechten voorbehouden.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Laatste berichten',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Geplaatst op',
    'views'             => '{0} weergaven',
    'readingTime'       => '{0} min lezen',
    'publishedBy'       => 'Door',
    'inCategory'        => 'in',
    'tags'              => 'Tags',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Voorbeeldmodus - Dit bericht is niet openbaar zichtbaar',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Premium inhoud',
    'paywallMessage'        => 'Deze inhoud is beschikbaar voor premium abonnees.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => 'Over de auteur',
    'unknownAuthor'     => 'Onbekende auteur',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Categorie: {0}',
    'noPostsInCategory' => 'Nog geen berichten in deze categorie.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Tag: {0}',
    'noPostsWithTag'    => 'Geen berichten met deze tag.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archief: {0}',
    'noPostsInPeriod'   => 'Geen berichten in deze periode.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Zoekresultaten',
    'searchShowingFor'      => 'Resultaten voor: {0}',
    'searchNoResults'       => 'Geen berichten gevonden voor "{0}".',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Reacties ({0})',
    'commentsClosed'        => 'Reacties zijn gesloten.',
    'commentFormTitle'      => 'Een reactie plaatsen',
    'commentLabel'          => 'Reactie *',
    'commentPostBtn'        => 'Reactie plaatsen',
    'commentModerated'      => 'Reacties worden gemodereerd voordat ze verschijnen.',
    'commentLoginRequired'  => 'om een reactie te plaatsen.',
    'commentLoginLink'      => 'Inloggen',
    'commentAwaitModeration'=> 'Uw reactie wacht op moderatie.',
    'commentPosted'         => 'Uw reactie is geplaatst.',
    'commentLoginToComment' => 'U moet ingelogd zijn om te reageren.',
    'commentTooFast'        => 'U reageert te snel. Wacht een paar minuten voordat u het opnieuw probeert.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Contact',
    'contactName'           => 'Naam',
    'contactEmail'          => 'E-mail',
    'contactMessage'        => 'Bericht',
    'contactSendBtn'        => 'Bericht verzenden',
    'contactSent'           => 'Uw bericht is verzonden!',
    'contactCaptchaFail'    => 'Captcha-verificatie mislukt. Probeer het opnieuw.',
    'contactSubject'        => 'Contactformulier: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Paginanavigatie',
    'prevPage'          => 'Vorige',
    'nextPage'          => 'Volgende',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Pagina niet gevonden.',
    'pageNotFoundTitle' => '404 - Pagina niet gevonden',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'Onderhoud',
    'maintenanceBody'   => 'We voeren gepland onderhoud uit. We zijn snel terug - bedankt voor uw geduld!',

    // Language
    'language'          => 'Taal',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Mijn profiel',
    'profileBasicInfo'        => 'Basisinformatie',
    'profileUsername'          => 'Gebruikersnaam',
    'profileEmail'            => 'E-mail',
    'profilePassword'         => 'Wachtwoord',
    'profilePasswordConfirm'  => 'Wachtwoord bevestigen',
    'profilePasswordHelp'     => 'Laat leeg om het huidige wachtwoord te bewaren.',
    'profileSave'             => 'Wijzigingen opslaan',
    'profileUpdated'          => 'Profiel succesvol bijgewerkt.',
    'profileUsernameRequired' => 'Gebruikersnaam is verplicht.',
    'profileUsernameTaken'    => 'Die gebruikersnaam is al in gebruik.',
    'profileEmailRequired'    => 'E-mail is verplicht.',
    'profileEmailTaken'       => 'Dat e-mailadres is al in gebruik.',
    'profilePasswordMismatch' => 'Wachtwoorden komen niet overeen.',
    'profilePasswordTooShort' => 'Wachtwoord moet minimaal 8 tekens bevatten.',

    'profileAuthorInfo'       => 'Auteursprofiel',
    'profileDisplayName'      => 'Weergavenaam',
    'profileBio'              => 'Bio',
    'profileAvatar'           => 'Avatar',
    'profileAvatarChange'     => 'Avatar wijzigen',
    'profileAvatarUpload'     => 'Uploaden',
    'profileWebsite'          => 'Website',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avatar succesvol bijgewerkt.',
    'profileAvatarInvalid'    => 'Ongeldig bestand geüpload.',
    'profileAvatarTypeError'  => 'Alleen JPEG-, PNG-, WebP- en GIF-afbeeldingen worden geaccepteerd.',
    'profileAvatarTooLarge'   => 'Avatar mag maximaal 2 MB zijn.',
    'profileAvatarNotAllowed' => 'Avatar-uploads zijn beschikbaar voor auteurs en hoger.',

    'login'                         => 'Inloggen',
    'adminPanel'                    => 'Beheerpaneel',

    'profileUpdatedRelogin'         => 'Profiel bijgewerkt. Log opnieuw in.',
    'profileUsernameChangedSubject' => 'Uw gebruikersnaam is gewijzigd',
    'profileUsernameChangedBody'    => 'Uw gebruikersnaam is gewijzigd van "{0}" naar "{1}". Als u deze wijziging niet heeft doorgevoerd, neem dan onmiddellijk contact op met de sitebeheerder.',
    'profileEmailChangedSubject'    => 'Uw e-mailadres is gewijzigd',
    'profileEmailChangedBody'       => 'Uw e-mailadres is gewijzigd van "{0}" naar "{1}". Als u deze wijziging niet heeft doorgevoerd, neem dan onmiddellijk contact op met de sitebeheerder.',
    'profilePasswordChangedSubject' => 'Uw wachtwoord is gewijzigd',
    'profilePasswordChangedBody'    => 'Uw wachtwoord is onlangs gewijzigd. Als u deze wijziging niet heeft doorgevoerd, neem dan onmiddellijk contact op met de sitebeheerder.',

];
