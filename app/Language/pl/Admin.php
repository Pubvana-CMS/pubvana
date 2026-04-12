<?php

/**
 * Pubvana CMS - Admin language strings (Polish)
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
    'save'              => 'Zapisz',
    'saveChanges'       => 'Zapisz zmiany',
    'cancel'            => 'Anuluj',
    'edit'              => 'Edytuj',
    'delete'            => 'Usuń',
    'create'            => 'Utwórz',
    'add'               => 'Dodaj',
    'back'              => 'Wstecz',
    'view'              => 'Wyświetl',
    'apply'             => 'Zastosuj',
    'install'           => 'Zainstaluj',
    'update'            => 'Aktualizuj',
    'refresh'           => 'Odśwież',
    'activate'          => 'Aktywuj',
    'deactivate'        => 'Dezaktywuj',
    'enable'            => 'Włącz',
    'disable'           => 'Wyłącz',
    'disabled'          => 'Wyłączone',
    'approve'           => 'Zatwierdź',
    'spam'              => 'Spam',
    'trash'             => 'Kosz',
    'restore'           => 'Przywróć',
    'dismiss'           => 'Odrzuć',
    'recheck'           => 'Sprawdź ponownie',
    'clickToCopy'       => 'Kliknij, aby skopiować',
    'download'          => 'Pobierz',
    'upload'            => 'Prześlij',
    'import'            => 'Importuj',
    'export'            => 'Eksportuj',
    'publish'           => 'Opublikuj',
    'unpublish'         => 'Cofnij publikację',
    'logout'            => 'Wyloguj',
    'viewSite'          => 'Wyświetl witrynę',
    'newPost'           => 'Nowy wpis',
    'buyNow'            => 'Kup teraz',
    'visitStore'        => 'Odwiedź sklep',
    'loadMore'          => 'Wczytaj więcej',

    // Table headers / labels
    'title'             => 'Tytuł',
    'name'              => 'Nazwa',
    'slug'              => 'Slug',
    'status'            => 'Status',
    'date'              => 'Data',
    'actions'           => 'Akcje',
    'author'            => 'Autor',
    'views'             => 'Wyświetlenia',
    'type'              => 'Typ',
    'url'               => 'URL',
    'description'       => 'Opis',
    'role'              => 'Rola',
    'email'             => 'E-mail',
    'username'          => 'Nazwa użytkownika',
    'active'            => 'Aktywny',
    'version'           => 'Wersja',
    'size'              => 'Rozmiar',
    'clicks'            => 'Kliknięcia',
    'total'             => 'Łącznie',
    'platform'          => 'Platforma',
    'label'             => 'Etykieta',
    'order'             => 'Kolejność',
    'source'            => 'Źródło',
    'content'           => 'Treść',
    'excerpt'           => 'Wstęp',
    'details'           => 'Szczegóły',
    'contentType'       => 'Typ treści',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta tytuł',
    'metaDescription'   => 'Meta opis',

    // Status badges
    'published'         => 'Opublikowany',
    'draft'             => 'Szkic',
    'scheduled'         => 'Zaplanowany',
    'pending'           => 'Oczekujący',
    'safe'              => 'Bezpieczny',
    'notSafe'           => 'Niebezpieczny',
    'malicious'         => 'Złośliwy',
    'safetyUnknown'     => 'Nieznany',
    'inactive'          => 'Nieaktywny',
    'installed'         => 'Zainstalowany',
    'free'              => 'Darmowy',
    'premium'           => 'Premium',
    'all'               => 'Wszystkie',

    // Confirmations
    'confirmDelete'         => 'Czy na pewno chcesz usunąć ten element?',
    'confirmDeletePost'     => 'Usunąć ten wpis?',
    'confirmDeletePage'     => 'Usunąć tę stronę?',
    'confirmDeleteComment'  => 'Trwale usunąć ten komentarz?',
    'confirmDeleteUser'     => 'Usunąć tego użytkownika?',
    'confirmDeleteMedia'    => 'Usunąć?',
    'confirmDeleteBackup'   => 'Usunąć ten plik kopii zapasowej?',
    'confirmBulkAction'     => 'Zastosować akcję zbiorczą do zaznaczonych wpisów?',

    // Empty states
    'noPostsYet'        => 'Brak wpisów. {0}',
    'noResultsFound'    => 'Nie znaleziono wyników.',
    'noCommentsYet'     => 'Brak oczekujących komentarzy.',
    'noMediaYet'        => 'Brak mediów.',
    'noItemsFound'      => 'Nie znaleziono elementów w marketplace.',
    'noCategoriesYet'   => 'Brak kategorii.',
    'noTagsYet'         => 'Brak tagów.',
    'noRevisionsYet'    => 'Nie znaleziono wersji.',

    // Misc common
    'permissionDenied'  => 'Odmowa dostępu.',
    'notFound'          => 'Rekord nie został znaleziony.',
    'commasSeparated'   => 'Oddzielone przecinkami',
    'optional'          => 'Opcjonalne',
    'required'          => 'Wymagane',
    'enabled'           => 'Włączone',
    'selected'          => '{0} wpis(y) zaznaczony(-e)',
    'published_count'   => '{0} opublikowanych',
    'pending_count'     => '{0} oczekujących',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Panel',
    'navContent'        => 'Treść',
    'navAppearance'     => 'Wygląd',
    'navUsersAndSite'   => 'Użytkownicy i witryna',
    'navTools'          => 'Narzędzia',
    'navMarketplace'    => 'Marketplace',
    'navPlugins'        => 'Wtyczki',
    'navPosts'          => 'Wpisy',
    'navSchedule'       => 'Harmonogram',
    'navPages'          => 'Strony',
    'navCategories'     => 'Kategorie',
    'navTags'           => 'Tagi',
    'navComments'       => 'Komentarze',
    'navMedia'          => 'Media',
    'navImport'         => 'Importuj',
    'navThemes'         => 'Motywy',
    'navWidgets'        => 'Widżety',
    'navNavigation'     => 'Nawigacja',
    'navUsers'          => 'Użytkownicy',
    'navSocialLinks'    => 'Media społecznościowe',
    'navRedirects'      => 'Przekierowania',
    'navLanguages'      => 'Języki',
    'navSettings'       => 'Ustawienia',
    'navAnalytics'      => 'Analityka',
    'navAffiliates'     => 'Linki afiliacyjne',
    'navBrokenLinks'    => 'Uszkodzone linki',
    'navActivityLog'    => 'Dziennik aktywności',
    'navBackup'         => 'Kopia zapasowa i eksport',
    'navUpdates'        => 'Aktualizacje',
    'navBrowse'         => 'Przeglądaj',
    'navLicenses'       => 'Licencje',
    'navPubvanaStore'   => 'Sklep Pubvana',
    'navUpdateAvailable'=> 'Dostępna aktualizacja',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Chcesz się wylogować?',
    'logoutModalBody'   => 'Wybierz „Wyloguj" poniżej, aby zakończyć sesję.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Panel',
    'dashStats'             => 'Statystyki',
    'dashPosts'             => 'Wpisy',
    'dashPages'             => 'Strony',
    'dashComments'          => 'Komentarze',
    'dashUsers'             => 'Użytkownicy',
    'dashRecentPosts'       => 'Ostatnie wpisy',
    'dashPendingComments'   => 'Oczekujące komentarze',
    'dashViewAll'           => 'Zobacz wszystkie',
    'dashCreateOne'         => 'Utwórz jeden!',
    'dashNoPosts'           => 'Brak wpisów.',
    'dashNoPendingComments' => 'Brak oczekujących komentarzy.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Wpisy',
    'newPostTitle'          => 'Nowy wpis',
    'editPostTitle'         => 'Edytuj wpis: {0}',
    'copyPreviewLink'       => 'Kopiuj link podglądu',
    'backToPosts'           => 'Wróć do wpisów',
    'postTitleField'        => 'Tytuł *',
    'postEditor'            => 'Edytor',
    'postHtmlEditor'        => 'Edytor HTML',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Wstęp',
    'postExcerptPlaceholder'=> 'Opcjonalne krótkie podsumowanie...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta tytuł',
    'postMetaDescription'   => 'Meta opis',
    'postPublishSection'    => 'Publikuj',
    'postStatus'            => 'Status',
    'postStatusDraft'       => 'Szkic',
    'postStatusPublished'   => 'Opublikowany',
    'postStatusScheduled'   => 'Zaplanowany',
    'postScheduledAt'       => 'Zaplanowana data i godzina',
    'postFeatured'          => 'Wyróżniony wpis',
    'postMembersOnly'       => 'Tylko dla członków',
    'postShareOnPublish'    => 'Udostępnij w mediach społecznościowych przy publikacji',
    'postSaveBtn'           => 'Zapisz wpis',
    'postFeaturedImage'     => 'Obraz wyróżniony',
    'postFeaturedImagePlaceholder' => 'URL lub ścieżka przesyłania…',
    'postCategories'        => 'Kategorie',
    'postTags'              => 'Tagi',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'Wersje',
    'postRevisionCount'     => '{0} wersja(-e)',
    'postPreview'           => 'Podgląd',
    'postBulkAction'        => '- Wybierz akcję -',
    'postBulkPublish'       => 'Opublikuj',
    'postBulkUnpublish'     => 'Cofnij publikację (ustaw jako szkic)',
    'postBulkDelete'        => 'Usuń',

    // Post flash messages
    'postCreated'           => 'Wpis został pomyślnie utworzony.',
    'postUpdated'           => 'Wpis zaktualizowany.',
    'scheduledDateMustBeFuture' => 'Zaplanowana data musi być w przyszłości.',
    'postDeleted'           => 'Wpis usunięty.',
    'postBulkUpdated'       => '{0} wpis(y) zaktualizowany(-e).',
    'postBulkInvalid'       => 'Nieprawidłowa akcja zbiorcza.',
    'postPermission'        => 'Możesz edytować tylko własne wpisy.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Wersje: {0}',
    'revisionTitle'         => 'Wersja — {0}',
    'revisionShowTitle'     => 'Wersja',
    'revisionsBackToPost'   => 'Wróć do wpisu',
    'revisionsBackToList'   => 'Wróć do listy wersji',
    'revisionRestored'      => 'Wpis przywrócony do wersji z {0}.',
    'revisionRestoreBtn'    => 'Przywróć tę wersję',
    'revisionSaved'         => 'Zapisano',
    'revisionBy'            => 'Przez',
    'revisionOn'            => 'Dnia',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Strony',
    'newPageTitle'          => 'Nowa strona',
    'editPageTitle'         => 'Edytuj stronę',
    'pageSlugInUse'         => "Slug '{0}' jest już w użyciu.",
    'pageCannotDelete'      => 'Nie można usunąć tej strony.',
    'slugAutoGenHint'       => 'automatycznie generowany z tytułu, jeśli puste',
    'slugCannotChange'      => 'nie można zmienić',
    'colSystem'             => 'System',
    'system'                => 'System',

    // Page flash messages
    'pageCreated'           => 'Strona utworzona.',
    'pageUpdated'           => 'Strona zaktualizowana.',
    'pageDeleted'           => 'Strona usunięta.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Kategorie',
    'newCategoryTitle'      => 'Nowa kategoria',
    'editCategoryTitle'     => 'Edytuj kategorię',
    'categoryName'          => 'Nazwa',
    'categoryDescription'   => 'Opis',
    'categoryPostCount'     => 'Liczba wpisów',

    // Category flash messages
    'categoryCreated'       => 'Kategoria utworzona.',
    'categoryUpdated'       => 'Kategoria zaktualizowana.',
    'categoryDeleted'       => 'Kategoria usunięta.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Tagi',
    'tagPostCount'          => 'Liczba wpisów',

    // Tag flash messages
    'tagDeleted'            => 'Tag usunięty.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Komentarze',
    'commentAuthor'         => 'Autor',
    'commentContent'        => 'Komentarz',
    'commentPost'           => 'Wpis',
    'commentDate'           => 'Data',
    'commentStatusFilter'   => 'Filtruj według statusu',

    // Comment flash messages
    'commentApproved'       => 'Komentarz zatwierdzony.',
    'commentSpam'           => 'Oznaczony jako spam.',
    'commentTrashed'        => 'Komentarz przeniesiony do kosza.',
    'commentDeleted'        => 'Komentarz trwale usunięty.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Biblioteka mediów',
    'mediaTitle'            => 'Tytuł',
    'mediaAltText'          => 'Tekst alternatywny',
    'mediaAltPlaceholder'   => 'Opisz obraz dla dostępności',
    'mediaTitlePlaceholder' => 'Opcjonalny tytuł obrazu',
    'mediaImageDetails'     => 'Szczegóły obrazu',
    'mediaSaved'            => 'Zapisano!',
    'mediaNoSelection'      => 'Nie wybrano obrazu',
    'mediaBrowse'           => 'Przeglądaj media',
    'mediaRemove'           => 'Usuń',
    'mediaUseImage'         => 'Użyj tego obrazu',
    'mediaDropzone'         => 'Przeciągnij i upuść obraz tutaj lub kliknij, aby przeglądać',
    'mediaLoading'          => 'Ładowanie mediów…',
    'mediaEmpty'            => 'Brak przesłanych mediów.',
    'mediaUpload'           => 'Prześlij media',
    'mediaDragDrop'         => 'Przeciągnij i upuść pliki tutaj lub',
    'mediaChooseFiles'      => 'Wybierz pliki',
    'mediaUploading'        => 'Przesyłanie…',
    'mediaFilename'         => 'Nazwa pliku',
    'mediaSize'             => 'Rozmiar',
    'mediaUploadFailed'     => 'Przesyłanie nie powiodło się: {0}',
    'mediaUploadError'      => 'Błąd przesyłania: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Media usunięte.',
    'mediaNoValidFile'      => 'Nie przesłano prawidłowego pliku.',
    'mediaUploadSuccess'    => 'Plik pomyślnie przesłany.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Nawigacja',
    'navQuickAdd'           => 'Szybkie dodawanie',
    'navQuickAddPlaceholder' => 'Wyszukaj strony, kategorie, wtyczki...',
    'navItemLabel'          => 'Etykieta',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Cel',
    'navItemOrder'          => 'Kolejność sortowania',
    'navGroupPrimary'       => 'Główna',
    'navGroupFooter'        => 'Stopka',
    'navSelectGroup'        => 'Wybierz grupę nawigacji:',
    'navParent'             => 'Element nadrzędny',
    'navTopLevel'           => '— Najwyższy poziom —',
    'navSameWindow'         => 'To samo okno',
    'navNewWindow'          => 'Nowe okno',
    'navMenuItems'          => 'Elementy menu',
    'navNoItems'            => 'Brak elementów w tym menu.',
    'dragToReorder'         => 'Przeciągnij, aby zmienić kolejność',

    // Navigation flash messages
    'navItemAdded'          => 'Element nawigacji dodany.',
    'navItemRemoved'        => 'Element nawigacji usunięty.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Motywy',
    'themeOptions'          => 'Opcje motywu',
    'themeActivate'         => 'Aktywuj',
    'themeOptionsBtn'       => 'Opcje',
    'themeActive'           => 'Aktywny',
    'themeBy'               => 'Przez',
    'themeSupport'          => 'Wsparcie',
    'themeVersion'          => 'Wersja',
    'themeSaveOptions'      => 'Zapisz opcje',
    'themeInvalidLicense'   => 'Nie można aktywować motywu - licencja jest nieważna. Zainstaluj ponownie lub skontaktuj się z pomocą techniczną.',
    'themeValidationFailed' => 'Motyw zawiera kod PHP i nie może zostać aktywowany.',
    'noThemesInstalled'     => 'Brak zainstalowanych motywów. Odwiedź Marketplace, aby pobrać motywy.',
    'themeUnapprovedTitle'  => 'Aktywować niezatwierdzony motyw?',
    'themeNotApproved'      => 'Ten motyw nie został zatwierdzony przez Pubvana.',
    'themeUnapprovedRisk'   => 'Aktywowanie niezatwierdzonych motywów może wprowadzić zagrożenia bezpieczeństwa lub problemy ze zgodnością.',
    'themeActivateConfirm'  => 'Czy na pewno chcesz go aktywować mimo to?',
    'themeActivateAnyway'   => 'Aktywuj mimo to',
    'themeNoOptions'        => 'Ten motyw nie ma konfigurowalnych opcji.',
    'themeCustomize'        => 'Dostosuj motyw',

    // Theme flash messages
    'themeActivated'        => 'Motyw aktywowany.',
    'themeOptionsSaved'     => 'Opcje zapisane.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Licencjonowany',
    'licenseCheckNow'        => 'Sprawdź teraz',
    'licenseExpired'         => 'Wygasła',
    'licenseEnterKey'        => 'Wprowadź klucz',
    'licenseChangeKey'       => 'Zmień',
    'licenseRenew'           => 'Odnów',
    'licenseThirdParty'      => 'Strona trzecia',
    'unchecked'              => 'Niesprawdzony',
    'safetyLabel'            => 'Bezpieczeństwo:',
    'recheckBtn'             => 'Sprawdź ponownie',
    'recheckSuccess'         => 'Sprawdzenie bezpieczeństwa zaktualizowane.',
    'recheckFailed'          => 'Nie udało się połączyć z serwerem weryfikacji. Spróbuj ponownie później.',
    'recheckNotFound'        => 'Element nie znaleziony.',
    'widgetBlockedMalicious' => '{0} został oznaczony jako złośliwy i nie może zostać dodany.',
    'licenseNoStoreProduct'  => 'Ten element nie jest powiązany z produktem sklepowym. Jeśli zakupiłeś ten element, zainstaluj go ponownie z marketplace, aby włączyć licencjonowanie.',
    'securityWarning'        => 'Ostrzeżenie bezpieczeństwa:',
    'licenseModalTitle'      => 'Wprowadź klucz licencji',
    'licenseModalBody'       => 'Wklej swój klucz licencji poniżej.',
    'licenseModalSave'       => 'Zapisz',
    'licenseSaved'           => 'Klucz licencji zapisany i zweryfikowany.',
    'licenseInvalid'         => 'Klucz licencji jest nieprawidłowy.',
    'licenseKeyRequired'     => 'Klucz licencji i produkt są wymagane.',
    'licenseCheckFailed'     => 'Nie można połączyć się z serwerem licencji. Spróbuj ponownie później.',
    'licenseProductNotFound' => 'Nie można znaleźć tego elementu w sklepie.',
    'btnCancel'              => 'Anuluj',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widżety',
    'widgetConfigureTitle'  => 'Konfiguruj widżet',
    'widgetAreas'           => 'Obszary widżetów',
    'widgetAvailable'       => 'Dostępne widżety',
    'widgetAddToArea'       => 'Dodaj do obszaru',
    'widgetArea'            => 'Obszar',
    'widgetNoOptions'       => 'Brak opcji.',
    'widgetSaveConfig'      => 'Zapisz konfigurację',
    'widgetConfigure'       => 'Konfiguruj',
    'widgetNoAreas'         => 'Nie znaleziono obszarów widżetów. Aktywuj motyw, aby włączyć obszary widżetów.',
    'widgetAreaEmpty'       => 'Brak widżetów w tym obszarze. Dodaj jeden z listy →',

    // Widget flash messages
    'widgetAdded'           => 'Widżet dodany.',
    'widgetRemoved'         => 'Widżet usunięty.',
    'widgetConfigured'      => 'Widżet skonfigurowany.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Marketplace',
    'marketplaceRefresh'    => 'Odśwież',
    'marketplaceVisitStore' => 'Odwiedź sklep',
    'marketplaceAll'        => 'Wszystkie',
    'marketplaceThemes'     => 'Motywy',
    'marketplaceWidgets'    => 'Widżety',
    'marketplacePlugins'    => 'Wtyczki',
    'marketplaceUpdatesAvailable' => '{0} aktualizacja(-e) dostępna(-e).',
    'marketplaceBy'         => 'Przez',
    'marketplaceFree'       => 'Darmowy',
    'marketplaceInstalled'  => 'Zainstalowany',
    'marketplaceInstall'    => 'Zainstaluj',
    'marketplaceBuyNow'     => 'Kup teraz',
    'marketplaceNoItems'    => 'Nie znaleziono elementów w marketplace.',
    'marketplaceInstalledVersion' => 'v{0} zainstalowana',
    'marketplaceLoadError'  => 'Nie można załadować produktów ze sklepu. Spróbuj ponownie później.',
    'byAuthor'              => 'Przez {0}',
    'unknown'               => 'Nieznany',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} pomyślnie zainstalowany(-a).',
    'marketplaceInstallFail'    => 'Instalacja nie powiodła się. Sprawdź logi.',
    'marketplaceUpdateSuccess'  => 'Pomyślnie zaktualizowano.',
    'marketplaceUpdateFail'     => 'Aktualizacja nie powiodła się.',
    'marketplaceCacheRefreshed' => 'Pamięć podręczna marketplace odświeżona.',
    'marketplaceInvalidRequest' => 'Nieprawidłowe żądanie instalacji.',
    'marketplaceCannotUpdate'   => 'Nie można zaktualizować tego elementu.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licencje',
    'licensesNone'                => 'Brak licencji',
    'licensesProduct'             => 'Produkt',
    'licensesKey'                 => 'Klucz licencji',
    'licensesStatus'              => 'Status',
    'licensesType'                => 'Typ',
    'licensesExpires'             => 'Wygasa',
    'licensesDomain'              => 'Domena',
    'licensesInstalled'           => 'Zainstalowany',
    'licensesLastChecked'         => 'Ostatnio sprawdzony',
    'licensesActions'             => 'Akcje',
    'licensesStatusValid'         => 'Ważna',
    'licensesStatusInvalid'       => 'Nieważna',
    'licensesStatusExpired'       => 'Wygasła',
    'licensesStatusSubExpired'    => 'Subskrypcja wygasła',
    'licensesStatusUnchecked'     => 'Niesprawdzona',
    'licensesSubscription'        => 'Subskrypcja',
    'licensesOneTime'             => 'Jednorazowy',
    'licensesPerpetual'           => 'Bezterminowy',
    'licensesNotInstalled'        => 'Niezainstalowany',
    'licensesNever'               => 'Nigdy',
    'licensesRevalidate'          => 'Sprawdź ponownie',
    'licenseKeyPlaceholder'       => 'Wprowadź klucz licencji...',
    'marketplaceLicensesEmpty'    => 'Licencjonowane produkty pojawią się tutaj po instalacji.',
    'typeTheme'                   => 'Motyw',
    'typeWidget'                  => 'Widżet',
    'typePlugin'                  => 'Wtyczka',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Licencja pomyślnie zweryfikowana.',
    'licenseRevalidateInvalid'     => 'Licencja jest nieważna lub wygasła.',
    'licenseRevalidateUnreachable' => 'Nie można połączyć się z serwerem licencji. Spróbuj ponownie później.',
    'licenseRevalidateSkipped'     => 'Sprawdzanie licencji pominięte (tryb deweloperski).',
    'licenseRevalidateNotFound'    => 'Licencja nie została znaleziona.',

    // License warning banners
    'licenseWarningTitle'   => 'Problemy z licencją',
    'licenseWarningInvalid' => 'licencja jest nieważna lub wygasła',
    'licenseWarningManage'  => 'Zarządzaj licencjami',

    // Plugin license
    'pluginInvalidLicense' => 'Ta wtyczka ma nieważną lub wygasłą licencję i nie może zostać aktywowana.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Klucz licencji',
    'storeBrowseFull'       => 'Przeglądaj pełny sklep',
    'storeBackToMarketplace'=> 'Wróć do marketplace',
    'storeNoProducts'       => 'Brak dostępnych produktów.',
    'storeViewInStore'      => 'Wyświetl w sklepie',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Użytkownicy',
    'editUserTitle'         => 'Edytuj użytkownika',
    'createUserTitle'       => 'Utwórz użytkownika',
    'authorProfileTitle'    => 'Profil autora',
    'userRoleLabel'         => 'Rola',
    'userActiveLabel'       => 'Aktywny',
    'userPasswordLabel'     => 'Hasło',
    'userPasswordOptional'  => 'Pozostaw puste, aby zachować bieżące hasło',
    'userDisplayName'       => 'Wyświetlana nazwa',
    'userBio'               => 'Biografia',
    'userWebsite'           => 'Strona internetowa',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Awatar',
    'userSaveProfile'       => 'Zapisz profil',
    'userSaveChanges'       => 'Zapisz zmiany',
    'userCannotDeleteSelf'  => 'Nie możesz usunąć samego siebie.',
    'userCannotDeleteOwner' => 'Konto właściciela witryny nie może zostać usunięte.',
    'userOwnerCannotModify' => 'Konto właściciela witryny nie może być modyfikowane.',

    // User flash messages
    'userCreated'           => 'Użytkownik utworzony.',
    'userUpdated'           => 'Użytkownik zaktualizowany.',
    'userDeleted'           => 'Użytkownik usunięty.',
    'userBanned'            => 'Użytkownik został zablokowany.',
    'userUnbanned'          => 'Użytkownik został odblokowany.',
    'userCannotBanSelf'     => 'Nie możesz zablokować samego siebie ani właściciela witryny.',
    'banStatus'             => 'Status blokady',
    'banned'                => 'Zablokowany',
    'ban'                   => 'Zablokuj użytkownika',
    'unban'                 => 'Odblokuj',
    'banReasonRequired'     => 'Powód blokady jest wymagany.',
    'banReasonPlaceholder'  => 'Powód blokady...',
    'confirmBanUser'        => 'Czy na pewno chcesz zablokować tego użytkownika?',
    'userProfileSaved'      => 'Profil zapisany.',
    'userAvatarUploadFail'  => 'Przesyłanie awatara nie powiodło się: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Konfiguracja 2FA',
    'tfaSetupHeading'       => 'Skonfiguruj uwierzytelnianie dwuskładnikowe',
    'tfaScanQr'             => 'Zeskanuj poniższy kod QR za pomocą aplikacji uwierzytelniającej (np. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Lub wprowadź klucz tajny ręcznie:',
    'tfaEnterCode'          => 'Wprowadź 6-cyfrowy kod z aplikacji, aby potwierdzić:',
    'tfaCodeLabel'          => 'Kod uwierzytelniania',
    'tfaConfirmBtn'         => 'Potwierdź i włącz 2FA',
    'tfaDisableBtn'         => 'Wyłącz 2FA',
    'tfaDisableConfirm'     => 'Wprowadź bieżący kod 2FA, aby wyłączyć:',
    'tfaEnabled'            => 'Uwierzytelnianie dwuskładnikowe włączone.',
    'tfaDisabled'           => 'Uwierzytelnianie dwuskładnikowe wyłączone.',
    'tfaInvalidCode'        => 'Nieprawidłowy kod - zeskanuj kod QR i spróbuj ponownie.',
    'tfaInvalidDisable'     => 'Nieprawidłowy kod - 2FA nie zostało wyłączone.',
    'tfaSessionExpired'     => 'Sesja konfiguracji wygasła - zacznij od nowa.',
    'tfaNotEnabled'         => '2FA nie jest aktualnie włączone.',
    'tfaCantScan'           => 'Nie możesz zeskanować? Wprowadź ten kod ręcznie:',
    'tfaWarning'            => 'Przechowuj ten klucz tajny w bezpiecznym miejscu. Będziesz go potrzebować do odzyskania dostępu, jeśli utracisz urządzenie uwierzytelniające.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Media społecznościowe',
    'socialPlatform'           => 'Platforma',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Ikona',
    'socialSortOrder'          => 'Kolejność sortowania',
    'socialIconPackInfo'       => 'Aktualny motyw <strong>{0}</strong> używa <strong>{1}</strong> (v{2}) dla ikon. Poniżej możesz wybrać dostępne ikony, które będą wyświetlane dla funkcji linków społecznościowych tej witryny.',
    'socialSearchPlaceholder'  => 'Szukaj platform...',
    'socialIconDisclaimer'     => 'Te ikony są jedynie reprezentacją używanej ikony. Rzeczywista ikona może się różnić w zależności od pakietu ikon aktywnego motywu.',

    // Social flash messages
    'socialLinkAdded'       => 'Link społecznościowy dodany.',
    'socialLinkUpdated'     => 'Link zaktualizowany.',
    'socialLinkDeleted'     => 'Link usunięty.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Przekierowania',
    'redirectFrom'          => 'Z URL',
    'redirectTo'            => 'Do URL',
    'redirectType'          => 'Typ',
    'redirectAdd'           => 'Dodaj przekierowanie',
    'redirectFromHint'      => '(względny, np. /stara-strona)',
    'redirect301'           => '301 Trwałe',
    'redirect302'           => '302 Tymczasowe',
    'redirectInvalidDest'   => 'Nieprawidłowy docelowy URL przekierowania.',

    // Redirect flash messages
    'redirectAdded'         => 'Przekierowanie dodane.',
    'redirectDeleted'       => 'Przekierowanie usunięte.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Ustawienia',
    'settingsGeneral'       => 'Ogólne',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'E-mail',
    'settingsSocialLogin'   => 'Logowanie przez media społecznościowe',
    'settingsSocialSharing' => 'Udostępnianie w mediach społecznościowych',
    'settingsSpam'          => 'Ochrona przed spamem',

    'generalSettingsHeading'    => 'Ustawienia ogólne',
    'generalSiteName'           => 'Nazwa witryny',
    'generalTagline'            => 'Motto',
    'generalAdminEmail'         => 'E-mail administratora',
    'generalPostsPerPage'       => 'Wpisy na stronę',
    'generalComments'           => 'Komentarze',
    'generalCommentsEnable'     => 'Włącz komentarze',
    'generalCommentModeration'  => 'Wymagaj moderacji przed publikacją',
    'generalMaintenanceMode'    => 'Tryb konserwacji',
    'generalMaintenanceEnable'  => 'Włącz tryb konserwacji',
    'generalMaintenanceHelp'    => 'Odwiedzający widzą stronę „Wrócimy wkrótce". Administratorzy nadal mają dostęp do witryny.',
    'generalFrontPage'          => 'Strona główna',
    'generalFrontPageBlog'      => 'Indeks bloga (najnowsze wpisy)',
    'generalFrontPageStatic'    => 'Strona statyczna:',
    'generalFrontPagePlugin'    => 'Strona wtyczki:',
    'generalSelectPage'         => '- Wybierz stronę -',
    'generalSelectRoute'        => '- Wybierz trasę -',
    'generalFrontPageNoPlugins' => 'Brak dostępnych tras wtyczek',
    'generalPageCacheTtl'       => 'TTL pamięci podręcznej strony',
    'settingsCacheTtlHint'      => 'Sekundy. 0 = wyłączone.',
    'generalSaveBtn'            => 'Zapisz ustawienia ogólne',

    // General flash messages
    'generalSettingsSaved'      => 'Ustawienia ogólne zapisane.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'Ustawienia SEO',
    'seoMetaDescription'        => 'Meta opis',
    'seoGoogleAnalytics'        => 'ID Google Analytics',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Mapa witryny',
    'seoSitemapEnable'          => 'Włącz sitemap.xml',
    'seoSitemapHelp'            => 'Standardowa mapa witryny dla wszystkich opublikowanych wpisów i stron.',
    'seoNewsSitemap'            => 'Włącz news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Mapa witryny Google News - lista wpisów opublikowanych w ciągu ostatnich 48 godzin.',
    'seoSaveBtn'                => 'Zapisz ustawienia SEO',
    'seoSettingsSaved'          => 'Ustawienia SEO zapisane.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Ustawienia e-mail',
    'emailFromName'             => 'Nazwa nadawcy',
    'emailFromAddress'          => 'Adres nadawcy',
    'emailProtocol'             => 'Protokół',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'Host SMTP',
    'emailSmtpPort'             => 'Port SMTP',
    'emailSmtpEncryption'       => 'Szyfrowanie',
    'emailSmtpEncryptionNone'   => 'Brak',
    'emailSmtpUsername'         => 'Nazwa użytkownika SMTP',
    'emailSmtpPassword'         => 'Hasło SMTP',
    'emailProvider'             => 'Dostawca e-maili',
    'emailProviderCore'         => 'Główny (domyślny)',
    'emailProviderHelp'         => 'Wybierz, która wtyczka obsługuje dostarczanie wychodzących e-maili.',
    'emailSaveBtn'              => 'Zapisz ustawienia e-mail',
    'emailSettingsSaved'        => 'Ustawienia e-mail zapisane.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Logowanie przez media społecznościowe (OAuth)',
    'socialLoginHelp'           => 'Dane uwierzytelniające są zapisywane w pliku .env. Zarejestruj aplikację w Google i Facebook, aby uzyskać identyfikatory klientów i klucze tajne.',
    'socialLoginGoogleId'       => 'ID klienta',
    'socialLoginGoogleSecret'   => 'Klucz tajny klienta',
    'socialLoginFbAppId'        => 'ID aplikacji',
    'socialLoginFbAppSecret'    => 'Klucz tajny aplikacji',
    'socialLoginPlaceholderSecret' => '(pozostaw puste, aby zachować istniejący)',
    'socialLoginSaveBtn'        => 'Zapisz ustawienia logowania społecznościowego',
    'socialLoginSettingsSaved'  => 'Ustawienia logowania społecznościowego zapisane.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Automatyczne udostępnianie w mediach społecznościowych przy publikacji',
    'socialSharingHelp'         => 'Gdy wpis zostanie opublikowany z zaznaczoną opcją „Udostępnij przy publikacji", Pubvana automatycznie opublikuje go na skonfigurowanych kontach społecznościowych.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Pobierz klucze na developer.twitter.com → Twoja aplikacja → Klucze i tokeny.',
    'socialSharingApiKey'       => 'Klucz API',
    'socialSharingApiSecret'    => 'Klucz tajny API',
    'socialSharingAccessToken'  => 'Token dostępu',
    'socialSharingAccessSecret' => 'Klucz tajny dostępu',
    'socialSharingFbPage'       => 'Strona Facebook',
    'socialSharingFbPageHelp'   => 'Wymaga tokenu dostępu do strony z uprawnieniem pages_manage_posts.',
    'socialSharingFbPageId'     => 'ID strony',
    'socialSharingFbPageToken'  => 'Token dostępu do strony',
    'socialSharingSaveBtn'      => 'Zapisz ustawienia udostępniania',
    'socialSharingSettingsSaved'=> 'Ustawienia udostępniania społecznościowego zapisane.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Ochrona przed spamem (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana używa hCaptcha (szanującej prywatność, nie-Google) do ochrony formularzy komentarzy i formularza kontaktowego przed spambotami.',
    'spamHcaptchaFree'          => 'hCaptcha jest darmowe dla większości witryn. Zarejestruj się na hcaptcha.com, a następnie: Account → Sites → Add Site aby uzyskać klucz witryny i Account → Settings → Secret Key → Generate aby uzyskać klucz tajny. Wprowadź oba poniżej.',
    'spamHcaptchaSiteKey'       => 'Klucz witryny',
    'spamHcaptchaSecretKey'     => 'Klucz tajny',
    'spamHcaptchaNote'          => 'Jeśli te klucze nie są ustawione, hCaptcha jest po cichu pomijane — bezpieczne dla lokalnego środowiska deweloperskiego. Po zapisaniu widżet automatycznie pojawia się w formularzu komentarzy i na stronie kontaktowej.',
    'spamSettingsSaved'         => 'Ustawienia ochrony przed spamem zapisane.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Języki',
    'languageCode'              => 'Kod',
    'languageName'              => 'Nazwa',
    'languageDefault'           => 'Domyślny',
    'languageEnabled'           => 'Włączony',
    'languageMakeDefault'       => 'Ustaw jako domyślny',
    'languageSetAsDefault'      => '{0} ustawiony jako domyślny język.',
    'languageEnabled_msg'       => '{0} włączony.',
    'languageDisabled_msg'      => '{0} wyłączony.',
    'languageNotFound'          => 'Język nie został znaleziony.',
    'languageCannotDisable'     => 'Nie można wyłączyć domyślnego języka.',
    'languageDirection'         => 'Kierunek',
    'languageNativeName'        => 'Nazwa rodzima',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analityka',
    'analyticsTotalViews'       => 'Łączna liczba wyświetleń',
    'analyticsTopPosts'         => 'Najpopularniejsze wpisy',
    'analyticsReferrers'        => 'Najpopularniejsze źródła',
    'analyticsLast7'            => 'Ostatnie 7 dni',
    'analyticsLast30'           => 'Ostatnie 30 dni',
    'analyticsLast90'           => 'Ostatnie 90 dni',
    'analyticsChartTitle'       => 'Wyświetlenia stron',
    'analyticsNoData'           => 'Brak danych analitycznych dla tego okresu.',
    'analyticsDomain'           => 'Domena',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Linki afiliacyjne',
    'newAffiliateLinkTitle'     => 'Nowy link afiliacyjny',
    'editAffiliateLinkTitle'    => 'Edytuj link afiliacyjny',
    'affiliateName'             => 'Nazwa',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Docelowy URL',
    'affiliateActive'           => 'Aktywny',
    'affiliateClicks'           => 'Kliknięcia',
    'affiliateClicksTitle'      => 'Kliknięcia - {0}',
    'affiliateTotal'            => 'Łącznie',
    'affiliateViewClicks'       => 'Zobacz kliknięcia',

    // Affiliate flash messages
    'affiliateCreated'          => 'Link afiliacyjny utworzony.',
    'affiliateUpdated'          => 'Link afiliacyjny zaktualizowany.',
    'affiliateDeleted'          => 'Link afiliacyjny usunięty.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Uszkodzone linki',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'Status HTTP',
    'brokenLinkError'           => 'Błąd',
    'brokenLinkSource'          => 'Źródło',
    'brokenLinkShowDismissed'   => 'Pokaż odrzucone',
    'brokenLinkHideDismissed'   => 'Ukryj odrzucone',
    'brokenLinkTimeout'         => 'Przekroczenie czasu',
    'brokenLinkBroken'          => 'uszkodzony',
    'brokenLinkNone'            => 'Nie wykryto uszkodzonych linków.',
    'brokenLinkNowReachable'    => 'Link jest teraz osiągalny - usunięty z wyników.',
    'brokenLinkStillBroken'     => 'Link nadal uszkodzony ({0}).',
    'brokenLinkDismissed'       => 'Link odrzucony.',
    'brokenLinksCliHint'        => 'Uruchom pełne skanowanie z wiersza poleceń, aby wypełnić ten raport: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => 'Znaleziono {0} problem(y)',
    'brokenLinksCount'          => '{0} uszkodzone',
    'brokenLinksRecheck'        => 'Sprawdź ponownie ten URL',
    'brokenLinksDismiss'        => 'Odrzuć (ukryj z wyników)',
    'brokenLinksRunScan'        => 'Uruchom skanowanie',
    'brokenLinksScanComplete'   => 'Skanowanie zakończone: sprawdzono {0} linków, uszkodzonych {1}.',
    'timeout'                   => 'Przekroczenie czasu',
    'typePost'                  => 'Wpis',
    'typePage'                  => 'Strona',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Dziennik aktywności',
    'activityLogType'           => 'Typ',
    'activityLogAction'         => 'Akcja',
    'activityLogUser'           => 'Użytkownik',
    'activityLogDate'           => 'Data',
    'activityLogNote'           => 'Notatka',
    'activityLogFilterAll'      => 'Wszystkie typy',
    'activityLogEmpty'          => 'Brak zarejestrowanej aktywności.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Kopia zapasowa i eksport',
    'backupDownload'            => 'Utwórz i pobierz kopię zapasową',
    'backupFiles'               => 'Dostępne kopie zapasowe',
    'backupFilename'            => 'Nazwa pliku',
    'backupSize'                => 'Rozmiar',
    'backupDate'                => 'Utworzona',
    'backupGenerating'          => 'Generowanie kopii zapasowej…',
    'backupNoFiles'             => 'Brak zapisanych kopii zapasowych.',
    'backupFailed'              => 'Tworzenie kopii zapasowej nie powiodło się: {0}',
    'backupDeleted'             => 'Kopia zapasowa usunięta.',
    'backupCannotDelete'        => 'Nie można usunąć kopii zapasowej.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'Adresy IP są przechowywane jako skróty SHA-256 — nie są zapisywane żadne surowe dane osobowe.',
    'colTime'                   => 'Czas',
    'colIpHash'                 => 'Skrót IP',
    'colReferrer'               => 'Źródło odesłania',
    'affiliateDirectReferrer'   => 'Bezpośrednie',
    'affiliateNameHint'         => 'Etykieta wewnętrzna — niewidoczna dla odwiedzających.',
    'affiliateSlugHint'         => 'Tylko litery, cyfry, myślniki i podkreślenia. Nie można zmienić po udostępnieniu linków.',
    'affiliateDestHint'         => 'Musi zawierać https://. Odwiedzający zostaną przekierowani 301 tutaj.',
    'affiliateInactiveHint'     => 'Nieaktywne linki zwracają 404.',
    'affiliateLinkCount'        => '{0} linków',
    'colDomain'                 => 'Domena',
    'commentAll'                => 'Wszystkie',
    'commentPending'            => 'Oczekujące',
    'commentTrash'              => 'Kosz',
    'commentsNone'              => 'Brak {0} komentarzy.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Utwórz kopię zapasową',
    'backupStarting'            => 'Rozpoczynanie tworzenia kopii zapasowej...',
    'backupNoneYet'             => 'Brak kopii zapasowych. Kliknij „Utwórz kopię zapasową", aby utworzyć pierwszą.',
    'backupsTitle'              => 'Kopie zapasowe',
    'backupRetentionNote'       => 'Przechowywanych jest maksymalnie 15 kopii zapasowych — najstarsze są automatycznie usuwane.',
    'backupRestoreConfirm'      => 'Przywrócić tę kopię zapasową? Najpierw zostanie utworzona kopia zapasowa bieżącego stanu.',
    'backupDeleteConfirm'       => 'Usunąć tę kopię zapasową?',
    'colFilename'               => 'Nazwa pliku',
    'colVersion'                => 'Wersja',
    'colTrigger'                => 'Wyzwalacz',
    'colSize'                   => 'Rozmiar',
    'colDate'                   => 'Data',
    'colActions'                => 'Akcje',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Importuj',
    'importWpHeading'           => 'Importuj z WordPress',
    'importWpHelp'              => 'Wyeksportuj witrynę WordPress przez Narzędzia → Eksport, a następnie prześlij plik .xml poniżej.',
    'importChooseFile'          => 'Wybierz plik WXR (.xml)',
    'importDryRun'              => 'Próbny przebieg (tylko podgląd - nic nie jest zapisywane)',
    'importRunBtn'              => 'Uruchom import',
    'importNoValidFile'         => 'Prześlij prawidłowy plik eksportu WordPress WXR.',
    'importOnlyXml'             => 'Akceptowane są tylko pliki .xml.',
    'importFileTooLarge'        => 'Plik importu jest zbyt duży. Maksymalny rozmiar to 50 MB.',
    'importResultsHeading'      => 'Wyniki importu',
    'importDryRunNote'          => 'Próbny przebieg - żadne dane nie zostały zapisane.',
    'importDryRunLabel'         => '(Próbny przebieg — żadne dane nie zostały zapisane)',
    'importComplete'            => 'Import zakończony',
    'importCreated'             => 'utworzono',
    'importSkipped'             => 'pominięto',
    'importErrors'              => 'Błędy:',
    'importInstructions'        => 'Wyeksportuj zawartość WordPress z <strong>Narzędzia → Eksport → Cała zawartość</strong> i prześlij plik <code>.xml</code> tutaj. Pubvana zaimportuje wpisy, strony, kategorie, tagi, autorów i komentarze.',
    'importCliTitle'            => 'Import CLI',
    'importCliHint'             => 'Możesz również uruchomić importer z wiersza poleceń:',
    'importCliDryRunHint'       => 'Flaga <code>--dry-run</code> pokazuje, co zostałoby zaimportowane bez zapisywania do bazy danych.',
    'importWhatTitle'           => 'Co jest importowane',
    'importItemPosts'           => 'Wpisy (tytuł, treść, wstęp, slug, status)',
    'importItemPages'           => 'Strony',
    'importItemCategories'      => 'Kategorie (z hierarchią)',
    'importItemTags'            => 'Tagi',
    'importItemAuthors'         => 'Autorzy (utworzeni jako konta subskrybentów)',
    'importItemComments'        => 'Komentarze',
    'importItemMedia'           => 'Pliki mediów (URL zachowane w treści)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Aktualizacje',
    'updatesCurrentVersion'     => 'Bieżąca wersja',
    'updatesLatestVersion'      => 'Najnowsza wersja',
    'updatesUpToDate'           => 'Pubvana jest aktualna.',
    'updatesAvailable'          => 'Dostępna aktualizacja: {0}',
    'updatesCheckBtn'           => 'Sprawdź aktualizacje',
    'updatesReleaseNotes'       => 'Informacje o wersji',
    'updatesHowToApply'         => 'Jak zastosować aktualizację',
    'updatesCacheCleared'       => 'Pamięć podręczna aktualizacji wyczyszczona - sprawdzanie ponownie.',
    'updatesExtCapped'          => 'Dostępna aktualizacja: {0} (bezpieczna dla dodatków)',
    'updatesNewerAvailable'     => 'Dostępna jest również Pubvana {0} - zaktualizuj poniższe dodatki, aby ją odblokować.',

    // Addon Updates
    'updatesExtTitle'               => 'Dodatki',
    'updatesExtCheckAll'            => 'Sprawdź wszystkie',
    'updatesExtUpdateAll'           => 'Zaktualizuj wszystkie',
    'updatesExtCheckAllType'        => 'Sprawdź wszystkie {0}',
    'updatesExtUpdateAllType'       => 'Zaktualizuj wszystkie {0}',
    'updatesExtNoInstalled'         => 'Brak zainstalowanych {0}.',
    'updatesExtColName'             => 'Nazwa',
    'updatesExtColVersion'          => 'Wersja',
    'updatesExtColLatest'           => 'Najnowsza',
    'updatesExtColAutoUpdate'       => 'Auto-aktualizacja',
    'updatesExtColStatus'           => 'Status',
    'updatesExtColActions'          => 'Akcje',
    'updatesExtBundled'             => 'Dołączony do rdzenia',
    'updatesExtNoSource'            => 'Brak źródła aktualizacji',
    'updatesExtFailed'              => 'Nie powiodło się',
    'updatesExtUpdatedAt'           => 'Zaktualizowano {0}',
    'updatesExtAvailable'           => 'Dostępna aktualizacja',
    'updatesExtUpToDate'            => 'Aktualna',
    'updatesExtUpdate'              => 'Aktualizuj',
    'updatesExtChecking'            => 'Sprawdzanie...',
    'updatesExtUpdating'            => 'Aktualizowanie...',
    'updatesExtUpdated'             => 'Zaktualizowano',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Potwierdź aktualizację',
    'updatesConfirmBody'            => 'Spowoduje to utworzenie kopii zapasowej witryny, pobranie aktualizacji i jej zastosowanie.',
    'updatesConfirmSafe'            => 'Twoje pliki <code>.env</code>, <code>App.php</code> i <code>Database.php</code> nigdy nie są nadpisywane.',
    'updatesConfirmBtn'             => 'Aktualizuj teraz',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Zaktualizuj wszystkie dodatki',
    'updatesExtAllBody'             => 'Spowoduje to aktualizację wszystkich dodatków, dla których dostępne są aktualizacje.',
    'updatesExtAllNote'             => 'Dodatki z wyłączoną auto-aktualizacją również zostaną zaktualizowane.',
    'updatesExtAllBtn'              => 'Zaktualizuj wszystkie',

    'updatesExtBadge'               => 'Aktualizacja: v{0}',
    'updatesExtGoToUpdates'         => 'Aktualizacje',

    // Update Settings
    'updatesSettingsTitle'          => 'Ustawienia aktualizacji',
    'updatesAutoUpdateLabel'        => 'Automatyczna aktualizacja Pubvana',
    'updatesAutoUpdateManual'       => 'Ręczna',
    'updatesAutoUpdateAuto'         => 'Automatyczna',
    'updatesAutoUpdateHelp'         => 'Gdy włączone, aktualizacje Pubvana bez przełomowych zmian są stosowane automatycznie.',
    'updatesCheckMethodLabel'       => 'Metoda sprawdzania aktualizacji',
    'updatesCheckMethodPageload'    => 'Ładowanie strony',
    'updatesCheckMethodCron'        => 'Zadanie Cron',
    'updatesCheckMethodHelp'        => 'Ładowanie strony sprawdza przy każdym żądaniu (buforowane 24h). Cron wymaga zadania cron serwera.',
    'updatesCronCommand'            => 'Polecenie Cron',
    'updatesCronHelp'               => 'Dodaj to do crontab serwera, aby uruchamiać sprawdzanie aktualizacji codziennie:',
    'updatesSettingsSaved'          => 'Ustawienia aktualizacji zapisane.',

    // Compatibility
    'compatWarningTitle'            => 'Ostrzeżenie o zgodności',
    'compatNotCompatible'           => 'Niektóre zainstalowane dodatki nie są zgodne z tą wersją.',
    'compatRequiresUpdate'          => 'ale wymaga najpierw zaktualizowania następujących dodatków:',
    'compatSupportsUpTo'            => 'obsługuje do {0}',
    'compatRequiresMin'             => 'wymaga Pubvana {0}+',
    'compatNotDeclared'             => 'Następujące dodatki nie zadeklarowały zgodności z Pubvana {0}. Mogą przestać działać po aktualizacji:',
    'compatColType'                 => 'Typ',
    'compatColName'                 => 'Nazwa',
    'compatColVersion'              => 'Zgodność',
    'compatRemoveHint'              => 'Możesz usunąć niezgodne dodatki lub przełączyć się na domyślny motyw w razie problemów. Przed każdą aktualizacją tworzona jest kopia zapasowa.',
    'compatMaxVersion'              => 'Maksymalna zgodna wersja: {0}',
    'compatMinVersion'              => 'Wymaga Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Harmonogram wpisów',
    'scheduleNoScheduled'       => 'Brak zaplanowanych wpisów.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Wersje - {0}',
    'revisionPageTitle'         => 'Wersja - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Musisz być zalogowany, aby uzyskać dostęp do panelu administracyjnego.',
    'dirNotWritable'            => 'Katalog nie jest zapisywalny: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} jest nieprawidłowo skonfigurowany. Jeśli jesteś użytkownikiem końcowym, skontaktuj się z deweloperem. Jeśli jesteś deweloperem, zapoznaj się z dokumentacją.',
    'addonMisconfiguredLink'    => '{0} jest nieprawidłowo skonfigurowany. Jeśli jesteś użytkownikiem końcowym, <a href="{1}">skontaktuj się z deweloperem</a>. Jeśli jesteś deweloperem, <a href="https://github.com/enlivenapp/pubvana">zapoznaj się z dokumentacją</a>.',
    'licenseExpiringSoon'       => 'Licencja dla {0} wygasa {1}. {0} zostanie dezaktywowany po wygaśnięciu licencji.',
    'licenseExpiredDeactivated' => '{0} został dezaktywowany z powodu wygaśnięcia licencji.',
    'addonDeactivated'          => '{0} został dezaktywowany. Powód: {1}.',
    'widgetValidationFailed'    => 'Widżet \'\'{0}\'\' nie mógł zostać zweryfikowany. Skontaktuj się z deweloperem lub usuń dodatek.',
    'widgetValidationFailedLink' => 'Widżet \'\'{0}\'\' nie mógł zostać zweryfikowany. <a href="{1}">Skontaktuj się z deweloperem</a> lub usuń dodatek.',

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Dezaktywowany: licencja wygasła',
    'addonDeactivatedTampered'  => 'Dezaktywowany: nieprawidłowo skonfigurowany',
    'addonDeactivatedNoLicense' => 'Dezaktywowany: brak ważnej licencji',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Wyłączony',
    'addonDisabledInvalidJson'  => 'System: {0} ma nieprawidłowy lub nieczytelny {1}.',
    'addonDisabledMissingFields' => 'System: {0} brakuje wymaganych pól: {1}.',
    'addonDisabledPhpFiles'     => 'System: {0} zawiera pliki PHP. Widżety muszą zawierać tylko JSON i szablony.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'Do aktywacji {0} wymagana jest ważna licencja.',
    'licenseInvalidActivation'  => 'Weryfikacja licencji nie powiodła się dla {0}. Sprawdź klucz licencji.',
    'licenseExpiredActivation'  => 'Licencja dla {0} wygasła. Odnów, aby aktywować.',
    'licenseCheckUnreachable'   => 'Nie można zweryfikować licencji dla {0}. Serwer licencji jest niedostępny. Spróbuj ponownie później.',
    'activationBlockedTampered' => '{0} nie może zostać aktywowany, ponieważ jest nieprawidłowo skonfigurowany.',
    'activationBlockedBundled'  => '{0} nie może zostać aktywowany: tylko dodatki Pubvana mogą być oznaczone jako dołączone.',
    'activationBlockedNoUrls'   => '{0} nie może zostać aktywowany: płatne dodatki muszą zawierać URL weryfikacji licencji.',
    'activationBlockedFreeFlag' => '{0} nie może zostać aktywowany: dodatki Pubvana nie mogą być oznaczone jako darmowe.',
    'activationBlockedDisabled' => '{0} nie może zostać aktywowany z powodu błędów konfiguracji. Sprawdź plik informacyjny.',

    // Third-party license
    'licenseThirdPartyLabel'    => 'Strona trzecia',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Rozpoczynanie aktualizacji...',
    'updateCheckLabel'           => 'Sprawdzanie aktualizacji:',
    'updateAvailable'            => 'Pubvana {0} jest dostępna!',
    'updateRunning'              => 'Używasz wersji {0}.',
    'updateBreakingChanges'      => 'Przełomowe zmiany',
    'updateMigrationNotes'       => 'Uwagi dotyczące migracji',
    'updateNotices'              => 'Powiadomienia',
    'updatePreflightTitle'       => 'Kontrole wstępne',
    'updateToVersion'            => 'Zaktualizuj do Pubvana {0}',
    'updatePreflightFailed'      => 'Jedna lub więcej wymaganych kontroli wstępnych nie powiodła się. Rozwiąż je przed aktualizacją.',
    'updateUpToDate'             => 'Pubvana jest aktualna. Używasz wersji {0}.',
    'updateAnyway'               => 'Aktualizuj mimo to',
    'updateAvailableTooltip'     => 'Dostępna Pubvana {0}',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(ty)',
    'usersNone'                  => 'Nie znaleziono użytkowników.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Konto aktywne',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Szczegóły profilu',
    'profileDisplayNameHint'     => 'Wyświetlana w opublikowanych wpisach zamiast nazwy użytkownika.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP lub GIF. Maks. 10 MB.',
    'profileSocialHandles'       => 'Konta w mediach społecznościowych',
    'preview'                    => 'Podgląd',
    'website'                    => 'Strona internetowa',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Uwierzytelnianie dwuskładnikowe',
    'totpActiveDesc'             => 'Uwierzytelnianie dwuskładnikowe TOTP jest aktywne na Twoim koncie. Przy każdym logowaniu będziesz proszony o 6-cyfrowy kod z aplikacji uwierzytelniającej.',
    'totpCurrentCode'            => 'Bieżący kod',
    'totpInactiveDesc'           => 'Dodaj dodatkową warstwę zabezpieczeń do swojego konta. Po włączeniu będziesz musiał przy każdym logowaniu wprowadzać kod z aplikacji uwierzytelniającej.',
    'totpEnable'                 => 'Włącz uwierzytelnianie dwuskładnikowe',
    'totpScanInstructions'       => 'Otwórz aplikację uwierzytelniającą (Google Authenticator, Authy, 1Password itp.) i zeskanuj ten kod QR.',
    'totpManualEntry'            => 'Nie możesz zeskanować? Wprowadź ten kod ręcznie:',
    'totpConfirmInstructions'    => 'Po zeskanowaniu wprowadź 6-cyfrowy kod wyświetlany w aplikacji, aby potwierdzić konfigurację.',
    'totpRecoveryWarning'        => 'Przechowuj kody odzyskiwania. Jeśli utracisz dostęp do aplikacji uwierzytelniającej, nie będziesz mógł się zalogować. Skontaktuj się z administratorem witryny, aby zresetować 2FA.',

];
