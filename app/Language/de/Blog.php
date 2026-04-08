<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (German)
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

    'home'              => 'Startseite',
    'blog'              => 'Blog',
    'readMore'          => 'Weiterlesen',
    'viewAll'           => 'Alle anzeigen',
    'noPostsYet'        => 'Noch keine Beiträge. Schauen Sie bald wieder vorbei!',
    'search'            => 'Suche',
    'searchPlaceholder' => 'Suchen…',
    'searchPostsPlaceholder' => 'Beiträge suchen…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Sitemap',
    'allRightsReserved' => 'Alle Rechte vorbehalten.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Neueste Beiträge',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Veröffentlicht am',
    'views'             => '{0} Aufrufe',
    'readingTime'       => '{0} Min. Lesezeit',
    'publishedBy'       => 'Von',
    'inCategory'        => 'in',
    'tags'              => 'Tags',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Vorschaumodus – Dieser Beitrag ist nicht öffentlich sichtbar',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Premium-Inhalt',
    'paywallMessage'        => 'Dieser Inhalt ist nur für Premium-Abonnenten verfügbar.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => 'Über den Autor',
    'unknownAuthor'     => 'Unbekannter Autor',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Kategorie: {0}',
    'noPostsInCategory' => 'Noch keine Beiträge in dieser Kategorie.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Tag: {0}',
    'noPostsWithTag'    => 'Keine Beiträge mit diesem Tag.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archiv: {0}',
    'noPostsInPeriod'   => 'Keine Beiträge in diesem Zeitraum.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Suchergebnisse',
    'searchShowingFor'      => 'Ergebnisse für: {0}',
    'searchNoResults'       => 'Keine Beiträge für „{0}" gefunden.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Kommentare ({0})',
    'commentsClosed'        => 'Kommentare sind geschlossen.',
    'commentFormTitle'      => 'Kommentar hinterlassen',
    'commentLabel'          => 'Kommentar *',
    'commentPostBtn'        => 'Kommentar absenden',
    'commentModerated'      => 'Kommentare werden vor der Veröffentlichung moderiert.',
    'commentLoginRequired'  => 'um einen Kommentar zu hinterlassen.',
    'commentLoginLink'      => 'Anmelden',
    'commentAwaitModeration'=> 'Ihr Kommentar wartet auf Freischaltung.',
    'commentPosted'         => 'Ihr Kommentar wurde veröffentlicht.',
    'commentLoginToComment' => 'Sie müssen angemeldet sein, um zu kommentieren.',
    'commentTooFast'        => 'Sie kommentieren zu schnell. Bitte warten Sie einige Minuten, bevor Sie es erneut versuchen.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Kontakt',
    'contactName'           => 'Name',
    'contactEmail'          => 'E-Mail',
    'contactMessage'        => 'Nachricht',
    'contactSendBtn'        => 'Nachricht senden',
    'contactSent'           => 'Ihre Nachricht wurde gesendet!',
    'contactCaptchaFail'    => 'Captcha-Überprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'contactSubject'        => 'Kontaktformular: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Seitennavigation',
    'prevPage'          => 'Zurück',
    'nextPage'          => 'Weiter',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Seite nicht gefunden.',
    'pageNotFoundTitle' => '404 – Seite nicht gefunden',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'Wartungsarbeiten',
    'maintenanceBody'   => 'Wir führen planmäßige Wartungsarbeiten durch. Wir sind bald wieder da – vielen Dank für Ihre Geduld!',

    // Language
    'language'          => 'Sprache',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Mein Profil',
    'profileBasicInfo'        => 'Grundlegende Informationen',
    'profileUsername'          => 'Benutzername',
    'profileEmail'            => 'E-Mail',
    'profilePassword'         => 'Passwort',
    'profilePasswordConfirm'  => 'Passwort bestätigen',
    'profilePasswordHelp'     => 'Leer lassen, um das aktuelle Passwort beizubehalten.',
    'profileSave'             => 'Änderungen speichern',
    'profileUpdated'          => 'Profil erfolgreich aktualisiert.',
    'profileUsernameRequired' => 'Benutzername ist erforderlich.',
    'profileUsernameTaken'    => 'Dieser Benutzername ist bereits vergeben.',
    'profileEmailRequired'    => 'E-Mail ist erforderlich.',
    'profileEmailTaken'       => 'Diese E-Mail-Adresse wird bereits verwendet.',
    'profilePasswordMismatch' => 'Passwörter stimmen nicht überein.',
    'profilePasswordTooShort' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

    'profileAuthorInfo'       => 'Autorenprofil',
    'profileDisplayName'      => 'Anzeigename',
    'profileBio'              => 'Biografie',
    'profileAvatar'           => 'Avatar',
    'profileAvatarChange'     => 'Avatar ändern',
    'profileAvatarUpload'     => 'Hochladen',
    'profileWebsite'          => 'Website',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avatar erfolgreich aktualisiert.',
    'profileAvatarInvalid'    => 'Ungültiger Datei-Upload.',
    'profileAvatarTypeError'  => 'Nur JPEG-, PNG-, WebP- und GIF-Bilder werden akzeptiert.',
    'profileAvatarTooLarge'   => 'Der Avatar darf maximal 2 MB groß sein.',
    'profileAvatarNotAllowed' => 'Avatar-Uploads sind für Autoren und höhere Rollen verfügbar.',

    'login'                         => 'Anmelden',
    'adminPanel'                    => 'Admin-Panel',

    'profileUpdatedRelogin'         => 'Profil aktualisiert. Bitte melden Sie sich erneut an.',
    'profileUsernameChangedSubject' => 'Ihr Benutzername wurde geändert',
    'profileUsernameChangedBody'    => 'Ihr Benutzername wurde von „{0}" in „{1}" geändert. Wenn Sie diese Änderung nicht vorgenommen haben, kontaktieren Sie bitte umgehend den Website-Administrator.',
    'profileEmailChangedSubject'    => 'Ihre E-Mail-Adresse wurde geändert',
    'profileEmailChangedBody'       => 'Ihre E-Mail-Adresse wurde von „{0}" in „{1}" geändert. Wenn Sie diese Änderung nicht vorgenommen haben, kontaktieren Sie bitte umgehend den Website-Administrator.',
    'profilePasswordChangedSubject' => 'Ihr Passwort wurde geändert',
    'profilePasswordChangedBody'    => 'Ihr Passwort wurde kürzlich geändert. Wenn Sie diese Änderung nicht vorgenommen haben, kontaktieren Sie bitte umgehend den Website-Administrator.',

];
