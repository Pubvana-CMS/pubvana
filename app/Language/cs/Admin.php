<?php

/**
 * Pubvana CMS - Admin language strings (Czech)
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
    'save'              => 'Uložit',
    'saveChanges'       => 'Uložit změny',
    'cancel'            => 'Zrušit',
    'edit'              => 'Upravit',
    'delete'            => 'Smazat',
    'create'            => 'Vytvořit',
    'add'               => 'Přidat',
    'back'              => 'Zpět',
    'view'              => 'Zobrazit',
    'apply'             => 'Použít',
    'install'           => 'Instalovat',
    'update'            => 'Aktualizovat',
    'refresh'           => 'Obnovit',
    'activate'          => 'Aktivovat',
    'deactivate'        => 'Deaktivovat',
    'enable'            => 'Povolit',
    'disable'           => 'Zakázat',
    'disabled'          => 'Zakázáno',
    'approve'           => 'Schválit',
    'spam'              => 'Spam',
    'trash'             => 'Koš',
    'restore'           => 'Obnovit',
    'dismiss'           => 'Zavřít',
    'recheck'           => 'Znovu zkontrolovat',
    'clickToCopy'       => 'Kliknutím zkopírovat',
    'download'          => 'Stáhnout',
    'upload'            => 'Nahrát',
    'import'            => 'Importovat',
    'export'            => 'Exportovat',
    'publish'           => 'Publikovat',
    'unpublish'         => 'Zrušit publikování',
    'logout'            => 'Odhlásit se',
    'viewSite'          => 'Zobrazit web',
    'newPost'           => 'Nový příspěvek',
    'buyNow'            => 'Koupit nyní',
    'visitStore'        => 'Navštívit obchod',
    'loadMore'          => 'Načíst více',

    // Table headers / labels
    'title'             => 'Název',
    'name'              => 'Jméno',
    'slug'              => 'Slug',
    'status'            => 'Stav',
    'date'              => 'Datum',
    'actions'           => 'Akce',
    'author'            => 'Autor',
    'views'             => 'Zobrazení',
    'type'              => 'Typ',
    'url'               => 'URL',
    'description'       => 'Popis',
    'role'              => 'Role',
    'email'             => 'E-mail',
    'username'          => 'Uživatelské jméno',
    'active'            => 'Aktivní',
    'version'           => 'Verze',
    'size'              => 'Velikost',
    'clicks'            => 'Kliknutí',
    'total'             => 'Celkem',
    'platform'          => 'Platforma',
    'label'             => 'Popis',
    'order'             => 'Pořadí',
    'source'            => 'Zdroj',
    'content'           => 'Obsah',
    'excerpt'           => 'Výňatek',
    'details'           => 'Podrobnosti',
    'contentType'       => 'Typ obsahu',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta název',
    'metaDescription'   => 'Meta popis',

    // Status badges
    'published'         => 'Publikováno',
    'draft'             => 'Koncept',
    'scheduled'         => 'Naplánováno',
    'pending'           => 'Čeká na schválení',
    'safe'              => 'Bezpečný',
    'notSafe'           => 'Nebezpečný',
    'malicious'         => 'Škodlivý',
    'safetyUnknown'     => 'Neznámý',
    'inactive'          => 'Neaktivní',
    'installed'         => 'Nainstalováno',
    'free'              => 'Zdarma',
    'premium'           => 'Prémiový',
    'all'               => 'Vše',

    // Confirmations
    'confirmDelete'         => 'Opravdu chcete smazat tuto položku?',
    'confirmDeletePost'     => 'Smazat tento příspěvek?',
    'confirmDeletePage'     => 'Smazat tuto stránku?',
    'confirmDeleteComment'  => 'Trvale smazat tento komentář?',
    'confirmDeleteUser'     => 'Smazat tohoto uživatele?',
    'confirmDeleteMedia'    => 'Smazat?',
    'confirmDeleteBackup'   => 'Smazat tento záložní soubor?',
    'confirmBulkAction'     => 'Použít hromadnou akci na vybrané příspěvky?',

    // Empty states
    'noPostsYet'        => 'Zatím žádné příspěvky. {0}',
    'noResultsFound'    => 'Nebyly nalezeny žádné výsledky.',
    'noCommentsYet'     => 'Žádné komentáře čekající na schválení.',
    'noMediaYet'        => 'Zatím žádná média.',
    'noItemsFound'      => 'Na tržišti nebyly nalezeny žádné položky.',
    'noCategoriesYet'   => 'Zatím žádné kategorie.',
    'noTagsYet'         => 'Zatím žádné štítky.',
    'noRevisionsYet'    => 'Nebyly nalezeny žádné revize.',

    // Misc common
    'permissionDenied'  => 'Přístup odepřen.',
    'notFound'          => 'Záznam nenalezen.',
    'commasSeparated'   => 'Odděleno čárkami',
    'optional'          => 'Volitelné',
    'required'          => 'Povinné',
    'enabled'           => 'Povoleno',
    'selected'          => '{0} příspěvek/příspěvků vybráno',
    'published_count'   => '{0} publikováno',
    'pending_count'     => '{0} čeká',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Přehled',
    'navContent'        => 'Obsah',
    'navAppearance'     => 'Vzhled',
    'navUsersAndSite'   => 'Uživatelé a web',
    'navTools'          => 'Nástroje',
    'navMarketplace'    => 'Tržiště',
    'navPlugins'        => 'Pluginy',
    'navPosts'          => 'Příspěvky',
    'navSchedule'       => 'Plán',
    'navPages'          => 'Stránky',
    'navCategories'     => 'Kategorie',
    'navTags'           => 'Štítky',
    'navComments'       => 'Komentáře',
    'navMedia'          => 'Média',
    'navImport'         => 'Import',
    'navThemes'         => 'Šablony',
    'navWidgets'        => 'Widgety',
    'navNavigation'     => 'Navigace',
    'navUsers'          => 'Uživatelé',
    'navSocialLinks'    => 'Sociální sítě',
    'navRedirects'      => 'Přesměrování',
    'navLanguages'      => 'Jazyky',
    'navSettings'       => 'Nastavení',
    'navAnalytics'      => 'Analytika',
    'navAffiliates'     => 'Affiliate odkazy',
    'navBrokenLinks'    => 'Nefunkční odkazy',
    'navActivityLog'    => 'Protokol aktivit',
    'navBackup'         => 'Záloha a export',
    'navUpdates'        => 'Aktualizace',
    'navBrowse'         => 'Procházet',
    'navLicenses'       => 'Licence',
    'navPubvanaStore'   => 'Obchod Pubvana',
    'navUpdateAvailable'=> 'Dostupná aktualizace',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Chcete se odhlásit?',
    'logoutModalBody'   => 'Kliknutím na „Odhlásit se" níže ukončíte svou relaci.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Přehled',
    'dashStats'             => 'Statistiky',
    'dashPosts'             => 'Příspěvky',
    'dashPages'             => 'Stránky',
    'dashComments'          => 'Komentáře',
    'dashUsers'             => 'Uživatelé',
    'dashRecentPosts'       => 'Nedávné příspěvky',
    'dashPendingComments'   => 'Komentáře čekající na schválení',
    'dashViewAll'           => 'Zobrazit vše',
    'dashCreateOne'         => 'Vytvořit jeden!',
    'dashNoPosts'           => 'Zatím žádné příspěvky.',
    'dashNoPendingComments' => 'Žádné komentáře čekající na schválení.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Příspěvky',
    'newPostTitle'          => 'Nový příspěvek',
    'editPostTitle'         => 'Upravit příspěvek: {0}',
    'copyPreviewLink'       => 'Zkopírovat odkaz na náhled',
    'backToPosts'           => 'Zpět na příspěvky',
    'postTitleField'        => 'Název *',
    'postEditor'            => 'Editor',
    'postHtmlEditor'        => 'HTML editor',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Výňatek',
    'postExcerptPlaceholder'=> 'Volitelné krátké shrnutí...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta název',
    'postMetaDescription'   => 'Meta popis',
    'postPublishSection'    => 'Publikovat',
    'postStatus'            => 'Stav',
    'postStatusDraft'       => 'Koncept',
    'postStatusPublished'   => 'Publikováno',
    'postStatusScheduled'   => 'Naplánováno',
    'postScheduledAt'       => 'Plánované datum a čas',
    'postFeatured'          => 'Hlavní příspěvek',
    'postMembersOnly'       => 'Pouze pro členy',
    'postShareOnPublish'    => 'Sdílet na sociálních sítích při publikování',
    'postSaveBtn'           => 'Uložit příspěvek',
    'postFeaturedImage'     => 'Hlavní obrázek',
    'postFeaturedImagePlaceholder' => 'URL nebo cesta k nahrání…',
    'postCategories'        => 'Kategorie',
    'postTags'              => 'Štítky',
    'postTagsPlaceholder'   => 'štítek1, štítek2, štítek3',
    'postRevisions'         => 'Revize',
    'postRevisionCount'     => '{0} revize',
    'postPreview'           => 'Náhled',
    'postBulkAction'        => '- Vybrat akci -',
    'postBulkPublish'       => 'Publikovat',
    'postBulkUnpublish'     => 'Zrušit publikování (nastavit jako koncept)',
    'postBulkDelete'        => 'Smazat',

    // Post flash messages
    'postCreated'           => 'Příspěvek byl úspěšně vytvořen.',
    'postUpdated'           => 'Příspěvek byl aktualizován.',
    'scheduledDateMustBeFuture' => 'Plánované datum musí být v budoucnosti.',
    'postDeleted'           => 'Příspěvek byl smazán.',
    'postBulkUpdated'       => '{0} příspěvek/příspěvků aktualizováno.',
    'postBulkInvalid'       => 'Neplatná hromadná akce.',
    'postPermission'        => 'Můžete upravovat pouze své vlastní příspěvky.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revize: {0}',
    'revisionTitle'         => 'Revize — {0}',
    'revisionShowTitle'     => 'Revize',
    'revisionsBackToPost'   => 'Zpět na příspěvek',
    'revisionsBackToList'   => 'Zpět na seznam revizí',
    'revisionRestored'      => 'Příspěvek byl obnoven na revizi z {0}.',
    'revisionRestoreBtn'    => 'Obnovit tuto revizi',
    'revisionSaved'         => 'Uloženo',
    'revisionBy'            => 'Autor',
    'revisionOn'            => 'Datum',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Stránky',
    'newPageTitle'          => 'Nová stránka',
    'editPageTitle'         => 'Upravit stránku',
    'pageSlugInUse'         => "Slug '{0}' je již používán.",
    'pageCannotDelete'      => 'Tuto stránku nelze smazat.',
    'slugAutoGenHint'       => 'automaticky generováno z názvu, pokud je ponecháno prázdné',
    'slugCannotChange'      => 'nelze změnit',
    'colSystem'             => 'Systém',
    'system'                => 'Systém',

    // Page flash messages
    'pageCreated'           => 'Stránka byla vytvořena.',
    'pageUpdated'           => 'Stránka byla aktualizována.',
    'pageDeleted'           => 'Stránka byla smazána.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Kategorie',
    'newCategoryTitle'      => 'Nová kategorie',
    'editCategoryTitle'     => 'Upravit kategorii',
    'categoryName'          => 'Název',
    'categoryDescription'   => 'Popis',
    'categoryPostCount'     => 'Počet příspěvků',

    // Category flash messages
    'categoryCreated'       => 'Kategorie byla vytvořena.',
    'categoryUpdated'       => 'Kategorie byla aktualizována.',
    'categoryDeleted'       => 'Kategorie byla smazána.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Štítky',
    'tagPostCount'          => 'Počet příspěvků',

    // Tag flash messages
    'tagDeleted'            => 'Štítek byl smazán.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Komentáře',
    'commentAuthor'         => 'Autor',
    'commentContent'        => 'Komentář',
    'commentPost'           => 'Příspěvek',
    'commentDate'           => 'Datum',
    'commentStatusFilter'   => 'Filtrovat podle stavu',

    // Comment flash messages
    'commentApproved'       => 'Komentář byl schválen.',
    'commentSpam'           => 'Označeno jako spam.',
    'commentTrashed'        => 'Komentář přesunut do koše.',
    'commentDeleted'        => 'Komentář byl trvale smazán.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Knihovna médií',
    'mediaTitle'            => 'Název',
    'mediaAltText'          => 'Alternativní text',
    'mediaAltPlaceholder'   => 'Popište obrázek pro přístupnost',
    'mediaTitlePlaceholder' => 'Volitelný název obrázku',
    'mediaImageDetails'     => 'Podrobnosti obrázku',
    'mediaSaved'            => 'Uloženo!',
    'mediaNoSelection'      => 'Není vybrán žádný obrázek',
    'mediaBrowse'           => 'Procházet média',
    'mediaRemove'           => 'Odebrat',
    'mediaUseImage'         => 'Použít tento obrázek',
    'mediaDropzone'         => 'Přetáhněte obrázek sem nebo klikněte pro procházení',
    'mediaLoading'          => 'Načítání médií…',
    'mediaEmpty'            => 'Zatím nebyla nahrána žádná média.',
    'mediaUpload'           => 'Nahrát média',
    'mediaDragDrop'         => 'Přetáhněte soubory sem nebo',
    'mediaChooseFiles'      => 'Vybrat soubory',
    'mediaUploading'        => 'Nahrávání…',
    'mediaFilename'         => 'Název souboru',
    'mediaSize'             => 'Velikost',
    'mediaUploadFailed'     => 'Nahrávání selhalo: {0}',
    'mediaUploadError'      => 'Chyba nahrávání: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Médium bylo smazáno.',
    'mediaNoValidFile'      => 'Nebyl nahrán žádný platný soubor.',
    'mediaUploadSuccess'    => 'Soubor byl úspěšně nahrán.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navigace',
    'navQuickAdd'           => 'Rychlé přidání',
    'navQuickAddPlaceholder' => 'Hledat stránky, kategorie, pluginy...',
    'navItemLabel'          => 'Popis',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Cíl',
    'navItemOrder'          => 'Pořadí řazení',
    'navGroupPrimary'       => 'Primární',
    'navGroupFooter'        => 'Zápatí',
    'navSelectGroup'        => 'Vybrat navigační skupinu:',
    'navParent'             => 'Nadřazená položka',
    'navTopLevel'           => '— Nejvyšší úroveň —',
    'navSameWindow'         => 'Stejné okno',
    'navNewWindow'          => 'Nové okno',
    'navMenuItems'          => 'Položky menu',
    'navNoItems'            => 'V tomto menu nejsou žádné položky.',
    'dragToReorder'         => 'Přetáhněte pro přeřazení',

    // Navigation flash messages
    'navItemAdded'          => 'Navigační položka přidána.',
    'navItemRemoved'        => 'Navigační položka odebrána.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Šablony',
    'themeOptions'          => 'Možnosti šablony',
    'themeActivate'         => 'Aktivovat',
    'themeOptionsBtn'       => 'Možnosti',
    'themeActive'           => 'Aktivní',
    'themeBy'               => 'Autor',
    'themeSupport'          => 'Podpora',
    'themeVersion'          => 'Verze',
    'themeSaveOptions'      => 'Uložit možnosti',
    'themeInvalidLicense'   => 'Šablonu nelze aktivovat – licence je neplatná. Přeinstalujte nebo kontaktujte podporu.',
    'themeValidationFailed' => 'Šablona obsahuje PHP kód a nelze ji aktivovat.',
    'noThemesInstalled'     => 'Nejsou nainstalovány žádné šablony. Navštivte Tržiště pro získání šablon.',
    'themeUnapprovedTitle'  => 'Aktivovat neschválenou šablonu?',
    'themeNotApproved'      => 'Tato šablona nebyla schválena Pubvana.',
    'themeUnapprovedRisk'   => 'Aktivace neschválených šablon může zavést bezpečnostní rizika nebo problémy s kompatibilitou.',
    'themeActivateConfirm'  => 'Opravdu ji chcete přesto aktivovat?',
    'themeActivateAnyway'   => 'Přesto aktivovat',
    'themeNoOptions'        => 'Tato šablona nemá žádné konfigurovatelné možnosti.',
    'themeCustomize'        => 'Přizpůsobit šablonu',

    // Theme flash messages
    'themeActivated'        => 'Šablona byla aktivována.',
    'themeOptionsSaved'     => 'Možnosti byly uloženy.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Licencováno',
    'licenseCheckNow'        => 'Zkontrolovat nyní',
    'licenseExpired'         => 'Vypršela',
    'licenseEnterKey'        => 'Zadat klíč',
    'licenseChangeKey'       => 'Změnit',
    'licenseRenew'           => 'Obnovit',
    'licenseThirdParty'      => 'Třetí strana',
    'unchecked'              => 'Neověřeno',
    'safetyLabel'            => 'Bezpečnost:',
    'recheckBtn'             => 'Znovu ověřit',
    'recheckSuccess'         => 'Kontrola bezpečnosti aktualizována.',
    'recheckFailed'          => 'Nepodařilo se kontaktovat ověřovací server. Zkuste to prosím později.',
    'recheckNotFound'        => 'Položka nenalezena.',
    'securityWarning'        => 'Bezpečnostní varování:',
    'licenseModalTitle'      => 'Zadat licenční klíč',
    'licenseModalBody'       => 'Vložte svůj licenční klíč níže.',
    'licenseModalSave'       => 'Uložit',
    'licenseSaved'           => 'Licenční klíč byl uložen a ověřen.',
    'licenseInvalid'         => 'Licenční klíč není platný.',
    'licenseKeyRequired'     => 'Licenční klíč a produkt jsou povinné.',
    'licenseCheckFailed'     => 'Nelze se připojit k licenčnímu serveru. Zkuste to prosím později.',
    'licenseProductNotFound' => 'Tuto položku se v obchodě nepodařilo najít.',
    'btnCancel'              => 'Zrušit',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widgety',
    'widgetConfigureTitle'  => 'Konfigurovat widget',
    'widgetAreas'           => 'Oblasti widgetů',
    'widgetAvailable'       => 'Dostupné widgety',
    'widgetAddToArea'       => 'Přidat do oblasti',
    'widgetArea'            => 'Oblast',
    'widgetNoOptions'       => 'Žádné možnosti.',
    'widgetSaveConfig'      => 'Uložit konfiguraci',
    'widgetConfigure'       => 'Konfigurovat',
    'widgetNoAreas'         => 'Nebyly nalezeny žádné oblasti widgetů. Aktivujte šablonu pro povolení oblastí widgetů.',
    'widgetAreaEmpty'       => 'V této oblasti nejsou žádné widgety. Přidejte jeden ze seznamu →',

    // Widget flash messages
    'widgetAdded'           => 'Widget byl přidán.',
    'widgetRemoved'         => 'Widget byl odebrán.',
    'widgetConfigured'      => 'Widget byl nakonfigurován.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Tržiště',
    'marketplaceRefresh'    => 'Obnovit',
    'marketplaceVisitStore' => 'Navštívit obchod',
    'marketplaceAll'        => 'Vše',
    'marketplaceThemes'     => 'Šablony',
    'marketplaceWidgets'    => 'Widgety',
    'marketplacePlugins'    => 'Pluginy',
    'marketplaceUpdatesAvailable' => '{0} dostupná aktualizace/aktualizací.',
    'marketplaceBy'         => 'Autor',
    'marketplaceFree'       => 'Zdarma',
    'marketplaceInstalled'  => 'Nainstalováno',
    'marketplaceInstall'    => 'Instalovat',
    'marketplaceBuyNow'     => 'Koupit nyní',
    'marketplaceNoItems'    => 'Na tržišti nebyly nalezeny žádné položky.',
    'marketplaceInstalledVersion' => 'v{0} nainstalováno',
    'marketplaceLoadError'  => 'Produkty z obchodu se nepodařilo načíst. Zkuste to prosím později.',
    'byAuthor'              => 'Autor: {0}',
    'unknown'               => 'Neznámý',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} bylo úspěšně nainstalováno.',
    'marketplaceInstallFail'    => 'Instalace selhala. Zkontrolujte protokoly.',
    'marketplaceUpdateSuccess'  => 'Úspěšně aktualizováno.',
    'marketplaceUpdateFail'     => 'Aktualizace selhala.',
    'marketplaceCacheRefreshed' => 'Mezipaměť tržiště byla obnovena.',
    'marketplaceInvalidRequest' => 'Neplatná žádost o instalaci.',
    'marketplaceCannotUpdate'   => 'Tuto položku nelze aktualizovat.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licence',
    'licensesNone'                => 'Žádné licence',
    'licensesProduct'             => 'Produkt',
    'licensesKey'                 => 'Licenční klíč',
    'licensesStatus'              => 'Stav',
    'licensesType'                => 'Typ',
    'licensesExpires'             => 'Vyprší',
    'licensesDomain'              => 'Doména',
    'licensesInstalled'           => 'Nainstalováno',
    'licensesLastChecked'         => 'Naposledy zkontrolováno',
    'licensesActions'             => 'Akce',
    'licensesStatusValid'         => 'Platná',
    'licensesStatusInvalid'       => 'Neplatná',
    'licensesStatusExpired'       => 'Vypršela',
    'licensesStatusSubExpired'    => 'Předplatné vypršelo',
    'licensesStatusUnchecked'     => 'Neověřeno',
    'licensesSubscription'        => 'Předplatné',
    'licensesOneTime'             => 'Jednorázový',
    'licensesPerpetual'           => 'Trvalý',
    'licensesNotInstalled'        => 'Není nainstalováno',
    'licensesNever'               => 'Nikdy',
    'licensesRevalidate'          => 'Znovu ověřit',
    'licenseKeyPlaceholder'       => 'Zadat licenční klíč...',
    'marketplaceLicensesEmpty'    => 'Licencované produkty se zde zobrazí po instalaci.',
    'typeTheme'                   => 'Šablona',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Plugin',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Licence byla úspěšně ověřena.',
    'licenseRevalidateInvalid'     => 'Licence je neplatná nebo vypršela.',
    'licenseRevalidateUnreachable' => 'Nelze se připojit k licenčnímu serveru. Zkuste to prosím později.',
    'licenseRevalidateSkipped'     => 'Kontrola licence byla přeskočena (vývojový režim).',
    'licenseRevalidateNotFound'    => 'Licence nebyla nalezena.',

    // License warning banners
    'licenseWarningTitle'   => 'Problémy s licencí',
    'licenseWarningInvalid' => 'licence je neplatná nebo vypršela',
    'licenseWarningManage'  => 'Spravovat licence',

    // Plugin license
    'pluginInvalidLicense' => 'Tento plugin má neplatnou nebo vypršelou licenci a nelze ho aktivovat.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Licenční klíč',
    'storeBrowseFull'       => 'Procházet celý obchod',
    'storeBackToMarketplace'=> 'Zpět na tržiště',
    'storeNoProducts'       => 'Nejsou k dispozici žádné produkty.',
    'storeViewInStore'      => 'Zobrazit v obchodě',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Uživatelé',
    'editUserTitle'         => 'Upravit uživatele',
    'createUserTitle'       => 'Vytvořit uživatele',
    'authorProfileTitle'    => 'Profil autora',
    'userRoleLabel'         => 'Role',
    'userActiveLabel'       => 'Aktivní',
    'userPasswordLabel'     => 'Heslo',
    'userPasswordOptional'  => 'Ponechte prázdné pro zachování aktuálního hesla',
    'userDisplayName'       => 'Zobrazované jméno',
    'userBio'               => 'Životopis',
    'userWebsite'           => 'Webová stránka',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Uložit profil',
    'userSaveChanges'       => 'Uložit změny',
    'userCannotDeleteSelf'  => 'Nemůžete smazat sami sebe.',
    'userCannotDeleteOwner' => 'Účet vlastníka webu nelze smazat.',
    'userOwnerCannotModify' => 'Účet vlastníka webu nelze upravit.',

    // User flash messages
    'userCreated'           => 'Uživatel byl vytvořen.',
    'userUpdated'           => 'Uživatel byl aktualizován.',
    'userDeleted'           => 'Uživatel byl smazán.',
    'userBanned'            => 'Uživatel byl zablokován.',
    'userUnbanned'          => 'Uživatel byl odblokován.',
    'userCannotBanSelf'     => 'Nemůžete zablokovat sami sebe ani vlastníka webu.',
    'banStatus'             => 'Stav zablokování',
    'banned'                => 'Zablokován',
    'ban'                   => 'Zablokovat uživatele',
    'unban'                 => 'Odblokovat',
    'banReasonRequired'     => 'Důvod zablokování je povinný.',
    'banReasonPlaceholder'  => 'Důvod zablokování...',
    'confirmBanUser'        => 'Opravdu chcete zablokovat tohoto uživatele?',
    'userProfileSaved'      => 'Profil byl uložen.',
    'userAvatarUploadFail'  => 'Nahrávání avatara selhalo: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Nastavení 2FA',
    'tfaSetupHeading'       => 'Nastavit dvoufaktorové ověřování',
    'tfaScanQr'             => 'Naskenujte níže uvedený QR kód svou ověřovací aplikací (např. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Nebo zadejte tajný klíč ručně:',
    'tfaEnterCode'          => 'Zadejte 6místný kód z aplikace pro potvrzení:',
    'tfaCodeLabel'          => 'Ověřovací kód',
    'tfaConfirmBtn'         => 'Potvrdit a povolit 2FA',
    'tfaDisableBtn'         => 'Zakázat 2FA',
    'tfaDisableConfirm'     => 'Zadejte aktuální 2FA kód pro zakázání:',
    'tfaEnabled'            => 'Dvoufaktorové ověřování bylo povoleno.',
    'tfaDisabled'           => 'Dvoufaktorové ověřování bylo zakázáno.',
    'tfaInvalidCode'        => 'Neplatný kód – naskenujte prosím znovu QR kód a zkuste to ještě jednou.',
    'tfaInvalidDisable'     => 'Neplatný kód – 2FA nebylo zakázáno.',
    'tfaSessionExpired'     => 'Relace nastavení vypršela – začněte prosím znovu.',
    'tfaNotEnabled'         => '2FA není aktuálně povoleno.',
    'tfaCantScan'           => 'Nemůžete naskenovat? Zadejte tento kód ručně:',
    'tfaWarning'            => 'Uložte tento tajný klíč na bezpečné místo. Budete ho potřebovat k obnovení přístupu, pokud ztratíte ověřovací zařízení.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Sociální sítě',
    'socialPlatform'           => 'Platforma',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Ikona',
    'socialSortOrder'          => 'Pořadí řazení',
    'socialIconPackInfo'       => 'Aktuální šablona <strong>{0}</strong> používá <strong>{1}</strong> (v{2}) pro ikony. Níže můžete vybrat dostupné ikony, které se zobrazí pro funkci sociálních sítí tohoto webu.',
    'socialSearchPlaceholder'  => 'Hledat platformy...',
    'socialIconDisclaimer'     => "Tyto ikony jsou pouze reprezentací použité ikony. Skutečná ikona se může lišit v závislosti na sadě ikon aktivní šablony.",

    // Social flash messages
    'socialLinkAdded'       => 'Odkaz na sociální síť přidán.',
    'socialLinkUpdated'     => 'Odkaz byl aktualizován.',
    'socialLinkDeleted'     => 'Odkaz byl smazán.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Přesměrování',
    'redirectFrom'          => 'Z URL',
    'redirectTo'            => 'Na URL',
    'redirectType'          => 'Typ',
    'redirectAdd'           => 'Přidat přesměrování',
    'redirectFromHint'      => '(relativní, např. /stara-stranka)',
    'redirect301'           => '301 Trvalé',
    'redirect302'           => '302 Dočasné',
    'redirectInvalidDest'   => 'Neplatná cílová URL přesměrování.',

    // Redirect flash messages
    'redirectAdded'         => 'Přesměrování přidáno.',
    'redirectDeleted'       => 'Přesměrování smazáno.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Nastavení',
    'settingsGeneral'       => 'Obecné',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'E-mail',
    'settingsSocialLogin'   => 'Sociální přihlášení',
    'settingsSocialSharing' => 'Sdílení na sociálních sítích',
    'settingsSpam'          => 'Ochrana proti spamu',

    'generalSettingsHeading'    => 'Obecná nastavení',
    'generalSiteName'           => 'Název webu',
    'generalTagline'            => 'Slogan',
    'generalAdminEmail'         => 'E-mail administrátora',
    'generalPostsPerPage'       => 'Příspěvků na stránku',
    'generalComments'           => 'Komentáře',
    'generalCommentsEnable'     => 'Povolit komentáře',
    'generalCommentModeration'  => 'Vyžadovat moderování před publikováním',
    'generalMaintenanceMode'    => 'Režim údržby',
    'generalMaintenanceEnable'  => 'Povolit režim údržby',
    'generalMaintenanceHelp'    => 'Návštěvníci uvidí stránku „Brzy budeme zpět". Administrátoři mají stále přístup.',
    'generalFrontPage'          => 'Úvodní stránka',
    'generalFrontPageBlog'      => 'Index blogu (nejnovější příspěvky)',
    'generalFrontPageStatic'    => 'Statická stránka:',
    'generalFrontPagePlugin'    => 'Stránka pluginu:',
    'generalSelectPage'         => '- Vybrat stránku -',
    'generalSelectRoute'        => '- Vybrat trasu -',
    'generalFrontPageNoPlugins' => 'Žádné trasy pluginů nejsou k dispozici',
    'generalPageCacheTtl'       => 'TTL mezipaměti stránek',
    'settingsCacheTtlHint'      => 'Sekundy. 0 = zakázáno.',
    'generalSaveBtn'            => 'Uložit obecná nastavení',

    // General flash messages
    'generalSettingsSaved'      => 'Obecná nastavení byla uložena.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'Nastavení SEO',
    'seoMetaDescription'        => 'Meta popis',
    'seoGoogleAnalytics'        => 'ID Google Analytics',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Mapa webu',
    'seoSitemapEnable'          => 'Povolit sitemap.xml',
    'seoSitemapHelp'            => 'Standardní mapa webu pro všechny publikované příspěvky a stránky.',
    'seoNewsSitemap'            => 'Povolit news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Mapa webu Google News – zobrazuje příspěvky publikované za posledních 48 hodin.',
    'seoSaveBtn'                => 'Uložit nastavení SEO',
    'seoSettingsSaved'          => 'Nastavení SEO bylo uloženo.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Nastavení e-mailu',
    'emailFromName'             => 'Jméno odesílatele',
    'emailFromAddress'          => 'Adresa odesílatele',
    'emailProtocol'             => 'Protokol',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP host',
    'emailSmtpPort'             => 'SMTP port',
    'emailSmtpEncryption'       => 'Šifrování',
    'emailSmtpEncryptionNone'   => 'Žádné',
    'emailSmtpUsername'         => 'SMTP uživatelské jméno',
    'emailSmtpPassword'         => 'SMTP heslo',
    'emailSaveBtn'              => 'Uložit nastavení e-mailu',
    'emailSettingsSaved'        => 'Nastavení e-mailu bylo uloženo.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Sociální přihlášení (OAuth)',
    'socialLoginHelp'           => 'Přihlašovací údaje jsou uloženy do souboru .env. Zaregistrujte svou aplikaci u Googlu a Facebooku pro získání ID klientů a tajných klíčů.',
    'socialLoginGoogleId'       => 'ID klienta',
    'socialLoginGoogleSecret'   => 'Tajný klíč klienta',
    'socialLoginFbAppId'        => 'ID aplikace',
    'socialLoginFbAppSecret'    => 'Tajný klíč aplikace',
    'socialLoginPlaceholderSecret' => '(ponechte prázdné pro zachování stávajícího)',
    'socialLoginSaveBtn'        => 'Uložit nastavení sociálního přihlášení',
    'socialLoginSettingsSaved'  => 'Nastavení sociálního přihlášení bylo uloženo.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Automatické sdílení na sociálních sítích při publikování',
    'socialSharingHelp'         => 'Když je příspěvek publikován se zaškrtnutým „Sdílet při publikování", Pubvana automaticky zveřejní na nakonfigurovaných sociálních účtech.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Klíče získáte na developer.twitter.com → Vaše aplikace → Klíče a tokeny.',
    'socialSharingApiKey'       => 'API klíč',
    'socialSharingApiSecret'    => 'API tajný klíč',
    'socialSharingAccessToken'  => 'Přístupový token',
    'socialSharingAccessSecret' => 'Tajný přístupový klíč',
    'socialSharingFbPage'       => 'Stránka Facebook',
    'socialSharingFbPageHelp'   => 'Vyžaduje přístupový token stránky s oprávněním pages_manage_posts.',
    'socialSharingFbPageId'     => 'ID stránky',
    'socialSharingFbPageToken'  => 'Přístupový token stránky',
    'socialSharingSaveBtn'      => 'Uložit nastavení sdílení',
    'socialSharingSettingsSaved'=> 'Nastavení sdílení na sociálních sítích bylo uloženo.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Ochrana proti spamu (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana používá hCaptcha (respektující soukromí, ne Google) k ochraně formulářů komentářů a kontaktního formuláře před spamboty.',
    'spamHcaptchaFree'          => 'hCaptcha je pro většinu webů zdarma. Zaregistrujte se na hcaptcha.com, vytvořte web a zadejte klíče níže.',
    'spamHcaptchaSiteKey'       => 'Klíč webu',
    'spamHcaptchaSecretKey'     => 'Tajný klíč',
    'spamHcaptchaNote'          => 'Pokud tyto klíče nejsou nastaveny, hCaptcha je tiše přeskočena – bezpečné pro lokální vývoj. Po uložení se widget automaticky zobrazí na formuláři komentářů a kontaktní stránce.',
    'spamSettingsSaved'         => 'Nastavení ochrany proti spamu bylo uloženo.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Jazyky',
    'languageCode'              => 'Kód',
    'languageName'              => 'Název',
    'languageDefault'           => 'Výchozí',
    'languageEnabled'           => 'Povoleno',
    'languageMakeDefault'       => 'Nastavit jako výchozí',
    'languageSetAsDefault'      => '{0} nastaven jako výchozí jazyk.',
    'languageEnabled_msg'       => '{0} povoleno.',
    'languageDisabled_msg'      => '{0} zakázáno.',
    'languageNotFound'          => 'Jazyk nenalezen.',
    'languageCannotDisable'     => 'Výchozí jazyk nelze zakázat.',
    'languageDirection'         => 'Směr',
    'languageNativeName'        => 'Nativní název',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analytika',
    'analyticsTotalViews'       => 'Celkové zobrazení',
    'analyticsTopPosts'         => 'Nejčtenější příspěvky',
    'analyticsReferrers'        => 'Nejčastější zdroje',
    'analyticsLast7'            => 'Posledních 7 dní',
    'analyticsLast30'           => 'Posledních 30 dní',
    'analyticsLast90'           => 'Posledních 90 dní',
    'analyticsChartTitle'       => 'Zobrazení stránek',
    'analyticsNoData'           => 'Žádná analytická data pro toto období.',
    'analyticsDomain'           => 'Doména',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Affiliate odkazy',
    'newAffiliateLinkTitle'     => 'Nový affiliate odkaz',
    'editAffiliateLinkTitle'    => 'Upravit affiliate odkaz',
    'affiliateName'             => 'Název',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Cílová URL',
    'affiliateActive'           => 'Aktivní',
    'affiliateClicks'           => 'Kliknutí',
    'affiliateClicksTitle'      => 'Kliknutí - {0}',
    'affiliateTotal'            => 'Celkem',
    'affiliateViewClicks'       => 'Zobrazit kliknutí',

    // Affiliate flash messages
    'affiliateCreated'          => 'Affiliate odkaz byl vytvořen.',
    'affiliateUpdated'          => 'Affiliate odkaz byl aktualizován.',
    'affiliateDeleted'          => 'Affiliate odkaz byl smazán.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Nefunkční odkazy',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP stav',
    'brokenLinkError'           => 'Chyba',
    'brokenLinkSource'          => 'Zdroj',
    'brokenLinkShowDismissed'   => 'Zobrazit skryté',
    'brokenLinkHideDismissed'   => 'Skrýt skryté',
    'brokenLinkTimeout'         => 'Časový limit',
    'brokenLinkBroken'          => 'nefunkční',
    'brokenLinkNone'            => 'Nebyly nalezeny žádné nefunkční odkazy.',
    'brokenLinkNowReachable'    => 'Odkaz je nyní dostupný – odstraněn z výsledků.',
    'brokenLinkStillBroken'     => 'Odkaz je stále nefunkční ({0}).',
    'brokenLinkDismissed'       => 'Odkaz byl skryt.',
    'brokenLinksCliHint'        => 'Spusťte úplné skenování z příkazové řádky pro naplnění tohoto reportu: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} problém/problémů nalezeno',
    'brokenLinksCount'          => '{0} nefunkční',
    'brokenLinksRecheck'        => 'Znovu zkontrolovat tuto URL',
    'brokenLinksDismiss'        => 'Skrýt (schovat z výsledků)',
    'brokenLinksRunScan'        => 'Spustit skenování',
    'brokenLinksScanComplete'   => 'Skenování dokončeno: {0} odkazů zkontrolováno, {1} nefunkčních.',
    'timeout'                   => 'Časový limit',
    'typePost'                  => 'Příspěvek',
    'typePage'                  => 'Stránka',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Protokol aktivit',
    'activityLogType'           => 'Typ',
    'activityLogAction'         => 'Akce',
    'activityLogUser'           => 'Uživatel',
    'activityLogDate'           => 'Datum',
    'activityLogNote'           => 'Poznámka',
    'activityLogFilterAll'      => 'Všechny typy',
    'activityLogEmpty'          => 'Zatím nejsou zaznamenány žádné aktivity.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Záloha a export',
    'backupDownload'            => 'Vytvořit a stáhnout zálohu',
    'backupFiles'               => 'Dostupné zálohy',
    'backupFilename'            => 'Název souboru',
    'backupSize'                => 'Velikost',
    'backupDate'                => 'Vytvořeno',
    'backupGenerating'          => 'Vytváření zálohy…',
    'backupNoFiles'             => 'Žádné uložené zálohy.',
    'backupFailed'              => 'Záloha selhala: {0}',
    'backupDeleted'             => 'Záloha byla smazána.',
    'backupCannotDelete'        => 'Zálohu se nepodařilo smazat.',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP adresy jsou uloženy jako SHA-256 hashe – nejsou zaznamenávány žádné nezpracované osobní údaje.',
    'colTime'                   => 'Čas',
    'colIpHash'                 => 'Hash IP',
    'colReferrer'               => 'Zdroj',
    'affiliateDirectReferrer'   => 'Přímý',
    'affiliateNameHint'         => 'Interní označení – návštěvníkům se nezobrazuje.',
    'affiliateSlugHint'         => 'Pouze písmena, čísla, pomlčky a podtržítka. Po sdílení odkazů nelze změnit.',
    'affiliateDestHint'         => 'Musí obsahovat https://. Návštěvníci budou přesměrováni 301 sem.',
    'affiliateInactiveHint'     => 'Neaktivní odkazy vrátí 404.',
    'affiliateLinkCount'        => '{0} odkazů',
    'colDomain'                 => 'Doména',
    'commentAll'                => 'Vše',
    'commentPending'            => 'Čeká na schválení',
    'commentTrash'              => 'Koš',
    'commentsNone'              => 'Žádné {0} komentáře.',

    'backupCreate'              => 'Vytvořit zálohu',
    'backupStarting'            => 'Zahájení zálohy...',
    'backupNoneYet'             => 'Zatím žádné zálohy. Klikněte na „Vytvořit zálohu" pro vytvoření první.',
    'backupsTitle'              => 'Zálohy',
    'backupRetentionNote'       => 'Uchováváno maximálně 15 záloh – nejstarší jsou automaticky odstraňovány.',
    'backupRestoreConfirm'      => 'Obnovit tuto zálohu? Nejprve bude vytvořena záloha aktuálního stavu.',
    'backupDeleteConfirm'       => 'Smazat tuto zálohu?',
    'colFilename'               => 'Název souboru',
    'colVersion'                => 'Verze',
    'colTrigger'                => 'Spouštěč',
    'colSize'                   => 'Velikost',
    'colDate'                   => 'Datum',
    'colActions'                => 'Akce',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Import',
    'importWpHeading'           => 'Import z WordPressu',
    'importWpHelp'              => 'Exportujte svůj web WordPress přes Nástroje → Export a nahrajte soubor .xml níže.',
    'importChooseFile'          => 'Vybrat soubor WXR (.xml)',
    'importDryRun'              => 'Testovací spuštění (pouze náhled – nic se neukládá)',
    'importRunBtn'              => 'Spustit import',
    'importNoValidFile'         => 'Nahrajte prosím platný exportní soubor WordPress WXR.',
    'importOnlyXml'             => 'Jsou přijímány pouze soubory .xml.',
    'importFileTooLarge'        => 'Importní soubor je příliš velký. Maximální velikost je 50 MB.',
    'importResultsHeading'      => 'Výsledky importu',
    'importDryRunNote'          => 'Testovací spuštění – žádná data nebyla uložena.',
    'importDryRunLabel'         => '(Testovací spuštění — žádná data nezapsána)',
    'importComplete'            => 'Import dokončen',
    'importCreated'             => 'vytvořeno',
    'importSkipped'             => 'přeskočeno',
    'importErrors'              => 'Chyby:',
    'importInstructions'        => 'Exportujte obsah WordPressu z <strong>Nástroje → Export → Veškerý obsah</strong> a nahrajte soubor <code>.xml</code> zde. Pubvana naimportuje příspěvky, stránky, kategorie, štítky, autory a komentáře.',
    'importCliTitle'            => 'Import přes CLI',
    'importCliHint'             => 'Importer lze spustit také z příkazové řádky:',
    'importCliDryRunHint'       => 'Příznak <code>--dry-run</code> zobrazí, co by bylo importováno, aniž by zapisoval do databáze.',
    'importWhatTitle'           => 'Co se importuje',
    'importItemPosts'           => 'Příspěvky (název, obsah, výňatek, slug, stav)',
    'importItemPages'           => 'Stránky',
    'importItemCategories'      => 'Kategorie (s hierarchií)',
    'importItemTags'            => 'Štítky',
    'importItemAuthors'         => 'Autoři (vytvořeni jako účty předplatitelů)',
    'importItemComments'        => 'Komentáře',
    'importItemMedia'           => 'Soubory médií (URL zachovány v obsahu)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Aktualizace',
    'updatesCurrentVersion'     => 'Aktuální verze',
    'updatesLatestVersion'      => 'Nejnovější verze',
    'updatesUpToDate'           => 'Pubvana je aktuální.',
    'updatesAvailable'          => 'Dostupná aktualizace: {0}',
    'updatesCheckBtn'           => 'Zkontrolovat aktualizace',
    'updatesReleaseNotes'       => 'Poznámky k vydání',
    'updatesHowToApply'         => 'Jak použít aktualizaci',
    'updatesCacheCleared'       => 'Mezipaměť aktualizací vymazána – nyní se znovu kontroluje.',
    'updatesExtCapped'          => 'Dostupná aktualizace: {0} (bezpečná pro addony)',
    'updatesNewerAvailable'     => 'Pubvana {0} je také dostupná – aktualizujte níže uvedené addony pro její odemknutí.',

    // Addon Updates
    'updatesExtTitle'               => 'Addony',
    'updatesExtCheckAll'            => 'Zkontrolovat vše',
    'updatesExtUpdateAll'           => 'Aktualizovat vše',
    'updatesExtCheckAllType'        => 'Zkontrolovat vše {0}',
    'updatesExtUpdateAllType'       => 'Aktualizovat vše {0}',
    'updatesExtNoInstalled'         => 'Žádné {0} nainstalovány.',
    'updatesExtColName'             => 'Název',
    'updatesExtColVersion'          => 'Verze',
    'updatesExtColLatest'           => 'Nejnovější',
    'updatesExtColAutoUpdate'       => 'Automatická aktualizace',
    'updatesExtColStatus'           => 'Stav',
    'updatesExtColActions'          => 'Akce',
    'updatesExtBundled'             => 'Součást jádra',
    'updatesExtNoSource'            => 'Žádný zdroj aktualizací',
    'updatesExtFailed'              => 'Selhalo',
    'updatesExtUpdatedAt'           => 'Aktualizováno {0}',
    'updatesExtAvailable'           => 'Dostupná aktualizace',
    'updatesExtUpToDate'            => 'Aktuální',
    'updatesExtUpdate'              => 'Aktualizovat',
    'updatesExtChecking'            => 'Kontroluji...',
    'updatesExtUpdating'            => 'Aktualizuji...',
    'updatesExtUpdated'             => 'Aktualizováno',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Potvrdit aktualizaci',
    'updatesConfirmBody'            => 'Toto zazálohuje váš web, stáhne aktualizaci a použije ji.',
    'updatesConfirmSafe'            => 'Vaše soubory <code>.env</code>, <code>App.php</code> a <code>Database.php</code> nebudou nikdy přepsány.',
    'updatesConfirmBtn'             => 'Aktualizovat nyní',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Aktualizovat všechny addony',
    'updatesExtAllBody'             => 'Toto aktualizuje všechny addony s čekajícími aktualizacemi.',
    'updatesExtAllNote'             => 'Addony s vypnutou automatickou aktualizací budou také aktualizovány.',
    'updatesExtAllBtn'              => 'Aktualizovat vše',

    'updatesExtBadge'               => 'Aktualizace: v{0}',
    'updatesExtGoToUpdates'         => 'Aktualizace',

    // Update Settings
    'updatesSettingsTitle'          => 'Nastavení aktualizací',
    'updatesAutoUpdateLabel'        => 'Automatická aktualizace Pubvana',
    'updatesAutoUpdateManual'       => 'Ručně',
    'updatesAutoUpdateAuto'         => 'Automaticky',
    'updatesAutoUpdateHelp'         => 'Pokud je povoleno, aktualizace Pubvana bez zásadních změn jsou aplikovány automaticky.',
    'updatesCheckMethodLabel'       => 'Metoda kontroly aktualizací',
    'updatesCheckMethodPageload'    => 'Načtení stránky',
    'updatesCheckMethodCron'        => 'Cron úloha',
    'updatesCheckMethodHelp'        => 'Načtení stránky kontroluje při každém požadavku (uloženo do mezipaměti na 24 h). Cron vyžaduje serverovou cron úlohu.',
    'updatesCronCommand'            => 'Příkaz cron',
    'updatesCronHelp'               => 'Přidejte toto do crontab serveru pro denní spouštění kontroly aktualizací:',
    'updatesSettingsSaved'          => 'Nastavení aktualizací bylo uloženo.',

    // Compatibility
    'compatWarningTitle'            => 'Varování o kompatibilitě',
    'compatNotCompatible'           => 'Některé nainstalované addony nejsou kompatibilní s touto verzí.',
    'compatRequiresUpdate'          => 'ale vyžaduje nejprve aktualizaci následujících addonů:',
    'compatSupportsUpTo'            => 'podporuje až {0}',
    'compatRequiresMin'             => 'vyžaduje Pubvana {0}+',
    'compatNotDeclared'             => 'Následující addony nedeklarovaly kompatibilitu s Pubvana {0}. Po aktualizaci mohou přestat fungovat:',
    'compatColType'                 => 'Typ',
    'compatColName'                 => 'Název',
    'compatColVersion'              => 'Kompatibilita',
    'compatRemoveHint'              => 'Nekompatibilní addony můžete odebrat nebo přepnout na výchozí šablonu, pokud nastanou problémy. Před každou aktualizací je vytvořena záloha.',
    'compatMaxVersion'              => 'Maximální kompatibilní verze: {0}',
    'compatMinVersion'              => 'Vyžaduje Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Plán příspěvků',
    'scheduleNoScheduled'       => 'Žádné naplánované příspěvky.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revize - {0}',
    'revisionPageTitle'         => 'Revize - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Pro přístup do administrace musíte být přihlášeni.',
    'dirNotWritable'            => 'Adresář není zapisovatelný: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} je nesprávně nakonfigurován. Pokud jste koncový uživatel, kontaktujte vývojáře. Pokud jste vývojář, prostudujte dokumentaci.',
    'addonMisconfiguredLink'    => '{0} je nesprávně nakonfigurován. Pokud jste koncový uživatel, <a href="{1}">kontaktujte vývojáře</a>. Pokud jste vývojář, <a href="https://github.com/enlivenapp/pubvana">prostudujte dokumentaci</a>.',
    'licenseExpiringSoon'       => 'Licence pro {0} vyprší dne {1}. {0} bude deaktivováno po vypršení licence.',
    'licenseExpiredDeactivated' => '{0} bylo deaktivováno, protože licence vypršela.',
    'addonDeactivated'          => '{0} bylo deaktivováno. Důvod: {1}.',
    'widgetValidationFailed'    => "Widget ''{0}'' nebylo možné ověřit. Kontaktujte vývojáře nebo odeberte addon.",
    'widgetValidationFailedLink' => "Widget ''{0}'' nebylo možné ověřit. <a href=\"{1}\">Kontaktujte vývojáře</a> nebo odeberte addon.",

    'addonDeactivatedExpired'   => 'Deaktivováno: licence vypršela',
    'addonDeactivatedTampered'  => 'Deaktivováno: nesprávně nakonfigurováno',
    'addonDeactivatedNoLicense' => 'Deaktivováno: žádná platná licence',

    'addonDisabled'             => 'Zakázáno',
    'addonDisabledInvalidJson'  => 'Systém: {0} má neplatný nebo nečitelný {1}.',
    'addonDisabledMissingFields' => 'Systém: {0} chybí povinná pole: {1}.',
    'addonDisabledPhpFiles'     => 'Systém: {0} obsahuje PHP soubory. Widgety mohou obsahovat pouze JSON + šablony.',

    'licenseRequired'           => 'K aktivaci {0} je vyžadována platná licence.',
    'licenseInvalidActivation'  => 'Ověření licence pro {0} selhalo. Zkontrolujte prosím svůj licenční klíč.',
    'licenseExpiredActivation'  => 'Licence pro {0} vypršela. Obnovte ji prosím pro aktivaci.',
    'licenseCheckUnreachable'   => 'Nelze ověřit licenci pro {0}. Licenční server není dostupný. Zkuste to prosím později.',
    'activationBlockedTampered' => '{0} nelze aktivovat, protože je nesprávně nakonfigurováno.',
    'activationBlockedBundled'  => '{0} nelze aktivovat: jako součást jádra mohou být označeny pouze addony Pubvana.',
    'activationBlockedNoUrls'   => '{0} nelze aktivovat: placené addony musí obsahovat URL pro ověření licence.',
    'activationBlockedFreeFlag' => '{0} nelze aktivovat: addony Pubvana nemohou být označeny jako zdarma.',
    'activationBlockedDisabled' => '{0} nelze aktivovat, protože má chyby konfigurace. Zkontrolujte informační soubor.',

    'licenseThirdPartyLabel'    => 'Třetí strana',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Spouštění aktualizace...',
    'updateCheckLabel'           => 'Kontrola aktualizací:',
    'updateAvailable'            => 'Pubvana {0} je dostupná!',
    'updateRunning'              => 'Používáte verzi {0}.',
    'updateBreakingChanges'      => 'Zásadní změny',
    'updateMigrationNotes'       => 'Poznámky k migraci',
    'updateNotices'              => 'Upozornění',
    'updatePreflightTitle'       => 'Předletové kontroly',
    'updateToVersion'            => 'Aktualizovat na Pubvana {0}',
    'updatePreflightFailed'      => 'Jedna nebo více požadovaných předletových kontrol selhala. Před aktualizací je prosím vyřešte.',
    'updateUpToDate'             => 'Pubvana je aktuální. Používáte verzi {0}.',
    'updateAnyway'               => 'Přesto aktualizovat',
    'updateAvailableTooltip'     => 'Pubvana {0} dostupná',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(vy)',
    'usersNone'                  => 'Nebyli nalezeni žádní uživatelé.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Účet aktivní',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Podrobnosti profilu',
    'profileDisplayNameHint'     => 'Zobrazuje se na publikovaných příspěvcích místo uživatelského jména.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP nebo GIF. Max 10 MB.',
    'profileSocialHandles'       => 'Sociální sítě',
    'preview'                    => 'Náhled',
    'website'                    => 'Webová stránka',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Dvoufaktorové ověřování',
    'totpActiveDesc'             => 'Na vašem účtu je aktivní dvoufaktorové ověřování TOTP. Při každém přihlášení budete požádáni o 6místný kód z ověřovací aplikace.',
    'totpCurrentCode'            => 'Aktuální kód',
    'totpInactiveDesc'           => 'Přidejte ke svému účtu další vrstvu zabezpečení. Po aktivaci budete při každém přihlášení potřebovat zadat kód z ověřovací aplikace.',
    'totpEnable'                 => 'Povolit dvoufaktorové ověřování',
    'totpScanInstructions'       => 'Otevřete ověřovací aplikaci (Google Authenticator, Authy, 1Password atd.) a naskenujte tento QR kód.',
    'totpManualEntry'            => 'Nemůžete naskenovat? Zadejte tento kód ručně:',
    'totpConfirmInstructions'    => 'Po naskenování zadejte 6místný kód zobrazený v aplikaci pro potvrzení nastavení.',
    'totpRecoveryWarning'        => 'Uložte si záložní kódy. Pokud ztratíte přístup k ověřovací aplikaci, nebudete se moci přihlásit. Kontaktujte správce webu pro resetování 2FA.',

];
