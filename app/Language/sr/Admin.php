<?php

/**
 * Pubvana CMS - Admin language strings (Serbian)
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
    'save'              => 'Сачувај',
    'saveChanges'       => 'Сачувај измене',
    'cancel'            => 'Откажи',
    'edit'              => 'Уреди',
    'delete'            => 'Обриши',
    'create'            => 'Креирај',
    'add'               => 'Додај',
    'back'              => 'Назад',
    'view'              => 'Прикажи',
    'apply'             => 'Примени',
    'install'           => 'Инсталирај',
    'update'            => 'Ажурирај',
    'refresh'           => 'Освежи',
    'activate'          => 'Активирај',
    'deactivate'        => 'Деактивирај',
    'enable'            => 'Омогући',
    'disable'           => 'Онемогући',
    'disabled'          => 'Онемогућено',
    'approve'           => 'Одобри',
    'spam'              => 'Нежељена пошта',
    'trash'             => 'Смеће',
    'restore'           => 'Врати',
    'dismiss'           => 'Одбаци',
    'recheck'           => 'Поново провери',
    'clickToCopy'       => 'Кликни за копирање',
    'download'          => 'Преузми',
    'upload'            => 'Отпреми',
    'import'            => 'Увоз',
    'export'            => 'Извоз',
    'publish'           => 'Објави',
    'unpublish'         => 'Повуци с објаве',
    'logout'            => 'Одјави се',
    'viewSite'          => 'Прикажи сајт',
    'newPost'           => 'Нова објава',
    'buyNow'            => 'Купи одмах',
    'visitStore'        => 'Посети продавницу',
    'loadMore'          => 'Учитај још',

    // Table headers / labels
    'title'             => 'Наслов',
    'name'              => 'Назив',
    'slug'              => 'Slug',
    'status'            => 'Статус',
    'date'              => 'Датум',
    'actions'           => 'Акције',
    'author'            => 'Аутор',
    'views'             => 'Прегледи',
    'type'              => 'Тип',
    'url'               => 'URL',
    'description'       => 'Опис',
    'role'              => 'Улога',
    'email'             => 'Имејл',
    'username'          => 'Корисничко ime',
    'active'            => 'Активан',
    'version'           => 'Верзија',
    'size'              => 'Величина',
    'clicks'            => 'Кликови',
    'total'             => 'Укупно',
    'platform'          => 'Платформа',
    'label'             => 'Ознака',
    'order'             => 'Редослед',
    'source'            => 'Извор',
    'content'           => 'Садржај',
    'excerpt'           => 'Одломак',
    'details'           => 'Детаљи',
    'contentType'       => 'Тип садржаја',
    'seo'               => 'SEO',
    'metaTitle'         => 'Мета наслов',
    'metaDescription'   => 'Мета опис',

    // Status badges
    'published'         => 'Објављено',
    'draft'             => 'Нацрт',
    'scheduled'         => 'Заказано',
    'pending'           => 'На чекању',
    'safe'              => 'Безбедан',
    'notSafe'           => 'Није безбедан',
    'malicious'         => 'Злонамеран',
    'safetyUnknown'     => 'Непознато',
    'inactive'          => 'Неактиван',
    'installed'         => 'Инсталиран',
    'free'              => 'Бесплатно',
    'premium'           => 'Премиум',
    'all'               => 'Све',

    // Confirmations
    'confirmDelete'         => 'Да ли сте сигурни да желите да обришете ову ставку?',
    'confirmDeletePost'     => 'Обриши ову објаву?',
    'confirmDeletePage'     => 'Обриши ову страницу?',
    'confirmDeleteComment'  => 'Трајно обришите овај коментар?',
    'confirmDeleteUser'     => 'Обриши овог корисника?',
    'confirmDeleteMedia'    => 'Обриши?',
    'confirmDeleteBackup'   => 'Обриши ову резервну копију?',
    'confirmBulkAction'     => 'Примени масовну акцију на изабране објаве?',

    // Empty states
    'noPostsYet'        => 'Нема објава. {0}',
    'noResultsFound'    => 'Нема резултата.',
    'noCommentsYet'     => 'Нема коментара на чекању.',
    'noMediaYet'        => 'Нема медијских датотека.',
    'noItemsFound'      => 'Нема ставки на тржишту.',
    'noCategoriesYet'   => 'Нема категорија.',
    'noTagsYet'         => 'Нема ознака.',
    'noRevisionsYet'    => 'Нема пронађених ревизија.',

    // Misc common
    'permissionDenied'  => 'Приступ забрањен.',
    'notFound'          => 'Запис није пронађен.',
    'commasSeparated'   => 'Одвојено зарезима',
    'optional'          => 'Опционо',
    'required'          => 'Обавезно',
    'enabled'           => 'Омогућено',
    'selected'          => '{0} објава(е) изабрано',
    'published_count'   => '{0} објављено',
    'pending_count'     => '{0} на чекању',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Контролна табла',
    'navContent'        => 'Садржај',
    'navAppearance'     => 'Изглед',
    'navUsersAndSite'   => 'Корисници и сајт',
    'navTools'          => 'Алати',
    'navMarketplace'    => 'Тржиште',
    'navPlugins'        => 'Додаци',
    'navPosts'          => 'Објаве',
    'navSchedule'       => 'Распоред',
    'navPages'          => 'Странице',
    'navCategories'     => 'Категорије',
    'navTags'           => 'Ознаке',
    'navComments'       => 'Коментари',
    'navMedia'          => 'Медији',
    'navImport'         => 'Увоз',
    'navThemes'         => 'Теме',
    'navWidgets'        => 'Виџети',
    'navNavigation'     => 'Навигација',
    'navUsers'          => 'Корисници',
    'navSocialLinks'    => 'Друштвени линкови',
    'navRedirects'      => 'Преусмеравања',
    'navLanguages'      => 'Језици',
    'navSettings'       => 'Подешавања',
    'navAnalytics'      => 'Аналитика',
    'navAffiliates'     => 'Афилијатни линкови',
    'navBrokenLinks'    => 'Покварени линкови',
    'navActivityLog'    => 'Евиденција активности',
    'navBackup'         => 'Резервне копије и извоз',
    'navUpdates'        => 'Ажурирања',
    'navBrowse'         => 'Прегледај',
    'navLicenses'       => 'Лиценце',
    'navPubvanaStore'   => 'Pubvana продавница',
    'navUpdateAvailable'=> 'Доступно ажурирање',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Одјава?',
    'logoutModalBody'   => 'Изаберите "Одјави се" испод да бисте завршили сесију.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Контролна табла',
    'dashStats'             => 'Статистика',
    'dashPosts'             => 'Објаве',
    'dashPages'             => 'Странице',
    'dashComments'          => 'Коментари',
    'dashUsers'             => 'Корисници',
    'dashRecentPosts'       => 'Недавне објаве',
    'dashPendingComments'   => 'Коментари на чекању',
    'dashViewAll'           => 'Прикажи све',
    'dashCreateOne'         => 'Направи прву!',
    'dashNoPosts'           => 'Нема објава.',
    'dashNoPendingComments' => 'Нема коментара на чекању.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Објаве',
    'newPostTitle'          => 'Нова објава',
    'editPostTitle'         => 'Уреди објаву: {0}',
    'copyPreviewLink'       => 'Копирај линк за преглед',
    'backToPosts'           => 'Назад на објаве',
    'postTitleField'        => 'Наслов *',
    'postEditor'            => 'Уредник',
    'postHtmlEditor'        => 'HTML уредник',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Одломак',
    'postExcerptPlaceholder'=> 'Опционо кратко резиме...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Мета наслов',
    'postMetaDescription'   => 'Мета опис',
    'postPublishSection'    => 'Објављивање',
    'postStatus'            => 'Статус',
    'postStatusDraft'       => 'Нацрт',
    'postStatusPublished'   => 'Објављено',
    'postStatusScheduled'   => 'Заказано',
    'postScheduledAt'       => 'Заказани датум и време',
    'postFeatured'          => 'Истакнута објава',
    'postMembersOnly'       => 'Само за чланове',
    'postShareOnPublish'    => 'Подели на друштвеним мрежама при објављивању',
    'postSaveBtn'           => 'Сачувај објаву',
    'postFeaturedImage'     => 'Истакнута слика',
    'postFeaturedImagePlaceholder' => 'URL или путања за отпремање…',
    'postCategories'        => 'Категорије',
    'postTags'              => 'Ознаке',
    'postTagsPlaceholder'   => 'ознака1, ознака2, ознака3',
    'postRevisions'         => 'Ревизије',
    'postRevisionCount'     => '{0} ревизија(е)',
    'postPreview'           => 'Преглед',
    'postBulkAction'        => '- Изабери акцију -',
    'postBulkPublish'       => 'Објави',
    'postBulkUnpublish'     => 'Повуци с објаве (постави као нацрт)',
    'postBulkDelete'        => 'Обриши',

    // Post flash messages
    'postCreated'           => 'Објава успешно креирана.',
    'postUpdated'           => 'Објава ажурирана.',
    'scheduledDateMustBeFuture' => 'Заказани датум мора бити у будућности.',
    'postDeleted'           => 'Објава обрисана.',
    'postBulkUpdated'       => '{0} објава(е) ажурирано.',
    'postBulkInvalid'       => 'Неважећа масовна акција.',
    'postPermission'        => 'Можете уређивати само своје objave.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Ревизије: {0}',
    'revisionTitle'         => 'Ревизија — {0}',
    'revisionShowTitle'     => 'Ревизија',
    'revisionsBackToPost'   => 'Назад на објаву',
    'revisionsBackToList'   => 'Назад на ревизије',
    'revisionRestored'      => 'Објава враћена на ревизију из {0}.',
    'revisionRestoreBtn'    => 'Врати ову ревизију',
    'revisionSaved'         => 'Сачувано',
    'revisionBy'            => 'Аутор:',
    'revisionOn'            => 'Датум:',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Странице',
    'newPageTitle'          => 'Нова страница',
    'editPageTitle'         => 'Уреди страницу',
    'pageSlugInUse'         => "Slug '{0}' је већ у употреби.",
    'pageCannotDelete'      => 'Ова страница се не може обрисати.',
    'slugAutoGenHint'       => 'аутоматски генерисан из наслова ако је остављено празно',
    'slugCannotChange'      => 'не може се мењати',
    'colSystem'             => 'Систем',
    'system'                => 'Систем',

    // Page flash messages
    'pageCreated'           => 'Страница креирана.',
    'pageUpdated'           => 'Страница ажурирана.',
    'pageDeleted'           => 'Страница обрисана.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Категорије',
    'newCategoryTitle'      => 'Нова категорија',
    'editCategoryTitle'     => 'Уреди категорију',
    'categoryName'          => 'Назив',
    'categoryDescription'   => 'Опис',
    'categoryPostCount'     => 'Број објава',

    // Category flash messages
    'categoryCreated'       => 'Категорија креирана.',
    'categoryUpdated'       => 'Категорија ажурирана.',
    'categoryDeleted'       => 'Категорија обрисана.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Ознаке',
    'tagPostCount'          => 'Број објава',

    // Tag flash messages
    'tagDeleted'            => 'Ознака обрисана.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Коментари',
    'commentAuthor'         => 'Аутор',
    'commentContent'        => 'Коментар',
    'commentPost'           => 'Објава',
    'commentDate'           => 'Датум',
    'commentStatusFilter'   => 'Филтрирај по статусу',

    // Comment flash messages
    'commentApproved'       => 'Коментар одобрен.',
    'commentSpam'           => 'Означено као нежељена пошта.',
    'commentTrashed'        => 'Коментар премештен у смеће.',
    'commentDeleted'        => 'Коментар трајно обрисан.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Медијска библиотека',
    'mediaTitle'            => 'Наслов',
    'mediaAltText'          => 'Алтернативни текст',
    'mediaAltPlaceholder'   => 'Опишите слику ради приступачности',
    'mediaTitlePlaceholder' => 'Опционални наслов слике',
    'mediaImageDetails'     => 'Детаљи слике',
    'mediaSaved'            => 'Сачувано!',
    'mediaNoSelection'      => 'Нема изабране слике',
    'mediaBrowse'           => 'Прегледај медије',
    'mediaRemove'           => 'Уклони',
    'mediaUseImage'         => 'Користи ову слику',
    'mediaDropzone'         => 'Превуците слику овде или кликните да бисте прегледали',
    'mediaLoading'          => 'Учитавање медија…',
    'mediaEmpty'            => 'Нема отпремљених медија.',
    'mediaUpload'           => 'Отпреми медије',
    'mediaDragDrop'         => 'Превуците датотеке овде или',
    'mediaChooseFiles'      => 'Изабери датотеке',
    'mediaUploading'        => 'Отпремање…',
    'mediaFilename'         => 'Назив датотеке',
    'mediaSize'             => 'Величина',
    'mediaUploadFailed'     => 'Отпремање није успело: {0}',
    'mediaUploadError'      => 'Грешка при отпремању: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Медији обрисани.',
    'mediaNoValidFile'      => 'Није отпремена ниједна важећа датотека.',
    'mediaUploadSuccess'    => 'Датотека успешно отпремљена.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Навигација',
    'navQuickAdd'           => 'Брзо додавање',
    'navQuickAddPlaceholder' => 'Претражи странице, категорије, додатке...',
    'navItemLabel'          => 'Ознака',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Циљ',
    'navItemOrder'          => 'Редослед сортирања',
    'navGroupPrimary'       => 'Главна',
    'navGroupFooter'        => 'Подножје',
    'navSelectGroup'        => 'Изабери групу навигације:',
    'navParent'             => 'Родитељски елемент',
    'navTopLevel'           => '— Највиши ниво —',
    'navSameWindow'         => 'Исти прозор',
    'navNewWindow'          => 'Нови прозор',
    'navMenuItems'          => 'Ставке менија',
    'navNoItems'            => 'Нема ставки у овом менију.',
    'dragToReorder'         => 'Превуци за промену редоследа',

    // Navigation flash messages
    'navItemAdded'          => 'Ставка навигације додата.',
    'navItemRemoved'        => 'Ставка навигације уклоњена.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Теме',
    'themeOptions'          => 'Опције теме',
    'themeActivate'         => 'Активирај',
    'themeOptionsBtn'       => 'Опције',
    'themeActive'           => 'Активна',
    'themeBy'               => 'Аутор:',
    'themeSupport'          => 'Подршка',
    'themeVersion'          => 'Верзија',
    'themeSaveOptions'      => 'Сачувај опције',
    'themeInvalidLicense'   => 'Не може се активирати тема — лиценца је неважећа. Поново инсталирајте или контактирајте подршку.',
    'themeValidationFailed' => 'Тема садржи PHP кôд и не може се активирати.',
    'noThemesInstalled'     => 'Нема инсталираних тема. Посетите Тржиште да бисте преузели теме.',
    'themeUnapprovedTitle'  => 'Активирати неодобрену тему?',
    'themeNotApproved'      => 'Ова тема није одобрена од стране Pubvana.',
    'themeUnapprovedRisk'   => 'Активирање неодобрених тема може увести безбедносне ризике или проблеме компатибилности.',
    'themeActivateConfirm'  => 'Да ли сте сигурни да желите да је активирате?',
    'themeActivateAnyway'   => 'Активирај свеједно',
    'themeNoOptions'        => 'Ова тема нема конфигурабилних опција.',
    'themeCustomize'        => 'Прилагоди тему',

    // Theme flash messages
    'themeActivated'        => 'Тема активирана.',
    'themeOptionsSaved'     => 'Опције сачуване.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Лиценциран',
    'licenseCheckNow'        => 'Провери одмах',
    'licenseExpired'         => 'Истекла',
    'licenseEnterKey'        => 'Унеси кључ',
    'licenseChangeKey'       => 'Промени',
    'licenseRenew'           => 'Обнови',
    'licenseThirdParty'      => 'Треће лице',
    'unchecked'              => 'Непроверено',
    'safetyLabel'            => 'Безбедност:',
    'recheckBtn'             => 'Провери поново',
    'recheckSuccess'         => 'Безбедносна провера ажурирана.',
    'recheckFailed'          => 'Није могуће повезати се са сервером за верификацију. Покушајте поново касније.',
    'recheckNotFound'        => 'Ставка није пронађена.',
    'widgetBlockedMalicious' => '{0} је означен као злонамеран и не може бити додат.',
    'licenseNoStoreProduct'  => 'Ова ставка није повезана са производом у продавници. Ако сте купили ову ставку, поново је инсталирајте из marketplace-а да бисте омогућили лиценцирање.',
    'securityWarning'        => 'Безбедносно упозорење:',
    'licenseModalTitle'      => 'Унесите лиценцни кључ',
    'licenseModalBody'       => 'Налепите свој лиценцни кључ испод.',
    'licenseModalSave'       => 'Сачувај',
    'licenseSaved'           => 'Лиценцни кључ је сачуван и потврђен.',
    'licenseInvalid'         => 'Лиценцни кључ није важећи.',
    'licenseKeyRequired'     => 'Лиценцни кључ и производ су обавезни.',
    'licenseCheckFailed'     => 'Није могуће достићи сервер за лиценце. Покушајте поново касније.',
    'licenseProductNotFound' => 'Није могуће пронаћи ову ставку у продавници.',
    'btnCancel'              => 'Откажи',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Виџети',
    'widgetConfigureTitle'  => 'Конфигуриши виџет',
    'widgetAreas'           => 'Области виџета',
    'widgetAvailable'       => 'Доступни виџети',
    'widgetAddToArea'       => 'Додај у област',
    'widgetArea'            => 'Област',
    'widgetNoOptions'       => 'Нема опција.',
    'widgetSaveConfig'      => 'Сачувај конфигурацију',
    'widgetConfigure'       => 'Конфигуриши',
    'widgetNoAreas'         => 'Нису пронађене области виџета. Активирајте тему да бисте омогућили области виџета.',
    'widgetAreaEmpty'       => 'Нема виџета у овој области. Додај један са листе →',

    // Widget flash messages
    'widgetAdded'           => 'Виџет додат.',
    'widgetRemoved'         => 'Виџет уклоњен.',
    'widgetConfigured'      => 'Виџет конфигурисан.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Тржиште',
    'marketplaceRefresh'    => 'Освежи',
    'marketplaceVisitStore' => 'Посети продавницу',
    'marketplaceAll'        => 'Све',
    'marketplaceThemes'     => 'Теме',
    'marketplaceWidgets'    => 'Виџети',
    'marketplacePlugins'    => 'Додаци',
    'marketplaceUpdatesAvailable' => '{0} ажурирање(а) доступно.',
    'marketplaceBy'         => 'Аутор:',
    'marketplaceFree'       => 'Бесплатно',
    'marketplaceInstalled'  => 'Инсталирано',
    'marketplaceInstall'    => 'Инсталирај',
    'marketplaceBuyNow'     => 'Купи одмах',
    'marketplaceNoItems'    => 'Нема ставки на тржишту.',
    'marketplaceInstalledVersion' => 'v{0} инсталирана',
    'marketplaceLoadError'  => 'Није могуће учитати производе из продавнице. Покушајте поново касније.',
    'byAuthor'              => 'Аутор: {0}',
    'unknown'               => 'Непознато',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} успешно инсталиран.',
    'marketplaceInstallFail'    => 'Инсталација није успела. Проверите евиденцију.',
    'marketplaceUpdateSuccess'  => 'Успешно ажурирано.',
    'marketplaceUpdateFail'     => 'Ажурирање није успело.',
    'marketplaceCacheRefreshed' => 'Кеш тржишта освежен.',
    'marketplaceInvalidRequest' => 'Неважећи захтев за инсталацију.',
    'marketplaceCannotUpdate'   => 'Не може се ажурирати ова ставка.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Лиценце',
    'licensesNone'                => 'Нема лиценци',
    'licensesProduct'             => 'Производ',
    'licensesKey'                 => 'Лиценцни кључ',
    'licensesStatus'              => 'Статус',
    'licensesType'                => 'Тип',
    'licensesExpires'             => 'Истиче',
    'licensesDomain'              => 'Домен',
    'licensesInstalled'           => 'Инсталирано',
    'licensesLastChecked'         => 'Последња провера',
    'licensesActions'             => 'Акције',
    'licensesStatusValid'         => 'Важећа',
    'licensesStatusInvalid'       => 'Неважећа',
    'licensesStatusExpired'       => 'Истекла',
    'licensesStatusSubExpired'    => 'Претплата истекла',
    'licensesStatusUnchecked'     => 'Непроверено',
    'licensesSubscription'        => 'Претплата',
    'licensesOneTime'             => 'Јединствена',
    'licensesPerpetual'           => 'Трајна',
    'licensesNotInstalled'        => 'Није инсталирано',
    'licensesNever'               => 'Никад',
    'licensesRevalidate'          => 'Поново потврди',
    'licenseKeyPlaceholder'       => 'Унесите лиценцни кључ...',
    'marketplaceLicensesEmpty'    => 'Лиценцирани производи ће се појавити овде након инсталације.',
    'typeTheme'                   => 'Тема',
    'typeWidget'                  => 'Виџет',
    'typePlugin'                  => 'Додатак',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Лиценца успешно потврђена.',
    'licenseRevalidateInvalid'     => 'Лиценца је неважећа или је истекла.',
    'licenseRevalidateUnreachable' => 'Није могуће достићи сервер за лиценце. Покушајте поново касније.',
    'licenseRevalidateSkipped'     => 'Провера лиценце прескочена (развојни режим).',
    'licenseRevalidateNotFound'    => 'Лиценца није пронађена.',

    // License warning banners
    'licenseWarningTitle'   => 'Проблеми са лиценцом',
    'licenseWarningInvalid' => 'лиценца је неважећа или истекла',
    'licenseWarningManage'  => 'Управљај лиценцама',

    // Plugin license
    'pluginInvalidLicense' => 'Овај додатак има неважећу или истеклу лиценцу и не може се активирати.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Лиценцни кључ',
    'storeBrowseFull'       => 'Прегледај целу продавницу',
    'storeBackToMarketplace'=> 'Назад на тржиште',
    'storeNoProducts'       => 'Нема доступних производа.',
    'storeViewInStore'      => 'Прикажи у продавници',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Корисници',
    'editUserTitle'         => 'Уреди корисника',
    'createUserTitle'       => 'Kreiranje korisnika',
    'authorProfileTitle'    => 'Профил аутора',
    'userRoleLabel'         => 'Улога',
    'userActiveLabel'       => 'Активан',
    'userPasswordLabel'     => 'Лозинка',
    'userPasswordOptional'  => 'Оставите празно да задржите тренутну лозинку',
    'userDisplayName'       => 'Приказно ime',
    'userBio'               => 'Биографија',
    'userWebsite'           => 'Веб-сајт',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Аватар',
    'userSaveProfile'       => 'Сачувај профил',
    'userSaveChanges'       => 'Сачувај измене',
    'userCannotDeleteSelf'  => 'Не можете обрисати себе.',
    'userCannotDeleteOwner' => 'Налог власника сајта не може се обрисати.',
    'userOwnerCannotModify' => 'Налог власника сајта не може се мењати.',

    // User flash messages
    'userCreated'           => 'Корисник креиран.',
    'userUpdated'           => 'Корисник ажуриран.',
    'userDeleted'           => 'Корисник обрисан.',
    'userBanned'            => 'Корисник је забрањен.',
    'userUnbanned'          => 'Забрана корисника је уклоњена.',
    'userCannotBanSelf'     => 'Не можете забранити себе или власника сајта.',
    'banStatus'             => 'Статус забране',
    'banned'                => 'Забрањен',
    'ban'                   => 'Забрани корисника',
    'unban'                 => 'Уклони забрану',
    'banReasonRequired'     => 'Разлог забране је обавезан.',
    'banReasonPlaceholder'  => 'Разлог забране...',
    'confirmBanUser'        => 'Да ли сте сигурни да желите да забраните овог корисника?',
    'userProfileSaved'      => 'Профил сачуван.',
    'userAvatarUploadFail'  => 'Отпремање аватара није успело: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Подешавање 2FA',
    'tfaSetupHeading'       => 'Подешавање двофакторске аутентификације',
    'tfaScanQr'             => 'Скенирајте QR код испод помоћу апликације за аутентификацију (нпр. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Или унесите тајни кључ ручно:',
    'tfaEnterCode'          => 'Унесите 6-цифрени код из ваше апликације за потврду:',
    'tfaCodeLabel'          => 'Код за аутентификацију',
    'tfaConfirmBtn'         => 'Потврди и омогући 2FA',
    'tfaDisableBtn'         => 'Онемогући 2FA',
    'tfaDisableConfirm'     => 'Унесите тренутни 2FA код за онемогућавање:',
    'tfaEnabled'            => 'Двофакторска аутентификација омогућена.',
    'tfaDisabled'           => 'Двофакторска аутентификација онемогућена.',
    'tfaInvalidCode'        => 'Неважећи код — поново скенирајте QR код и покушајте поново.',
    'tfaInvalidDisable'     => 'Неважећи код — 2FA није онемогућена.',
    'tfaSessionExpired'     => 'Сесија подешавања је истекла — почните поново.',
    'tfaNotEnabled'         => '2FA тренутно није омогућена.',
    'tfaCantScan'           => "Не можете скенирати? Унесите овај код ручно:",
    'tfaWarning'            => 'Сачувајте овај тајни кључ на сигурном месту. Биће вам потребан за повраћај приступа ако изгубите уређај за аутентификацију.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Друштвени линкови',
    'socialPlatform'           => 'Платформа',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Иконица',
    'socialSortOrder'          => 'Редослед сортирања',
    'socialIconPackInfo'       => 'Тренутна тема <strong>{0}</strong> користи <strong>{1}</strong> (v{2}) за иконице. Испод можете изабрати доступне иконице које ће се приказивати за функцију Друштвених линкова овог сајта.',
    'socialSearchPlaceholder'  => 'Претражи платформе...',
    'socialIconDisclaimer'     => "Ове иконице су само приказ иконице која ће бити коришћена. Стварна иконица може се разликовати у зависности од пакета иконица активне теме.",

    // Social flash messages
    'socialLinkAdded'       => 'Друштвени линк додат.',
    'socialLinkUpdated'     => 'Линк ажуриран.',
    'socialLinkDeleted'     => 'Линк обрисан.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Преусмеравања',
    'redirectFrom'          => 'Полазни URL',
    'redirectTo'            => 'Одредишни URL',
    'redirectType'          => 'Тип',
    'redirectAdd'           => 'Додај преусмеравање',
    'redirectFromHint'      => '(релативни, нпр. /стара-страница)',
    'redirect301'           => '301 Трајно',
    'redirect302'           => '302 Привремено',
    'redirectInvalidDest'   => 'Неважећи одредишни URL преусмеравања.',

    // Redirect flash messages
    'redirectAdded'         => 'Преусмеравање додато.',
    'redirectDeleted'       => 'Преусмеравање обрисано.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Подешавања',
    'settingsGeneral'       => 'Општа',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'Имејл',
    'settingsSocialLogin'   => 'Друштвена пријава',
    'settingsSocialSharing' => 'Дељење на друштвеним мрежама',
    'settingsSpam'          => 'Заштита од нежељене поште',

    'generalSettingsHeading'    => 'Општа подешавања',
    'generalSiteName'           => 'Назив сајта',
    'generalTagline'            => 'Слоган',
    'generalAdminEmail'         => 'Административни имејл',
    'generalPostsPerPage'       => 'Објава по страни',
    'generalComments'           => 'Коментари',
    'generalCommentsEnable'     => 'Омогући коментаре',
    'generalCommentModeration'  => 'Захтевај модерацију пре објављивања',
    'generalMaintenanceMode'    => 'Режим одржавања',
    'generalMaintenanceEnable'  => 'Омогући режим одржавања',
    'generalMaintenanceHelp'    => 'Посетиоци виде страницу „Ускоро се враћамо". Администратори и даље могу приступити сајту.',
    'generalFrontPage'          => 'Почетна страница',
    'generalFrontPageBlog'      => 'Индекс блога (последње objave)',
    'generalFrontPageStatic'    => 'Статична страница:',
    'generalFrontPagePlugin'    => 'Страница додатка:',
    'generalSelectPage'         => '- Изабери страницу -',
    'generalSelectRoute'        => '- Изабери руту -',
    'generalFrontPageNoPlugins' => 'Нема доступних рута додатака',
    'generalPageCacheTtl'       => 'TTL кеша странице',
    'settingsCacheTtlHint'      => 'Секунде. 0 = онемогућено.',
    'generalSaveBtn'            => 'Сачувај општа подешавања',

    // General flash messages
    'generalSettingsSaved'      => 'Општа подешавања сачувана.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO подешавања',
    'seoMetaDescription'        => 'Мета опис',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Мапа сајта',
    'seoSitemapEnable'          => 'Омогући sitemap.xml',
    'seoSitemapHelp'            => 'Стандардна мапа сајта за све objavljene objave и странице.',
    'seoNewsSitemap'            => 'Омогући news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Google News мапа сајта — листа objava objavljenih у последњих 48 сати.',
    'seoSaveBtn'                => 'Сачувај SEO подешавања',
    'seoSettingsSaved'          => 'SEO подешавања сачувана.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Подешавања имејла',
    'emailFromName'             => 'Ime пошиљаоца',
    'emailFromAddress'          => 'Адреса пошиљаоца',
    'emailProtocol'             => 'Протокол',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP домаћин',
    'emailSmtpPort'             => 'SMTP порт',
    'emailSmtpEncryption'       => 'Шифровање',
    'emailSmtpEncryptionNone'   => 'Без шифровања',
    'emailSmtpUsername'         => 'SMTP корисничко ime',
    'emailSmtpPassword'         => 'SMTP лозинка',
    'emailSaveBtn'              => 'Сачувај подешавања имејла',
    'emailSettingsSaved'        => 'Подешавања имејла сачувана.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Друштвена пријава (OAuth)',
    'socialLoginHelp'           => 'Акредитиви се чувају у вашој .env датотеци. Региструјте своју апликацију у Google-у и Facebook-у да бисте добили ID клијента и тајне кључеве.',
    'socialLoginGoogleId'       => 'ID клијента',
    'socialLoginGoogleSecret'   => 'Тајни кључ клијента',
    'socialLoginFbAppId'        => 'ID апликације',
    'socialLoginFbAppSecret'    => 'Тајни кључ апликације',
    'socialLoginPlaceholderSecret' => '(оставите празно да задржите постојећи)',
    'socialLoginSaveBtn'        => 'Сачувај подешавања друштвене пријаве',
    'socialLoginSettingsSaved'  => 'Подешавања друштвене пријаве сачувана.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Аутоматско дељење при objavi',
    'socialSharingHelp'         => 'Када је objava objavljana са означеним „Подели при objavi", Pubvana ће аутоматски objavi на конфигурисаним друштвеним налозима.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Набавите кључеве на developer.twitter.com → Ваша апликација → Кључеви и токени.',
    'socialSharingApiKey'       => 'API кључ',
    'socialSharingApiSecret'    => 'API тајни кључ',
    'socialSharingAccessToken'  => 'Приступни токен',
    'socialSharingAccessSecret' => 'Тајни приступни кључ',
    'socialSharingFbPage'       => 'Facebook страница',
    'socialSharingFbPageHelp'   => 'Захтева токен за приступ страни са дозволом pages_manage_posts.',
    'socialSharingFbPageId'     => 'ID странице',
    'socialSharingFbPageToken'  => 'Токен за приступ страни',
    'socialSharingSaveBtn'      => 'Сачувај подешавања дељења',
    'socialSharingSettingsSaved'=> 'Подешавања дељења на друштвеним мрежама сачувана.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Заштита од нежељене поште (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana користи hCaptcha (са поштовањем приватности, не Google) за заштиту образаца за коментаре и контакт образаца од спам ботова.',
    'spamHcaptchaFree'          => 'hCaptcha је бесплатна за већину сајтова. Региструјте се на hcaptcha.com, затим: Account → Sites → Add Site за ваш кључ сајта и Account → Settings → Secret Key → Generate за ваш тајни кључ. Унесите оба испод.',
    'spamHcaptchaSiteKey'       => 'Кључ сајта',
    'spamHcaptchaSecretKey'     => 'Тајни кључ',
    'spamHcaptchaNote'          => 'Ако ови кључеви нису подешени, hCaptcha се тихо прескаче — безбедно за локални развој. Једном сачуван, виџет се аутоматски pojavljuje у обрасцу за коментаре и на страници за контакт.',
    'spamSettingsSaved'         => 'Подешавања заштите од нежељене поште сачувана.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Језици',
    'languageCode'              => 'Код',
    'languageName'              => 'Назив',
    'languageDefault'           => 'Подразумевани',
    'languageEnabled'           => 'Омогућен',
    'languageMakeDefault'       => 'Постави као подразумевани',
    'languageSetAsDefault'      => '{0} постављен као подразумевани језик.',
    'languageEnabled_msg'       => '{0} омогућен.',
    'languageDisabled_msg'      => '{0} онемогућен.',
    'languageNotFound'          => 'Језик није пронађен.',
    'languageCannotDisable'     => 'Не може се онемогућити подразумевани језик.',
    'languageDirection'         => 'Смер',
    'languageNativeName'        => 'Изворно ime',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Аналитика',
    'analyticsTotalViews'       => 'Укупни прегледи',
    'analyticsTopPosts'         => 'Најпопуларније objave',
    'analyticsReferrers'        => 'Главни извори саобраћаја',
    'analyticsLast7'            => 'Последњих 7 дана',
    'analyticsLast30'           => 'Последњих 30 дана',
    'analyticsLast90'           => 'Последњих 90 дана',
    'analyticsChartTitle'       => 'Прегледи страница',
    'analyticsNoData'           => 'Нема аналитичких података за овај период.',
    'analyticsDomain'           => 'Домен',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Афилијатни линкови',
    'newAffiliateLinkTitle'     => 'Нови афилијатни линк',
    'editAffiliateLinkTitle'    => 'Уреди афилијатни линк',
    'affiliateName'             => 'Назив',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Одредишни URL',
    'affiliateActive'           => 'Активан',
    'affiliateClicks'           => 'Кликови',
    'affiliateClicksTitle'      => 'Кликови — {0}',
    'affiliateTotal'            => 'Укупно',
    'affiliateViewClicks'       => 'Прикажи кликове',

    // Affiliate flash messages
    'affiliateCreated'          => 'Афилијатни линк креиран.',
    'affiliateUpdated'          => 'Афилијатни линк ажуриран.',
    'affiliateDeleted'          => 'Афилијатни линк обрисан.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Покварени линкови',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP статус',
    'brokenLinkError'           => 'Грешка',
    'brokenLinkSource'          => 'Извор',
    'brokenLinkShowDismissed'   => 'Прикажи одбачене',
    'brokenLinkHideDismissed'   => 'Сакриј одбачене',
    'brokenLinkTimeout'         => 'Прекорачење времена',
    'brokenLinkBroken'          => 'поквареn',
    'brokenLinkNone'            => 'Нису откривени покварени линкови.',
    'brokenLinkNowReachable'    => 'Линк је сада доступан — уклоњен из резултата.',
    'brokenLinkStillBroken'     => 'Линк је и даље поквареn ({0}).',
    'brokenLinkDismissed'       => 'Линк одбачен.',
    'brokenLinksCliHint'        => 'Покрените потпуно скенирање из командне линије за попуњавање овог извештаја: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} проблем(а) пронађено',
    'brokenLinksCount'          => '{0} покварених',
    'brokenLinksRecheck'        => 'Поново провери овај URL',
    'brokenLinksDismiss'        => 'Одбаци (сакриј из резултата)',
    'brokenLinksRunScan'        => 'Покрени скенирање',
    'brokenLinksScanComplete'   => 'Скенирање завршено: проверено {0} линкова, {1} поквареног.',
    'timeout'                   => 'Прекорачење времена',
    'typePost'                  => 'Objava',
    'typePage'                  => 'Страница',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Евиденција активности',
    'activityLogType'           => 'Тип',
    'activityLogAction'         => 'Акција',
    'activityLogUser'           => 'Корисник',
    'activityLogDate'           => 'Датум',
    'activityLogNote'           => 'Напомена',
    'activityLogFilterAll'      => 'Сви типови',
    'activityLogEmpty'          => 'Нема забележених активности.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Резервне копије и извоз',
    'backupDownload'            => 'Креирај и преузми резервну копију',
    'backupFiles'               => 'Доступне резервне копије',
    'backupFilename'            => 'Назив датотеке',
    'backupSize'                => 'Величина',
    'backupDate'                => 'Креирано',
    'backupGenerating'          => 'Генерисање резервне копије…',
    'backupNoFiles'             => 'Нема сачуваних резервних копија.',
    'backupFailed'              => 'Резервна копија није успела: {0}',
    'backupDeleted'             => 'Резервна копија обрисана.',
    'backupCannotDelete'        => 'Није могуће обрисати резервну копију.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP адресе се чувају као SHA-256 хешеви — не бележе се сирови лични подаци.',
    'colTime'                   => 'Време',
    'colIpHash'                 => 'IP хеш',
    'colReferrer'               => 'Извор',
    'affiliateDirectReferrer'   => 'Директно',
    'affiliateNameHint'         => 'Интерна ознака — не приказује се посетиоцима.',
    'affiliateSlugHint'         => 'Само слова, бројеви, цртице и подвлаке. Не може се мењати након дељења линкова.',
    'affiliateDestHint'         => 'Мора укључивати https://. Посетиоци ће бити преусмерени 301 овде.',
    'affiliateInactiveHint'     => 'Неактивни линкови враћају 404.',
    'affiliateLinkCount'        => '{0} линкова',
    'colDomain'                 => 'Домен',
    'commentAll'                => 'Сви',
    'commentPending'            => 'На чекању',
    'commentTrash'              => 'Смеће',
    'commentsNone'              => 'Нема {0} коментара.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Креирај резервну копију',
    'backupStarting'            => 'Покретање резервне копије...',
    'backupNoneYet'             => 'Нема резервних копија. Кликните „Kreiranje rezerne kopije" да kreiraте прву.',
    'backupsTitle'              => 'Резервне копије',
    'backupRetentionNote'       => 'Чува се највише 15 резервних копија — старије се аутоматски бришу.',
    'backupRestoreConfirm'      => 'Vratiti ову резервну копију? Прво ће бити kreirana rezerna kopija тренутног стања.',
    'backupDeleteConfirm'       => 'Обрисати ову резервну копију?',
    'colFilename'               => 'Назив датотеке',
    'colVersion'                => 'Верзија',
    'colTrigger'                => 'Окидач',
    'colSize'                   => 'Величина',
    'colDate'                   => 'Датум',
    'colActions'                => 'Акције',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Увоз',
    'importWpHeading'           => 'Увоз из WordPress-а',
    'importWpHelp'              => 'Извезите свој WordPress сајт преко Алати → Извоз, па отпремите .xml датотеку испод.',
    'importChooseFile'          => 'Изабери WXR датотеку (.xml)',
    'importDryRun'              => 'Пробни режим (само преглед — ништа се не чува)',
    'importRunBtn'              => 'Покрени увоз',
    'importNoValidFile'         => 'Молимо отпремите важећу WordPress WXR извозну датотеку.',
    'importOnlyXml'             => 'Прихватају се само .xml датотеке.',
    'importFileTooLarge'        => 'Датотека за увоз је превелика. Максимална величина је 50 МБ.',
    'importResultsHeading'      => 'Резултати увоза',
    'importDryRunNote'          => 'Пробни режим — подаци нису сачувани.',
    'importDryRunLabel'         => '(Пробни режим — подаци нису записани)',
    'importComplete'            => 'Увоз завршен',
    'importCreated'             => 'kreiran',
    'importSkipped'             => 'preskočen',
    'importErrors'              => 'Грешке:',
    'importInstructions'        => 'Извезите свој WordPress садржај из <strong>Алати → Извоз → Сав садржај</strong> и отпремите <code>.xml</code> датотеку овде. Pubvana ће увести objave, странице, категорије, ознаке, ауторе и коментаре.',
    'importCliTitle'            => 'CLI увоз',
    'importCliHint'             => 'Такође можете покренути увозник из командне линије:',
    'importCliDryRunHint'       => 'Заставица <code>--dry-run</code> приказује шта би se uvezlo без писања у базу података.',
    'importWhatTitle'           => 'Шта се uvozi',
    'importItemPosts'           => 'Objave (наслов, садржај, одломак, slug, статус)',
    'importItemPages'           => 'Странице',
    'importItemCategories'      => 'Категорије (са хијерархијом)',
    'importItemTags'            => 'Ознаке',
    'importItemAuthors'         => 'Аутори (kreiran kao претплатнички налози)',
    'importItemComments'        => 'Коментари',
    'importItemMedia'           => 'Медијске датотеке (URL-ови чувани у садржају)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Ажурирања',
    'updatesCurrentVersion'     => 'Тренутна верзија',
    'updatesLatestVersion'      => 'Најновија верзија',
    'updatesUpToDate'           => 'Pubvana је ажуриран.',
    'updatesAvailable'          => 'Доступно ажурирање: {0}',
    'updatesCheckBtn'           => 'Провери ажурирања',
    'updatesReleaseNotes'       => 'Белешке о издању',
    'updatesHowToApply'         => 'Kako применити ажурирање',
    'updatesCacheCleared'       => 'Кеш ажурирања очишћен — поновна провера у току.',
    'updatesExtCapped'          => 'Доступно ажурирање: {0} (безбедно за додатке)',
    'updatesNewerAvailable'     => 'Pubvana {0} је такође доступан — ажурирајте додатке наведене испод да бисте га откључали.',

    // Addon Updates
    'updatesExtTitle'               => 'Додатне компоненте',
    'updatesExtCheckAll'            => 'Провери све',
    'updatesExtUpdateAll'           => 'Ажурирај све',
    'updatesExtCheckAllType'        => 'Провери sve {0}',
    'updatesExtUpdateAllType'       => 'Ажурирај sve {0}',
    'updatesExtNoInstalled'         => 'Нема инсталираних {0}.',
    'updatesExtColName'             => 'Назив',
    'updatesExtColVersion'          => 'Верзија',
    'updatesExtColLatest'           => 'Најновија',
    'updatesExtColAutoUpdate'       => 'Аутоажурирање',
    'updatesExtColStatus'           => 'Статус',
    'updatesExtColActions'          => 'Акције',
    'updatesExtBundled'             => 'Уграђено у језгро',
    'updatesExtNoSource'            => 'Нема извора ажурирања',
    'updatesExtFailed'              => 'Неуспешно',
    'updatesExtUpdatedAt'           => 'Ажурирано {0}',
    'updatesExtAvailable'           => 'Доступно ажурирање',
    'updatesExtUpToDate'            => 'Ажурирано',
    'updatesExtUpdate'              => 'Ажурирај',
    'updatesExtChecking'            => 'Провера...',
    'updatesExtUpdating'            => 'Ажурирање...',
    'updatesExtUpdated'             => 'Ажурирано',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Потврди ажурирање',
    'updatesConfirmBody'            => 'Ово ће направити резервну копију вашег сајта, preuzeti ažuriranje и применити га.',
    'updatesConfirmSafe'            => 'Ваши <code>.env</code>, <code>App.php</code> и <code>Database.php</code> никада нису prepisani.',
    'updatesConfirmBtn'             => 'Ажурирај одмах',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Ажурирај све додатне компоненте',
    'updatesExtAllBody'             => 'Ово ће ažurirati sve додатне компоненте које имају ажурирања на чекању.',
    'updatesExtAllNote'             => 'Додатне компоненте са онемогућеним аутоажурирањем ће такође бити ажуриране.',
    'updatesExtAllBtn'              => 'Ажурирај све',

    'updatesExtBadge'               => 'Ажурирање: v{0}',
    'updatesExtGoToUpdates'         => 'Ажурирања',

    // Update Settings
    'updatesSettingsTitle'          => 'Подешавања ажурирања',
    'updatesAutoUpdateLabel'        => 'Аутоажурирање Pubvana',
    'updatesAutoUpdateManual'       => 'Ручно',
    'updatesAutoUpdateAuto'         => 'Аутоматски',
    'updatesAutoUpdateHelp'         => 'Када је омогућено, ажурирања Pubvana без прекидних промена се примењују аутоматски.',
    'updatesCheckMethodLabel'       => 'Метод провере ажурирања',
    'updatesCheckMethodPageload'    => 'Учитавање странице',
    'updatesCheckMethodCron'        => 'Cron задатак',
    'updatesCheckMethodHelp'        => 'Учитавање странице проверава при сваком захтеву (кеш 24ч). Cron захтева серверски cron задатак.',
    'updatesCronCommand'            => 'Cron команда',
    'updatesCronHelp'               => 'Додајте ово у crontab сервера за свакодневну проверу ажурирања:',
    'updatesSettingsSaved'          => 'Подешавања ажурирања сачувана.',

    // Compatibility
    'compatWarningTitle'            => 'Упозорење компатибилности',
    'compatNotCompatible'           => 'Неке инсталиране додатне компоненте нису компатибилне са овом верзијом.',
    'compatRequiresUpdate'          => 'али захтева да следеће додатне компоненте буду прво ажуриране:',
    'compatSupportsUpTo'            => 'подржава до {0}',
    'compatRequiresMin'             => 'захтева Pubvana {0}+',
    'compatNotDeclared'             => 'Следеће додатне компоненте нису декларисале компатибилност са Pubvana {0}. Могу prestati да раде после ažuriranja:',
    'compatColType'                 => 'Тип',
    'compatColName'                 => 'Назив',
    'compatColVersion'              => 'Компатибилност',
    'compatRemoveHint'              => 'Можете уклонити некомпатибилне додатне компоненте или прећи на подразумевану тему ако дođe до проблема. Резервна kopija se kreira пре сваког ažuriranja.',
    'compatMaxVersion'              => 'Максимална компатибилна верзија: {0}',
    'compatMinVersion'              => 'Захтева Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Распоред objava',
    'scheduleNoScheduled'       => 'Нема заказаних objava.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Ревизије - {0}',
    'revisionPageTitle'         => 'Ревизија - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Морате бити пријављени да бисте приступили административној табли.',
    'dirNotWritable'            => 'Директоријум није доступан за pisanje: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} је неправилно конфигурисан. Ако сте крајњи корисник, контактирајте програмера. Ако сте програмер, консултујте документацију.',
    'addonMisconfiguredLink'    => '{0} је неправилно конфигурисан. Ако сте крајњи корисник <a href="{1}">контактирајте програмера</a>. Ако сте програмер <a href="https://github.com/enlivenapp/pubvana">консултујте документацију</a>.',
    'licenseExpiringSoon'       => 'Лиценца за {0} истиче {1}. {0} ће бити деактивиран када лиценца истекне.',
    'licenseExpiredDeactivated' => '{0} је деактивиран јер је лиценца истекла.',
    'addonDeactivated'          => '{0} је деактивиран. Разлог: {1}.',
    'widgetValidationFailed'    => "Виџет ''{0}'' није могао да буде потврђен. Контактирајте програмера или уклоните додатак.",
    'widgetValidationFailedLink' => "Виџет ''{0}'' није могао да буде потврђен. <a href=\"{1}\">Контактирајте програмера</a> или уклоните додатак.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Деактивиран: лиценца истекла',
    'addonDeactivatedTampered'  => 'Деактивиран: неправилна конфигурација',
    'addonDeactivatedNoLicense' => 'Деактивиран: нема важеће лиценце',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Онемогућено',
    'addonDisabledInvalidJson'  => 'Систем: {0} има неважећи или нечитљиви {1}.',
    'addonDisabledMissingFields' => 'Систем: {0} недостају обавезна поља: {1}.',
    'addonDisabledPhpFiles'     => 'Систем: {0} садржи PHP датотеке. Виџети морају biti само JSON + шаблони.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'Важећа лиценца је потребна за активирање {0}.',
    'licenseInvalidActivation'  => 'Потврда лиценце није успела за {0}. Проверите свој лиценцни кључ.',
    'licenseExpiredActivation'  => 'Лиценца за {0} је истекла. Обновите да бисте активирали.',
    'licenseCheckUnreachable'   => 'Није могуће верификовати лиценцу за {0}. Сервер за лиценце је недоступан. Покушајте поново касније.',
    'activationBlockedTampered' => '{0} не може да се активира јер је неправилно конфигурисан.',
    'activationBlockedBundled'  => '{0} не може да се активира: само Pubvana додаци могу бити означени као уграђени.',
    'activationBlockedNoUrls'   => '{0} не може да се активира: плаћени додаци морају садржати URL-ове за верификацију лиценце.',
    'activationBlockedFreeFlag' => '{0} не може да се активира: Pubvana додаци не могу бити означени као бесплатни.',
    'activationBlockedDisabled' => '{0} не може да се активира јер има грешке у конфигурацији. Проверите информациону датотеку.',

    // Third-party license
    'licenseThirdPartyLabel'    => 'Треће лице',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Покретање ažuriranja...',
    'updateCheckLabel'           => 'Провера ažuriranja:',
    'updateAvailable'            => 'Pubvana {0} је доступан!',
    'updateRunning'              => 'Покрећете {0}.',
    'updateBreakingChanges'      => 'Прекидне promene',
    'updateMigrationNotes'       => 'Белешке о миграцији',
    'updateNotices'              => 'Обавештења',
    'updatePreflightTitle'       => 'Pretletne провере',
    'updateToVersion'            => 'Ažuriraj na Pubvana {0}',
    'updatePreflightFailed'      => 'Jedna или više обавезних pretletnih проверa није успело. Решите их пре ažuriranja.',
    'updateUpToDate'             => 'Pubvana је ažuriran. Покрећете верзију {0}.',
    'updateAnyway'               => 'Ažuriraj svejedno',
    'updateAvailableTooltip'     => 'Pubvana {0} је доступан',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(ви)',
    'usersNone'                  => 'Нема пронађених корисника.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Налог активан',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Детаљи профила',
    'profileDisplayNameHint'     => 'Приказује se на objavljenim objavam уместо корисничког imеna.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP или GIF. Макс 10 МБ.',
    'profileSocialHandles'       => 'Друштвене мреже',
    'preview'                    => 'Преглед',
    'website'                    => 'Веб-сајт',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Двофакторска аутентификација',
    'totpActiveDesc'             => 'TOTP двофакторска аутентификација је активна на вашем налогу. При сваком пријављивању ће vam бити затражен 6-цифрени код из ваше апликације за аутентификацију.',
    'totpCurrentCode'            => 'Тренутни код',
    'totpInactiveDesc'           => 'Додајте додатни слој безбедности вашем налогу. Једном омогућена, мораћете да унесете код из ваше апликације за аутентификацију при сваком пријављивању.',
    'totpEnable'                 => 'Омогући двофакторску аутентификацију',
    'totpScanInstructions'       => 'Отворите вашу апликацију за аутентификацију (Google Authenticator, Authy, 1Password итд.) и скенирајте овај QR код.',
    'totpManualEntry'            => "Не можете скенирати? Унесите овај код ручно:",
    'totpConfirmInstructions'    => 'Након скенирања, унесите 6-цифрени код prikazan у вашој апликацији да бисте потврдили подешавање.',
    'totpRecoveryWarning'        => 'Сачувајте своје резервне кодове. Ако изгубите приступ вашој апликацији за аутентификацију, нећете моћи да se prijavite. Kontaktirajte администратора сајта за ресетовање 2FA.',

];
