<?php

/**
 * Pubvana CMS - Admin language strings (Lithuanian)
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
    'save'              => 'Išsaugoti',
    'saveChanges'       => 'Išsaugoti pakeitimus',
    'cancel'            => 'Atšaukti',
    'edit'              => 'Redaguoti',
    'delete'            => 'Ištrinti',
    'create'            => 'Sukurti',
    'add'               => 'Pridėti',
    'back'              => 'Atgal',
    'view'              => 'Peržiūrėti',
    'apply'             => 'Taikyti',
    'install'           => 'Įdiegti',
    'update'            => 'Atnaujinti',
    'refresh'           => 'Atnaujinti',
    'activate'          => 'Aktyvuoti',
    'deactivate'        => 'Deaktyvuoti',
    'enable'            => 'Įjungti',
    'disable'           => 'Išjungti',
    'disabled'          => 'Išjungta',
    'approve'           => 'Patvirtinti',
    'spam'              => 'Šlamštas',
    'trash'             => 'Šiukšlinė',
    'restore'           => 'Atkurti',
    'dismiss'           => 'Atmesti',
    'recheck'           => 'Patikrinti iš naujo',
    'clickToCopy'       => 'Spustelėkite kopijuoti',
    'download'          => 'Atsisiųsti',
    'upload'            => 'Įkelti',
    'import'            => 'Importuoti',
    'export'            => 'Eksportuoti',
    'publish'           => 'Paskelbti',
    'unpublish'         => 'Atšaukti paskelbimą',
    'logout'            => 'Atsijungti',
    'viewSite'          => 'Peržiūrėti svetainę',
    'newPost'           => 'Naujas įrašas',
    'buyNow'            => 'Pirkti dabar',
    'visitStore'        => 'Apsilankyti parduotuvėje',
    'loadMore'          => 'Įkelti daugiau',

    // Table headers / labels
    'title'             => 'Pavadinimas',
    'name'              => 'Vardas',
    'slug'              => 'Nuoroda',
    'status'            => 'Būsena',
    'date'              => 'Data',
    'actions'           => 'Veiksmai',
    'author'            => 'Autorius',
    'views'             => 'Peržiūros',
    'type'              => 'Tipas',
    'url'               => 'URL',
    'description'       => 'Aprašymas',
    'role'              => 'Vaidmuo',
    'email'             => 'El. paštas',
    'username'          => 'Naudotojo vardas',
    'active'            => 'Aktyvus',
    'version'           => 'Versija',
    'size'              => 'Dydis',
    'clicks'            => 'Paspaudimai',
    'total'             => 'Iš viso',
    'platform'          => 'Platforma',
    'label'             => 'Etiketė',
    'order'             => 'Tvarka',
    'source'            => 'Šaltinis',
    'content'           => 'Turinys',
    'excerpt'           => 'Santrauka',
    'details'           => 'Detalės',
    'contentType'       => 'Turinio tipas',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta pavadinimas',
    'metaDescription'   => 'Meta aprašymas',

    // Status badges
    'published'         => 'Paskelbta',
    'draft'             => 'Juodraštis',
    'scheduled'         => 'Suplanuota',
    'pending'           => 'Laukiama',
    'safe'              => 'Saugu',
    'notSafe'           => 'Nesaugu',
    'malicious'         => 'Kenkėjiškas',
    'safetyUnknown'     => 'Nežinomas',
    'inactive'          => 'Neaktyvus',
    'installed'         => 'Įdiegta',
    'free'              => 'Nemokama',
    'premium'           => 'Premium',
    'all'               => 'Visi',

    // Confirmations
    'confirmDelete'         => 'Ar tikrai norite ištrinti šį elementą?',
    'confirmDeletePost'     => 'Ištrinti šį įrašą?',
    'confirmDeletePage'     => 'Ištrinti šį puslapį?',
    'confirmDeleteComment'  => 'Ištrinti šį komentarą visam laikui?',
    'confirmDeleteUser'     => 'Ištrinti šį naudotoją?',
    'confirmDeleteMedia'    => 'Ištrinti?',
    'confirmDeleteBackup'   => 'Ištrinti šią atsarginę kopiją?',
    'confirmBulkAction'     => 'Taikyti masinį veiksmą pažymėtiems įrašams?',

    // Empty states
    'noPostsYet'        => 'Dar nėra įrašų. {0}',
    'noResultsFound'    => 'Rezultatų nerasta.',
    'noCommentsYet'     => 'Nėra laukiančių komentarų.',
    'noMediaYet'        => 'Dar nėra medijos.',
    'noItemsFound'      => 'Rinkoje elementų nerasta.',
    'noCategoriesYet'   => 'Dar nėra kategorijų.',
    'noTagsYet'         => 'Dar nėra žymų.',
    'noRevisionsYet'    => 'Redakcijų nerasta.',

    // Misc common
    'permissionDenied'  => 'Prieiga uždrausta.',
    'notFound'          => 'Įrašas nerastas.',
    'commasSeparated'   => 'Atskirta kableliais',
    'optional'          => 'Neprivaloma',
    'required'          => 'Privaloma',
    'enabled'           => 'Įjungta',
    'selected'          => '{0} įrašas (-ai) pasirinktas (-i)',
    'published_count'   => '{0} paskelbta',
    'pending_count'     => '{0} laukiama',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Skydelis',
    'navContent'        => 'Turinys',
    'navAppearance'     => 'Išvaizda',
    'navUsersAndSite'   => 'Naudotojai ir svetainė',
    'navTools'          => 'Įrankiai',
    'navMarketplace'    => 'Rinka',
    'navPlugins'        => 'Įskiepiai',
    'navPosts'          => 'Įrašai',
    'navSchedule'       => 'Tvarkaraštis',
    'navPages'          => 'Puslapiai',
    'navCategories'     => 'Kategorijos',
    'navTags'           => 'Žymos',
    'navComments'       => 'Komentarai',
    'navMedia'          => 'Medija',
    'navImport'         => 'Importuoti',
    'navThemes'         => 'Temos',
    'navWidgets'        => 'Valdikliai',
    'navNavigation'     => 'Naršymas',
    'navUsers'          => 'Naudotojai',
    'navSocialLinks'    => 'Socialiniai tinklai',
    'navRedirects'      => 'Peradresavimai',
    'navLanguages'      => 'Kalbos',
    'navSettings'       => 'Nustatymai',
    'navAnalytics'      => 'Analizė',
    'navAffiliates'     => 'Filialų nuorodos',
    'navBrokenLinks'    => 'Sugedusios nuorodos',
    'navActivityLog'    => 'Veiklos žurnalas',
    'navBackup'         => 'Atsarginė kopija ir eksportas',
    'navUpdates'        => 'Atnaujinimai',
    'navBrowse'         => 'Naršyti',
    'navLicenses'       => 'Licencijos',
    'navPubvanaStore'   => 'Pubvana parduotuvė',
    'navUpdateAvailable'=> 'Yra atnaujinimas',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Ar norite atsijungti?',
    'logoutModalBody'   => 'Pasirinkite „Atsijungti" žemiau, kad užbaigtumėte sesiją.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Skydelis',
    'dashStats'             => 'Statistika',
    'dashPosts'             => 'Įrašai',
    'dashPages'             => 'Puslapiai',
    'dashComments'          => 'Komentarai',
    'dashUsers'             => 'Naudotojai',
    'dashRecentPosts'       => 'Naujausi įrašai',
    'dashPendingComments'   => 'Laukiantys komentarai',
    'dashViewAll'           => 'Žiūrėti visus',
    'dashCreateOne'         => 'Sukurkite vieną!',
    'dashNoPosts'           => 'Dar nėra įrašų.',
    'dashNoPendingComments' => 'Nėra laukiančių komentarų.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Įrašai',
    'newPostTitle'          => 'Naujas įrašas',
    'editPostTitle'         => 'Redaguoti įrašą: {0}',
    'copyPreviewLink'       => 'Kopijuoti peržiūros nuorodą',
    'backToPosts'           => 'Grįžti į įrašus',
    'postTitleField'        => 'Pavadinimas *',
    'postEditor'            => 'Redaktorius',
    'postHtmlEditor'        => 'HTML redaktorius',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Santrauka',
    'postExcerptPlaceholder'=> 'Neprivaloma trumpa santrauka...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta pavadinimas',
    'postMetaDescription'   => 'Meta aprašymas',
    'postPublishSection'    => 'Publikuoti',
    'postStatus'            => 'Būsena',
    'postStatusDraft'       => 'Juodraštis',
    'postStatusPublished'   => 'Paskelbta',
    'postStatusScheduled'   => 'Suplanuota',
    'postScheduledAt'       => 'Suplanuota data ir laikas',
    'postFeatured'          => 'Pagrindinis įrašas',
    'postMembersOnly'       => 'Tik nariams',
    'postShareOnPublish'    => 'Bendrinti socialiniuose tinkluose paskelbus',
    'postSaveBtn'           => 'Išsaugoti įrašą',
    'postFeaturedImage'     => 'Pagrindinis paveikslėlis',
    'postFeaturedImagePlaceholder' => 'URL arba įkėlimo kelias…',
    'postCategories'        => 'Kategorijos',
    'postTags'              => 'Žymos',
    'postTagsPlaceholder'   => 'žyma1, žyma2, žyma3',
    'postRevisions'         => 'Redakcijos',
    'postRevisionCount'     => '{0} redakcija (-os)',
    'postPreview'           => 'Peržiūra',
    'postBulkAction'        => '- Pasirinkti veiksmą -',
    'postBulkPublish'       => 'Paskelbti',
    'postBulkUnpublish'     => 'Atšaukti paskelbimą (perkelti į juodraštį)',
    'postBulkDelete'        => 'Ištrinti',

    // Post flash messages
    'postCreated'           => 'Įrašas sėkmingai sukurtas.',
    'postUpdated'           => 'Įrašas atnaujintas.',
    'scheduledDateMustBeFuture' => 'Suplanuota data turi būti ateityje.',
    'postDeleted'           => 'Įrašas ištrintas.',
    'postBulkUpdated'       => '{0} įrašas (-ai) atnaujintas (-i).',
    'postBulkInvalid'       => 'Netinkamas masinis veiksmas.',
    'postPermission'        => 'Galite redaguoti tik savo įrašus.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Redakcijos: {0}',
    'revisionTitle'         => 'Redakcija — {0}',
    'revisionShowTitle'     => 'Redakcija',
    'revisionsBackToPost'   => 'Grįžti į įrašą',
    'revisionsBackToList'   => 'Grįžti į redakcijų sąrašą',
    'revisionRestored'      => 'Įrašas atkurtas į redakciją iš {0}.',
    'revisionRestoreBtn'    => 'Atkurti šią redakciją',
    'revisionSaved'         => 'Išsaugota',
    'revisionBy'            => 'Autorius',
    'revisionOn'            => 'Data',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Puslapiai',
    'newPageTitle'          => 'Naujas puslapis',
    'editPageTitle'         => 'Redaguoti puslapį',
    'pageSlugInUse'         => 'Nuoroda „{0}" jau naudojama.',
    'pageCannotDelete'      => 'Negalima ištrinti šio puslapio.',
    'slugAutoGenHint'       => 'automatiškai generuojama iš pavadinimo, jei palikta tuščia',
    'slugCannotChange'      => 'negalima pakeisti',
    'colSystem'             => 'Sistema',
    'system'                => 'Sistema',

    // Page flash messages
    'pageCreated'           => 'Puslapis sukurtas.',
    'pageUpdated'           => 'Puslapis atnaujintas.',
    'pageDeleted'           => 'Puslapis ištrintas.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Kategorijos',
    'newCategoryTitle'      => 'Nauja kategorija',
    'editCategoryTitle'     => 'Redaguoti kategoriją',
    'categoryName'          => 'Pavadinimas',
    'categoryDescription'   => 'Aprašymas',
    'categoryPostCount'     => 'Įrašų skaičius',

    // Category flash messages
    'categoryCreated'       => 'Kategorija sukurta.',
    'categoryUpdated'       => 'Kategorija atnaujinta.',
    'categoryDeleted'       => 'Kategorija ištrinta.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Žymos',
    'tagPostCount'          => 'Įrašų skaičius',

    // Tag flash messages
    'tagDeleted'            => 'Žyma ištrinta.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Komentarai',
    'commentAuthor'         => 'Autorius',
    'commentContent'        => 'Komentaras',
    'commentPost'           => 'Įrašas',
    'commentDate'           => 'Data',
    'commentStatusFilter'   => 'Filtruoti pagal būseną',

    // Comment flash messages
    'commentApproved'       => 'Komentaras patvirtintas.',
    'commentSpam'           => 'Pažymėta kaip šlamštas.',
    'commentTrashed'        => 'Komentaras perkeltas į šiukšlinę.',
    'commentDeleted'        => 'Komentaras ištrintas visam laikui.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Medijos biblioteka',
    'mediaTitle'            => 'Pavadinimas',
    'mediaAltText'          => 'Alternatyvus tekstas',
    'mediaAltPlaceholder'   => 'Apibūdinkite vaizdą prieinamumo tikslais',
    'mediaTitlePlaceholder' => 'Neprivalomas vaizdo pavadinimas',
    'mediaImageDetails'     => 'Vaizdo detalės',
    'mediaSaved'            => 'Išsaugota!',
    'mediaNoSelection'      => 'Vaizdas nepasirinktas',
    'mediaBrowse'           => 'Naršyti mediją',
    'mediaRemove'           => 'Pašalinti',
    'mediaUseImage'         => 'Naudoti šį vaizdą',
    'mediaDropzone'         => 'Nuvilkite vaizdą čia arba spustelėkite naršyti',
    'mediaLoading'          => 'Kraunama medija…',
    'mediaEmpty'            => 'Dar neįkelta medijos.',
    'mediaUpload'           => 'Įkelti mediją',
    'mediaDragDrop'         => 'Nuvilkite failus čia arba',
    'mediaChooseFiles'      => 'Pasirinkti failus',
    'mediaUploading'        => 'Įkeliama…',
    'mediaFilename'         => 'Failo pavadinimas',
    'mediaSize'             => 'Dydis',
    'mediaUploadFailed'     => 'Įkėlimas nepavyko: {0}',
    'mediaUploadError'      => 'Įkėlimo klaida: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Medija ištrinta.',
    'mediaNoValidFile'      => 'Neįkeltas tinkamas failas.',
    'mediaUploadSuccess'    => 'Failas sėkmingai įkeltas.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Naršymas',
    'navQuickAdd'           => 'Greitai pridėti',
    'navQuickAddPlaceholder' => 'Ieškoti puslapių, kategorijų, įskiepių...',
    'navItemLabel'          => 'Etiketė',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Tikslas',
    'navItemOrder'          => 'Rikiavimo tvarka',
    'navGroupPrimary'       => 'Pagrindinis',
    'navGroupFooter'        => 'Poraštė',
    'navSelectGroup'        => 'Pasirinkite naršymo grupę:',
    'navParent'             => 'Tėvinis elementas',
    'navTopLevel'           => '— Aukščiausias lygis —',
    'navSameWindow'         => 'Tas pats langas',
    'navNewWindow'          => 'Naujas langas',
    'navMenuItems'          => 'Meniu elementai',
    'navNoItems'            => 'Šiame meniu nėra elementų.',
    'dragToReorder'         => 'Vilkti, kad pertvarkyti',

    // Navigation flash messages
    'navItemAdded'          => 'Naršymo elementas pridėtas.',
    'navItemRemoved'        => 'Naršymo elementas pašalintas.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Temos',
    'themeOptions'          => 'Temos parinktys',
    'themeActivate'         => 'Aktyvuoti',
    'themeOptionsBtn'       => 'Parinktys',
    'themeActive'           => 'Aktyvus',
    'themeBy'               => 'Autorius',
    'themeSupport'          => 'Palaikymas',
    'themeVersion'          => 'Versija',
    'themeSaveOptions'      => 'Išsaugoti parinktis',
    'themeInvalidLicense'   => 'Negalima aktyvuoti temos – licencija netinkama. Įdiekite iš naujo arba susisiekite su palaikymo tarnyba.',
    'themeValidationFailed' => 'Tema turi PHP kodo ir negali būti aktyvuota.',
    'noThemesInstalled'     => 'Temų neįdiegta. Apsilankykite rinkoje, kad gautumėte temų.',
    'themeUnapprovedTitle'  => 'Aktyvuoti nepatvirtintą temą?',
    'themeNotApproved'      => 'Ši tema nebuvo patvirtinta Pubvana.',
    'themeUnapprovedRisk'   => 'Nepatvirtintų temų aktyvavimas gali sukelti saugumo riziką ar suderinamumo problemų.',
    'themeActivateConfirm'  => 'Ar tikrai norite ją aktyvuoti?',
    'themeActivateAnyway'   => 'Aktyvuoti vis tiek',
    'themeNoOptions'        => 'Ši tema neturi konfigūruojamų parinkčių.',
    'themeCustomize'        => 'Tinkinti temą',

    // Theme flash messages
    'themeActivated'        => 'Tema aktyvuota.',
    'themeOptionsSaved'     => 'Parinktys išsaugotos.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Licencijuota',
    'licenseCheckNow'        => 'Tikrinti dabar',
    'licenseExpired'         => 'Pasibaigusi',
    'licenseEnterKey'        => 'Įvesti raktą',
    'licenseChangeKey'       => 'Keisti',
    'licenseRenew'           => 'Atnaujinti',
    'licenseThirdParty'      => 'Trečioji šalis',
    'unchecked'              => 'Netikrinta',
    'safetyLabel'            => 'Saugumas:',
    'recheckBtn'             => 'Patikrinti iš naujo',
    'recheckSuccess'         => 'Saugumo patikra atnaujinta.',
    'recheckFailed'          => 'Nepavyko pasiekti tikrinimo serverio. Bandykite vėliau.',
    'recheckNotFound'        => 'Elementas nerastas.',
    'widgetBlockedMalicious' => '{0} pažymėtas kaip kenkėjiškas ir negali būti pridėtas.',
    'licenseNoStoreProduct'  => 'Šis elementas nesusietas su parduotuvės produktu. Jei įsigijote šį elementą, iš naujo įdiekite jį iš marketplace, kad įgalintumėte licencijavimą.',
    'securityWarning'        => 'Saugumo įspėjimas:',
    'licenseModalTitle'      => 'Įvesti licencijos raktą',
    'licenseModalBody'       => 'Įklijuokite licencijos raktą žemiau.',
    'licenseModalSave'       => 'Išsaugoti',
    'licenseSaved'           => 'Licencijos raktas išsaugotas ir patvirtintas.',
    'licenseInvalid'         => 'Licencijos raktas netinkamas.',
    'licenseKeyRequired'     => 'Licencijos raktas ir produktas yra privalomi.',
    'licenseCheckFailed'     => 'Nepavyko pasiekti licencijų serverio. Bandykite vėliau.',
    'licenseProductNotFound' => 'Nepavyko rasti šio elemento parduotuvėje.',
    'btnCancel'              => 'Atšaukti',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Valdikliai',
    'widgetConfigureTitle'  => 'Konfigūruoti valdiklį',
    'widgetAreas'           => 'Valdiklių sritys',
    'widgetAvailable'       => 'Galimi valdikliai',
    'widgetAddToArea'       => 'Pridėti į sritį',
    'widgetArea'            => 'Sritis',
    'widgetNoOptions'       => 'Nėra parinkčių.',
    'widgetSaveConfig'      => 'Išsaugoti konfigūraciją',
    'widgetConfigure'       => 'Konfigūruoti',
    'widgetNoAreas'         => 'Valdiklių sričių nerasta. Aktyvuokite temą, kad įjungtumėte valdiklių sritis.',
    'widgetAreaEmpty'       => 'Šioje srityje nėra valdiklių. Pridėkite vieną iš sąrašo →',

    // Widget flash messages
    'widgetAdded'           => 'Valdiklis pridėtas.',
    'widgetRemoved'         => 'Valdiklis pašalintas.',
    'widgetConfigured'      => 'Valdiklis sukonfigūruotas.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Rinka',
    'marketplaceRefresh'    => 'Atnaujinti',
    'marketplaceVisitStore' => 'Apsilankyti parduotuvėje',
    'marketplaceAll'        => 'Visi',
    'marketplaceThemes'     => 'Temos',
    'marketplaceWidgets'    => 'Valdikliai',
    'marketplacePlugins'    => 'Įskiepiai',
    'marketplaceUpdatesAvailable' => '{0} atnaujinimas (-ai) pasiekiamas (-i).',
    'marketplaceBy'         => 'Autorius',
    'marketplaceFree'       => 'Nemokama',
    'marketplaceInstalled'  => 'Įdiegta',
    'marketplaceInstall'    => 'Įdiegti',
    'marketplaceBuyNow'     => 'Pirkti dabar',
    'marketplaceNoItems'    => 'Rinkoje elementų nerasta.',
    'marketplaceInstalledVersion' => 'v{0} įdiegta',
    'marketplaceLoadError'  => 'Nepavyko įkelti produktų iš parduotuvės. Bandykite vėliau.',
    'byAuthor'              => 'Autorius: {0}',
    'unknown'               => 'Nežinoma',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} sėkmingai įdiegta.',
    'marketplaceInstallFail'    => 'Diegimas nepavyko. Patikrinkite žurnalus.',
    'marketplaceUpdateSuccess'  => 'Sėkmingai atnaujinta.',
    'marketplaceUpdateFail'     => 'Atnaujinimas nepavyko.',
    'marketplaceCacheRefreshed' => 'Rinkos talpykla atnaujinta.',
    'marketplaceInvalidRequest' => 'Netinkama diegimo užklausa.',
    'marketplaceCannotUpdate'   => 'Negalima atnaujinti šio elemento.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licencijos',
    'licensesNone'                => 'Nėra licencijų',
    'licensesProduct'             => 'Produktas',
    'licensesKey'                 => 'Licencijos raktas',
    'licensesStatus'              => 'Būsena',
    'licensesType'                => 'Tipas',
    'licensesExpires'             => 'Galioja iki',
    'licensesDomain'              => 'Domenas',
    'licensesInstalled'           => 'Įdiegta',
    'licensesLastChecked'         => 'Paskutinį kartą tikrinta',
    'licensesActions'             => 'Veiksmai',
    'licensesStatusValid'         => 'Galioja',
    'licensesStatusInvalid'       => 'Negalioja',
    'licensesStatusExpired'       => 'Pasibaigusi',
    'licensesStatusSubExpired'    => 'Prenumerata pasibaigusi',
    'licensesStatusUnchecked'     => 'Netikrinta',
    'licensesSubscription'        => 'Prenumerata',
    'licensesOneTime'             => 'Vienkartinis',
    'licensesPerpetual'           => 'Amžinas',
    'licensesNotInstalled'        => 'Neįdiegta',
    'licensesNever'               => 'Niekada',
    'licensesRevalidate'          => 'Patikrinti iš naujo',
    'licenseKeyPlaceholder'       => 'Įvesti licencijos raktą...',
    'marketplaceLicensesEmpty'    => 'Licencijuoti produktai pasirodys čia po įdiegimo.',
    'typeTheme'                   => 'Tema',
    'typeWidget'                  => 'Valdiklis',
    'typePlugin'                  => 'Įskiepis',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Licencija sėkmingai patvirtinta.',
    'licenseRevalidateInvalid'     => 'Licencija negalioja arba pasibaigusi.',
    'licenseRevalidateUnreachable' => 'Nepavyko pasiekti licencijų serverio. Bandykite vėliau.',
    'licenseRevalidateSkipped'     => 'Licencijos patikrinimas praleistas (kūrimo režimas).',
    'licenseRevalidateNotFound'    => 'Licencija nerasta.',

    // License warning banners
    'licenseWarningTitle'   => 'Licencijos problemos',
    'licenseWarningInvalid' => 'licencija negalioja arba pasibaigusi',
    'licenseWarningManage'  => 'Tvarkyti licencijas',

    // Plugin license
    'pluginInvalidLicense' => 'Šis įskiepis turi netinkamą arba pasibaigusią licenciją ir negali būti aktyvuotas.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Licencijos raktas',
    'storeBrowseFull'       => 'Naršyti visą parduotuvę',
    'storeBackToMarketplace'=> 'Grįžti į rinką',
    'storeNoProducts'       => 'Nėra galimų produktų.',
    'storeViewInStore'      => 'Peržiūrėti parduotuvėje',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Naudotojai',
    'editUserTitle'         => 'Redaguoti naudotoją',
    'createUserTitle'       => 'Sukurti naudotoją',
    'authorProfileTitle'    => 'Autoriaus profilis',
    'userRoleLabel'         => 'Vaidmuo',
    'userActiveLabel'       => 'Aktyvus',
    'userPasswordLabel'     => 'Slaptažodis',
    'userPasswordOptional'  => 'Palikite tuščią, kad nepakeisite slaptažodžio',
    'userDisplayName'       => 'Rodomas vardas',
    'userBio'               => 'Biografija',
    'userWebsite'           => 'Svetainė',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avataras',
    'userSaveProfile'       => 'Išsaugoti profilį',
    'userSaveChanges'       => 'Išsaugoti pakeitimus',
    'userCannotDeleteSelf'  => 'Negalite ištrinti savęs.',
    'userCannotDeleteOwner' => 'Svetainės savininko paskyros negalima ištrinti.',
    'userOwnerCannotModify' => 'Svetainės savininko paskyros negalima keisti.',

    // User flash messages
    'userCreated'           => 'Naudotojas sukurtas.',
    'userUpdated'           => 'Naudotojas atnaujintas.',
    'userDeleted'           => 'Naudotojas ištrintas.',
    'userBanned'            => 'Naudotojas užblokuotas.',
    'userUnbanned'          => 'Naudotojo blokavimas panaikintas.',
    'userCannotBanSelf'     => 'Negalite užblokuoti savęs ar svetainės savininko.',
    'banStatus'             => 'Blokavimo būsena',
    'banned'                => 'Užblokuotas',
    'ban'                   => 'Užblokuoti naudotoją',
    'unban'                 => 'Atblokuoti',
    'banReasonRequired'     => 'Blokavimo priežastis yra privaloma.',
    'banReasonPlaceholder'  => 'Blokavimo priežastis...',
    'confirmBanUser'        => 'Ar tikrai norite užblokuoti šį naudotoją?',
    'userProfileSaved'      => 'Profilis išsaugotas.',
    'userAvatarUploadFail'  => 'Avataro įkėlimas nepavyko: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA sąranka',
    'tfaSetupHeading'       => 'Nustatyti dviejų veiksnių autentifikaciją',
    'tfaScanQr'             => 'Nuskaitykite QR kodą žemiau naudodami autentifikatoriaus programą (pvz., Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Arba įveskite slaptąjį raktą rankiniu būdu:',
    'tfaEnterCode'          => 'Įveskite 6 skaitmenų kodą iš programos patvirtinimui:',
    'tfaCodeLabel'          => 'Autentifikacijos kodas',
    'tfaConfirmBtn'         => 'Patvirtinti ir įjungti 2FA',
    'tfaDisableBtn'         => 'Išjungti 2FA',
    'tfaDisableConfirm'     => 'Įveskite dabartinį 2FA kodą, kad išjungtumėte:',
    'tfaEnabled'            => 'Dviejų veiksnių autentifikacija įjungta.',
    'tfaDisabled'           => 'Dviejų veiksnių autentifikacija išjungta.',
    'tfaInvalidCode'        => 'Netinkamas kodas – nuskaitykite QR kodą ir bandykite dar kartą.',
    'tfaInvalidDisable'     => 'Netinkamas kodas – 2FA nebuvo išjungta.',
    'tfaSessionExpired'     => 'Sąrankos sesija baigėsi – pradėkite iš naujo.',
    'tfaNotEnabled'         => '2FA šiuo metu neįjungta.',
    'tfaCantScan'           => 'Negalite nuskaityti? Įveskite šį kodą rankiniu būdu:',
    'tfaWarning'            => 'Išsaugokite šį slaptąjį raktą saugioje vietoje. Jums jo reikės, jei prarasite prieigą prie autentifikatoriaus įrenginio.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Socialiniai tinklai',
    'socialPlatform'           => 'Platforma',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Piktograma',
    'socialSortOrder'          => 'Rikiavimo tvarka',
    'socialIconPackInfo'       => 'Dabartinė tema <strong>{0}</strong> naudoja <strong>{1}</strong> (v{2}) piktogramoms. Žemiau galite pasirinkti piktogramas, kurios bus rodomos socialinių tinklų funkcijoje.',
    'socialSearchPlaceholder'  => 'Ieškoti platformų...',
    'socialIconDisclaimer'     => 'Šios piktogramos tik vaizduoja naudojamą piktogramą. Faktinė piktograma gali skirtis priklausomai nuo aktyvios temos piktogramų rinkinio.',

    // Social flash messages
    'socialLinkAdded'       => 'Socialinio tinklo nuoroda pridėta.',
    'socialLinkUpdated'     => 'Nuoroda atnaujinta.',
    'socialLinkDeleted'     => 'Nuoroda ištrinta.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Peradresavimai',
    'redirectFrom'          => 'Iš URL',
    'redirectTo'            => 'Į URL',
    'redirectType'          => 'Tipas',
    'redirectAdd'           => 'Pridėti peradresavimą',
    'redirectFromHint'      => '(santykinis, pvz. /senas-puslapis)',
    'redirect301'           => '301 Nuolatinis',
    'redirect302'           => '302 Laikinas',
    'redirectInvalidDest'   => 'Netinkamas peradresavimo tikslo URL.',

    // Redirect flash messages
    'redirectAdded'         => 'Peradresavimas pridėtas.',
    'redirectDeleted'       => 'Peradresavimas ištrintas.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Nustatymai',
    'settingsGeneral'       => 'Bendrieji',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'El. paštas',
    'settingsSocialLogin'   => 'Socialinis prisijungimas',
    'settingsSocialSharing' => 'Bendrinimas socialiniuose tinkluose',
    'settingsSpam'          => 'Apsauga nuo šlamšto',

    'generalSettingsHeading'    => 'Bendrieji nustatymai',
    'generalSiteName'           => 'Svetainės pavadinimas',
    'generalTagline'            => 'Šūkis',
    'generalAdminEmail'         => 'Administratoriaus el. paštas',
    'generalPostsPerPage'       => 'Įrašų per puslapį',
    'generalComments'           => 'Komentarai',
    'generalCommentsEnable'     => 'Įjungti komentarus',
    'generalCommentModeration'  => 'Reikalauti moderavimo prieš paskelbimą',
    'generalMaintenanceMode'    => 'Techninės priežiūros režimas',
    'generalMaintenanceEnable'  => 'Įjungti techninės priežiūros režimą',
    'generalMaintenanceHelp'    => 'Lankytojai mato puslapį „Greitai grįšime". Administratoriai vis tiek gali pasiekti svetainę.',
    'generalFrontPage'          => 'Pagrindinis puslapis',
    'generalFrontPageBlog'      => 'Tinklaraščio indeksas (naujausi įrašai)',
    'generalFrontPageStatic'    => 'Statinis puslapis:',
    'generalFrontPagePlugin'    => 'Įskiepio puslapis:',
    'generalSelectPage'         => '- Pasirinkti puslapį -',
    'generalSelectRoute'        => '- Pasirinkti maršrutą -',
    'generalFrontPageNoPlugins' => 'Nėra galimų įskiepių maršrutų',
    'generalPageCacheTtl'       => 'Puslapio talpyklos TTL',
    'settingsCacheTtlHint'      => 'Sekundės. 0 = išjungta.',
    'generalSaveBtn'            => 'Išsaugoti bendruosius nustatymus',

    // General flash messages
    'generalSettingsSaved'      => 'Bendrieji nustatymai išsaugoti.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO nustatymai',
    'seoMetaDescription'        => 'Meta aprašymas',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Svetainės žemėlapis',
    'seoSitemapEnable'          => 'Įjungti sitemap.xml',
    'seoSitemapHelp'            => 'Standartinis svetainės žemėlapis visiems paskelbtiems įrašams ir puslapiams.',
    'seoNewsSitemap'            => 'Įjungti news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Google News svetainės žemėlapis – nurodo per paskutines 48 valandas paskelbtus įrašus.',
    'seoSaveBtn'                => 'Išsaugoti SEO nustatymus',
    'seoSettingsSaved'          => 'SEO nustatymai išsaugoti.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'El. pašto nustatymai',
    'emailFromName'             => 'Siuntėjo vardas',
    'emailFromAddress'          => 'Siuntėjo adresas',
    'emailProtocol'             => 'Protokolas',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP serveris',
    'emailSmtpPort'             => 'SMTP prievadas',
    'emailSmtpEncryption'       => 'Šifravimas',
    'emailSmtpEncryptionNone'   => 'Nėra',
    'emailSmtpUsername'         => 'SMTP naudotojo vardas',
    'emailSmtpPassword'         => 'SMTP slaptažodis',
    'emailSaveBtn'              => 'Išsaugoti el. pašto nustatymus',
    'emailSettingsSaved'        => 'El. pašto nustatymai išsaugoti.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Socialinis prisijungimas (OAuth)',
    'socialLoginHelp'           => 'Duomenys išsaugomi jūsų .env faile. Užregistruokite programą „Google" ir „Facebook", kad gautumėte kliento ID ir slaptus raktus.',
    'socialLoginGoogleId'       => 'Kliento ID',
    'socialLoginGoogleSecret'   => 'Kliento slaptasis raktas',
    'socialLoginFbAppId'        => 'Programos ID',
    'socialLoginFbAppSecret'    => 'Programos slaptasis raktas',
    'socialLoginPlaceholderSecret' => '(palikite tuščią, kad išsaugotumėte esamą)',
    'socialLoginSaveBtn'        => 'Išsaugoti socialinio prisijungimo nustatymus',
    'socialLoginSettingsSaved'  => 'Socialinio prisijungimo nustatymai išsaugoti.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Automatinis bendrinimas socialiniuose tinkluose paskelbus',
    'socialSharingHelp'         => 'Kai įrašas paskelbiamas su pažymėtu „Bendrinti paskelbus", Pubvana automatiškai paskelbia sukonfigūruotose socialinių tinklų paskyrose.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Gaukite raktus adresu developer.twitter.com → Jūsų programa → Raktai ir žetonai.',
    'socialSharingApiKey'       => 'API raktas',
    'socialSharingApiSecret'    => 'API slaptasis raktas',
    'socialSharingAccessToken'  => 'Prieigos žetonas',
    'socialSharingAccessSecret' => 'Prieigos slaptasis raktas',
    'socialSharingFbPage'       => 'Facebook puslapis',
    'socialSharingFbPageHelp'   => 'Reikalauja puslapio prieigos žetono su pages_manage_posts leidimais.',
    'socialSharingFbPageId'     => 'Puslapio ID',
    'socialSharingFbPageToken'  => 'Puslapio prieigos žetonas',
    'socialSharingSaveBtn'      => 'Išsaugoti bendrinimo nustatymus',
    'socialSharingSettingsSaved'=> 'Socialinio tinklo bendrinimo nustatymai išsaugoti.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Apsauga nuo šlamšto (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana naudoja hCaptcha (privatumą gerbianti, ne Google) apsaugoti komentarų formas ir kontaktų formą nuo šlamšto botų.',
    'spamHcaptchaFree'          => 'hCaptcha yra nemokama daugumai svetainių. Prisiregistruokite adresu hcaptcha.com, sukurkite svetainę ir įveskite raktus žemiau.',
    'spamHcaptchaSiteKey'       => 'Svetainės raktas',
    'spamHcaptchaSecretKey'     => 'Slaptasis raktas',
    'spamHcaptchaNote'          => 'Jei šie raktai nenustatyti, hCaptcha tyliai praleistas – saugu vietiniam kūrimui. Kai išsaugota, valdiklis automatiškai atsiranda komentarų formoje ir kontaktų puslapyje.',
    'spamSettingsSaved'         => 'Apsaugos nuo šlamšto nustatymai išsaugoti.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Kalbos',
    'languageCode'              => 'Kodas',
    'languageName'              => 'Pavadinimas',
    'languageDefault'           => 'Numatytoji',
    'languageEnabled'           => 'Įjungta',
    'languageMakeDefault'       => 'Nustatyti kaip numatytąją',
    'languageSetAsDefault'      => '{0} nustatyta kaip numatytoji kalba.',
    'languageEnabled_msg'       => '{0} įjungta.',
    'languageDisabled_msg'      => '{0} išjungta.',
    'languageNotFound'          => 'Kalba nerasta.',
    'languageCannotDisable'     => 'Negalima išjungti numatytosios kalbos.',
    'languageDirection'         => 'Kryptis',
    'languageNativeName'        => 'Pavadinimas gimtąja kalba',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analizė',
    'analyticsTotalViews'       => 'Viso peržiūrų',
    'analyticsTopPosts'         => 'Populiariausi įrašai',
    'analyticsReferrers'        => 'Populiariausi persiuntėjai',
    'analyticsLast7'            => 'Paskutinės 7 dienos',
    'analyticsLast30'           => 'Paskutinės 30 dienų',
    'analyticsLast90'           => 'Paskutinės 90 dienų',
    'analyticsChartTitle'       => 'Puslapio peržiūros',
    'analyticsNoData'           => 'Nėra analizės duomenų šiam laikotarpiui.',
    'analyticsDomain'           => 'Domenas',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Filialų nuorodos',
    'newAffiliateLinkTitle'     => 'Nauja filialo nuoroda',
    'editAffiliateLinkTitle'    => 'Redaguoti filialo nuorodą',
    'affiliateName'             => 'Pavadinimas',
    'affiliateSlug'             => 'Nuoroda',
    'affiliateDestination'      => 'Tikslo URL',
    'affiliateActive'           => 'Aktyvus',
    'affiliateClicks'           => 'Paspaudimai',
    'affiliateClicksTitle'      => 'Paspaudimai – {0}',
    'affiliateTotal'            => 'Iš viso',
    'affiliateViewClicks'       => 'Peržiūrėti paspaudimus',

    // Affiliate flash messages
    'affiliateCreated'          => 'Filialo nuoroda sukurta.',
    'affiliateUpdated'          => 'Filialo nuoroda atnaujinta.',
    'affiliateDeleted'          => 'Filialo nuoroda ištrinta.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Sugedusios nuorodos',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP būsena',
    'brokenLinkError'           => 'Klaida',
    'brokenLinkSource'          => 'Šaltinis',
    'brokenLinkShowDismissed'   => 'Rodyti atmestas',
    'brokenLinkHideDismissed'   => 'Slėpti atmestas',
    'brokenLinkTimeout'         => 'Baigėsi laikas',
    'brokenLinkBroken'          => 'sugadinta',
    'brokenLinkNone'            => 'Sugedusių nuorodų nerasta.',
    'brokenLinkNowReachable'    => 'Nuoroda dabar pasiekiama – pašalinta iš rezultatų.',
    'brokenLinkStillBroken'     => 'Nuoroda vis dar sugadinta ({0}).',
    'brokenLinkDismissed'       => 'Nuoroda atmesta.',
    'brokenLinksCliHint'        => 'Paleiskite visą nuskaitymą iš komandinės eilutės, kad užpildytumėte šią ataskaitą: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} problema (-os) rasta (-os)',
    'brokenLinksCount'          => '{0} sugadinta',
    'brokenLinksRecheck'        => 'Patikrinti šį URL iš naujo',
    'brokenLinksDismiss'        => 'Atmesti (slėpti iš rezultatų)',
    'brokenLinksRunScan'        => 'Paleisti nuskaitymą',
    'brokenLinksScanComplete'   => 'Nuskaitymas baigtas: {0} nuorodų patikrinta, {1} sugadinta.',
    'timeout'                   => 'Baigėsi laikas',
    'typePost'                  => 'Įrašas',
    'typePage'                  => 'Puslapis',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Veiklos žurnalas',
    'activityLogType'           => 'Tipas',
    'activityLogAction'         => 'Veiksmas',
    'activityLogUser'           => 'Naudotojas',
    'activityLogDate'           => 'Data',
    'activityLogNote'           => 'Pastaba',
    'activityLogFilterAll'      => 'Visi tipai',
    'activityLogEmpty'          => 'Veiklos dar neužregistruota.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Atsarginė kopija ir eksportas',
    'backupDownload'            => 'Sukurti ir atsisiųsti atsarginę kopiją',
    'backupFiles'               => 'Galimos atsarginės kopijos',
    'backupFilename'            => 'Failo pavadinimas',
    'backupSize'                => 'Dydis',
    'backupDate'                => 'Sukurta',
    'backupGenerating'          => 'Kuriama atsarginė kopija…',
    'backupNoFiles'             => 'Nėra išsaugotų atsarginių kopijų.',
    'backupFailed'              => 'Atsarginės kopijos kūrimas nepavyko: {0}',
    'backupDeleted'             => 'Atsarginė kopija ištrinta.',
    'backupCannotDelete'        => 'Nepavyko ištrinti atsarginės kopijos.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP adresai saugomi kaip SHA-256 maišos – nėra saugomi jokie neapdoroti asmens duomenys.',
    'colTime'                   => 'Laikas',
    'colIpHash'                 => 'IP maišos kodas',
    'colReferrer'               => 'Persiuntėjas',
    'affiliateDirectReferrer'   => 'Tiesioginis',
    'affiliateNameHint'         => 'Vidinis žymeklis – lankytojams nerodomas.',
    'affiliateSlugHint'         => 'Tik raidės, skaičiai, brūkšneliai ir pabraukimas. Negalima pakeisti, kai nuorodos bendrinamos.',
    'affiliateDestHint'         => 'Turi prasidėti https://. Lankytojai bus 301 peradresuoti čia.',
    'affiliateInactiveHint'     => 'Neaktyvios nuorodos grąžina 404.',
    'affiliateLinkCount'        => '{0} nuorodos',
    'colDomain'                 => 'Domenas',
    'commentAll'                => 'Visi',
    'commentPending'            => 'Laukiama',
    'commentTrash'              => 'Šiukšlinė',
    'commentsNone'              => 'Nėra {0} komentarų.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Sukurti atsarginę kopiją',
    'backupStarting'            => 'Pradedama atsarginė kopija...',
    'backupNoneYet'             => 'Dar nėra atsarginių kopijų. Spustelėkite „Sukurti atsarginę kopiją", kad sukurtumėte pirmąją.',
    'backupsTitle'              => 'Atsarginės kopijos',
    'backupRetentionNote'       => 'Saugomos ne daugiau kaip 15 atsarginių kopijų – seniausios ištrinamos automatiškai.',
    'backupRestoreConfirm'      => 'Atkurti šią atsarginę kopiją? Pirmiausia bus sukurta dabartinės būsenos atsarginė kopija.',
    'backupDeleteConfirm'       => 'Ištrinti šią atsarginę kopiją?',
    'colFilename'               => 'Failo pavadinimas',
    'colVersion'                => 'Versija',
    'colTrigger'                => 'Suaktyvintojas',
    'colSize'                   => 'Dydis',
    'colDate'                   => 'Data',
    'colActions'                => 'Veiksmai',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Importuoti',
    'importWpHeading'           => 'Importuoti iš WordPress',
    'importWpHelp'              => 'Eksportuokite WordPress svetainę per Įrankiai → Eksportas, tada įkelkite .xml failą žemiau.',
    'importChooseFile'          => 'Pasirinkti WXR failą (.xml)',
    'importDryRun'              => 'Bandomasis paleidimas (tik peržiūra – niekas neišsaugoma)',
    'importRunBtn'              => 'Paleisti importavimą',
    'importNoValidFile'         => 'Įkelkite tinkamą WordPress WXR eksporto failą.',
    'importOnlyXml'             => 'Priimami tik .xml failai.',
    'importFileTooLarge'        => 'Importavimo failas per didelis. Maksimalus dydis 50 MB.',
    'importResultsHeading'      => 'Importavimo rezultatai',
    'importDryRunNote'          => 'Bandomasis paleidimas – duomenys neišsaugoti.',
    'importDryRunLabel'         => '(Bandomasis paleidimas – duomenys neįrašyti)',
    'importComplete'            => 'Importavimas baigtas',
    'importCreated'             => 'sukurta',
    'importSkipped'             => 'praleista',
    'importErrors'              => 'Klaidos:',
    'importInstructions'        => 'Eksportuokite WordPress turinį iš <strong>Įrankiai → Eksportas → Visas turinys</strong> ir įkelkite <code>.xml</code> failą čia. Pubvana importuos įrašus, puslapius, kategorijas, žymas, autorius ir komentarus.',
    'importCliTitle'            => 'CLI importavimas',
    'importCliHint'             => 'Taip pat galite paleisti importavimą iš komandinės eilutės:',
    'importCliDryRunHint'       => 'Žymeklis <code>--dry-run</code> rodo, kas būtų importuota, nerašant į duomenų bazę.',
    'importWhatTitle'           => 'Kas importuojama',
    'importItemPosts'           => 'Įrašai (pavadinimas, turinys, santrauka, nuoroda, būsena)',
    'importItemPages'           => 'Puslapiai',
    'importItemCategories'      => 'Kategorijos (su hierarchija)',
    'importItemTags'            => 'Žymos',
    'importItemAuthors'         => 'Autoriai (sukuriami kaip prenumeratoriaus paskyros)',
    'importItemComments'        => 'Komentarai',
    'importItemMedia'           => 'Medijos failai (URL išsaugomi turinyje)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Atnaujinimai',
    'updatesCurrentVersion'     => 'Dabartinė versija',
    'updatesLatestVersion'      => 'Naujausia versija',
    'updatesUpToDate'           => 'Pubvana yra atnaujinta.',
    'updatesAvailable'          => 'Yra atnaujinimas: {0}',
    'updatesCheckBtn'           => 'Tikrinti atnaujinimus',
    'updatesReleaseNotes'       => 'Versijos pastabos',
    'updatesHowToApply'         => 'Kaip pritaikyti atnaujinimą',
    'updatesCacheCleared'       => 'Atnaujinimo talpykla išvalyta – tikrinama iš naujo.',
    'updatesExtCapped'          => 'Yra atnaujinimas: {0} (saugu papildiniams)',
    'updatesNewerAvailable'     => 'Taip pat pasiekiama Pubvana {0} – atnaujinkite žemiau išvardytus papildinius, kad ją atrakintumėte.',

    // Addon Updates
    'updatesExtTitle'               => 'Papildiniai',
    'updatesExtCheckAll'            => 'Tikrinti visus',
    'updatesExtUpdateAll'           => 'Atnaujinti visus',
    'updatesExtCheckAllType'        => 'Tikrinti visus {0}',
    'updatesExtUpdateAllType'       => 'Atnaujinti visus {0}',
    'updatesExtNoInstalled'         => 'Nėra įdiegtų {0}.',
    'updatesExtColName'             => 'Pavadinimas',
    'updatesExtColVersion'          => 'Versija',
    'updatesExtColLatest'           => 'Naujausia',
    'updatesExtColAutoUpdate'       => 'Automatinis atnaujinimas',
    'updatesExtColStatus'           => 'Būsena',
    'updatesExtColActions'          => 'Veiksmai',
    'updatesExtBundled'             => 'Įtraukta į branduolį',
    'updatesExtNoSource'            => 'Nėra atnaujinimo šaltinio',
    'updatesExtFailed'              => 'Nepavyko',
    'updatesExtUpdatedAt'           => 'Atnaujinta {0}',
    'updatesExtAvailable'           => 'Yra atnaujinimas',
    'updatesExtUpToDate'            => 'Atnaujinta',
    'updatesExtUpdate'              => 'Atnaujinti',
    'updatesExtChecking'            => 'Tikrinama...',
    'updatesExtUpdating'            => 'Atnaujinama...',
    'updatesExtUpdated'             => 'Atnaujinta',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Patvirtinti atnaujinimą',
    'updatesConfirmBody'            => 'Bus sukurta atsarginė svetainės kopija, atsiųstas atnaujinimas ir pritaikytas.',
    'updatesConfirmSafe'            => 'Jūsų <code>.env</code>, <code>App.php</code> ir <code>Database.php</code> niekada neperrašomi.',
    'updatesConfirmBtn'             => 'Atnaujinti dabar',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Atnaujinti visus papildinius',
    'updatesExtAllBody'             => 'Bus atnaujinti visi papildiniai, kuriems yra laukiančių atnaujinimų.',
    'updatesExtAllNote'             => 'Papildiniai su išjungtu automatiniu atnaujinimu taip pat bus atnaujinti.',
    'updatesExtAllBtn'              => 'Atnaujinti visus',

    'updatesExtBadge'               => 'Atnaujinimas: v{0}',
    'updatesExtGoToUpdates'         => 'Atnaujinimai',

    // Update Settings
    'updatesSettingsTitle'          => 'Atnaujinimų nustatymai',
    'updatesAutoUpdateLabel'        => 'Pubvana automatinis atnaujinimas',
    'updatesAutoUpdateManual'       => 'Rankinis',
    'updatesAutoUpdateAuto'         => 'Automatinis',
    'updatesAutoUpdateHelp'         => 'Kai įjungta, Pubvana atnaujinimai be lūžio pakeitimų taikomi automatiškai.',
    'updatesCheckMethodLabel'       => 'Atnaujinimų tikrinimo metodas',
    'updatesCheckMethodPageload'    => 'Puslapio įkėlimas',
    'updatesCheckMethodCron'        => 'Cron užduotis',
    'updatesCheckMethodHelp'        => 'Puslapio įkėlimas tikrina kiekvieno prašymo metu (talpinama 24 val.). Cron reikalauja serverio cron užduoties.',
    'updatesCronCommand'            => 'Cron komanda',
    'updatesCronHelp'               => 'Pridėkite tai prie serverio crontab, kad paleistumėte atnaujinimų tikrinimą kasdien:',
    'updatesSettingsSaved'          => 'Atnaujinimų nustatymai išsaugoti.',

    // Compatibility
    'compatWarningTitle'            => 'Suderinamumo įspėjimas',
    'compatNotCompatible'           => 'Kai kurie įdiegti papildiniai nesuderinami su šia versija.',
    'compatRequiresUpdate'          => 'tačiau pirmiausia reikia atnaujinti šiuos papildinius:',
    'compatSupportsUpTo'            => 'palaiko iki {0}',
    'compatRequiresMin'             => 'reikalauja Pubvana {0}+',
    'compatNotDeclared'             => 'Šie papildiniai nedeklaravo suderinamumo su Pubvana {0}. Jie gali nustoti veikti po atnaujinimo:',
    'compatColType'                 => 'Tipas',
    'compatColName'                 => 'Pavadinimas',
    'compatColVersion'              => 'Suderinamumas',
    'compatRemoveHint'              => 'Galite pašalinti nesuderinamus papildinius arba perjungti į numatytąją temą, jei iškiltų problemų. Prieš kiekvieną atnaujinimą sukuriama atsarginė kopija.',
    'compatMaxVersion'              => 'Maksimali suderinama versija: {0}',
    'compatMinVersion'              => 'Reikalauja Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Įrašų tvarkaraštis',
    'scheduleNoScheduled'       => 'Nėra suplanuotų įrašų.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Redakcijos – {0}',
    'revisionPageTitle'         => 'Redakcija – {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Norėdami pasiekti administravimo skydelį, turite būti prisijungę.',
    'dirNotWritable'            => 'Katalogas nėra įrašomas: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} yra netinkamai sukonfigūruotas. Jei esate galutinis naudotojas, susisiekite su kūrėju. Jei esate kūrėjas, skaitykite dokumentaciją.',
    'addonMisconfiguredLink'    => '{0} yra netinkamai sukonfigūruotas. Jei esate galutinis naudotojas, <a href="{1}">susisiekite su kūrėju</a>. Jei esate kūrėjas, <a href="https://github.com/enlivenapp/pubvana">skaitykite dokumentaciją</a>.',
    'licenseExpiringSoon'       => '{0} licencija baigiasi {1}. {0} bus deaktyvuotas, kai pasibaigs licencija.',
    'licenseExpiredDeactivated' => '{0} buvo deaktyvuotas, nes licencija baigėsi.',
    'addonDeactivated'          => '{0} buvo deaktyvuotas. Priežastis: {1}.',
    'widgetValidationFailed'    => "Valdiklio ''{0}'' nepavyko patvirtinti. Susisiekite su kūrėju arba pašalinkite papildinį.",
    'widgetValidationFailedLink' => "Valdiklio ''{0}'' nepavyko patvirtinti. <a href=\"{1}\">Susisiekite su kūrėju</a> arba pašalinkite papildinį.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Deaktyvuotas: licencija baigėsi',
    'addonDeactivatedTampered'  => 'Deaktyvuotas: netinkamai sukonfigūruotas',
    'addonDeactivatedNoLicense' => 'Deaktyvuotas: nėra galiojančios licencijos',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Išjungta',
    'addonDisabledInvalidJson'  => 'Sistema: {0} turi netinkamą arba neskaitytiną {1}.',
    'addonDisabledMissingFields' => 'Sistema: {0} trūksta privalomų laukų: {1}.',
    'addonDisabledPhpFiles'     => 'Sistema: {0} turi PHP failų. Valdikliai turi būti tik JSON + šablonai.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'Norint aktyvuoti {0}, reikalinga galiojanti licencija.',
    'licenseInvalidActivation'  => 'Licencijos patvirtinimas nepavyko {0}. Patikrinkite licencijos raktą.',
    'licenseExpiredActivation'  => '{0} licencija baigėsi. Atnaujinkite, kad aktyvuotumėte.',
    'licenseCheckUnreachable'   => 'Nepavyko patikrinti {0} licencijos. Licencijų serveris nepasiekiamas. Bandykite vėliau.',
    'activationBlockedTampered' => '{0} negalima aktyvuoti, nes jis yra netinkamai sukonfigūruotas.',
    'activationBlockedBundled'  => '{0} negalima aktyvuoti: tik Pubvana papildiniai gali būti žymimi kaip įtrauktieji.',
    'activationBlockedNoUrls'   => '{0} negalima aktyvuoti: mokami papildiniai turi turėti licencijų patvirtinimo URL.',
    'activationBlockedFreeFlag' => '{0} negalima aktyvuoti: Pubvana papildiniai negali būti žymimi kaip nemokami.',
    'activationBlockedDisabled' => '{0} negalima aktyvuoti, nes jis turi konfigūracijos klaidų. Patikrinkite informacinį failą.',

    // Third-party license
    'licenseThirdPartyLabel'    => 'Trečioji šalis',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Pradedamas atnaujinimas...',
    'updateCheckLabel'           => 'Atnaujinimo patikrinimas:',
    'updateAvailable'            => 'Pubvana {0} yra pasiekiama!',
    'updateRunning'              => 'Jūs naudojate {0}.',
    'updateBreakingChanges'      => 'Lūžio pakeitimai',
    'updateMigrationNotes'       => 'Migracijos pastabos',
    'updateNotices'              => 'Pranešimai',
    'updatePreflightTitle'       => 'Išankstiniai patikrinimai',
    'updateToVersion'            => 'Atnaujinti į Pubvana {0}',
    'updatePreflightFailed'      => 'Vienas ar daugiau privalomų išankstinių patikrinimų nepavyko. Išspręskite juos prieš atnaujindami.',
    'updateUpToDate'             => 'Pubvana yra atnaujinta. Jūs naudojate versiją {0}.',
    'updateAnyway'               => 'Atnaujinti vis tiek',
    'updateAvailableTooltip'     => 'Pubvana {0} yra pasiekiama',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(jūs)',
    'usersNone'                  => 'Naudotojų nerasta.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Paskyra aktyvi',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Profilio detalės',
    'profileDisplayNameHint'     => 'Rodoma paskelbtų įrašų vietoje naudotojo vardo.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP arba GIF. Maks. 10 MB.',
    'profileSocialHandles'       => 'Socialinių tinklų paskyros',
    'preview'                    => 'Peržiūra',
    'website'                    => 'Svetainė',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Dviejų veiksnių autentifikacija',
    'totpActiveDesc'             => 'Jūsų paskyroje aktyvus TOTP dviejų veiksnių autentifikavimas. Kiekvieną kartą prisijungiant bus prašoma 6 skaitmenų kodo iš autentifikatoriaus programos.',
    'totpCurrentCode'            => 'Dabartinis kodas',
    'totpInactiveDesc'           => 'Pridėkite papildomą apsaugos sluoksnį savo paskyrai. Kai bus įjungta, kiekvieno prisijungimo metu reikės įvesti kodą iš autentifikatoriaus programos.',
    'totpEnable'                 => 'Įjungti dviejų veiksnių autentifikaciją',
    'totpScanInstructions'       => 'Atidarykite autentifikatoriaus programą (Google Authenticator, Authy, 1Password ir kt.) ir nuskaitykite šį QR kodą.',
    'totpManualEntry'            => 'Negalite nuskaityti? Įveskite šį kodą rankiniu būdu:',
    'totpConfirmInstructions'    => 'Nuskaičius, įveskite 6 skaitmenų kodą, rodomą programoje, kad patvirtintumėte sąranką.',
    'totpRecoveryWarning'        => 'Išsaugokite atkūrimo kodus. Jei prarasite prieigą prie autentifikatoriaus programos, negalėsite prisijungti. Susisiekite su svetainės administratoriumi, kad iš naujo nustatytumėte 2FA.',

];
