<?php

/**
 * Pubvana CMS - Admin language strings (Turkish)
 *
 * AI Translated: verification needed from native speaker
 *
 * Usage: lang('Admin.keyName')
 */

return [

    // =========================================================================
    // Common UI - buttons, labels, confirmations, status badges
    // =========================================================================

    // Buttons
    'save'              => 'Kaydet',
    'saveChanges'       => 'Değişiklikleri Kaydet',
    'cancel'            => 'İptal',
    'edit'              => 'Düzenle',
    'delete'            => 'Sil',
    'create'            => 'Oluştur',
    'add'               => 'Ekle',
    'back'              => 'Geri',
    'view'              => 'Görüntüle',
    'apply'             => 'Uygula',
    'install'           => 'Yükle',
    'update'            => 'Güncelle',
    'refresh'           => 'Yenile',
    'activate'          => 'Etkinleştir',
    'deactivate'        => 'Devre Dışı Bırak',
    'enable'            => 'Etkinleştir',
    'disable'           => 'Devre Dışı Bırak',
    'disabled'          => 'Devre Dışı',
    'approve'           => 'Onayla',
    'spam'              => 'Spam',
    'trash'             => 'Çöp Kutusu',
    'restore'           => 'Geri Yükle',
    'dismiss'           => 'Kapat',
    'recheck'           => 'Tekrar Kontrol Et',
    'clickToCopy'       => 'Kopyalamak için tıklayın',
    'download'          => 'İndir',
    'upload'            => 'Yükle',
    'import'            => 'İçe Aktar',
    'export'            => 'Dışa Aktar',
    'publish'           => 'Yayınla',
    'unpublish'         => 'Yayından Kaldır',
    'logout'            => 'Çıkış Yap',
    'viewSite'          => 'Siteyi Görüntüle',
    'newPost'           => 'Yeni Yazı',
    'buyNow'            => 'Hemen Satın Al',
    'visitStore'        => 'Mağazayı Ziyaret Et',
    'loadMore'          => 'Daha Fazla Yükle',

    // Table headers / labels
    'title'             => 'Başlık',
    'name'              => 'Ad',
    'slug'              => 'Slug',
    'status'            => 'Durum',
    'date'              => 'Tarih',
    'actions'           => 'İşlemler',
    'author'            => 'Yazar',
    'views'             => 'Görüntüleme',
    'type'              => 'Tür',
    'url'               => 'URL',
    'description'       => 'Açıklama',
    'role'              => 'Rol',
    'email'             => 'E-posta',
    'username'          => 'Kullanıcı Adı',
    'active'            => 'Etkin',
    'version'           => 'Sürüm',
    'size'              => 'Boyut',
    'clicks'            => 'Tıklamalar',
    'total'             => 'Toplam',
    'platform'          => 'Platform',
    'label'             => 'Etiket',
    'order'             => 'Sıra',
    'source'            => 'Kaynak',
    'content'           => 'İçerik',
    'excerpt'           => 'Özet',
    'details'           => 'Ayrıntılar',
    'contentType'       => 'İçerik türü',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta Başlık',
    'metaDescription'   => 'Meta Açıklama',

    // Status badges
    'published'         => 'Yayınlandı',
    'draft'             => 'Taslak',
    'scheduled'         => 'Zamanlandı',
    'pending'           => 'Beklemede',
    'safe'              => 'Güvenli',
    'notSafe'           => 'Güvenli Değil',
    'malicious'         => 'Kötü Amaçlı',
    'safetyUnknown'     => 'Bilinmiyor',
    'inactive'          => 'Devre Dışı',
    'installed'         => 'Yüklendi',
    'free'              => 'Ücretsiz',
    'premium'           => 'Premium',
    'all'               => 'Tümü',

    // Confirmations
    'confirmDelete'         => 'Bu öğeyi silmek istediğinizden emin misiniz?',
    'confirmDeletePost'     => 'Bu yazıyı sil?',
    'confirmDeletePage'     => 'Bu sayfayı sil?',
    'confirmDeleteComment'  => 'Bu yorumu kalıcı olarak sil?',
    'confirmDeleteUser'     => 'Bu kullanıcıyı sil?',
    'confirmDeleteMedia'    => 'Sil?',
    'confirmDeleteBackup'   => 'Bu yedek dosyasını sil?',
    'confirmBulkAction'     => 'Seçili yazılara toplu işlem uygula?',

    // Empty states
    'noPostsYet'        => 'Henüz yazı yok. {0}',
    'noResultsFound'    => 'Sonuç bulunamadı.',
    'noCommentsYet'     => 'Bekleyen yorum yok.',
    'noMediaYet'        => 'Henüz medya yok.',
    'noItemsFound'      => 'Pazar yerinde öğe bulunamadı.',
    'noCategoriesYet'   => 'Henüz kategori yok.',
    'noTagsYet'         => 'Henüz etiket yok.',
    'noRevisionsYet'    => 'Revizyon bulunamadı.',

    // Misc common
    'permissionDenied'  => 'İzin reddedildi.',
    'notFound'          => 'Kayıt bulunamadı.',
    'commasSeparated'   => 'Virgülle ayrılmış',
    'optional'          => 'İsteğe bağlı',
    'required'          => 'Zorunlu',
    'enabled'           => 'Etkin',
    'selected'          => '{0} yazı seçildi',
    'published_count'   => '{0} yayınlandı',
    'pending_count'     => '{0} beklemede',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Gösterge Paneli',
    'navContent'        => 'İçerik',
    'navAppearance'     => 'Görünüm',
    'navUsersAndSite'   => 'Kullanıcılar & Site',
    'navTools'          => 'Araçlar',
    'navMarketplace'    => 'Pazar Yeri',
    'navPlugins'        => 'Eklentiler',
    'navPosts'          => 'Yazılar',
    'navSchedule'       => 'Zamanlama',
    'navPages'          => 'Sayfalar',
    'navCategories'     => 'Kategoriler',
    'navTags'           => 'Etiketler',
    'navComments'       => 'Yorumlar',
    'navMedia'          => 'Medya',
    'navImport'         => 'İçe Aktar',
    'navThemes'         => 'Temalar',
    'navWidgets'        => 'Widget\'lar',
    'navNavigation'     => 'Navigasyon',
    'navUsers'          => 'Kullanıcılar',
    'navSocialLinks'    => 'Sosyal Bağlantılar',
    'navRedirects'      => 'Yönlendirmeler',
    'navLanguages'      => 'Diller',
    'navSettings'       => 'Ayarlar',
    'navAnalytics'      => 'Analitik',
    'navAffiliates'     => 'Ortaklık Bağlantıları',
    'navBrokenLinks'    => 'Bozuk Bağlantılar',
    'navActivityLog'    => 'Etkinlik Günlüğü',
    'navBackup'         => 'Yedekleme & Dışa Aktarma',
    'navUpdates'        => 'Güncellemeler',
    'navBrowse'         => 'Gözat',
    'navLicenses'       => 'Lisanslar',
    'navPubvanaStore'   => 'Pubvana Mağazası',
    'navUpdateAvailable'=> 'Güncelleme Mevcut',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Çıkmaya Hazır mısınız?',
    'logoutModalBody'   => 'Oturumunuzu sonlandırmak için aşağıdaki "Çıkış Yap" seçeneğini seçin.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Gösterge Paneli',
    'dashStats'             => 'İstatistikler',
    'dashPosts'             => 'Yazılar',
    'dashPages'             => 'Sayfalar',
    'dashComments'          => 'Yorumlar',
    'dashUsers'             => 'Kullanıcılar',
    'dashRecentPosts'       => 'Son Yazılar',
    'dashPendingComments'   => 'Bekleyen Yorumlar',
    'dashViewAll'           => 'Tümünü Görüntüle',
    'dashCreateOne'         => 'Bir tane oluşturun!',
    'dashNoPosts'           => 'Henüz yazı yok.',
    'dashNoPendingComments' => 'Bekleyen yorum yok.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Yazılar',
    'newPostTitle'          => 'Yeni Yazı',
    'editPostTitle'         => 'Yazıyı Düzenle: {0}',
    'copyPreviewLink'       => 'Önizleme Bağlantısını Kopyala',
    'backToPosts'           => 'Yazılara Geri Dön',
    'postTitleField'        => 'Başlık *',
    'postEditor'            => 'Düzenleyici',
    'postHtmlEditor'        => 'HTML Düzenleyici',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Özet',
    'postExcerptPlaceholder'=> 'İsteğe bağlı kısa özet...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta Başlık',
    'postMetaDescription'   => 'Meta Açıklama',
    'postPublishSection'    => 'Yayınla',
    'postStatus'            => 'Durum',
    'postStatusDraft'       => 'Taslak',
    'postStatusPublished'   => 'Yayınlandı',
    'postStatusScheduled'   => 'Zamanlandı',
    'postScheduledAt'       => 'Zamanlanan Tarih ve Saat',
    'postFeatured'          => 'Öne Çıkan Yazı',
    'postMembersOnly'       => 'Yalnızca Üyeler İçin',
    'postShareOnPublish'    => 'Yayınlandığında sosyal medyada paylaş',
    'postSaveBtn'           => 'Yazıyı Kaydet',
    'postFeaturedImage'     => 'Öne Çıkan Görsel',
    'postFeaturedImagePlaceholder' => 'URL veya yükleme yolu…',
    'postCategories'        => 'Kategoriler',
    'postTags'              => 'Etiketler',
    'postTagsPlaceholder'   => 'etiket1, etiket2, etiket3',
    'postRevisions'         => 'Revizyonlar',
    'postRevisionCount'     => '{0} revizyon',
    'postPreview'           => 'Önizleme',
    'postBulkAction'        => '- İşlem seçin -',
    'postBulkPublish'       => 'Yayınla',
    'postBulkUnpublish'     => 'Yayından Kaldır (Taslak yap)',
    'postBulkDelete'        => 'Sil',

    // Post flash messages
    'postCreated'           => 'Yazı başarıyla oluşturuldu.',
    'postUpdated'           => 'Yazı güncellendi.',
    'scheduledDateMustBeFuture' => 'Zamanlanan tarih gelecekte olmalıdır.',
    'postDeleted'           => 'Yazı silindi.',
    'postBulkUpdated'       => '{0} yazı güncellendi.',
    'postBulkInvalid'       => 'Geçersiz toplu işlem.',
    'postPermission'        => 'Yalnızca kendi yazılarınızı düzenleyebilirsiniz.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revizyonlar: {0}',
    'revisionTitle'         => 'Revizyon — {0}',
    'revisionShowTitle'     => 'Revizyon',
    'revisionsBackToPost'   => 'Yazıya Geri Dön',
    'revisionsBackToList'   => 'Revizyonlara Geri Dön',
    'revisionRestored'      => 'Yazı {0} tarihli revizyona geri yüklendi.',
    'revisionRestoreBtn'    => 'Bu Revizyonu Geri Yükle',
    'revisionSaved'         => 'Kaydedildi',
    'revisionBy'            => 'Tarafından',
    'revisionOn'            => 'Tarihinde',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Sayfalar',
    'newPageTitle'          => 'Yeni Sayfa',
    'editPageTitle'         => 'Sayfayı Düzenle',
    'pageSlugInUse'         => "'{0}' slug\'ı zaten kullanımda.",
    'pageCannotDelete'      => 'Bu sayfa silinemez.',
    'slugAutoGenHint'       => 'boş bırakılırsa başlıktan otomatik oluşturulur',
    'slugCannotChange'      => 'değiştirilemez',
    'colSystem'             => 'Sistem',
    'system'                => 'Sistem',

    // Page flash messages
    'pageCreated'           => 'Sayfa oluşturuldu.',
    'pageUpdated'           => 'Sayfa güncellendi.',
    'pageDeleted'           => 'Sayfa silindi.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Kategoriler',
    'newCategoryTitle'      => 'Yeni Kategori',
    'editCategoryTitle'     => 'Kategoriyi Düzenle',
    'categoryName'          => 'Ad',
    'categoryDescription'   => 'Açıklama',
    'categoryPostCount'     => 'Yazı Sayısı',

    // Category flash messages
    'categoryCreated'       => 'Kategori oluşturuldu.',
    'categoryUpdated'       => 'Kategori güncellendi.',
    'categoryDeleted'       => 'Kategori silindi.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Etiketler',
    'tagPostCount'          => 'Yazı Sayısı',

    // Tag flash messages
    'tagDeleted'            => 'Etiket silindi.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Yorumlar',
    'commentAuthor'         => 'Yazar',
    'commentContent'        => 'Yorum',
    'commentPost'           => 'Yazı',
    'commentDate'           => 'Tarih',
    'commentStatusFilter'   => 'Duruma göre filtrele',

    // Comment flash messages
    'commentApproved'       => 'Yorum onaylandı.',
    'commentSpam'           => 'Spam olarak işaretlendi.',
    'commentTrashed'        => 'Yorum çöp kutusuna taşındı.',
    'commentDeleted'        => 'Yorum kalıcı olarak silindi.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Medya Kütüphanesi',
    'mediaTitle'            => 'Başlık',
    'mediaAltText'          => 'Alternatif Metin',
    'mediaAltPlaceholder'   => 'Erişilebilirlik için görseli açıklayın',
    'mediaTitlePlaceholder' => 'İsteğe bağlı görsel başlığı',
    'mediaImageDetails'     => 'Görsel Ayrıntıları',
    'mediaSaved'            => 'Kaydedildi!',
    'mediaNoSelection'      => 'Görsel seçilmedi',
    'mediaBrowse'           => 'Medyaya Göz At',
    'mediaRemove'           => 'Kaldır',
    'mediaUseImage'         => 'Bu Görseli Kullan',
    'mediaDropzone'         => 'Görseli buraya sürükleyip bırakın veya göz atmak için tıklayın',
    'mediaLoading'          => 'Medya yükleniyor…',
    'mediaEmpty'            => 'Henüz medya yüklenmedi.',
    'mediaUpload'           => 'Medya Yükle',
    'mediaDragDrop'         => 'Dosyaları buraya sürükleyip bırakın veya',
    'mediaChooseFiles'      => 'Dosyaları Seç',
    'mediaUploading'        => 'Yükleniyor…',
    'mediaFilename'         => 'Dosya Adı',
    'mediaSize'             => 'Boyut',
    'mediaUploadFailed'     => 'Yükleme başarısız: {0}',
    'mediaUploadError'      => 'Yükleme hatası: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Medya silindi.',
    'mediaNoValidFile'      => 'Geçerli bir dosya yüklenmedi.',
    'mediaUploadSuccess'    => 'Dosya başarıyla yüklendi.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navigasyon',
    'navQuickAdd'           => 'Hızlı Ekle',
    'navQuickAddPlaceholder' => 'Sayfalar, kategoriler, eklentiler ara...',
    'navItemLabel'          => 'Etiket',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Hedef',
    'navItemOrder'          => 'Sıralama',
    'navGroupPrimary'       => 'Birincil',
    'navGroupFooter'        => 'Altbilgi',
    'navSelectGroup'        => 'Navigasyon grubu seçin:',
    'navParent'             => 'Üst',
    'navTopLevel'           => '— Üst seviye —',
    'navSameWindow'         => 'Aynı pencere',
    'navNewWindow'          => 'Yeni pencere',
    'navMenuItems'          => 'Menü Öğeleri',
    'navNoItems'            => 'Bu menüde öğe yok.',
    'dragToReorder'         => 'Yeniden sıralamak için sürükleyin',

    // Navigation flash messages
    'navItemAdded'          => 'Navigasyon öğesi eklendi.',
    'navItemRemoved'        => 'Navigasyon öğesi kaldırıldı.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Temalar',
    'themeOptions'          => 'Tema Seçenekleri',
    'themeActivate'         => 'Etkinleştir',
    'themeOptionsBtn'       => 'Seçenekler',
    'themeActive'           => 'Etkin',
    'themeBy'               => 'Yapan',
    'themeSupport'          => 'Destek',
    'themeVersion'          => 'Sürüm',
    'themeSaveOptions'      => 'Seçenekleri Kaydet',
    'themeInvalidLicense'   => 'Tema etkinleştirilemiyor - lisans geçersiz. Yeniden yükleyin veya destekle iletişime geçin.',
    'themeValidationFailed' => 'Tema PHP kodu içeriyor ve etkinleştirilemiyor.',
    'noThemesInstalled'     => 'Yüklü tema yok. Tema almak için Pazar Yeri\'ni ziyaret edin.',
    'themeUnapprovedTitle'  => 'Onaylanmamış Tema Etkinleştirilsin mi?',
    'themeNotApproved'      => 'Bu tema Pubvana tarafından onaylanmamıştır.',
    'themeUnapprovedRisk'   => 'Onaylanmamış temaların etkinleştirilmesi güvenlik riskleri veya uyumluluk sorunları yaratabilir.',
    'themeActivateConfirm'  => 'Yine de etkinleştirmek istediğinizden emin misiniz?',
    'themeActivateAnyway'   => 'Yine de Etkinleştir',
    'themeNoOptions'        => 'Bu temanın yapılandırılabilir seçeneği yok.',
    'themeCustomize'        => 'Temayı Özelleştir',

    // Theme flash messages
    'themeActivated'        => 'Tema etkinleştirildi.',
    'themeOptionsSaved'     => 'Seçenekler kaydedildi.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Lisanslı',
    'licenseCheckNow'        => 'Şimdi Kontrol Et',
    'licenseExpired'         => 'Süresi Dolmuş',
    'licenseEnterKey'        => 'Anahtar Gir',
    'licenseChangeKey'       => 'Değiştir',
    'licenseRenew'           => 'Yenile',
    'licenseThirdParty'      => 'Üçüncü Taraf',
    'unchecked'              => 'Kontrol Edilmedi',
    'safetyLabel'            => 'Güvenlik:',
    'recheckBtn'             => 'Tekrar Kontrol Et',
    'recheckSuccess'         => 'Güvenlik kontrolü güncellendi.',
    'recheckFailed'          => 'Doğrulama sunucusuna ulaşılamadı. Lütfen daha sonra tekrar deneyin.',
    'recheckNotFound'        => 'Öğe bulunamadı.',
    'securityWarning'        => 'Güvenlik Uyarısı:',
    'licenseModalTitle'      => 'Lisans Anahtarı Gir',
    'licenseModalBody'       => 'Lisans anahtarınızı aşağıya yapıştırın.',
    'licenseModalSave'       => 'Kaydet',
    'licenseSaved'           => 'Lisans anahtarı kaydedildi ve doğrulandı.',
    'licenseInvalid'         => 'Lisans anahtarı geçerli değil.',
    'licenseKeyRequired'     => 'Lisans anahtarı ve ürün gereklidir.',
    'licenseCheckFailed'     => 'Lisans sunucusuna ulaşılamadı. Lütfen daha sonra tekrar deneyin.',
    'licenseProductNotFound' => 'Bu öğe mağazada bulunamadı.',
    'btnCancel'              => 'İptal',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widget\'lar',
    'widgetConfigureTitle'  => 'Widget\'ı Yapılandır',
    'widgetAreas'           => 'Widget Alanları',
    'widgetAvailable'       => 'Kullanılabilir Widget\'lar',
    'widgetAddToArea'       => 'Alana Ekle',
    'widgetArea'            => 'Alan',
    'widgetNoOptions'       => 'Seçenek yok.',
    'widgetSaveConfig'      => 'Yapılandırmayı Kaydet',
    'widgetConfigure'       => 'Yapılandır',
    'widgetNoAreas'         => 'Widget alanı bulunamadı. Widget alanlarını etkinleştirmek için bir tema etkinleştirin.',
    'widgetAreaEmpty'       => 'Bu alanda widget yok. Listeden bir tane ekleyin →',

    // Widget flash messages
    'widgetAdded'           => 'Widget eklendi.',
    'widgetRemoved'         => 'Widget kaldırıldı.',
    'widgetConfigured'      => 'Widget yapılandırıldı.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Pazar Yeri',
    'marketplaceRefresh'    => 'Yenile',
    'marketplaceVisitStore' => 'Mağazayı Ziyaret Et',
    'marketplaceAll'        => 'Tümü',
    'marketplaceThemes'     => 'Temalar',
    'marketplaceWidgets'    => 'Widget\'lar',
    'marketplacePlugins'    => 'Eklentiler',
    'marketplaceUpdatesAvailable' => '{0} güncelleme mevcut.',
    'marketplaceBy'         => 'Yapan',
    'marketplaceFree'       => 'Ücretsiz',
    'marketplaceInstalled'  => 'Yüklendi',
    'marketplaceInstall'    => 'Yükle',
    'marketplaceBuyNow'     => 'Hemen Satın Al',
    'marketplaceNoItems'    => 'Pazar yerinde öğe bulunamadı.',
    'marketplaceInstalledVersion' => 'v{0} yüklü',
    'marketplaceLoadError'  => 'Mağazadan ürünler yüklenemedi. Lütfen daha sonra tekrar kontrol edin.',
    'byAuthor'              => '{0} tarafından',
    'unknown'               => 'Bilinmiyor',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} başarıyla yüklendi.',
    'marketplaceInstallFail'    => 'Kurulum başarısız. Günlükleri kontrol edin.',
    'marketplaceUpdateSuccess'  => 'Başarıyla güncellendi.',
    'marketplaceUpdateFail'     => 'Güncelleme başarısız.',
    'marketplaceCacheRefreshed' => 'Pazar yeri önbelleği yenilendi.',
    'marketplaceInvalidRequest' => 'Geçersiz kurulum isteği.',
    'marketplaceCannotUpdate'   => 'Bu öğe güncellenemiyor.',

    // =========================================================================
    // Licenses
    // =========================================================================

    'licensesTitle'               => 'Lisanslar',
    'licensesNone'                => 'Lisans Yok',
    'licensesProduct'             => 'Ürün',
    'licensesKey'                 => 'Lisans Anahtarı',
    'licensesStatus'              => 'Durum',
    'licensesType'                => 'Tür',
    'licensesExpires'             => 'Bitiş Tarihi',
    'licensesDomain'              => 'Alan Adı',
    'licensesInstalled'           => 'Yüklendi',
    'licensesLastChecked'         => 'Son Kontrol',
    'licensesActions'             => 'İşlemler',
    'licensesStatusValid'         => 'Geçerli',
    'licensesStatusInvalid'       => 'Geçersiz',
    'licensesStatusExpired'       => 'Süresi Dolmuş',
    'licensesStatusSubExpired'    => 'Abonelik Süresi Dolmuş',
    'licensesStatusUnchecked'     => 'Kontrol Edilmedi',
    'licensesSubscription'        => 'Abonelik',
    'licensesOneTime'             => 'Tek Seferlik',
    'licensesPerpetual'           => 'Süresiz',
    'licensesNotInstalled'        => 'Yüklenmedi',
    'licensesNever'               => 'Hiçbir zaman',
    'licensesRevalidate'          => 'Yeniden Doğrula',
    'licenseKeyPlaceholder'       => 'Lisans anahtarı girin...',
    'marketplaceLicensesEmpty'    => 'Lisanslı ürünler kurulumdan sonra burada görünecek.',
    'typeTheme'                   => 'Tema',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Eklenti',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Lisans başarıyla doğrulandı.',
    'licenseRevalidateInvalid'     => 'Lisans geçersiz veya süresi dolmuş.',
    'licenseRevalidateUnreachable' => 'Lisans sunucusuna ulaşılamadı. Lütfen daha sonra tekrar deneyin.',
    'licenseRevalidateSkipped'     => 'Lisans kontrolü atlandı (geliştirici modu).',
    'licenseRevalidateNotFound'    => 'Lisans bulunamadı.',

    // License warning banners
    'licenseWarningTitle'   => 'Lisans Sorunları',
    'licenseWarningInvalid' => 'lisans geçersiz veya süresi dolmuş',
    'licenseWarningManage'  => 'Lisansları Yönet',

    // Plugin license
    'pluginInvalidLicense' => 'Bu eklentinin geçersiz veya süresi dolmuş bir lisansı var ve etkinleştirilemiyor.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Lisans Anahtarı',
    'storeBrowseFull'       => 'Tam Mağazaya Göz At',
    'storeBackToMarketplace'=> 'Pazar Yerine Geri Dön',
    'storeNoProducts'       => 'Mevcut ürün yok.',
    'storeViewInStore'      => 'Mağazada görüntüle',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Kullanıcılar',
    'editUserTitle'         => 'Kullanıcıyı Düzenle',
    'createUserTitle'       => 'Kullanıcı Oluştur',
    'authorProfileTitle'    => 'Yazar Profili',
    'userRoleLabel'         => 'Rol',
    'userActiveLabel'       => 'Etkin',
    'userPasswordLabel'     => 'Şifre',
    'userPasswordOptional'  => 'Mevcut şifreyi korumak için boş bırakın',
    'userDisplayName'       => 'Görünen Ad',
    'userBio'               => 'Biyografi',
    'userWebsite'           => 'Web Sitesi',
    'userTwitter'           => 'Twitter / X Kullanıcı Adı',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Profili Kaydet',
    'userSaveChanges'       => 'Değişiklikleri Kaydet',
    'userCannotDeleteSelf'  => 'Kendinizi silemezsiniz.',
    'userCannotDeleteOwner' => 'Site sahibinin hesabı silinemez.',
    'userOwnerCannotModify' => 'Site sahibinin hesabı değiştirilemez.',

    // User flash messages
    'userCreated'           => 'Kullanıcı oluşturuldu.',
    'userUpdated'           => 'Kullanıcı güncellendi.',
    'userDeleted'           => 'Kullanıcı silindi.',
    'userBanned'            => 'Kullanıcı yasaklandı.',
    'userUnbanned'          => 'Kullanıcının yasağı kaldırıldı.',
    'userCannotBanSelf'     => 'Kendinizi veya site sahibini yasaklayamazsınız.',
    'banStatus'             => 'Yasak Durumu',
    'banned'                => 'Yasaklı',
    'ban'                   => 'Kullanıcıyı Yasakla',
    'unban'                 => 'Yasağı Kaldır',
    'banReasonRequired'     => 'Yasak nedeni gereklidir.',
    'banReasonPlaceholder'  => 'Yasak nedeni...',
    'confirmBanUser'        => 'Bu kullanıcıyı yasaklamak istediğinizden emin misiniz?',
    'userProfileSaved'      => 'Profil kaydedildi.',
    'userAvatarUploadFail'  => 'Avatar yükleme başarısız: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA Kurulumu',
    'tfaSetupHeading'       => 'İki Faktörlü Kimlik Doğrulamayı Kur',
    'tfaScanQr'             => 'Kimlik doğrulama uygulamanızla (örn. Google Authenticator, Authy) aşağıdaki QR kodunu tarayın.',
    'tfaManualEntry'        => 'Ya da gizli anahtarı manuel olarak girin:',
    'tfaEnterCode'          => 'Onaylamak için uygulamanızdaki 6 haneli kodu girin:',
    'tfaCodeLabel'          => 'Kimlik Doğrulama Kodu',
    'tfaConfirmBtn'         => 'Onayla ve 2FA\'yı Etkinleştir',
    'tfaDisableBtn'         => '2FA\'yı Devre Dışı Bırak',
    'tfaDisableConfirm'     => 'Devre dışı bırakmak için mevcut 2FA kodunuzu girin:',
    'tfaEnabled'            => 'İki faktörlü kimlik doğrulama etkinleştirildi.',
    'tfaDisabled'           => 'İki faktörlü kimlik doğrulama devre dışı bırakıldı.',
    'tfaInvalidCode'        => 'Geçersiz kod - lütfen QR kodunu tarayın ve bir kez daha deneyin.',
    'tfaInvalidDisable'     => 'Geçersiz kod - 2FA devre dışı bırakılmadı.',
    'tfaSessionExpired'     => 'Kurulum oturumunun süresi doldu - lütfen yeniden başlayın.',
    'tfaNotEnabled'         => '2FA şu anda etkin değil.',
    'tfaCantScan'           => "Tarayamıyor musunuz? Bu kodu manuel olarak girin:",
    'tfaWarning'            => 'Bu gizli anahtarı güvenli bir yerde saklayın. Kimlik doğrulama cihazınızı kaybederseniz erişimi kurtarmak için buna ihtiyacınız olacak.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Sosyal Bağlantılar',
    'socialPlatform'           => 'Platform',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Simge',
    'socialSortOrder'          => 'Sıralama',
    'socialIconPackInfo'       => 'Mevcut tema <strong>{0}</strong>, simgeler için <strong>{1}</strong> (v{2}) kullanıyor. Aşağıdan bu sitenin Sosyal Bağlantılar özelliği için görüntülenecek simgeleri seçebilirsiniz.',
    'socialSearchPlaceholder'  => 'Platform ara...',
    'socialIconDisclaimer'     => "Bu simgeler yalnızca kullanılacak simgenin bir temsilidir. Gerçek simge, etkin temanın simge paketine bağlı olarak farklılık gösterebilir.",

    // Social flash messages
    'socialLinkAdded'       => 'Sosyal bağlantı eklendi.',
    'socialLinkUpdated'     => 'Bağlantı güncellendi.',
    'socialLinkDeleted'     => 'Bağlantı silindi.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Yönlendirmeler',
    'redirectFrom'          => 'Kaynak URL',
    'redirectTo'            => 'Hedef URL',
    'redirectType'          => 'Tür',
    'redirectAdd'           => 'Yönlendirme Ekle',
    'redirectFromHint'      => '(göreli, örn. /eski-sayfa)',
    'redirect301'           => '301 Kalıcı',
    'redirect302'           => '302 Geçici',
    'redirectInvalidDest'   => 'Geçersiz yönlendirme hedef URL\'si.',

    // Redirect flash messages
    'redirectAdded'         => 'Yönlendirme eklendi.',
    'redirectDeleted'       => 'Yönlendirme silindi.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Ayarlar',
    'settingsGeneral'       => 'Genel',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'E-posta',
    'settingsSocialLogin'   => 'Sosyal Giriş',
    'settingsSocialSharing' => 'Sosyal Paylaşım',
    'settingsSpam'          => 'Spam Koruması',

    'generalSettingsHeading'    => 'Genel Ayarlar',
    'generalSiteName'           => 'Site Adı',
    'generalTagline'            => 'Slogan',
    'generalAdminEmail'         => 'Yönetici E-postası',
    'generalPostsPerPage'       => 'Sayfa Başına Yazı',
    'generalComments'           => 'Yorumlar',
    'generalCommentsEnable'     => 'Yorumları etkinleştir',
    'generalCommentModeration'  => 'Yayınlanmadan önce moderasyon gerektir',
    'generalMaintenanceMode'    => 'Bakım Modu',
    'generalMaintenanceEnable'  => 'Bakım modunu etkinleştir',
    'generalMaintenanceHelp'    => "Ziyaretçiler \"Yakında döneceğiz\" sayfasını görür. Yöneticiler siteye erişmeye devam edebilir.",
    'generalFrontPage'          => 'Ön Sayfa',
    'generalFrontPageBlog'      => 'Blog dizini (son yazılar)',
    'generalFrontPageStatic'    => 'Statik sayfa:',
    'generalFrontPagePlugin'    => 'Eklenti sayfası:',
    'generalSelectPage'         => '- Bir sayfa seçin -',
    'generalSelectRoute'        => '- Bir rota seçin -',
    'generalFrontPageNoPlugins' => 'Kullanılabilir eklenti rotası yok',
    'generalPageCacheTtl'       => 'Sayfa Önbellek TTL',
    'settingsCacheTtlHint'      => 'Saniye. 0 = devre dışı.',
    'generalSaveBtn'            => 'Genel Ayarları Kaydet',

    // General flash messages
    'generalSettingsSaved'      => 'Genel ayarlar kaydedildi.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO Ayarları',
    'seoMetaDescription'        => 'Meta Açıklama',
    'seoGoogleAnalytics'        => 'Google Analytics Kimliği',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Site Haritası',
    'seoSitemapEnable'          => 'sitemap.xml\'yi etkinleştir',
    'seoSitemapHelp'            => 'Tüm yayınlanmış yazılar ve sayfalar için standart site haritası.',
    'seoNewsSitemap'            => 'news-sitemap.xml\'yi etkinleştir',
    'seoNewsSitemapHelp'        => 'Google Haberler site haritası - son 48 saatte yayınlanan yazıları listeler.',
    'seoSaveBtn'                => 'SEO Ayarlarını Kaydet',
    'seoSettingsSaved'          => 'SEO ayarları kaydedildi.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'E-posta Ayarları',
    'emailFromName'             => 'Gönderen Adı',
    'emailFromAddress'          => 'Gönderen Adresi',
    'emailProtocol'             => 'Protokol',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP Ana Bilgisayarı',
    'emailSmtpPort'             => 'SMTP Bağlantı Noktası',
    'emailSmtpEncryption'       => 'Şifreleme',
    'emailSmtpEncryptionNone'   => 'Yok',
    'emailSmtpUsername'         => 'SMTP Kullanıcı Adı',
    'emailSmtpPassword'         => 'SMTP Şifresi',
    'emailSaveBtn'              => 'E-posta Ayarlarını Kaydet',
    'emailSettingsSaved'        => 'E-posta ayarları kaydedildi.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Sosyal Giriş (OAuth)',
    'socialLoginHelp'           => 'Kimlik bilgileri .env dosyanıza kaydedilir. İstemci kimlikleri ve gizli anahtarları almak için uygulamanızı Google ve Facebook\'a kaydedin.',
    'socialLoginGoogleId'       => 'İstemci Kimliği',
    'socialLoginGoogleSecret'   => 'İstemci Gizli Anahtarı',
    'socialLoginFbAppId'        => 'Uygulama Kimliği',
    'socialLoginFbAppSecret'    => 'Uygulama Gizli Anahtarı',
    'socialLoginPlaceholderSecret' => '(mevcut olanı korumak için boş bırakın)',
    'socialLoginSaveBtn'        => 'Sosyal Giriş Ayarlarını Kaydet',
    'socialLoginSettingsSaved'  => 'Sosyal giriş ayarları kaydedildi.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Yayınlandığında Otomatik Sosyal Paylaşım',
    'socialSharingHelp'         => 'Bir yazı "Yayında paylaş" seçeneği işaretli olarak yayınlandığında, Pubvana otomatik olarak yapılandırılmış sosyal hesaplara gönderi yapar.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Anahtarları developer.twitter.com → Uygulamanız → Anahtarlar ve Token\'lar bölümünden alın.',
    'socialSharingApiKey'       => 'API Anahtarı',
    'socialSharingApiSecret'    => 'API Gizli Anahtarı',
    'socialSharingAccessToken'  => 'Erişim Token\'ı',
    'socialSharingAccessSecret' => 'Erişim Gizli Anahtarı',
    'socialSharingFbPage'       => 'Facebook Sayfası',
    'socialSharingFbPageHelp'   => 'pages_manage_posts izniyle bir Sayfa Erişim Token\'ı gerektirir.',
    'socialSharingFbPageId'     => 'Sayfa Kimliği',
    'socialSharingFbPageToken'  => 'Sayfa Erişim Token\'ı',
    'socialSharingSaveBtn'      => 'Paylaşım Ayarlarını Kaydet',
    'socialSharingSettingsSaved'=> 'Sosyal paylaşım ayarları kaydedildi.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Spam Koruması (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana, yorum formlarını ve iletişim formunu spam botlarından korumak için hCaptcha (gizlilik odaklı, Google dışı) kullanır.',
    'spamHcaptchaFree'          => 'hCaptcha çoğu site için ücretsizdir. hcaptcha.com\'a kayıt olun, bir site oluşturun ve anahtarlarınızı aşağıya girin.',
    'spamHcaptchaSiteKey'       => 'Site Anahtarı',
    'spamHcaptchaSecretKey'     => 'Gizli Anahtar',
    'spamHcaptchaNote'          => 'Bu anahtarlar ayarlanmamışsa, hCaptcha sessizce atlanır — yerel geliştirme için güvenlidir. Kaydedildiğinde, widget otomatik olarak yorum formu ve iletişim sayfasında görünür.',
    'spamSettingsSaved'         => 'Spam koruma ayarları kaydedildi.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Diller',
    'languageCode'              => 'Kod',
    'languageName'              => 'Ad',
    'languageDefault'           => 'Varsayılan',
    'languageEnabled'           => 'Etkin',
    'languageMakeDefault'       => 'Varsayılan Yap',
    'languageSetAsDefault'      => '{0} varsayılan dil olarak ayarlandı.',
    'languageEnabled_msg'       => '{0} etkinleştirildi.',
    'languageDisabled_msg'      => '{0} devre dışı bırakıldı.',
    'languageNotFound'          => 'Dil bulunamadı.',
    'languageCannotDisable'     => 'Varsayılan dil devre dışı bırakılamaz.',
    'languageDirection'         => 'Yön',
    'languageNativeName'        => 'Yerel Ad',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analitik',
    'analyticsTotalViews'       => 'Toplam Görüntüleme',
    'analyticsTopPosts'         => 'En Çok Görüntülenen Yazılar',
    'analyticsReferrers'        => 'En Üst Yönlendirenler',
    'analyticsLast7'            => 'Son 7 gün',
    'analyticsLast30'           => 'Son 30 gün',
    'analyticsLast90'           => 'Son 90 gün',
    'analyticsChartTitle'       => 'Sayfa Görüntüleme',
    'analyticsNoData'           => 'Bu dönem için analitik verisi yok.',
    'analyticsDomain'           => 'Alan Adı',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Ortaklık Bağlantıları',
    'newAffiliateLinkTitle'     => 'Yeni Ortaklık Bağlantısı',
    'editAffiliateLinkTitle'    => 'Ortaklık Bağlantısını Düzenle',
    'affiliateName'             => 'Ad',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Hedef URL',
    'affiliateActive'           => 'Etkin',
    'affiliateClicks'           => 'Tıklamalar',
    'affiliateClicksTitle'      => 'Tıklamalar - {0}',
    'affiliateTotal'            => 'Toplam',
    'affiliateViewClicks'       => 'Tıklamaları Görüntüle',

    // Affiliate flash messages
    'affiliateCreated'          => 'Ortaklık bağlantısı oluşturuldu.',
    'affiliateUpdated'          => 'Ortaklık bağlantısı güncellendi.',
    'affiliateDeleted'          => 'Ortaklık bağlantısı silindi.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Bozuk Bağlantılar',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP Durumu',
    'brokenLinkError'           => 'Hata',
    'brokenLinkSource'          => 'Kaynak',
    'brokenLinkShowDismissed'   => 'Kapatılanları göster',
    'brokenLinkHideDismissed'   => 'Kapatılanları gizle',
    'brokenLinkTimeout'         => 'Zaman Aşımı',
    'brokenLinkBroken'          => 'bozuk',
    'brokenLinkNone'            => 'Bozuk bağlantı tespit edilmedi.',
    'brokenLinkNowReachable'    => 'Bağlantı artık erişilebilir - sonuçlardan kaldırıldı.',
    'brokenLinkStillBroken'     => 'Bağlantı hala bozuk ({0}).',
    'brokenLinkDismissed'       => 'Bağlantı kapatıldı.',
    'brokenLinksCliHint'        => 'Bu raporu doldurmak için komut satırından tam tarama çalıştırın: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} sorun bulundu',
    'brokenLinksCount'          => '{0} bozuk',
    'brokenLinksRecheck'        => 'Bu URL\'yi yeniden kontrol et',
    'brokenLinksDismiss'        => 'Kapat (sonuçlardan gizle)',
    'brokenLinksRunScan'        => 'Tarama Çalıştır',
    'brokenLinksScanComplete'   => 'Tarama tamamlandı: {0} bağlantı kontrol edildi, {1} bozuk.',
    'timeout'                   => 'Zaman Aşımı',
    'typePost'                  => 'Yazı',
    'typePage'                  => 'Sayfa',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Etkinlik Günlüğü',
    'activityLogType'           => 'Tür',
    'activityLogAction'         => 'İşlem',
    'activityLogUser'           => 'Kullanıcı',
    'activityLogDate'           => 'Tarih',
    'activityLogNote'           => 'Not',
    'activityLogFilterAll'      => 'Tüm Türler',
    'activityLogEmpty'          => 'Henüz etkinlik kaydedilmedi.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Yedekleme & Dışa Aktarma',
    'backupDownload'            => 'Yedek Oluştur ve İndir',
    'backupFiles'               => 'Mevcut Yedekler',
    'backupFilename'            => 'Dosya Adı',
    'backupSize'                => 'Boyut',
    'backupDate'                => 'Oluşturulma',
    'backupGenerating'          => 'Yedek oluşturuluyor…',
    'backupNoFiles'             => 'Kayıtlı yedek yok.',
    'backupFailed'              => 'Yedekleme başarısız: {0}',
    'backupDeleted'             => 'Yedek silindi.',
    'backupCannotDelete'        => 'Yedek silinemedi.',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP\'ler SHA-256 hash olarak saklanır — ham kişisel veri kaydedilmez.',
    'colTime'                   => 'Saat',
    'colIpHash'                 => 'IP Hash',
    'colReferrer'               => 'Yönlendiren',
    'affiliateDirectReferrer'   => 'Doğrudan',
    'affiliateNameHint'         => 'Dahili etiket — ziyaretçilere gösterilmez.',
    'affiliateSlugHint'         => 'Yalnızca harfler, rakamlar, kısa çizgiler ve alt çizgiler. Bağlantılar paylaşıldıktan sonra değiştirilemez.',
    'affiliateDestHint'         => 'https:// içermeli. Ziyaretçiler 301 ile buraya yönlendirilir.',
    'affiliateInactiveHint'     => 'Devre dışı bağlantılar 404 döndürür.',
    'affiliateLinkCount'        => '{0} Bağlantı',
    'colDomain'                 => 'Alan Adı',
    'commentAll'                => 'Tümü',
    'commentPending'            => 'Beklemede',
    'commentTrash'              => 'Çöp Kutusu',
    'commentsNone'              => '{0} yorum yok.',

    'backupCreate'              => 'Yedek Oluştur',
    'backupStarting'            => 'Yedekleme başlatılıyor...',
    'backupNoneYet'             => 'Henüz yedek yok. İlk yedeğinizi oluşturmak için "Yedek Oluştur"a tıklayın.',
    'backupsTitle'              => 'Yedekler',
    'backupRetentionNote'       => 'Maksimum 15 yedek saklanır — eskiler otomatik olarak silinir.',
    'backupRestoreConfirm'      => 'Bu yedeği geri yükle? Önce mevcut durumun yedeği oluşturulacak.',
    'backupDeleteConfirm'       => 'Bu yedeği sil?',
    'colFilename'               => 'Dosya Adı',
    'colVersion'                => 'Sürüm',
    'colTrigger'                => 'Tetikleyici',
    'colSize'                   => 'Boyut',
    'colDate'                   => 'Tarih',
    'colActions'                => 'İşlemler',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'İçe Aktar',
    'importWpHeading'           => 'WordPress\'ten İçe Aktar',
    'importWpHelp'              => 'WordPress sitenizi Araçlar → Dışa Aktar üzerinden dışa aktarın, ardından .xml dosyasını aşağıya yükleyin.',
    'importChooseFile'          => 'WXR Dosyası Seç (.xml)',
    'importDryRun'              => 'Deneme çalışması (yalnızca önizleme - hiçbir şey kaydedilmez)',
    'importRunBtn'              => 'İçe Aktarmayı Çalıştır',
    'importNoValidFile'         => 'Lütfen geçerli bir WordPress WXR dışa aktarma dosyası yükleyin.',
    'importOnlyXml'             => 'Yalnızca .xml dosyaları kabul edilir.',
    'importFileTooLarge'        => 'İçe aktarma dosyası çok büyük. Maksimum boyut 50 MB.',
    'importResultsHeading'      => 'İçe Aktarma Sonuçları',
    'importDryRunNote'          => 'Deneme çalışması - veri kaydedilmedi.',
    'importDryRunLabel'         => '(Deneme Çalışması — veri yazılmadı)',
    'importComplete'            => 'İçe Aktarma Tamamlandı',
    'importCreated'             => 'oluşturuldu',
    'importSkipped'             => 'atlandı',
    'importErrors'              => 'Hatalar:',
    'importInstructions'        => 'WordPress içeriğinizi <strong>Araçlar → Dışa Aktar → Tüm içerik</strong> bölümünden dışa aktarın ve <code>.xml</code> dosyasını buraya yükleyin. Pubvana yazıları, sayfaları, kategorileri, etiketleri, yazarları ve yorumları içe aktarır.',
    'importCliTitle'            => 'CLI İçe Aktarma',
    'importCliHint'             => 'İçe aktarıcıyı komut satırından da çalıştırabilirsiniz:',
    'importCliDryRunHint'       => '<code>--dry-run</code> bayrağı, veritabanına yazmadan neyin içe aktarılacağını gösterir.',
    'importWhatTitle'           => 'Neler İçe Aktarılır',
    'importItemPosts'           => 'Yazılar (başlık, içerik, özet, slug, durum)',
    'importItemPages'           => 'Sayfalar',
    'importItemCategories'      => 'Kategoriler (hiyerarşiyle)',
    'importItemTags'            => 'Etiketler',
    'importItemAuthors'         => 'Yazarlar (abone hesapları olarak oluşturulur)',
    'importItemComments'        => 'Yorumlar',
    'importItemMedia'           => 'Medya dosyaları (URL\'ler içerikte korunur)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Güncellemeler',
    'updatesCurrentVersion'     => 'Mevcut Sürüm',
    'updatesLatestVersion'      => 'Son Sürüm',
    'updatesUpToDate'           => 'Pubvana güncel.',
    'updatesAvailable'          => 'Güncelleme mevcut: {0}',
    'updatesCheckBtn'           => 'Güncellemeleri Kontrol Et',
    'updatesReleaseNotes'       => 'Sürüm Notları',
    'updatesHowToApply'         => 'Güncelleme Nasıl Uygulanır',
    'updatesCacheCleared'       => 'Güncelleme önbelleği temizlendi - şimdi yeniden kontrol ediliyor.',
    'updatesExtCapped'          => 'Güncelleme mevcut: {0} (eklenti uyumlu)',
    'updatesNewerAvailable'     => 'Pubvana {0} da mevcut - kilidini açmak için aşağıdaki eklentileri güncelleyin.',

    // Addon Updates
    'updatesExtTitle'               => 'Eklentiler',
    'updatesExtCheckAll'            => 'Tümünü Kontrol Et',
    'updatesExtUpdateAll'           => 'Tümünü Güncelle',
    'updatesExtCheckAllType'        => 'Tüm {0} Kontrol Et',
    'updatesExtUpdateAllType'       => 'Tüm {0} Güncelle',
    'updatesExtNoInstalled'         => 'Yüklü {0} yok.',
    'updatesExtColName'             => 'Ad',
    'updatesExtColVersion'          => 'Sürüm',
    'updatesExtColLatest'           => 'Son',
    'updatesExtColAutoUpdate'       => 'Otomatik Güncelleme',
    'updatesExtColStatus'           => 'Durum',
    'updatesExtColActions'          => 'İşlemler',
    'updatesExtBundled'             => 'Çekirdekle Birlikte Gelen',
    'updatesExtNoSource'            => 'Güncelleme kaynağı yok',
    'updatesExtFailed'              => 'Başarısız',
    'updatesExtUpdatedAt'           => '{0} tarihinde güncellendi',
    'updatesExtAvailable'           => 'Güncelleme mevcut',
    'updatesExtUpToDate'            => 'Güncel',
    'updatesExtUpdate'              => 'Güncelle',
    'updatesExtChecking'            => 'Kontrol ediliyor...',
    'updatesExtUpdating'            => 'Güncelleniyor...',
    'updatesExtUpdated'             => 'Güncellendi',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Güncellemeyi Onayla',
    'updatesConfirmBody'            => 'Bu işlem sitenizi yedekleyecek, güncellemeyi indirecek ve uygulayacak.',
    'updatesConfirmSafe'            => '<code>.env</code>, <code>App.php</code> ve <code>Database.php</code> dosyalarınız hiçbir zaman üzerine yazılmaz.',
    'updatesConfirmBtn'             => 'Şimdi Güncelle',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Tüm Eklentileri Güncelle',
    'updatesExtAllBody'             => 'Bu işlem bekleyen güncellemelere sahip tüm eklentileri güncelleyecek.',
    'updatesExtAllNote'             => 'Otomatik güncelleme devre dışı eklentiler de güncellenecek.',
    'updatesExtAllBtn'              => 'Tümünü Güncelle',

    'updatesExtBadge'               => 'Güncelleme: v{0}',
    'updatesExtGoToUpdates'         => 'Güncellemeler',

    // Update Settings
    'updatesSettingsTitle'          => 'Güncelleme Ayarları',
    'updatesAutoUpdateLabel'        => 'Pubvana Otomatik Güncelleme',
    'updatesAutoUpdateManual'       => 'Manuel',
    'updatesAutoUpdateAuto'         => 'Otomatik',
    'updatesAutoUpdateHelp'         => 'Etkinleştirildiğinde, kırılıcı değişiklik içermeyen Pubvana güncellemeleri otomatik olarak uygulanır.',
    'updatesCheckMethodLabel'       => 'Güncelleme Kontrol Yöntemi',
    'updatesCheckMethodPageload'    => 'Sayfa Yükleme',
    'updatesCheckMethodCron'        => 'Cron Görevi',
    'updatesCheckMethodHelp'        => 'Sayfa Yükleme her istekte kontrol eder (24s önbellekli). Cron, sunucu cron görevi gerektirir.',
    'updatesCronCommand'            => 'Cron Komutu',
    'updatesCronHelp'               => 'Güncelleme kontrolünü günlük çalıştırmak için sunucunuzun crontab\'ına bunu ekleyin:',
    'updatesSettingsSaved'          => 'Güncelleme ayarları kaydedildi.',

    // Compatibility
    'compatWarningTitle'            => 'Uyumluluk Uyarısı',
    'compatNotCompatible'           => 'Bazı yüklü eklentiler bu sürümle uyumlu değil.',
    'compatRequiresUpdate'          => 'ancak önce aşağıdaki eklentilerin güncellenmesi gerekiyor:',
    'compatSupportsUpTo'            => '{0} sürümüne kadar destekler',
    'compatRequiresMin'             => 'Pubvana {0}+ gerektirir',
    'compatNotDeclared'             => 'Aşağıdaki eklentiler Pubvana {0} ile uyumluluğunu beyan etmedi. Güncellemeden sonra çalışmayı durdurabilirler:',
    'compatColType'                 => 'Tür',
    'compatColName'                 => 'Ad',
    'compatColVersion'              => 'Uyumluluk',
    'compatRemoveHint'              => 'Sorun oluşursa uyumsuz eklentileri kaldırabilir veya varsayılan temaya geçebilirsiniz. Her güncellemeden önce bir yedek oluşturulur.',
    'compatMaxVersion'              => 'Maksimum uyumlu sürüm: {0}',
    'compatMinVersion'              => 'Pubvana {0}+ gerektirir',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Yazı Zamanlaması',
    'scheduleNoScheduled'       => 'Zamanlanmış yazı yok.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revizyonlar - {0}',
    'revisionPageTitle'         => 'Revizyon - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Yönetim paneline erişmek için giriş yapmalısınız.',
    'dirNotWritable'            => 'Dizin yazılabilir değil: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} hatalı yapılandırılmış. Son kullanıcıysanız geliştiriciye başvurun. Geliştirici iseniz belgelere bakın.',
    'addonMisconfiguredLink'    => '{0} hatalı yapılandırılmış. Son kullanıcıysanız <a href="{1}">geliştiriciye başvurun</a>. Geliştirici iseniz <a href="https://github.com/enlivenapp/pubvana">belgelere bakın</a>.',
    'licenseExpiringSoon'       => '{0} lisansı {1} tarihinde sona erecek. Lisans sona erdiğinde {0} devre dışı bırakılacak.',
    'licenseExpiredDeactivated' => '{0} lisansı sona erdiği için devre dışı bırakıldı.',
    'addonDeactivated'          => '{0} devre dışı bırakıldı. Neden: {1}.',
    'widgetValidationFailed'    => "''{0}'' widget\'ı doğrulanamadı. Geliştiriciye başvurun veya eklentiyi kaldırın.",
    'widgetValidationFailedLink' => "''{0}'' widget\'ı doğrulanamadı. <a href=\"{1}\">Geliştiriciye başvurun</a> veya eklentiyi kaldırın.",

    'addonDeactivatedExpired'   => 'Devre dışı: lisansın süresi doldu',
    'addonDeactivatedTampered'  => 'Devre dışı: hatalı yapılandırılmış',
    'addonDeactivatedNoLicense' => 'Devre dışı: geçerli lisans yok',

    'addonDisabled'             => 'Devre Dışı',
    'addonDisabledInvalidJson'  => 'Sistem: {0} geçersiz veya okunamaz bir {1} içeriyor.',
    'addonDisabledMissingFields' => 'Sistem: {0} gerekli alanları eksik: {1}.',
    'addonDisabledPhpFiles'     => 'Sistem: {0} PHP dosyaları içeriyor. Widget\'lar yalnızca JSON + şablon olmalıdır.',

    'licenseRequired'           => '{0}\'i etkinleştirmek için geçerli bir lisans gereklidir.',
    'licenseInvalidActivation'  => '{0} için lisans doğrulaması başarısız. Lütfen lisans anahtarınızı kontrol edin.',
    'licenseExpiredActivation'  => '{0} lisansının süresi dolmuş. Etkinleştirmek için yenileyin.',
    'licenseCheckUnreachable'   => '{0} için lisans doğrulanamadı. Lisans sunucusuna ulaşılamıyor. Lütfen daha sonra tekrar deneyin.',
    'activationBlockedTampered' => '{0} hatalı yapılandırıldığı için etkinleştirilemiyor.',
    'activationBlockedBundled'  => '{0} etkinleştirilemiyor: yalnızca Pubvana eklentileri paket olarak işaretlenebilir.',
    'activationBlockedNoUrls'   => '{0} etkinleştirilemiyor: ücretli eklentiler lisans doğrulama URL\'leri içermelidir.',
    'activationBlockedFreeFlag' => '{0} etkinleştirilemiyor: Pubvana eklentileri ücretsiz olarak işaretlenemez.',
    'activationBlockedDisabled' => '{0} yapılandırma hataları olduğu için etkinleştirilemiyor. Bilgi dosyasını kontrol edin.',

    'licenseThirdPartyLabel'    => '3. Taraf',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Güncelleme başlatılıyor...',
    'updateCheckLabel'           => 'Güncelleme kontrolü:',
    'updateAvailable'            => 'Pubvana {0} mevcut!',
    'updateRunning'              => '{0} çalışıyorsunuz.',
    'updateBreakingChanges'      => 'Kırılıcı Değişiklikler',
    'updateMigrationNotes'       => 'Migrasyon Notları',
    'updateNotices'              => 'Bildirimler',
    'updatePreflightTitle'       => 'Uçuş Öncesi Kontroller',
    'updateToVersion'            => 'Pubvana {0}\'e Güncelle',
    'updatePreflightFailed'      => 'Bir veya daha fazla zorunlu uçuş öncesi kontrol başarısız. Güncellemeden önce bunları çözün.',
    'updateUpToDate'             => 'Pubvana güncel. {0} sürümünü çalıştırıyorsunuz.',
    'updateAnyway'               => 'Yine de Güncelle',
    'updateAvailableTooltip'     => 'Pubvana {0} mevcut',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(siz)',
    'usersNone'                  => 'Kullanıcı bulunamadı.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Hesap etkin',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Profil Ayrıntıları',
    'profileDisplayNameHint'     => 'Yayınlanan yazılarda kullanıcı adı yerine gösterilir.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP veya GIF. Maks 10 MB.',
    'profileSocialHandles'       => 'Sosyal Medya Hesapları',
    'preview'                    => 'Önizleme',
    'website'                    => 'Web Sitesi',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'İki Faktörlü Kimlik Doğrulama',
    'totpActiveDesc'             => 'Hesabınızda TOTP iki faktörlü kimlik doğrulama etkin. Her oturum açtığınızda kimlik doğrulama uygulamanızdan 6 haneli bir kod girmeniz istenecek.',
    'totpCurrentCode'            => 'Mevcut Kod',
    'totpInactiveDesc'           => 'Hesabınıza ekstra güvenlik katmanı ekleyin. Etkinleştirildiğinde, her oturum açışta kimlik doğrulama uygulamanızdan bir kod girmeniz gerekecek.',
    'totpEnable'                 => 'İki Faktörlü Kimlik Doğrulamayı Etkinleştir',
    'totpScanInstructions'       => 'Kimlik doğrulama uygulamanızı (Google Authenticator, Authy, 1Password, vb.) açın ve bu QR kodunu tarayın.',
    'totpManualEntry'            => "Tarayamıyor musunuz? Bu kodu manuel olarak girin:",
    'totpConfirmInstructions'    => 'Taradıktan sonra, kurulumu onaylamak için uygulamanızda gösterilen 6 haneli kodu girin.',
    'totpRecoveryWarning'        => 'Kurtarma kodlarınızı saklayın. Kimlik doğrulama uygulamanıza erişimi kaybederseniz giriş yapamayacaksınız. 2FA\'yı sıfırlamak için site yöneticinizle iletişime geçin.',

];
