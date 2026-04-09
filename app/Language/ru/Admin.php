<?php

/**
 * Pubvana CMS - Admin language strings (Russian)
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
    'save'              => 'Сохранить',
    'saveChanges'       => 'Сохранить изменения',
    'cancel'            => 'Отмена',
    'edit'              => 'Редактировать',
    'delete'            => 'Удалить',
    'create'            => 'Создать',
    'add'               => 'Добавить',
    'back'              => 'Назад',
    'view'              => 'Просмотр',
    'apply'             => 'Применить',
    'install'           => 'Установить',
    'update'            => 'Обновить',
    'refresh'           => 'Обновить',
    'activate'          => 'Активировать',
    'deactivate'        => 'Деактивировать',
    'enable'            => 'Включить',
    'disable'           => 'Отключить',
    'disabled'          => 'Отключено',
    'approve'           => 'Одобрить',
    'spam'              => 'Спам',
    'trash'             => 'Корзина',
    'restore'           => 'Восстановить',
    'dismiss'           => 'Скрыть',
    'recheck'           => 'Проверить снова',
    'clickToCopy'       => 'Нажмите для копирования',
    'download'          => 'Скачать',
    'upload'            => 'Загрузить',
    'import'            => 'Импорт',
    'export'            => 'Экспорт',
    'publish'           => 'Опубликовать',
    'unpublish'         => 'Снять с публикации',
    'logout'            => 'Выйти',
    'viewSite'          => 'Просмотр сайта',
    'newPost'           => 'Новая запись',
    'buyNow'            => 'Купить сейчас',
    'visitStore'        => 'Перейти в магазин',
    'loadMore'          => 'Загрузить ещё',

    // Table headers / labels
    'title'             => 'Заголовок',
    'name'              => 'Название',
    'slug'              => 'Слаг',
    'status'            => 'Статус',
    'date'              => 'Дата',
    'actions'           => 'Действия',
    'author'            => 'Автор',
    'views'             => 'Просмотры',
    'type'              => 'Тип',
    'url'               => 'URL',
    'description'       => 'Описание',
    'role'              => 'Роль',
    'email'             => 'Email',
    'username'          => 'Имя пользователя',
    'active'            => 'Активен',
    'version'           => 'Версия',
    'size'              => 'Размер',
    'clicks'            => 'Клики',
    'total'             => 'Всего',
    'platform'          => 'Платформа',
    'label'             => 'Метка',
    'order'             => 'Порядок',
    'source'            => 'Источник',
    'content'           => 'Содержание',
    'excerpt'           => 'Отрывок',
    'details'           => 'Подробности',
    'contentType'       => 'Тип контента',
    'seo'               => 'SEO',
    'metaTitle'         => 'Мета-заголовок',
    'metaDescription'   => 'Мета-описание',

    // Status badges
    'published'         => 'Опубликовано',
    'draft'             => 'Черновик',
    'scheduled'         => 'Запланировано',
    'pending'           => 'На рассмотрении',
    'safe'              => 'Безопасный',
    'notSafe'           => 'Небезопасный',
    'malicious'         => 'Вредоносный',
    'safetyUnknown'     => 'Неизвестно',
    'inactive'          => 'Неактивен',
    'installed'         => 'Установлен',
    'free'              => 'Бесплатно',
    'premium'           => 'Премиум',
    'all'               => 'Все',

    // Confirmations
    'confirmDelete'         => 'Вы уверены, что хотите удалить этот элемент?',
    'confirmDeletePost'     => 'Удалить эту запись?',
    'confirmDeletePage'     => 'Удалить эту страницу?',
    'confirmDeleteComment'  => 'Удалить этот комментарий безвозвратно?',
    'confirmDeleteUser'     => 'Удалить этого пользователя?',
    'confirmDeleteMedia'    => 'Удалить?',
    'confirmDeleteBackup'   => 'Удалить этот файл резервной копии?',
    'confirmBulkAction'     => 'Применить массовое действие к выбранным записям?',

    // Empty states
    'noPostsYet'        => 'Записей пока нет. {0}',
    'noResultsFound'    => 'Результаты не найдены.',
    'noCommentsYet'     => 'Нет комментариев на рассмотрении.',
    'noMediaYet'        => 'Медиафайлов пока нет.',
    'noItemsFound'      => 'Элементы в маркетплейсе не найдены.',
    'noCategoriesYet'   => 'Категорий пока нет.',
    'noTagsYet'         => 'Тегов пока нет.',
    'noRevisionsYet'    => 'Версии не найдены.',

    // Misc common
    'permissionDenied'  => 'Доступ запрещён.',
    'notFound'          => 'Запись не найдена.',
    'commasSeparated'   => 'Через запятую',
    'optional'          => 'Необязательно',
    'required'          => 'Обязательно',
    'enabled'           => 'Включено',
    'selected'          => '{0} запись(ей) выбрано',
    'published_count'   => '{0} опубликовано',
    'pending_count'     => '{0} на рассмотрении',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Панель управления',
    'navContent'        => 'Контент',
    'navAppearance'     => 'Внешний вид',
    'navUsersAndSite'   => 'Пользователи и сайт',
    'navTools'          => 'Инструменты',
    'navMarketplace'    => 'Маркетплейс',
    'navPlugins'        => 'Плагины',
    'navPosts'          => 'Записи',
    'navSchedule'       => 'Расписание',
    'navPages'          => 'Страницы',
    'navCategories'     => 'Категории',
    'navTags'           => 'Теги',
    'navComments'       => 'Комментарии',
    'navMedia'          => 'Медиа',
    'navImport'         => 'Импорт',
    'navThemes'         => 'Темы',
    'navWidgets'        => 'Виджеты',
    'navNavigation'     => 'Навигация',
    'navUsers'          => 'Пользователи',
    'navSocialLinks'    => 'Социальные ссылки',
    'navRedirects'      => 'Перенаправления',
    'navLanguages'      => 'Языки',
    'navSettings'       => 'Настройки',
    'navAnalytics'      => 'Аналитика',
    'navAffiliates'     => 'Партнёрские ссылки',
    'navBrokenLinks'    => 'Битые ссылки',
    'navActivityLog'    => 'Журнал активности',
    'navBackup'         => 'Резервное копирование',
    'navUpdates'        => 'Обновления',
    'navBrowse'         => 'Обзор',
    'navLicenses'       => 'Лицензии',
    'navPubvanaStore'   => 'Магазин Pubvana',
    'navUpdateAvailable'=> 'Доступно обновление',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Выйти из системы?',
    'logoutModalBody'   => 'Нажмите «Выйти» ниже, чтобы завершить сессию.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Панель управления',
    'dashStats'             => 'Статистика',
    'dashPosts'             => 'Записи',
    'dashPages'             => 'Страницы',
    'dashComments'          => 'Комментарии',
    'dashUsers'             => 'Пользователи',
    'dashRecentPosts'       => 'Последние записи',
    'dashPendingComments'   => 'Комментарии на рассмотрении',
    'dashViewAll'           => 'Просмотреть все',
    'dashCreateOne'         => 'Создать!',
    'dashNoPosts'           => 'Записей пока нет.',
    'dashNoPendingComments' => 'Нет комментариев на рассмотрении.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Записи',
    'newPostTitle'          => 'Новая запись',
    'editPostTitle'         => 'Редактировать запись: {0}',
    'copyPreviewLink'       => 'Копировать ссылку предпросмотра',
    'backToPosts'           => 'Назад к записям',
    'postTitleField'        => 'Заголовок *',
    'postEditor'            => 'Редактор',
    'postHtmlEditor'        => 'HTML-редактор',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Отрывок',
    'postExcerptPlaceholder'=> 'Необязательное краткое описание...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Мета-заголовок',
    'postMetaDescription'   => 'Мета-описание',
    'postPublishSection'    => 'Публикация',
    'postStatus'            => 'Статус',
    'postStatusDraft'       => 'Черновик',
    'postStatusPublished'   => 'Опубликовано',
    'postStatusScheduled'   => 'Запланировано',
    'postScheduledAt'       => 'Дата и время публикации',
    'postFeatured'          => 'Избранная запись',
    'postMembersOnly'       => 'Только для участников',
    'postShareOnPublish'    => 'Поделиться в соцсетях при публикации',
    'postSaveBtn'           => 'Сохранить запись',
    'postFeaturedImage'     => 'Изображение записи',
    'postFeaturedImagePlaceholder' => 'URL или путь загрузки…',
    'postCategories'        => 'Категории',
    'postTags'              => 'Теги',
    'postTagsPlaceholder'   => 'тег1, тег2, тег3',
    'postRevisions'         => 'Версии',
    'postRevisionCount'     => '{0} версия(ий)',
    'postPreview'           => 'Предпросмотр',
    'postBulkAction'        => '- Выберите действие -',
    'postBulkPublish'       => 'Опубликовать',
    'postBulkUnpublish'     => 'Снять с публикации (сохранить как черновик)',
    'postBulkDelete'        => 'Удалить',

    // Post flash messages
    'postCreated'           => 'Запись успешно создана.',
    'postUpdated'           => 'Запись обновлена.',
    'scheduledDateMustBeFuture' => 'Дата публикации должна быть в будущем.',
    'postDeleted'           => 'Запись удалена.',
    'postBulkUpdated'       => '{0} запись(ей) обновлено.',
    'postBulkInvalid'       => 'Недопустимое массовое действие.',
    'postPermission'        => 'Вы можете редактировать только свои записи.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Версии: {0}',
    'revisionTitle'         => 'Версия — {0}',
    'revisionShowTitle'     => 'Версия',
    'revisionsBackToPost'   => 'Назад к записи',
    'revisionsBackToList'   => 'Назад к версиям',
    'revisionRestored'      => 'Запись восстановлена до версии от {0}.',
    'revisionRestoreBtn'    => 'Восстановить эту версию',
    'revisionSaved'         => 'Сохранено',
    'revisionBy'            => 'Автор:',
    'revisionOn'            => 'Дата:',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Страницы',
    'newPageTitle'          => 'Новая страница',
    'editPageTitle'         => 'Редактировать страницу',
    'pageSlugInUse'         => "Слаг '{0}' уже используется.",
    'pageCannotDelete'      => 'Эту страницу нельзя удалить.',
    'slugAutoGenHint'       => 'автоматически создаётся из заголовка, если оставить пустым',
    'slugCannotChange'      => 'нельзя изменить',
    'colSystem'             => 'Система',
    'system'                => 'Система',

    // Page flash messages
    'pageCreated'           => 'Страница создана.',
    'pageUpdated'           => 'Страница обновлена.',
    'pageDeleted'           => 'Страница удалена.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Категории',
    'newCategoryTitle'      => 'Новая категория',
    'editCategoryTitle'     => 'Редактировать категорию',
    'categoryName'          => 'Название',
    'categoryDescription'   => 'Описание',
    'categoryPostCount'     => 'Количество записей',

    // Category flash messages
    'categoryCreated'       => 'Категория создана.',
    'categoryUpdated'       => 'Категория обновлена.',
    'categoryDeleted'       => 'Категория удалена.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Теги',
    'tagPostCount'          => 'Количество записей',

    // Tag flash messages
    'tagDeleted'            => 'Тег удалён.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Комментарии',
    'commentAuthor'         => 'Автор',
    'commentContent'        => 'Комментарий',
    'commentPost'           => 'Запись',
    'commentDate'           => 'Дата',
    'commentStatusFilter'   => 'Фильтр по статусу',

    // Comment flash messages
    'commentApproved'       => 'Комментарий одобрен.',
    'commentSpam'           => 'Помечено как спам.',
    'commentTrashed'        => 'Комментарий перемещён в корзину.',
    'commentDeleted'        => 'Комментарий удалён безвозвратно.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Медиатека',
    'mediaTitle'            => 'Заголовок',
    'mediaAltText'          => 'Альтернативный текст',
    'mediaAltPlaceholder'   => 'Опишите изображение для доступности',
    'mediaTitlePlaceholder' => 'Необязательный заголовок изображения',
    'mediaImageDetails'     => 'Сведения об изображении',
    'mediaSaved'            => 'Сохранено!',
    'mediaNoSelection'      => 'Изображение не выбрано',
    'mediaBrowse'           => 'Просмотр медиа',
    'mediaRemove'           => 'Удалить',
    'mediaUseImage'         => 'Использовать это изображение',
    'mediaDropzone'         => 'Перетащите изображение сюда или нажмите для выбора',
    'mediaLoading'          => 'Загрузка медиа…',
    'mediaEmpty'            => 'Медиафайлы ещё не загружены.',
    'mediaUpload'           => 'Загрузить медиа',
    'mediaDragDrop'         => 'Перетащите файлы сюда или',
    'mediaChooseFiles'      => 'Выберите файлы',
    'mediaUploading'        => 'Загружается…',
    'mediaFilename'         => 'Имя файла',
    'mediaSize'             => 'Размер',
    'mediaUploadFailed'     => 'Ошибка загрузки: {0}',
    'mediaUploadError'      => 'Ошибка при загрузке: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Медиафайл удалён.',
    'mediaNoValidFile'      => 'Не загружен ни один допустимый файл.',
    'mediaUploadSuccess'    => 'Файл успешно загружен.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Навигация',
    'navQuickAdd'           => 'Быстрое добавление',
    'navQuickAddPlaceholder' => 'Поиск страниц, категорий, плагинов...',
    'navItemLabel'          => 'Метка',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Цель',
    'navItemOrder'          => 'Порядок сортировки',
    'navGroupPrimary'       => 'Основная',
    'navGroupFooter'        => 'Нижний колонтитул',
    'navSelectGroup'        => 'Выберите группу навигации:',
    'navParent'             => 'Родительский элемент',
    'navTopLevel'           => '— Верхний уровень —',
    'navSameWindow'         => 'Текущее окно',
    'navNewWindow'          => 'Новое окно',
    'navMenuItems'          => 'Пункты меню',
    'navNoItems'            => 'В этом меню нет элементов.',
    'dragToReorder'         => 'Перетащите для изменения порядка',

    // Navigation flash messages
    'navItemAdded'          => 'Элемент навигации добавлен.',
    'navItemRemoved'        => 'Элемент навигации удалён.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Темы',
    'themeOptions'          => 'Параметры темы',
    'themeActivate'         => 'Активировать',
    'themeOptionsBtn'       => 'Параметры',
    'themeActive'           => 'Активна',
    'themeBy'               => 'Автор:',
    'themeSupport'          => 'Поддержка',
    'themeVersion'          => 'Версия',
    'themeSaveOptions'      => 'Сохранить параметры',
    'themeInvalidLicense'   => 'Нельзя активировать тему — лицензия недействительна. Переустановите или обратитесь в поддержку.',
    'themeValidationFailed' => 'Тема содержит PHP-код и не может быть активирована.',
    'noThemesInstalled'     => 'Темы не установлены. Посетите Маркетплейс для получения тем.',
    'themeUnapprovedTitle'  => 'Активировать неодобренную тему?',
    'themeNotApproved'      => 'Эта тема не прошла проверку Pubvana.',
    'themeUnapprovedRisk'   => 'Активация неодобренных тем может создать угрозы безопасности или проблемы совместимости.',
    'themeActivateConfirm'  => 'Вы уверены, что хотите активировать её в любом случае?',
    'themeActivateAnyway'   => 'Активировать в любом случае',
    'themeNoOptions'        => 'У этой темы нет настраиваемых параметров.',
    'themeCustomize'        => 'Настроить тему',

    // Theme flash messages
    'themeActivated'        => 'Тема активирована.',
    'themeOptionsSaved'     => 'Параметры сохранены.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Лицензирован',
    'licenseCheckNow'        => 'Проверить сейчас',
    'licenseExpired'         => 'Истекла',
    'licenseEnterKey'        => 'Ввести ключ',
    'licenseChangeKey'       => 'Изменить',
    'licenseRenew'           => 'Продлить',
    'licenseThirdParty'      => 'Сторонний',
    'unchecked'              => 'Не проверено',
    'safetyLabel'            => 'Безопасность:',
    'recheckBtn'             => 'Перепроверить',
    'recheckSuccess'         => 'Проверка безопасности обновлена.',
    'recheckFailed'          => 'Не удалось связаться с сервером проверки. Попробуйте позже.',
    'recheckNotFound'        => 'Элемент не найден.',
    'securityWarning'        => 'Предупреждение безопасности:',
    'licenseModalTitle'      => 'Введите лицензионный ключ',
    'licenseModalBody'       => 'Вставьте ваш лицензионный ключ ниже.',
    'licenseModalSave'       => 'Сохранить',
    'licenseSaved'           => 'Лицензионный ключ сохранён и подтверждён.',
    'licenseInvalid'         => 'Лицензионный ключ недействителен.',
    'licenseKeyRequired'     => 'Лицензионный ключ и продукт обязательны.',
    'licenseCheckFailed'     => 'Не удалось связаться с сервером лицензий. Попробуйте позже.',
    'licenseProductNotFound' => 'Не удалось найти этот элемент в магазине.',
    'btnCancel'              => 'Отмена',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Виджеты',
    'widgetConfigureTitle'  => 'Настройка виджета',
    'widgetAreas'           => 'Области виджетов',
    'widgetAvailable'       => 'Доступные виджеты',
    'widgetAddToArea'       => 'Добавить в область',
    'widgetArea'            => 'Область',
    'widgetNoOptions'       => 'Нет параметров.',
    'widgetSaveConfig'      => 'Сохранить конфигурацию',
    'widgetConfigure'       => 'Настроить',
    'widgetNoAreas'         => 'Области виджетов не найдены. Активируйте тему, чтобы включить области виджетов.',
    'widgetAreaEmpty'       => 'В этой области нет виджетов. Добавьте один из списка →',

    // Widget flash messages
    'widgetAdded'           => 'Виджет добавлен.',
    'widgetRemoved'         => 'Виджет удалён.',
    'widgetConfigured'      => 'Виджет настроен.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Маркетплейс',
    'marketplaceRefresh'    => 'Обновить',
    'marketplaceVisitStore' => 'Перейти в магазин',
    'marketplaceAll'        => 'Все',
    'marketplaceThemes'     => 'Темы',
    'marketplaceWidgets'    => 'Виджеты',
    'marketplacePlugins'    => 'Плагины',
    'marketplaceUpdatesAvailable' => '{0} обновление(й) доступно.',
    'marketplaceBy'         => 'Автор:',
    'marketplaceFree'       => 'Бесплатно',
    'marketplaceInstalled'  => 'Установлен',
    'marketplaceInstall'    => 'Установить',
    'marketplaceBuyNow'     => 'Купить сейчас',
    'marketplaceNoItems'    => 'Элементы в маркетплейсе не найдены.',
    'marketplaceInstalledVersion' => 'v{0} установлена',
    'marketplaceLoadError'  => 'Не удалось загрузить товары из магазина. Попробуйте позже.',
    'byAuthor'              => 'Автор: {0}',
    'unknown'               => 'Неизвестно',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} успешно установлен.',
    'marketplaceInstallFail'    => 'Ошибка установки. Проверьте журналы.',
    'marketplaceUpdateSuccess'  => 'Успешно обновлено.',
    'marketplaceUpdateFail'     => 'Ошибка обновления.',
    'marketplaceCacheRefreshed' => 'Кэш маркетплейса обновлён.',
    'marketplaceInvalidRequest' => 'Недопустимый запрос на установку.',
    'marketplaceCannotUpdate'   => 'Не удаётся обновить этот элемент.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Лицензии',
    'licensesNone'                => 'Нет лицензий',
    'licensesProduct'             => 'Продукт',
    'licensesKey'                 => 'Лицензионный ключ',
    'licensesStatus'              => 'Статус',
    'licensesType'                => 'Тип',
    'licensesExpires'             => 'Истекает',
    'licensesDomain'              => 'Домен',
    'licensesInstalled'           => 'Установлен',
    'licensesLastChecked'         => 'Последняя проверка',
    'licensesActions'             => 'Действия',
    'licensesStatusValid'         => 'Действительна',
    'licensesStatusInvalid'       => 'Недействительна',
    'licensesStatusExpired'       => 'Истекла',
    'licensesStatusSubExpired'    => 'Подписка истекла',
    'licensesStatusUnchecked'     => 'Не проверено',
    'licensesSubscription'        => 'Подписка',
    'licensesOneTime'             => 'Разовая',
    'licensesPerpetual'           => 'Бессрочная',
    'licensesNotInstalled'        => 'Не установлен',
    'licensesNever'               => 'Никогда',
    'licensesRevalidate'          => 'Повторная проверка',
    'licenseKeyPlaceholder'       => 'Введите лицензионный ключ...',
    'marketplaceLicensesEmpty'    => 'Лицензированные продукты появятся здесь после установки.',
    'typeTheme'                   => 'Тема',
    'typeWidget'                  => 'Виджет',
    'typePlugin'                  => 'Плагин',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Лицензия успешно подтверждена.',
    'licenseRevalidateInvalid'     => 'Лицензия недействительна или истекла.',
    'licenseRevalidateUnreachable' => 'Не удалось связаться с сервером лицензий. Попробуйте позже.',
    'licenseRevalidateSkipped'     => 'Проверка лицензии пропущена (режим разработки).',
    'licenseRevalidateNotFound'    => 'Лицензия не найдена.',

    // License warning banners
    'licenseWarningTitle'   => 'Проблемы с лицензией',
    'licenseWarningInvalid' => 'лицензия недействительна или истекла',
    'licenseWarningManage'  => 'Управление лицензиями',

    // Plugin license
    'pluginInvalidLicense' => 'Этот плагин имеет недействительную или истекшую лицензию и не может быть активирован.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Лицензионный ключ',
    'storeBrowseFull'       => 'Просмотреть весь магазин',
    'storeBackToMarketplace'=> 'Назад в маркетплейс',
    'storeNoProducts'       => 'Нет доступных продуктов.',
    'storeViewInStore'      => 'Посмотреть в магазине',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Пользователи',
    'editUserTitle'         => 'Редактировать пользователя',
    'createUserTitle'       => 'Создать пользователя',
    'authorProfileTitle'    => 'Профиль автора',
    'userRoleLabel'         => 'Роль',
    'userActiveLabel'       => 'Активен',
    'userPasswordLabel'     => 'Пароль',
    'userPasswordOptional'  => 'Оставьте пустым, чтобы сохранить текущий пароль',
    'userDisplayName'       => 'Отображаемое имя',
    'userBio'               => 'О себе',
    'userWebsite'           => 'Веб-сайт',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Аватар',
    'userSaveProfile'       => 'Сохранить профиль',
    'userSaveChanges'       => 'Сохранить изменения',
    'userCannotDeleteSelf'  => 'Нельзя удалить самого себя.',
    'userCannotDeleteOwner' => 'Аккаунт владельца сайта нельзя удалить.',
    'userOwnerCannotModify' => 'Аккаунт владельца сайта нельзя изменить.',

    // User flash messages
    'userCreated'           => 'Пользователь создан.',
    'userUpdated'           => 'Пользователь обновлён.',
    'userDeleted'           => 'Пользователь удалён.',
    'userBanned'            => 'Пользователь заблокирован.',
    'userUnbanned'          => 'Блокировка пользователя снята.',
    'userCannotBanSelf'     => 'Нельзя заблокировать самого себя или владельца сайта.',
    'banStatus'             => 'Статус блокировки',
    'banned'                => 'Заблокирован',
    'ban'                   => 'Заблокировать',
    'unban'                 => 'Разблокировать',
    'banReasonRequired'     => 'Необходимо указать причину блокировки.',
    'banReasonPlaceholder'  => 'Причина блокировки...',
    'confirmBanUser'        => 'Вы уверены, что хотите заблокировать этого пользователя?',
    'userProfileSaved'      => 'Профиль сохранён.',
    'userAvatarUploadFail'  => 'Ошибка загрузки аватара: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Настройка 2FA',
    'tfaSetupHeading'       => 'Настройка двухфакторной аутентификации',
    'tfaScanQr'             => 'Отсканируйте QR-код ниже с помощью приложения-аутентификатора (например, Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Или введите секретный ключ вручную:',
    'tfaEnterCode'          => 'Введите 6-значный код из вашего приложения для подтверждения:',
    'tfaCodeLabel'          => 'Код аутентификации',
    'tfaConfirmBtn'         => 'Подтвердить и включить 2FA',
    'tfaDisableBtn'         => 'Отключить 2FA',
    'tfaDisableConfirm'     => 'Введите текущий код 2FA для отключения:',
    'tfaEnabled'            => 'Двухфакторная аутентификация включена.',
    'tfaDisabled'           => 'Двухфакторная аутентификация отключена.',
    'tfaInvalidCode'        => 'Неверный код — отсканируйте QR-код заново и попробуйте ещё раз.',
    'tfaInvalidDisable'     => 'Неверный код — 2FA не была отключена.',
    'tfaSessionExpired'     => 'Сессия настройки истекла — начните заново.',
    'tfaNotEnabled'         => '2FA в данный момент не включена.',
    'tfaCantScan'           => "Не можете сканировать? Введите этот код вручную:",
    'tfaWarning'            => 'Сохраните этот секретный ключ в надёжном месте. Он понадобится для восстановления доступа, если вы потеряете устройство с аутентификатором.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Социальные ссылки',
    'socialPlatform'           => 'Платформа',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Иконка',
    'socialSortOrder'          => 'Порядок сортировки',
    'socialIconPackInfo'       => 'Текущая тема <strong>{0}</strong> использует <strong>{1}</strong> (v{2}) для иконок. Ниже вы можете выбрать доступные иконки для функции Социальных ссылок сайта.',
    'socialSearchPlaceholder'  => 'Поиск платформ...',
    'socialIconDisclaimer'     => "Эти иконки являются лишь примером. Фактические иконки могут отличаться в зависимости от пакета иконок активной темы.",

    // Social flash messages
    'socialLinkAdded'       => 'Социальная ссылка добавлена.',
    'socialLinkUpdated'     => 'Ссылка обновлена.',
    'socialLinkDeleted'     => 'Ссылка удалена.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Перенаправления',
    'redirectFrom'          => 'URL источника',
    'redirectTo'            => 'URL назначения',
    'redirectType'          => 'Тип',
    'redirectAdd'           => 'Добавить перенаправление',
    'redirectFromHint'      => '(относительный, например /старая-страница)',
    'redirect301'           => '301 Постоянное',
    'redirect302'           => '302 Временное',
    'redirectInvalidDest'   => 'Недопустимый URL назначения перенаправления.',

    // Redirect flash messages
    'redirectAdded'         => 'Перенаправление добавлено.',
    'redirectDeleted'       => 'Перенаправление удалено.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Настройки',
    'settingsGeneral'       => 'Общие',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'Email',
    'settingsSocialLogin'   => 'Социальный вход',
    'settingsSocialSharing' => 'Публикация в соцсетях',
    'settingsSpam'          => 'Защита от спама',

    'generalSettingsHeading'    => 'Общие настройки',
    'generalSiteName'           => 'Название сайта',
    'generalTagline'            => 'Слоган',
    'generalAdminEmail'         => 'Email администратора',
    'generalPostsPerPage'       => 'Записей на странице',
    'generalComments'           => 'Комментарии',
    'generalCommentsEnable'     => 'Включить комментарии',
    'generalCommentModeration'  => 'Требовать модерацию перед публикацией',
    'generalMaintenanceMode'    => 'Режим обслуживания',
    'generalMaintenanceEnable'  => 'Включить режим обслуживания',
    'generalMaintenanceHelp'    => "Посетители видят страницу «Скоро вернёмся». Администраторы по-прежнему могут получить доступ к сайту.",
    'generalFrontPage'          => 'Главная страница',
    'generalFrontPageBlog'      => 'Список блога (последние записи)',
    'generalFrontPageStatic'    => 'Статическая страница:',
    'generalFrontPagePlugin'    => 'Страница плагина:',
    'generalSelectPage'         => '- Выберите страницу -',
    'generalSelectRoute'        => '- Выберите маршрут -',
    'generalFrontPageNoPlugins' => 'Маршруты плагинов недоступны',
    'generalPageCacheTtl'       => 'TTL кэша страницы',
    'settingsCacheTtlHint'      => 'Секунды. 0 = отключено.',
    'generalSaveBtn'            => 'Сохранить общие настройки',

    // General flash messages
    'generalSettingsSaved'      => 'Общие настройки сохранены.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'Настройки SEO',
    'seoMetaDescription'        => 'Мета-описание',
    'seoGoogleAnalytics'        => 'ID Google Analytics',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Карта сайта',
    'seoSitemapEnable'          => 'Включить sitemap.xml',
    'seoSitemapHelp'            => 'Стандартная карта сайта для всех опубликованных записей и страниц.',
    'seoNewsSitemap'            => 'Включить news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Карта сайта Google News — список записей, опубликованных за последние 48 часов.',
    'seoSaveBtn'                => 'Сохранить настройки SEO',
    'seoSettingsSaved'          => 'Настройки SEO сохранены.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Настройки Email',
    'emailFromName'             => 'Имя отправителя',
    'emailFromAddress'          => 'Адрес отправителя',
    'emailProtocol'             => 'Протокол',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP-хост',
    'emailSmtpPort'             => 'SMTP-порт',
    'emailSmtpEncryption'       => 'Шифрование',
    'emailSmtpEncryptionNone'   => 'Нет',
    'emailSmtpUsername'         => 'Пользователь SMTP',
    'emailSmtpPassword'         => 'Пароль SMTP',
    'emailSaveBtn'              => 'Сохранить настройки Email',
    'emailSettingsSaved'        => 'Настройки Email сохранены.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Социальный вход (OAuth)',
    'socialLoginHelp'           => 'Учётные данные сохраняются в файл .env. Зарегистрируйте приложение в Google и Facebook для получения идентификаторов клиентов и секретных ключей.',
    'socialLoginGoogleId'       => 'Идентификатор клиента',
    'socialLoginGoogleSecret'   => 'Секрет клиента',
    'socialLoginFbAppId'        => 'Идентификатор приложения',
    'socialLoginFbAppSecret'    => 'Секрет приложения',
    'socialLoginPlaceholderSecret' => '(оставьте пустым, чтобы сохранить текущий)',
    'socialLoginSaveBtn'        => 'Сохранить настройки социального входа',
    'socialLoginSettingsSaved'  => 'Настройки социального входа сохранены.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Автопубликация в соцсетях при публикации',
    'socialSharingHelp'         => 'Когда запись публикуется с отмеченным «Поделиться при публикации», Pubvana автоматически публикует в настроенных социальных аккаунтах.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Получите ключи на developer.twitter.com → Ваше приложение → Ключи и токены.',
    'socialSharingApiKey'       => 'Ключ API',
    'socialSharingApiSecret'    => 'Секрет API',
    'socialSharingAccessToken'  => 'Токен доступа',
    'socialSharingAccessSecret' => 'Секрет доступа',
    'socialSharingFbPage'       => 'Страница Facebook',
    'socialSharingFbPageHelp'   => 'Требуется токен доступа страницы с разрешением pages_manage_posts.',
    'socialSharingFbPageId'     => 'Идентификатор страницы',
    'socialSharingFbPageToken'  => 'Токен доступа страницы',
    'socialSharingSaveBtn'      => 'Сохранить настройки публикации',
    'socialSharingSettingsSaved'=> 'Настройки публикации в соцсетях сохранены.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Защита от спама (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana использует hCaptcha (с уважением к конфиденциальности, не Google) для защиты форм комментариев и контактных форм от спам-ботов.',
    'spamHcaptchaFree'          => 'hCaptcha бесплатна для большинства сайтов. Зарегистрируйтесь на hcaptcha.com, создайте сайт и введите ключи ниже.',
    'spamHcaptchaSiteKey'       => 'Ключ сайта',
    'spamHcaptchaSecretKey'     => 'Секретный ключ',
    'spamHcaptchaNote'          => 'Если эти ключи не установлены, hCaptcha тихо пропускается — безопасно для локальной разработки. После сохранения виджет автоматически появляется в форме комментариев и на странице контактов.',
    'spamSettingsSaved'         => 'Настройки защиты от спама сохранены.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Языки',
    'languageCode'              => 'Код',
    'languageName'              => 'Название',
    'languageDefault'           => 'По умолчанию',
    'languageEnabled'           => 'Включён',
    'languageMakeDefault'       => 'Сделать основным',
    'languageSetAsDefault'      => '{0} установлен как язык по умолчанию.',
    'languageEnabled_msg'       => '{0} включён.',
    'languageDisabled_msg'      => '{0} отключён.',
    'languageNotFound'          => 'Язык не найден.',
    'languageCannotDisable'     => 'Нельзя отключить язык по умолчанию.',
    'languageDirection'         => 'Направление',
    'languageNativeName'        => 'Родное название',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Аналитика',
    'analyticsTotalViews'       => 'Всего просмотров',
    'analyticsTopPosts'         => 'Популярные записи',
    'analyticsReferrers'        => 'Топ-источники трафика',
    'analyticsLast7'            => 'Последние 7 дней',
    'analyticsLast30'           => 'Последние 30 дней',
    'analyticsLast90'           => 'Последние 90 дней',
    'analyticsChartTitle'       => 'Просмотры страниц',
    'analyticsNoData'           => 'Нет аналитических данных за этот период.',
    'analyticsDomain'           => 'Домен',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Партнёрские ссылки',
    'newAffiliateLinkTitle'     => 'Новая партнёрская ссылка',
    'editAffiliateLinkTitle'    => 'Редактировать партнёрскую ссылку',
    'affiliateName'             => 'Название',
    'affiliateSlug'             => 'Слаг',
    'affiliateDestination'      => 'URL назначения',
    'affiliateActive'           => 'Активна',
    'affiliateClicks'           => 'Клики',
    'affiliateClicksTitle'      => 'Клики — {0}',
    'affiliateTotal'            => 'Всего',
    'affiliateViewClicks'       => 'Просмотр кликов',

    // Affiliate flash messages
    'affiliateCreated'          => 'Партнёрская ссылка создана.',
    'affiliateUpdated'          => 'Партнёрская ссылка обновлена.',
    'affiliateDeleted'          => 'Партнёрская ссылка удалена.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Битые ссылки',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP-статус',
    'brokenLinkError'           => 'Ошибка',
    'brokenLinkSource'          => 'Источник',
    'brokenLinkShowDismissed'   => 'Показать скрытые',
    'brokenLinkHideDismissed'   => 'Скрыть скрытые',
    'brokenLinkTimeout'         => 'Таймаут',
    'brokenLinkBroken'          => 'битая',
    'brokenLinkNone'            => 'Битые ссылки не обнаружены.',
    'brokenLinkNowReachable'    => 'Ссылка теперь доступна — удалена из результатов.',
    'brokenLinkStillBroken'     => 'Ссылка по-прежнему битая ({0}).',
    'brokenLinkDismissed'       => 'Ссылка скрыта.',
    'brokenLinksCliHint'        => 'Запустите полное сканирование из командной строки для заполнения отчёта: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} проблема(ы) найдено',
    'brokenLinksCount'          => '{0} битых',
    'brokenLinksRecheck'        => 'Проверить этот URL снова',
    'brokenLinksDismiss'        => 'Скрыть (убрать из результатов)',
    'brokenLinksRunScan'        => 'Запустить сканирование',
    'brokenLinksScanComplete'   => 'Сканирование завершено: проверено {0} ссылок, {1} битых.',
    'timeout'                   => 'Таймаут',
    'typePost'                  => 'Запись',
    'typePage'                  => 'Страница',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Журнал активности',
    'activityLogType'           => 'Тип',
    'activityLogAction'         => 'Действие',
    'activityLogUser'           => 'Пользователь',
    'activityLogDate'           => 'Дата',
    'activityLogNote'           => 'Примечание',
    'activityLogFilterAll'      => 'Все типы',
    'activityLogEmpty'          => 'Активность пока не записана.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Резервное копирование',
    'backupDownload'            => 'Создать и скачать резервную копию',
    'backupFiles'               => 'Доступные резервные копии',
    'backupFilename'            => 'Имя файла',
    'backupSize'                => 'Размер',
    'backupDate'                => 'Создано',
    'backupGenerating'          => 'Создание резервной копии…',
    'backupNoFiles'             => 'Нет сохранённых резервных копий.',
    'backupFailed'              => 'Ошибка резервного копирования: {0}',
    'backupDeleted'             => 'Резервная копия удалена.',
    'backupCannotDelete'        => 'Не удалось удалить резервную копию.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP-адреса хранятся в виде SHA-256 хешей — необработанные персональные данные не записываются.',
    'colTime'                   => 'Время',
    'colIpHash'                 => 'Хеш IP',
    'colReferrer'               => 'Источник',
    'affiliateDirectReferrer'   => 'Прямой',
    'affiliateNameHint'         => 'Внутренняя метка — не отображается посетителям.',
    'affiliateSlugHint'         => 'Только буквы, цифры, дефисы и символы подчёркивания. Нельзя изменить после того, как ссылки расшарены.',
    'affiliateDestHint'         => 'Должен включать https://. Посетители будут перенаправлены 301 сюда.',
    'affiliateInactiveHint'     => 'Неактивные ссылки возвращают 404.',
    'affiliateLinkCount'        => '{0} ссылок',
    'colDomain'                 => 'Домен',
    'commentAll'                => 'Все',
    'commentPending'            => 'На рассмотрении',
    'commentTrash'              => 'Корзина',
    'commentsNone'              => 'Нет комментариев {0}.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Создать резервную копию',
    'backupStarting'            => 'Начало резервного копирования...',
    'backupNoneYet'             => 'Резервных копий пока нет. Нажмите «Создать резервную копию», чтобы создать первую.',
    'backupsTitle'              => 'Резервные копии',
    'backupRetentionNote'       => 'Хранится не более 15 резервных копий — старые удаляются автоматически.',
    'backupRestoreConfirm'      => 'Восстановить из этой резервной копии? Сначала будет создана резервная копия текущего состояния.',
    'backupDeleteConfirm'       => 'Удалить эту резервную копию?',
    'colFilename'               => 'Имя файла',
    'colVersion'                => 'Версия',
    'colTrigger'                => 'Триггер',
    'colSize'                   => 'Размер',
    'colDate'                   => 'Дата',
    'colActions'                => 'Действия',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Импорт',
    'importWpHeading'           => 'Импорт из WordPress',
    'importWpHelp'              => 'Экспортируйте сайт WordPress через Инструменты → Экспорт, затем загрузите .xml файл ниже.',
    'importChooseFile'          => 'Выберите WXR файл (.xml)',
    'importDryRun'              => 'Пробный запуск (только предпросмотр — ничего не сохраняется)',
    'importRunBtn'              => 'Запустить импорт',
    'importNoValidFile'         => 'Пожалуйста, загрузите допустимый файл экспорта WordPress WXR.',
    'importOnlyXml'             => 'Принимаются только файлы .xml.',
    'importFileTooLarge'        => 'Файл импорта слишком большой. Максимальный размер — 50 МБ.',
    'importResultsHeading'      => 'Результаты импорта',
    'importDryRunNote'          => 'Пробный запуск — данные не были сохранены.',
    'importDryRunLabel'         => '(Пробный запуск — данные не записаны)',
    'importComplete'            => 'Импорт завершён',
    'importCreated'             => 'создано',
    'importSkipped'             => 'пропущено',
    'importErrors'              => 'Ошибки:',
    'importInstructions'        => 'Экспортируйте контент WordPress через <strong>Инструменты → Экспорт → Весь контент</strong> и загрузите файл <code>.xml</code> сюда. Pubvana импортирует записи, страницы, категории, теги, авторов и комментарии.',
    'importCliTitle'            => 'Импорт через CLI',
    'importCliHint'             => 'Также можно запустить импортёр из командной строки:',
    'importCliDryRunHint'       => 'Флаг <code>--dry-run</code> показывает, что будет импортировано, без записи в базу данных.',
    'importWhatTitle'           => 'Что импортируется',
    'importItemPosts'           => 'Записи (заголовок, содержание, отрывок, слаг, статус)',
    'importItemPages'           => 'Страницы',
    'importItemCategories'      => 'Категории (с иерархией)',
    'importItemTags'            => 'Теги',
    'importItemAuthors'         => 'Авторы (создаются как подписчики)',
    'importItemComments'        => 'Комментарии',
    'importItemMedia'           => 'Медиафайлы (URL сохраняются в содержании)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Обновления',
    'updatesCurrentVersion'     => 'Текущая версия',
    'updatesLatestVersion'      => 'Последняя версия',
    'updatesUpToDate'           => 'Pubvana актуален.',
    'updatesAvailable'          => 'Доступно обновление: {0}',
    'updatesCheckBtn'           => 'Проверить обновления',
    'updatesReleaseNotes'       => 'Примечания к выпуску',
    'updatesHowToApply'         => 'Как применить обновление',
    'updatesCacheCleared'       => 'Кэш обновлений очищен — проверка выполняется снова.',
    'updatesExtCapped'          => 'Доступно обновление: {0} (безопасно для дополнений)',
    'updatesNewerAvailable'     => 'Pubvana {0} также доступен — обновите перечисленные ниже дополнения, чтобы разблокировать его.',

    // Addon Updates
    'updatesExtTitle'               => 'Дополнения',
    'updatesExtCheckAll'            => 'Проверить все',
    'updatesExtUpdateAll'           => 'Обновить все',
    'updatesExtCheckAllType'        => 'Проверить все {0}',
    'updatesExtUpdateAllType'       => 'Обновить все {0}',
    'updatesExtNoInstalled'         => 'Нет установленных {0}.',
    'updatesExtColName'             => 'Название',
    'updatesExtColVersion'          => 'Версия',
    'updatesExtColLatest'           => 'Последняя',
    'updatesExtColAutoUpdate'       => 'Автообновление',
    'updatesExtColStatus'           => 'Статус',
    'updatesExtColActions'          => 'Действия',
    'updatesExtBundled'             => 'Включено в ядро',
    'updatesExtNoSource'            => 'Нет источника обновлений',
    'updatesExtFailed'              => 'Ошибка',
    'updatesExtUpdatedAt'           => 'Обновлено {0}',
    'updatesExtAvailable'           => 'Доступно обновление',
    'updatesExtUpToDate'            => 'Актуально',
    'updatesExtUpdate'              => 'Обновить',
    'updatesExtChecking'            => 'Проверка...',
    'updatesExtUpdating'            => 'Обновление...',
    'updatesExtUpdated'             => 'Обновлено',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Подтверждение обновления',
    'updatesConfirmBody'            => 'Будет создана резервная копия сайта, загружено и применено обновление.',
    'updatesConfirmSafe'            => 'Ваши <code>.env</code>, <code>App.php</code> и <code>Database.php</code> никогда не перезаписываются.',
    'updatesConfirmBtn'             => 'Обновить сейчас',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Обновить все дополнения',
    'updatesExtAllBody'             => 'Все дополнения с ожидающими обновлениями будут обновлены.',
    'updatesExtAllNote'             => 'Дополнения с отключённым автообновлением также будут обновлены.',
    'updatesExtAllBtn'              => 'Обновить все',

    'updatesExtBadge'               => 'Обновление: v{0}',
    'updatesExtGoToUpdates'         => 'Обновления',

    // Update Settings
    'updatesSettingsTitle'          => 'Настройки обновлений',
    'updatesAutoUpdateLabel'        => 'Автообновление Pubvana',
    'updatesAutoUpdateManual'       => 'Вручную',
    'updatesAutoUpdateAuto'         => 'Автоматически',
    'updatesAutoUpdateHelp'         => 'При включении обновления Pubvana без критических изменений применяются автоматически.',
    'updatesCheckMethodLabel'       => 'Метод проверки обновлений',
    'updatesCheckMethodPageload'    => 'При загрузке страницы',
    'updatesCheckMethodCron'        => 'Cron-задание',
    'updatesCheckMethodHelp'        => 'Проверка при загрузке страницы выполняется при каждом запросе (кэш 24ч). Cron требует серверного cron-задания.',
    'updatesCronCommand'            => 'Команда Cron',
    'updatesCronHelp'               => 'Добавьте в crontab сервера для ежедневной проверки обновлений:',
    'updatesSettingsSaved'          => 'Настройки обновлений сохранены.',

    // Compatibility
    'compatWarningTitle'            => 'Предупреждение совместимости',
    'compatNotCompatible'           => 'Некоторые установленные дополнения несовместимы с этой версией.',
    'compatRequiresUpdate'          => 'но требует предварительного обновления следующих дополнений:',
    'compatSupportsUpTo'            => 'поддерживает до {0}',
    'compatRequiresMin'             => 'требуется Pubvana {0}+',
    'compatNotDeclared'             => 'Следующие дополнения не объявили совместимость с Pubvana {0}. После обновления они могут перестать работать:',
    'compatColType'                 => 'Тип',
    'compatColName'                 => 'Название',
    'compatColVersion'              => 'Совместимость',
    'compatRemoveHint'              => 'При возникновении проблем можно удалить несовместимые дополнения или переключиться на тему по умолчанию. Перед каждым обновлением создаётся резервная копия.',
    'compatMaxVersion'              => 'Максимальная совместимая версия: {0}',
    'compatMinVersion'              => 'Требуется Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Расписание публикаций',
    'scheduleNoScheduled'       => 'Запланированных публикаций нет.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Версии - {0}',
    'revisionPageTitle'         => 'Версия - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Для доступа к панели администратора необходимо войти в систему.',
    'dirNotWritable'            => 'Директория недоступна для записи: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} настроен неправильно. Если вы конечный пользователь, обратитесь к разработчику. Если вы разработчик, обратитесь к документации.',
    'addonMisconfiguredLink'    => '{0} настроен неправильно. Если вы конечный пользователь, <a href="{1}">обратитесь к разработчику</a>. Если вы разработчик, <a href="https://github.com/enlivenapp/pubvana">обратитесь к документации</a>.',
    'licenseExpiringSoon'       => 'Лицензия {0} истекает {1}. {0} будет деактивирован по истечении лицензии.',
    'licenseExpiredDeactivated' => '{0} деактивирован, так как лицензия истекла.',
    'addonDeactivated'          => '{0} деактивирован. Причина: {1}.',
    'widgetValidationFailed'    => "Виджет ''{0}'' не прошёл проверку. Обратитесь к разработчику или удалите дополнение.",
    'widgetValidationFailedLink' => "Виджет ''{0}'' не прошёл проверку. <a href=\"{1}\">Обратитесь к разработчику</a> или удалите дополнение.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Деактивирован: лицензия истекла',
    'addonDeactivatedTampered'  => 'Деактивирован: неправильная конфигурация',
    'addonDeactivatedNoLicense' => 'Деактивирован: нет действительной лицензии',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Отключено',
    'addonDisabledInvalidJson'  => 'Система: {0} имеет недействительный или нечитаемый {1}.',
    'addonDisabledMissingFields' => 'Система: {0} отсутствуют обязательные поля: {1}.',
    'addonDisabledPhpFiles'     => 'Система: {0} содержит PHP-файлы. Виджеты должны состоять только из JSON + шаблонов.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'Для активации {0} требуется действительная лицензия.',
    'licenseInvalidActivation'  => 'Проверка лицензии для {0} не прошла. Проверьте ваш лицензионный ключ.',
    'licenseExpiredActivation'  => 'Лицензия для {0} истекла. Продлите для активации.',
    'licenseCheckUnreachable'   => 'Не удалось проверить лицензию для {0}. Сервер лицензий недоступен. Попробуйте позже.',
    'activationBlockedTampered' => '{0} нельзя активировать, так как он настроен неправильно.',
    'activationBlockedBundled'  => '{0} нельзя активировать: только дополнения Pubvana могут быть отмечены как встроенные.',
    'activationBlockedNoUrls'   => '{0} нельзя активировать: платные дополнения должны включать URL для проверки лицензии.',
    'activationBlockedFreeFlag' => '{0} нельзя активировать: дополнения Pubvana нельзя пометить как бесплатные.',
    'activationBlockedDisabled' => '{0} нельзя активировать из-за ошибок конфигурации. Проверьте информационный файл.',

    // Third-party license
    'licenseThirdPartyLabel'    => 'Сторонний',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Начало обновления...',
    'updateCheckLabel'           => 'Проверка обновлений:',
    'updateAvailable'            => 'Доступен Pubvana {0}!',
    'updateRunning'              => 'Вы используете {0}.',
    'updateBreakingChanges'      => 'Критические изменения',
    'updateMigrationNotes'       => 'Примечания по миграции',
    'updateNotices'              => 'Уведомления',
    'updatePreflightTitle'       => 'Предварительные проверки',
    'updateToVersion'            => 'Обновить до Pubvana {0}',
    'updatePreflightFailed'      => 'Одна или несколько обязательных предварительных проверок не прошли. Устраните их перед обновлением.',
    'updateUpToDate'             => 'Pubvana актуален. Вы используете версию {0}.',
    'updateAnyway'               => 'Обновить в любом случае',
    'updateAvailableTooltip'     => 'Доступен Pubvana {0}',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(вы)',
    'usersNone'                  => 'Пользователи не найдены.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Аккаунт активен',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Данные профиля',
    'profileDisplayNameHint'     => 'Отображается на опубликованных записях вместо имени пользователя.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP или GIF. Макс. 10 МБ.',
    'profileSocialHandles'       => 'Социальные сети',
    'preview'                    => 'Предпросмотр',
    'website'                    => 'Веб-сайт',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Двухфакторная аутентификация',
    'totpActiveDesc'             => 'TOTP двухфакторная аутентификация активна на вашем аккаунте. При каждом входе вас будут просить ввести 6-значный код из приложения-аутентификатора.',
    'totpCurrentCode'            => 'Текущий код',
    'totpInactiveDesc'           => 'Добавьте дополнительный уровень безопасности для вашего аккаунта. После включения вам нужно будет вводить код из приложения-аутентификатора при каждом входе.',
    'totpEnable'                 => 'Включить двухфакторную аутентификацию',
    'totpScanInstructions'       => 'Откройте приложение-аутентификатор (Google Authenticator, Authy, 1Password и т.д.) и отсканируйте этот QR-код.',
    'totpManualEntry'            => "Не можете сканировать? Введите этот код вручную:",
    'totpConfirmInstructions'    => 'После сканирования введите 6-значный код из приложения для подтверждения настройки.',
    'totpRecoveryWarning'        => 'Сохраните коды восстановления. Если вы потеряете доступ к приложению-аутентификатору, вы не сможете войти. Обратитесь к администратору сайта для сброса 2FA.',

];
