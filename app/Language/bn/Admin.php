<?php

/**
 * Pubvana CMS - Admin language strings (Bengali)
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
    'save'              => 'সংরক্ষণ',
    'saveChanges'       => 'পরিবর্তন সংরক্ষণ',
    'cancel'            => 'বাতিল',
    'edit'              => 'সম্পাদনা',
    'delete'            => 'মুছুন',
    'create'            => 'তৈরি করুন',
    'add'               => 'যোগ করুন',
    'back'              => 'ফিরে যান',
    'view'              => 'দেখুন',
    'apply'             => 'প্রয়োগ করুন',
    'install'           => 'ইনস্টল করুন',
    'update'            => 'আপডেট',
    'refresh'           => 'রিফ্রেশ',
    'activate'          => 'সক্রিয় করুন',
    'deactivate'        => 'নিষ্ক্রিয় করুন',
    'enable'            => 'সক্ষম করুন',
    'disable'           => 'অক্ষম করুন',
    'disabled'          => 'অক্ষম',
    'approve'           => 'অনুমোদন করুন',
    'spam'              => 'স্প্যাম',
    'trash'             => 'ট্র্যাশ',
    'restore'           => 'পুনরুদ্ধার করুন',
    'dismiss'           => 'বাতিল করুন',
    'recheck'           => 'পুনরায় পরীক্ষা করুন',
    'clickToCopy'       => 'কপি করতে ক্লিক করুন',
    'download'          => 'ডাউনলোড',
    'upload'            => 'আপলোড',
    'import'            => 'আমদানি',
    'export'            => 'রপ্তানি',
    'publish'           => 'প্রকাশ করুন',
    'unpublish'         => 'অপ্রকাশিত করুন',
    'logout'            => 'লগআউট',
    'viewSite'          => 'সাইট দেখুন',
    'newPost'           => 'নতুন পোস্ট',
    'buyNow'            => 'এখনই কিনুন',
    'visitStore'        => 'স্টোর দেখুন',
    'loadMore'          => 'আরও লোড করুন',

    // Table headers / labels
    'title'             => 'শিরোনাম',
    'name'              => 'নাম',
    'slug'              => 'স্লাগ',
    'status'            => 'অবস্থা',
    'date'              => 'তারিখ',
    'actions'           => 'ক্রিয়া',
    'author'            => 'লেখক',
    'views'             => 'দর্শন',
    'type'              => 'ধরন',
    'url'               => 'URL',
    'description'       => 'বিবরণ',
    'role'              => 'ভূমিকা',
    'email'             => 'ইমেইল',
    'username'          => 'ব্যবহারকারীর নাম',
    'active'            => 'সক্রিয়',
    'version'           => 'সংস্করণ',
    'size'              => 'আকার',
    'clicks'            => 'ক্লিক',
    'total'             => 'মোট',
    'platform'          => 'প্ল্যাটফর্ম',
    'label'             => 'লেবেল',
    'order'             => 'ক্রম',
    'source'            => 'উৎস',
    'content'           => 'বিষয়বস্তু',
    'excerpt'           => 'সংক্ষেপ',
    'details'           => 'বিস্তারিত',
    'contentType'       => 'বিষয়বস্তুর ধরন',
    'seo'               => 'SEO',
    'metaTitle'         => 'মেটা শিরোনাম',
    'metaDescription'   => 'মেটা বিবরণ',

    // Status badges
    'published'         => 'প্রকাশিত',
    'draft'             => 'খসড়া',
    'scheduled'         => 'নির্ধারিত',
    'pending'           => 'অপেক্ষমান',
    'safe'              => 'নিরাপদ',
    'notSafe'           => 'অনিরাপদ',
    'malicious'         => 'ক্ষতিকারক',
    'safetyUnknown'     => 'অজানা',
    'inactive'          => 'নিষ্ক্রিয়',
    'installed'         => 'ইনস্টল করা',
    'free'              => 'বিনামূল্যে',
    'premium'           => 'প্রিমিয়াম',
    'all'               => 'সব',

    // Confirmations
    'confirmDelete'         => 'আপনি কি এই আইটেমটি মুছতে চান?',
    'confirmDeletePost'     => 'এই পোস্টটি মুছবেন?',
    'confirmDeletePage'     => 'এই পৃষ্ঠাটি মুছবেন?',
    'confirmDeleteComment'  => 'এই মন্তব্যটি স্থায়ীভাবে মুছবেন?',
    'confirmDeleteUser'     => 'এই ব্যবহারকারীকে মুছবেন?',
    'confirmDeleteMedia'    => 'মুছবেন?',
    'confirmDeleteBackup'   => 'এই ব্যাকআপ ফাইলটি মুছবেন?',
    'confirmBulkAction'     => 'নির্বাচিত পোস্টে বাল্ক অ্যাকশন প্রয়োগ করবেন?',

    // Empty states
    'noPostsYet'        => 'এখনও কোনো পোস্ট নেই। {0}',
    'noResultsFound'    => 'কোনো ফলাফল পাওয়া যায়নি।',
    'noCommentsYet'     => 'কোনো অপেক্ষমান মন্তব্য নেই।',
    'noMediaYet'        => 'এখনও কোনো মিডিয়া নেই।',
    'noItemsFound'      => 'মার্কেটপ্লেসে কোনো আইটেম পাওয়া যায়নি।',
    'noCategoriesYet'   => 'এখনও কোনো বিভাগ নেই।',
    'noTagsYet'         => 'এখনও কোনো ট্যাগ নেই।',
    'noRevisionsYet'    => 'কোনো সংশোধন পাওয়া যায়নি।',

    // Misc common
    'permissionDenied'  => 'অনুমতি প্রত্যাখ্যাত।',
    'notFound'          => 'রেকর্ড পাওয়া যায়নি।',
    'commasSeparated'   => 'কমা দ্বারা পৃথক',
    'optional'          => 'ঐচ্ছিক',
    'required'          => 'প্রয়োজনীয়',
    'enabled'           => 'সক্ষম',
    'selected'          => '{0}টি পোস্ট নির্বাচিত',
    'published_count'   => '{0}টি প্রকাশিত',
    'pending_count'     => '{0}টি অপেক্ষমান',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'ড্যাশবোর্ড',
    'navContent'        => 'বিষয়বস্তু',
    'navAppearance'     => 'চেহারা',
    'navUsersAndSite'   => 'ব্যবহারকারী ও সাইট',
    'navTools'          => 'সরঞ্জাম',
    'navMarketplace'    => 'মার্কেটপ্লেস',
    'navPlugins'        => 'প্লাগইন',
    'navPosts'          => 'পোস্ট',
    'navSchedule'       => 'সময়সূচি',
    'navPages'          => 'পৃষ্ঠা',
    'navCategories'     => 'বিভাগ',
    'navTags'           => 'ট্যাগ',
    'navComments'       => 'মন্তব্য',
    'navMedia'          => 'মিডিয়া',
    'navImport'         => 'আমদানি',
    'navThemes'         => 'থিম',
    'navWidgets'        => 'উইজেট',
    'navNavigation'     => 'নেভিগেশন',
    'navUsers'          => 'ব্যবহারকারী',
    'navSocialLinks'    => 'সামাজিক লিঙ্ক',
    'navRedirects'      => 'পুনঃনির্দেশ',
    'navLanguages'      => 'ভাষা',
    'navSettings'       => 'সেটিংস',
    'navAnalytics'      => 'বিশ্লেষণ',
    'navAffiliates'     => 'অ্যাফিলিয়েট লিঙ্ক',
    'navBrokenLinks'    => 'ভাঙা লিঙ্ক',
    'navActivityLog'    => 'কার্যকলাপ লগ',
    'navBackup'         => 'ব্যাকআপ ও রপ্তানি',
    'navUpdates'        => 'আপডেট',
    'navBrowse'         => 'ব্রাউজ',
    'navLicenses'       => 'লাইসেন্স',
    'navPubvanaStore'   => 'Pubvana স্টোর',
    'navUpdateAvailable'=> 'আপডেট উপলব্ধ',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'চলে যেতে চান?',
    'logoutModalBody'   => 'আপনার সেশন শেষ করতে নিচে "লগআউট" নির্বাচন করুন।',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'ড্যাশবোর্ড',
    'dashStats'             => 'পরিসংখ্যান',
    'dashPosts'             => 'পোস্ট',
    'dashPages'             => 'পৃষ্ঠা',
    'dashComments'          => 'মন্তব্য',
    'dashUsers'             => 'ব্যবহারকারী',
    'dashRecentPosts'       => 'সাম্প্রতিক পোস্ট',
    'dashPendingComments'   => 'অপেক্ষমান মন্তব্য',
    'dashViewAll'           => 'সব দেখুন',
    'dashCreateOne'         => 'একটি তৈরি করুন!',
    'dashNoPosts'           => 'এখনও কোনো পোস্ট নেই।',
    'dashNoPendingComments' => 'কোনো অপেক্ষমান মন্তব্য নেই।',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'পোস্ট',
    'newPostTitle'          => 'নতুন পোস্ট',
    'editPostTitle'         => 'পোস্ট সম্পাদনা: {0}',
    'copyPreviewLink'       => 'প্রিভিউ লিঙ্ক কপি করুন',
    'backToPosts'           => 'পোস্টে ফিরে যান',
    'postTitleField'        => 'শিরোনাম *',
    'postEditor'            => 'সম্পাদক',
    'postHtmlEditor'        => 'HTML সম্পাদক',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'সংক্ষেপ',
    'postExcerptPlaceholder'=> 'ঐচ্ছিক সংক্ষিপ্ত সারসংক্ষেপ...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'মেটা শিরোনাম',
    'postMetaDescription'   => 'মেটা বিবরণ',
    'postPublishSection'    => 'প্রকাশ',
    'postStatus'            => 'অবস্থা',
    'postStatusDraft'       => 'খসড়া',
    'postStatusPublished'   => 'প্রকাশিত',
    'postStatusScheduled'   => 'নির্ধারিত',
    'postScheduledAt'       => 'নির্ধারিত তারিখ ও সময়',
    'postFeatured'          => 'ফিচার্ড পোস্ট',
    'postMembersOnly'       => 'শুধুমাত্র সদস্যদের জন্য',
    'postShareOnPublish'    => 'প্রকাশে সামাজিকে শেয়ার করুন',
    'postSaveBtn'           => 'পোস্ট সংরক্ষণ',
    'postFeaturedImage'     => 'ফিচার্ড ছবি',
    'postFeaturedImagePlaceholder' => 'URL বা আপলোড পাথ…',
    'postCategories'        => 'বিভাগ',
    'postTags'              => 'ট্যাগ',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'সংশোধন',
    'postRevisionCount'     => '{0}টি সংশোধন',
    'postPreview'           => 'প্রিভিউ',
    'postBulkAction'        => '- ক্রিয়া নির্বাচন করুন -',
    'postBulkPublish'       => 'প্রকাশ করুন',
    'postBulkUnpublish'     => 'অপ্রকাশিত করুন (খসড়ায় সেট করুন)',
    'postBulkDelete'        => 'মুছুন',

    // Post flash messages
    'postCreated'           => 'পোস্ট সফলভাবে তৈরি হয়েছে।',
    'postUpdated'           => 'পোস্ট আপডেট হয়েছে।',
    'scheduledDateMustBeFuture' => 'নির্ধারিত তারিখ ভবিষ্যতে হতে হবে।',
    'postDeleted'           => 'পোস্ট মুছে ফেলা হয়েছে।',
    'postBulkUpdated'       => '{0}টি পোস্ট আপডেট হয়েছে।',
    'postBulkInvalid'       => 'অবৈধ বাল্ক অ্যাকশন।',
    'postPermission'        => 'আপনি শুধুমাত্র আপনার নিজের পোস্ট সম্পাদনা করতে পারেন।',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'সংশোধন: {0}',
    'revisionTitle'         => 'সংশোধন — {0}',
    'revisionShowTitle'     => 'সংশোধন',
    'revisionsBackToPost'   => 'পোস্টে ফিরে যান',
    'revisionsBackToList'   => 'সংশোধন তালিকায় ফিরে যান',
    'revisionRestored'      => 'পোস্ট {0} এর সংশোধনে পুনরুদ্ধার করা হয়েছে।',
    'revisionRestoreBtn'    => 'এই সংশোধন পুনরুদ্ধার করুন',
    'revisionSaved'         => 'সংরক্ষিত',
    'revisionBy'            => 'লিখেছেন',
    'revisionOn'            => 'তারিখে',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'পৃষ্ঠা',
    'newPageTitle'          => 'নতুন পৃষ্ঠা',
    'editPageTitle'         => 'পৃষ্ঠা সম্পাদনা',
    'pageSlugInUse'         => "স্লাগ '{0}' ইতিমধ্যে ব্যবহারে আছে।",
    'pageCannotDelete'      => 'এই পৃষ্ঠাটি মুছা যাবে না।',
    'slugAutoGenHint'       => 'ফাঁকা রাখলে শিরোনাম থেকে স্বয়ংক্রিয়ভাবে তৈরি হবে',
    'slugCannotChange'      => 'পরিবর্তন করা যাবে না',
    'colSystem'             => 'সিস্টেম',
    'system'                => 'সিস্টেম',

    // Page flash messages
    'pageCreated'           => 'পৃষ্ঠা তৈরি হয়েছে।',
    'pageUpdated'           => 'পৃষ্ঠা আপডেট হয়েছে।',
    'pageDeleted'           => 'পৃষ্ঠা মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'বিভাগ',
    'newCategoryTitle'      => 'নতুন বিভাগ',
    'editCategoryTitle'     => 'বিভাগ সম্পাদনা',
    'categoryName'          => 'নাম',
    'categoryDescription'   => 'বিবরণ',
    'categoryPostCount'     => 'পোস্ট সংখ্যা',

    // Category flash messages
    'categoryCreated'       => 'বিভাগ তৈরি হয়েছে।',
    'categoryUpdated'       => 'বিভাগ আপডেট হয়েছে।',
    'categoryDeleted'       => 'বিভাগ মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'ট্যাগ',
    'tagPostCount'          => 'পোস্ট সংখ্যা',

    // Tag flash messages
    'tagDeleted'            => 'ট্যাগ মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'মন্তব্য',
    'commentAuthor'         => 'লেখক',
    'commentContent'        => 'মন্তব্য',
    'commentPost'           => 'পোস্ট',
    'commentDate'           => 'তারিখ',
    'commentStatusFilter'   => 'অবস্থা অনুযায়ী ফিল্টার করুন',

    // Comment flash messages
    'commentApproved'       => 'মন্তব্য অনুমোদিত।',
    'commentSpam'           => 'স্প্যাম হিসেবে চিহ্নিত।',
    'commentTrashed'        => 'মন্তব্য ট্র্যাশ করা হয়েছে।',
    'commentDeleted'        => 'মন্তব্য স্থায়ীভাবে মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'মিডিয়া লাইব্রেরি',
    'mediaTitle'            => 'শিরোনাম',
    'mediaAltText'          => 'Alt টেক্সট',
    'mediaAltPlaceholder'   => 'অ্যাক্সেসিবিলিটির জন্য ছবি বর্ণনা করুন',
    'mediaTitlePlaceholder' => 'ঐচ্ছিক ছবির শিরোনাম',
    'mediaImageDetails'     => 'ছবির বিস্তারিত',
    'mediaSaved'            => 'সংরক্ষিত!',
    'mediaNoSelection'      => 'কোনো ছবি নির্বাচিত নয়',
    'mediaBrowse'           => 'মিডিয়া ব্রাউজ করুন',
    'mediaRemove'           => 'সরান',
    'mediaUseImage'         => 'এই ছবি ব্যবহার করুন',
    'mediaDropzone'         => 'এখানে ছবি টেনে ছাড়ুন বা ব্রাউজ করতে ক্লিক করুন',
    'mediaLoading'          => 'মিডিয়া লোড হচ্ছে…',
    'mediaEmpty'            => 'এখনও কোনো মিডিয়া আপলোড করা হয়নি।',
    'mediaUpload'           => 'মিডিয়া আপলোড',
    'mediaDragDrop'         => 'এখানে ফাইল টেনে ছাড়ুন, অথবা',
    'mediaChooseFiles'      => 'ফাইল বেছে নিন',
    'mediaUploading'        => 'আপলোড হচ্ছে…',
    'mediaFilename'         => 'ফাইলের নাম',
    'mediaSize'             => 'আকার',
    'mediaUploadFailed'     => 'আপলোড ব্যর্থ: {0}',
    'mediaUploadError'      => 'আপলোড ত্রুটি: {0}',

    // Media flash messages
    'mediaDeleted'          => 'মিডিয়া মুছে ফেলা হয়েছে।',
    'mediaNoValidFile'      => 'কোনো বৈধ ফাইল আপলোড হয়নি।',
    'mediaUploadSuccess'    => 'ফাইল সফলভাবে আপলোড হয়েছে।',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'নেভিগেশন',
    'navQuickAdd'           => 'দ্রুত যোগ করুন',
    'navQuickAddPlaceholder' => 'পৃষ্ঠা, বিভাগ, প্লাগইন খুঁজুন...',
    'navItemLabel'          => 'লেবেল',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'লক্ষ্য',
    'navItemOrder'          => 'বাছাই ক্রম',
    'navGroupPrimary'       => 'প্রধান',
    'navGroupFooter'        => 'ফুটার',
    'navSelectGroup'        => 'নেভিগেশন গ্রুপ নির্বাচন করুন:',
    'navParent'             => 'মূল',
    'navTopLevel'           => '— শীর্ষ স্তর —',
    'navSameWindow'         => 'একই উইন্ডোতে',
    'navNewWindow'          => 'নতুন উইন্ডোতে',
    'navMenuItems'          => 'মেনু আইটেম',
    'navNoItems'            => 'এই মেনুতে কোনো আইটেম নেই।',
    'dragToReorder'         => 'পুনর্বিন্যাস করতে টেনে নিন',

    // Navigation flash messages
    'navItemAdded'          => 'নেভ আইটেম যোগ হয়েছে।',
    'navItemRemoved'        => 'নেভ আইটেম সরানো হয়েছে।',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'থিম',
    'themeOptions'          => 'থিম বিকল্প',
    'themeActivate'         => 'সক্রিয় করুন',
    'themeOptionsBtn'       => 'বিকল্প',
    'themeActive'           => 'সক্রিয়',
    'themeBy'               => 'লিখেছেন',
    'themeSupport'          => 'সহায়তা',
    'themeVersion'          => 'সংস্করণ',
    'themeSaveOptions'      => 'বিকল্প সংরক্ষণ',
    'themeInvalidLicense'   => 'থিম সক্রিয় করা যাচ্ছে না - লাইসেন্স অবৈধ। পুনরায় ইনস্টল করুন বা সহায়তার সাথে যোগাযোগ করুন।',
    'themeValidationFailed' => 'থিমে PHP কোড রয়েছে এবং সক্রিয় করা যাবে না।',
    'noThemesInstalled'     => 'কোনো থিম ইনস্টল নেই। থিম পেতে মার্কেটপ্লেস পরিদর্শন করুন।',
    'themeUnapprovedTitle'  => 'অননুমোদিত থিম সক্রিয় করবেন?',
    'themeNotApproved'      => 'এই থিমটি Pubvana দ্বারা অনুমোদিত হয়নি।',
    'themeUnapprovedRisk'   => 'অননুমোদিত থিম সক্রিয় করলে নিরাপত্তা ঝুঁকি বা সামঞ্জস্য সমস্যা হতে পারে।',
    'themeActivateConfirm'  => 'আপনি কি তবুও এটি সক্রিয় করতে চান?',
    'themeActivateAnyway'   => 'যাইহোক সক্রিয় করুন',
    'themeNoOptions'        => 'এই থিমের কোনো কনফিগারযোগ্য বিকল্প নেই।',
    'themeCustomize'        => 'থিম কাস্টমাইজ করুন',

    // Theme flash messages
    'themeActivated'        => 'থিম সক্রিয় হয়েছে।',
    'themeOptionsSaved'     => 'বিকল্প সংরক্ষিত।',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'লাইসেন্সপ্রাপ্ত',
    'licenseCheckNow'        => 'এখন পরীক্ষা করুন',
    'licenseExpired'         => 'মেয়াদ শেষ',
    'licenseEnterKey'        => 'কী দিন',
    'licenseChangeKey'       => 'পরিবর্তন',
    'licenseRenew'           => 'নবায়ন',
    'licenseThirdParty'      => 'তৃতীয় পক্ষ',
    'unchecked'              => 'অযাচাইকৃত',
    'safetyLabel'            => 'নিরাপত্তা:',
    'recheckBtn'             => 'পুনরায় পরীক্ষা',
    'recheckSuccess'         => 'নিরাপত্তা পরীক্ষা আপডেট হয়েছে।',
    'recheckFailed'          => 'যাচাই সার্ভারে সংযোগ করা যায়নি। পরে আবার চেষ্টা করুন।',
    'recheckNotFound'        => 'আইটেম পাওয়া যায়নি।',
    'widgetBlockedMalicious' => '{0} ক্ষতিকারক হিসেবে চিহ্নিত করা হয়েছে এবং যোগ করা যাবে না।',
    'licenseNoStoreProduct'  => 'এই আইটেমটি কোনো স্টোর পণ্যের সাথে সংযুক্ত নয়। আপনি যদি এই আইটেমটি কিনে থাকেন, তাহলে লাইসেন্সিং সক্রিয় করতে মার্কেটপ্লেস থেকে পুনরায় ইনস্টল করুন।',
    'securityWarning'        => 'নিরাপত্তা সতর্কতা:',
    'licenseModalTitle'      => 'লাইসেন্স কী দিন',
    'licenseModalBody'       => 'নিচে আপনার লাইসেন্স কী পেস্ট করুন।',
    'licenseModalSave'       => 'সংরক্ষণ',
    'licenseSaved'           => 'লাইসেন্স কী সংরক্ষিত ও যাচাই হয়েছে।',
    'licenseInvalid'         => 'লাইসেন্স কী বৈধ নয়।',
    'licenseKeyRequired'     => 'লাইসেন্স কী এবং পণ্য প্রয়োজন।',
    'licenseCheckFailed'     => 'লাইসেন্স সার্ভারে পৌঁছানো যায়নি। পরে আবার চেষ্টা করুন।',
    'licenseProductNotFound' => 'স্টোরে এই আইটেম খুঁজে পাওয়া যায়নি।',
    'btnCancel'              => 'বাতিল',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'উইজেট',
    'widgetConfigureTitle'  => 'উইজেট কনফিগার করুন',
    'widgetAreas'           => 'উইজেট এলাকা',
    'widgetAvailable'       => 'উপলব্ধ উইজেট',
    'widgetAddToArea'       => 'এলাকায় যোগ করুন',
    'widgetArea'            => 'এলাকা',
    'widgetNoOptions'       => 'কোনো বিকল্প নেই।',
    'widgetSaveConfig'      => 'কনফিগারেশন সংরক্ষণ',
    'widgetConfigure'       => 'কনফিগার করুন',
    'widgetNoAreas'         => 'কোনো উইজেট এলাকা পাওয়া যায়নি। উইজেট এলাকা সক্ষম করতে থিম সক্রিয় করুন।',
    'widgetAreaEmpty'       => 'এই এলাকায় কোনো উইজেট নেই। তালিকা থেকে একটি যোগ করুন →',

    // Widget flash messages
    'widgetAdded'           => 'উইজেট যোগ হয়েছে।',
    'widgetRemoved'         => 'উইজেট সরানো হয়েছে।',
    'widgetConfigured'      => 'উইজেট কনফিগার হয়েছে।',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'মার্কেটপ্লেস',
    'marketplaceRefresh'    => 'রিফ্রেশ',
    'marketplaceVisitStore' => 'স্টোর দেখুন',
    'marketplaceAll'        => 'সব',
    'marketplaceThemes'     => 'থিম',
    'marketplaceWidgets'    => 'উইজেট',
    'marketplacePlugins'    => 'প্লাগইন',
    'marketplaceUpdatesAvailable' => '{0}টি আপডেট উপলব্ধ।',
    'marketplaceBy'         => 'লিখেছেন',
    'marketplaceFree'       => 'বিনামূল্যে',
    'marketplaceInstalled'  => 'ইনস্টল করা',
    'marketplaceInstall'    => 'ইনস্টল',
    'marketplaceBuyNow'     => 'এখনই কিনুন',
    'marketplaceNoItems'    => 'মার্কেটপ্লেসে কোনো আইটেম পাওয়া যায়নি।',
    'marketplaceInstalledVersion' => 'v{0} ইনস্টল করা',
    'marketplaceLoadError'  => 'স্টোর থেকে পণ্য লোড করা যায়নি। পরে আবার চেষ্টা করুন।',
    'byAuthor'              => '{0} লিখেছেন',
    'unknown'               => 'অজানা',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} সফলভাবে ইনস্টল হয়েছে।',
    'marketplaceInstallFail'    => 'ইনস্টলেশন ব্যর্থ। লগ পরীক্ষা করুন।',
    'marketplaceUpdateSuccess'  => 'সফলভাবে আপডেট হয়েছে।',
    'marketplaceUpdateFail'     => 'আপডেট ব্যর্থ।',
    'marketplaceCacheRefreshed' => 'মার্কেটপ্লেস ক্যাশ রিফ্রেশ হয়েছে।',
    'marketplaceInvalidRequest' => 'অবৈধ ইনস্টল অনুরোধ।',
    'marketplaceCannotUpdate'   => 'এই আইটেম আপডেট করা যাচ্ছে না।',

    // =========================================================================
    // Licenses
    // =========================================================================

    'licensesTitle'               => 'লাইসেন্স',
    'licensesNone'                => 'কোনো লাইসেন্স নেই',
    'licensesProduct'             => 'পণ্য',
    'licensesKey'                 => 'লাইসেন্স কী',
    'licensesStatus'              => 'অবস্থা',
    'licensesType'                => 'ধরন',
    'licensesExpires'             => 'মেয়াদ শেষ',
    'licensesDomain'              => 'ডোমেইন',
    'licensesInstalled'           => 'ইনস্টল করা',
    'licensesLastChecked'         => 'সর্বশেষ পরীক্ষিত',
    'licensesActions'             => 'ক্রিয়া',
    'licensesStatusValid'         => 'বৈধ',
    'licensesStatusInvalid'       => 'অবৈধ',
    'licensesStatusExpired'       => 'মেয়াদ শেষ',
    'licensesStatusSubExpired'    => 'সদস্যতা মেয়াদ শেষ',
    'licensesStatusUnchecked'     => 'অযাচাইকৃত',
    'licensesSubscription'        => 'সদস্যতা',
    'licensesOneTime'             => 'এককালীন',
    'licensesPerpetual'           => 'স্থায়ী',
    'licensesNotInstalled'        => 'ইনস্টল করা নেই',
    'licensesNever'               => 'কখনো না',
    'licensesRevalidate'          => 'পুনরায় যাচাই করুন',
    'licenseKeyPlaceholder'       => 'লাইসেন্স কী দিন...',
    'marketplaceLicensesEmpty'    => 'ইনস্টলেশনের পরে লাইসেন্সপ্রাপ্ত পণ্য এখানে দেখাবে।',
    'typeTheme'                   => 'থিম',
    'typeWidget'                  => 'উইজেট',
    'typePlugin'                  => 'প্লাগইন',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'লাইসেন্স সফলভাবে যাচাই হয়েছে।',
    'licenseRevalidateInvalid'     => 'লাইসেন্স অবৈধ বা মেয়াদোত্তীর্ণ।',
    'licenseRevalidateUnreachable' => 'লাইসেন্স সার্ভারে পৌঁছানো যায়নি। পরে আবার চেষ্টা করুন।',
    'licenseRevalidateSkipped'     => 'লাইসেন্স পরীক্ষা এড়িয়ে যাওয়া হয়েছে (dev মোড)।',
    'licenseRevalidateNotFound'    => 'লাইসেন্স পাওয়া যায়নি।',

    // License warning banners
    'licenseWarningTitle'   => 'লাইসেন্স সমস্যা',
    'licenseWarningInvalid' => 'লাইসেন্স অবৈধ বা মেয়াদোত্তীর্ণ',
    'licenseWarningManage'  => 'লাইসেন্স পরিচালনা করুন',

    // Plugin license
    'pluginInvalidLicense' => 'এই প্লাগইনটির একটি অবৈধ বা মেয়াদোত্তীর্ণ লাইসেন্স রয়েছে এবং সক্রিয় করা যাবে না।',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'লাইসেন্স কী',
    'storeBrowseFull'       => 'সম্পূর্ণ স্টোর ব্রাউজ করুন',
    'storeBackToMarketplace'=> 'মার্কেটপ্লেসে ফিরে যান',
    'storeNoProducts'       => 'কোনো পণ্য উপলব্ধ নেই।',
    'storeViewInStore'      => 'স্টোরে দেখুন',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'ব্যবহারকারী',
    'editUserTitle'         => 'ব্যবহারকারী সম্পাদনা',
    'createUserTitle'       => 'ব্যবহারকারী তৈরি করুন',
    'authorProfileTitle'    => 'লেখক প্রোফাইল',
    'userRoleLabel'         => 'ভূমিকা',
    'userActiveLabel'       => 'সক্রিয়',
    'userPasswordLabel'     => 'পাসওয়ার্ড',
    'userPasswordOptional'  => 'বর্তমান পাসওয়ার্ড রাখতে ফাঁকা রাখুন',
    'userDisplayName'       => 'প্রদর্শন নাম',
    'userBio'               => 'বায়ো',
    'userWebsite'           => 'ওয়েবসাইট',
    'userTwitter'           => 'Twitter / X হ্যান্ডেল',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'অবতার',
    'userSaveProfile'       => 'প্রোফাইল সংরক্ষণ',
    'userSaveChanges'       => 'পরিবর্তন সংরক্ষণ',
    'userCannotDeleteSelf'  => 'নিজেকে মুছা যাবে না।',
    'userCannotDeleteOwner' => 'সাইট মালিকের অ্যাকাউন্ট মুছা যাবে না।',
    'userOwnerCannotModify' => 'সাইট মালিকের অ্যাকাউন্ট পরিবর্তন করা যাবে না।',

    // User flash messages
    'userCreated'           => 'ব্যবহারকারী তৈরি হয়েছে।',
    'userUpdated'           => 'ব্যবহারকারী আপডেট হয়েছে।',
    'userDeleted'           => 'ব্যবহারকারী মুছে ফেলা হয়েছে।',
    'userBanned'            => 'ব্যবহারকারী নিষিদ্ধ করা হয়েছে।',
    'userUnbanned'          => 'ব্যবহারকারীর নিষেধাজ্ঞা তুলে নেওয়া হয়েছে।',
    'userCannotBanSelf'     => 'আপনি নিজেকে বা সাইট মালিককে নিষিদ্ধ করতে পারবেন না।',
    'banStatus'             => 'নিষেধাজ্ঞার অবস্থা',
    'banned'                => 'নিষিদ্ধ',
    'ban'                   => 'ব্যবহারকারী নিষিদ্ধ করুন',
    'unban'                 => 'নিষেধাজ্ঞা তুলুন',
    'banReasonRequired'     => 'নিষেধাজ্ঞার কারণ প্রয়োজন।',
    'banReasonPlaceholder'  => 'নিষেধাজ্ঞার কারণ...',
    'confirmBanUser'        => 'আপনি কি এই ব্যবহারকারীকে নিষিদ্ধ করতে চান?',
    'userProfileSaved'      => 'প্রোফাইল সংরক্ষিত।',
    'userAvatarUploadFail'  => 'অবতার আপলোড ব্যর্থ: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA সেটআপ',
    'tfaSetupHeading'       => 'দুই-স্তর যাচাইকরণ সেট করুন',
    'tfaScanQr'             => 'আপনার অথেনটিকেটর অ্যাপ দিয়ে নিচের QR কোড স্ক্যান করুন (যেমন Google Authenticator, Authy)।',
    'tfaManualEntry'        => 'অথবা গোপন কীটি ম্যানুয়ালি দিন:',
    'tfaEnterCode'          => 'নিশ্চিত করতে আপনার অ্যাপ থেকে ৬-সংখ্যার কোড দিন:',
    'tfaCodeLabel'          => 'যাচাইকরণ কোড',
    'tfaConfirmBtn'         => 'নিশ্চিত করুন ও 2FA সক্ষম করুন',
    'tfaDisableBtn'         => '2FA অক্ষম করুন',
    'tfaDisableConfirm'     => 'অক্ষম করতে আপনার বর্তমান 2FA কোড দিন:',
    'tfaEnabled'            => 'দুই-স্তর যাচাইকরণ সক্ষম।',
    'tfaDisabled'           => 'দুই-স্তর যাচাইকরণ অক্ষম।',
    'tfaInvalidCode'        => 'অবৈধ কোড - QR কোড স্ক্যান করুন এবং আবার চেষ্টা করুন।',
    'tfaInvalidDisable'     => 'অবৈধ কোড - 2FA অক্ষম হয়নি।',
    'tfaSessionExpired'     => 'সেটআপ সেশন শেষ - আবার শুরু করুন।',
    'tfaNotEnabled'         => '2FA বর্তমানে সক্ষম নয়।',
    'tfaCantScan'           => "স্ক্যান করতে পারছেন না? এই কোডটি ম্যানুয়ালি দিন:",
    'tfaWarning'            => 'এই গোপন কীটি নিরাপদ স্থানে সংরক্ষণ করুন। অথেনটিকেটর ডিভাইস হারালে আপনার এটি প্রয়োজন হবে।',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'সামাজিক লিঙ্ক',
    'socialPlatform'           => 'প্ল্যাটফর্ম',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'আইকন',
    'socialSortOrder'          => 'বাছাই ক্রম',
    'socialIconPackInfo'       => 'বর্তমান থিম <strong>{0}</strong> আইকনের জন্য <strong>{1}</strong> (v{2}) ব্যবহার করে। নিচে আপনি উপলব্ধ আইকন বেছে নিতে পারেন যা এই সাইটের সামাজিক লিঙ্ক বৈশিষ্ট্যে প্রদর্শিত হবে।',
    'socialSearchPlaceholder'  => 'প্ল্যাটফর্ম খুঁজুন...',
    'socialIconDisclaimer'     => "এই আইকনগুলি ব্যবহৃত আইকনের প্রতিনিধিত্ব মাত্র। সক্রিয় থিমের আইকন প্যাকের উপর নির্ভর করে প্রকৃত আইকন ভিন্ন হতে পারে।",

    // Social flash messages
    'socialLinkAdded'       => 'সামাজিক লিঙ্ক যোগ হয়েছে।',
    'socialLinkUpdated'     => 'লিঙ্ক আপডেট হয়েছে।',
    'socialLinkDeleted'     => 'লিঙ্ক মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'পুনঃনির্দেশ',
    'redirectFrom'          => 'URL থেকে',
    'redirectTo'            => 'URL তে',
    'redirectType'          => 'ধরন',
    'redirectAdd'           => 'পুনঃনির্দেশ যোগ করুন',
    'redirectFromHint'      => '(আপেক্ষিক, যেমন /old-page)',
    'redirect301'           => '৩০১ স্থায়ী',
    'redirect302'           => '৩০২ অস্থায়ী',
    'redirectInvalidDest'   => 'অবৈধ পুনঃনির্দেশ গন্তব্য URL।',

    // Redirect flash messages
    'redirectAdded'         => 'পুনঃনির্দেশ যোগ হয়েছে।',
    'redirectDeleted'       => 'পুনঃনির্দেশ মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'সেটিংস',
    'settingsGeneral'       => 'সাধারণ',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'ইমেইল',
    'settingsSocialLogin'   => 'সামাজিক লগইন',
    'settingsSocialSharing' => 'সামাজিক শেয়ারিং',
    'settingsSpam'          => 'স্প্যাম সুরক্ষা',

    'generalSettingsHeading'    => 'সাধারণ সেটিংস',
    'generalSiteName'           => 'সাইটের নাম',
    'generalTagline'            => 'ট্যাগলাইন',
    'generalAdminEmail'         => 'অ্যাডমিন ইমেইল',
    'generalPostsPerPage'       => 'প্রতি পৃষ্ঠায় পোস্ট',
    'generalComments'           => 'মন্তব্য',
    'generalCommentsEnable'     => 'মন্তব্য সক্ষম করুন',
    'generalCommentModeration'  => 'প্রকাশের আগে মডারেশন প্রয়োজন',
    'generalMaintenanceMode'    => 'রক্ষণাবেক্ষণ মোড',
    'generalMaintenanceEnable'  => 'রক্ষণাবেক্ষণ মোড সক্ষম করুন',
    'generalMaintenanceHelp'    => "দর্শকরা \"আমরা শীঘ্রই ফিরে আসব\" পৃষ্ঠা দেখবে। অ্যাডমিনরা এখনও সাইট অ্যাক্সেস করতে পারবে।",
    'generalFrontPage'          => 'প্রথম পৃষ্ঠা',
    'generalFrontPageBlog'      => 'ব্লগ সূচক (সর্বশেষ পোস্ট)',
    'generalFrontPageStatic'    => 'স্থির পৃষ্ঠা:',
    'generalFrontPagePlugin'    => 'প্লাগইন পৃষ্ঠা:',
    'generalSelectPage'         => '- একটি পৃষ্ঠা নির্বাচন করুন -',
    'generalSelectRoute'        => '- একটি রুট নির্বাচন করুন -',
    'generalFrontPageNoPlugins' => 'কোনো প্লাগইন রুট উপলব্ধ নেই',
    'generalPageCacheTtl'       => 'পেজ ক্যাশ TTL',
    'settingsCacheTtlHint'      => 'সেকেন্ড। ০ = অক্ষম।',
    'generalSaveBtn'            => 'সাধারণ সেটিংস সংরক্ষণ',

    // General flash messages
    'generalSettingsSaved'      => 'সাধারণ সেটিংস সংরক্ষিত।',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO সেটিংস',
    'seoMetaDescription'        => 'মেটা বিবরণ',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'সাইটম্যাপ',
    'seoSitemapEnable'          => 'sitemap.xml সক্ষম করুন',
    'seoSitemapHelp'            => 'সমস্ত প্রকাশিত পোস্ট ও পৃষ্ঠার জন্য মানক সাইটম্যাপ।',
    'seoNewsSitemap'            => 'news-sitemap.xml সক্ষম করুন',
    'seoNewsSitemapHelp'        => 'Google News সাইটম্যাপ - গত ৪৮ ঘণ্টায় প্রকাশিত পোস্ট তালিকা করে।',
    'seoSaveBtn'                => 'SEO সেটিংস সংরক্ষণ',
    'seoSettingsSaved'          => 'SEO সেটিংস সংরক্ষিত।',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'ইমেইল সেটিংস',
    'emailFromName'             => 'প্রেরকের নাম',
    'emailFromAddress'          => 'প্রেরকের ঠিকানা',
    'emailProtocol'             => 'প্রোটোকল',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP হোস্ট',
    'emailSmtpPort'             => 'SMTP পোর্ট',
    'emailSmtpEncryption'       => 'এনক্রিপশন',
    'emailSmtpEncryptionNone'   => 'কোনোটি নয়',
    'emailSmtpUsername'         => 'SMTP ব্যবহারকারীর নাম',
    'emailSmtpPassword'         => 'SMTP পাসওয়ার্ড',
    'emailProvider'             => 'ইমেইল প্রদানকারী',
    'emailProviderCore'         => 'মূল (ডিফল্ট)',
    'emailProviderHelp'         => 'কোন প্লাগইন আউটবাউন্ড ইমেইল ডেলিভারি পরিচালনা করবে তা নির্বাচন করুন।',
    'emailSaveBtn'              => 'ইমেইল সেটিংস সংরক্ষণ',
    'emailSettingsSaved'        => 'ইমেইল সেটিংস সংরক্ষিত।',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'সামাজিক লগইন (OAuth)',
    'socialLoginHelp'           => 'শংসাপত্র আপনার .env ফাইলে সংরক্ষিত হয়। ক্লায়েন্ট ID ও সিক্রেট পেতে Google ও Facebook-এ আপনার অ্যাপ নিবন্ধন করুন।',
    'socialLoginGoogleId'       => 'Client ID',
    'socialLoginGoogleSecret'   => 'Client Secret',
    'socialLoginFbAppId'        => 'App ID',
    'socialLoginFbAppSecret'    => 'App Secret',
    'socialLoginPlaceholderSecret' => '(বিদ্যমান রাখতে ফাঁকা রাখুন)',
    'socialLoginSaveBtn'        => 'সামাজিক লগইন সেটিংস সংরক্ষণ',
    'socialLoginSettingsSaved'  => 'সামাজিক লগইন সেটিংস সংরক্ষিত।',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'প্রকাশে সামাজিক অটো-শেয়ার',
    'socialSharingHelp'         => 'যখন "প্রকাশে শেয়ার করুন" চেক করে পোস্ট প্রকাশিত হয়, Pubvana স্বয়ংক্রিয়ভাবে কনফিগার করা সামাজিক অ্যাকাউন্টে পোস্ট করবে।',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'developer.twitter.com → আপনার অ্যাপ → Keys and Tokens-এ কী পান।',
    'socialSharingApiKey'       => 'API Key',
    'socialSharingApiSecret'    => 'API Secret',
    'socialSharingAccessToken'  => 'Access Token',
    'socialSharingAccessSecret' => 'Access Secret',
    'socialSharingFbPage'       => 'Facebook Page',
    'socialSharingFbPageHelp'   => 'pages_manage_posts অনুমতি সহ Page Access Token প্রয়োজন।',
    'socialSharingFbPageId'     => 'Page ID',
    'socialSharingFbPageToken'  => 'Page Access Token',
    'socialSharingSaveBtn'      => 'শেয়ারিং সেটিংস সংরক্ষণ',
    'socialSharingSettingsSaved'=> 'সামাজিক শেয়ারিং সেটিংস সংরক্ষিত।',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'স্প্যাম সুরক্ষা (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana স্প্যাম বট থেকে মন্তব্য ফর্ম ও যোগাযোগ ফর্ম রক্ষা করতে hCaptcha (গোপনীয়তা-বান্ধব, নন-Google) ব্যবহার করে।',
    'spamHcaptchaFree'          => 'বেশিরভাগ সাইটের জন্য hCaptcha বিনামূল্যে। hcaptcha.com-এ সাইন আপ করুন, তারপর: আপনার সাইট কী পেতে Account → Sites → Add Site এবং আপনার সিক্রেট কী পেতে Account → Settings → Secret Key → Generate অনুসরণ করুন। নিচে দুটোই দিন।',
    'spamHcaptchaSiteKey'       => 'Site Key',
    'spamHcaptchaSecretKey'     => 'Secret Key',
    'spamHcaptchaNote'          => 'এই কীগুলি সেট না থাকলে hCaptcha নীরবে এড়িয়ে যায় — স্থানীয় উন্নয়নের জন্য নিরাপদ। একবার সংরক্ষণ করলে উইজেট স্বয়ংক্রিয়ভাবে মন্তব্য ফর্ম ও যোগাযোগ পৃষ্ঠায় দেখা যায়।',
    'spamSettingsSaved'         => 'স্প্যাম সুরক্ষা সেটিংস সংরক্ষিত।',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'ভাষা',
    'languageCode'              => 'কোড',
    'languageName'              => 'নাম',
    'languageDefault'           => 'ডিফল্ট',
    'languageEnabled'           => 'সক্ষম',
    'languageMakeDefault'       => 'ডিফল্ট করুন',
    'languageSetAsDefault'      => '{0} ডিফল্ট ভাষা হিসেবে সেট।',
    'languageEnabled_msg'       => '{0} সক্ষম।',
    'languageDisabled_msg'      => '{0} অক্ষম।',
    'languageNotFound'          => 'ভাষা পাওয়া যায়নি।',
    'languageCannotDisable'     => 'ডিফল্ট ভাষা অক্ষম করা যাবে না।',
    'languageDirection'         => 'দিকনির্দেশনা',
    'languageNativeName'        => 'স্থানীয় নাম',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'বিশ্লেষণ',
    'analyticsTotalViews'       => 'মোট দর্শন',
    'analyticsTopPosts'         => 'শীর্ষ পোস্ট',
    'analyticsReferrers'        => 'শীর্ষ রেফারার',
    'analyticsLast7'            => 'শেষ ৭ দিন',
    'analyticsLast30'           => 'শেষ ৩০ দিন',
    'analyticsLast90'           => 'শেষ ৯০ দিন',
    'analyticsChartTitle'       => 'পৃষ্ঠা দর্শন',
    'analyticsNoData'           => 'এই সময়কালে কোনো বিশ্লেষণ ডেটা নেই।',
    'analyticsDomain'           => 'ডোমেইন',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'অ্যাফিলিয়েট লিঙ্ক',
    'newAffiliateLinkTitle'     => 'নতুন অ্যাফিলিয়েট লিঙ্ক',
    'editAffiliateLinkTitle'    => 'অ্যাফিলিয়েট লিঙ্ক সম্পাদনা',
    'affiliateName'             => 'নাম',
    'affiliateSlug'             => 'স্লাগ',
    'affiliateDestination'      => 'গন্তব্য URL',
    'affiliateActive'           => 'সক্রিয়',
    'affiliateClicks'           => 'ক্লিক',
    'affiliateClicksTitle'      => 'ক্লিক - {0}',
    'affiliateTotal'            => 'মোট',
    'affiliateViewClicks'       => 'ক্লিক দেখুন',

    // Affiliate flash messages
    'affiliateCreated'          => 'অ্যাফিলিয়েট লিঙ্ক তৈরি হয়েছে।',
    'affiliateUpdated'          => 'অ্যাফিলিয়েট লিঙ্ক আপডেট হয়েছে।',
    'affiliateDeleted'          => 'অ্যাফিলিয়েট লিঙ্ক মুছে ফেলা হয়েছে।',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'ভাঙা লিঙ্ক',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP অবস্থা',
    'brokenLinkError'           => 'ত্রুটি',
    'brokenLinkSource'          => 'উৎস',
    'brokenLinkShowDismissed'   => 'বাতিল দেখান',
    'brokenLinkHideDismissed'   => 'বাতিল লুকান',
    'brokenLinkTimeout'         => 'টাইমআউট',
    'brokenLinkBroken'          => 'ভাঙা',
    'brokenLinkNone'            => 'কোনো ভাঙা লিঙ্ক পাওয়া যায়নি।',
    'brokenLinkNowReachable'    => 'লিঙ্ক এখন পৌঁছানো যাচ্ছে - ফলাফল থেকে সরানো হয়েছে।',
    'brokenLinkStillBroken'     => 'লিঙ্ক এখনও ভাঙা ({0})।',
    'brokenLinkDismissed'       => 'লিঙ্ক বাতিল করা হয়েছে।',
    'brokenLinksCliHint'        => 'এই প্রতিবেদন পূরণ করতে কমান্ড লাইন থেকে সম্পূর্ণ স্ক্যান চালান: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0}টি সমস্যা পাওয়া গেছে',
    'brokenLinksCount'          => '{0}টি ভাঙা',
    'brokenLinksRecheck'        => 'এই URL পুনরায় পরীক্ষা করুন',
    'brokenLinksDismiss'        => 'বাতিল করুন (ফলাফল থেকে লুকান)',
    'brokenLinksRunScan'        => 'স্ক্যান চালান',
    'brokenLinksScanComplete'   => 'স্ক্যান সম্পন্ন: {0}টি লিঙ্ক পরীক্ষিত, {1}টি ভাঙা।',
    'timeout'                   => 'টাইমআউট',
    'typePost'                  => 'পোস্ট',
    'typePage'                  => 'পৃষ্ঠা',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'কার্যকলাপ লগ',
    'activityLogType'           => 'ধরন',
    'activityLogAction'         => 'ক্রিয়া',
    'activityLogUser'           => 'ব্যবহারকারী',
    'activityLogDate'           => 'তারিখ',
    'activityLogNote'           => 'নোট',
    'activityLogFilterAll'      => 'সব ধরন',
    'activityLogEmpty'          => 'এখনও কোনো কার্যকলাপ রেকর্ড হয়নি।',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'ব্যাকআপ ও রপ্তানি',
    'backupDownload'            => 'ব্যাকআপ তৈরি ও ডাউনলোড করুন',
    'backupFiles'               => 'উপলব্ধ ব্যাকআপ',
    'backupFilename'            => 'ফাইলের নাম',
    'backupSize'                => 'আকার',
    'backupDate'                => 'তৈরি',
    'backupGenerating'          => 'ব্যাকআপ তৈরি হচ্ছে…',
    'backupNoFiles'             => 'কোনো সংরক্ষিত ব্যাকআপ নেই।',
    'backupFailed'              => 'ব্যাকআপ ব্যর্থ: {0}',
    'backupDeleted'             => 'ব্যাকআপ মুছে ফেলা হয়েছে।',
    'backupCannotDelete'        => 'ব্যাকআপ মুছতে পারা যায়নি।',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP গুলি SHA-256 হ্যাশ হিসেবে সংরক্ষিত — কোনো কাঁচা PII রেকর্ড করা হয় না।',
    'colTime'                   => 'সময়',
    'colIpHash'                 => 'IP হ্যাশ',
    'colReferrer'               => 'রেফারার',
    'affiliateDirectReferrer'   => 'সরাসরি',
    'affiliateNameHint'         => 'অভ্যন্তরীণ লেবেল — দর্শকদের দেখানো হয় না।',
    'affiliateSlugHint'         => 'শুধুমাত্র অক্ষর, সংখ্যা, হাইফেন ও আন্ডারস্কোর। লিঙ্ক শেয়ার হলে পরিবর্তন করা যাবে না।',
    'affiliateDestHint'         => 'https:// অন্তর্ভুক্ত করতে হবে। দর্শকদের ৩০১-পুনঃনির্দেশ করা হবে।',
    'affiliateInactiveHint'     => 'নিষ্ক্রিয় লিঙ্ক ৪০৪ ফেরত দেয়।',
    'affiliateLinkCount'        => '{0}টি লিঙ্ক',
    'colDomain'                 => 'ডোমেইন',
    'commentAll'                => 'সব',
    'commentPending'            => 'অপেক্ষমান',
    'commentTrash'              => 'ট্র্যাশ',
    'commentsNone'              => 'কোনো {0} মন্তব্য নেই।',

    'backupCreate'              => 'ব্যাকআপ তৈরি করুন',
    'backupStarting'            => 'ব্যাকআপ শুরু হচ্ছে...',
    'backupNoneYet'             => 'এখনও কোনো ব্যাকআপ নেই। আপনার প্রথমটি তৈরি করতে "ব্যাকআপ তৈরি করুন" ক্লিক করুন।',
    'backupsTitle'              => 'ব্যাকআপ',
    'backupRetentionNote'       => 'সর্বোচ্চ ১৫টি ব্যাকআপ রাখা হয় — সবচেয়ে পুরনো স্বয়ংক্রিয়ভাবে মুছে যায়।',
    'backupRestoreConfirm'      => 'এই ব্যাকআপ পুনরুদ্ধার করবেন? প্রথমে বর্তমান অবস্থার একটি ব্যাকআপ তৈরি হবে।',
    'backupDeleteConfirm'       => 'এই ব্যাকআপ মুছবেন?',
    'colFilename'               => 'ফাইলের নাম',
    'colVersion'                => 'সংস্করণ',
    'colTrigger'                => 'ট্রিগার',
    'colSize'                   => 'আকার',
    'colDate'                   => 'তারিখ',
    'colActions'                => 'ক্রিয়া',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'আমদানি',
    'importWpHeading'           => 'WordPress থেকে আমদানি',
    'importWpHelp'              => 'Tools → Export এর মাধ্যমে আপনার WordPress সাইট রপ্তানি করুন, তারপর নিচে .xml ফাইল আপলোড করুন।',
    'importChooseFile'          => 'WXR ফাইল (.xml) বেছে নিন',
    'importDryRun'              => 'ড্রাই রান (শুধু প্রিভিউ - কিছু সংরক্ষণ হবে না)',
    'importRunBtn'              => 'আমদানি চালান',
    'importNoValidFile'         => 'একটি বৈধ WordPress WXR রপ্তানি ফাইল আপলোড করুন।',
    'importOnlyXml'             => 'শুধুমাত্র .xml ফাইল গ্রহণযোগ্য।',
    'importFileTooLarge'        => 'আমদানি ফাইল অনেক বড়। সর্বোচ্চ আকার ৫০ MB।',
    'importResultsHeading'      => 'আমদানি ফলাফল',
    'importDryRunNote'          => 'ড্রাই রান - কোনো ডেটা সংরক্ষিত হয়নি।',
    'importDryRunLabel'         => '(ড্রাই রান — কোনো ডেটা লেখা হয়নি)',
    'importComplete'            => 'আমদানি সম্পন্ন',
    'importCreated'             => 'তৈরি',
    'importSkipped'             => 'এড়িয়ে যাওয়া',
    'importErrors'              => 'ত্রুটি:',
    'importInstructions'        => 'আপনার WordPress বিষয়বস্তু <strong>Tools → Export → All content</strong> থেকে রপ্তানি করুন এবং <code>.xml</code> ফাইল এখানে আপলোড করুন। Pubvana পোস্ট, পৃষ্ঠা, বিভাগ, ট্যাগ, লেখক ও মন্তব্য আমদানি করবে।',
    'importCliTitle'            => 'CLI আমদানি',
    'importCliHint'             => 'আপনি কমান্ড লাইন থেকেও ইম্পোর্টার চালাতে পারেন:',
    'importCliDryRunHint'       => '<code>--dry-run</code> ফ্ল্যাগ ডেটাবেসে না লিখে কী আমদানি হবে তা দেখায়।',
    'importWhatTitle'           => 'কী আমদানি হয়',
    'importItemPosts'           => 'পোস্ট (শিরোনাম, বিষয়বস্তু, সংক্ষেপ, স্লাগ, অবস্থা)',
    'importItemPages'           => 'পৃষ্ঠা',
    'importItemCategories'      => 'বিভাগ (শ্রেণিবিন্যাস সহ)',
    'importItemTags'            => 'ট্যাগ',
    'importItemAuthors'         => 'লেখক (সাবস্ক্রাইবার অ্যাকাউন্ট হিসেবে তৈরি)',
    'importItemComments'        => 'মন্তব্য',
    'importItemMedia'           => 'মিডিয়া ফাইল (URL বিষয়বস্তুতে সংরক্ষিত)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'আপডেট',
    'updatesCurrentVersion'     => 'বর্তমান সংস্করণ',
    'updatesLatestVersion'      => 'সর্বশেষ সংস্করণ',
    'updatesUpToDate'           => 'Pubvana আপ টু ডেট।',
    'updatesAvailable'          => 'আপডেট উপলব্ধ: {0}',
    'updatesCheckBtn'           => 'আপডেট পরীক্ষা করুন',
    'updatesReleaseNotes'       => 'রিলিজ নোট',
    'updatesHowToApply'         => 'আপডেট কীভাবে প্রয়োগ করবেন',
    'updatesCacheCleared'       => 'আপডেট ক্যাশ সাফ - এখন পুনরায় পরীক্ষা করা হচ্ছে।',
    'updatesExtCapped'          => 'আপডেট উপলব্ধ: {0} (addon-safe)',
    'updatesNewerAvailable'     => 'Pubvana {0} ও উপলব্ধ - এটি আনলক করতে নিচে তালিকাভুক্ত অ্যাডন আপডেট করুন।',

    // Addon Updates
    'updatesExtTitle'               => 'অ্যাডন',
    'updatesExtCheckAll'            => 'সব পরীক্ষা করুন',
    'updatesExtUpdateAll'           => 'সব আপডেট করুন',
    'updatesExtCheckAllType'        => 'সব {0} পরীক্ষা করুন',
    'updatesExtUpdateAllType'       => 'সব {0} আপডেট করুন',
    'updatesExtNoInstalled'         => 'কোনো {0} ইনস্টল নেই।',
    'updatesExtColName'             => 'নাম',
    'updatesExtColVersion'          => 'সংস্করণ',
    'updatesExtColLatest'           => 'সর্বশেষ',
    'updatesExtColAutoUpdate'       => 'অটো-আপডেট',
    'updatesExtColStatus'           => 'অবস্থা',
    'updatesExtColActions'          => 'ক্রিয়া',
    'updatesExtBundled'             => 'কোর বান্ডেলড',
    'updatesExtNoSource'            => 'কোনো আপডেট উৎস নেই',
    'updatesExtFailed'              => 'ব্যর্থ',
    'updatesExtUpdatedAt'           => '{0} তে আপডেট হয়েছে',
    'updatesExtAvailable'           => 'আপডেট উপলব্ধ',
    'updatesExtUpToDate'            => 'আপ টু ডেট',
    'updatesExtUpdate'              => 'আপডেট',
    'updatesExtChecking'            => 'পরীক্ষা করা হচ্ছে...',
    'updatesExtUpdating'            => 'আপডেট হচ্ছে...',
    'updatesExtUpdated'             => 'আপডেট হয়েছে',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'আপডেট নিশ্চিত করুন',
    'updatesConfirmBody'            => 'এটি আপনার সাইটের ব্যাকআপ নেবে, আপডেট ডাউনলোড করবে এবং প্রয়োগ করবে।',
    'updatesConfirmSafe'            => 'আপনার <code>.env</code>, <code>App.php</code>, এবং <code>Database.php</code> কখনো ওভাররাইট হবে না।',
    'updatesConfirmBtn'             => 'এখনই আপডেট করুন',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'সব অ্যাডন আপডেট করুন',
    'updatesExtAllBody'             => 'এটি অপেক্ষমান আপডেট আছে এমন সব অ্যাডন আপডেট করবে।',
    'updatesExtAllNote'             => 'অটো-আপডেট অক্ষম অ্যাডনও আপডেট হবে।',
    'updatesExtAllBtn'              => 'সব আপডেট করুন',

    'updatesExtBadge'               => 'আপডেট: v{0}',
    'updatesExtGoToUpdates'         => 'আপডেট',

    // Update Settings
    'updatesSettingsTitle'          => 'আপডেট সেটিংস',
    'updatesAutoUpdateLabel'        => 'Pubvana অটো-আপডেট',
    'updatesAutoUpdateManual'       => 'ম্যানুয়াল',
    'updatesAutoUpdateAuto'         => 'স্বয়ংক্রিয়',
    'updatesAutoUpdateHelp'         => 'সক্ষম হলে, Pubvana-র ব্রেকিং পরিবর্তন ছাড়া আপডেট স্বয়ংক্রিয়ভাবে প্রয়োগ হয়।',
    'updatesCheckMethodLabel'       => 'আপডেট পরীক্ষার পদ্ধতি',
    'updatesCheckMethodPageload'    => 'পেজ লোড',
    'updatesCheckMethodCron'        => 'Cron Job',
    'updatesCheckMethodHelp'        => 'পেজ লোড প্রতিটি অনুরোধে পরীক্ষা করে (২৪ঘণ্টা ক্যাশ)। Cron-এর জন্য সার্ভার cron job প্রয়োজন।',
    'updatesCronCommand'            => 'Cron কমান্ড',
    'updatesCronHelp'               => 'দৈনিক আপডেট পরীক্ষা চালাতে আপনার সার্ভারের crontab-এ এটি যোগ করুন:',
    'updatesSettingsSaved'          => 'আপডেট সেটিংস সংরক্ষিত।',

    // Compatibility
    'compatWarningTitle'            => 'সামঞ্জস্যতা সতর্কতা',
    'compatNotCompatible'           => 'কিছু ইনস্টল করা অ্যাডন এই সংস্করণের সাথে সামঞ্জস্যপূর্ণ নয়।',
    'compatRequiresUpdate'          => 'কিন্তু প্রথমে নিচের অ্যাডনগুলি আপডেট করতে হবে:',
    'compatSupportsUpTo'            => '{0} পর্যন্ত সমর্থন করে',
    'compatRequiresMin'             => 'Pubvana {0}+ প্রয়োজন',
    'compatNotDeclared'             => 'নিচের অ্যাডনগুলি Pubvana {0} এর সাথে সামঞ্জস্যতা ঘোষণা করেনি। আপডেটের পরে এগুলি কাজ করা বন্ধ করতে পারে:',
    'compatColType'                 => 'ধরন',
    'compatColName'                 => 'নাম',
    'compatColVersion'              => 'সামঞ্জস্যতা',
    'compatRemoveHint'              => 'সমস্যা হলে অসামঞ্জস্য অ্যাডন সরান বা ডিফল্ট থিমে স্যুইচ করুন। প্রতিটি আপডেটের আগে ব্যাকআপ তৈরি হয়।',
    'compatMaxVersion'              => 'সর্বোচ্চ সামঞ্জস্য সংস্করণ: {0}',
    'compatMinVersion'              => 'Pubvana {0}+ প্রয়োজন',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'পোস্ট সময়সূচি',
    'scheduleNoScheduled'       => 'কোনো নির্ধারিত পোস্ট নেই।',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'সংশোধন - {0}',
    'revisionPageTitle'         => 'সংশোধন - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'অ্যাডমিন প্যানেল অ্যাক্সেস করতে আপনাকে লগ ইন করতে হবে।',
    'dirNotWritable'            => 'ডিরেক্টরি লেখযোগ্য নয়: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} সঠিকভাবে কনফিগার করা হয়নি। আপনি যদি শেষ ব্যবহারকারী হন, ডেভেলপারের সাথে যোগাযোগ করুন। আপনি যদি ডেভেলপার হন, ডকুমেন্টেশন দেখুন।',
    'addonMisconfiguredLink'    => '{0} সঠিকভাবে কনফিগার করা হয়নি। আপনি যদি শেষ ব্যবহারকারী হন <a href="{1}">ডেভেলপারের সাথে যোগাযোগ করুন</a>। আপনি যদি ডেভেলপার হন <a href="https://github.com/enlivenapp/pubvana">ডকুমেন্টেশন দেখুন</a>।',
    'licenseExpiringSoon'       => '{0} এর লাইসেন্স {1} তে মেয়াদ শেষ হবে। লাইসেন্স মেয়াদ শেষ হলে {0} নিষ্ক্রিয় হবে।',
    'licenseExpiredDeactivated' => '{0} নিষ্ক্রিয় করা হয়েছে কারণ লাইসেন্সের মেয়াদ শেষ হয়েছে।',
    'addonDeactivated'          => '{0} নিষ্ক্রিয় করা হয়েছে। কারণ: {1}।',
    'widgetValidationFailed'    => "উইজেট ''{0}'' যাচাই করা যায়নি। ডেভেলপারের সাথে যোগাযোগ করুন বা অ্যাডন সরান।",
    'widgetValidationFailedLink' => "উইজেট ''{0}'' যাচাই করা যায়নি। <a href=\"{1}\">ডেভেলপারের সাথে যোগাযোগ করুন</a> বা অ্যাডন সরান।",

    // Inline warnings on addon listing
    'addonDeactivatedExpired'   => 'নিষ্ক্রিয়: লাইসেন্স মেয়াদ শেষ',
    'addonDeactivatedTampered'  => 'নিষ্ক্রিয়: সঠিকভাবে কনফিগার হয়নি',
    'addonDeactivatedNoLicense' => 'নিষ্ক্রিয়: কোনো বৈধ লাইসেন্স নেই',

    // Disabled addon reasons
    'addonDisabled'             => 'অক্ষম',
    'addonDisabledInvalidJson'  => 'সিস্টেম: {0} এর {1} অবৈধ বা অপঠনযোগ্য।',
    'addonDisabledMissingFields' => 'সিস্টেম: {0} এ প্রয়োজনীয় ক্ষেত্র অনুপস্থিত: {1}।',
    'addonDisabledPhpFiles'     => 'সিস্টেম: {0} এ PHP ফাইল রয়েছে। উইজেট শুধুমাত্র JSON + টেমপ্লেট হতে পারে।',

    // Flash messages (on activation attempt)
    'licenseRequired'           => '{0} সক্রিয় করতে একটি বৈধ লাইসেন্স প্রয়োজন।',
    'licenseInvalidActivation'  => '{0} এর জন্য লাইসেন্স যাচাইকরণ ব্যর্থ। আপনার লাইসেন্স কী পরীক্ষা করুন।',
    'licenseExpiredActivation'  => '{0} এর লাইসেন্সের মেয়াদ শেষ হয়েছে। সক্রিয় করতে নবায়ন করুন।',
    'licenseCheckUnreachable'   => '{0} এর লাইসেন্স যাচাই করা যায়নি। লাইসেন্স সার্ভার অপ্রাপ্য। পরে আবার চেষ্টা করুন।',
    'activationBlockedTampered' => '{0} সক্রিয় করা যাচ্ছে না কারণ এটি সঠিকভাবে কনফিগার হয়নি।',
    'activationBlockedBundled'  => '{0} সক্রিয় করা যাচ্ছে না: শুধুমাত্র Pubvana অ্যাডন বান্ডেলড হিসেবে চিহ্নিত হতে পারে।',
    'activationBlockedNoUrls'   => '{0} সক্রিয় করা যাচ্ছে না: পেইড অ্যাডনে লাইসেন্স যাচাইকরণ URL অন্তর্ভুক্ত করতে হবে।',
    'activationBlockedFreeFlag' => '{0} সক্রিয় করা যাচ্ছে না: Pubvana অ্যাডন বিনামূল্যে হিসেবে চিহ্নিত হতে পারে না।',
    'activationBlockedDisabled' => '{0} সক্রিয় করা যাচ্ছে না কারণ এতে কনফিগারেশন ত্রুটি রয়েছে। info ফাইল পরীক্ষা করুন।',

    // Third-party license
    'licenseThirdPartyLabel'    => '৩য় পক্ষ',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'আপডেট শুরু হচ্ছে...',
    'updateCheckLabel'           => 'আপডেট পরীক্ষা:',
    'updateAvailable'            => 'Pubvana {0} উপলব্ধ!',
    'updateRunning'              => 'আপনি {0} চালাচ্ছেন।',
    'updateBreakingChanges'      => 'ব্রেকিং পরিবর্তন',
    'updateMigrationNotes'       => 'মাইগ্রেশন নোট',
    'updateNotices'              => 'বিজ্ঞপ্তি',
    'updatePreflightTitle'       => 'প্রি-ফ্লাইট পরীক্ষা',
    'updateToVersion'            => 'Pubvana {0} এ আপডেট করুন',
    'updatePreflightFailed'      => 'এক বা একাধিক প্রয়োজনীয় প্রি-ফ্লাইট পরীক্ষা ব্যর্থ হয়েছে। আপডেট করার আগে সেগুলি সমাধান করুন।',
    'updateUpToDate'             => 'Pubvana আপ টু ডেট। আপনি সংস্করণ {0} চালাচ্ছেন।',
    'updateAnyway'               => 'যাইহোক আপডেট করুন',
    'updateAvailableTooltip'     => 'Pubvana {0} উপলব্ধ',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(আপনি)',
    'usersNone'                  => 'কোনো ব্যবহারকারী পাওয়া যায়নি।',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'অ্যাকাউন্ট সক্রিয়',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'প্রোফাইল বিস্তারিত',
    'profileDisplayNameHint'     => 'ব্যবহারকারীর নামের পরিবর্তে প্রকাশিত পোস্টে দেখানো হয়।',
    'profileAvatarHint'          => 'JPEG, PNG, WebP বা GIF। সর্বোচ্চ ১০ MB।',
    'profileSocialHandles'       => 'সামাজিক হ্যান্ডেল',
    'preview'                    => 'প্রিভিউ',
    'website'                    => 'ওয়েবসাইট',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'দুই-স্তর যাচাইকরণ',
    'totpActiveDesc'             => 'TOTP দুই-স্তর যাচাইকরণ আপনার অ্যাকাউন্টে সক্রিয়। প্রতিবার লগ ইন করার সময় আপনার অথেনটিকেটর অ্যাপ থেকে ৬-সংখ্যার কোড চাওয়া হবে।',
    'totpCurrentCode'            => 'বর্তমান কোড',
    'totpInactiveDesc'           => 'আপনার অ্যাকাউন্টে সুরক্ষার একটি অতিরিক্ত স্তর যোগ করুন। একবার সক্ষম হলে প্রতিটি লগইনে আপনার অথেনটিকেটর অ্যাপ থেকে কোড দিতে হবে।',
    'totpEnable'                 => 'দুই-স্তর যাচাইকরণ সক্ষম করুন',
    'totpScanInstructions'       => 'আপনার অথেনটিকেটর অ্যাপ (Google Authenticator, Authy, 1Password, ইত্যাদি) খুলুন এবং এই QR কোড স্ক্যান করুন।',
    'totpManualEntry'            => "স্ক্যান করতে পারছেন না? এই কোডটি ম্যানুয়ালি দিন:",
    'totpConfirmInstructions'    => 'স্ক্যান করার পরে সেটআপ নিশ্চিত করতে আপনার অ্যাপে দেখানো ৬-সংখ্যার কোড দিন।',
    'totpRecoveryWarning'        => 'আপনার পুনরুদ্ধার কোড সংরক্ষণ করুন। অথেনটিকেটর অ্যাপে অ্যাক্সেস হারালে আপনি লগ ইন করতে পারবেন না। 2FA রিসেট করতে সাইট প্রশাসকের সাথে যোগাযোগ করুন।',

];
