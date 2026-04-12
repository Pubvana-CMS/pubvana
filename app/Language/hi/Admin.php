<?php

/**
 * Pubvana CMS - Admin language strings (Hindi)
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
    'save'              => 'सहेजें',
    'saveChanges'       => 'परिवर्तन सहेजें',
    'cancel'            => 'रद्द करें',
    'edit'              => 'संपादित करें',
    'delete'            => 'हटाएं',
    'create'            => 'बनाएं',
    'add'               => 'जोड़ें',
    'back'              => 'वापस',
    'view'              => 'देखें',
    'apply'             => 'लागू करें',
    'install'           => 'इंस्टॉल करें',
    'update'            => 'अपडेट करें',
    'refresh'           => 'रीफ्रेश करें',
    'activate'          => 'सक्रिय करें',
    'deactivate'        => 'निष्क्रिय करें',
    'enable'            => 'सक्षम करें',
    'disable'           => 'अक्षम करें',
    'disabled'          => 'अक्षम',
    'approve'           => 'अनुमोदित करें',
    'spam'              => 'स्पैम',
    'trash'             => 'ट्रैश',
    'restore'           => 'पुनर्स्थापित करें',
    'dismiss'           => 'खारिज करें',
    'recheck'           => 'पुनः जाँचें',
    'clickToCopy'       => 'कॉपी करने के लिए क्लिक करें',
    'download'          => 'डाउनलोड करें',
    'upload'            => 'अपलोड करें',
    'import'            => 'आयात करें',
    'export'            => 'निर्यात करें',
    'publish'           => 'प्रकाशित करें',
    'unpublish'         => 'अप्रकाशित करें',
    'logout'            => 'लॉगआउट',
    'viewSite'          => 'साइट देखें',
    'newPost'           => 'नई पोस्ट',
    'buyNow'            => 'अभी खरीदें',
    'visitStore'        => 'स्टोर जाएं',
    'loadMore'          => 'और लोड करें',

    // Table headers / labels
    'title'             => 'शीर्षक',
    'name'              => 'नाम',
    'slug'              => 'स्लग',
    'status'            => 'स्थिति',
    'date'              => 'तारीख',
    'actions'           => 'क्रियाएँ',
    'author'            => 'लेखक',
    'views'             => 'दृश्य',
    'type'              => 'प्रकार',
    'url'               => 'URL',
    'description'       => 'विवरण',
    'role'              => 'भूमिका',
    'email'             => 'ईमेल',
    'username'          => 'उपयोगकर्ता नाम',
    'active'            => 'सक्रिय',
    'version'           => 'संस्करण',
    'size'              => 'आकार',
    'clicks'            => 'क्लिक',
    'total'             => 'कुल',
    'platform'          => 'प्लेटफ़ॉर्म',
    'label'             => 'लेबल',
    'order'             => 'क्रम',
    'source'            => 'स्रोत',
    'content'           => 'सामग्री',
    'excerpt'           => 'संक्षेप',
    'details'           => 'विवरण',
    'contentType'       => 'सामग्री प्रकार',
    'seo'               => 'SEO',
    'metaTitle'         => 'मेटा शीर्षक',
    'metaDescription'   => 'मेटा विवरण',

    // Status badges
    'published'         => 'प्रकाशित',
    'draft'             => 'ड्राफ्ट',
    'scheduled'         => 'निर्धारित',
    'pending'           => 'लंबित',
    'safe'              => 'सुरक्षित',
    'notSafe'           => 'असुरक्षित',
    'malicious'         => 'दुर्भावनापूर्ण',
    'safetyUnknown'     => 'अज्ञात',
    'inactive'          => 'निष्क्रिय',
    'installed'         => 'स्थापित',
    'free'              => 'मुफ्त',
    'premium'           => 'प्रीमियम',
    'all'               => 'सभी',

    // Confirmations
    'confirmDelete'         => 'क्या आप वाकई इस आइटम को हटाना चाहते हैं?',
    'confirmDeletePost'     => 'यह पोस्ट हटाएं?',
    'confirmDeletePage'     => 'यह पृष्ठ हटाएं?',
    'confirmDeleteComment'  => 'इस टिप्पणी को स्थायी रूप से हटाएं?',
    'confirmDeleteUser'     => 'यह उपयोगकर्ता हटाएं?',
    'confirmDeleteMedia'    => 'हटाएं?',
    'confirmDeleteBackup'   => 'यह बैकअप फ़ाइल हटाएं?',
    'confirmBulkAction'     => 'चुनी गई पोस्ट पर बल्क क्रिया लागू करें?',

    // Empty states
    'noPostsYet'        => 'अभी तक कोई पोस्ट नहीं। {0}',
    'noResultsFound'    => 'कोई परिणाम नहीं मिला।',
    'noCommentsYet'     => 'कोई लंबित टिप्पणी नहीं।',
    'noMediaYet'        => 'अभी तक कोई मीडिया नहीं।',
    'noItemsFound'      => 'मार्केटप्लेस में कोई आइटम नहीं मिला।',
    'noCategoriesYet'   => 'अभी तक कोई श्रेणी नहीं।',
    'noTagsYet'         => 'अभी तक कोई टैग नहीं।',
    'noRevisionsYet'    => 'कोई संशोधन नहीं मिला।',

    // Misc common
    'permissionDenied'  => 'अनुमति अस्वीकृत।',
    'notFound'          => 'रिकॉर्ड नहीं मिला।',
    'commasSeparated'   => 'अल्पविराम से अलग',
    'optional'          => 'वैकल्पिक',
    'required'          => 'आवश्यक',
    'enabled'           => 'सक्षम',
    'selected'          => '{0} पोस्ट चुनी गई',
    'published_count'   => '{0} प्रकाशित',
    'pending_count'     => '{0} लंबित',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'डैशबोर्ड',
    'navContent'        => 'सामग्री',
    'navAppearance'     => 'उपस्थिति',
    'navUsersAndSite'   => 'उपयोगकर्ता और साइट',
    'navTools'          => 'उपकरण',
    'navMarketplace'    => 'मार्केटप्लेस',
    'navPlugins'        => 'प्लगइन',
    'navPosts'          => 'पोस्ट',
    'navSchedule'       => 'शेड्यूल',
    'navPages'          => 'पृष्ठ',
    'navCategories'     => 'श्रेणियाँ',
    'navTags'           => 'टैग',
    'navComments'       => 'टिप्पणियाँ',
    'navMedia'          => 'मीडिया',
    'navImport'         => 'आयात',
    'navThemes'         => 'थीम',
    'navWidgets'        => 'विजेट',
    'navNavigation'     => 'नेविगेशन',
    'navUsers'          => 'उपयोगकर्ता',
    'navSocialLinks'    => 'सोशल लिंक',
    'navRedirects'      => 'रीडायरेक्ट',
    'navLanguages'      => 'भाषाएँ',
    'navSettings'       => 'सेटिंग्स',
    'navAnalytics'      => 'विश्लेषण',
    'navAffiliates'     => 'एफिलिएट लिंक',
    'navBrokenLinks'    => 'टूटे हुए लिंक',
    'navActivityLog'    => 'गतिविधि लॉग',
    'navBackup'         => 'बैकअप और निर्यात',
    'navUpdates'        => 'अपडेट',
    'navBrowse'         => 'ब्राउज़ करें',
    'navLicenses'       => 'लाइसेंस',
    'navPubvanaStore'   => 'Pubvana स्टोर',
    'navUpdateAvailable'=> 'अपडेट उपलब्ध',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'छोड़ने के लिए तैयार हैं?',
    'logoutModalBody'   => 'अपना सत्र समाप्त करने के लिए नीचे "लॉगआउट" चुनें।',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'डैशबोर्ड',
    'dashStats'             => 'आँकड़े',
    'dashPosts'             => 'पोस्ट',
    'dashPages'             => 'पृष्ठ',
    'dashComments'          => 'टिप्पणियाँ',
    'dashUsers'             => 'उपयोगकर्ता',
    'dashRecentPosts'       => 'हालिया पोस्ट',
    'dashPendingComments'   => 'लंबित टिप्पणियाँ',
    'dashViewAll'           => 'सभी देखें',
    'dashCreateOne'         => 'एक बनाएं!',
    'dashNoPosts'           => 'अभी तक कोई पोस्ट नहीं।',
    'dashNoPendingComments' => 'कोई लंबित टिप्पणी नहीं।',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'पोस्ट',
    'newPostTitle'          => 'नई पोस्ट',
    'editPostTitle'         => 'पोस्ट संपादित करें: {0}',
    'copyPreviewLink'       => 'पूर्वावलोकन लिंक कॉपी करें',
    'backToPosts'           => 'पोस्ट पर वापस जाएं',
    'postTitleField'        => 'शीर्षक *',
    'postEditor'            => 'संपादक',
    'postHtmlEditor'        => 'HTML संपादक',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'संक्षेप',
    'postExcerptPlaceholder'=> 'वैकल्पिक संक्षिप्त सारांश...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'मेटा शीर्षक',
    'postMetaDescription'   => 'मेटा विवरण',
    'postPublishSection'    => 'प्रकाशित करें',
    'postStatus'            => 'स्थिति',
    'postStatusDraft'       => 'ड्राफ्ट',
    'postStatusPublished'   => 'प्रकाशित',
    'postStatusScheduled'   => 'निर्धारित',
    'postScheduledAt'       => 'निर्धारित तारीख और समय',
    'postFeatured'          => 'फीचर्ड पोस्ट',
    'postMembersOnly'       => 'केवल सदस्यों के लिए',
    'postShareOnPublish'    => 'प्रकाशन पर सोशल पर साझा करें',
    'postSaveBtn'           => 'पोस्ट सहेजें',
    'postFeaturedImage'     => 'फीचर्ड छवि',
    'postFeaturedImagePlaceholder' => 'URL या अपलोड पथ…',
    'postCategories'        => 'श्रेणियाँ',
    'postTags'              => 'टैग',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'संशोधन',
    'postRevisionCount'     => '{0} संशोधन',
    'postPreview'           => 'पूर्वावलोकन',
    'postBulkAction'        => '- क्रिया चुनें -',
    'postBulkPublish'       => 'प्रकाशित करें',
    'postBulkUnpublish'     => 'अप्रकाशित करें (ड्राफ्ट में)',
    'postBulkDelete'        => 'हटाएं',

    // Post flash messages
    'postCreated'           => 'पोस्ट सफलतापूर्वक बनाई गई।',
    'postUpdated'           => 'पोस्ट अपडेट की गई।',
    'scheduledDateMustBeFuture' => 'निर्धारित तारीख भविष्य में होनी चाहिए।',
    'postDeleted'           => 'पोस्ट हटा दी गई।',
    'postBulkUpdated'       => '{0} पोस्ट अपडेट की गई।',
    'postBulkInvalid'       => 'अमान्य बल्क क्रिया।',
    'postPermission'        => 'आप केवल अपनी पोस्ट संपादित कर सकते हैं।',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'संशोधन: {0}',
    'revisionTitle'         => 'संशोधन — {0}',
    'revisionShowTitle'     => 'संशोधन',
    'revisionsBackToPost'   => 'पोस्ट पर वापस जाएं',
    'revisionsBackToList'   => 'संशोधन सूची पर वापस जाएं',
    'revisionRestored'      => 'पोस्ट {0} के संशोधन में पुनर्स्थापित की गई।',
    'revisionRestoreBtn'    => 'यह संशोधन पुनर्स्थापित करें',
    'revisionSaved'         => 'सहेजा गया',
    'revisionBy'            => 'द्वारा',
    'revisionOn'            => 'पर',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'पृष्ठ',
    'newPageTitle'          => 'नया पृष्ठ',
    'editPageTitle'         => 'पृष्ठ संपादित करें',
    'pageSlugInUse'         => "स्लग '{0}' पहले से उपयोग में है।",
    'pageCannotDelete'      => 'यह पृष्ठ हटाया नहीं जा सकता।',
    'slugAutoGenHint'       => 'यदि खाली छोड़ा तो शीर्षक से स्वचालित बनता है',
    'slugCannotChange'      => 'बदला नहीं जा सकता',
    'colSystem'             => 'सिस्टम',
    'system'                => 'सिस्टम',

    // Page flash messages
    'pageCreated'           => 'पृष्ठ बनाया गया।',
    'pageUpdated'           => 'पृष्ठ अपडेट किया गया।',
    'pageDeleted'           => 'पृष्ठ हटाया गया।',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'श्रेणियाँ',
    'newCategoryTitle'      => 'नई श्रेणी',
    'editCategoryTitle'     => 'श्रेणी संपादित करें',
    'categoryName'          => 'नाम',
    'categoryDescription'   => 'विवरण',
    'categoryPostCount'     => 'पोस्ट गिनती',

    // Category flash messages
    'categoryCreated'       => 'श्रेणी बनाई गई।',
    'categoryUpdated'       => 'श्रेणी अपडेट की गई।',
    'categoryDeleted'       => 'श्रेणी हटाई गई।',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'टैग',
    'tagPostCount'          => 'पोस्ट गिनती',

    // Tag flash messages
    'tagDeleted'            => 'टैग हटाया गया।',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'टिप्पणियाँ',
    'commentAuthor'         => 'लेखक',
    'commentContent'        => 'टिप्पणी',
    'commentPost'           => 'पोस्ट',
    'commentDate'           => 'तारीख',
    'commentStatusFilter'   => 'स्थिति के अनुसार फ़िल्टर करें',

    // Comment flash messages
    'commentApproved'       => 'टिप्पणी अनुमोदित।',
    'commentSpam'           => 'स्पैम के रूप में चिह्नित।',
    'commentTrashed'        => 'टिप्पणी ट्रैश की गई।',
    'commentDeleted'        => 'टिप्पणी स्थायी रूप से हटाई गई।',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'मीडिया लाइब्रेरी',
    'mediaTitle'            => 'शीर्षक',
    'mediaAltText'          => 'Alt टेक्स्ट',
    'mediaAltPlaceholder'   => 'पहुँच के लिए छवि का वर्णन करें',
    'mediaTitlePlaceholder' => 'वैकल्पिक छवि शीर्षक',
    'mediaImageDetails'     => 'छवि विवरण',
    'mediaSaved'            => 'सहेजा गया!',
    'mediaNoSelection'      => 'कोई छवि चुनी नहीं गई',
    'mediaBrowse'           => 'मीडिया ब्राउज़ करें',
    'mediaRemove'           => 'हटाएं',
    'mediaUseImage'         => 'यह छवि उपयोग करें',
    'mediaDropzone'         => 'छवि यहाँ खींचें और छोड़ें या ब्राउज़ करने के लिए क्लिक करें',
    'mediaLoading'          => 'मीडिया लोड हो रहा है…',
    'mediaEmpty'            => 'अभी तक कोई मीडिया अपलोड नहीं हुआ।',
    'mediaUpload'           => 'मीडिया अपलोड करें',
    'mediaDragDrop'         => 'फ़ाइलें यहाँ खींचें और छोड़ें, या',
    'mediaChooseFiles'      => 'फ़ाइलें चुनें',
    'mediaUploading'        => 'अपलोड हो रहा है…',
    'mediaFilename'         => 'फ़ाइल नाम',
    'mediaSize'             => 'आकार',
    'mediaUploadFailed'     => 'अपलोड विफल: {0}',
    'mediaUploadError'      => 'अपलोड त्रुटि: {0}',

    // Media flash messages
    'mediaDeleted'          => 'मीडिया हटाया गया।',
    'mediaNoValidFile'      => 'कोई वैध फ़ाइल अपलोड नहीं हुई।',
    'mediaUploadSuccess'    => 'फ़ाइल सफलतापूर्वक अपलोड हुई।',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'नेविगेशन',
    'navQuickAdd'           => 'त्वरित जोड़ें',
    'navQuickAddPlaceholder' => 'पृष्ठ, श्रेणियाँ, प्लगइन खोजें...',
    'navItemLabel'          => 'लेबल',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'लक्ष्य',
    'navItemOrder'          => 'क्रम',
    'navGroupPrimary'       => 'प्राथमिक',
    'navGroupFooter'        => 'फ़ुटर',
    'navSelectGroup'        => 'नेविगेशन समूह चुनें:',
    'navParent'             => 'मूल',
    'navTopLevel'           => '— शीर्ष स्तर —',
    'navSameWindow'         => 'उसी विंडो में',
    'navNewWindow'          => 'नई विंडो में',
    'navMenuItems'          => 'मेनू आइटम',
    'navNoItems'            => 'इस मेनू में कोई आइटम नहीं।',
    'dragToReorder'         => 'पुनः क्रमित करने के लिए खींचें',

    // Navigation flash messages
    'navItemAdded'          => 'नेव आइटम जोड़ा गया।',
    'navItemRemoved'        => 'नेव आइटम हटाया गया।',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'थीम',
    'themeOptions'          => 'थीम विकल्प',
    'themeActivate'         => 'सक्रिय करें',
    'themeOptionsBtn'       => 'विकल्प',
    'themeActive'           => 'सक्रिय',
    'themeBy'               => 'द्वारा',
    'themeSupport'          => 'सहायता',
    'themeVersion'          => 'संस्करण',
    'themeSaveOptions'      => 'विकल्प सहेजें',
    'themeInvalidLicense'   => 'थीम सक्रिय नहीं की जा सकती - लाइसेंस अमान्य है। पुनः इंस्टॉल करें या सहायता से संपर्क करें।',
    'themeValidationFailed' => 'थीम में PHP कोड है और इसे सक्रिय नहीं किया जा सकता।',
    'noThemesInstalled'     => 'कोई थीम स्थापित नहीं। थीम प्राप्त करने के लिए मार्केटप्लेस जाएं।',
    'themeUnapprovedTitle'  => 'अस्वीकृत थीम सक्रिय करें?',
    'themeNotApproved'      => 'इस थीम को Pubvana द्वारा अनुमोदित नहीं किया गया है।',
    'themeUnapprovedRisk'   => 'अस्वीकृत थीम सक्रिय करने से सुरक्षा जोखिम या संगतता समस्याएं हो सकती हैं।',
    'themeActivateConfirm'  => 'क्या आप वाकई इसे फिर भी सक्रिय करना चाहते हैं?',
    'themeActivateAnyway'   => 'फिर भी सक्रिय करें',
    'themeNoOptions'        => 'इस थीम में कोई कॉन्फ़िगरेशन विकल्प नहीं है।',
    'themeCustomize'        => 'थीम कस्टमाइज़ करें',

    // Theme flash messages
    'themeActivated'        => 'थीम सक्रिय की गई।',
    'themeOptionsSaved'     => 'विकल्प सहेजे गए।',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'लाइसेंस प्राप्त',
    'licenseCheckNow'        => 'अभी जाँचें',
    'licenseExpired'         => 'समाप्त',
    'licenseEnterKey'        => 'कुंजी दर्ज करें',
    'licenseChangeKey'       => 'बदलें',
    'licenseRenew'           => 'नवीनीकरण',
    'licenseThirdParty'      => 'तृतीय पक्ष',
    'unchecked'              => 'अनजाँचा',
    'safetyLabel'            => 'सुरक्षा:',
    'recheckBtn'             => 'पुनः जाँच',
    'recheckSuccess'         => 'सुरक्षा जाँच अपडेट की गई।',
    'recheckFailed'          => 'सत्यापन सर्वर से संपर्क नहीं हो सका। कृपया बाद में पुनः प्रयास करें।',
    'recheckNotFound'        => 'आइटम नहीं मिला।',
    'widgetBlockedMalicious' => '{0} को दुर्भावनापूर्ण के रूप में चिह्नित किया गया है और इसे जोड़ा नहीं जा सकता।',
    'licenseNoStoreProduct'  => 'यह आइटम किसी स्टोर उत्पाद से लिंक नहीं है। यदि आपने यह आइटम खरीदा है, तो कृपया लाइसेंसिंग सक्षम करने के लिए इसे मार्केटप्लेस से पुनः इंस्टॉल करें।',
    'securityWarning'        => 'सुरक्षा चेतावनी:',
    'licenseModalTitle'      => 'लाइसेंस कुंजी दर्ज करें',
    'licenseModalBody'       => 'अपनी लाइसेंस कुंजी नीचे पेस्ट करें।',
    'licenseModalSave'       => 'सहेजें',
    'licenseSaved'           => 'लाइसेंस कुंजी सहेजी और सत्यापित की गई।',
    'licenseInvalid'         => 'लाइसेंस कुंजी मान्य नहीं है।',
    'licenseKeyRequired'     => 'लाइसेंस कुंजी और उत्पाद आवश्यक हैं।',
    'licenseCheckFailed'     => 'लाइसेंस सर्वर तक नहीं पहुँच सका। कृपया बाद में पुनः प्रयास करें।',
    'licenseProductNotFound' => 'स्टोर में यह आइटम नहीं मिल सका।',
    'btnCancel'              => 'रद्द करें',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'विजेट',
    'widgetConfigureTitle'  => 'विजेट कॉन्फ़िगर करें',
    'widgetAreas'           => 'विजेट क्षेत्र',
    'widgetAvailable'       => 'उपलब्ध विजेट',
    'widgetAddToArea'       => 'क्षेत्र में जोड़ें',
    'widgetArea'            => 'क्षेत्र',
    'widgetNoOptions'       => 'कोई विकल्प नहीं।',
    'widgetSaveConfig'      => 'कॉन्फ़िगरेशन सहेजें',
    'widgetConfigure'       => 'कॉन्फ़िगर करें',
    'widgetNoAreas'         => 'कोई विजेट क्षेत्र नहीं मिला। विजेट क्षेत्र सक्षम करने के लिए थीम सक्रिय करें।',
    'widgetAreaEmpty'       => 'इस क्षेत्र में कोई विजेट नहीं। सूची से एक जोड़ें →',

    // Widget flash messages
    'widgetAdded'           => 'विजेट जोड़ा गया।',
    'widgetRemoved'         => 'विजेट हटाया गया।',
    'widgetConfigured'      => 'विजेट कॉन्फ़िगर किया गया।',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'मार्केटप्लेस',
    'marketplaceRefresh'    => 'रीफ्रेश करें',
    'marketplaceVisitStore' => 'स्टोर जाएं',
    'marketplaceAll'        => 'सभी',
    'marketplaceThemes'     => 'थीम',
    'marketplaceWidgets'    => 'विजेट',
    'marketplacePlugins'    => 'प्लगइन',
    'marketplaceUpdatesAvailable' => '{0} अपडेट उपलब्ध।',
    'marketplaceBy'         => 'द्वारा',
    'marketplaceFree'       => 'मुफ्त',
    'marketplaceInstalled'  => 'स्थापित',
    'marketplaceInstall'    => 'इंस्टॉल करें',
    'marketplaceBuyNow'     => 'अभी खरीदें',
    'marketplaceNoItems'    => 'मार्केटप्लेस में कोई आइटम नहीं मिला।',
    'marketplaceInstalledVersion' => 'v{0} स्थापित',
    'marketplaceLoadError'  => 'स्टोर से उत्पाद लोड नहीं हो सके। कृपया बाद में देखें।',
    'byAuthor'              => '{0} द्वारा',
    'unknown'               => 'अज्ञात',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} सफलतापूर्वक स्थापित।',
    'marketplaceInstallFail'    => 'इंस्टॉलेशन विफल। लॉग जाँचें।',
    'marketplaceUpdateSuccess'  => 'सफलतापूर्वक अपडेट किया गया।',
    'marketplaceUpdateFail'     => 'अपडेट विफल।',
    'marketplaceCacheRefreshed' => 'मार्केटप्लेस कैश रीफ्रेश हुआ।',
    'marketplaceInvalidRequest' => 'अमान्य इंस्टॉल अनुरोध।',
    'marketplaceCannotUpdate'   => 'यह आइटम अपडेट नहीं किया जा सकता।',

    // =========================================================================
    // Licenses
    // =========================================================================

    'licensesTitle'               => 'लाइसेंस',
    'licensesNone'                => 'कोई लाइसेंस नहीं',
    'licensesProduct'             => 'उत्पाद',
    'licensesKey'                 => 'लाइसेंस कुंजी',
    'licensesStatus'              => 'स्थिति',
    'licensesType'                => 'प्रकार',
    'licensesExpires'             => 'समाप्ति',
    'licensesDomain'              => 'डोमेन',
    'licensesInstalled'           => 'स्थापित',
    'licensesLastChecked'         => 'अंतिम जाँच',
    'licensesActions'             => 'क्रियाएँ',
    'licensesStatusValid'         => 'मान्य',
    'licensesStatusInvalid'       => 'अमान्य',
    'licensesStatusExpired'       => 'समाप्त',
    'licensesStatusSubExpired'    => 'सदस्यता समाप्त',
    'licensesStatusUnchecked'     => 'अनजाँचा',
    'licensesSubscription'        => 'सदस्यता',
    'licensesOneTime'             => 'एकमुश्त',
    'licensesPerpetual'           => 'स्थायी',
    'licensesNotInstalled'        => 'स्थापित नहीं',
    'licensesNever'               => 'कभी नहीं',
    'licensesRevalidate'          => 'पुनः सत्यापित करें',
    'licenseKeyPlaceholder'       => 'लाइसेंस कुंजी दर्ज करें...',
    'marketplaceLicensesEmpty'    => 'इंस्टॉलेशन के बाद लाइसेंस प्राप्त उत्पाद यहाँ दिखाई देंगे।',
    'typeTheme'                   => 'थीम',
    'typeWidget'                  => 'विजेट',
    'typePlugin'                  => 'प्लगइन',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'लाइसेंस सफलतापूर्वक सत्यापित।',
    'licenseRevalidateInvalid'     => 'लाइसेंस अमान्य या समाप्त है।',
    'licenseRevalidateUnreachable' => 'लाइसेंस सर्वर तक नहीं पहुँच सका। कृपया बाद में पुनः प्रयास करें।',
    'licenseRevalidateSkipped'     => 'लाइसेंस जाँच छोड़ी गई (dev मोड)।',
    'licenseRevalidateNotFound'    => 'लाइसेंस नहीं मिला।',

    // License warning banners
    'licenseWarningTitle'   => 'लाइसेंस समस्याएं',
    'licenseWarningInvalid' => 'लाइसेंस अमान्य या समाप्त है',
    'licenseWarningManage'  => 'लाइसेंस प्रबंधित करें',

    // Plugin license
    'pluginInvalidLicense' => 'इस प्लगइन का लाइसेंस अमान्य या समाप्त है और इसे सक्रिय नहीं किया जा सकता।',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'लाइसेंस कुंजी',
    'storeBrowseFull'       => 'पूरा स्टोर ब्राउज़ करें',
    'storeBackToMarketplace'=> 'मार्केटप्लेस पर वापस',
    'storeNoProducts'       => 'कोई उत्पाद उपलब्ध नहीं।',
    'storeViewInStore'      => 'स्टोर में देखें',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'उपयोगकर्ता',
    'editUserTitle'         => 'उपयोगकर्ता संपादित करें',
    'createUserTitle'       => 'उपयोगकर्ता बनाएं',
    'authorProfileTitle'    => 'लेखक प्रोफ़ाइल',
    'userRoleLabel'         => 'भूमिका',
    'userActiveLabel'       => 'सक्रिय',
    'userPasswordLabel'     => 'पासवर्ड',
    'userPasswordOptional'  => 'वर्तमान पासवर्ड रखने के लिए खाली छोड़ें',
    'userDisplayName'       => 'प्रदर्शन नाम',
    'userBio'               => 'बायो',
    'userWebsite'           => 'वेबसाइट',
    'userTwitter'           => 'Twitter / X हैंडल',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'अवतार',
    'userSaveProfile'       => 'प्रोफ़ाइल सहेजें',
    'userSaveChanges'       => 'परिवर्तन सहेजें',
    'userCannotDeleteSelf'  => 'स्वयं को हटाया नहीं जा सकता।',
    'userCannotDeleteOwner' => 'साइट स्वामी खाता हटाया नहीं जा सकता।',
    'userOwnerCannotModify' => 'साइट स्वामी खाता संशोधित नहीं किया जा सकता।',

    // User flash messages
    'userCreated'           => 'उपयोगकर्ता बनाया गया।',
    'userUpdated'           => 'उपयोगकर्ता अपडेट किया गया।',
    'userDeleted'           => 'उपयोगकर्ता हटाया गया।',
    'userBanned'            => 'उपयोगकर्ता प्रतिबंधित किया गया।',
    'userUnbanned'          => 'उपयोगकर्ता का प्रतिबंध हटाया गया।',
    'userCannotBanSelf'     => 'आप स्वयं को या साइट स्वामी को प्रतिबंधित नहीं कर सकते।',
    'banStatus'             => 'प्रतिबंध स्थिति',
    'banned'                => 'प्रतिबंधित',
    'ban'                   => 'उपयोगकर्ता प्रतिबंधित करें',
    'unban'                 => 'प्रतिबंध हटाएं',
    'banReasonRequired'     => 'प्रतिबंध का कारण आवश्यक है।',
    'banReasonPlaceholder'  => 'प्रतिबंध का कारण...',
    'confirmBanUser'        => 'क्या आप वाकई इस उपयोगकर्ता को प्रतिबंधित करना चाहते हैं?',
    'userProfileSaved'      => 'प्रोफ़ाइल सहेजी गई।',
    'userAvatarUploadFail'  => 'अवतार अपलोड विफल: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA सेटअप',
    'tfaSetupHeading'       => 'दो-कारक प्रमाणीकरण सेट करें',
    'tfaScanQr'             => 'अपने प्रमाणक ऐप से नीचे QR कोड स्कैन करें (जैसे Google Authenticator, Authy)।',
    'tfaManualEntry'        => 'या मैन्युअल रूप से गुप्त कुंजी दर्ज करें:',
    'tfaEnterCode'          => 'पुष्टि करने के लिए अपने ऐप से 6-अंकीय कोड दर्ज करें:',
    'tfaCodeLabel'          => 'प्रमाणीकरण कोड',
    'tfaConfirmBtn'         => 'पुष्टि करें और 2FA सक्षम करें',
    'tfaDisableBtn'         => '2FA अक्षम करें',
    'tfaDisableConfirm'     => 'अक्षम करने के लिए अपना वर्तमान 2FA कोड दर्ज करें:',
    'tfaEnabled'            => 'दो-कारक प्रमाणीकरण सक्षम।',
    'tfaDisabled'           => 'दो-कारक प्रमाणीकरण अक्षम।',
    'tfaInvalidCode'        => 'अमान्य कोड - कृपया QR कोड स्कैन करें और पुनः प्रयास करें।',
    'tfaInvalidDisable'     => 'अमान्य कोड - 2FA अक्षम नहीं हुआ।',
    'tfaSessionExpired'     => 'सेटअप सत्र समाप्त - कृपया पुनः शुरू करें।',
    'tfaNotEnabled'         => '2FA वर्तमान में सक्षम नहीं है।',
    'tfaCantScan'           => "स्कैन नहीं कर सकते? यह कोड मैन्युअल रूप से दर्ज करें:",
    'tfaWarning'            => 'इस गुप्त कुंजी को सुरक्षित स्थान पर संग्रहीत करें। यदि आप अपना प्रमाणक डिवाइस खो देते हैं तो आपको इसकी आवश्यकता होगी।',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'सोशल लिंक',
    'socialPlatform'           => 'प्लेटफ़ॉर्म',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'आइकन',
    'socialSortOrder'          => 'क्रम',
    'socialIconPackInfo'       => 'वर्तमान थीम <strong>{0}</strong> आइकन के लिए <strong>{1}</strong> (v{2}) उपयोग करती है। नीचे आप उपलब्ध आइकन चुन सकते हैं जो इस साइट की सोशल लिंक सुविधा के लिए प्रदर्शित होंगे।',
    'socialSearchPlaceholder'  => 'प्लेटफ़ॉर्म खोजें...',
    'socialIconDisclaimer'     => "ये आइकन उपयोग किए जाने वाले आइकन का प्रतिनिधित्व मात्र हैं। वास्तविक आइकन सक्रिय थीम के आइकन पैक के आधार पर भिन्न हो सकता है।",

    // Social flash messages
    'socialLinkAdded'       => 'सोशल लिंक जोड़ा गया।',
    'socialLinkUpdated'     => 'लिंक अपडेट किया गया।',
    'socialLinkDeleted'     => 'लिंक हटाया गया।',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'रीडायरेक्ट',
    'redirectFrom'          => 'URL से',
    'redirectTo'            => 'URL तक',
    'redirectType'          => 'प्रकार',
    'redirectAdd'           => 'रीडायरेक्ट जोड़ें',
    'redirectFromHint'      => '(सापेक्ष, जैसे /old-page)',
    'redirect301'           => '301 स्थायी',
    'redirect302'           => '302 अस्थायी',
    'redirectInvalidDest'   => 'अमान्य रीडायरेक्ट गंतव्य URL।',

    // Redirect flash messages
    'redirectAdded'         => 'रीडायरेक्ट जोड़ा गया।',
    'redirectDeleted'       => 'रीडायरेक्ट हटाया गया।',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'सेटिंग्स',
    'settingsGeneral'       => 'सामान्य',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'ईमेल',
    'settingsSocialLogin'   => 'सोशल लॉगिन',
    'settingsSocialSharing' => 'सोशल शेयरिंग',
    'settingsSpam'          => 'स्पैम सुरक्षा',

    'generalSettingsHeading'    => 'सामान्य सेटिंग्स',
    'generalSiteName'           => 'साइट का नाम',
    'generalTagline'            => 'टैगलाइन',
    'generalAdminEmail'         => 'एडमिन ईमेल',
    'generalPostsPerPage'       => 'प्रति पृष्ठ पोस्ट',
    'generalComments'           => 'टिप्पणियाँ',
    'generalCommentsEnable'     => 'टिप्पणियाँ सक्षम करें',
    'generalCommentModeration'  => 'प्रकाशन से पहले मॉडरेशन आवश्यक',
    'generalMaintenanceMode'    => 'रखरखाव मोड',
    'generalMaintenanceEnable'  => 'रखरखाव मोड सक्षम करें',
    'generalMaintenanceHelp'    => "आगंतुक \"हम जल्द वापस आएंगे\" पृष्ठ देखते हैं। एडमिन अभी भी साइट एक्सेस कर सकते हैं।",
    'generalFrontPage'          => 'फ्रंट पेज',
    'generalFrontPageBlog'      => 'ब्लॉग इंडेक्स (नवीनतम पोस्ट)',
    'generalFrontPageStatic'    => 'स्थिर पृष्ठ:',
    'generalFrontPagePlugin'    => 'प्लगइन पृष्ठ:',
    'generalSelectPage'         => '- पृष्ठ चुनें -',
    'generalSelectRoute'        => '- रूट चुनें -',
    'generalFrontPageNoPlugins' => 'कोई प्लगइन रूट उपलब्ध नहीं',
    'generalPageCacheTtl'       => 'पेज कैश TTL',
    'settingsCacheTtlHint'      => 'सेकंड। 0 = अक्षम।',
    'generalSaveBtn'            => 'सामान्य सेटिंग्स सहेजें',

    // General flash messages
    'generalSettingsSaved'      => 'सामान्य सेटिंग्स सहेजी गईं।',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO सेटिंग्स',
    'seoMetaDescription'        => 'मेटा विवरण',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'साइटमैप',
    'seoSitemapEnable'          => 'sitemap.xml सक्षम करें',
    'seoSitemapHelp'            => 'सभी प्रकाशित पोस्ट और पृष्ठों के लिए मानक साइटमैप।',
    'seoNewsSitemap'            => 'news-sitemap.xml सक्षम करें',
    'seoNewsSitemapHelp'        => 'Google News साइटमैप - पिछले 48 घंटों में प्रकाशित पोस्ट सूचीबद्ध करता है।',
    'seoSaveBtn'                => 'SEO सेटिंग्स सहेजें',
    'seoSettingsSaved'          => 'SEO सेटिंग्स सहेजी गईं।',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'ईमेल सेटिंग्स',
    'emailFromName'             => 'प्रेषक नाम',
    'emailFromAddress'          => 'प्रेषक पता',
    'emailProtocol'             => 'प्रोटोकॉल',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP होस्ट',
    'emailSmtpPort'             => 'SMTP पोर्ट',
    'emailSmtpEncryption'       => 'एन्क्रिप्शन',
    'emailSmtpEncryptionNone'   => 'कोई नहीं',
    'emailSmtpUsername'         => 'SMTP उपयोगकर्ता नाम',
    'emailSmtpPassword'         => 'SMTP पासवर्ड',
    'emailSaveBtn'              => 'ईमेल सेटिंग्स सहेजें',
    'emailSettingsSaved'        => 'ईमेल सेटिंग्स सहेजी गईं।',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'सोशल लॉगिन (OAuth)',
    'socialLoginHelp'           => 'क्रेडेंशियल आपकी .env फ़ाइल में सहेजे जाते हैं। क्लाइंट ID और सीक्रेट प्राप्त करने के लिए Google और Facebook पर अपना ऐप रजिस्टर करें।',
    'socialLoginGoogleId'       => 'Client ID',
    'socialLoginGoogleSecret'   => 'Client Secret',
    'socialLoginFbAppId'        => 'App ID',
    'socialLoginFbAppSecret'    => 'App Secret',
    'socialLoginPlaceholderSecret' => '(मौजूदा रखने के लिए खाली छोड़ें)',
    'socialLoginSaveBtn'        => 'सोशल लॉगिन सेटिंग्स सहेजें',
    'socialLoginSettingsSaved'  => 'सोशल लॉगिन सेटिंग्स सहेजी गईं।',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'प्रकाशन पर सोशल ऑटो-शेयर',
    'socialSharingHelp'         => 'जब "प्रकाशन पर शेयर करें" चेक के साथ पोस्ट प्रकाशित होती है, Pubvana स्वचालित रूप से कॉन्फ़िगर किए गए सोशल खातों पर पोस्ट करेगा।',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'developer.twitter.com → आपका ऐप → Keys and Tokens पर कुंजियाँ प्राप्त करें।',
    'socialSharingApiKey'       => 'API Key',
    'socialSharingApiSecret'    => 'API Secret',
    'socialSharingAccessToken'  => 'Access Token',
    'socialSharingAccessSecret' => 'Access Secret',
    'socialSharingFbPage'       => 'Facebook Page',
    'socialSharingFbPageHelp'   => 'pages_manage_posts अनुमति के साथ Page Access Token आवश्यक है।',
    'socialSharingFbPageId'     => 'Page ID',
    'socialSharingFbPageToken'  => 'Page Access Token',
    'socialSharingSaveBtn'      => 'शेयरिंग सेटिंग्स सहेजें',
    'socialSharingSettingsSaved'=> 'सोशल शेयरिंग सेटिंग्स सहेजी गईं।',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'स्पैम सुरक्षा (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana स्पैम बॉट से टिप्पणी फ़ॉर्म और संपर्क फ़ॉर्म की सुरक्षा के लिए hCaptcha (गोपनीयता-सम्मानजनक, गैर-Google) उपयोग करता है।',
    'spamHcaptchaFree'          => 'अधिकांश साइटों के लिए hCaptcha मुफ्त है। hcaptcha.com पर साइन अप करें, फिर: अपनी साइट कुंजी पाने के लिए Account → Sites → Add Site और अपनी गुप्त कुंजी पाने के लिए Account → Settings → Secret Key → Generate का अनुसरण करें। दोनों नीचे दर्ज करें।',
    'spamHcaptchaSiteKey'       => 'Site Key',
    'spamHcaptchaSecretKey'     => 'Secret Key',
    'spamHcaptchaNote'          => 'यदि ये कुंजियाँ सेट नहीं हैं, hCaptcha चुपचाप छोड़ा जाता है — स्थानीय विकास के लिए सुरक्षित। एक बार सहेजने के बाद, विजेट स्वचालित रूप से टिप्पणी फ़ॉर्म और संपर्क पृष्ठ पर दिखाई देता है।',
    'spamSettingsSaved'         => 'स्पैम सुरक्षा सेटिंग्स सहेजी गईं।',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'भाषाएँ',
    'languageCode'              => 'कोड',
    'languageName'              => 'नाम',
    'languageDefault'           => 'डिफ़ॉल्ट',
    'languageEnabled'           => 'सक्षम',
    'languageMakeDefault'       => 'डिफ़ॉल्ट बनाएं',
    'languageSetAsDefault'      => '{0} डिफ़ॉल्ट भाषा के रूप में सेट।',
    'languageEnabled_msg'       => '{0} सक्षम।',
    'languageDisabled_msg'      => '{0} अक्षम।',
    'languageNotFound'          => 'भाषा नहीं मिली।',
    'languageCannotDisable'     => 'डिफ़ॉल्ट भाषा अक्षम नहीं की जा सकती।',
    'languageDirection'         => 'दिशा',
    'languageNativeName'        => 'मूल नाम',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'विश्लेषण',
    'analyticsTotalViews'       => 'कुल दृश्य',
    'analyticsTopPosts'         => 'शीर्ष पोस्ट',
    'analyticsReferrers'        => 'शीर्ष रेफरर',
    'analyticsLast7'            => 'अंतिम 7 दिन',
    'analyticsLast30'           => 'अंतिम 30 दिन',
    'analyticsLast90'           => 'अंतिम 90 दिन',
    'analyticsChartTitle'       => 'पृष्ठ दृश्य',
    'analyticsNoData'           => 'इस अवधि के लिए कोई विश्लेषण डेटा नहीं।',
    'analyticsDomain'           => 'डोमेन',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'एफिलिएट लिंक',
    'newAffiliateLinkTitle'     => 'नया एफिलिएट लिंक',
    'editAffiliateLinkTitle'    => 'एफिलिएट लिंक संपादित करें',
    'affiliateName'             => 'नाम',
    'affiliateSlug'             => 'स्लग',
    'affiliateDestination'      => 'गंतव्य URL',
    'affiliateActive'           => 'सक्रिय',
    'affiliateClicks'           => 'क्लिक',
    'affiliateClicksTitle'      => 'क्लिक - {0}',
    'affiliateTotal'            => 'कुल',
    'affiliateViewClicks'       => 'क्लिक देखें',

    // Affiliate flash messages
    'affiliateCreated'          => 'एफिलिएट लिंक बनाया गया।',
    'affiliateUpdated'          => 'एफिलिएट लिंक अपडेट किया गया।',
    'affiliateDeleted'          => 'एफिलिएट लिंक हटाया गया।',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'टूटे हुए लिंक',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP स्थिति',
    'brokenLinkError'           => 'त्रुटि',
    'brokenLinkSource'          => 'स्रोत',
    'brokenLinkShowDismissed'   => 'खारिज दिखाएं',
    'brokenLinkHideDismissed'   => 'खारिज छिपाएं',
    'brokenLinkTimeout'         => 'टाइमआउट',
    'brokenLinkBroken'          => 'टूटा हुआ',
    'brokenLinkNone'            => 'कोई टूटा हुआ लिंक नहीं मिला।',
    'brokenLinkNowReachable'    => 'लिंक अब पहुँचने योग्य है - परिणामों से हटाया गया।',
    'brokenLinkStillBroken'     => 'लिंक अभी भी टूटा है ({0})।',
    'brokenLinkDismissed'       => 'लिंक खारिज किया गया।',
    'brokenLinksCliHint'        => 'इस रिपोर्ट को भरने के लिए कमांड लाइन से पूर्ण स्कैन चलाएं: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} समस्या(एं) मिलीं',
    'brokenLinksCount'          => '{0} टूटे',
    'brokenLinksRecheck'        => 'इस URL को पुनः जाँचें',
    'brokenLinksDismiss'        => 'खारिज करें (परिणामों से छिपाएं)',
    'brokenLinksRunScan'        => 'स्कैन चलाएं',
    'brokenLinksScanComplete'   => 'स्कैन पूर्ण: {0} लिंक जाँचे, {1} टूटे।',
    'timeout'                   => 'टाइमआउट',
    'typePost'                  => 'पोस्ट',
    'typePage'                  => 'पृष्ठ',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'गतिविधि लॉग',
    'activityLogType'           => 'प्रकार',
    'activityLogAction'         => 'क्रिया',
    'activityLogUser'           => 'उपयोगकर्ता',
    'activityLogDate'           => 'तारीख',
    'activityLogNote'           => 'नोट',
    'activityLogFilterAll'      => 'सभी प्रकार',
    'activityLogEmpty'          => 'अभी तक कोई गतिविधि दर्ज नहीं।',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'बैकअप और निर्यात',
    'backupDownload'            => 'बैकअप बनाएं और डाउनलोड करें',
    'backupFiles'               => 'उपलब्ध बैकअप',
    'backupFilename'            => 'फ़ाइल नाम',
    'backupSize'                => 'आकार',
    'backupDate'                => 'बनाया',
    'backupGenerating'          => 'बैकअप बन रहा है…',
    'backupNoFiles'             => 'कोई सहेजा हुआ बैकअप नहीं।',
    'backupFailed'              => 'बैकअप विफल: {0}',
    'backupDeleted'             => 'बैकअप हटाया गया।',
    'backupCannotDelete'        => 'बैकअप हटाया नहीं जा सका।',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP SHA-256 हैश के रूप में संग्रहीत हैं — कोई कच्चा PII रिकॉर्ड नहीं।',
    'colTime'                   => 'समय',
    'colIpHash'                 => 'IP हैश',
    'colReferrer'               => 'रेफरर',
    'affiliateDirectReferrer'   => 'डायरेक्ट',
    'affiliateNameHint'         => 'आंतरिक लेबल — आगंतुकों को नहीं दिखाया जाता।',
    'affiliateSlugHint'         => 'केवल अक्षर, संख्या, हाइफन और अंडरस्कोर। एक बार लिंक साझा होने पर बदला नहीं जा सकता।',
    'affiliateDestHint'         => 'https:// शामिल होना चाहिए। आगंतुकों को 301-रीडायरेक्ट किया जाएगा।',
    'affiliateInactiveHint'     => 'निष्क्रिय लिंक 404 लौटाते हैं।',
    'affiliateLinkCount'        => '{0} लिंक',
    'colDomain'                 => 'डोमेन',
    'commentAll'                => 'सभी',
    'commentPending'            => 'लंबित',
    'commentTrash'              => 'ट्रैश',
    'commentsNone'              => 'कोई {0} टिप्पणी नहीं।',

    'backupCreate'              => 'बैकअप बनाएं',
    'backupStarting'            => 'बैकअप शुरू हो रहा है...',
    'backupNoneYet'             => 'अभी तक कोई बैकअप नहीं। अपना पहला बनाने के लिए "बैकअप बनाएं" पर क्लिक करें।',
    'backupsTitle'              => 'बैकअप',
    'backupRetentionNote'       => 'अधिकतम 15 बैकअप रखे जाते हैं — सबसे पुराने स्वचालित रूप से हटाए जाते हैं।',
    'backupRestoreConfirm'      => 'यह बैकअप पुनर्स्थापित करें? पहले वर्तमान स्थिति का बैकअप बनाया जाएगा।',
    'backupDeleteConfirm'       => 'यह बैकअप हटाएं?',
    'colFilename'               => 'फ़ाइल नाम',
    'colVersion'                => 'संस्करण',
    'colTrigger'                => 'ट्रिगर',
    'colSize'                   => 'आकार',
    'colDate'                   => 'तारीख',
    'colActions'                => 'क्रियाएँ',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'आयात',
    'importWpHeading'           => 'WordPress से आयात करें',
    'importWpHelp'              => 'Tools → Export के माध्यम से अपनी WordPress साइट निर्यात करें, फिर नीचे .xml फ़ाइल अपलोड करें।',
    'importChooseFile'          => 'WXR फ़ाइल (.xml) चुनें',
    'importDryRun'              => 'ड्राई रन (केवल पूर्वावलोकन - कुछ सहेजा नहीं जाता)',
    'importRunBtn'              => 'आयात चलाएं',
    'importNoValidFile'         => 'कृपया एक वैध WordPress WXR निर्यात फ़ाइल अपलोड करें।',
    'importOnlyXml'             => 'केवल .xml फ़ाइलें स्वीकार की जाती हैं।',
    'importFileTooLarge'        => 'आयात फ़ाइल बहुत बड़ी है। अधिकतम आकार 50 MB है।',
    'importResultsHeading'      => 'आयात परिणाम',
    'importDryRunNote'          => 'ड्राई रन - कोई डेटा सहेजा नहीं गया।',
    'importDryRunLabel'         => '(ड्राई रन — कोई डेटा नहीं लिखा)',
    'importComplete'            => 'आयात पूर्ण',
    'importCreated'             => 'बनाया',
    'importSkipped'             => 'छोड़ा',
    'importErrors'              => 'त्रुटियाँ:',
    'importInstructions'        => 'अपनी WordPress सामग्री <strong>Tools → Export → All content</strong> से निर्यात करें और <code>.xml</code> फ़ाइल यहाँ अपलोड करें। Pubvana पोस्ट, पृष्ठ, श्रेणियाँ, टैग, लेखक और टिप्पणियाँ आयात करेगा।',
    'importCliTitle'            => 'CLI आयात',
    'importCliHint'             => 'आप कमांड लाइन से भी इम्पोर्टर चला सकते हैं:',
    'importCliDryRunHint'       => '<code>--dry-run</code> फ्लैग दिखाता है कि डेटाबेस में लिखे बिना क्या आयात किया जाएगा।',
    'importWhatTitle'           => 'क्या आयात होता है',
    'importItemPosts'           => 'पोस्ट (शीर्षक, सामग्री, संक्षेप, स्लग, स्थिति)',
    'importItemPages'           => 'पृष्ठ',
    'importItemCategories'      => 'श्रेणियाँ (पदानुक्रम सहित)',
    'importItemTags'            => 'टैग',
    'importItemAuthors'         => 'लेखक (सदस्य खातों के रूप में बनाए)',
    'importItemComments'        => 'टिप्पणियाँ',
    'importItemMedia'           => 'मीडिया फ़ाइलें (URL सामग्री में संरक्षित)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'अपडेट',
    'updatesCurrentVersion'     => 'वर्तमान संस्करण',
    'updatesLatestVersion'      => 'नवीनतम संस्करण',
    'updatesUpToDate'           => 'Pubvana अद्यतित है।',
    'updatesAvailable'          => 'अपडेट उपलब्ध: {0}',
    'updatesCheckBtn'           => 'अपडेट जाँचें',
    'updatesReleaseNotes'       => 'रिलीज़ नोट',
    'updatesHowToApply'         => 'अपडेट कैसे लागू करें',
    'updatesCacheCleared'       => 'अपडेट कैश साफ़ - अभी पुनः जाँच रहे हैं।',
    'updatesExtCapped'          => 'अपडेट उपलब्ध: {0} (addon-safe)',
    'updatesNewerAvailable'     => 'Pubvana {0} भी उपलब्ध है - इसे अनलॉक करने के लिए नीचे सूचीबद्ध addon अपडेट करें।',

    // Addon Updates
    'updatesExtTitle'               => 'Addon',
    'updatesExtCheckAll'            => 'सभी जाँचें',
    'updatesExtUpdateAll'           => 'सभी अपडेट करें',
    'updatesExtCheckAllType'        => 'सभी {0} जाँचें',
    'updatesExtUpdateAllType'       => 'सभी {0} अपडेट करें',
    'updatesExtNoInstalled'         => 'कोई {0} स्थापित नहीं।',
    'updatesExtColName'             => 'नाम',
    'updatesExtColVersion'          => 'संस्करण',
    'updatesExtColLatest'           => 'नवीनतम',
    'updatesExtColAutoUpdate'       => 'ऑटो-अपडेट',
    'updatesExtColStatus'           => 'स्थिति',
    'updatesExtColActions'          => 'क्रियाएँ',
    'updatesExtBundled'             => 'कोर बंडल',
    'updatesExtNoSource'            => 'कोई अपडेट स्रोत नहीं',
    'updatesExtFailed'              => 'विफल',
    'updatesExtUpdatedAt'           => '{0} को अपडेट किया',
    'updatesExtAvailable'           => 'अपडेट उपलब्ध',
    'updatesExtUpToDate'            => 'अद्यतित',
    'updatesExtUpdate'              => 'अपडेट',
    'updatesExtChecking'            => 'जाँच हो रही है...',
    'updatesExtUpdating'            => 'अपडेट हो रहा है...',
    'updatesExtUpdated'             => 'अपडेट किया',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'अपडेट की पुष्टि करें',
    'updatesConfirmBody'            => 'यह आपकी साइट का बैकअप लेगा, अपडेट डाउनलोड करेगा और लागू करेगा।',
    'updatesConfirmSafe'            => 'आपकी <code>.env</code>, <code>App.php</code>, और <code>Database.php</code> कभी ओवरराइट नहीं होती।',
    'updatesConfirmBtn'             => 'अभी अपडेट करें',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'सभी Addon अपडेट करें',
    'updatesExtAllBody'             => 'यह सभी addon अपडेट करेगा जिनके पास लंबित अपडेट हैं।',
    'updatesExtAllNote'             => 'ऑटो-अपडेट अक्षम addon भी अपडेट किए जाएंगे।',
    'updatesExtAllBtn'              => 'सभी अपडेट करें',

    'updatesExtBadge'               => 'अपडेट: v{0}',
    'updatesExtGoToUpdates'         => 'अपडेट',

    // Update Settings
    'updatesSettingsTitle'          => 'अपडेट सेटिंग्स',
    'updatesAutoUpdateLabel'        => 'Pubvana ऑटो-अपडेट',
    'updatesAutoUpdateManual'       => 'मैन्युअल',
    'updatesAutoUpdateAuto'         => 'स्वचालित',
    'updatesAutoUpdateHelp'         => 'सक्षम होने पर, Pubvana के ब्रेकिंग चेंज रहित अपडेट स्वचालित रूप से लागू होते हैं।',
    'updatesCheckMethodLabel'       => 'अपडेट जाँच विधि',
    'updatesCheckMethodPageload'    => 'पेज लोड',
    'updatesCheckMethodCron'        => 'Cron Job',
    'updatesCheckMethodHelp'        => 'पेज लोड हर अनुरोध पर जाँचता है (24h कैश)। Cron को सर्वर cron job चाहिए।',
    'updatesCronCommand'            => 'Cron कमांड',
    'updatesCronHelp'               => 'दैनिक अपडेट जाँच चलाने के लिए इसे अपने सर्वर के crontab में जोड़ें:',
    'updatesSettingsSaved'          => 'अपडेट सेटिंग्स सहेजी गईं।',

    // Compatibility
    'compatWarningTitle'            => 'संगतता चेतावनी',
    'compatNotCompatible'           => 'कुछ स्थापित addon इस संस्करण के साथ संगत नहीं हैं।',
    'compatRequiresUpdate'          => 'लेकिन पहले निम्न addon को अपडेट करने की आवश्यकता है:',
    'compatSupportsUpTo'            => '{0} तक समर्थन करता है',
    'compatRequiresMin'             => 'Pubvana {0}+ आवश्यक',
    'compatNotDeclared'             => 'निम्न addon ने Pubvana {0} के साथ संगतता घोषित नहीं की है। अपडेट के बाद ये काम करना बंद कर सकते हैं:',
    'compatColType'                 => 'प्रकार',
    'compatColName'                 => 'नाम',
    'compatColVersion'              => 'संगतता',
    'compatRemoveHint'              => 'समस्या होने पर असंगत addon हटाएं या डिफ़ॉल्ट थीम पर स्विच करें। हर अपडेट से पहले बैकअप बनाया जाता है।',
    'compatMaxVersion'              => 'अधिकतम संगत संस्करण: {0}',
    'compatMinVersion'              => 'Pubvana {0}+ आवश्यक',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'पोस्ट शेड्यूल',
    'scheduleNoScheduled'       => 'कोई निर्धारित पोस्ट नहीं।',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'संशोधन - {0}',
    'revisionPageTitle'         => 'संशोधन - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'एडमिन पैनल एक्सेस करने के लिए आपको लॉग इन होना चाहिए।',
    'dirNotWritable'            => 'डायरेक्टरी लिखने योग्य नहीं है: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} गलत तरीके से कॉन्फ़िगर किया गया है। यदि आप अंतिम उपयोगकर्ता हैं, तो डेवलपर से संपर्क करें। यदि आप डेवलपर हैं, तो दस्तावेज़ देखें।',
    'addonMisconfiguredLink'    => '{0} गलत तरीके से कॉन्फ़िगर किया गया है। यदि आप अंतिम उपयोगकर्ता हैं <a href="{1}">डेवलपर से संपर्क करें</a>। यदि आप डेवलपर हैं <a href="https://github.com/enlivenapp/pubvana">दस्तावेज़ देखें</a>।',
    'licenseExpiringSoon'       => '{0} का लाइसेंस {1} को समाप्त होता है। लाइसेंस समाप्त होने पर {0} निष्क्रिय हो जाएगा।',
    'licenseExpiredDeactivated' => '{0} को निष्क्रिय कर दिया गया है क्योंकि लाइसेंस समाप्त हो गया है।',
    'addonDeactivated'          => '{0} को निष्क्रिय कर दिया गया है। कारण: {1}।',
    'widgetValidationFailed'    => "विजेट ''{0}'' सत्यापित नहीं किया जा सका। डेवलपर से संपर्क करें या addon हटाएं।",
    'widgetValidationFailedLink' => "विजेट ''{0}'' सत्यापित नहीं किया जा सका। <a href=\"{1}\">डेवलपर से संपर्क करें</a> या addon हटाएं।",

    // Inline warnings on addon listing
    'addonDeactivatedExpired'   => 'निष्क्रिय: लाइसेंस समाप्त',
    'addonDeactivatedTampered'  => 'निष्क्रिय: गलत तरीके से कॉन्फ़िगर',
    'addonDeactivatedNoLicense' => 'निष्क्रिय: कोई वैध लाइसेंस नहीं',

    // Disabled addon reasons
    'addonDisabled'             => 'अक्षम',
    'addonDisabledInvalidJson'  => 'सिस्टम: {0} का {1} अमान्य या अपठनीय है।',
    'addonDisabledMissingFields' => 'सिस्टम: {0} में आवश्यक फ़ील्ड गायब हैं: {1}।',
    'addonDisabledPhpFiles'     => 'सिस्टम: {0} में PHP फ़ाइलें हैं। विजेट केवल JSON + टेम्पलेट होने चाहिए।',

    // Flash messages (on activation attempt)
    'licenseRequired'           => '{0} को सक्रिय करने के लिए एक वैध लाइसेंस आवश्यक है।',
    'licenseInvalidActivation'  => '{0} के लिए लाइसेंस सत्यापन विफल। कृपया अपनी लाइसेंस कुंजी जाँचें।',
    'licenseExpiredActivation'  => '{0} का लाइसेंस समाप्त हो गया है। सक्रिय करने के लिए नवीनीकरण करें।',
    'licenseCheckUnreachable'   => '{0} के लिए लाइसेंस सत्यापित नहीं किया जा सका। लाइसेंस सर्वर अनुपलब्ध है। कृपया बाद में पुनः प्रयास करें।',
    'activationBlockedTampered' => '{0} सक्रिय नहीं किया जा सकता क्योंकि यह गलत तरीके से कॉन्फ़िगर है।',
    'activationBlockedBundled'  => '{0} सक्रिय नहीं किया जा सकता: केवल Pubvana addon बंडल के रूप में चिह्नित किए जा सकते हैं।',
    'activationBlockedNoUrls'   => '{0} सक्रिय नहीं किया जा सकता: भुगतान किए addon में लाइसेंस सत्यापन URL शामिल होने चाहिए।',
    'activationBlockedFreeFlag' => '{0} सक्रिय नहीं किया जा सकता: Pubvana addon को मुफ्त के रूप में चिह्नित नहीं किया जा सकता।',
    'activationBlockedDisabled' => '{0} सक्रिय नहीं किया जा सकता क्योंकि इसमें कॉन्फ़िगरेशन त्रुटियाँ हैं। info फ़ाइल जाँचें।',

    // Third-party license
    'licenseThirdPartyLabel'    => '3rd पार्टी',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'अपडेट शुरू हो रहा है...',
    'updateCheckLabel'           => 'अपडेट जाँच:',
    'updateAvailable'            => 'Pubvana {0} उपलब्ध है!',
    'updateRunning'              => 'आप {0} चला रहे हैं।',
    'updateBreakingChanges'      => 'ब्रेकिंग चेंज',
    'updateMigrationNotes'       => 'माइग्रेशन नोट',
    'updateNotices'              => 'नोटिस',
    'updatePreflightTitle'       => 'प्री-फ्लाइट जाँच',
    'updateToVersion'            => 'Pubvana {0} में अपडेट करें',
    'updatePreflightFailed'      => 'एक या अधिक आवश्यक प्री-फ्लाइट जाँच विफल रहीं। अपडेट करने से पहले उन्हें हल करें।',
    'updateUpToDate'             => 'Pubvana अद्यतित है। आप संस्करण {0} चला रहे हैं।',
    'updateAnyway'               => 'फिर भी अपडेट करें',
    'updateAvailableTooltip'     => 'Pubvana {0} उपलब्ध',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(आप)',
    'usersNone'                  => 'कोई उपयोगकर्ता नहीं मिला।',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'खाता सक्रिय',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'प्रोफ़ाइल विवरण',
    'profileDisplayNameHint'     => 'उपयोगकर्ता नाम के बजाय प्रकाशित पोस्ट पर दिखाया जाता है।',
    'profileAvatarHint'          => 'JPEG, PNG, WebP या GIF। अधिकतम 10 MB।',
    'profileSocialHandles'       => 'सोशल हैंडल',
    'preview'                    => 'पूर्वावलोकन',
    'website'                    => 'वेबसाइट',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'दो-कारक प्रमाणीकरण',
    'totpActiveDesc'             => 'TOTP दो-कारक प्रमाणीकरण आपके खाते पर सक्रिय है। हर बार लॉग इन करते समय आपसे आपके प्रमाणक ऐप से 6-अंकीय कोड माँगा जाएगा।',
    'totpCurrentCode'            => 'वर्तमान कोड',
    'totpInactiveDesc'           => 'अपने खाते में सुरक्षा की एक अतिरिक्त परत जोड़ें। एक बार सक्षम होने पर, आपको हर लॉगिन पर अपने प्रमाणक ऐप से कोड दर्ज करना होगा।',
    'totpEnable'                 => 'दो-कारक प्रमाणीकरण सक्षम करें',
    'totpScanInstructions'       => 'अपना प्रमाणक ऐप (Google Authenticator, Authy, 1Password, आदि) खोलें और यह QR कोड स्कैन करें।',
    'totpManualEntry'            => "स्कैन नहीं कर सकते? यह कोड मैन्युअल रूप से दर्ज करें:",
    'totpConfirmInstructions'    => 'स्कैन करने के बाद, सेटअप की पुष्टि करने के लिए अपने ऐप में दिखाया गया 6-अंकीय कोड दर्ज करें।',
    'totpRecoveryWarning'        => 'अपने रिकवरी कोड संग्रहीत करें। यदि आप अपने प्रमाणक ऐप तक पहुँच खो देते हैं, तो आप लॉग इन नहीं कर पाएंगे। 2FA रीसेट करने के लिए साइट व्यवस्थापक से संपर्क करें।',

];
