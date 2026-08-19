<?php

/**
 * Pubvana CMS - Admin language strings (Bulgarian)
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
    'save'              => 'Запази',
    'saveChanges'       => 'Запази промените',
    'cancel'            => 'Отказ',
    'edit'              => 'Редактирай',
    'delete'            => 'Изтрий',
    'create'            => 'Създай',
    'add'               => 'Добави',
    'back'              => 'Назад',
    'view'              => 'Преглед',
    'apply'             => 'Приложи',
    'install'           => 'Инсталирай',
    'update'            => 'Актуализирай',
    'refresh'           => 'Опресни',
    'activate'          => 'Активирай',
    'deactivate'        => 'Деактивирай',
    'enable'            => 'Разреши',
    'disable'           => 'Забрани',
    'disabled'          => 'Забранен',
    'approve'           => 'Одобри',
    'spam'              => 'Спам',
    'trash'             => 'Кошче',
    'restore'           => 'Възстанови',
    'dismiss'           => 'Затвори',
    'recheck'           => 'Провери отново',
    'clickToCopy'       => 'Натисни за копиране',
    'download'          => 'Изтегли',
    'upload'            => 'Качи',
    'import'            => 'Импортирай',
    'export'            => 'Експортирай',
    'publish'           => 'Публикувай',
    'unpublish'         => 'Отмени публикуването',
    'logout'            => 'Излез',
    'viewSite'          => 'Виж сайта',
    'newPost'           => 'Нова публикация',
    'buyNow'            => 'Купи сега',
    'visitStore'        => 'Посети магазина',
    'loadMore'          => 'Зареди повече',

    // Table headers / labels
    'title'             => 'Заглавие',
    'name'              => 'Име',
    'slug'              => 'Slug',
    'status'            => 'Статус',
    'date'              => 'Дата',
    'actions'           => 'Действия',
    'author'            => 'Автор',
    'views'             => 'Прегледи',
    'type'              => 'Тип',
    'url'               => 'URL',
    'description'       => 'Описание',
    'role'              => 'Роля',
    'email'             => 'Имейл',
    'username'          => 'Потребителско име',
    'active'            => 'Активен',
    'version'           => 'Версия',
    'size'              => 'Размер',
    'clicks'            => 'Кликвания',
    'total'             => 'Общо',
    'platform'          => 'Платформа',
    'label'             => 'Етикет',
    'order'             => 'Ред',
    'source'            => 'Източник',
    'content'           => 'Съдържание',
    'excerpt'           => 'Откъс',
    'details'           => 'Подробности',
    'contentType'       => 'Тип съдържание',
    'seo'               => 'SEO',
    'metaTitle'         => 'Мета заглавие',
    'metaDescription'   => 'Мета описание',

    // Status badges
    'published'         => 'Публикувано',
    'draft'             => 'Чернова',
    'scheduled'         => 'Планирано',
    'pending'           => 'Изчакващо',
    'safe'              => 'Безопасен',
    'notSafe'           => 'Небезопасен',
    'malicious'         => 'Злонамерен',
    'safetyUnknown'     => 'Неизвестен',
    'inactive'          => 'Неактивен',
    'installed'         => 'Инсталиран',
    'free'              => 'Безплатен',
    'premium'           => 'Премиум',
    'all'               => 'Всички',

    // Confirmations
    'confirmDelete'         => 'Сигурни ли сте, че искате да изтриете този елемент?',
    'confirmDeletePost'     => 'Изтрий тази публикация?',
    'confirmDeletePage'     => 'Изтрий тази страница?',
    'confirmDeleteComment'  => 'Изтрий окончателно този коментар?',
    'confirmDeleteUser'     => 'Изтрий този потребител?',
    'confirmDeleteMedia'    => 'Изтрий?',
    'confirmDeleteBackup'   => 'Изтрий този архивен файл?',
    'confirmBulkAction'     => 'Прилагане на масово действие върху избраните публикации?',

    // Empty states
    'noPostsYet'        => 'Все още няма публикации. {0}',
    'noResultsFound'    => 'Не са намерени резултати.',
    'noCommentsYet'     => 'Няма изчакващи коментари.',
    'noMediaYet'        => 'Все още няма медия.',
    'noItemsFound'      => 'Не са намерени елементи в магазина.',
    'noCategoriesYet'   => 'Все още няма категории.',
    'noTagsYet'         => 'Все още няма тагове.',
    'noRevisionsYet'    => 'Не са намерени ревизии.',

    // Misc common
    'permissionDenied'  => 'Достъпът е отказан.',
    'notFound'          => 'Записът не е намерен.',
    'commasSeparated'   => 'Разделени със запетаи',
    'optional'          => 'По избор',
    'required'          => 'Задължително',
    'enabled'           => 'Разрешен',
    'selected'          => '{0} публикация/и избрана/и',
    'published_count'   => '{0} публикувани',
    'pending_count'     => '{0} изчакващи',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Табло',
    'navContent'        => 'Съдържание',
    'navAppearance'     => 'Изглед',
    'navUsersAndSite'   => 'Потребители и сайт',
    'navTools'          => 'Инструменти',
    'navMarketplace'    => 'Магазин',
    'navPlugins'        => 'Плъгини',
    'navPosts'          => 'Публикации',
    'navSchedule'       => 'График',
    'navPages'          => 'Страници',
    'navCategories'     => 'Категории',
    'navTags'           => 'Тагове',
    'navComments'       => 'Коментари',
    'navMedia'          => 'Медия',
    'navImport'         => 'Импорт',
    'navThemes'         => 'Теми',
    'navWidgets'        => 'Уиджети',
    'navNavigation'     => 'Навигация',
    'navUsers'          => 'Потребители',
    'navSocialLinks'    => 'Социални връзки',
    'navRedirects'      => 'Пренасочвания',
    'navLanguages'      => 'Езици',
    'navSettings'       => 'Настройки',
    'navAnalytics'      => 'Анализи',
    'navAffiliates'     => 'Партньорски връзки',
    'navBrokenLinks'    => 'Счупени връзки',
    'navActivityLog'    => 'Журнал на активността',
    'navBackup'         => 'Архивиране и експорт',
    'navUpdates'        => 'Актуализации',
    'navBrowse'         => 'Разгледай',
    'navLicenses'       => 'Лицензи',
    'navPubvanaStore'   => 'Магазин Pubvana',
    'navUpdateAvailable'=> 'Налична актуализация',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Готови ли сте да излезете?',
    'logoutModalBody'   => 'Натиснете „Излез" по-долу, за да приключите сесията си.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Табло',
    'dashStats'             => 'Статистика',
    'dashPosts'             => 'Публикации',
    'dashPages'             => 'Страници',
    'dashComments'          => 'Коментари',
    'dashUsers'             => 'Потребители',
    'dashRecentPosts'       => 'Скорошни публикации',
    'dashPendingComments'   => 'Изчакващи коментари',
    'dashViewAll'           => 'Виж всички',
    'dashCreateOne'         => 'Създай!',
    'dashNoPosts'           => 'Все още няма публикации.',
    'dashNoPendingComments' => 'Няма изчакващи коментари.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Публикации',
    'newPostTitle'          => 'Нова публикация',
    'editPostTitle'         => 'Редактирай публикация: {0}',
    'copyPreviewLink'       => 'Копирай линк за преглед',
    'backToPosts'           => 'Обратно към публикациите',
    'postTitleField'        => 'Заглавие *',
    'postEditor'            => 'Редактор',
    'postHtmlEditor'        => 'HTML редактор',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Откъс',
    'postExcerptPlaceholder'=> 'Незадължително кратко резюме...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Мета заглавие',
    'postMetaDescription'   => 'Мета описание',
    'postPublishSection'    => 'Публикувай',
    'postStatus'            => 'Статус',
    'postStatusDraft'       => 'Чернова',
    'postStatusPublished'   => 'Публикувано',
    'postStatusScheduled'   => 'Планирано',
    'postScheduledAt'       => 'Планирана дата и час',
    'postFeatured'          => 'Featured публикация',
    'postMembersOnly'       => 'Само за членове',
    'postShareOnPublish'    => 'Сподели в социалните мрежи при публикуване',
    'postSaveBtn'           => 'Запази публикацията',
    'postFeaturedImage'     => 'Главно изображение',
    'postFeaturedImagePlaceholder' => 'URL или път за качване…',
    'postCategories'        => 'Категории',
    'postTags'              => 'Тагове',
    'postTagsPlaceholder'   => 'таг1, таг2, таг3',
    'postRevisions'         => 'Ревизии',
    'postRevisionCount'     => '{0} ревизия/и',
    'postPreview'           => 'Преглед',
    'postBulkAction'        => '- Избери действие -',
    'postBulkPublish'       => 'Публикувай',
    'postBulkUnpublish'     => 'Отмени публикуването (задай като чернова)',
    'postBulkDelete'        => 'Изтрий',

    // Post flash messages
    'postCreated'           => 'Публикацията е създадена успешно.',
    'postUpdated'           => 'Публикацията е актуализирана.',
    'scheduledDateMustBeFuture' => 'Планираната дата трябва да бъде в бъдещето.',
    'postDeleted'           => 'Публикацията е изтрита.',
    'postBulkUpdated'       => '{0} публикация/и актуализирана/и.',
    'postBulkInvalid'       => 'Невалидно масово действие.',
    'postPermission'        => 'Можете да редактирате само собствените си публикации.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Ревизии: {0}',
    'revisionTitle'         => 'Ревизия — {0}',
    'revisionShowTitle'     => 'Ревизия',
    'revisionsBackToPost'   => 'Обратно към публикацията',
    'revisionsBackToList'   => 'Обратно към списъка с ревизии',
    'revisionRestored'      => 'Публикацията е възстановена до ревизията от {0}.',
    'revisionRestoreBtn'    => 'Възстанови тази ревизия',
    'revisionSaved'         => 'Запазено',
    'revisionBy'            => 'От',
    'revisionOn'            => 'На',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Страници',
    'newPageTitle'          => 'Нова страница',
    'editPageTitle'         => 'Редактирай страница',
    'pageSlugInUse'         => "Slug '{0}' вече се използва.",
    'pageCannotDelete'      => 'Тази страница не може да бъде изтрита.',
    'slugAutoGenHint'       => 'автоматично генериран от заглавието, ако е оставен празен',
    'slugCannotChange'      => 'не може да се промени',
    'colSystem'             => 'Система',
    'system'                => 'Система',

    // Page flash messages
    'pageCreated'           => 'Страницата е създадена.',
    'pageUpdated'           => 'Страницата е актуализирана.',
    'pageDeleted'           => 'Страницата е изтрита.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Категории',
    'newCategoryTitle'      => 'Нова категория',
    'editCategoryTitle'     => 'Редактирай категория',
    'categoryName'          => 'Название',
    'categoryDescription'   => 'Описание',
    'categoryPostCount'     => 'Брой публикации',

    // Category flash messages
    'categoryCreated'       => 'Категорията е създадена.',
    'categoryUpdated'       => 'Категорията е актуализирана.',
    'categoryDeleted'       => 'Категорията е изтрита.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Тагове',
    'tagPostCount'          => 'Брой публикации',

    // Tag flash messages
    'tagDeleted'            => 'Тагът е изтрит.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Коментари',
    'commentAuthor'         => 'Автор',
    'commentContent'        => 'Коментар',
    'commentPost'           => 'Публикация',
    'commentDate'           => 'Дата',
    'commentStatusFilter'   => 'Филтриране по статус',

    // Comment flash messages
    'commentApproved'       => 'Коментарът е одобрен.',
    'commentSpam'           => 'Маркиран като спам.',
    'commentTrashed'        => 'Коментарът е преместен в кошчето.',
    'commentDeleted'        => 'Коментарът е изтрит окончателно.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Медийна библиотека',
    'mediaTitle'            => 'Заглавие',
    'mediaAltText'          => 'Алтернативен текст',
    'mediaAltPlaceholder'   => 'Опишете изображението за достъпност',
    'mediaTitlePlaceholder' => 'Незадължително заглавие на изображението',
    'mediaImageDetails'     => 'Детайли за изображението',
    'mediaSaved'            => 'Запазено!',
    'mediaNoSelection'      => 'Няма избрано изображение',
    'mediaBrowse'           => 'Разгледай медия',
    'mediaRemove'           => 'Премахни',
    'mediaUseImage'         => 'Използвай това изображение',
    'mediaDropzone'         => 'Плъзнете изображение тук или натиснете за разглеждане',
    'mediaLoading'          => 'Зареждане на медия…',
    'mediaEmpty'            => 'Все още няма качена медия.',
    'mediaUpload'           => 'Качи медия',
    'mediaDragDrop'         => 'Плъзнете файлове тук или',
    'mediaChooseFiles'      => 'Избери файлове',
    'mediaUploading'        => 'Качване…',
    'mediaFilename'         => 'Файлово име',
    'mediaSize'             => 'Размер',
    'mediaUploadFailed'     => 'Качването е неуспешно: {0}',
    'mediaUploadError'      => 'Грешка при качване: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Медията е изтрита.',
    'mediaNoValidFile'      => 'Не е качен валиден файл.',
    'mediaUploadSuccess'    => 'Файлът е качен успешно.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Навигация',
    'navQuickAdd'           => 'Бързо добавяне',
    'navQuickAddPlaceholder' => 'Търси страници, категории, плъгини...',
    'navItemLabel'          => 'Етикет',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Цел',
    'navItemOrder'          => 'Ред на сортиране',
    'navGroupPrimary'       => 'Основна',
    'navGroupFooter'        => 'Долен колонтитул',
    'navSelectGroup'        => 'Избери навигационна група:',
    'navParent'             => 'Родителски',
    'navTopLevel'           => '— Най-горно ниво —',
    'navSameWindow'         => 'Същия прозорец',
    'navNewWindow'          => 'Нов прозорец',
    'navMenuItems'          => 'Елементи на менюто',
    'navNoItems'            => 'Няма елементи в това меню.',
    'dragToReorder'         => 'Плъзни за пренареждане',

    // Navigation flash messages
    'navItemAdded'          => 'Навигационният елемент е добавен.',
    'navItemRemoved'        => 'Навигационният елемент е премахнат.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Теми',
    'themeOptions'          => 'Опции на темата',
    'themeActivate'         => 'Активирай',
    'themeOptionsBtn'       => 'Опции',
    'themeActive'           => 'Активна',
    'themeBy'               => 'От',
    'themeSupport'          => 'Поддръжка',
    'themeVersion'          => 'Версия',
    'themeSaveOptions'      => 'Запази опциите',
    'themeInvalidLicense'   => 'Темата не може да бъде активирана – лицензът е невалиден. Преинсталирайте или се свържете с поддръжката.',
    'themeValidationFailed' => 'Темата съдържа PHP код и не може да бъде активирана.',
    'noThemesInstalled'     => 'Няма инсталирани теми. Посетете Магазина, за да получите теми.',
    'themeUnapprovedTitle'  => 'Активиране на неодобрена тема?',
    'themeNotApproved'      => 'Тази тема не е одобрена от Pubvana.',
    'themeUnapprovedRisk'   => 'Активирането на неодобрени теми може да въведе рискове за сигурността или проблеми с съвместимостта.',
    'themeActivateConfirm'  => 'Сигурни ли сте, че искате да я активирате въпреки това?',
    'themeActivateAnyway'   => 'Активирай въпреки това',
    'themeNoOptions'        => 'Тази тема няма конфигурируеми опции.',
    'themeCustomize'        => 'Персонализирай темата',

    // Theme flash messages
    'themeActivated'        => 'Темата е активирана.',
    'themeOptionsSaved'     => 'Опциите са запазени.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Лицензиран',
    'licenseCheckNow'        => 'Провери сега',
    'licenseExpired'         => 'Изтекъл',
    'licenseEnterKey'        => 'Въведи ключ',
    'licenseChangeKey'       => 'Промени',
    'licenseRenew'           => 'Поднови',
    'licenseThirdParty'      => 'Трета страна',
    'unchecked'              => 'Непроверен',
    'safetyLabel'            => 'Безопасност:',
    'recheckBtn'             => 'Провери отново',
    'recheckSuccess'         => 'Проверката за безопасност е актуализирана.',
    'recheckFailed'          => 'Неуспешна връзка със сървъра за проверка. Опитайте по-късно.',
    'recheckNotFound'        => 'Елементът не е намерен.',
    'widgetBlockedMalicious' => '{0} е маркиран като злонамерен и не може да бъде добавен.',
    'licenseNoStoreProduct'  => 'Този елемент не е свързан с продукт от магазина. Ако сте закупили този елемент, моля преинсталирайте го от marketplace, за да активирате лицензирането.',
    'securityWarning'        => 'Предупреждение за сигурност:',
    'licenseModalTitle'      => 'Въведи лицензен ключ',
    'licenseModalBody'       => 'Поставете вашия лицензен ключ по-долу.',
    'licenseModalSave'       => 'Запази',
    'licenseSaved'           => 'Лицензният ключ е запазен и валидиран.',
    'licenseInvalid'         => 'Лицензният ключ не е валиден.',
    'licenseKeyRequired'     => 'Лицензният ключ и продуктът са задължителни.',
    'licenseCheckFailed'     => 'Не можахме да достигнем лицензния сървър. Моля, опитайте отново по-късно.',
    'licenseProductNotFound' => 'Не можахме да намерим този елемент в магазина.',
    'btnCancel'              => 'Отказ',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Уиджети',
    'widgetConfigureTitle'  => 'Конфигурирай уиджет',
    'widgetAreas'           => 'Зони за уиджети',
    'widgetAvailable'       => 'Налични уиджети',
    'widgetAddToArea'       => 'Добави към зона',
    'widgetArea'            => 'Зона',
    'widgetNoOptions'       => 'Няма опции.',
    'widgetSaveConfig'      => 'Запази конфигурацията',
    'widgetConfigure'       => 'Конфигурирай',
    'widgetNoAreas'         => 'Не са намерени зони за уиджети. Активирайте тема, за да разрешите зони за уиджети.',
    'widgetAreaEmpty'       => 'Няма уиджети в тази зона. Добавете един от списъка →',

    // Widget flash messages
    'widgetAdded'           => 'Уиджетът е добавен.',
    'widgetRemoved'         => 'Уиджетът е премахнат.',
    'widgetConfigured'      => 'Уиджетът е конфигуриран.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Магазин',
    'marketplaceRefresh'    => 'Опресни',
    'marketplaceVisitStore' => 'Посети магазина',
    'marketplaceAll'        => 'Всички',
    'marketplaceThemes'     => 'Теми',
    'marketplaceWidgets'    => 'Уиджети',
    'marketplacePlugins'    => 'Плъгини',
    'marketplaceUpdatesAvailable' => '{0} налична/и актуализация/и.',
    'marketplaceBy'         => 'От',
    'marketplaceFree'       => 'Безплатен',
    'marketplaceInstalled'  => 'Инсталиран',
    'marketplaceInstall'    => 'Инсталирай',
    'marketplaceBuyNow'     => 'Купи сега',
    'marketplaceNoItems'    => 'Не са намерени елементи в магазина.',
    'marketplaceInstalledVersion' => 'v{0} инсталирана',
    'marketplaceLoadError'  => 'Не можахме да заредим продуктите от магазина. Моля, опитайте отново по-късно.',
    'byAuthor'              => 'От {0}',
    'unknown'               => 'Неизвестен',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} е инсталиран успешно.',
    'marketplaceInstallFail'    => 'Инсталирането е неуспешно. Проверете журналите.',
    'marketplaceUpdateSuccess'  => 'Актуализиран успешно.',
    'marketplaceUpdateFail'     => 'Актуализирането е неуспешно.',
    'marketplaceCacheRefreshed' => 'Кешът на магазина е опреснен.',
    'marketplaceInvalidRequest' => 'Невалидна заявка за инсталиране.',
    'marketplaceCannotUpdate'   => 'Не можем да актуализираме този елемент.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Лицензи',
    'licensesNone'                => 'Няма лицензи',
    'licensesProduct'             => 'Продукт',
    'licensesKey'                 => 'Лицензен ключ',
    'licensesStatus'              => 'Статус',
    'licensesType'                => 'Тип',
    'licensesExpires'             => 'Изтича',
    'licensesDomain'              => 'Домейн',
    'licensesInstalled'           => 'Инсталиран',
    'licensesLastChecked'         => 'Последно проверен',
    'licensesActions'             => 'Действия',
    'licensesStatusValid'         => 'Валиден',
    'licensesStatusInvalid'       => 'Невалиден',
    'licensesStatusExpired'       => 'Изтекъл',
    'licensesStatusSubExpired'    => 'Абонаментът е изтекъл',
    'licensesStatusUnchecked'     => 'Непроверен',
    'licensesSubscription'        => 'Абонамент',
    'licensesOneTime'             => 'Еднократен',
    'licensesPerpetual'           => 'Постоянен',
    'licensesNotInstalled'        => 'Не е инсталиран',
    'licensesNever'               => 'Никога',
    'licensesRevalidate'          => 'Повторна валидация',
    'licenseKeyPlaceholder'       => 'Въведи лицензен ключ...',
    'marketplaceLicensesEmpty'    => 'Лицензираните продукти ще се появят тук след инсталиране.',
    'typeTheme'                   => 'Тема',
    'typeWidget'                  => 'Уиджет',
    'typePlugin'                  => 'Плъгин',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Лицензът е валидиран успешно.',
    'licenseRevalidateInvalid'     => 'Лицензът е невалиден или изтекъл.',
    'licenseRevalidateUnreachable' => 'Не можахме да достигнем лицензния сървър. Моля, опитайте отново по-късно.',
    'licenseRevalidateSkipped'     => 'Проверката на лиценза е пропусната (режим за разработка).',
    'licenseRevalidateNotFound'    => 'Лицензът не е намерен.',

    // License warning banners
    'licenseWarningTitle'   => 'Проблеми с лиценза',
    'licenseWarningInvalid' => 'лицензът е невалиден или изтекъл',
    'licenseWarningManage'  => 'Управление на лицензи',

    // Plugin license
    'pluginInvalidLicense' => 'Този плъгин има невалиден или изтекъл лиценз и не може да бъде активиран.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Лицензен ключ',
    'storeBrowseFull'       => 'Разгледай пълния магазин',
    'storeBackToMarketplace'=> 'Обратно към магазина',
    'storeNoProducts'       => 'Няма налични продукти.',
    'storeViewInStore'      => 'Виж в магазина',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Потребители',
    'editUserTitle'         => 'Редактирай потребител',
    'createUserTitle'       => 'Създай потребител',
    'authorProfileTitle'    => 'Профил на автора',
    'userRoleLabel'         => 'Роля',
    'userActiveLabel'       => 'Активен',
    'userPasswordLabel'     => 'Парола',
    'userPasswordOptional'  => 'Оставете празно, за да запазите текущата парола',
    'userDisplayName'       => 'Показвано име',
    'userBio'               => 'Биография',
    'userWebsite'           => 'Уебсайт',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Аватар',
    'userSaveProfile'       => 'Запази профила',
    'userSaveChanges'       => 'Запази промените',
    'userCannotDeleteSelf'  => 'Не можете да изтриете себе си.',
    'userCannotDeleteOwner' => 'Акаунтът на собственика на сайта не може да бъде изтрит.',
    'userOwnerCannotModify' => 'Акаунтът на собственика на сайта не може да бъде модифициран.',

    // User flash messages
    'userCreated'           => 'Потребителят е създаден.',
    'userUpdated'           => 'Потребителят е актуализиран.',
    'userDeleted'           => 'Потребителят е изтрит.',
    'userBanned'            => 'Потребителят е блокиран.',
    'userUnbanned'          => 'Потребителят е разблокиран.',
    'userCannotBanSelf'     => 'Не можете да блокирате себе си или собственика на сайта.',
    'banStatus'             => 'Статус на блокиране',
    'banned'                => 'Блокиран',
    'ban'                   => 'Блокирай потребител',
    'unban'                 => 'Разблокирай',
    'banReasonRequired'     => 'Причина за блокиране е задължителна.',
    'banReasonPlaceholder'  => 'Причина за блокиране...',
    'confirmBanUser'        => 'Сигурни ли сте, че искате да блокирате този потребител?',
    'userProfileSaved'      => 'Профилът е запазен.',
    'userAvatarUploadFail'  => 'Качването на аватара е неуспешно: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Настройка на 2FA',
    'tfaSetupHeading'       => 'Настройване на двуфакторна автентикация',
    'tfaScanQr'             => 'Сканирайте QR кода по-долу с вашето приложение за автентикация (напр. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Или въведете тайния ключ ръчно:',
    'tfaEnterCode'          => 'Въведете 6-цифрения код от вашето приложение за потвърждение:',
    'tfaCodeLabel'          => 'Код за автентикация',
    'tfaConfirmBtn'         => 'Потвърди и активирай 2FA',
    'tfaDisableBtn'         => 'Деактивирай 2FA',
    'tfaDisableConfirm'     => 'Въведете текущия 2FA код за деактивиране:',
    'tfaEnabled'            => 'Двуфакторната автентикация е активирана.',
    'tfaDisabled'           => 'Двуфакторната автентикация е деактивирана.',
    'tfaInvalidCode'        => 'Невалиден код – моля сканирайте QR кода отново и опитайте още веднъж.',
    'tfaInvalidDisable'     => 'Невалиден код – 2FA не беше деактивирана.',
    'tfaSessionExpired'     => 'Сесията за настройка е изтекла – моля започнете отначало.',
    'tfaNotEnabled'         => '2FA в момента не е активирана.',
    'tfaCantScan'           => 'Не можете да сканирате? Въведете този код ръчно:',
    'tfaWarning'            => 'Съхранете този таен ключ на безопасно място. Ще ви трябва за възстановяване на достъпа, ако загубите вашето устройство за автентикация.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Социални връзки',
    'socialPlatform'           => 'Платформа',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Икона',
    'socialSortOrder'          => 'Ред на сортиране',
    'socialIconPackInfo'       => 'Текущата тема <strong>{0}</strong> използва <strong>{1}</strong> (v{2}) за икони. По-долу можете да изберете наличните икони, които ще се показват за функцията за социални връзки на този сайт.',
    'socialSearchPlaceholder'  => 'Търси платформи...',
    'socialIconDisclaimer'     => "Тези икони са само представяне на иконата, която ще се използва. Действителната икона може да се различава в зависимост от пакета икони на активната тема.",

    // Social flash messages
    'socialLinkAdded'       => 'Социалната връзка е добавена.',
    'socialLinkUpdated'     => 'Връзката е актуализирана.',
    'socialLinkDeleted'     => 'Връзката е изтрита.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Пренасочвания',
    'redirectFrom'          => 'От URL',
    'redirectTo'            => 'Към URL',
    'redirectType'          => 'Тип',
    'redirectAdd'           => 'Добави пренасочване',
    'redirectFromHint'      => '(относително, напр. /стара-страница)',
    'redirect301'           => '301 Постоянно',
    'redirect302'           => '302 Временно',
    'redirectInvalidDest'   => 'Невалиден URL адрес за пренасочване.',

    // Redirect flash messages
    'redirectAdded'         => 'Пренасочването е добавено.',
    'redirectDeleted'       => 'Пренасочването е изтрито.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Настройки',
    'settingsGeneral'       => 'Общи',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'Имейл',
    'settingsSocialLogin'   => 'Социално влизане',
    'settingsSocialSharing' => 'Споделяне в социалните мрежи',
    'settingsSpam'          => 'Защита от спам',

    'generalSettingsHeading'    => 'Общи настройки',
    'generalSiteName'           => 'Име на сайта',
    'generalTagline'            => 'Слоган',
    'generalAdminEmail'         => 'Имейл на администратора',
    'generalPostsPerPage'       => 'Публикации на страница',
    'generalComments'           => 'Коментари',
    'generalCommentsEnable'     => 'Разреши коментари',
    'generalCommentModeration'  => 'Изисквай модерация преди публикуване',
    'generalMaintenanceMode'    => 'Режим на поддръжка',
    'generalMaintenanceEnable'  => 'Разреши режим на поддръжка',
    'generalMaintenanceHelp'    => 'Посетителите виждат страница „Ще се върнем скоро". Администраторите все още имат достъп до сайта.',
    'generalFrontPage'          => 'Начална страница',
    'generalFrontPageBlog'      => 'Блог индекс (последни публикации)',
    'generalFrontPageStatic'    => 'Статична страница:',
    'generalFrontPagePlugin'    => 'Страница на плъгин:',
    'generalSelectPage'         => '- Избери страница -',
    'generalSelectRoute'        => '- Избери маршрут -',
    'generalFrontPageNoPlugins' => 'Няма налични маршрути на плъгини',
    'generalPageCacheTtl'       => 'TTL на кеша на страниците',
    'settingsCacheTtlHint'      => 'Секунди. 0 = деактивирано.',
    'generalSaveBtn'            => 'Запази общите настройки',

    // General flash messages
    'generalSettingsSaved'      => 'Общите настройки са запазени.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO настройки',
    'seoMetaDescription'        => 'Мета описание',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Карта на сайта',
    'seoSitemapEnable'          => 'Разреши sitemap.xml',
    'seoSitemapHelp'            => 'Стандартна карта на сайта за всички публикувани публикации и страници.',
    'seoNewsSitemap'            => 'Разреши news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Google News карта на сайта – включва публикации от последните 48 часа.',
    'seoSaveBtn'                => 'Запази SEO настройките',
    'seoSettingsSaved'          => 'SEO настройките са запазени.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Настройки за имейл',
    'emailFromName'             => 'Име на подателя',
    'emailFromAddress'          => 'Адрес на подателя',
    'emailProtocol'             => 'Протокол',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP хост',
    'emailSmtpPort'             => 'SMTP порт',
    'emailSmtpEncryption'       => 'Криптиране',
    'emailSmtpEncryptionNone'   => 'Без криптиране',
    'emailSmtpUsername'         => 'SMTP потребителско име',
    'emailSmtpPassword'         => 'SMTP парола',
    'emailSaveBtn'              => 'Запази настройките за имейл',
    'emailSettingsSaved'        => 'Настройките за имейл са запазени.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Социално влизане (OAuth)',
    'socialLoginHelp'           => 'Идентификационните данни се запазват в .env файла. Регистрирайте приложението си в Google и Facebook, за да получите клиентски ID и тайни ключове.',
    'socialLoginGoogleId'       => 'Клиентски ID',
    'socialLoginGoogleSecret'   => 'Клиентски таен ключ',
    'socialLoginFbAppId'        => 'App ID',
    'socialLoginFbAppSecret'    => 'App таен ключ',
    'socialLoginPlaceholderSecret' => '(оставете празно, за да запазите съществуващото)',
    'socialLoginSaveBtn'        => 'Запази настройките за социално влизане',
    'socialLoginSettingsSaved'  => 'Настройките за социално влизане са запазени.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Автоматично споделяне в социалните мрежи при публикуване',
    'socialSharingHelp'         => 'Когато публикация се публикува с отметката „Споделяне при публикуване", Pubvana автоматично ще публикува в конфигурираните социални акаунти.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Вземете ключовете от developer.twitter.com → Вашето приложение → Ключове и токени.',
    'socialSharingApiKey'       => 'API ключ',
    'socialSharingApiSecret'    => 'API таен ключ',
    'socialSharingAccessToken'  => 'Токен за достъп',
    'socialSharingAccessSecret' => 'Таен ключ за достъп',
    'socialSharingFbPage'       => 'Facebook страница',
    'socialSharingFbPageHelp'   => 'Изисква токен за достъп до страницата с разрешение pages_manage_posts.',
    'socialSharingFbPageId'     => 'ID на страницата',
    'socialSharingFbPageToken'  => 'Токен за достъп до страницата',
    'socialSharingSaveBtn'      => 'Запази настройките за споделяне',
    'socialSharingSettingsSaved'=> 'Настройките за споделяне в социалните мрежи са запазени.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Защита от спам (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana използва hCaptcha (зачитащ поверителността, не Google) за защита на формулярите за коментари и формуляра за контакт от спам ботове.',
    'spamHcaptchaFree'          => 'hCaptcha е безплатна за повечето сайтове. Регистрирайте се на hcaptcha.com, създайте сайт и въведете ключовете си по-долу.',
    'spamHcaptchaSiteKey'       => 'Ключ на сайта',
    'spamHcaptchaSecretKey'     => 'Таен ключ',
    'spamHcaptchaNote'          => 'Ако тези ключове не са зададени, hCaptcha тихо се пропуска – безопасно за локална разработка. След запазване, уиджетът автоматично се появява в формуляра за коментари и страницата за контакт.',
    'spamSettingsSaved'         => 'Настройките за защита от спам са запазени.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Езици',
    'languageCode'              => 'Код',
    'languageName'              => 'Наименование',
    'languageDefault'           => 'По подразбиране',
    'languageEnabled'           => 'Разрешен',
    'languageMakeDefault'       => 'Задай като по подразбиране',
    'languageSetAsDefault'      => '{0} е зададен като език по подразбиране.',
    'languageEnabled_msg'       => '{0} е разрешен.',
    'languageDisabled_msg'      => '{0} е забранен.',
    'languageNotFound'          => 'Езикът не е намерен.',
    'languageCannotDisable'     => 'Не можете да забраните езика по подразбиране.',
    'languageDirection'         => 'Посока',
    'languageNativeName'        => 'Родно наименование',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Анализи',
    'analyticsTotalViews'       => 'Общо прегледи',
    'analyticsTopPosts'         => 'Топ публикации',
    'analyticsReferrers'        => 'Топ препращачи',
    'analyticsLast7'            => 'Последните 7 дни',
    'analyticsLast30'           => 'Последните 30 дни',
    'analyticsLast90'           => 'Последните 90 дни',
    'analyticsChartTitle'       => 'Прегледи на страницата',
    'analyticsNoData'           => 'Няма аналитични данни за този период.',
    'analyticsDomain'           => 'Домейн',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Партньорски връзки',
    'newAffiliateLinkTitle'     => 'Нова партньорска връзка',
    'editAffiliateLinkTitle'    => 'Редактирай партньорска връзка',
    'affiliateName'             => 'Наименование',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'URL на дестинацията',
    'affiliateActive'           => 'Активна',
    'affiliateClicks'           => 'Кликвания',
    'affiliateClicksTitle'      => 'Кликвания - {0}',
    'affiliateTotal'            => 'Общо',
    'affiliateViewClicks'       => 'Виж кликванията',

    // Affiliate flash messages
    'affiliateCreated'          => 'Партньорската връзка е създадена.',
    'affiliateUpdated'          => 'Партньорската връзка е актуализирана.',
    'affiliateDeleted'          => 'Партньорската връзка е изтрита.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Счупени връзки',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP статус',
    'brokenLinkError'           => 'Грешка',
    'brokenLinkSource'          => 'Източник',
    'brokenLinkShowDismissed'   => 'Покажи скритите',
    'brokenLinkHideDismissed'   => 'Скрий скритите',
    'brokenLinkTimeout'         => 'Изтекло време',
    'brokenLinkBroken'          => 'счупена',
    'brokenLinkNone'            => 'Не са открити счупени връзки.',
    'brokenLinkNowReachable'    => 'Връзката вече е достъпна – премахната от резултатите.',
    'brokenLinkStillBroken'     => 'Връзката все още е счупена ({0}).',
    'brokenLinkDismissed'       => 'Връзката е скрита.',
    'brokenLinksCliHint'        => 'Стартирайте пълно сканиране от командния ред, за да попълните този отчет: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} проблем/а намерен/и',
    'brokenLinksCount'          => '{0} счупени',
    'brokenLinksRecheck'        => 'Провери отново този URL',
    'brokenLinksDismiss'        => 'Скрий (от резултатите)',
    'brokenLinksRunScan'        => 'Стартирай сканиране',
    'brokenLinksScanComplete'   => 'Сканирането е завършено: {0} проверени връзки, {1} счупени.',
    'timeout'                   => 'Изтекло време',
    'typePost'                  => 'Публикация',
    'typePage'                  => 'Страница',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Журнал на активността',
    'activityLogType'           => 'Тип',
    'activityLogAction'         => 'Действие',
    'activityLogUser'           => 'Потребител',
    'activityLogDate'           => 'Дата',
    'activityLogNote'           => 'Забележка',
    'activityLogFilterAll'      => 'Всички типове',
    'activityLogEmpty'          => 'Все още не е записана активност.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Архивиране и експорт',
    'backupDownload'            => 'Създай и изтегли архив',
    'backupFiles'               => 'Налични архиви',
    'backupFilename'            => 'Файлово име',
    'backupSize'                => 'Размер',
    'backupDate'                => 'Създаден',
    'backupGenerating'          => 'Генериране на архив…',
    'backupNoFiles'             => 'Няма запазени архиви.',
    'backupFailed'              => 'Архивирането е неуспешно: {0}',
    'backupDeleted'             => 'Архивът е изтрит.',
    'backupCannotDelete'        => 'Не можахме да изтрием архива.',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP адресите се съхраняват като SHA-256 хешове – не се записват необработени лични данни.',
    'colTime'                   => 'Час',
    'colIpHash'                 => 'IP хеш',
    'colReferrer'               => 'Препращач',
    'affiliateDirectReferrer'   => 'Директен',
    'affiliateNameHint'         => 'Вътрешен етикет – не се показва на посетителите.',
    'affiliateSlugHint'         => 'Само букви, цифри, тирета и долни черти. Не може да се промени след споделяне на връзките.',
    'affiliateDestHint'         => 'Трябва да включва https://. Посетителите ще бъдат пренасочени с 301 тук.',
    'affiliateInactiveHint'     => 'Неактивните връзки връщат 404.',
    'affiliateLinkCount'        => '{0} връзки',
    'colDomain'                 => 'Домейн',
    'commentAll'                => 'Всички',
    'commentPending'            => 'Изчакващи',
    'commentTrash'              => 'Кошче',
    'commentsNone'              => 'Няма {0} коментари.',

    'backupCreate'              => 'Създай архив',
    'backupStarting'            => 'Стартиране на архивирането...',
    'backupNoneYet'             => 'Все още няма архиви. Натиснете „Създай архив", за да създадете първия.',
    'backupsTitle'              => 'Архиви',
    'backupRetentionNote'       => 'Максимум 15 архива се пазят – най-старите се изтриват автоматично.',
    'backupRestoreConfirm'      => 'Възстанови този архив? Първо ще бъде създаден архив на текущото състояние.',
    'backupDeleteConfirm'       => 'Изтрий този архив?',
    'colFilename'               => 'Файлово име',
    'colVersion'                => 'Версия',
    'colTrigger'                => 'Тригер',
    'colSize'                   => 'Размер',
    'colDate'                   => 'Дата',
    'colActions'                => 'Действия',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Импорт',
    'importWpHeading'           => 'Импортирай от WordPress',
    'importWpHelp'              => 'Експортирайте своя WordPress сайт чрез Инструменти → Експорт, след което качете .xml файла по-долу.',
    'importChooseFile'          => 'Избери WXR файл (.xml)',
    'importDryRun'              => 'Тестово изпълнение (само преглед – нищо не се запазва)',
    'importRunBtn'              => 'Стартирай импорта',
    'importNoValidFile'         => 'Моля качете валиден WordPress WXR експортен файл.',
    'importOnlyXml'             => 'Приемат се само .xml файлове.',
    'importFileTooLarge'        => 'Файлът за импорт е твърде голям. Максималният размер е 50 МБ.',
    'importResultsHeading'      => 'Резултати от импорта',
    'importDryRunNote'          => 'Тестово изпълнение – не са запазени данни.',
    'importDryRunLabel'         => '(Тестово изпълнение — не са записани данни)',
    'importComplete'            => 'Импортът е завършен',
    'importCreated'             => 'създадени',
    'importSkipped'             => 'пропуснати',
    'importErrors'              => 'Грешки:',
    'importInstructions'        => 'Експортирайте съдържанието на WordPress от <strong>Инструменти → Експорт → Всичко съдържание</strong> и качете файла <code>.xml</code> тук. Pubvana ще импортира публикации, страници, категории, тагове, автори и коментари.',
    'importCliTitle'            => 'CLI импорт',
    'importCliHint'             => 'Можете също да стартирате вносителя от командния ред:',
    'importCliDryRunHint'       => 'Флагът <code>--dry-run</code> показва какво би се импортирало, без да записва в базата данни.',
    'importWhatTitle'           => 'Какво се импортира',
    'importItemPosts'           => 'Публикации (заглавие, съдържание, откъс, slug, статус)',
    'importItemPages'           => 'Страници',
    'importItemCategories'      => 'Категории (с йерархия)',
    'importItemTags'            => 'Тагове',
    'importItemAuthors'         => 'Автори (създадени като абонаментни акаунти)',
    'importItemComments'        => 'Коментари',
    'importItemMedia'           => 'Медийни файлове (URL адресите се запазват в съдържанието)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Актуализации',
    'updatesCurrentVersion'     => 'Текуща версия',
    'updatesLatestVersion'      => 'Последна версия',
    'updatesUpToDate'           => 'Pubvana е актуален.',
    'updatesAvailable'          => 'Налична актуализация: {0}',
    'updatesCheckBtn'           => 'Провери за актуализации',
    'updatesReleaseNotes'       => 'Бележки за изданието',
    'updatesHowToApply'         => 'Как да приложите актуализация',
    'updatesCacheCleared'       => 'Кешът за актуализации е изчистен – проверява се отново.',
    'updatesExtCapped'          => 'Налична актуализация: {0} (безопасна за добавки)',
    'updatesNewerAvailable'     => 'Pubvana {0} е също налична – актуализирайте изброените по-долу добавки, за да я отключите.',

    // Addon Updates
    'updatesExtTitle'               => 'Добавки',
    'updatesExtCheckAll'            => 'Провери всички',
    'updatesExtUpdateAll'           => 'Актуализирай всички',
    'updatesExtCheckAllType'        => 'Провери всички {0}',
    'updatesExtUpdateAllType'       => 'Актуализирай всички {0}',
    'updatesExtNoInstalled'         => 'Няма инсталирани {0}.',
    'updatesExtColName'             => 'Наименование',
    'updatesExtColVersion'          => 'Версия',
    'updatesExtColLatest'           => 'Последна',
    'updatesExtColAutoUpdate'       => 'Авто-актуализация',
    'updatesExtColStatus'           => 'Статус',
    'updatesExtColActions'          => 'Действия',
    'updatesExtBundled'             => 'Включено в ядрото',
    'updatesExtNoSource'            => 'Няма източник за актуализации',
    'updatesExtFailed'              => 'Неуспешно',
    'updatesExtUpdatedAt'           => 'Актуализиран {0}',
    'updatesExtAvailable'           => 'Налична актуализация',
    'updatesExtUpToDate'            => 'Актуален',
    'updatesExtUpdate'              => 'Актуализирай',
    'updatesExtChecking'            => 'Проверка...',
    'updatesExtUpdating'            => 'Актуализиране...',
    'updatesExtUpdated'             => 'Актуализиран',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Потвърди актуализацията',
    'updatesConfirmBody'            => 'Това ще архивира сайта ви, ще изтегли актуализацията и ще я приложи.',
    'updatesConfirmSafe'            => 'Вашите <code>.env</code>, <code>App.php</code> и <code>Database.php</code> никога не се презаписват.',
    'updatesConfirmBtn'             => 'Актуализирай сега',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Актуализирай всички добавки',
    'updatesExtAllBody'             => 'Това ще актуализира всички добавки с изчакващи актуализации.',
    'updatesExtAllNote'             => 'Добавките с деактивирана авто-актуализация също ще бъдат актуализирани.',
    'updatesExtAllBtn'              => 'Актуализирай всички',

    'updatesExtBadge'               => 'Актуализация: v{0}',
    'updatesExtGoToUpdates'         => 'Актуализации',

    // Update Settings
    'updatesSettingsTitle'          => 'Настройки за актуализации',
    'updatesAutoUpdateLabel'        => 'Авто-актуализация на Pubvana',
    'updatesAutoUpdateManual'       => 'Ръчно',
    'updatesAutoUpdateAuto'         => 'Автоматично',
    'updatesAutoUpdateHelp'         => 'Когато е разрешено, актуализациите на Pubvana без критични промени се прилагат автоматично.',
    'updatesCheckMethodLabel'       => 'Метод за проверка на актуализации',
    'updatesCheckMethodPageload'    => 'Зареждане на страница',
    'updatesCheckMethodCron'        => 'Cron задача',
    'updatesCheckMethodHelp'        => 'Зареждането на страница проверява при всяка заявка (кеширано за 24 часа). Cron изисква сървърна cron задача.',
    'updatesCronCommand'            => 'Cron команда',
    'updatesCronHelp'               => 'Добавете това към crontab на сървъра си, за да стартирате проверката за актуализации ежедневно:',
    'updatesSettingsSaved'          => 'Настройките за актуализации са запазени.',

    // Compatibility
    'compatWarningTitle'            => 'Предупреждение за съвместимост',
    'compatNotCompatible'           => 'Някои инсталирани добавки не са съвместими с тази версия.',
    'compatRequiresUpdate'          => 'но изисква следните добавки да бъдат актуализирани първо:',
    'compatSupportsUpTo'            => 'поддържа до {0}',
    'compatRequiresMin'             => 'изисква Pubvana {0}+',
    'compatNotDeclared'             => 'Следните добавки не са декларирали съвместимост с Pubvana {0}. Те може да спрат да работят след актуализацията:',
    'compatColType'                 => 'Тип',
    'compatColName'                 => 'Наименование',
    'compatColVersion'              => 'Съвместимост',
    'compatRemoveHint'              => 'Можете да премахнете несъвместими добавки или да превключите към темата по подразбиране, ако възникнат проблеми. Архив се създава преди всяка актуализация.',
    'compatMaxVersion'              => 'Максимална съвместима версия: {0}',
    'compatMinVersion'              => 'Изисква Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'График на публикациите',
    'scheduleNoScheduled'       => 'Няма планирани публикации.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Ревизии - {0}',
    'revisionPageTitle'         => 'Ревизия - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Трябва да сте влезли, за да получите достъп до административния панел.',
    'dirNotWritable'            => 'Директорията не е записваема: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} е неправилно конфигуриран. Ако сте крайният потребител, свържете се с разработчика. Ако сте разработчикът, вижте документацията.',
    'addonMisconfiguredLink'    => '{0} е неправилно конфигуриран. Ако сте крайният потребител, <a href="{1}">свържете се с разработчика</a>. Ако сте разработчикът, <a href="https://github.com/enlivenapp/pubvana">вижте документацията</a>.',
    'licenseExpiringSoon'       => 'Лицензът за {0} изтича на {1}. {0} ще бъде деактивиран при изтичане на лиценза.',
    'licenseExpiredDeactivated' => '{0} е деактивиран, защото лицензът е изтекъл.',
    'addonDeactivated'          => '{0} е деактивиран. Причина: {1}.',
    'widgetValidationFailed'    => "Уиджетът ''{0}'' не можа да бъде валидиран. Свържете се с разработчика или премахнете добавката.",
    'widgetValidationFailedLink' => "Уиджетът ''{0}'' не можа да бъде валидиран. <a href=\"{1}\">Свържете се с разработчика</a> или премахнете добавката.",

    'addonDeactivatedExpired'   => 'Деактивиран: лицензът е изтекъл',
    'addonDeactivatedTampered'  => 'Деактивиран: неправилно конфигуриран',
    'addonDeactivatedNoLicense' => 'Деактивиран: няма валиден лиценз',

    'addonDisabled'             => 'Забранен',
    'addonDisabledInvalidJson'  => 'Система: {0} има невалиден или нечетим {1}.',
    'addonDisabledMissingFields' => 'Система: {0} липсват задължителни полета: {1}.',
    'addonDisabledPhpFiles'     => 'Система: {0} съдържа PHP файлове. Уиджетите трябва да съдържат само JSON + шаблони.',

    'licenseRequired'           => 'За активиране на {0} е необходим валиден лиценз.',
    'licenseInvalidActivation'  => 'Валидирането на лиценза за {0} е неуспешно. Моля проверете лицензния си ключ.',
    'licenseExpiredActivation'  => 'Лицензът за {0} е изтекъл. Моля подновете го, за да активирате.',
    'licenseCheckUnreachable'   => 'Не можахме да верифицираме лиценза за {0}. Лицензният сървър е недостъпен. Моля, опитайте отново по-късно.',
    'activationBlockedTampered' => '{0} не може да бъде активиран, защото е неправилно конфигуриран.',
    'activationBlockedBundled'  => '{0} не може да бъде активиран: само добавките на Pubvana могат да бъдат маркирани като включени в ядрото.',
    'activationBlockedNoUrls'   => '{0} не може да бъде активиран: платените добавки трябва да включват URL адреси за верификация на лиценза.',
    'activationBlockedFreeFlag' => '{0} не може да бъде активиран: добавките на Pubvana не могат да бъдат маркирани като безплатни.',
    'activationBlockedDisabled' => '{0} не може да бъде активиран, защото има грешки в конфигурацията. Проверете информационния файл.',

    'licenseThirdPartyLabel'    => 'Трета страна',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Стартиране на актуализацията...',
    'updateCheckLabel'           => 'Проверка за актуализации:',
    'updateAvailable'            => 'Pubvana {0} е налична!',
    'updateRunning'              => 'Използвате {0}.',
    'updateBreakingChanges'      => 'Критични промени',
    'updateMigrationNotes'       => 'Бележки за миграцията',
    'updateNotices'              => 'Известия',
    'updatePreflightTitle'       => 'Проверки преди старт',
    'updateToVersion'            => 'Актуализирай до Pubvana {0}',
    'updatePreflightFailed'      => 'Една или повече задължителни проверки преди старт са неуспешни. Моля разрешете ги преди актуализиране.',
    'updateUpToDate'             => 'Pubvana е актуален. Използвате версия {0}.',
    'updateAnyway'               => 'Актуализирай въпреки това',
    'updateAvailableTooltip'     => 'Pubvana {0} е налична',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(вие)',
    'usersNone'                  => 'Не са намерени потребители.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Акаунтът е активен',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Детайли на профила',
    'profileDisplayNameHint'     => 'Показва се в публикуваните публикации вместо потребителското име.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP или GIF. Макс 10 МБ.',
    'profileSocialHandles'       => 'Социални профили',
    'preview'                    => 'Преглед',
    'website'                    => 'Уебсайт',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Двуфакторна автентикация',
    'totpActiveDesc'             => 'TOTP двуфакторната автентикация е активна на вашия акаунт. При всяко влизане ще бъдете помолени за 6-цифрен код от вашето приложение за автентикация.',
    'totpCurrentCode'            => 'Текущ код',
    'totpInactiveDesc'           => 'Добавете допълнителен слой сигурност към акаунта си. След активиране ще трябва да въвеждате код от вашето приложение за автентикация при всяко влизане.',
    'totpEnable'                 => 'Активирай двуфакторна автентикация',
    'totpScanInstructions'       => 'Отворете вашето приложение за автентикация (Google Authenticator, Authy, 1Password и т.н.) и сканирайте този QR код.',
    'totpManualEntry'            => 'Не можете да сканирате? Въведете ръчно този код:',
    'totpConfirmInstructions'    => 'След сканиране въведете 6-цифрения код от приложението, за да потвърдите настройката.',
    'totpRecoveryWarning'        => 'Запазете вашите кодове за възстановяване. Ако загубите достъп до приложението за автентикация, няма да можете да влезете. Свържете се с администратора на сайта, за да нулирате 2FA.',

];
