<?php

/**
 * Pubvana CMS - Admin language strings (Mandarin Chinese - Simplified)
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
    'save'              => '保存',
    'saveChanges'       => '保存更改',
    'cancel'            => '取消',
    'edit'              => '编辑',
    'delete'            => '删除',
    'create'            => '创建',
    'add'               => '添加',
    'back'              => '返回',
    'view'              => '查看',
    'apply'             => '应用',
    'install'           => '安装',
    'update'            => '更新',
    'refresh'           => '刷新',
    'activate'          => '启用',
    'deactivate'        => '禁用',
    'enable'            => '开启',
    'disable'           => '关闭',
    'disabled'          => '已禁用',
    'approve'           => '批准',
    'spam'              => '垃圾',
    'trash'             => '回收站',
    'restore'           => '恢复',
    'dismiss'           => '忽略',
    'recheck'           => '重新检查',
    'clickToCopy'       => '点击复制',
    'download'          => '下载',
    'upload'            => '上传',
    'import'            => '导入',
    'export'            => '导出',
    'publish'           => '发布',
    'unpublish'         => '取消发布',
    'logout'            => '退出登录',
    'viewSite'          => '查看网站',
    'newPost'           => '新建文章',
    'buyNow'            => '立即购买',
    'visitStore'        => '访问商店',
    'loadMore'          => '加载更多',

    // Table headers / labels
    'title'             => '标题',
    'name'              => '名称',
    'slug'              => '别名',
    'status'            => '状态',
    'date'              => '日期',
    'actions'           => '操作',
    'author'            => '作者',
    'views'             => '浏览量',
    'type'              => '类型',
    'url'               => 'URL',
    'description'       => '描述',
    'role'              => '角色',
    'email'             => '邮箱',
    'username'          => '用户名',
    'active'            => '已启用',
    'version'           => '版本',
    'size'              => '大小',
    'clicks'            => '点击量',
    'total'             => '总计',
    'platform'          => '平台',
    'label'             => '标签',
    'order'             => '排序',
    'source'            => '来源',
    'content'           => '内容',
    'excerpt'           => '摘要',
    'details'           => '详情',
    'contentType'       => '内容类型',
    'seo'               => 'SEO',
    'metaTitle'         => '元标题',
    'metaDescription'   => '元描述',

    // Status badges
    'published'         => '已发布',
    'draft'             => '草稿',
    'scheduled'         => '已计划',
    'pending'           => '待审',
    'safe'              => '安全',
    'notSafe'           => '不安全',
    'malicious'         => '恶意',
    'safetyUnknown'     => '未知',
    'inactive'          => '已禁用',
    'installed'         => '已安装',
    'free'              => '免费',
    'premium'           => '付费',
    'all'               => '全部',

    // Confirmations
    'confirmDelete'         => '确定要删除此项目吗？',
    'confirmDeletePost'     => '删除此文章？',
    'confirmDeletePage'     => '删除此页面？',
    'confirmDeleteComment'  => '永久删除此评论？',
    'confirmDeleteUser'     => '删除此用户？',
    'confirmDeleteMedia'    => '删除？',
    'confirmDeleteBackup'   => '删除此备份文件？',
    'confirmBulkAction'     => '对选中的文章执行批量操作？',

    // Empty states
    'noPostsYet'        => '暂无文章。{0}',
    'noResultsFound'    => '未找到相关结果。',
    'noCommentsYet'     => '暂无待审评论。',
    'noMediaYet'        => '暂无媒体文件。',
    'noItemsFound'      => '应用市场中未找到任何项目。',
    'noCategoriesYet'   => '暂无分类。',
    'noTagsYet'         => '暂无标签。',
    'noRevisionsYet'    => '未找到修订版本。',

    // Misc common
    'permissionDenied'  => '权限不足。',
    'notFound'          => '未找到记录。',
    'commasSeparated'   => '逗号分隔',
    'optional'          => '可选',
    'required'          => '必填',
    'enabled'           => '已开启',
    'selected'          => '已选择 {0} 篇文章',
    'published_count'   => '{0} 篇已发布',
    'pending_count'     => '{0} 篇待审',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => '仪表盘',
    'navContent'        => '内容',
    'navAppearance'     => '外观',
    'navUsersAndSite'   => '用户与站点',
    'navTools'          => '工具',
    'navMarketplace'    => '应用市场',
    'navPlugins'        => '插件',
    'navPosts'          => '文章',
    'navSchedule'       => '计划',
    'navPages'          => '页面',
    'navCategories'     => '分类',
    'navTags'           => '标签',
    'navComments'       => '评论',
    'navMedia'          => '媒体',
    'navImport'         => '导入',
    'navThemes'         => '主题',
    'navWidgets'        => '小工具',
    'navNavigation'     => '导航',
    'navUsers'          => '用户',
    'navSocialLinks'    => '社交链接',
    'navRedirects'      => '重定向',
    'navLanguages'      => '语言',
    'navSettings'       => '设置',
    'navAnalytics'      => '统计分析',
    'navAffiliates'     => '联盟链接',
    'navBrokenLinks'    => '失效链接',
    'navActivityLog'    => '活动日志',
    'navBackup'         => '备份与导出',
    'navUpdates'        => '更新',
    'navBrowse'         => '浏览',
    'navLicenses'       => '许可证',
    'navPubvanaStore'   => 'Pubvana 商店',
    'navUpdateAvailable'=> '有可用更新',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => '准备离开？',
    'logoutModalBody'   => '点击下方"退出登录"以结束当前会话。',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => '仪表盘',
    'dashStats'             => '统计',
    'dashPosts'             => '文章',
    'dashPages'             => '页面',
    'dashComments'          => '评论',
    'dashUsers'             => '用户',
    'dashRecentPosts'       => '最近文章',
    'dashPendingComments'   => '待审评论',
    'dashViewAll'           => '查看全部',
    'dashCreateOne'         => '立即创建！',
    'dashNoPosts'           => '暂无文章。',
    'dashNoPendingComments' => '暂无待审评论。',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => '文章',
    'newPostTitle'          => '新建文章',
    'editPostTitle'         => '编辑文章：{0}',
    'copyPreviewLink'       => '复制预览链接',
    'backToPosts'           => '返回文章列表',
    'postTitleField'        => '标题 *',
    'postEditor'            => '编辑器',
    'postHtmlEditor'        => 'HTML 编辑器',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => '摘要',
    'postExcerptPlaceholder'=> '可选的简短摘要...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => '元标题',
    'postMetaDescription'   => '元描述',
    'postPublishSection'    => '发布',
    'postStatus'            => '状态',
    'postStatusDraft'       => '草稿',
    'postStatusPublished'   => '已发布',
    'postStatusScheduled'   => '已计划',
    'postScheduledAt'       => '计划发布时间',
    'postFeatured'          => '置顶文章',
    'postMembersOnly'       => '仅限会员',
    'postShareOnPublish'    => '发布时分享至社交媒体',
    'postSaveBtn'           => '保存文章',
    'postFeaturedImage'     => '特色图片',
    'postFeaturedImagePlaceholder' => 'URL 或上传路径…',
    'postCategories'        => '分类',
    'postTags'              => '标签',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => '修订版本',
    'postRevisionCount'     => '{0} 个修订版本',
    'postPreview'           => '预览',
    'postBulkAction'        => '- 选择操作 -',
    'postBulkPublish'       => '发布',
    'postBulkUnpublish'     => '取消发布（设为草稿）',
    'postBulkDelete'        => '删除',

    // Post flash messages
    'postCreated'           => '文章已成功创建。',
    'postUpdated'           => '文章已更新。',
    'scheduledDateMustBeFuture' => '计划发布时间必须是将来的时间。',
    'postDeleted'           => '文章已删除。',
    'postBulkUpdated'       => '{0} 篇文章已更新。',
    'postBulkInvalid'       => '无效的批量操作。',
    'postPermission'        => '您只能编辑自己的文章。',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => '修订版本：{0}',
    'revisionTitle'         => '修订版本 — {0}',
    'revisionShowTitle'     => '修订版本',
    'revisionsBackToPost'   => '返回文章',
    'revisionsBackToList'   => '返回修订列表',
    'revisionRestored'      => '文章已恢复至 {0} 的修订版本。',
    'revisionRestoreBtn'    => '恢复此修订版本',
    'revisionSaved'         => '已保存',
    'revisionBy'            => '作者',
    'revisionOn'            => '时间',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => '页面',
    'newPageTitle'          => '新建页面',
    'editPageTitle'         => '编辑页面',
    'pageSlugInUse'         => '别名"{0}"已被使用。',
    'pageCannotDelete'      => '无法删除此页面。',
    'slugAutoGenHint'       => '留空则根据标题自动生成',
    'slugCannotChange'      => '无法更改',
    'colSystem'             => '系统',
    'system'                => '系统',

    // Page flash messages
    'pageCreated'           => '页面已创建。',
    'pageUpdated'           => '页面已更新。',
    'pageDeleted'           => '页面已删除。',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => '分类',
    'newCategoryTitle'      => '新建分类',
    'editCategoryTitle'     => '编辑分类',
    'categoryName'          => '名称',
    'categoryDescription'   => '描述',
    'categoryPostCount'     => '文章数量',

    // Category flash messages
    'categoryCreated'       => '分类已创建。',
    'categoryUpdated'       => '分类已更新。',
    'categoryDeleted'       => '分类已删除。',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => '标签',
    'tagPostCount'          => '文章数量',

    // Tag flash messages
    'tagDeleted'            => '标签已删除。',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => '评论',
    'commentAuthor'         => '作者',
    'commentContent'        => '评论内容',
    'commentPost'           => '文章',
    'commentDate'           => '日期',
    'commentStatusFilter'   => '按状态筛选',

    // Comment flash messages
    'commentApproved'       => '评论已批准。',
    'commentSpam'           => '已标记为垃圾。',
    'commentTrashed'        => '评论已移至回收站。',
    'commentDeleted'        => '评论已永久删除。',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => '媒体库',
    'mediaTitle'            => '标题',
    'mediaAltText'          => '替代文本',
    'mediaAltPlaceholder'   => '为无障碍访问描述图片',
    'mediaTitlePlaceholder' => '可选的图片标题',
    'mediaImageDetails'     => '图片详情',
    'mediaSaved'            => '已保存！',
    'mediaNoSelection'      => '未选择图片',
    'mediaBrowse'           => '浏览媒体',
    'mediaRemove'           => '移除',
    'mediaUseImage'         => '使用此图片',
    'mediaDropzone'         => '将图片拖放到此处或点击浏览',
    'mediaLoading'          => '正在加载媒体…',
    'mediaEmpty'            => '暂未上传任何媒体文件。',
    'mediaUpload'           => '上传媒体',
    'mediaDragDrop'         => '将文件拖放到此处，或',
    'mediaChooseFiles'      => '选择文件',
    'mediaUploading'        => '上传中…',
    'mediaFilename'         => '文件名',
    'mediaSize'             => '大小',
    'mediaUploadFailed'     => '上传失败：{0}',
    'mediaUploadError'      => '上传错误：{0}',

    // Media flash messages
    'mediaDeleted'          => '媒体已删除。',
    'mediaNoValidFile'      => '未上传有效文件。',
    'mediaUploadSuccess'    => '文件上传成功。',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => '导航',
    'navQuickAdd'           => '快速添加',
    'navQuickAddPlaceholder' => '搜索页面、分类、插件...',
    'navItemLabel'          => '标签',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => '目标',
    'navItemOrder'          => '排序',
    'navGroupPrimary'       => '主导航',
    'navGroupFooter'        => '页脚导航',
    'navSelectGroup'        => '选择导航组：',
    'navParent'             => '父级',
    'navTopLevel'           => '— 顶级 —',
    'navSameWindow'         => '当前窗口',
    'navNewWindow'          => '新窗口',
    'navMenuItems'          => '菜单项',
    'navNoItems'            => '此菜单暂无项目。',
    'dragToReorder'         => '拖动重新排序',

    // Navigation flash messages
    'navItemAdded'          => '导航项已添加。',
    'navItemRemoved'        => '导航项已移除。',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => '主题',
    'themeOptions'          => '主题选项',
    'themeActivate'         => '启用',
    'themeOptionsBtn'       => '选项',
    'themeActive'           => '当前使用',
    'themeBy'               => '作者',
    'themeSupport'          => '支持',
    'themeVersion'          => '版本',
    'themeSaveOptions'      => '保存选项',
    'themeInvalidLicense'   => '无法启用主题 - 许可证无效。请重新安装或联系支持。',
    'themeValidationFailed' => '主题包含 PHP 代码，无法启用。',
    'noThemesInstalled'     => '未安装任何主题。请访问应用市场获取主题。',
    'themeUnapprovedTitle'  => '启用未经审核的主题？',
    'themeNotApproved'      => '此主题尚未经 Pubvana 批准。',
    'themeUnapprovedRisk'   => '启用未经审核的主题可能引入安全风险或兼容性问题。',
    'themeActivateConfirm'  => '您确定仍要启用它吗？',
    'themeActivateAnyway'   => '仍然启用',
    'themeNoOptions'        => '此主题没有可配置的选项。',
    'themeCustomize'        => '自定义主题',

    // Theme flash messages
    'themeActivated'        => '主题已启用。',
    'themeOptionsSaved'     => '选项已保存。',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => '已授权',
    'licenseCheckNow'        => '立即检查',
    'licenseExpired'         => '已过期',
    'licenseEnterKey'        => '输入密钥',
    'licenseChangeKey'       => '更改',
    'licenseRenew'           => '续期',
    'licenseThirdParty'      => '第三方',
    'unchecked'              => '未检查',
    'safetyLabel'            => '安全性：',
    'recheckBtn'             => '重新检查',
    'recheckSuccess'         => '安全检查已更新。',
    'recheckFailed'          => '无法连接验证服务器。请稍后重试。',
    'recheckNotFound'        => '未找到项目。',
    'widgetBlockedMalicious' => '{0} 已被标记为恶意，无法添加。',
    'licenseNoStoreProduct'  => '此项目未链接到商店产品。如果您购买了此项目，请从市场重新安装以启用许可。',
    'securityWarning'        => '安全警告：',
    'licenseModalTitle'      => '输入许可证密钥',
    'licenseModalBody'       => '请在下方粘贴您的许可证密钥。',
    'licenseModalSave'       => '保存',
    'licenseSaved'           => '许可证密钥已保存并验证。',
    'licenseInvalid'         => '许可证密钥无效。',
    'licenseKeyRequired'     => '许可证密钥和产品为必填项。',
    'licenseCheckFailed'     => '无法连接到许可证服务器，请稍后重试。',
    'licenseProductNotFound' => '在商店中找不到此项目。',
    'btnCancel'              => '取消',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => '小工具',
    'widgetConfigureTitle'  => '配置小工具',
    'widgetAreas'           => '小工具区域',
    'widgetAvailable'       => '可用小工具',
    'widgetAddToArea'       => '添加到区域',
    'widgetArea'            => '区域',
    'widgetNoOptions'       => '无选项。',
    'widgetSaveConfig'      => '保存配置',
    'widgetConfigure'       => '配置',
    'widgetNoAreas'         => '未找到小工具区域，请启用主题以开启小工具区域。',
    'widgetAreaEmpty'       => '此区域暂无小工具，请从列表中添加 →',

    // Widget flash messages
    'widgetAdded'           => '小工具已添加。',
    'widgetRemoved'         => '小工具已移除。',
    'widgetConfigured'      => '小工具已配置。',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => '应用市场',
    'marketplaceRefresh'    => '刷新',
    'marketplaceVisitStore' => '访问商店',
    'marketplaceAll'        => '全部',
    'marketplaceThemes'     => '主题',
    'marketplaceWidgets'    => '小工具',
    'marketplacePlugins'    => '插件',
    'marketplaceUpdatesAvailable' => '{0} 个更新可用。',
    'marketplaceBy'         => '作者',
    'marketplaceFree'       => '免费',
    'marketplaceInstalled'  => '已安装',
    'marketplaceInstall'    => '安装',
    'marketplaceBuyNow'     => '立即购买',
    'marketplaceNoItems'    => '应用市场中未找到任何项目。',
    'marketplaceInstalledVersion' => '已安装 v{0}',
    'marketplaceLoadError'  => '无法从商店加载产品，请稍后再试。',
    'byAuthor'              => '作者：{0}',
    'unknown'               => '未知',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} 安装成功。',
    'marketplaceInstallFail'    => '安装失败，请查看日志。',
    'marketplaceUpdateSuccess'  => '更新成功。',
    'marketplaceUpdateFail'     => '更新失败。',
    'marketplaceCacheRefreshed' => '应用市场缓存已刷新。',
    'marketplaceInvalidRequest' => '无效的安装请求。',
    'marketplaceCannotUpdate'   => '无法更新此项目。',

    // =========================================================================
    // Licenses
    // =========================================================================

    'licensesTitle'               => '许可证',
    'licensesNone'                => '无许可证',
    'licensesProduct'             => '产品',
    'licensesKey'                 => '许可证密钥',
    'licensesStatus'              => '状态',
    'licensesType'                => '类型',
    'licensesExpires'             => '到期时间',
    'licensesDomain'              => '域名',
    'licensesInstalled'           => '已安装',
    'licensesLastChecked'         => '最近检查',
    'licensesActions'             => '操作',
    'licensesStatusValid'         => '有效',
    'licensesStatusInvalid'       => '无效',
    'licensesStatusExpired'       => '已过期',
    'licensesStatusSubExpired'    => '订阅已过期',
    'licensesStatusUnchecked'     => '未检查',
    'licensesSubscription'        => '订阅',
    'licensesOneTime'             => '一次性',
    'licensesPerpetual'           => '永久',
    'licensesNotInstalled'        => '未安装',
    'licensesNever'               => '从未',
    'licensesRevalidate'          => '重新验证',
    'licenseKeyPlaceholder'       => '输入许可证密钥...',
    'marketplaceLicensesEmpty'    => '安装后，已授权的产品将显示在此处。',
    'typeTheme'                   => '主题',
    'typeWidget'                  => '小工具',
    'typePlugin'                  => '插件',

    // License revalidation flash messages
    'licenseRevalidateValid'       => '许可证验证成功。',
    'licenseRevalidateInvalid'     => '许可证无效或已过期。',
    'licenseRevalidateUnreachable' => '无法连接到许可证服务器，请稍后重试。',
    'licenseRevalidateSkipped'     => '许可证检查已跳过（开发模式）。',
    'licenseRevalidateNotFound'    => '未找到许可证。',

    // License warning banners
    'licenseWarningTitle'   => '许可证问题',
    'licenseWarningInvalid' => '许可证无效或已过期',
    'licenseWarningManage'  => '管理许可证',

    // Plugin license
    'pluginInvalidLicense' => '此插件的许可证无效或已过期，无法启用。',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => '许可证密钥',
    'storeBrowseFull'       => '浏览完整商店',
    'storeBackToMarketplace'=> '返回应用市场',
    'storeNoProducts'       => '暂无可用产品。',
    'storeViewInStore'      => '在商店中查看',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => '用户',
    'editUserTitle'         => '编辑用户',
    'createUserTitle'       => '创建用户',
    'authorProfileTitle'    => '作者资料',
    'userRoleLabel'         => '角色',
    'userActiveLabel'       => '已启用',
    'userPasswordLabel'     => '密码',
    'userPasswordOptional'  => '留空则保留当前密码',
    'userDisplayName'       => '显示名称',
    'userBio'               => '简介',
    'userWebsite'           => '网站',
    'userTwitter'           => 'Twitter / X 账号',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => '头像',
    'userSaveProfile'       => '保存资料',
    'userSaveChanges'       => '保存更改',
    'userCannotDeleteSelf'  => '无法删除自己的账户。',
    'userCannotDeleteOwner' => '无法删除网站所有者账户。',
    'userOwnerCannotModify' => '无法修改网站所有者账户。',

    // User flash messages
    'userCreated'           => '用户已创建。',
    'userUpdated'           => '用户已更新。',
    'userDeleted'           => '用户已删除。',
    'userBanned'            => '用户已被封禁。',
    'userUnbanned'          => '用户已解除封禁。',
    'userCannotBanSelf'     => '您无法封禁自己或网站所有者。',
    'banStatus'             => '封禁状态',
    'banned'                => '已封禁',
    'ban'                   => '封禁用户',
    'unban'                 => '解除封禁',
    'banReasonRequired'     => '封禁原因为必填项。',
    'banReasonPlaceholder'  => '封禁原因...',
    'confirmBanUser'        => '确定要封禁此用户吗？',
    'userProfileSaved'      => '资料已保存。',
    'userAvatarUploadFail'  => '头像上传失败：{0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '双因素认证设置',
    'tfaSetupHeading'       => '设置双因素认证',
    'tfaScanQr'             => '请使用您的认证应用（如 Google Authenticator、Authy）扫描以下二维码。',
    'tfaManualEntry'        => '或手动输入密钥：',
    'tfaEnterCode'          => '输入您的应用中显示的6位验证码进行确认：',
    'tfaCodeLabel'          => '验证码',
    'tfaConfirmBtn'         => '确认并启用双因素认证',
    'tfaDisableBtn'         => '禁用双因素认证',
    'tfaDisableConfirm'     => '输入当前双因素认证码以禁用：',
    'tfaEnabled'            => '双因素认证已启用。',
    'tfaDisabled'           => '双因素认证已禁用。',
    'tfaInvalidCode'        => '无效的验证码 - 请重新扫描二维码并再试一次。',
    'tfaInvalidDisable'     => '无效的验证码 - 双因素认证未被禁用。',
    'tfaSessionExpired'     => '设置会话已过期 - 请重新开始。',
    'tfaNotEnabled'         => '双因素认证当前未启用。',
    'tfaCantScan'           => "无法扫描？请手动输入此密钥：",
    'tfaWarning'            => '请将此密钥保存在安全的地方。如果丢失认证设备，您将需要它来恢复访问权限。',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => '社交链接',
    'socialPlatform'           => '平台',
    'socialUrl'                => 'URL',
    'socialIcon'               => '图标',
    'socialSortOrder'          => '排序',
    'socialIconPackInfo'       => '当前主题 <strong>{0}</strong> 使用 <strong>{1}</strong>（v{2}）作为图标。您可以在下方选择可用图标，这些图标将显示在本站的社交链接功能中。',
    'socialSearchPlaceholder'  => '搜索平台...',
    'socialIconDisclaimer'     => "这些图标仅代表将要使用的图标的示例，实际图标可能因活动主题的图标包而有所不同。",

    // Social flash messages
    'socialLinkAdded'       => '社交链接已添加。',
    'socialLinkUpdated'     => '链接已更新。',
    'socialLinkDeleted'     => '链接已删除。',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => '重定向',
    'redirectFrom'          => '来源 URL',
    'redirectTo'            => '目标 URL',
    'redirectType'          => '类型',
    'redirectAdd'           => '添加重定向',
    'redirectFromHint'      => '（相对路径，如 /old-page）',
    'redirect301'           => '301 永久重定向',
    'redirect302'           => '302 临时重定向',
    'redirectInvalidDest'   => '无效的重定向目标 URL。',

    // Redirect flash messages
    'redirectAdded'         => '重定向已添加。',
    'redirectDeleted'       => '重定向已删除。',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => '设置',
    'settingsGeneral'       => '常规',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => '邮件',
    'settingsSocialLogin'   => '社交登录',
    'settingsSocialSharing' => '社交分享',
    'settingsSpam'          => '垃圾防护',

    'generalSettingsHeading'    => '常规设置',
    'generalSiteName'           => '站点名称',
    'generalTagline'            => '副标题',
    'generalAdminEmail'         => '管理员邮箱',
    'generalPostsPerPage'       => '每页文章数',
    'generalComments'           => '评论',
    'generalCommentsEnable'     => '开启评论',
    'generalCommentModeration'  => '发布前需要审核',
    'generalMaintenanceMode'    => '维护模式',
    'generalMaintenanceEnable'  => '开启维护模式',
    'generalMaintenanceHelp'    => '访客将看到"我们即将回归"页面，管理员仍可访问网站。',
    'generalFrontPage'          => '首页',
    'generalFrontPageBlog'      => '博客索引（最新文章）',
    'generalFrontPageStatic'    => '静态页面：',
    'generalFrontPagePlugin'    => '插件页面：',
    'generalSelectPage'         => '- 选择页面 -',
    'generalSelectRoute'        => '- 选择路由 -',
    'generalFrontPageNoPlugins' => '无可用插件路由',
    'generalPageCacheTtl'       => '页面缓存 TTL',
    'settingsCacheTtlHint'      => '秒。0 = 禁用。',
    'generalSaveBtn'            => '保存常规设置',

    // General flash messages
    'generalSettingsSaved'      => '常规设置已保存。',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO 设置',
    'seoMetaDescription'        => '元描述',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => '站点地图',
    'seoSitemapEnable'          => '启用 sitemap.xml',
    'seoSitemapHelp'            => '适用于所有已发布文章和页面的标准站点地图。',
    'seoNewsSitemap'            => '启用 news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Google News 站点地图 - 列出过去 48 小时内发布的文章。',
    'seoSaveBtn'                => '保存 SEO 设置',
    'seoSettingsSaved'          => 'SEO 设置已保存。',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => '邮件设置',
    'emailFromName'             => '发件人名称',
    'emailFromAddress'          => '发件人地址',
    'emailProtocol'             => '协议',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP 主机',
    'emailSmtpPort'             => 'SMTP 端口',
    'emailSmtpEncryption'       => '加密方式',
    'emailSmtpEncryptionNone'   => '无',
    'emailSmtpUsername'         => 'SMTP 用户名',
    'emailSmtpPassword'         => 'SMTP 密码',
    'emailSaveBtn'              => '保存邮件设置',
    'emailSettingsSaved'        => '邮件设置已保存。',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => '社交登录（OAuth）',
    'socialLoginHelp'           => '凭据保存在您的 .env 文件中。请在 Google 和 Facebook 上注册您的应用以获取客户端 ID 和密钥。',
    'socialLoginGoogleId'       => '客户端 ID',
    'socialLoginGoogleSecret'   => '客户端密钥',
    'socialLoginFbAppId'        => '应用 ID',
    'socialLoginFbAppSecret'    => '应用密钥',
    'socialLoginPlaceholderSecret' => '（留空以保留现有密钥）',
    'socialLoginSaveBtn'        => '保存社交登录设置',
    'socialLoginSettingsSaved'  => '社交登录设置已保存。',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => '发布时自动分享至社交媒体',
    'socialSharingHelp'         => '当勾选"发布时分享"后发布文章，Pubvana 将自动发布到已配置的社交账户。',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => '在 developer.twitter.com → 您的应用 → Keys and Tokens 处获取密钥。',
    'socialSharingApiKey'       => 'API Key',
    'socialSharingApiSecret'    => 'API Secret',
    'socialSharingAccessToken'  => 'Access Token',
    'socialSharingAccessSecret' => 'Access Secret',
    'socialSharingFbPage'       => 'Facebook 主页',
    'socialSharingFbPageHelp'   => '需要具有 pages_manage_posts 权限的主页访问令牌。',
    'socialSharingFbPageId'     => '主页 ID',
    'socialSharingFbPageToken'  => '主页访问令牌',
    'socialSharingSaveBtn'      => '保存分享设置',
    'socialSharingSettingsSaved'=> '社交分享设置已保存。',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => '垃圾防护（hCaptcha）',
    'spamHcaptchaIntro'         => 'Pubvana 使用 hCaptcha（注重隐私，非 Google）保护评论表单和联系表单免受垃圾机器人攻击。',
    'spamHcaptchaFree'          => '大多数网站可免费使用 hCaptcha。在 hcaptcha.com 注册，创建站点后在下方输入您的密钥。',
    'spamHcaptchaSiteKey'       => '站点密钥',
    'spamHcaptchaSecretKey'     => '私密密钥',
    'spamHcaptchaNote'          => '如果未设置这些密钥，hCaptcha 将静默跳过 - 适合本地开发。保存后，小部件将自动出现在评论表单和联系页面上。',
    'spamSettingsSaved'         => '垃圾防护设置已保存。',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => '语言',
    'languageCode'              => '代码',
    'languageName'              => '名称',
    'languageDefault'           => '默认',
    'languageEnabled'           => '已启用',
    'languageMakeDefault'       => '设为默认',
    'languageSetAsDefault'      => '{0} 已设为默认语言。',
    'languageEnabled_msg'       => '{0} 已启用。',
    'languageDisabled_msg'      => '{0} 已禁用。',
    'languageNotFound'          => '未找到该语言。',
    'languageCannotDisable'     => '无法禁用默认语言。',
    'languageDirection'         => '文字方向',
    'languageNativeName'        => '本地名称',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => '统计分析',
    'analyticsTotalViews'       => '总浏览量',
    'analyticsTopPosts'         => '热门文章',
    'analyticsReferrers'        => '热门来源',
    'analyticsLast7'            => '近 7 天',
    'analyticsLast30'           => '近 30 天',
    'analyticsLast90'           => '近 90 天',
    'analyticsChartTitle'       => '页面浏览量',
    'analyticsNoData'           => '该时间段暂无分析数据。',
    'analyticsDomain'           => '域名',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => '联盟链接',
    'newAffiliateLinkTitle'     => '新建联盟链接',
    'editAffiliateLinkTitle'    => '编辑联盟链接',
    'affiliateName'             => '名称',
    'affiliateSlug'             => '别名',
    'affiliateDestination'      => '目标 URL',
    'affiliateActive'           => '已启用',
    'affiliateClicks'           => '点击量',
    'affiliateClicksTitle'      => '点击量 - {0}',
    'affiliateTotal'            => '总计',
    'affiliateViewClicks'       => '查看点击',

    // Affiliate flash messages
    'affiliateCreated'          => '联盟链接已创建。',
    'affiliateUpdated'          => '联盟链接已更新。',
    'affiliateDeleted'          => '联盟链接已删除。',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => '失效链接',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP 状态',
    'brokenLinkError'           => '错误',
    'brokenLinkSource'          => '来源',
    'brokenLinkShowDismissed'   => '显示已忽略',
    'brokenLinkHideDismissed'   => '隐藏已忽略',
    'brokenLinkTimeout'         => '超时',
    'brokenLinkBroken'          => '失效',
    'brokenLinkNone'            => '未检测到失效链接。',
    'brokenLinkNowReachable'    => '链接现已可访问 - 已从结果中移除。',
    'brokenLinkStillBroken'     => '链接仍然失效（{0}）。',
    'brokenLinkDismissed'       => '链接已忽略。',
    'brokenLinksCliHint'        => '从命令行运行完整扫描以填充此报告：<code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '发现 {0} 个问题',
    'brokenLinksCount'          => '{0} 个失效',
    'brokenLinksRecheck'        => '重新检查此 URL',
    'brokenLinksDismiss'        => '忽略（从结果中隐藏）',
    'brokenLinksRunScan'        => '运行扫描',
    'brokenLinksScanComplete'   => '扫描完成：已检查 {0} 个链接，{1} 个失效。',
    'timeout'                   => '超时',
    'typePost'                  => '文章',
    'typePage'                  => '页面',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => '活动日志',
    'activityLogType'           => '类型',
    'activityLogAction'         => '操作',
    'activityLogUser'           => '用户',
    'activityLogDate'           => '日期',
    'activityLogNote'           => '备注',
    'activityLogFilterAll'      => '所有类型',
    'activityLogEmpty'          => '暂无活动记录。',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => '备份与导出',
    'backupDownload'            => '创建并下载备份',
    'backupFiles'               => '可用备份',
    'backupFilename'            => '文件名',
    'backupSize'                => '大小',
    'backupDate'                => '创建时间',
    'backupGenerating'          => '正在生成备份…',
    'backupNoFiles'             => '暂无保存的备份。',
    'backupFailed'              => '备份失败：{0}',
    'backupDeleted'             => '备份已删除。',
    'backupCannotDelete'        => '无法删除备份。',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP 以 SHA-256 哈希存储，不记录原始个人信息。',
    'colTime'                   => '时间',
    'colIpHash'                 => 'IP 哈希',
    'colReferrer'               => '来源',
    'affiliateDirectReferrer'   => '直接访问',
    'affiliateNameHint'         => '内部标签，访客不可见。',
    'affiliateSlugHint'         => '仅限字母、数字、连字符和下划线。链接分享后不可更改。',
    'affiliateDestHint'         => '必须包含 https://，访客将被 301 重定向至此。',
    'affiliateInactiveHint'     => '非活动链接返回 404。',
    'affiliateLinkCount'        => '{0} 个链接',
    'colDomain'                 => '域名',
    'commentAll'                => '全部',
    'commentPending'            => '待审',
    'commentTrash'              => '回收站',
    'commentsNone'              => '暂无{0}评论。',

    'backupCreate'              => '创建备份',
    'backupStarting'            => '备份启动中...',
    'backupNoneYet'             => '暂无备份。点击"创建备份"以创建第一个备份。',
    'backupsTitle'              => '备份',
    'backupRetentionNote'       => '最多保留 15 个备份，最旧的将自动删除。',
    'backupRestoreConfirm'      => '恢复此备份？操作前将先创建当前状态的备份。',
    'backupDeleteConfirm'       => '删除此备份？',
    'colFilename'               => '文件名',
    'colVersion'                => '版本',
    'colTrigger'                => '触发方式',
    'colSize'                   => '大小',
    'colDate'                   => '日期',
    'colActions'                => '操作',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => '导入',
    'importWpHeading'           => '从 WordPress 导入',
    'importWpHelp'              => '通过 WordPress 的工具 → 导出功能导出您的站点，然后在下方上传 .xml 文件。',
    'importChooseFile'          => '选择 WXR 文件（.xml）',
    'importDryRun'              => '演习（仅预览，不保存任何内容）',
    'importRunBtn'              => '运行导入',
    'importNoValidFile'         => '请上传有效的 WordPress WXR 导出文件。',
    'importOnlyXml'             => '仅接受 .xml 文件。',
    'importFileTooLarge'        => '导入文件过大，最大支持 50 MB。',
    'importResultsHeading'      => '导入结果',
    'importDryRunNote'          => '演习 - 未保存任何数据。',
    'importDryRunLabel'         => '（演习 — 未写入任何数据）',
    'importComplete'            => '导入完成',
    'importCreated'             => '已创建',
    'importSkipped'             => '已跳过',
    'importErrors'              => '错误：',
    'importInstructions'        => '从 <strong>工具 → 导出 → 所有内容</strong> 导出您的 WordPress 内容，然后在此上传 <code>.xml</code> 文件。Pubvana 将导入文章、页面、分类、标签、作者和评论。',
    'importCliTitle'            => 'CLI 导入',
    'importCliHint'             => '您也可以从命令行运行导入程序：',
    'importCliDryRunHint'       => '<code>--dry-run</code> 标志显示将要导入的内容，而不会写入数据库。',
    'importWhatTitle'           => '将导入哪些内容',
    'importItemPosts'           => '文章（标题、内容、摘要、别名、状态）',
    'importItemPages'           => '页面',
    'importItemCategories'      => '分类（含层级关系）',
    'importItemTags'            => '标签',
    'importItemAuthors'         => '作者（创建为订阅者账户）',
    'importItemComments'        => '评论',
    'importItemMedia'           => '媒体文件（内容中的 URL 保留）',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => '更新',
    'updatesCurrentVersion'     => '当前版本',
    'updatesLatestVersion'      => '最新版本',
    'updatesUpToDate'           => 'Pubvana 已是最新版本。',
    'updatesAvailable'          => '有可用更新：{0}',
    'updatesCheckBtn'           => '检查更新',
    'updatesReleaseNotes'       => '发布说明',
    'updatesHowToApply'         => '如何应用更新',
    'updatesCacheCleared'       => '更新缓存已清除，正在重新检查。',
    'updatesExtCapped'          => '有可用更新：{0}（addon 兼容）',
    'updatesNewerAvailable'     => 'Pubvana {0} 也可用，请先更新以下扩展以解锁。',

    // Addon Updates
    'updatesExtTitle'               => '扩展',
    'updatesExtCheckAll'            => '全部检查',
    'updatesExtUpdateAll'           => '全部更新',
    'updatesExtCheckAllType'        => '检查所有 {0}',
    'updatesExtUpdateAllType'       => '更新所有 {0}',
    'updatesExtNoInstalled'         => '未安装任何 {0}。',
    'updatesExtColName'             => '名称',
    'updatesExtColVersion'          => '版本',
    'updatesExtColLatest'           => '最新版本',
    'updatesExtColAutoUpdate'       => '自动更新',
    'updatesExtColStatus'           => '状态',
    'updatesExtColActions'          => '操作',
    'updatesExtBundled'             => '核心捆绑',
    'updatesExtNoSource'            => '无更新来源',
    'updatesExtFailed'              => '失败',
    'updatesExtUpdatedAt'           => '{0} 更新',
    'updatesExtAvailable'           => '有可用更新',
    'updatesExtUpToDate'            => '已是最新',
    'updatesExtUpdate'              => '更新',
    'updatesExtChecking'            => '检查中...',
    'updatesExtUpdating'            => '更新中...',
    'updatesExtUpdated'             => '已更新',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => '确认更新',
    'updatesConfirmBody'            => '此操作将备份您的站点、下载并应用更新。',
    'updatesConfirmSafe'            => '您的 <code>.env</code>、<code>App.php</code> 和 <code>Database.php</code> 不会被覆盖。',
    'updatesConfirmBtn'             => '立即更新',

    // Addon Update All Modal
    'updatesExtAllTitle'            => '更新所有扩展',
    'updatesExtAllBody'             => '此操作将更新所有有待更新的扩展。',
    'updatesExtAllNote'             => '已禁用自动更新的扩展也将被更新。',
    'updatesExtAllBtn'              => '全部更新',

    'updatesExtBadge'               => '更新：v{0}',
    'updatesExtGoToUpdates'         => '更新',

    // Update Settings
    'updatesSettingsTitle'          => '更新设置',
    'updatesAutoUpdateLabel'        => 'Pubvana 自动更新',
    'updatesAutoUpdateManual'       => '手动',
    'updatesAutoUpdateAuto'         => '自动',
    'updatesAutoUpdateHelp'         => '启用后，不含破坏性更改的 Pubvana 更新将自动应用。',
    'updatesCheckMethodLabel'       => '更新检查方式',
    'updatesCheckMethodPageload'    => '页面加载',
    'updatesCheckMethodCron'        => 'Cron 任务',
    'updatesCheckMethodHelp'        => '页面加载方式在每次请求时检查（缓存 24 小时）。Cron 方式需要配置服务器 cron 任务。',
    'updatesCronCommand'            => 'Cron 命令',
    'updatesCronHelp'               => '将以下命令添加到服务器的 crontab 以每日运行更新检查：',
    'updatesSettingsSaved'          => '更新设置已保存。',

    // Compatibility
    'compatWarningTitle'            => '兼容性警告',
    'compatNotCompatible'           => '部分已安装的扩展与此版本不兼容。',
    'compatRequiresUpdate'          => '但需要先更新以下扩展：',
    'compatSupportsUpTo'            => '支持至 {0}',
    'compatRequiresMin'             => '需要 Pubvana {0}+',
    'compatNotDeclared'             => '以下扩展尚未声明与 Pubvana {0} 的兼容性，更新后可能停止工作：',
    'compatColType'                 => '类型',
    'compatColName'                 => '名称',
    'compatColVersion'              => '兼容性',
    'compatRemoveHint'              => '如有问题，可移除不兼容的扩展或切换到默认主题。每次更新前都会创建备份。',
    'compatMaxVersion'              => '最高兼容版本：{0}',
    'compatMinVersion'              => '需要 Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => '文章计划',
    'scheduleNoScheduled'       => '暂无计划发布的文章。',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => '修订版本 - {0}',
    'revisionPageTitle'         => '修订版本 - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => '您必须登录才能访问管理面板。',
    'dirNotWritable'            => '目录不可写：{0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} 配置不正确。如果您是最终用户，请联系开发者。如果您是开发者，请查阅文档。',
    'addonMisconfiguredLink'    => '{0} 配置不正确。如果您是最终用户，请<a href="{1}">联系开发者</a>。如果您是开发者，请<a href="https://github.com/enlivenapp/pubvana">查阅文档</a>。',
    'licenseExpiringSoon'       => '{0} 的许可证将于 {1} 到期，届时 {0} 将被停用。',
    'licenseExpiredDeactivated' => '{0} 已因许可证到期而停用。',
    'addonDeactivated'          => '{0} 已停用。原因：{1}。',
    'widgetValidationFailed'    => '小工具"{0}"无法验证，请联系开发者或移除该扩展。',
    'widgetValidationFailedLink' => '小工具"{0}"无法验证。<a href="{1}">联系开发者</a>或移除该扩展。',

    // Inline warnings on addon listing
    'addonDeactivatedExpired'   => '已停用：许可证已过期',
    'addonDeactivatedTampered'  => '已停用：配置不正确',
    'addonDeactivatedNoLicense' => '已停用：无有效许可证',

    // Disabled addon reasons
    'addonDisabled'             => '已禁用',
    'addonDisabledInvalidJson'  => '系统：{0} 的 {1} 无效或不可读。',
    'addonDisabledMissingFields' => '系统：{0} 缺少必填字段：{1}。',
    'addonDisabledPhpFiles'     => '系统：{0} 包含 PHP 文件。小工具只能是 JSON 和模板文件。',

    // Flash messages (on activation attempt)
    'licenseRequired'           => '启用 {0} 需要有效的许可证。',
    'licenseInvalidActivation'  => '{0} 的许可证验证失败，请检查您的许可证密钥。',
    'licenseExpiredActivation'  => '{0} 的许可证已过期，请续期后再启用。',
    'licenseCheckUnreachable'   => '无法验证 {0} 的许可证，许可证服务器不可达，请稍后重试。',
    'activationBlockedTampered' => '{0} 无法启用，因为配置不正确。',
    'activationBlockedBundled'  => '{0} 无法启用：只有 Pubvana 扩展才能标记为捆绑。',
    'activationBlockedNoUrls'   => '{0} 无法启用：付费扩展必须包含许可证验证 URL。',
    'activationBlockedFreeFlag' => '{0} 无法启用：Pubvana 扩展不能标记为免费。',
    'activationBlockedDisabled' => '{0} 无法启用，因为存在配置错误，请检查信息文件。',

    // Third-party license
    'licenseThirdPartyLabel'    => '第三方',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => '开始更新...',
    'updateCheckLabel'           => '更新检查：',
    'updateAvailable'            => 'Pubvana {0} 现已可用！',
    'updateRunning'              => '您正在运行 {0}。',
    'updateBreakingChanges'      => '破坏性更改',
    'updateMigrationNotes'       => '迁移说明',
    'updateNotices'              => '通知',
    'updatePreflightTitle'       => '飞前检查',
    'updateToVersion'            => '更新至 Pubvana {0}',
    'updatePreflightFailed'      => '一项或多项必要的飞前检查失败，请在更新前解决这些问题。',
    'updateUpToDate'             => 'Pubvana 已是最新版本，您正在运行版本 {0}。',
    'updateAnyway'               => '仍然更新',
    'updateAvailableTooltip'     => 'Pubvana {0} 可用',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '（您）',
    'usersNone'                  => '未找到用户。',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => '账户已启用',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => '资料详情',
    'profileDisplayNameHint'     => '在已发布的文章中替代用户名显示。',
    'profileAvatarHint'          => 'JPEG、PNG、WebP 或 GIF，最大 10 MB。',
    'profileSocialHandles'       => '社交账号',
    'preview'                    => '预览',
    'website'                    => '网站',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => '双因素认证',
    'totpActiveDesc'             => 'TOTP 双因素认证已在您的账户上启用。每次登录时，您都需要输入认证应用中的 6 位验证码。',
    'totpCurrentCode'            => '当前验证码',
    'totpInactiveDesc'           => '为您的账户添加额外的安全层。启用后，每次登录都需要输入认证应用中的验证码。',
    'totpEnable'                 => '启用双因素认证',
    'totpScanInstructions'       => '打开您的认证应用（Google Authenticator、Authy、1Password 等）并扫描此二维码。',
    'totpManualEntry'            => "无法扫描？请手动输入此密钥：",
    'totpConfirmInstructions'    => '扫描后，输入您的应用中显示的 6 位验证码以确认设置。',
    'totpRecoveryWarning'        => '请保存您的恢复码。如果无法访问您的认证应用，您将无法登录。请联系网站管理员重置双因素认证。',

];
