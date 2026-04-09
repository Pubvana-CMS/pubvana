<?php

/**
 * Pubvana CMS - Admin language strings (English)
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
    'disabled'          => 'Disabled',
    'approve'           => 'Approve',
    'spam'              => 'Spam',
    'trash'             => 'Trash',
    'restore'           => 'Restore',
    'dismiss'           => 'Dismiss',
    'recheck'           => 'Recheck',
    'clickToCopy'       => 'Click to Copy',
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
    'loadMore'          => 'Load More',

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
    'content'           => 'Content',
    'excerpt'           => 'Excerpt',
    'details'           => 'Details',
    'contentType'       => 'Content type',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta Title',
    'metaDescription'   => 'Meta Description',

    // Status badges
    'published'         => 'Published',
    'draft'             => 'Draft',
    'scheduled'         => 'Scheduled',
    'pending'           => 'Pending',
    'safe'              => 'Safe',
    'notSafe'           => 'Not Safe',
    'malicious'         => 'Malicious',
    'safetyUnknown'     => 'Unknown',
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
    'optional'          => 'Optional',
    'required'          => 'Required',
    'enabled'           => 'Enabled',
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
    'navUpdates'        => 'Updates',
    'navBrowse'         => 'Browse',
    'navLicenses'       => 'Licenses',
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
    'editPostTitle'         => 'Edit Post: {0}',
    'copyPreviewLink'       => 'Copy Preview Link',
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
    'postBulkAction'        => '- Select action -',
    'postBulkPublish'       => 'Publish',
    'postBulkUnpublish'     => 'Unpublish (set to Draft)',
    'postBulkDelete'        => 'Delete',

    // Post flash messages
    'postCreated'           => 'Post created successfully.',
    'postUpdated'           => 'Post updated.',
    'scheduledDateMustBeFuture' => 'Scheduled date must be in the future.',
    'postDeleted'           => 'Post deleted.',
    'postBulkUpdated'       => '{0} post(s) updated.',
    'postBulkInvalid'       => 'Invalid bulk action.',
    'postPermission'        => 'You can only edit your own posts.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revisions: {0}',
    'revisionTitle'         => 'Revision — {0}',
    'revisionShowTitle'     => 'Revision',
    'revisionsBackToPost'   => 'Back to Post',
    'revisionsBackToList'   => 'Back to Revisions',
    'revisionRestored'      => 'Post restored to revision from {0}.',
    'revisionRestoreBtn'    => 'Restore this Revision',
    'revisionSaved'         => 'Saved',
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
    'slugAutoGenHint'       => 'auto-generated from title if left blank',
    'slugCannotChange'      => 'cannot change',
    'colSystem'             => 'System',
    'system'                => 'System',

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

    'mediaLibrary'          => 'Media Library',
    'mediaTitle'            => 'Title',
    'mediaAltText'          => 'Alt Text',
    'mediaAltPlaceholder'   => 'Describe the image for accessibility',
    'mediaTitlePlaceholder' => 'Optional image title',
    'mediaImageDetails'     => 'Image Details',
    'mediaSaved'            => 'Saved!',
    'mediaNoSelection'      => 'No image selected',
    'mediaBrowse'           => 'Browse Media',
    'mediaRemove'           => 'Remove',
    'mediaUseImage'         => 'Use This Image',
    'mediaDropzone'         => 'Drag & drop image here or click to browse',
    'mediaLoading'          => 'Loading media…',
    'mediaEmpty'            => 'No media uploaded yet.',
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
    'navQuickAdd'           => 'Quick Add',
    'navQuickAddPlaceholder' => 'Search pages, categories, plugins...',
    'navItemLabel'          => 'Label',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Target',
    'navItemOrder'          => 'Sort Order',
    'navGroupPrimary'       => 'Primary',
    'navGroupFooter'        => 'Footer',
    'navSelectGroup'        => 'Select navigation group:',
    'navParent'             => 'Parent',
    'navTopLevel'           => '— Top level —',
    'navSameWindow'         => 'Same window',
    'navNewWindow'          => 'New window',
    'navMenuItems'          => 'Menu Items',
    'navNoItems'            => 'No items in this menu.',
    'dragToReorder'         => 'Drag to reorder',

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
    'themeSupport'          => 'Support',
    'themeVersion'          => 'Version',
    'themeSaveOptions'      => 'Save Options',
    'themeInvalidLicense'   => 'Cannot activate theme - license is invalid. Re-install or contact support.',
    'themeValidationFailed' => 'Theme contains PHP code and cannot be activated.',
    'noThemesInstalled'     => 'No themes installed. Visit the Marketplace to get themes.',
    'themeUnapprovedTitle'  => 'Activate Unapproved Theme?',
    'themeNotApproved'      => 'This theme has not been approved by Pubvana.',
    'themeUnapprovedRisk'   => 'Activating unapproved themes may introduce security risks or compatibility issues.',
    'themeActivateConfirm'  => 'Are you sure you want to activate it anyway?',
    'themeActivateAnyway'   => 'Activate Anyway',
    'themeNoOptions'        => 'This theme has no configurable options.',
    'themeCustomize'        => 'Customize Theme',

    // Theme flash messages
    'themeActivated'        => 'Theme activated.',
    'themeOptionsSaved'     => 'Options saved.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Licensed',
    'licenseCheckNow'        => 'Check Now',
    'licenseExpired'         => 'Expired',
    'licenseEnterKey'        => 'Enter Key',
    'licenseChangeKey'       => 'Change',
    'licenseRenew'           => 'Renew',
    'licenseThirdParty'      => 'Third Party',
    'unchecked'              => 'Unchecked',
    'safetyLabel'            => 'Safety:',
    'recheckBtn'             => 'Recheck',
    'recheckSuccess'         => 'Safety check updated.',
    'recheckFailed'          => 'Could not reach the vetting server. Please try again later.',
    'recheckNotFound'        => 'Item not found.',
    'widgetBlockedMalicious' => '{0} has been flagged as malicious and cannot be added.',
    'licenseNoStoreProduct'  => 'This item is not linked to a store product. If you purchased this item, please reinstall it from the marketplace to enable licensing.',
    'securityWarning'        => 'Security Warning:',
    'licenseModalTitle'      => 'Enter License Key',
    'licenseModalBody'       => 'Paste your license key below.',
    'licenseModalSave'       => 'Save',
    'licenseSaved'           => 'License key saved and validated.',
    'licenseInvalid'         => 'License key is not valid.',
    'licenseKeyRequired'     => 'License key and product are required.',
    'licenseCheckFailed'     => 'Could not reach the license server. Please try again later.',
    'licenseProductNotFound' => 'Could not find this item in the store.',
    'btnCancel'              => 'Cancel',

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
    'widgetConfigure'       => 'Configure',
    'widgetNoAreas'         => 'No widget areas found. Activate a theme to enable widget areas.',
    'widgetAreaEmpty'       => 'No widgets in this area. Add one from the list →',

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
    'marketplacePlugins'    => 'Plugins',
    'marketplaceUpdatesAvailable' => '{0} update(s) available.',
    'marketplaceBy'         => 'By',
    'marketplaceFree'       => 'Free',
    'marketplaceInstalled'  => 'Installed',
    'marketplaceInstall'    => 'Install',
    'marketplaceBuyNow'     => 'Buy Now',
    'marketplaceNoItems'    => 'No items found in the marketplace.',
    'marketplaceInstalledVersion' => 'v{0} installed',
    'marketplaceLoadError'  => 'Could not load products from the store. Please check back later.',
    'byAuthor'              => 'By {0}',
    'unknown'               => 'Unknown',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} installed successfully.',
    'marketplaceInstallFail'    => 'Installation failed. Check logs.',
    'marketplaceUpdateSuccess'  => 'Updated successfully.',
    'marketplaceUpdateFail'     => 'Update failed.',
    'marketplaceCacheRefreshed' => 'Marketplace cache refreshed.',
    'marketplaceInvalidRequest' => 'Invalid install request.',
    'marketplaceCannotUpdate'   => 'Cannot update this item.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licenses',
    'licensesNone'                => 'No Licenses',
    'licensesProduct'             => 'Product',
    'licensesKey'                 => 'License Key',
    'licensesStatus'              => 'Status',
    'licensesType'                => 'Type',
    'licensesExpires'             => 'Expires',
    'licensesDomain'              => 'Domain',
    'licensesInstalled'           => 'Installed',
    'licensesLastChecked'         => 'Last Checked',
    'licensesActions'             => 'Actions',
    'licensesStatusValid'         => 'Valid',
    'licensesStatusInvalid'       => 'Invalid',
    'licensesStatusExpired'       => 'Expired',
    'licensesStatusSubExpired'    => 'Subscription Expired',
    'licensesStatusUnchecked'     => 'Unchecked',
    'licensesSubscription'        => 'Subscription',
    'licensesOneTime'             => 'One-time',
    'licensesPerpetual'           => 'Perpetual',
    'licensesNotInstalled'        => 'Not installed',
    'licensesNever'               => 'Never',
    'licensesRevalidate'          => 'Revalidate',
    'licenseKeyPlaceholder'       => 'Enter license key...',
    'marketplaceLicensesEmpty'    => 'Licensed products will appear here after installation.',
    'typeTheme'                   => 'Theme',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Plugin',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'License validated successfully.',
    'licenseRevalidateInvalid'     => 'License is invalid or expired.',
    'licenseRevalidateUnreachable' => 'Could not reach the license server. Please try again later.',
    'licenseRevalidateSkipped'     => 'License check was skipped (dev mode).',
    'licenseRevalidateNotFound'    => 'License not found.',

    // License warning banners
    'licenseWarningTitle'   => 'License Issues',
    'licenseWarningInvalid' => 'license is invalid or expired',
    'licenseWarningManage'  => 'Manage Licenses',

    // Plugin license
    'pluginInvalidLicense' => 'This plugin has an invalid or expired license and cannot be activated.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'License Key',
    'storeBrowseFull'       => 'Browse Full Store',
    'storeBackToMarketplace'=> 'Back to Marketplace',
    'storeNoProducts'       => 'No products available.',
    'storeViewInStore'      => 'View in store',

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
    'userBanned'            => 'User has been banned.',
    'userUnbanned'          => 'User has been unbanned.',
    'userCannotBanSelf'     => 'You cannot ban yourself or the site owner.',
    'banStatus'             => 'Ban Status',
    'banned'                => 'Banned',
    'ban'                   => 'Ban User',
    'unban'                 => 'Unban',
    'banReasonRequired'     => 'A ban reason is required.',
    'banReasonPlaceholder'  => 'Reason for ban...',
    'confirmBanUser'        => 'Are you sure you want to ban this user?',
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
    'tfaInvalidCode'        => 'Invalid code - please scan the QR code and try once more.',
    'tfaInvalidDisable'     => 'Invalid code - 2FA was not disabled.',
    'tfaSessionExpired'     => 'Setup session expired - please start again.',
    'tfaNotEnabled'         => '2FA is not currently enabled.',
    'tfaCantScan'           => "Can't scan? Enter this code manually:",
    'tfaWarning'            => 'Store this secret key in a safe place. You will need it to recover access if you lose your authenticator device.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Social Links',
    'socialPlatform'           => 'Platform',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Icon',
    'socialSortOrder'          => 'Sort Order',
    'socialIconPackInfo'       => 'The current theme <strong>{0}</strong> uses <strong>{1}</strong> (v{2}) for icons. Below you can choose the icons available that will display for the Social Links feature of this site.',
    'socialSearchPlaceholder'  => 'Search platforms...',
    'socialIconDisclaimer'     => "These icons are just a representation of the icon that will be used. The actual icon may differ depending on the active theme's icon pack.",

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
    'redirectFromHint'      => '(relative, e.g. /old-page)',
    'redirect301'           => '301 Permanent',
    'redirect302'           => '302 Temporary',
    'redirectInvalidDest'   => 'Invalid redirect destination URL.',

    // Redirect flash messages
    'redirectAdded'         => 'Redirect added.',
    'redirectDeleted'       => 'Redirect deleted.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Settings',
    'settingsGeneral'       => 'General',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'Email',
    'settingsSocialLogin'   => 'Social Login',
    'settingsSocialSharing' => 'Social Sharing',
    'settingsSpam'          => 'Spam Protection',

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
    'generalFrontPagePlugin'    => 'Plugin page:',
    'generalSelectPage'         => '- Select a page -',
    'generalSelectRoute'        => '- Select a route -',
    'generalFrontPageNoPlugins' => 'No plugin routes available',
    'generalPageCacheTtl'       => 'Page Cache TTL',
    'settingsCacheTtlHint'      => 'Seconds. 0 = disabled.',
    'generalSaveBtn'            => 'Save General Settings',

    // General flash messages
    'generalSettingsSaved'      => 'General settings saved.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO Settings',
    'seoMetaDescription'        => 'Meta Description',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Sitemap',
    'seoSitemapEnable'          => 'Enable sitemap.xml',
    'seoSitemapHelp'            => 'Standard sitemap for all published posts and pages.',
    'seoNewsSitemap'            => 'Enable news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Google News sitemap - lists posts published in the last 48 hours.',
    'seoSaveBtn'                => 'Save SEO Settings',
    'seoSettingsSaved'          => 'SEO settings saved.',

    // =========================================================================
    // Settings - Email
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
    // Settings - Social Login (OAuth)
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
    // Settings - Social Sharing
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
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Spam Protection (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana uses hCaptcha (privacy-respecting, non-Google) to protect comment forms and the contact form from spam bots.',
    'spamHcaptchaFree'          => 'hCaptcha is free for most sites. Sign up at hcaptcha.com, create a site, and enter your keys below.',
    'spamHcaptchaSiteKey'       => 'Site Key',
    'spamHcaptchaSecretKey'     => 'Secret Key',
    'spamHcaptchaNote'          => 'If these keys are not set, hCaptcha is silently skipped — safe for local development. Once saved, the widget appears automatically on the comment form and contact page.',
    'spamSettingsSaved'         => 'Spam protection settings saved.',

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
    'languageDirection'         => 'Direction',
    'languageNativeName'        => 'Native Name',

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
    'analyticsDomain'           => 'Domain',

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
    'affiliateClicksTitle'      => 'Clicks - {0}',
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
    'brokenLinkTimeout'         => 'Timeout',
    'brokenLinkBroken'          => 'broken',
    'brokenLinkNone'            => 'No broken links detected.',
    'brokenLinkNowReachable'    => 'Link is now reachable - removed from results.',
    'brokenLinkStillBroken'     => 'Link still broken ({0}).',
    'brokenLinkDismissed'       => 'Link dismissed.',
    'brokenLinksCliHint'        => 'Run a full scan from the command line to populate this report: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} issue(s) found',
    'brokenLinksCount'          => '{0} broken',
    'brokenLinksRecheck'        => 'Re-check this URL',
    'brokenLinksDismiss'        => 'Dismiss (hide from results)',
    'brokenLinksRunScan'        => 'Run Scan',
    'brokenLinksScanComplete'   => 'Scan complete: {0} links checked, {1} broken.',
    'timeout'                   => 'Timeout',
    'typePost'                  => 'Post',
    'typePage'                  => 'Page',

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
    'backupGenerating'          => 'Generating backup…',
    'backupNoFiles'             => 'No saved backups.',
    'backupFailed'              => 'Backup failed: {0}',
    'backupDeleted'             => 'Backup deleted.',
    'backupCannotDelete'        => 'Could not delete backup.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IPs are stored as SHA-256 hashes — no raw PII recorded.',
    'colTime'                   => 'Time',
    'colIpHash'                 => 'IP Hash',
    'colReferrer'               => 'Referrer',
    'affiliateDirectReferrer'   => 'Direct',
    'affiliateNameHint'         => 'Internal label — not shown to visitors.',
    'affiliateSlugHint'         => 'Letters, numbers, hyphens and underscores only. Cannot be changed once links are shared.',
    'affiliateDestHint'         => 'Must include https://. Visitors will be 301-redirected here.',
    'affiliateInactiveHint'     => 'Inactive links return a 404.',
    'affiliateLinkCount'        => '{0} Links',
    'colDomain'                 => 'Domain',
    'commentAll'                => 'All',
    'commentPending'            => 'Pending',
    'commentTrash'              => 'Trash',
    'commentsNone'              => 'No {0} comments.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Create Backup',
    'backupStarting'            => 'Starting backup...',
    'backupNoneYet'             => 'No backups yet. Click "Create Backup" to create your first one.',
    'backupsTitle'              => 'Backups',
    'backupRetentionNote'       => 'Maximum 15 backups retained — oldest are deleted automatically.',
    'backupRestoreConfirm'      => 'Restore this backup? A backup of the current state will be created first.',
    'backupDeleteConfirm'       => 'Delete this backup?',
    'colFilename'               => 'Filename',
    'colVersion'                => 'Version',
    'colTrigger'                => 'Trigger',
    'colSize'                   => 'Size',
    'colDate'                   => 'Date',
    'colActions'                => 'Actions',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Import',
    'importWpHeading'           => 'Import from WordPress',
    'importWpHelp'              => 'Export your WordPress site via Tools → Export, then upload the .xml file below.',
    'importChooseFile'          => 'Choose WXR File (.xml)',
    'importDryRun'              => 'Dry run (preview only - nothing is saved)',
    'importRunBtn'              => 'Run Import',
    'importNoValidFile'         => 'Please upload a valid WordPress WXR export file.',
    'importOnlyXml'             => 'Only .xml files are accepted.',
    'importFileTooLarge'        => 'Import file too large. Maximum size is 50 MB.',
    'importResultsHeading'      => 'Import Results',
    'importDryRunNote'          => 'Dry run - no data was saved.',
    'importDryRunLabel'         => '(Dry Run — no data written)',
    'importComplete'            => 'Import Complete',
    'importCreated'             => 'created',
    'importSkipped'             => 'skipped',
    'importErrors'              => 'Errors:',
    'importInstructions'        => 'Export your WordPress content from <strong>Tools → Export → All content</strong> and upload the <code>.xml</code> file here. Pubvana will import posts, pages, categories, tags, authors, and comments.',
    'importCliTitle'            => 'CLI Import',
    'importCliHint'             => 'You can also run the importer from the command line:',
    'importCliDryRunHint'       => 'The <code>--dry-run</code> flag shows what would be imported without writing to the database.',
    'importWhatTitle'           => 'What Gets Imported',
    'importItemPosts'           => 'Posts (title, content, excerpt, slug, status)',
    'importItemPages'           => 'Pages',
    'importItemCategories'      => 'Categories (with hierarchy)',
    'importItemTags'            => 'Tags',
    'importItemAuthors'         => 'Authors (created as subscriber accounts)',
    'importItemComments'        => 'Comments',
    'importItemMedia'           => 'Media files (URLs preserved in content)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Updates',
    'updatesCurrentVersion'     => 'Current Version',
    'updatesLatestVersion'      => 'Latest Version',
    'updatesUpToDate'           => 'Pubvana is up to date.',
    'updatesAvailable'          => 'Update available: {0}',
    'updatesCheckBtn'           => 'Check for Updates',
    'updatesReleaseNotes'       => 'Release Notes',
    'updatesHowToApply'         => 'How to Apply an Update',
    'updatesCacheCleared'       => 'Update cache cleared - re-checking now.',
    'updatesExtCapped'          => 'Update available: {0} (addon-safe)',
    'updatesNewerAvailable'     => 'Pubvana {0} is also available - update the addons listed below to unlock it.',

    // Addon Updates
    'updatesExtTitle'               => 'Addons',
    'updatesExtCheckAll'            => 'Check All',
    'updatesExtUpdateAll'           => 'Update All',
    'updatesExtCheckAllType'        => 'Check All {0}',
    'updatesExtUpdateAllType'       => 'Update All {0}',
    'updatesExtNoInstalled'         => 'No {0} installed.',
    'updatesExtColName'             => 'Name',
    'updatesExtColVersion'          => 'Version',
    'updatesExtColLatest'           => 'Latest',
    'updatesExtColAutoUpdate'       => 'Auto-Update',
    'updatesExtColStatus'           => 'Status',
    'updatesExtColActions'          => 'Actions',
    'updatesExtBundled'             => 'Core Bundled',
    'updatesExtNoSource'            => 'No update source',
    'updatesExtFailed'              => 'Failed',
    'updatesExtUpdatedAt'           => 'Updated {0}',
    'updatesExtAvailable'           => 'Update available',
    'updatesExtUpToDate'            => 'Up to date',
    'updatesExtUpdate'              => 'Update',
    'updatesExtChecking'            => 'Checking...',
    'updatesExtUpdating'            => 'Updating...',
    'updatesExtUpdated'             => 'Updated',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Confirm Update',
    'updatesConfirmBody'            => 'This will backup your site, download the update, and apply it.',
    'updatesConfirmSafe'            => 'Your <code>.env</code>, <code>App.php</code>, and <code>Database.php</code> are never overwritten.',
    'updatesConfirmBtn'             => 'Update Now',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Update All Addons',
    'updatesExtAllBody'             => 'This will update all addons that have pending updates.',
    'updatesExtAllNote'             => 'Addons with auto-update disabled will also be updated.',
    'updatesExtAllBtn'              => 'Update All',

    'updatesExtBadge'               => 'Update: v{0}',
    'updatesExtGoToUpdates'         => 'Updates',

    // Update Settings
    'updatesSettingsTitle'          => 'Update Settings',
    'updatesAutoUpdateLabel'        => 'Pubvana Auto-Update',
    'updatesAutoUpdateManual'       => 'Manual',
    'updatesAutoUpdateAuto'         => 'Automatic',
    'updatesAutoUpdateHelp'         => 'When enabled, Pubvana updates without breaking changes are applied automatically.',
    'updatesCheckMethodLabel'       => 'Update Check Method',
    'updatesCheckMethodPageload'    => 'Page Load',
    'updatesCheckMethodCron'        => 'Cron Job',
    'updatesCheckMethodHelp'        => 'Page Load checks on every request (cached 24h). Cron requires a server cron job.',
    'updatesCronCommand'            => 'Cron Command',
    'updatesCronHelp'               => 'Add this to your server\'s crontab to run the update check daily:',
    'updatesSettingsSaved'          => 'Update settings saved.',

    // Compatibility
    'compatWarningTitle'            => 'Compatibility Warning',
    'compatNotCompatible'           => 'Some installed addons are not compatible with this version.',
    'compatRequiresUpdate'          => 'but requires the following addons to be updated first:',
    'compatSupportsUpTo'            => 'supports up to {0}',
    'compatRequiresMin'             => 'requires Pubvana {0}+',
    'compatNotDeclared'             => 'The following addons have not declared compatibility with Pubvana {0}. They may stop working after the update:',
    'compatColType'                 => 'Type',
    'compatColName'                 => 'Name',
    'compatColVersion'              => 'Compatibility',
    'compatRemoveHint'              => 'You can remove incompatible addons or switch to the default theme if issues occur. A backup is created before every update.',
    'compatMaxVersion'              => 'Max compatible version: {0}',
    'compatMinVersion'              => 'Requires Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Post Schedule',
    'scheduleNoScheduled'       => 'No scheduled posts.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revisions - {0}',
    'revisionPageTitle'         => 'Revision - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'You must be logged in to access the admin panel.',
    'dirNotWritable'            => 'Directory is not writable: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} is improperly configured. If you\'re the end-user, contact the developer. If you are the developer, consult the documentation.',
    'addonMisconfiguredLink'    => '{0} is improperly configured. If you\'re the end-user <a href="{1}">contact the developer</a>. If you are the developer <a href="https://github.com/enlivenapp/pubvana">consult the documentation</a>.',
    'licenseExpiringSoon'       => 'License for {0} expires on {1}. {0} will be deactivated when the license expires.',
    'licenseExpiredDeactivated' => '{0} has been deactivated because the license has expired.',
    'addonDeactivated'          => '{0} has been deactivated. Reason: {1}.',
    'widgetValidationFailed'    => "Widget ''{0}'' could not be validated. Contact the developer or remove the addon.",
    'widgetValidationFailedLink' => "Widget ''{0}'' could not be validated. <a href=\"{1}\">Contact the developer</a> or remove the addon.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Deactivated: license expired',
    'addonDeactivatedTampered'  => 'Deactivated: improperly configured',
    'addonDeactivatedNoLicense' => 'Deactivated: no valid license',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Disabled',
    'addonDisabledInvalidJson'  => 'System: {0} has an invalid or unreadable {1}.',
    'addonDisabledMissingFields' => 'System: {0} is missing required fields: {1}.',
    'addonDisabledPhpFiles'     => 'System: {0} contains PHP files. Widgets must be JSON + templates only.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'A valid license is required to activate {0}.',
    'licenseInvalidActivation'  => 'License validation failed for {0}. Please check your license key.',
    'licenseExpiredActivation'  => 'The license for {0} has expired. Please renew to activate.',
    'licenseCheckUnreachable'   => 'Could not verify the license for {0}. The license server is unreachable. Please try again later.',
    'activationBlockedTampered' => '{0} cannot be activated because it is improperly configured.',
    'activationBlockedBundled'  => '{0} cannot be activated: only Pubvana addons can be marked as bundled.',
    'activationBlockedNoUrls'   => '{0} cannot be activated: paid addons must include license verification URLs.',
    'activationBlockedFreeFlag' => '{0} cannot be activated: Pubvana addons cannot be marked as free.',
    'activationBlockedDisabled' => '{0} cannot be activated because it has configuration errors. Check the info file.',

    // Third-party license
    'licenseThirdPartyLabel'    => '3rd Party',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Starting update...',
    'updateCheckLabel'           => 'Update check:',
    'updateAvailable'            => 'Pubvana {0} is available!',
    'updateRunning'              => 'You are running {0}.',
    'updateBreakingChanges'      => 'Breaking Changes',
    'updateMigrationNotes'       => 'Migration Notes',
    'updateNotices'              => 'Notices',
    'updatePreflightTitle'       => 'Pre-flight Checks',
    'updateToVersion'            => 'Update to Pubvana {0}',
    'updatePreflightFailed'      => 'One or more required pre-flight checks failed. Please resolve them before updating.',
    'updateUpToDate'             => 'Pubvana is up to date. You are running version {0}.',
    'updateAnyway'               => 'Update Anyway',
    'updateAvailableTooltip'     => 'Pubvana {0} available',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(you)',
    'usersNone'                  => 'No users found.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Account active',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Profile Details',
    'profileDisplayNameHint'     => 'Shown on published posts instead of username.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP or GIF. Max 10 MB.',
    'profileSocialHandles'       => 'Social Handles',
    'preview'                    => 'Preview',
    'website'                    => 'Website',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Two-Factor Authentication',
    'totpActiveDesc'             => 'TOTP two-factor authentication is active on your account. You will be asked for a 6-digit code from your authenticator app each time you log in.',
    'totpCurrentCode'            => 'Current Code',
    'totpInactiveDesc'           => 'Add an extra layer of security to your account. Once enabled, you will need to enter a code from your authenticator app on each login.',
    'totpEnable'                 => 'Enable Two-Factor Authentication',
    'totpScanInstructions'       => 'Open your authenticator app (Google Authenticator, Authy, 1Password, etc.) and scan this QR code.',
    'totpManualEntry'            => "Can't scan? Enter this code manually:",
    'totpConfirmInstructions'    => 'After scanning, enter the 6-digit code shown in your app to confirm setup.',
    'totpRecoveryWarning'        => 'Store your recovery codes. If you lose access to your authenticator app, you will not be able to log in. Contact your site administrator to reset 2FA.',

];
