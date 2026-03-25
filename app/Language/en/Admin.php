<?php

/**
 * Pubvana CMS — Admin language strings (English)
 *
 * Convention: snake_case v1 key → camelCase CI4 key
 * Keys are grouped by feature section with comment headers.
 *
 * Usage: lang('Admin.keyName')
 */

return [

    // =========================================================================
    // Common UI — buttons, labels, confirmations, status badges
    // =========================================================================

    // Buttons
    'save'              => 'Save',
    'saveChanges'       => 'Save Changes',
    'cancel'            => 'Cancel',
    'edit'              => 'Edit',
    'delete'            => 'Delete',
    'create'            => 'Create',
    'add'               => 'Add',
    'back'              => 'Back',
    'view'              => 'View',
    'apply'             => 'Apply',
    'install'           => 'Install',
    'update'            => 'Update',
    'refresh'           => 'Refresh',
    'activate'          => 'Activate',
    'deactivate'        => 'Deactivate',
    'enable'            => 'Enable',
    'disable'           => 'Disable',
    'approve'           => 'Approve',
    'spam'              => 'Spam',
    'trash'             => 'Trash',
    'restore'           => 'Restore',
    'dismiss'           => 'Dismiss',
    'recheck'           => 'Recheck',
    'download'          => 'Download',
    'upload'            => 'Upload',
    'import'            => 'Import',
    'export'            => 'Export',
    'publish'           => 'Publish',
    'unpublish'         => 'Unpublish',
    'logout'            => 'Logout',
    'viewSite'          => 'View Site',
    'newPost'           => 'New Post',
    'buyNow'            => 'Buy Now',
    'visitStore'        => 'Visit Store',

    // Table headers / labels
    'title'             => 'Title',
    'name'              => 'Name',
    'slug'              => 'Slug',
    'status'            => 'Status',
    'date'              => 'Date',
    'actions'           => 'Actions',
    'author'            => 'Author',
    'views'             => 'Views',
    'type'              => 'Type',
    'url'               => 'URL',
    'description'       => 'Description',
    'role'              => 'Role',
    'email'             => 'Email',
    'username'          => 'Username',
    'active'            => 'Active',
    'version'           => 'Version',
    'size'              => 'Size',
    'clicks'            => 'Clicks',
    'total'             => 'Total',
    'platform'          => 'Platform',
    'label'             => 'Label',
    'order'             => 'Order',
    'source'            => 'Source',

    // Status badges
    'published'         => 'Published',
    'draft'             => 'Draft',
    'scheduled'         => 'Scheduled',
    'pending'           => 'Pending',
    'approved'          => 'Approved',
    'inactive'          => 'Inactive',
    'installed'         => 'Installed',
    'free'              => 'Free',
    'premium'           => 'Premium',
    'all'               => 'All',

    // Confirmations
    'confirmDelete'         => 'Are you sure you want to delete this item?',
    'confirmDeletePost'     => 'Delete this post?',
    'confirmDeletePage'     => 'Delete this page?',
    'confirmDeleteComment'  => 'Delete this comment permanently?',
    'confirmDeleteUser'     => 'Delete this user?',
    'confirmDeleteMedia'    => 'Delete?',
    'confirmDeleteBackup'   => 'Delete this backup file?',
    'confirmBulkAction'     => 'Apply bulk action to selected posts?',

    // Empty states
    'noPostsYet'        => 'No posts yet. {0}',
    'noResultsFound'    => 'No results found.',
    'noCommentsYet'     => 'No pending comments.',
    'noMediaYet'        => 'No media yet.',
    'noItemsFound'      => 'No items found in the marketplace.',
    'noCategoriesYet'   => 'No categories yet.',
    'noTagsYet'         => 'No tags yet.',
    'noRevisionsYet'    => 'No revisions found.',

    // Misc common
    'permissionDenied'  => 'Permission denied.',
    'notFound'          => 'Record not found.',
    'commasSeparated'   => 'Comma-separated',
    'optional'          => 'Optional short summary...',
    'selected'          => '{0} post(s) selected',
    'published_count'   => '{0} published',
    'pending_count'     => '{0} pending',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Dashboard',
    'navContent'        => 'Content',
    'navAppearance'     => 'Appearance',
    'navUsersAndSite'   => 'Users & Site',
    'navTools'          => 'Tools',
    'navMarketplace'    => 'Marketplace',
    'navPlugins'        => 'Plugins',
    'navPosts'          => 'Posts',
    'navSchedule'       => 'Schedule',
    'navPages'          => 'Pages',
    'navCategories'     => 'Categories',
    'navTags'           => 'Tags',
    'navComments'       => 'Comments',
    'navMedia'          => 'Media',
    'navImport'         => 'Import',
    'navThemes'         => 'Themes',
    'navWidgets'        => 'Widgets',
    'navNavigation'     => 'Navigation',
    'navUsers'          => 'Users',
    'navSocialLinks'    => 'Social Links',
    'navRedirects'      => 'Redirects',
    'navLanguages'      => 'Languages',
    'navSettings'       => 'Settings',
    'navAnalytics'      => 'Analytics',
    'navAffiliates'     => 'Affiliate Links',
    'navBrokenLinks'    => 'Broken Links',
    'navActivityLog'    => 'Activity Log',
    'navBackup'         => 'Backup & Export',
    'navBrowse'         => 'Browse',
    'navPremium'        => 'Premium',
    'navPubvanaStore'   => 'Pubvana Store',
    'navUpdateAvailable'=> 'Update Available',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Ready to Leave?',
    'logoutModalBody'   => 'Select "Logout" below to end your session.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Dashboard',
    'dashStats'             => 'Stats',
    'dashPosts'             => 'Posts',
    'dashPages'             => 'Pages',
    'dashComments'          => 'Comments',
    'dashUsers'             => 'Users',
    'dashRecentPosts'       => 'Recent Posts',
    'dashPendingComments'   => 'Pending Comments',
    'dashViewAll'           => 'View All',
    'dashCreateOne'         => 'Create one!',
    'dashNoPosts'           => 'No posts yet.',
    'dashNoPendingComments' => 'No pending comments.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Posts',
    'newPostTitle'          => 'New Post',
    'editPostTitle'         => 'Edit Post',
    'backToPosts'           => 'Back to Posts',
    'postTitleField'        => 'Title *',
    'postEditor'            => 'Editor',
    'postHtmlEditor'        => 'HTML Editor',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Excerpt',
    'postExcerptPlaceholder'=> 'Optional short summary...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta Title',
    'postMetaDescription'   => 'Meta Description',
    'postPublishSection'    => 'Publish',
    'postStatus'            => 'Status',
    'postStatusDraft'       => 'Draft',
    'postStatusPublished'   => 'Published',
    'postStatusScheduled'   => 'Scheduled',
    'postScheduledAt'       => 'Scheduled Date & Time',
    'postFeatured'          => 'Featured Post',
    'postMembersOnly'       => 'Members Only',
    'postShareOnPublish'    => 'Share to social on publish',
    'postSaveBtn'           => 'Save Post',
    'postFeaturedImage'     => 'Featured Image',
    'postFeaturedImagePlaceholder' => 'URL or upload path…',
    'postCategories'        => 'Categories',
    'postTags'              => 'Tags',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'Revisions',
    'postRevisionCount'     => '{0} revision(s)',
    'postPreview'           => 'Preview',
    'postBulkAction'        => '— Select action —',
    'postBulkPublish'       => 'Publish',
    'postBulkUnpublish'     => 'Unpublish (set to Draft)',
    'postBulkDelete'        => 'Delete',

    // Post flash messages
    'postCreated'           => 'Post created successfully.',
    'postUpdated'           => 'Post updated.',
    'postDeleted'           => 'Post deleted.',
    'postBulkUpdated'       => '{0} post(s) updated.',
    'postBulkInvalid'       => 'Invalid bulk action.',
    'postPermission'        => 'You can only edit your own posts.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revisions',
    'revisionShowTitle'     => 'Revision',
    'revisionsBackToPost'   => 'Back to Post',
    'revisionsBackToList'   => 'Back to Revisions',
    'revisionRestored'      => 'Post restored to revision from {0}.',
    'revisionRestoreBtn'    => 'Restore this Revision',
    'revisionBy'            => 'By',
    'revisionOn'            => 'On',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Pages',
    'newPageTitle'          => 'New Page',
    'editPageTitle'         => 'Edit Page',
    'pageSlugInUse'         => "Slug '{0}' already in use.",
    'pageCannotDelete'      => 'Cannot delete this page.',

    // Page flash messages
    'pageCreated'           => 'Page created.',
    'pageUpdated'           => 'Page updated.',
    'pageDeleted'           => 'Page deleted.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Categories',
    'newCategoryTitle'      => 'New Category',
    'editCategoryTitle'     => 'Edit Category',
    'categoryName'          => 'Name',
    'categoryDescription'   => 'Description',
    'categoryPostCount'     => 'Post Count',

    // Category flash messages
    'categoryCreated'       => 'Category created.',
    'categoryUpdated'       => 'Category updated.',
    'categoryDeleted'       => 'Category deleted.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Tags',
    'tagPostCount'          => 'Post Count',

    // Tag flash messages
    'tagDeleted'            => 'Tag deleted.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Comments',
    'commentAuthor'         => 'Author',
    'commentContent'        => 'Comment',
    'commentPost'           => 'Post',
    'commentDate'           => 'Date',
    'commentStatusFilter'   => 'Filter by status',

    // Comment flash messages
    'commentApproved'       => 'Comment approved.',
    'commentSpam'           => 'Marked as spam.',
    'commentTrashed'        => 'Comment trashed.',
    'commentDeleted'        => 'Comment deleted permanently.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaTitle'            => 'Media Library',
    'mediaUpload'           => 'Upload Media',
    'mediaDragDrop'         => 'Drag & drop files here, or',
    'mediaChooseFiles'      => 'Choose Files',
    'mediaUploading'        => 'Uploading…',
    'mediaFilename'         => 'Filename',
    'mediaSize'             => 'Size',
    'mediaUploadFailed'     => 'Upload failed: {0}',
    'mediaUploadError'      => 'Upload error: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Media deleted.',
    'mediaNoValidFile'      => 'No valid file uploaded.',
    'mediaUploadSuccess'    => 'File uploaded successfully.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navigation',
    'navItemLabel'          => 'Label',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Target',
    'navItemOrder'          => 'Sort Order',
    'navGroupPrimary'       => 'Primary',
    'navGroupFooter'        => 'Footer',
    'navSelectGroup'        => 'Select navigation group:',

    // Navigation flash messages
    'navItemAdded'          => 'Nav item added.',
    'navItemRemoved'        => 'Nav item removed.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Themes',
    'themeOptions'          => 'Theme Options',
    'themeActivate'         => 'Activate',
    'themeOptionsBtn'       => 'Options',
    'themeActive'           => 'Active',
    'themeBy'               => 'By',
    'themeVersion'          => 'Version',
    'themeSaveOptions'      => 'Save Options',
    'themeInvalidLicense'   => 'Cannot activate theme — license is invalid. Re-install or contact support.',

    // Theme flash messages
    'themeActivated'        => 'Theme activated.',
    'themeOptionsSaved'     => 'Options saved.',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widgets',
    'widgetConfigureTitle'  => 'Configure Widget',
    'widgetAreas'           => 'Widget Areas',
    'widgetAvailable'       => 'Available Widgets',
    'widgetAddToArea'       => 'Add to Area',
    'widgetArea'            => 'Area',
    'widgetNoOptions'       => 'No options.',
    'widgetSaveConfig'      => 'Save Configuration',

    // Widget flash messages
    'widgetAdded'           => 'Widget added.',
    'widgetRemoved'         => 'Widget removed.',
    'widgetConfigured'      => 'Widget configured.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Marketplace',
    'marketplaceRefresh'    => 'Refresh',
    'marketplaceVisitStore' => 'Visit Store',
    'marketplaceAll'        => 'All',
    'marketplaceThemes'     => 'Themes',
    'marketplaceWidgets'    => 'Widgets',
    'marketplaceUpdatesAvailable' => '{0} update(s) available.',
    'marketplaceBy'         => 'By',
    'marketplaceFree'       => 'Free',
    'marketplaceInstalled'  => 'Installed',
    'marketplaceInstall'    => 'Install',
    'marketplaceBuyNow'     => 'Buy Now',
    'marketplaceNoItems'    => 'No items found in the marketplace.',
    'marketplaceInstalledVersion' => 'v{0} installed',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} installed successfully.',
    'marketplaceInstallFail'    => 'Installation failed. Check logs.',
    'marketplaceUpdateSuccess'  => 'Updated successfully.',
    'marketplaceUpdateFail'     => 'Update failed.',
    'marketplaceCacheRefreshed' => 'Marketplace cache refreshed.',
    'marketplaceInvalidRequest' => 'Invalid install request.',
    'marketplaceCannotUpdate'   => 'Cannot update this item.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeTitle'            => 'Pubvana Store',
    'storeInstallBtn'       => 'Install with License',
    'storeLicenseKey'       => 'License Key',
    'storeLicenseKeyPlaceholder' => 'Paste your license key',
    'storeItemNotFound'     => 'Item not found or not a free item.',
    'storeSlugTypeRequired' => 'Item slug and item type are required.',
    'storeInstallFail'      => 'Installation failed. Please check your license key and try again. See application logs for details.',
    'storeInstallSuccess'   => '{0} "{1}" installed successfully.',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Users',
    'editUserTitle'         => 'Edit User',
    'createUserTitle'       => 'Create User',
    'authorProfileTitle'    => 'Author Profile',
    'userRoleLabel'         => 'Role',
    'userActiveLabel'       => 'Active',
    'userPasswordLabel'     => 'Password',
    'userPasswordOptional'  => 'Leave blank to keep current password',
    'userDisplayName'       => 'Display Name',
    'userBio'               => 'Bio',
    'userWebsite'           => 'Website',
    'userTwitter'           => 'Twitter / X Handle',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Save Profile',
    'userSaveChanges'       => 'Save Changes',
    'userCannotDeleteSelf'  => 'Cannot delete yourself.',
    'userCannotDeleteOwner' => 'The site owner account cannot be deleted.',
    'userOwnerCannotModify' => 'The site owner account cannot be modified.',

    // User flash messages
    'userCreated'           => 'User created.',
    'userUpdated'           => 'User updated.',
    'userDeleted'           => 'User deleted.',
    'userProfileSaved'      => 'Profile saved.',
    'userAvatarUploadFail'  => 'Avatar upload failed: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA Setup',
    'tfaSetupHeading'       => 'Set Up Two-Factor Authentication',
    'tfaScanQr'             => 'Scan the QR code below with your authenticator app (e.g. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Or enter the secret key manually:',
    'tfaEnterCode'          => 'Enter the 6-digit code from your app to confirm:',
    'tfaCodeLabel'          => 'Authentication Code',
    'tfaConfirmBtn'         => 'Confirm & Enable 2FA',
    'tfaDisableBtn'         => 'Disable 2FA',
    'tfaDisableConfirm'     => 'Enter your current 2FA code to disable:',
    'tfaEnabled'            => 'Two-factor authentication enabled.',
    'tfaDisabled'           => 'Two-factor authentication disabled.',
    'tfaInvalidCode'        => 'Invalid code — please scan the QR code and try once more.',
    'tfaInvalidDisable'     => 'Invalid code — 2FA was not disabled.',
    'tfaSessionExpired'     => 'Setup session expired — please start again.',
    'tfaNotEnabled'         => '2FA is not currently enabled.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'           => 'Social Links',
    'socialPlatform'        => 'Platform',
    'socialUrl'             => 'URL',
    'socialIcon'            => 'Icon (Font Awesome class)',
    'socialSortOrder'       => 'Sort Order',

    // Social flash messages
    'socialLinkAdded'       => 'Social link added.',
    'socialLinkUpdated'     => 'Link updated.',
    'socialLinkDeleted'     => 'Link deleted.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Redirects',
    'redirectFrom'          => 'From URL',
    'redirectTo'            => 'To URL',
    'redirectType'          => 'Type',
    'redirectAdd'           => 'Add Redirect',
    'redirectInvalidDest'   => 'Invalid redirect destination URL.',

    // Redirect flash messages
    'redirectAdded'         => 'Redirect added.',
    'redirectDeleted'       => 'Redirect deleted.',

    // =========================================================================
    // Settings — General
    // =========================================================================

    'settingsTitle'         => 'Settings',
    'settingsGeneral'       => 'General',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'Email',
    'settingsSocialLogin'   => 'Social Login',
    'settingsSocialSharing' => 'Social Sharing',
    'settingsSpam'          => 'Spam Protection',
    'settingsPremium'       => 'Premium',

    'generalSettingsHeading'    => 'General Settings',
    'generalSiteName'           => 'Site Name',
    'generalTagline'            => 'Tagline',
    'generalAdminEmail'         => 'Admin Email',
    'generalPostsPerPage'       => 'Posts Per Page',
    'generalComments'           => 'Comments',
    'generalCommentsEnable'     => 'Enable comments',
    'generalCommentModeration'  => 'Require moderation before publishing',
    'generalMaintenanceMode'    => 'Maintenance Mode',
    'generalMaintenanceEnable'  => 'Enable maintenance mode',
    'generalMaintenanceHelp'    => "Visitors see a \"We'll be back soon\" page. Admins can still access the site.",
    'generalFrontPage'          => 'Front Page',
    'generalFrontPageBlog'      => 'Blog index (latest posts)',
    'generalFrontPageStatic'    => 'Static page:',
    'generalSelectPage'         => '— Select a page —',
    'generalSaveBtn'            => 'Save General Settings',

    // General flash messages
    'generalSettingsSaved'      => 'General settings saved.',

    // =========================================================================
    // Settings — SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO Settings',
    'seoMetaDescription'        => 'Meta Description',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Sitemap',
    'seoSitemapEnable'          => 'Enable sitemap.xml',
    'seoSitemapHelp'            => 'Standard sitemap for all published posts and pages.',
    'seoNewsSitemap'            => 'Enable news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Google News sitemap — lists posts published in the last 48 hours.',
    'seoSaveBtn'                => 'Save SEO Settings',
    'seoSettingsSaved'          => 'SEO settings saved.',

    // =========================================================================
    // Settings — Email
    // =========================================================================

    'emailSettingsHeading'      => 'Email Settings',
    'emailFromName'             => 'From Name',
    'emailFromAddress'          => 'From Address',
    'emailProtocol'             => 'Protocol',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP Host',
    'emailSmtpPort'             => 'SMTP Port',
    'emailSmtpEncryption'       => 'Encryption',
    'emailSmtpEncryptionNone'   => 'None',
    'emailSmtpUsername'         => 'SMTP Username',
    'emailSmtpPassword'         => 'SMTP Password',
    'emailSaveBtn'              => 'Save Email Settings',
    'emailSettingsSaved'        => 'Email settings saved.',

    // =========================================================================
    // Settings — Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Social Login (OAuth)',
    'socialLoginHelp'           => 'Credentials are saved to your .env file. Register your app at Google and Facebook to obtain client IDs and secrets.',
    'socialLoginGoogleId'       => 'Client ID',
    'socialLoginGoogleSecret'   => 'Client Secret',
    'socialLoginFbAppId'        => 'App ID',
    'socialLoginFbAppSecret'    => 'App Secret',
    'socialLoginPlaceholderSecret' => '(leave blank to keep existing)',
    'socialLoginSaveBtn'        => 'Save Social Login Settings',
    'socialLoginSettingsSaved'  => 'Social login settings saved.',

    // =========================================================================
    // Settings — Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Social Auto-Share on Publish',
    'socialSharingHelp'         => 'When a post is published with "Share on publish" checked, Pubvana will automatically post to configured social accounts.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Get keys at developer.twitter.com → Your App → Keys and Tokens.',
    'socialSharingApiKey'       => 'API Key',
    'socialSharingApiSecret'    => 'API Secret',
    'socialSharingAccessToken'  => 'Access Token',
    'socialSharingAccessSecret' => 'Access Secret',
    'socialSharingFbPage'       => 'Facebook Page',
    'socialSharingFbPageHelp'   => 'Requires a Page Access Token with pages_manage_posts permission.',
    'socialSharingFbPageId'     => 'Page ID',
    'socialSharingFbPageToken'  => 'Page Access Token',
    'socialSharingSaveBtn'      => 'Save Sharing Settings',
    'socialSharingSettingsSaved'=> 'Social sharing settings saved.',

    // =========================================================================
    // Settings — Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Spam Protection (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana uses hCaptcha (privacy-respecting, non-Google) to protect comment forms and the contact form from spam bots.',
    'spamHcaptchaFree'          => 'hCaptcha is free for most sites. Sign up at hcaptcha.com, create a site, and add the following two keys to your .env file:',
    'spamHcaptchaNote'          => 'If these keys are not set, hCaptcha is silently skipped — safe for local development. Once keys are present in .env, the widget appears automatically on the comment form and contact page without any further configuration.',

    // =========================================================================
    // Settings — Premium / Licence
    // =========================================================================

    'premiumHeading'            => 'Pubvana Premium Core',
    'premiumDevMode'            => 'Dev mode — always active',
    'premiumStatusValid'        => 'Valid',
    'premiumStatusInvalid'      => 'Invalid',
    'premiumStatusUnreachable'  => 'Unreachable',
    'premiumStatusUnchecked'    => 'Unchecked',
    'premiumDevInfo'            => 'Running on a local dev domain — all Premium Core features are active without a licence key.',
    'premiumHelp'               => 'Enter your Pubvana Premium Core licence key to unlock premium features. Purchase at pubvana.net/store/premium.',
    'premiumLicenceKey'         => 'Licence Key',
    'premiumLicenceKeyPlaceholder' => 'XXXX-XXXX-XXXX-XXXX',
    'premiumKeyValid'           => 'Licence verified.',
    'premiumKeyInvalid'         => 'This key was not accepted. Check the key and try again.',
    'premiumKeyUnreachable'     => 'Could not reach the licence server during last check.',
    'premiumActivateBtn'        => 'Activate Licence',
    'premiumActivated'          => 'Licence key is valid. Pubvana Premium Core is active.',
    'premiumInvalidKey'         => 'Licence key is invalid or could not be verified.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Languages',
    'languageCode'              => 'Code',
    'languageName'              => 'Name',
    'languageDefault'           => 'Default',
    'languageEnabled'           => 'Enabled',
    'languageMakeDefault'       => 'Make Default',
    'languageSetAsDefault'      => '{0} set as default language.',
    'languageEnabled_msg'       => '{0} enabled.',
    'languageDisabled_msg'      => '{0} disabled.',
    'languageNotFound'          => 'Language not found.',
    'languageCannotDisable'     => 'Cannot disable the default language.',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analytics',
    'analyticsTotalViews'       => 'Total Views',
    'analyticsTopPosts'         => 'Top Posts',
    'analyticsReferrers'        => 'Top Referrers',
    'analyticsLast7'            => 'Last 7 days',
    'analyticsLast30'           => 'Last 30 days',
    'analyticsLast90'           => 'Last 90 days',
    'analyticsChartTitle'       => 'Page Views',
    'analyticsNoData'           => 'No analytics data for this period.',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Affiliate Links',
    'newAffiliateLinkTitle'     => 'New Affiliate Link',
    'editAffiliateLinkTitle'    => 'Edit Affiliate Link',
    'affiliateName'             => 'Name',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Destination URL',
    'affiliateActive'           => 'Active',
    'affiliateClicks'           => 'Clicks',
    'affiliateClicksTitle'      => 'Clicks — {0}',
    'affiliateTotal'            => 'Total',
    'affiliateViewClicks'       => 'View Clicks',

    // Affiliate flash messages
    'affiliateCreated'          => 'Affiliate link created.',
    'affiliateUpdated'          => 'Affiliate link updated.',
    'affiliateDeleted'          => 'Affiliate link deleted.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Broken Links',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP Status',
    'brokenLinkError'           => 'Error',
    'brokenLinkSource'          => 'Source',
    'brokenLinkShowDismissed'   => 'Show dismissed',
    'brokenLinkHideDismissed'   => 'Hide dismissed',
    'brokenLinkNone'            => 'No broken links detected.',
    'brokenLinkNowReachable'    => 'Link is now reachable — removed from results.',
    'brokenLinkStillBroken'     => 'Link still broken ({0}).',
    'brokenLinkDismissed'       => 'Link dismissed.',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Activity Log',
    'activityLogType'           => 'Type',
    'activityLogAction'         => 'Action',
    'activityLogUser'           => 'User',
    'activityLogDate'           => 'Date',
    'activityLogNote'           => 'Note',
    'activityLogFilterAll'      => 'All Types',
    'activityLogEmpty'          => 'No activity recorded yet.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Backup & Export',
    'backupDownload'            => 'Create & Download Backup',
    'backupFiles'               => 'Available Backups',
    'backupFilename'            => 'Filename',
    'backupSize'                => 'Size',
    'backupDate'                => 'Created',
    'backupNoFiles'             => 'No saved backups.',
    'backupFailed'              => 'Backup failed: {0}',
    'backupDeleted'             => 'Backup deleted.',
    'backupCannotDelete'        => 'Could not delete backup.',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Import',
    'importWpHeading'           => 'Import from WordPress',
    'importWpHelp'              => 'Export your WordPress site via Tools → Export, then upload the .xml file below.',
    'importChooseFile'          => 'Choose WXR File (.xml)',
    'importDryRun'              => 'Dry run (preview only — nothing is saved)',
    'importRunBtn'              => 'Run Import',
    'importNoValidFile'         => 'Please upload a valid WordPress WXR export file.',
    'importOnlyXml'             => 'Only .xml files are accepted.',
    'importFileTooLarge'        => 'Import file too large. Maximum size is 50 MB.',
    'importResultsHeading'      => 'Import Results',
    'importDryRunNote'          => 'Dry run — no data was saved.',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Updates',
    'updatesCurrentVersion'     => 'Current Version',
    'updatesLatestVersion'      => 'Latest Version',
    'updatesUpToDate'           => 'Pubvana is up to date.',
    'updatesAvailable'          => 'Update available: {0}',
    'updatesCheckBtn'           => 'Check for Updates',
    'updatesCacheCleared'       => 'Update cache cleared — re-checking now.',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Post Schedule',
    'scheduleNoScheduled'       => 'No scheduled posts.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revisions — {0}',
    'revisionPageTitle'         => 'Revision — {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'You must be logged in to access the admin panel.',
    'premiumRequired'           => 'This feature requires a Pubvana Premium Core licence. Please add your licence key below.',

];
