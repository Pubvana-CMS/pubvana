<?php

/**
 * Pubvana CMS - Admin language strings (Dutch)
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
    'save'              => 'Opslaan',
    'saveChanges'       => 'Wijzigingen opslaan',
    'cancel'            => 'Annuleren',
    'edit'              => 'Bewerken',
    'delete'            => 'Verwijderen',
    'create'            => 'Aanmaken',
    'add'               => 'Toevoegen',
    'back'              => 'Terug',
    'view'              => 'Bekijken',
    'apply'             => 'Toepassen',
    'install'           => 'Installeren',
    'update'            => 'Bijwerken',
    'refresh'           => 'Vernieuwen',
    'activate'          => 'Activeren',
    'deactivate'        => 'Deactiveren',
    'enable'            => 'Inschakelen',
    'disable'           => 'Uitschakelen',
    'disabled'          => 'Uitgeschakeld',
    'approve'           => 'Goedkeuren',
    'spam'              => 'Spam',
    'trash'             => 'Prullenbak',
    'restore'           => 'Herstellen',
    'dismiss'           => 'Sluiten',
    'recheck'           => 'Opnieuw controleren',
    'clickToCopy'       => 'Klikken om te kopiëren',
    'download'          => 'Downloaden',
    'upload'            => 'Uploaden',
    'import'            => 'Importeren',
    'export'            => 'Exporteren',
    'publish'           => 'Publiceren',
    'unpublish'         => 'Publicatie intrekken',
    'logout'            => 'Uitloggen',
    'viewSite'          => 'Site bekijken',
    'newPost'           => 'Nieuw bericht',
    'buyNow'            => 'Nu kopen',
    'visitStore'        => 'Winkel bezoeken',
    'loadMore'          => 'Meer laden',

    // Table headers / labels
    'title'             => 'Titel',
    'name'              => 'Naam',
    'slug'              => 'Slug',
    'status'            => 'Status',
    'date'              => 'Datum',
    'actions'           => 'Acties',
    'author'            => 'Auteur',
    'views'             => 'Weergaven',
    'type'              => 'Type',
    'url'               => 'URL',
    'description'       => 'Beschrijving',
    'role'              => 'Rol',
    'email'             => 'E-mail',
    'username'          => 'Gebruikersnaam',
    'active'            => 'Actief',
    'version'           => 'Versie',
    'size'              => 'Grootte',
    'clicks'            => 'Klikken',
    'total'             => 'Totaal',
    'platform'          => 'Platform',
    'label'             => 'Label',
    'order'             => 'Volgorde',
    'source'            => 'Bron',
    'content'           => 'Inhoud',
    'excerpt'           => 'Samenvatting',
    'details'           => 'Details',
    'contentType'       => 'Inhoudstype',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta-titel',
    'metaDescription'   => 'Meta-beschrijving',

    // Status badges
    'published'         => 'Gepubliceerd',
    'draft'             => 'Concept',
    'scheduled'         => 'Gepland',
    'pending'           => 'In behandeling',
    'safe'              => 'Veilig',
    'notSafe'           => 'Niet veilig',
    'malicious'         => 'Kwaadaardig',
    'safetyUnknown'     => 'Onbekend',
    'inactive'          => 'Inactief',
    'installed'         => 'Geïnstalleerd',
    'free'              => 'Gratis',
    'premium'           => 'Premium',
    'all'               => 'Alle',

    // Confirmations
    'confirmDelete'         => 'Weet u zeker dat u dit item wilt verwijderen?',
    'confirmDeletePost'     => 'Dit bericht verwijderen?',
    'confirmDeletePage'     => 'Deze pagina verwijderen?',
    'confirmDeleteComment'  => 'Deze reactie permanent verwijderen?',
    'confirmDeleteUser'     => 'Deze gebruiker verwijderen?',
    'confirmDeleteMedia'    => 'Verwijderen?',
    'confirmDeleteBackup'   => 'Dit back-upbestand verwijderen?',
    'confirmBulkAction'     => 'Bulkactie toepassen op geselecteerde berichten?',

    // Empty states
    'noPostsYet'        => 'Nog geen berichten. {0}',
    'noResultsFound'    => 'Geen resultaten gevonden.',
    'noCommentsYet'     => 'Geen openstaande reacties.',
    'noMediaYet'        => 'Nog geen media.',
    'noItemsFound'      => 'Geen items gevonden op de marktplaats.',
    'noCategoriesYet'   => 'Nog geen categorieën.',
    'noTagsYet'         => 'Nog geen tags.',
    'noRevisionsYet'    => 'Geen revisies gevonden.',

    // Misc common
    'permissionDenied'  => 'Toegang geweigerd.',
    'notFound'          => 'Record niet gevonden.',
    'commasSeparated'   => 'Komma-gescheiden',
    'optional'          => 'Optioneel',
    'required'          => 'Verplicht',
    'enabled'           => 'Ingeschakeld',
    'selected'          => '{0} bericht(en) geselecteerd',
    'published_count'   => '{0} gepubliceerd',
    'pending_count'     => '{0} in behandeling',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Dashboard',
    'navContent'        => 'Inhoud',
    'navAppearance'     => 'Uiterlijk',
    'navUsersAndSite'   => 'Gebruikers & site',
    'navTools'          => 'Hulpmiddelen',
    'navMarketplace'    => 'Marktplaats',
    'navPlugins'        => 'Plugins',
    'navPosts'          => 'Berichten',
    'navSchedule'       => 'Planning',
    'navPages'          => 'Pagina\'s',
    'navCategories'     => 'Categorieën',
    'navTags'           => 'Tags',
    'navComments'       => 'Reacties',
    'navMedia'          => 'Media',
    'navImport'         => 'Importeren',
    'navThemes'         => 'Thema\'s',
    'navWidgets'        => 'Widgets',
    'navNavigation'     => 'Navigatie',
    'navUsers'          => 'Gebruikers',
    'navSocialLinks'    => 'Sociale links',
    'navRedirects'      => 'Omleidingen',
    'navLanguages'      => 'Talen',
    'navSettings'       => 'Instellingen',
    'navAnalytics'      => 'Analyse',
    'navAffiliates'     => 'Affiliate-links',
    'navBrokenLinks'    => 'Kapotte links',
    'navActivityLog'    => 'Activiteitenlog',
    'navBackup'         => 'Back-up & export',
    'navUpdates'        => 'Updates',
    'navBrowse'         => 'Bladeren',
    'navLicenses'       => 'Licenties',
    'navPubvanaStore'   => 'Pubvana-winkel',
    'navUpdateAvailable'=> 'Update beschikbaar',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Klaar om te vertrekken?',
    'logoutModalBody'   => 'Selecteer "Uitloggen" hieronder om uw sessie te beëindigen.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Dashboard',
    'dashStats'             => 'Statistieken',
    'dashPosts'             => 'Berichten',
    'dashPages'             => 'Pagina\'s',
    'dashComments'          => 'Reacties',
    'dashUsers'             => 'Gebruikers',
    'dashRecentPosts'       => 'Recente berichten',
    'dashPendingComments'   => 'Openstaande reacties',
    'dashViewAll'           => 'Alles bekijken',
    'dashCreateOne'         => 'Maak er een aan!',
    'dashNoPosts'           => 'Nog geen berichten.',
    'dashNoPendingComments' => 'Geen openstaande reacties.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Berichten',
    'newPostTitle'          => 'Nieuw bericht',
    'editPostTitle'         => 'Bericht bewerken: {0}',
    'copyPreviewLink'       => 'Voorbeeldlink kopiëren',
    'backToPosts'           => 'Terug naar berichten',
    'postTitleField'        => 'Titel *',
    'postEditor'            => 'Editor',
    'postHtmlEditor'        => 'HTML-editor',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Samenvatting',
    'postExcerptPlaceholder'=> 'Optionele korte samenvatting...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta-titel',
    'postMetaDescription'   => 'Meta-beschrijving',
    'postPublishSection'    => 'Publiceren',
    'postStatus'            => 'Status',
    'postStatusDraft'       => 'Concept',
    'postStatusPublished'   => 'Gepubliceerd',
    'postStatusScheduled'   => 'Gepland',
    'postScheduledAt'       => 'Geplande datum en tijd',
    'postFeatured'          => 'Uitgelicht bericht',
    'postMembersOnly'       => 'Alleen voor leden',
    'postShareOnPublish'    => 'Delen op sociale media bij publicatie',
    'postSaveBtn'           => 'Bericht opslaan',
    'postFeaturedImage'     => 'Uitgelichte afbeelding',
    'postFeaturedImagePlaceholder' => 'URL of uploadpad…',
    'postCategories'        => 'Categorieën',
    'postTags'              => 'Tags',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'Revisies',
    'postRevisionCount'     => '{0} revisie(s)',
    'postPreview'           => 'Voorbeeld',
    'postBulkAction'        => '- Actie selecteren -',
    'postBulkPublish'       => 'Publiceren',
    'postBulkUnpublish'     => 'Publicatie intrekken (naar concept)',
    'postBulkDelete'        => 'Verwijderen',

    // Post flash messages
    'postCreated'           => 'Bericht succesvol aangemaakt.',
    'postUpdated'           => 'Bericht bijgewerkt.',
    'scheduledDateMustBeFuture' => 'Geplande datum moet in de toekomst liggen.',
    'postDeleted'           => 'Bericht verwijderd.',
    'postBulkUpdated'       => '{0} bericht(en) bijgewerkt.',
    'postBulkInvalid'       => 'Ongeldige bulkactie.',
    'postPermission'        => 'U kunt alleen uw eigen berichten bewerken.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revisies: {0}',
    'revisionTitle'         => 'Revisie — {0}',
    'revisionShowTitle'     => 'Revisie',
    'revisionsBackToPost'   => 'Terug naar bericht',
    'revisionsBackToList'   => 'Terug naar revisies',
    'revisionRestored'      => 'Bericht hersteld naar revisie van {0}.',
    'revisionRestoreBtn'    => 'Deze revisie herstellen',
    'revisionSaved'         => 'Opgeslagen',
    'revisionBy'            => 'Door',
    'revisionOn'            => 'Op',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Pagina\'s',
    'newPageTitle'          => 'Nieuwe pagina',
    'editPageTitle'         => 'Pagina bewerken',
    'pageSlugInUse'         => "Slug '{0}' is al in gebruik.",
    'pageCannotDelete'      => 'Deze pagina kan niet worden verwijderd.',
    'slugAutoGenHint'       => 'automatisch gegenereerd uit titel als leeg gelaten',
    'slugCannotChange'      => 'kan niet worden gewijzigd',
    'colSystem'             => 'Systeem',
    'system'                => 'Systeem',

    // Page flash messages
    'pageCreated'           => 'Pagina aangemaakt.',
    'pageUpdated'           => 'Pagina bijgewerkt.',
    'pageDeleted'           => 'Pagina verwijderd.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Categorieën',
    'newCategoryTitle'      => 'Nieuwe categorie',
    'editCategoryTitle'     => 'Categorie bewerken',
    'categoryName'          => 'Naam',
    'categoryDescription'   => 'Beschrijving',
    'categoryPostCount'     => 'Aantal berichten',

    // Category flash messages
    'categoryCreated'       => 'Categorie aangemaakt.',
    'categoryUpdated'       => 'Categorie bijgewerkt.',
    'categoryDeleted'       => 'Categorie verwijderd.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Tags',
    'tagPostCount'          => 'Aantal berichten',

    // Tag flash messages
    'tagDeleted'            => 'Tag verwijderd.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Reacties',
    'commentAuthor'         => 'Auteur',
    'commentContent'        => 'Reactie',
    'commentPost'           => 'Bericht',
    'commentDate'           => 'Datum',
    'commentStatusFilter'   => 'Filteren op status',

    // Comment flash messages
    'commentApproved'       => 'Reactie goedgekeurd.',
    'commentSpam'           => 'Gemarkeerd als spam.',
    'commentTrashed'        => 'Reactie naar prullenbak verplaatst.',
    'commentDeleted'        => 'Reactie permanent verwijderd.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Mediabibliotheek',
    'mediaTitle'            => 'Titel',
    'mediaAltText'          => 'Alt-tekst',
    'mediaAltPlaceholder'   => 'Beschrijf de afbeelding voor toegankelijkheid',
    'mediaTitlePlaceholder' => 'Optionele afbeeldingstitel',
    'mediaImageDetails'     => 'Afbeeldingsdetails',
    'mediaSaved'            => 'Opgeslagen!',
    'mediaNoSelection'      => 'Geen afbeelding geselecteerd',
    'mediaBrowse'           => 'Media bladeren',
    'mediaRemove'           => 'Verwijderen',
    'mediaUseImage'         => 'Deze afbeelding gebruiken',
    'mediaDropzone'         => 'Sleep afbeelding hier naartoe of klik om te bladeren',
    'mediaLoading'          => 'Media laden…',
    'mediaEmpty'            => 'Nog geen media geüpload.',
    'mediaUpload'           => 'Media uploaden',
    'mediaDragDrop'         => 'Sleep bestanden hier naartoe, of',
    'mediaChooseFiles'      => 'Bestanden kiezen',
    'mediaUploading'        => 'Uploaden…',
    'mediaFilename'         => 'Bestandsnaam',
    'mediaSize'             => 'Grootte',
    'mediaUploadFailed'     => 'Upload mislukt: {0}',
    'mediaUploadError'      => 'Uploadfout: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Media verwijderd.',
    'mediaNoValidFile'      => 'Geen geldig bestand geüpload.',
    'mediaUploadSuccess'    => 'Bestand succesvol geüpload.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navigatie',
    'navQuickAdd'           => 'Snel toevoegen',
    'navQuickAddPlaceholder' => 'Zoek pagina\'s, categorieën, plugins...',
    'navItemLabel'          => 'Label',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Doel',
    'navItemOrder'          => 'Sorteervolgorde',
    'navGroupPrimary'       => 'Primair',
    'navGroupFooter'        => 'Voettekst',
    'navSelectGroup'        => 'Selecteer navigatiegroep:',
    'navParent'             => 'Bovenliggend item',
    'navTopLevel'           => '— Hoogste niveau —',
    'navSameWindow'         => 'Zelfde venster',
    'navNewWindow'          => 'Nieuw venster',
    'navMenuItems'          => 'Menu-items',
    'navNoItems'            => 'Geen items in dit menu.',
    'dragToReorder'         => 'Slepen om te herordenen',

    // Navigation flash messages
    'navItemAdded'          => 'Navigatie-item toegevoegd.',
    'navItemRemoved'        => 'Navigatie-item verwijderd.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Thema\'s',
    'themeOptions'          => 'Thema-opties',
    'themeActivate'         => 'Activeren',
    'themeOptionsBtn'       => 'Opties',
    'themeActive'           => 'Actief',
    'themeBy'               => 'Door',
    'themeSupport'          => 'Ondersteuning',
    'themeVersion'          => 'Versie',
    'themeSaveOptions'      => 'Opties opslaan',
    'themeInvalidLicense'   => 'Kan thema niet activeren - licentie is ongeldig. Herinstalleer of neem contact op met ondersteuning.',
    'themeValidationFailed' => 'Thema bevat PHP-code en kan niet worden geactiveerd.',
    'noThemesInstalled'     => 'Geen thema\'s geïnstalleerd. Bezoek de Marktplaats om thema\'s te downloaden.',
    'themeUnapprovedTitle'  => 'Niet-goedgekeurd thema activeren?',
    'themeNotApproved'      => 'Dit thema is niet goedgekeurd door Pubvana.',
    'themeUnapprovedRisk'   => 'Het activeren van niet-goedgekeurde thema\'s kan beveiligingsrisico\'s of compatibiliteitsproblemen introduceren.',
    'themeActivateConfirm'  => 'Weet u zeker dat u het toch wilt activeren?',
    'themeActivateAnyway'   => 'Toch activeren',
    'themeNoOptions'        => 'Dit thema heeft geen configureerbare opties.',
    'themeCustomize'        => 'Thema aanpassen',

    // Theme flash messages
    'themeActivated'        => 'Thema geactiveerd.',
    'themeOptionsSaved'     => 'Opties opgeslagen.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Gelicentieerd',
    'licenseCheckNow'        => 'Nu controleren',
    'licenseExpired'         => 'Verlopen',
    'licenseEnterKey'        => 'Sleutel invoeren',
    'licenseChangeKey'       => 'Wijzigen',
    'licenseRenew'           => 'Verlengen',
    'licenseThirdParty'      => 'Derde partij',
    'unchecked'              => 'Niet gecontroleerd',
    'safetyLabel'            => 'Veiligheid:',
    'recheckBtn'             => 'Opnieuw controleren',
    'recheckSuccess'         => 'Veiligheidscontrole bijgewerkt.',
    'recheckFailed'          => 'Kon de verificatieserver niet bereiken. Probeer het later opnieuw.',
    'recheckNotFound'        => 'Item niet gevonden.',
    'widgetBlockedMalicious' => '{0} is gemarkeerd als kwaadaardig en kan niet worden toegevoegd.',
    'licenseNoStoreProduct'  => 'Dit item is niet gekoppeld aan een winkelproduct. Als u dit item heeft gekocht, installeer het dan opnieuw via de marketplace om licentiëring in te schakelen.',
    'securityWarning'        => 'Beveiligingswaarschuwing:',
    'licenseModalTitle'      => 'Licentiesleutel invoeren',
    'licenseModalBody'       => 'Plak uw licentiesleutel hieronder.',
    'licenseModalSave'       => 'Opslaan',
    'licenseSaved'           => 'Licentiesleutel opgeslagen en gevalideerd.',
    'licenseInvalid'         => 'Licentiesleutel is niet geldig.',
    'licenseKeyRequired'     => 'Licentiesleutel en product zijn verplicht.',
    'licenseCheckFailed'     => 'Kon de licentieserver niet bereiken. Probeer het later opnieuw.',
    'licenseProductNotFound' => 'Kon dit item niet vinden in de winkel.',
    'btnCancel'              => 'Annuleren',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widgets',
    'widgetConfigureTitle'  => 'Widget configureren',
    'widgetAreas'           => 'Widgetgebieden',
    'widgetAvailable'       => 'Beschikbare widgets',
    'widgetAddToArea'       => 'Toevoegen aan gebied',
    'widgetArea'            => 'Gebied',
    'widgetNoOptions'       => 'Geen opties.',
    'widgetSaveConfig'      => 'Configuratie opslaan',
    'widgetConfigure'       => 'Configureren',
    'widgetNoAreas'         => 'Geen widgetgebieden gevonden. Activeer een thema om widgetgebieden in te schakelen.',
    'widgetAreaEmpty'       => 'Geen widgets in dit gebied. Voeg er een toe uit de lijst →',

    // Widget flash messages
    'widgetAdded'           => 'Widget toegevoegd.',
    'widgetRemoved'         => 'Widget verwijderd.',
    'widgetConfigured'      => 'Widget geconfigureerd.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Marktplaats',
    'marketplaceRefresh'    => 'Vernieuwen',
    'marketplaceVisitStore' => 'Winkel bezoeken',
    'marketplaceAll'        => 'Alle',
    'marketplaceThemes'     => 'Thema\'s',
    'marketplaceWidgets'    => 'Widgets',
    'marketplacePlugins'    => 'Plugins',
    'marketplaceUpdatesAvailable' => '{0} update(s) beschikbaar.',
    'marketplaceBy'         => 'Door',
    'marketplaceFree'       => 'Gratis',
    'marketplaceInstalled'  => 'Geïnstalleerd',
    'marketplaceInstall'    => 'Installeren',
    'marketplaceBuyNow'     => 'Nu kopen',
    'marketplaceNoItems'    => 'Geen items gevonden op de marktplaats.',
    'marketplaceInstalledVersion' => 'v{0} geïnstalleerd',
    'marketplaceLoadError'  => 'Kon producten niet laden uit de winkel. Probeer het later opnieuw.',
    'byAuthor'              => 'Door {0}',
    'unknown'               => 'Onbekend',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} succesvol geïnstalleerd.',
    'marketplaceInstallFail'    => 'Installatie mislukt. Controleer de logs.',
    'marketplaceUpdateSuccess'  => 'Succesvol bijgewerkt.',
    'marketplaceUpdateFail'     => 'Update mislukt.',
    'marketplaceCacheRefreshed' => 'Marktplaats-cache vernieuwd.',
    'marketplaceInvalidRequest' => 'Ongeldig installatieverzoek.',
    'marketplaceCannotUpdate'   => 'Kan dit item niet bijwerken.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licenties',
    'licensesNone'                => 'Geen licenties',
    'licensesProduct'             => 'Product',
    'licensesKey'                 => 'Licentiesleutel',
    'licensesStatus'              => 'Status',
    'licensesType'                => 'Type',
    'licensesExpires'             => 'Vervalt op',
    'licensesDomain'              => 'Domein',
    'licensesInstalled'           => 'Geïnstalleerd',
    'licensesLastChecked'         => 'Laatst gecontroleerd',
    'licensesActions'             => 'Acties',
    'licensesStatusValid'         => 'Geldig',
    'licensesStatusInvalid'       => 'Ongeldig',
    'licensesStatusExpired'       => 'Verlopen',
    'licensesStatusSubExpired'    => 'Abonnement verlopen',
    'licensesStatusUnchecked'     => 'Niet gecontroleerd',
    'licensesSubscription'        => 'Abonnement',
    'licensesOneTime'             => 'Eenmalig',
    'licensesPerpetual'           => 'Eeuwigdurend',
    'licensesNotInstalled'        => 'Niet geïnstalleerd',
    'licensesNever'               => 'Nooit',
    'licensesRevalidate'          => 'Opnieuw valideren',
    'licenseKeyPlaceholder'       => 'Licentiesleutel invoeren...',
    'marketplaceLicensesEmpty'    => 'Gelicentieerde producten verschijnen hier na installatie.',
    'typeTheme'                   => 'Thema',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Plugin',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Licentie succesvol gevalideerd.',
    'licenseRevalidateInvalid'     => 'Licentie is ongeldig of verlopen.',
    'licenseRevalidateUnreachable' => 'Kon de licentieserver niet bereiken. Probeer het later opnieuw.',
    'licenseRevalidateSkipped'     => 'Licentiecontrole overgeslagen (ontwikkelingsmodus).',
    'licenseRevalidateNotFound'    => 'Licentie niet gevonden.',

    // License warning banners
    'licenseWarningTitle'   => 'Licentieproblemen',
    'licenseWarningInvalid' => 'licentie is ongeldig of verlopen',
    'licenseWarningManage'  => 'Licenties beheren',

    // Plugin license
    'pluginInvalidLicense' => 'Deze plugin heeft een ongeldige of verlopen licentie en kan niet worden geactiveerd.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Licentiesleutel',
    'storeBrowseFull'       => 'Volledige winkel bekijken',
    'storeBackToMarketplace'=> 'Terug naar marktplaats',
    'storeNoProducts'       => 'Geen producten beschikbaar.',
    'storeViewInStore'      => 'Bekijken in winkel',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Gebruikers',
    'editUserTitle'         => 'Gebruiker bewerken',
    'createUserTitle'       => 'Gebruiker aanmaken',
    'authorProfileTitle'    => 'Auteursprofiel',
    'userRoleLabel'         => 'Rol',
    'userActiveLabel'       => 'Actief',
    'userPasswordLabel'     => 'Wachtwoord',
    'userPasswordOptional'  => 'Laat leeg om het huidige wachtwoord te bewaren',
    'userDisplayName'       => 'Weergavenaam',
    'userBio'               => 'Bio',
    'userWebsite'           => 'Website',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Profiel opslaan',
    'userSaveChanges'       => 'Wijzigingen opslaan',
    'userCannotDeleteSelf'  => 'U kunt uzelf niet verwijderen.',
    'userCannotDeleteOwner' => 'Het eigenaar-account kan niet worden verwijderd.',
    'userOwnerCannotModify' => 'Het eigenaar-account kan niet worden gewijzigd.',

    // User flash messages
    'userCreated'           => 'Gebruiker aangemaakt.',
    'userUpdated'           => 'Gebruiker bijgewerkt.',
    'userDeleted'           => 'Gebruiker verwijderd.',
    'userBanned'            => 'Gebruiker is verbannen.',
    'userUnbanned'          => 'Gebruiker is ontbannen.',
    'userCannotBanSelf'     => 'U kunt uzelf of de site-eigenaar niet verbannen.',
    'banStatus'             => 'Ban-status',
    'banned'                => 'Verbannen',
    'ban'                   => 'Gebruiker verbannen',
    'unban'                 => 'Ontbannen',
    'banReasonRequired'     => 'Een reden voor de ban is verplicht.',
    'banReasonPlaceholder'  => 'Reden voor ban...',
    'confirmBanUser'        => 'Weet u zeker dat u deze gebruiker wilt verbannen?',
    'userProfileSaved'      => 'Profiel opgeslagen.',
    'userAvatarUploadFail'  => 'Avatar-upload mislukt: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA instellen',
    'tfaSetupHeading'       => 'Twee-factor-authenticatie instellen',
    'tfaScanQr'             => 'Scan de QR-code hieronder met uw authenticator-app (bijv. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Of voer de geheime sleutel handmatig in:',
    'tfaEnterCode'          => 'Voer de 6-cijferige code uit uw app in ter bevestiging:',
    'tfaCodeLabel'          => 'Authenticatiecode',
    'tfaConfirmBtn'         => 'Bevestigen & 2FA inschakelen',
    'tfaDisableBtn'         => '2FA uitschakelen',
    'tfaDisableConfirm'     => 'Voer uw huidige 2FA-code in om uit te schakelen:',
    'tfaEnabled'            => 'Twee-factor-authenticatie ingeschakeld.',
    'tfaDisabled'           => 'Twee-factor-authenticatie uitgeschakeld.',
    'tfaInvalidCode'        => 'Ongeldige code - scan de QR-code en probeer het opnieuw.',
    'tfaInvalidDisable'     => 'Ongeldige code - 2FA werd niet uitgeschakeld.',
    'tfaSessionExpired'     => 'Installatiesessie verlopen - start opnieuw.',
    'tfaNotEnabled'         => '2FA is momenteel niet ingeschakeld.',
    'tfaCantScan'           => 'Kan niet scannen? Voer deze code handmatig in:',
    'tfaWarning'            => 'Bewaar deze geheime sleutel op een veilige plek. U heeft deze nodig om toegang te herstellen als u uw authenticator-apparaat verliest.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Sociale links',
    'socialPlatform'           => 'Platform',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Pictogram',
    'socialSortOrder'          => 'Sorteervolgorde',
    'socialIconPackInfo'       => 'Het huidige thema <strong>{0}</strong> gebruikt <strong>{1}</strong> (v{2}) voor pictogrammen. Hieronder kunt u de beschikbare pictogrammen kiezen die worden weergegeven voor de Sociale links-functie van deze site.',
    'socialSearchPlaceholder'  => 'Platforms zoeken...',
    'socialIconDisclaimer'     => 'Deze pictogrammen zijn slechts een weergave van het gebruikte pictogram. Het werkelijke pictogram kan verschillen afhankelijk van het pictogrampakket van het actieve thema.',

    // Social flash messages
    'socialLinkAdded'       => 'Sociale link toegevoegd.',
    'socialLinkUpdated'     => 'Link bijgewerkt.',
    'socialLinkDeleted'     => 'Link verwijderd.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Omleidingen',
    'redirectFrom'          => 'Van URL',
    'redirectTo'            => 'Naar URL',
    'redirectType'          => 'Type',
    'redirectAdd'           => 'Omleiding toevoegen',
    'redirectFromHint'      => '(relatief, bijv. /oude-pagina)',
    'redirect301'           => '301 Permanent',
    'redirect302'           => '302 Tijdelijk',
    'redirectInvalidDest'   => 'Ongeldig doel-URL voor omleiding.',

    // Redirect flash messages
    'redirectAdded'         => 'Omleiding toegevoegd.',
    'redirectDeleted'       => 'Omleiding verwijderd.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Instellingen',
    'settingsGeneral'       => 'Algemeen',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'E-mail',
    'settingsSocialLogin'   => 'Sociaal inloggen',
    'settingsSocialSharing' => 'Sociaal delen',
    'settingsSpam'          => 'Spambescherming',

    'generalSettingsHeading'    => 'Algemene instellingen',
    'generalSiteName'           => 'Sitenaam',
    'generalTagline'            => 'Tagline',
    'generalAdminEmail'         => 'Beheerders-e-mail',
    'generalPostsPerPage'       => 'Berichten per pagina',
    'generalComments'           => 'Reacties',
    'generalCommentsEnable'     => 'Reacties inschakelen',
    'generalCommentModeration'  => 'Moderatie vereisen voor publicatie',
    'generalMaintenanceMode'    => 'Onderhoudsmodus',
    'generalMaintenanceEnable'  => 'Onderhoudsmodus inschakelen',
    'generalMaintenanceHelp'    => 'Bezoekers zien een "We zijn snel terug"-pagina. Beheerders hebben nog steeds toegang tot de site.',
    'generalFrontPage'          => 'Startpagina',
    'generalFrontPageBlog'      => 'Blog-index (laatste berichten)',
    'generalFrontPageStatic'    => 'Statische pagina:',
    'generalFrontPagePlugin'    => 'Plugin-pagina:',
    'generalSelectPage'         => '- Selecteer een pagina -',
    'generalSelectRoute'        => '- Selecteer een route -',
    'generalFrontPageNoPlugins' => 'Geen plugin-routes beschikbaar',
    'generalPageCacheTtl'       => 'Paginacache TTL',
    'settingsCacheTtlHint'      => 'Seconden. 0 = uitgeschakeld.',
    'generalSaveBtn'            => 'Algemene instellingen opslaan',

    // General flash messages
    'generalSettingsSaved'      => 'Algemene instellingen opgeslagen.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO-instellingen',
    'seoMetaDescription'        => 'Meta-beschrijving',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Sitemap',
    'seoSitemapEnable'          => 'sitemap.xml inschakelen',
    'seoSitemapHelp'            => 'Standaard sitemap voor alle gepubliceerde berichten en pagina\'s.',
    'seoNewsSitemap'            => 'news-sitemap.xml inschakelen',
    'seoNewsSitemapHelp'        => 'Google News-sitemap - lijst berichten gepubliceerd in de laatste 48 uur.',
    'seoSaveBtn'                => 'SEO-instellingen opslaan',
    'seoSettingsSaved'          => 'SEO-instellingen opgeslagen.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'E-mailinstellingen',
    'emailFromName'             => 'Van naam',
    'emailFromAddress'          => 'Van adres',
    'emailProtocol'             => 'Protocol',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP-host',
    'emailSmtpPort'             => 'SMTP-poort',
    'emailSmtpEncryption'       => 'Versleuteling',
    'emailSmtpEncryptionNone'   => 'Geen',
    'emailSmtpUsername'         => 'SMTP-gebruikersnaam',
    'emailSmtpPassword'         => 'SMTP-wachtwoord',
    'emailSaveBtn'              => 'E-mailinstellingen opslaan',
    'emailSettingsSaved'        => 'E-mailinstellingen opgeslagen.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Sociaal inloggen (OAuth)',
    'socialLoginHelp'           => 'Inloggegevens worden opgeslagen in uw .env-bestand. Registreer uw app bij Google en Facebook om client-ID\'s en geheimen te verkrijgen.',
    'socialLoginGoogleId'       => 'Client-ID',
    'socialLoginGoogleSecret'   => 'Clientgeheim',
    'socialLoginFbAppId'        => 'App-ID',
    'socialLoginFbAppSecret'    => 'App-geheim',
    'socialLoginPlaceholderSecret' => '(laat leeg om bestaande te bewaren)',
    'socialLoginSaveBtn'        => 'Instellingen voor sociaal inloggen opslaan',
    'socialLoginSettingsSaved'  => 'Instellingen voor sociaal inloggen opgeslagen.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Automatisch delen op sociale media bij publicatie',
    'socialSharingHelp'         => 'Wanneer een bericht wordt gepubliceerd met "Delen bij publicatie" aangevinkt, zal Pubvana automatisch posten naar geconfigureerde sociale accounts.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Haal sleutels op via developer.twitter.com → Uw app → Sleutels en tokens.',
    'socialSharingApiKey'       => 'API-sleutel',
    'socialSharingApiSecret'    => 'API-geheim',
    'socialSharingAccessToken'  => 'Toegangstoken',
    'socialSharingAccessSecret' => 'Toegangsgeheim',
    'socialSharingFbPage'       => 'Facebook-pagina',
    'socialSharingFbPageHelp'   => 'Vereist een Paginatoegangstoken met pages_manage_posts-machtiging.',
    'socialSharingFbPageId'     => 'Pagina-ID',
    'socialSharingFbPageToken'  => 'Paginatoegangstoken',
    'socialSharingSaveBtn'      => 'Deelinstellingen opslaan',
    'socialSharingSettingsSaved'=> 'Instellingen voor sociaal delen opgeslagen.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Spambescherming (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana gebruikt hCaptcha (privacyvriendelijk, niet-Google) om reactieformulieren en het contactformulier te beschermen tegen spambots.',
    'spamHcaptchaFree'          => 'hCaptcha is gratis voor de meeste sites. Meld u aan op hcaptcha.com, dan: Account → Sites → Add Site voor uw sitesleutel en Account → Settings → Secret Key → Generate voor uw geheime sleutel. Voer beide hieronder in.',
    'spamHcaptchaSiteKey'       => 'Sitesleutel',
    'spamHcaptchaSecretKey'     => 'Geheime sleutel',
    'spamHcaptchaNote'          => 'Als deze sleutels niet zijn ingesteld, wordt hCaptcha stilzwijgend overgeslagen — veilig voor lokale ontwikkeling. Eenmaal opgeslagen verschijnt de widget automatisch op het reactieformulier en de contactpagina.',
    'spamSettingsSaved'         => 'Instellingen voor spambescherming opgeslagen.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Talen',
    'languageCode'              => 'Code',
    'languageName'              => 'Naam',
    'languageDefault'           => 'Standaard',
    'languageEnabled'           => 'Ingeschakeld',
    'languageMakeDefault'       => 'Standaard maken',
    'languageSetAsDefault'      => '{0} ingesteld als standaardtaal.',
    'languageEnabled_msg'       => '{0} ingeschakeld.',
    'languageDisabled_msg'      => '{0} uitgeschakeld.',
    'languageNotFound'          => 'Taal niet gevonden.',
    'languageCannotDisable'     => 'Kan de standaardtaal niet uitschakelen.',
    'languageDirection'         => 'Richting',
    'languageNativeName'        => 'Eigen naam',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analyse',
    'analyticsTotalViews'       => 'Totale weergaven',
    'analyticsTopPosts'         => 'Populairste berichten',
    'analyticsReferrers'        => 'Populairste verwijzers',
    'analyticsLast7'            => 'Laatste 7 dagen',
    'analyticsLast30'           => 'Laatste 30 dagen',
    'analyticsLast90'           => 'Laatste 90 dagen',
    'analyticsChartTitle'       => 'Paginaweergaven',
    'analyticsNoData'           => 'Geen analysegegevens voor deze periode.',
    'analyticsDomain'           => 'Domein',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Affiliate-links',
    'newAffiliateLinkTitle'     => 'Nieuwe affiliate-link',
    'editAffiliateLinkTitle'    => 'Affiliate-link bewerken',
    'affiliateName'             => 'Naam',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Doel-URL',
    'affiliateActive'           => 'Actief',
    'affiliateClicks'           => 'Klikken',
    'affiliateClicksTitle'      => 'Klikken - {0}',
    'affiliateTotal'            => 'Totaal',
    'affiliateViewClicks'       => 'Klikken bekijken',

    // Affiliate flash messages
    'affiliateCreated'          => 'Affiliate-link aangemaakt.',
    'affiliateUpdated'          => 'Affiliate-link bijgewerkt.',
    'affiliateDeleted'          => 'Affiliate-link verwijderd.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Kapotte links',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP-status',
    'brokenLinkError'           => 'Fout',
    'brokenLinkSource'          => 'Bron',
    'brokenLinkShowDismissed'   => 'Gesloten weergeven',
    'brokenLinkHideDismissed'   => 'Gesloten verbergen',
    'brokenLinkTimeout'         => 'Time-out',
    'brokenLinkBroken'          => 'kapot',
    'brokenLinkNone'            => 'Geen kapotte links gevonden.',
    'brokenLinkNowReachable'    => 'Link is nu bereikbaar - verwijderd uit resultaten.',
    'brokenLinkStillBroken'     => 'Link is nog steeds kapot ({0}).',
    'brokenLinkDismissed'       => 'Link gesloten.',
    'brokenLinksCliHint'        => 'Voer een volledige scan uit via de opdrachtregel om dit rapport te vullen: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} probleem/problemen gevonden',
    'brokenLinksCount'          => '{0} kapot',
    'brokenLinksRecheck'        => 'Deze URL opnieuw controleren',
    'brokenLinksDismiss'        => 'Sluiten (verbergen uit resultaten)',
    'brokenLinksRunScan'        => 'Scan uitvoeren',
    'brokenLinksScanComplete'   => 'Scan voltooid: {0} links gecontroleerd, {1} kapot.',
    'timeout'                   => 'Time-out',
    'typePost'                  => 'Bericht',
    'typePage'                  => 'Pagina',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Activiteitenlog',
    'activityLogType'           => 'Type',
    'activityLogAction'         => 'Actie',
    'activityLogUser'           => 'Gebruiker',
    'activityLogDate'           => 'Datum',
    'activityLogNote'           => 'Notitie',
    'activityLogFilterAll'      => 'Alle typen',
    'activityLogEmpty'          => 'Nog geen activiteit geregistreerd.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Back-up & export',
    'backupDownload'            => 'Back-up maken & downloaden',
    'backupFiles'               => 'Beschikbare back-ups',
    'backupFilename'            => 'Bestandsnaam',
    'backupSize'                => 'Grootte',
    'backupDate'                => 'Aangemaakt',
    'backupGenerating'          => 'Back-up genereren…',
    'backupNoFiles'             => 'Geen opgeslagen back-ups.',
    'backupFailed'              => 'Back-up mislukt: {0}',
    'backupDeleted'             => 'Back-up verwijderd.',
    'backupCannotDelete'        => 'Kon back-up niet verwijderen.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP\'s worden opgeslagen als SHA-256-hashes — geen onbewerkte PII opgeslagen.',
    'colTime'                   => 'Tijd',
    'colIpHash'                 => 'IP-hash',
    'colReferrer'               => 'Verwijzer',
    'affiliateDirectReferrer'   => 'Direct',
    'affiliateNameHint'         => 'Intern label — niet zichtbaar voor bezoekers.',
    'affiliateSlugHint'         => 'Alleen letters, cijfers, koppeltekens en underscores. Kan niet worden gewijzigd zodra links worden gedeeld.',
    'affiliateDestHint'         => 'Moet https:// bevatten. Bezoekers worden 301-doorgestuurd.',
    'affiliateInactiveHint'     => 'Inactieve links retourneren een 404.',
    'affiliateLinkCount'        => '{0} links',
    'colDomain'                 => 'Domein',
    'commentAll'                => 'Alle',
    'commentPending'            => 'In behandeling',
    'commentTrash'              => 'Prullenbak',
    'commentsNone'              => 'Geen {0} reacties.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Back-up maken',
    'backupStarting'            => 'Back-up starten...',
    'backupNoneYet'             => 'Nog geen back-ups. Klik op "Back-up maken" om uw eerste te maken.',
    'backupsTitle'              => 'Back-ups',
    'backupRetentionNote'       => 'Maximaal 15 back-ups bewaard — oudste worden automatisch verwijderd.',
    'backupRestoreConfirm'      => 'Deze back-up herstellen? Eerst wordt een back-up van de huidige staat gemaakt.',
    'backupDeleteConfirm'       => 'Deze back-up verwijderen?',
    'colFilename'               => 'Bestandsnaam',
    'colVersion'                => 'Versie',
    'colTrigger'                => 'Trigger',
    'colSize'                   => 'Grootte',
    'colDate'                   => 'Datum',
    'colActions'                => 'Acties',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Importeren',
    'importWpHeading'           => 'Importeren vanuit WordPress',
    'importWpHelp'              => 'Exporteer uw WordPress-site via Extra → Exporteren en upload het .xml-bestand hieronder.',
    'importChooseFile'          => 'WXR-bestand kiezen (.xml)',
    'importDryRun'              => 'Proefrun (alleen voorbeeld - niets wordt opgeslagen)',
    'importRunBtn'              => 'Import uitvoeren',
    'importNoValidFile'         => 'Upload een geldig WordPress WXR-exportbestand.',
    'importOnlyXml'             => 'Alleen .xml-bestanden worden geaccepteerd.',
    'importFileTooLarge'        => 'Importbestand te groot. Maximale grootte is 50 MB.',
    'importResultsHeading'      => 'Importresultaten',
    'importDryRunNote'          => 'Proefrun - geen gegevens opgeslagen.',
    'importDryRunLabel'         => '(Proefrun — geen gegevens geschreven)',
    'importComplete'            => 'Import voltooid',
    'importCreated'             => 'aangemaakt',
    'importSkipped'             => 'overgeslagen',
    'importErrors'              => 'Fouten:',
    'importInstructions'        => 'Exporteer uw WordPress-inhoud via <strong>Extra → Exporteren → Alle inhoud</strong> en upload het <code>.xml</code>-bestand hier. Pubvana importeert berichten, pagina\'s, categorieën, tags, auteurs en reacties.',
    'importCliTitle'            => 'CLI-import',
    'importCliHint'             => 'U kunt de importeur ook uitvoeren vanaf de opdrachtregel:',
    'importCliDryRunHint'       => 'De vlag <code>--dry-run</code> toont wat er geïmporteerd zou worden zonder naar de database te schrijven.',
    'importWhatTitle'           => 'Wat wordt geïmporteerd',
    'importItemPosts'           => 'Berichten (titel, inhoud, samenvatting, slug, status)',
    'importItemPages'           => 'Pagina\'s',
    'importItemCategories'      => 'Categorieën (met hiërarchie)',
    'importItemTags'            => 'Tags',
    'importItemAuthors'         => 'Auteurs (aangemaakt als abonnee-accounts)',
    'importItemComments'        => 'Reacties',
    'importItemMedia'           => 'Mediabestanden (URL\'s bewaard in inhoud)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Updates',
    'updatesCurrentVersion'     => 'Huidige versie',
    'updatesLatestVersion'      => 'Laatste versie',
    'updatesUpToDate'           => 'Pubvana is up-to-date.',
    'updatesAvailable'          => 'Update beschikbaar: {0}',
    'updatesCheckBtn'           => 'Controleren op updates',
    'updatesReleaseNotes'       => 'Release-opmerkingen',
    'updatesHowToApply'         => 'Een update toepassen',
    'updatesCacheCleared'       => 'Update-cache gewist - opnieuw controleren.',
    'updatesExtCapped'          => 'Update beschikbaar: {0} (add-on-veilig)',
    'updatesNewerAvailable'     => 'Pubvana {0} is ook beschikbaar - update de onderstaande add-ons om het te ontgrendelen.',

    // Addon Updates
    'updatesExtTitle'               => 'Add-ons',
    'updatesExtCheckAll'            => 'Alles controleren',
    'updatesExtUpdateAll'           => 'Alles bijwerken',
    'updatesExtCheckAllType'        => 'Alle {0} controleren',
    'updatesExtUpdateAllType'       => 'Alle {0} bijwerken',
    'updatesExtNoInstalled'         => 'Geen {0} geïnstalleerd.',
    'updatesExtColName'             => 'Naam',
    'updatesExtColVersion'          => 'Versie',
    'updatesExtColLatest'           => 'Laatste',
    'updatesExtColAutoUpdate'       => 'Auto-update',
    'updatesExtColStatus'           => 'Status',
    'updatesExtColActions'          => 'Acties',
    'updatesExtBundled'             => 'Kernbundel',
    'updatesExtNoSource'            => 'Geen updatebron',
    'updatesExtFailed'              => 'Mislukt',
    'updatesExtUpdatedAt'           => 'Bijgewerkt {0}',
    'updatesExtAvailable'           => 'Update beschikbaar',
    'updatesExtUpToDate'            => 'Up-to-date',
    'updatesExtUpdate'              => 'Bijwerken',
    'updatesExtChecking'            => 'Controleren...',
    'updatesExtUpdating'            => 'Bijwerken...',
    'updatesExtUpdated'             => 'Bijgewerkt',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Update bevestigen',
    'updatesConfirmBody'            => 'Dit maakt een back-up van uw site, downloadt de update en past deze toe.',
    'updatesConfirmSafe'            => 'Uw <code>.env</code>, <code>App.php</code> en <code>Database.php</code> worden nooit overschreven.',
    'updatesConfirmBtn'             => 'Nu bijwerken',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Alle add-ons bijwerken',
    'updatesExtAllBody'             => 'Dit werkt alle add-ons bij die updates hebben.',
    'updatesExtAllNote'             => 'Add-ons met auto-update uitgeschakeld worden ook bijgewerkt.',
    'updatesExtAllBtn'              => 'Alles bijwerken',

    'updatesExtBadge'               => 'Update: v{0}',
    'updatesExtGoToUpdates'         => 'Updates',

    // Update Settings
    'updatesSettingsTitle'          => 'Update-instellingen',
    'updatesAutoUpdateLabel'        => 'Pubvana automatische update',
    'updatesAutoUpdateManual'       => 'Handmatig',
    'updatesAutoUpdateAuto'         => 'Automatisch',
    'updatesAutoUpdateHelp'         => 'Indien ingeschakeld worden Pubvana-updates zonder breaking changes automatisch toegepast.',
    'updatesCheckMethodLabel'       => 'Methode voor updatecontrole',
    'updatesCheckMethodPageload'    => 'Paginalading',
    'updatesCheckMethodCron'        => 'Cron-taak',
    'updatesCheckMethodHelp'        => 'Paginalading controleert bij elk verzoek (gecachet 24u). Cron vereist een server-cron-taak.',
    'updatesCronCommand'            => 'Cron-opdracht',
    'updatesCronHelp'               => 'Voeg dit toe aan de crontab van uw server om de updatecontrole dagelijks uit te voeren:',
    'updatesSettingsSaved'          => 'Update-instellingen opgeslagen.',

    // Compatibility
    'compatWarningTitle'            => 'Compatibiliteitswaarschuwing',
    'compatNotCompatible'           => 'Sommige geïnstalleerde add-ons zijn niet compatibel met deze versie.',
    'compatRequiresUpdate'          => 'maar vereist dat de volgende add-ons eerst worden bijgewerkt:',
    'compatSupportsUpTo'            => 'ondersteunt tot {0}',
    'compatRequiresMin'             => 'vereist Pubvana {0}+',
    'compatNotDeclared'             => 'De volgende add-ons hebben geen compatibiliteit gedeclareerd met Pubvana {0}. Ze kunnen stoppen na de update:',
    'compatColType'                 => 'Type',
    'compatColName'                 => 'Naam',
    'compatColVersion'              => 'Compatibiliteit',
    'compatRemoveHint'              => 'U kunt incompatibele add-ons verwijderen of overschakelen naar het standaardthema als er problemen optreden. Voor elke update wordt een back-up gemaakt.',
    'compatMaxVersion'              => 'Maximale compatibele versie: {0}',
    'compatMinVersion'              => 'Vereist Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Berichtplanning',
    'scheduleNoScheduled'       => 'Geen geplande berichten.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revisies - {0}',
    'revisionPageTitle'         => 'Revisie - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'U moet ingelogd zijn om toegang te krijgen tot het beheerpaneel.',
    'dirNotWritable'            => 'Map is niet beschrijfbaar: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} is onjuist geconfigureerd. Als u de eindgebruiker bent, neem contact op met de ontwikkelaar. Als u de ontwikkelaar bent, raadpleeg dan de documentatie.',
    'addonMisconfiguredLink'    => '{0} is onjuist geconfigureerd. Als u de eindgebruiker bent, <a href="{1}">neem contact op met de ontwikkelaar</a>. Als u de ontwikkelaar bent, <a href="https://github.com/enlivenapp/pubvana">raadpleeg dan de documentatie</a>.',
    'licenseExpiringSoon'       => 'Licentie voor {0} vervalt op {1}. {0} wordt gedeactiveerd wanneer de licentie vervalt.',
    'licenseExpiredDeactivated' => '{0} is gedeactiveerd omdat de licentie is verlopen.',
    'addonDeactivated'          => '{0} is gedeactiveerd. Reden: {1}.',
    'widgetValidationFailed'    => "Widget ''{0}'' kon niet worden gevalideerd. Neem contact op met de ontwikkelaar of verwijder de add-on.",
    'widgetValidationFailedLink' => "Widget ''{0}'' kon niet worden gevalideerd. <a href=\"{1}\">Neem contact op met de ontwikkelaar</a> of verwijder de add-on.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Gedeactiveerd: licentie verlopen',
    'addonDeactivatedTampered'  => 'Gedeactiveerd: onjuist geconfigureerd',
    'addonDeactivatedNoLicense' => 'Gedeactiveerd: geen geldige licentie',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Uitgeschakeld',
    'addonDisabledInvalidJson'  => 'Systeem: {0} heeft een ongeldig of onleesbaar {1}.',
    'addonDisabledMissingFields' => 'Systeem: {0} mist verplichte velden: {1}.',
    'addonDisabledPhpFiles'     => 'Systeem: {0} bevat PHP-bestanden. Widgets mogen alleen JSON + sjablonen zijn.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'Een geldige licentie is vereist om {0} te activeren.',
    'licenseInvalidActivation'  => 'Licentievalidatie mislukt voor {0}. Controleer uw licentiesleutel.',
    'licenseExpiredActivation'  => 'De licentie voor {0} is verlopen. Verleng om te activeren.',
    'licenseCheckUnreachable'   => 'Kon de licentie voor {0} niet verifiëren. De licentieserver is niet bereikbaar. Probeer het later opnieuw.',
    'activationBlockedTampered' => '{0} kan niet worden geactiveerd omdat het onjuist is geconfigureerd.',
    'activationBlockedBundled'  => '{0} kan niet worden geactiveerd: alleen Pubvana-add-ons mogen als gebundeld worden gemarkeerd.',
    'activationBlockedNoUrls'   => '{0} kan niet worden geactiveerd: betaalde add-ons moeten licentie-verificatie-URL\'s bevatten.',
    'activationBlockedFreeFlag' => '{0} kan niet worden geactiveerd: Pubvana-add-ons mogen niet als gratis worden gemarkeerd.',
    'activationBlockedDisabled' => '{0} kan niet worden geactiveerd omdat het configuratiefouten heeft. Controleer het infobestand.',

    // Third-party license
    'licenseThirdPartyLabel'    => 'Derde partij',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Update starten...',
    'updateCheckLabel'           => 'Updatecontrole:',
    'updateAvailable'            => 'Pubvana {0} is beschikbaar!',
    'updateRunning'              => 'U gebruikt {0}.',
    'updateBreakingChanges'      => 'Breaking changes',
    'updateMigrationNotes'       => 'Migratie-opmerkingen',
    'updateNotices'              => 'Meldingen',
    'updatePreflightTitle'       => 'Voorbereidende controles',
    'updateToVersion'            => 'Bijwerken naar Pubvana {0}',
    'updatePreflightFailed'      => 'Een of meer vereiste voorbereidende controles zijn mislukt. Los ze op voordat u bijwerkt.',
    'updateUpToDate'             => 'Pubvana is up-to-date. U gebruikt versie {0}.',
    'updateAnyway'               => 'Toch bijwerken',
    'updateAvailableTooltip'     => 'Pubvana {0} beschikbaar',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(u)',
    'usersNone'                  => 'Geen gebruikers gevonden.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Account actief',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Profieldetails',
    'profileDisplayNameHint'     => 'Weergegeven op gepubliceerde berichten in plaats van gebruikersnaam.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP of GIF. Max. 10 MB.',
    'profileSocialHandles'       => 'Sociale handles',
    'preview'                    => 'Voorbeeld',
    'website'                    => 'Website',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Twee-factor-authenticatie',
    'totpActiveDesc'             => 'TOTP twee-factor-authenticatie is actief op uw account. U wordt gevraagd om een 6-cijferige code van uw authenticator-app bij elke inlog.',
    'totpCurrentCode'            => 'Huidige code',
    'totpInactiveDesc'           => 'Voeg een extra beveiligingslaag toe aan uw account. Eenmaal ingeschakeld moet u bij elke inlog een code invoeren van uw authenticator-app.',
    'totpEnable'                 => 'Twee-factor-authenticatie inschakelen',
    'totpScanInstructions'       => 'Open uw authenticator-app (Google Authenticator, Authy, 1Password, etc.) en scan deze QR-code.',
    'totpManualEntry'            => 'Kan niet scannen? Voer deze code handmatig in:',
    'totpConfirmInstructions'    => 'Voer na het scannen de 6-cijferige code in die in uw app wordt weergegeven om de installatie te bevestigen.',
    'totpRecoveryWarning'        => 'Bewaar uw herstelcodes. Als u de toegang tot uw authenticator-app verliest, kunt u niet inloggen. Neem contact op met uw sitebeheerder om 2FA opnieuw in te stellen.',

];
