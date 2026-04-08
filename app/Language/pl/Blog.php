<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Polish)
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

    'home'              => 'Strona główna',
    'blog'              => 'Blog',
    'readMore'          => 'Czytaj więcej',
    'viewAll'           => 'Zobacz wszystkie',
    'noPostsYet'        => 'Brak wpisów. Wróć wkrótce!',
    'search'            => 'Szukaj',
    'searchPlaceholder' => 'Szukaj…',
    'searchPostsPlaceholder' => 'Szukaj wpisów…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Mapa witryny',
    'allRightsReserved' => 'Wszelkie prawa zastrzeżone.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Najnowsze wpisy',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Opublikowano',
    'views'             => '{0} wyświetleń',
    'readingTime'       => '{0} min czytania',
    'publishedBy'       => 'Przez',
    'inCategory'        => 'w kategorii',
    'tags'              => 'Tagi',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Tryb podglądu - Ten wpis nie jest publicznie widoczny',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Treść premium',
    'paywallMessage'        => 'Ta treść jest dostępna dla subskrybentów premium.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => 'O autorze',
    'unknownAuthor'     => 'Nieznany autor',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Kategoria: {0}',
    'noPostsInCategory' => 'Brak wpisów w tej kategorii.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Tag: {0}',
    'noPostsWithTag'    => 'Brak wpisów z tym tagiem.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archiwum: {0}',
    'noPostsInPeriod'   => 'Brak wpisów w tym okresie.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Wyniki wyszukiwania',
    'searchShowingFor'      => 'Wyniki dla: {0}',
    'searchNoResults'       => 'Nie znaleziono wpisów dla „{0}".',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Komentarze ({0})',
    'commentsClosed'        => 'Komentarze są zamknięte.',
    'commentFormTitle'      => 'Dodaj komentarz',
    'commentLabel'          => 'Komentarz *',
    'commentPostBtn'        => 'Opublikuj komentarz',
    'commentModerated'      => 'Komentarze są moderowane przed publikacją.',
    'commentLoginRequired'  => 'aby dodać komentarz.',
    'commentLoginLink'      => 'Zaloguj się',
    'commentAwaitModeration'=> 'Twój komentarz oczekuje na moderację.',
    'commentPosted'         => 'Twój komentarz został opublikowany.',
    'commentLoginToComment' => 'Musisz być zalogowany, aby komentować.',
    'commentTooFast'        => 'Komentujesz zbyt szybko. Poczekaj kilka minut przed kolejną próbą.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Kontakt',
    'contactName'           => 'Imię i nazwisko',
    'contactEmail'          => 'E-mail',
    'contactMessage'        => 'Wiadomość',
    'contactSendBtn'        => 'Wyślij wiadomość',
    'contactSent'           => 'Twoja wiadomość została wysłana!',
    'contactCaptchaFail'    => 'Weryfikacja captcha nie powiodła się. Spróbuj ponownie.',
    'contactSubject'        => 'Formularz kontaktowy: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Nawigacja po stronach',
    'prevPage'          => 'Poprzednia',
    'nextPage'          => 'Następna',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Strona nie została znaleziona.',
    'pageNotFoundTitle' => '404 - Strona nie została znaleziona',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'Przerwa techniczna',
    'maintenanceBody'   => 'Wykonujemy zaplanowane prace konserwacyjne. Wrócimy wkrótce - dziękujemy za cierpliwość!',

    // Language
    'language'          => 'Język',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Mój profil',
    'profileBasicInfo'        => 'Podstawowe informacje',
    'profileUsername'          => 'Nazwa użytkownika',
    'profileEmail'            => 'E-mail',
    'profilePassword'         => 'Hasło',
    'profilePasswordConfirm'  => 'Potwierdź hasło',
    'profilePasswordHelp'     => 'Pozostaw puste, aby zachować bieżące hasło.',
    'profileSave'             => 'Zapisz zmiany',
    'profileUpdated'          => 'Profil został pomyślnie zaktualizowany.',
    'profileUsernameRequired' => 'Nazwa użytkownika jest wymagana.',
    'profileUsernameTaken'    => 'Ta nazwa użytkownika jest już zajęta.',
    'profileEmailRequired'    => 'E-mail jest wymagany.',
    'profileEmailTaken'       => 'Ten adres e-mail jest już w użyciu.',
    'profilePasswordMismatch' => 'Hasła nie są zgodne.',
    'profilePasswordTooShort' => 'Hasło musi mieć co najmniej 8 znaków.',

    'profileAuthorInfo'       => 'Profil autora',
    'profileDisplayName'      => 'Wyświetlana nazwa',
    'profileBio'              => 'Biografia',
    'profileAvatar'           => 'Awatar',
    'profileAvatarChange'     => 'Zmień awatar',
    'profileAvatarUpload'     => 'Prześlij',
    'profileWebsite'          => 'Strona internetowa',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Awatar został pomyślnie zaktualizowany.',
    'profileAvatarInvalid'    => 'Nieprawidłowe przesłanie pliku.',
    'profileAvatarTypeError'  => 'Akceptowane są tylko obrazy JPEG, PNG, WebP i GIF.',
    'profileAvatarTooLarge'   => 'Awatar musi mieć co najwyżej 2 MB.',
    'profileAvatarNotAllowed' => 'Przesyłanie awatarów jest dostępne dla autorów i wyżej.',

    'login'                         => 'Zaloguj się',
    'adminPanel'                    => 'Panel administracyjny',

    'profileUpdatedRelogin'         => 'Profil zaktualizowany. Zaloguj się ponownie.',
    'profileUsernameChangedSubject' => 'Twoja nazwa użytkownika została zmieniona',
    'profileUsernameChangedBody'    => 'Twoja nazwa użytkownika została zmieniona z „{0}" na „{1}". Jeśli nie dokonałeś(-aś) tej zmiany, skontaktuj się natychmiast z administratorem witryny.',
    'profileEmailChangedSubject'    => 'Twój adres e-mail został zmieniony',
    'profileEmailChangedBody'       => 'Twój adres e-mail został zmieniony z „{0}" na „{1}". Jeśli nie dokonałeś(-aś) tej zmiany, skontaktuj się natychmiast z administratorem witryny.',
    'profilePasswordChangedSubject' => 'Twoje hasło zostało zmienione',
    'profilePasswordChangedBody'    => 'Twoje hasło zostało niedawno zmienione. Jeśli nie dokonałeś(-aś) tej zmiany, skontaktuj się natychmiast z administratorem witryny.',

];
