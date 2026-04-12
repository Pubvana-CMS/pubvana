<?php

/**
 * Pubvana CMS - Admin language strings (Japanese)
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
    'saveChanges'       => '変更を保存',
    'cancel'            => 'キャンセル',
    'edit'              => '編集',
    'delete'            => '削除',
    'create'            => '作成',
    'add'               => '追加',
    'back'              => '戻る',
    'view'              => '表示',
    'apply'             => '適用',
    'install'           => 'インストール',
    'update'            => '更新',
    'refresh'           => '更新',
    'activate'          => '有効化',
    'deactivate'        => '無効化',
    'enable'            => '有効にする',
    'disable'           => '無効にする',
    'disabled'          => '無効',
    'approve'           => '承認',
    'spam'              => 'スパム',
    'trash'             => 'ゴミ箱',
    'restore'           => '復元',
    'dismiss'           => '閉じる',
    'recheck'           => '再確認',
    'clickToCopy'       => 'クリックしてコピー',
    'download'          => 'ダウンロード',
    'upload'            => 'アップロード',
    'import'            => 'インポート',
    'export'            => 'エクスポート',
    'publish'           => '公開',
    'unpublish'         => '非公開',
    'logout'            => 'ログアウト',
    'viewSite'          => 'サイトを表示',
    'newPost'           => '新規記事',
    'buyNow'            => '今すぐ購入',
    'visitStore'        => 'ストアを見る',
    'loadMore'          => 'もっと読む',

    // Table headers / labels
    'title'             => 'タイトル',
    'name'              => '名前',
    'slug'              => 'スラッグ',
    'status'            => 'ステータス',
    'date'              => '日付',
    'actions'           => 'アクション',
    'author'            => '著者',
    'views'             => '閲覧数',
    'type'              => '種類',
    'url'               => 'URL',
    'description'       => '説明',
    'role'              => 'ロール',
    'email'             => 'メールアドレス',
    'username'          => 'ユーザー名',
    'active'            => '有効',
    'version'           => 'バージョン',
    'size'              => 'サイズ',
    'clicks'            => 'クリック数',
    'total'             => '合計',
    'platform'          => 'プラットフォーム',
    'label'             => 'ラベル',
    'order'             => '順序',
    'source'            => 'ソース',
    'content'           => 'コンテンツ',
    'excerpt'           => '抜粋',
    'details'           => '詳細',
    'contentType'       => 'コンテンツタイプ',
    'seo'               => 'SEO',
    'metaTitle'         => 'メタタイトル',
    'metaDescription'   => 'メタ説明',

    // Status badges
    'published'         => '公開済み',
    'draft'             => '下書き',
    'scheduled'         => '予約済み',
    'pending'           => '承認待ち',
    'safe'              => '安全',
    'notSafe'           => '安全でない',
    'malicious'         => '悪意あり',
    'safetyUnknown'     => '不明',
    'inactive'          => '無効',
    'installed'         => 'インストール済み',
    'free'              => '無料',
    'premium'           => 'プレミアム',
    'all'               => 'すべて',

    // Confirmations
    'confirmDelete'         => 'このアイテムを削除してもよろしいですか？',
    'confirmDeletePost'     => 'この記事を削除しますか？',
    'confirmDeletePage'     => 'このページを削除しますか？',
    'confirmDeleteComment'  => 'このコメントを完全に削除しますか？',
    'confirmDeleteUser'     => 'このユーザーを削除しますか？',
    'confirmDeleteMedia'    => '削除しますか？',
    'confirmDeleteBackup'   => 'このバックアップファイルを削除しますか？',
    'confirmBulkAction'     => '選択した記事に一括操作を適用しますか？',

    // Empty states
    'noPostsYet'        => 'まだ記事がありません。{0}',
    'noResultsFound'    => '結果が見つかりませんでした。',
    'noCommentsYet'     => '承認待ちのコメントはありません。',
    'noMediaYet'        => 'まだメディアがありません。',
    'noItemsFound'      => 'マーケットプレイスにアイテムが見つかりません。',
    'noCategoriesYet'   => 'まだカテゴリがありません。',
    'noTagsYet'         => 'まだタグがありません。',
    'noRevisionsYet'    => 'リビジョンが見つかりませんでした。',

    // Misc common
    'permissionDenied'  => 'アクセス権限がありません。',
    'notFound'          => 'レコードが見つかりません。',
    'commasSeparated'   => 'カンマ区切り',
    'optional'          => '任意',
    'required'          => '必須',
    'enabled'           => '有効',
    'selected'          => '{0} 件の記事が選択されました',
    'published_count'   => '{0} 件公開済み',
    'pending_count'     => '{0} 件承認待ち',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'ダッシュボード',
    'navContent'        => 'コンテンツ',
    'navAppearance'     => '外観',
    'navUsersAndSite'   => 'ユーザーとサイト',
    'navTools'          => 'ツール',
    'navMarketplace'    => 'マーケットプレイス',
    'navPlugins'        => 'プラグイン',
    'navPosts'          => '記事',
    'navSchedule'       => 'スケジュール',
    'navPages'          => 'ページ',
    'navCategories'     => 'カテゴリ',
    'navTags'           => 'タグ',
    'navComments'       => 'コメント',
    'navMedia'          => 'メディア',
    'navImport'         => 'インポート',
    'navThemes'         => 'テーマ',
    'navWidgets'        => 'ウィジェット',
    'navNavigation'     => 'ナビゲーション',
    'navUsers'          => 'ユーザー',
    'navSocialLinks'    => 'ソーシャルリンク',
    'navRedirects'      => 'リダイレクト',
    'navLanguages'      => '言語',
    'navSettings'       => '設定',
    'navAnalytics'      => 'アナリティクス',
    'navAffiliates'     => 'アフィリエイトリンク',
    'navBrokenLinks'    => 'リンク切れ',
    'navActivityLog'    => 'アクティビティログ',
    'navBackup'         => 'バックアップとエクスポート',
    'navUpdates'        => 'アップデート',
    'navBrowse'         => '参照',
    'navLicenses'       => 'ライセンス',
    'navPubvanaStore'   => 'Pubvanaストア',
    'navUpdateAvailable'=> 'アップデートあり',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'ログアウトしますか？',
    'logoutModalBody'   => '「ログアウト」を選択してセッションを終了します。',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'ダッシュボード',
    'dashStats'             => '統計',
    'dashPosts'             => '記事',
    'dashPages'             => 'ページ',
    'dashComments'          => 'コメント',
    'dashUsers'             => 'ユーザー',
    'dashRecentPosts'       => '最近の記事',
    'dashPendingComments'   => '承認待ちのコメント',
    'dashViewAll'           => 'すべて見る',
    'dashCreateOne'         => '作成する！',
    'dashNoPosts'           => 'まだ記事がありません。',
    'dashNoPendingComments' => '承認待ちのコメントはありません。',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => '記事',
    'newPostTitle'          => '新規記事',
    'editPostTitle'         => '記事を編集：{0}',
    'copyPreviewLink'       => 'プレビューリンクをコピー',
    'backToPosts'           => '記事一覧に戻る',
    'postTitleField'        => 'タイトル *',
    'postEditor'            => 'エディター',
    'postHtmlEditor'        => 'HTMLエディター',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => '抜粋',
    'postExcerptPlaceholder'=> '任意の短い概要...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'メタタイトル',
    'postMetaDescription'   => 'メタ説明',
    'postPublishSection'    => '公開',
    'postStatus'            => 'ステータス',
    'postStatusDraft'       => '下書き',
    'postStatusPublished'   => '公開済み',
    'postStatusScheduled'   => '予約済み',
    'postScheduledAt'       => '公開予定日時',
    'postFeatured'          => '注目記事',
    'postMembersOnly'       => 'メンバー限定',
    'postShareOnPublish'    => '公開時にソーシャルでシェア',
    'postSaveBtn'           => '記事を保存',
    'postFeaturedImage'     => 'アイキャッチ画像',
    'postFeaturedImagePlaceholder' => 'URLまたはアップロードパス…',
    'postCategories'        => 'カテゴリ',
    'postTags'              => 'タグ',
    'postTagsPlaceholder'   => 'タグ1, タグ2, タグ3',
    'postRevisions'         => 'リビジョン',
    'postRevisionCount'     => '{0} 件のリビジョン',
    'postPreview'           => 'プレビュー',
    'postBulkAction'        => '- アクションを選択 -',
    'postBulkPublish'       => '公開',
    'postBulkUnpublish'     => '非公開（下書きに変更）',
    'postBulkDelete'        => '削除',

    // Post flash messages
    'postCreated'           => '記事が正常に作成されました。',
    'postUpdated'           => '記事が更新されました。',
    'scheduledDateMustBeFuture' => '予約日時は将来の日時である必要があります。',
    'postDeleted'           => '記事が削除されました。',
    'postBulkUpdated'       => '{0} 件の記事が更新されました。',
    'postBulkInvalid'       => '無効な一括操作です。',
    'postPermission'        => '自分の記事のみ編集できます。',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'リビジョン：{0}',
    'revisionTitle'         => 'リビジョン — {0}',
    'revisionShowTitle'     => 'リビジョン',
    'revisionsBackToPost'   => '記事に戻る',
    'revisionsBackToList'   => 'リビジョン一覧に戻る',
    'revisionRestored'      => '{0} のリビジョンに記事を復元しました。',
    'revisionRestoreBtn'    => 'このリビジョンに復元',
    'revisionSaved'         => '保存済み',
    'revisionBy'            => '著者：',
    'revisionOn'            => '日時：',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'ページ',
    'newPageTitle'          => '新規ページ',
    'editPageTitle'         => 'ページを編集',
    'pageSlugInUse'         => "スラッグ '{0}' はすでに使用されています。",
    'pageCannotDelete'      => 'このページは削除できません。',
    'slugAutoGenHint'       => '空白の場合はタイトルから自動生成されます',
    'slugCannotChange'      => '変更不可',
    'colSystem'             => 'システム',
    'system'                => 'システム',

    // Page flash messages
    'pageCreated'           => 'ページが作成されました。',
    'pageUpdated'           => 'ページが更新されました。',
    'pageDeleted'           => 'ページが削除されました。',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'カテゴリ',
    'newCategoryTitle'      => '新規カテゴリ',
    'editCategoryTitle'     => 'カテゴリを編集',
    'categoryName'          => '名前',
    'categoryDescription'   => '説明',
    'categoryPostCount'     => '記事数',

    // Category flash messages
    'categoryCreated'       => 'カテゴリが作成されました。',
    'categoryUpdated'       => 'カテゴリが更新されました。',
    'categoryDeleted'       => 'カテゴリが削除されました。',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'タグ',
    'tagPostCount'          => '記事数',

    // Tag flash messages
    'tagDeleted'            => 'タグが削除されました。',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'コメント',
    'commentAuthor'         => '著者',
    'commentContent'        => 'コメント',
    'commentPost'           => '記事',
    'commentDate'           => '日付',
    'commentStatusFilter'   => 'ステータスで絞り込む',

    // Comment flash messages
    'commentApproved'       => 'コメントが承認されました。',
    'commentSpam'           => 'スパムとしてマークされました。',
    'commentTrashed'        => 'コメントがゴミ箱に移動されました。',
    'commentDeleted'        => 'コメントが完全に削除されました。',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'メディアライブラリ',
    'mediaTitle'            => 'タイトル',
    'mediaAltText'          => '代替テキスト',
    'mediaAltPlaceholder'   => 'アクセシビリティのために画像を説明してください',
    'mediaTitlePlaceholder' => '任意の画像タイトル',
    'mediaImageDetails'     => '画像の詳細',
    'mediaSaved'            => '保存しました！',
    'mediaNoSelection'      => '画像が選択されていません',
    'mediaBrowse'           => 'メディアを参照',
    'mediaRemove'           => '削除',
    'mediaUseImage'         => 'この画像を使用',
    'mediaDropzone'         => '画像をここにドラッグ＆ドロップするか、クリックして参照',
    'mediaLoading'          => 'メディアを読み込み中…',
    'mediaEmpty'            => 'まだメディアがアップロードされていません。',
    'mediaUpload'           => 'メディアをアップロード',
    'mediaDragDrop'         => 'ファイルをここにドラッグ＆ドロップするか、',
    'mediaChooseFiles'      => 'ファイルを選択',
    'mediaUploading'        => 'アップロード中…',
    'mediaFilename'         => 'ファイル名',
    'mediaSize'             => 'サイズ',
    'mediaUploadFailed'     => 'アップロード失敗：{0}',
    'mediaUploadError'      => 'アップロードエラー：{0}',

    // Media flash messages
    'mediaDeleted'          => 'メディアが削除されました。',
    'mediaNoValidFile'      => '有効なファイルがアップロードされていません。',
    'mediaUploadSuccess'    => 'ファイルが正常にアップロードされました。',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'ナビゲーション',
    'navQuickAdd'           => 'クイック追加',
    'navQuickAddPlaceholder' => 'ページ、カテゴリ、プラグインを検索...',
    'navItemLabel'          => 'ラベル',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'ターゲット',
    'navItemOrder'          => '表示順',
    'navGroupPrimary'       => 'メイン',
    'navGroupFooter'        => 'フッター',
    'navSelectGroup'        => 'ナビゲーショングループを選択：',
    'navParent'             => '親',
    'navTopLevel'           => '— トップレベル —',
    'navSameWindow'         => '同じウィンドウ',
    'navNewWindow'          => '新しいウィンドウ',
    'navMenuItems'          => 'メニュー項目',
    'navNoItems'            => 'このメニューに項目がありません。',
    'dragToReorder'         => 'ドラッグして並べ替え',

    // Navigation flash messages
    'navItemAdded'          => 'ナビゲーション項目が追加されました。',
    'navItemRemoved'        => 'ナビゲーション項目が削除されました。',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'テーマ',
    'themeOptions'          => 'テーマオプション',
    'themeActivate'         => '有効化',
    'themeOptionsBtn'       => 'オプション',
    'themeActive'           => '有効',
    'themeBy'               => '作者：',
    'themeSupport'          => 'サポート',
    'themeVersion'          => 'バージョン',
    'themeSaveOptions'      => 'オプションを保存',
    'themeInvalidLicense'   => 'テーマを有効化できません - ライセンスが無効です。再インストールするかサポートにお問い合わせください。',
    'themeValidationFailed' => 'テーマにPHPコードが含まれているため有効化できません。',
    'noThemesInstalled'     => 'テーマがインストールされていません。マーケットプレイスでテーマを入手してください。',
    'themeUnapprovedTitle'  => '未承認テーマを有効化しますか？',
    'themeNotApproved'      => 'このテーマはPubvanaによって承認されていません。',
    'themeUnapprovedRisk'   => '未承認テーマを有効化すると、セキュリティリスクや互換性の問題が生じる可能性があります。',
    'themeActivateConfirm'  => '本当に有効化しますか？',
    'themeActivateAnyway'   => 'それでも有効化',
    'themeNoOptions'        => 'このテーマには設定可能なオプションがありません。',
    'themeCustomize'        => 'テーマをカスタマイズ',

    // Theme flash messages
    'themeActivated'        => 'テーマが有効化されました。',
    'themeOptionsSaved'     => 'オプションが保存されました。',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'ライセンス済み',
    'licenseCheckNow'        => '今すぐ確認',
    'licenseExpired'         => '期限切れ',
    'licenseEnterKey'        => 'キーを入力',
    'licenseChangeKey'       => '変更',
    'licenseRenew'           => '更新',
    'licenseThirdParty'      => 'サードパーティ',
    'unchecked'              => '未確認',
    'safetyLabel'            => '安全性：',
    'recheckBtn'             => '再確認',
    'recheckSuccess'         => '安全性チェックが更新されました。',
    'recheckFailed'          => '検証サーバーに接続できませんでした。後でもう一度お試しください。',
    'recheckNotFound'        => 'アイテムが見つかりません。',
    'widgetBlockedMalicious' => '{0} は悪意ありとマークされており、追加できません。',
    'licenseNoStoreProduct'  => 'このアイテムはストア製品にリンクされていません。このアイテムを購入された場合は、マーケットプレイスから再インストールしてライセンスを有効にしてください。',
    'securityWarning'        => 'セキュリティ警告：',
    'licenseModalTitle'      => 'ライセンスキーを入力',
    'licenseModalBody'       => '以下にライセンスキーを貼り付けてください。',
    'licenseModalSave'       => '保存',
    'licenseSaved'           => 'ライセンスキーが保存・検証されました。',
    'licenseInvalid'         => 'ライセンスキーが無効です。',
    'licenseKeyRequired'     => 'ライセンスキーと製品が必要です。',
    'licenseCheckFailed'     => 'ライセンスサーバーに接続できません。後でもう一度お試しください。',
    'licenseProductNotFound' => 'ストアでこのアイテムが見つかりませんでした。',
    'btnCancel'              => 'キャンセル',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'ウィジェット',
    'widgetConfigureTitle'  => 'ウィジェットの設定',
    'widgetAreas'           => 'ウィジェットエリア',
    'widgetAvailable'       => '利用可能なウィジェット',
    'widgetAddToArea'       => 'エリアに追加',
    'widgetArea'            => 'エリア',
    'widgetNoOptions'       => 'オプションなし。',
    'widgetSaveConfig'      => '設定を保存',
    'widgetConfigure'       => '設定',
    'widgetNoAreas'         => 'ウィジェットエリアが見つかりません。テーマを有効化してウィジェットエリアを使用してください。',
    'widgetAreaEmpty'       => 'このエリアにウィジェットがありません。右のリストから追加してください →',

    // Widget flash messages
    'widgetAdded'           => 'ウィジェットが追加されました。',
    'widgetRemoved'         => 'ウィジェットが削除されました。',
    'widgetConfigured'      => 'ウィジェットが設定されました。',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'マーケットプレイス',
    'marketplaceRefresh'    => '更新',
    'marketplaceVisitStore' => 'ストアを見る',
    'marketplaceAll'        => 'すべて',
    'marketplaceThemes'     => 'テーマ',
    'marketplaceWidgets'    => 'ウィジェット',
    'marketplacePlugins'    => 'プラグイン',
    'marketplaceUpdatesAvailable' => '{0} 件のアップデートがあります。',
    'marketplaceBy'         => '作者：',
    'marketplaceFree'       => '無料',
    'marketplaceInstalled'  => 'インストール済み',
    'marketplaceInstall'    => 'インストール',
    'marketplaceBuyNow'     => '今すぐ購入',
    'marketplaceNoItems'    => 'マーケットプレイスにアイテムが見つかりません。',
    'marketplaceInstalledVersion' => 'v{0} インストール済み',
    'marketplaceLoadError'  => 'ストアから製品を読み込めません。後でもう一度お試しください。',
    'byAuthor'              => '{0} 著',
    'unknown'               => '不明',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} が正常にインストールされました。',
    'marketplaceInstallFail'    => 'インストールに失敗しました。ログをご確認ください。',
    'marketplaceUpdateSuccess'  => '正常に更新されました。',
    'marketplaceUpdateFail'     => '更新に失敗しました。',
    'marketplaceCacheRefreshed' => 'マーケットプレイスのキャッシュが更新されました。',
    'marketplaceInvalidRequest' => '無効なインストールリクエストです。',
    'marketplaceCannotUpdate'   => 'このアイテムを更新できません。',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'ライセンス',
    'licensesNone'                => 'ライセンスなし',
    'licensesProduct'             => '製品',
    'licensesKey'                 => 'ライセンスキー',
    'licensesStatus'              => 'ステータス',
    'licensesType'                => '種類',
    'licensesExpires'             => '有効期限',
    'licensesDomain'              => 'ドメイン',
    'licensesInstalled'           => 'インストール済み',
    'licensesLastChecked'         => '最終確認',
    'licensesActions'             => 'アクション',
    'licensesStatusValid'         => '有効',
    'licensesStatusInvalid'       => '無効',
    'licensesStatusExpired'       => '期限切れ',
    'licensesStatusSubExpired'    => 'サブスクリプション期限切れ',
    'licensesStatusUnchecked'     => '未確認',
    'licensesSubscription'        => 'サブスクリプション',
    'licensesOneTime'             => '買い切り',
    'licensesPerpetual'           => '永続',
    'licensesNotInstalled'        => '未インストール',
    'licensesNever'               => 'なし',
    'licensesRevalidate'          => '再検証',
    'licenseKeyPlaceholder'       => 'ライセンスキーを入力...',
    'marketplaceLicensesEmpty'    => 'ライセンス済みの製品はインストール後にここに表示されます。',
    'typeTheme'                   => 'テーマ',
    'typeWidget'                  => 'ウィジェット',
    'typePlugin'                  => 'プラグイン',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'ライセンスが正常に検証されました。',
    'licenseRevalidateInvalid'     => 'ライセンスが無効または期限切れです。',
    'licenseRevalidateUnreachable' => 'ライセンスサーバーに接続できません。後でもう一度お試しください。',
    'licenseRevalidateSkipped'     => 'ライセンス確認がスキップされました（開発モード）。',
    'licenseRevalidateNotFound'    => 'ライセンスが見つかりません。',

    // License warning banners
    'licenseWarningTitle'   => 'ライセンスの問題',
    'licenseWarningInvalid' => 'ライセンスが無効または期限切れです',
    'licenseWarningManage'  => 'ライセンスを管理',

    // Plugin license
    'pluginInvalidLicense' => 'このプラグインのライセンスが無効または期限切れのため、有効化できません。',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'ライセンスキー',
    'storeBrowseFull'       => 'ストア全体を見る',
    'storeBackToMarketplace'=> 'マーケットプレイスに戻る',
    'storeNoProducts'       => '利用可能な製品がありません。',
    'storeViewInStore'      => 'ストアで見る',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'ユーザー',
    'editUserTitle'         => 'ユーザーを編集',
    'createUserTitle'       => 'ユーザーを作成',
    'authorProfileTitle'    => '著者プロフィール',
    'userRoleLabel'         => 'ロール',
    'userActiveLabel'       => '有効',
    'userPasswordLabel'     => 'パスワード',
    'userPasswordOptional'  => '現在のパスワードを維持する場合は空白のままにしてください',
    'userDisplayName'       => '表示名',
    'userBio'               => '自己紹介',
    'userWebsite'           => 'ウェブサイト',
    'userTwitter'           => 'Twitter / X ハンドル',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'アバター',
    'userSaveProfile'       => 'プロフィールを保存',
    'userSaveChanges'       => '変更を保存',
    'userCannotDeleteSelf'  => '自分自身を削除することはできません。',
    'userCannotDeleteOwner' => 'サイトオーナーのアカウントは削除できません。',
    'userOwnerCannotModify' => 'サイトオーナーのアカウントは変更できません。',

    // User flash messages
    'userCreated'           => 'ユーザーが作成されました。',
    'userUpdated'           => 'ユーザーが更新されました。',
    'userDeleted'           => 'ユーザーが削除されました。',
    'userBanned'            => 'ユーザーがBANされました。',
    'userUnbanned'          => 'ユーザーのBANが解除されました。',
    'userCannotBanSelf'     => '自分自身またはサイトオーナーをBANすることはできません。',
    'banStatus'             => 'BANステータス',
    'banned'                => 'BAN済み',
    'ban'                   => 'ユーザーをBAN',
    'unban'                 => 'BAN解除',
    'banReasonRequired'     => 'BAN理由が必要です。',
    'banReasonPlaceholder'  => 'BAN理由...',
    'confirmBanUser'        => 'このユーザーをBANしてもよろしいですか？',
    'userProfileSaved'      => 'プロフィールが保存されました。',
    'userAvatarUploadFail'  => 'アバターのアップロードに失敗しました：{0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA設定',
    'tfaSetupHeading'       => '二要素認証を設定',
    'tfaScanQr'             => '認証アプリ（Google Authenticator、Authyなど）で下のQRコードをスキャンしてください。',
    'tfaManualEntry'        => 'またはシークレットキーを手動で入力：',
    'tfaEnterCode'          => '確認のためアプリの6桁コードを入力：',
    'tfaCodeLabel'          => '認証コード',
    'tfaConfirmBtn'         => '確認して2FAを有効化',
    'tfaDisableBtn'         => '2FAを無効化',
    'tfaDisableConfirm'     => '無効化するため現在の2FAコードを入力：',
    'tfaEnabled'            => '二要素認証が有効化されました。',
    'tfaDisabled'           => '二要素認証が無効化されました。',
    'tfaInvalidCode'        => '無効なコードです - QRコードをスキャンしてもう一度お試しください。',
    'tfaInvalidDisable'     => '無効なコードです - 2FAは無効化されませんでした。',
    'tfaSessionExpired'     => '設定セッションが期限切れです - 最初からやり直してください。',
    'tfaNotEnabled'         => '2FAは現在有効ではありません。',
    'tfaCantScan'           => 'スキャンできませんか？このコードを手動で入力してください：',
    'tfaWarning'            => 'このシークレットキーを安全な場所に保管してください。認証デバイスを紛失した場合のアクセス回復に必要です。',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'ソーシャルリンク',
    'socialPlatform'           => 'プラットフォーム',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'アイコン',
    'socialSortOrder'          => '表示順',
    'socialIconPackInfo'       => '現在のテーマ <strong>{0}</strong> はアイコンに <strong>{1}</strong>（v{2}）を使用しています。以下でこのサイトのソーシャルリンク機能に表示されるアイコンを選択できます。',
    'socialSearchPlaceholder'  => 'プラットフォームを検索...',
    'socialIconDisclaimer'     => 'これらのアイコンは使用されるアイコンの表現にすぎません。実際のアイコンは有効なテーマのアイコンパックによって異なる場合があります。',

    // Social flash messages
    'socialLinkAdded'       => 'ソーシャルリンクが追加されました。',
    'socialLinkUpdated'     => 'リンクが更新されました。',
    'socialLinkDeleted'     => 'リンクが削除されました。',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'リダイレクト',
    'redirectFrom'          => 'リダイレクト元URL',
    'redirectTo'            => 'リダイレクト先URL',
    'redirectType'          => '種類',
    'redirectAdd'           => 'リダイレクトを追加',
    'redirectFromHint'      => '（相対パス、例：/old-page）',
    'redirect301'           => '301 恒久的',
    'redirect302'           => '302 一時的',
    'redirectInvalidDest'   => 'リダイレクト先URLが無効です。',

    // Redirect flash messages
    'redirectAdded'         => 'リダイレクトが追加されました。',
    'redirectDeleted'       => 'リダイレクトが削除されました。',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => '設定',
    'settingsGeneral'       => '一般',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'メール',
    'settingsSocialLogin'   => 'ソーシャルログイン',
    'settingsSocialSharing' => 'ソーシャルシェア',
    'settingsSpam'          => 'スパム対策',

    'generalSettingsHeading'    => '一般設定',
    'generalSiteName'           => 'サイト名',
    'generalTagline'            => 'タグライン',
    'generalAdminEmail'         => '管理者メールアドレス',
    'generalPostsPerPage'       => '1ページあたりの記事数',
    'generalComments'           => 'コメント',
    'generalCommentsEnable'     => 'コメントを有効にする',
    'generalCommentModeration'  => '公開前に承認を必要とする',
    'generalMaintenanceMode'    => 'メンテナンスモード',
    'generalMaintenanceEnable'  => 'メンテナンスモードを有効にする',
    'generalMaintenanceHelp'    => '訪問者には「まもなく戻ります」ページが表示されます。管理者は引き続きサイトにアクセスできます。',
    'generalFrontPage'          => 'トップページ',
    'generalFrontPageBlog'      => 'ブログインデックス（最新記事）',
    'generalFrontPageStatic'    => '固定ページ：',
    'generalFrontPagePlugin'    => 'プラグインページ：',
    'generalSelectPage'         => '- ページを選択 -',
    'generalSelectRoute'        => '- ルートを選択 -',
    'generalFrontPageNoPlugins' => 'プラグインルートがありません',
    'generalPageCacheTtl'       => 'ページキャッシュTTL',
    'settingsCacheTtlHint'      => '秒。0 = 無効。',
    'generalSaveBtn'            => '一般設定を保存',

    // General flash messages
    'generalSettingsSaved'      => '一般設定が保存されました。',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO設定',
    'seoMetaDescription'        => 'メタ説明',
    'seoGoogleAnalytics'        => 'Google アナリティクスID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'サイトマップ',
    'seoSitemapEnable'          => 'sitemap.xmlを有効にする',
    'seoSitemapHelp'            => '公開済みのすべての記事とページの標準サイトマップ。',
    'seoNewsSitemap'            => 'news-sitemap.xmlを有効にする',
    'seoNewsSitemapHelp'        => 'Google ニュースサイトマップ - 過去48時間以内に公開された記事を一覧表示します。',
    'seoSaveBtn'                => 'SEO設定を保存',
    'seoSettingsSaved'          => 'SEO設定が保存されました。',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'メール設定',
    'emailFromName'             => '送信者名',
    'emailFromAddress'          => '送信者アドレス',
    'emailProtocol'             => 'プロトコル',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTPホスト',
    'emailSmtpPort'             => 'SMTPポート',
    'emailSmtpEncryption'       => '暗号化',
    'emailSmtpEncryptionNone'   => 'なし',
    'emailSmtpUsername'         => 'SMTPユーザー名',
    'emailSmtpPassword'         => 'SMTPパスワード',
    'emailProvider'             => 'メールプロバイダー',
    'emailProviderCore'         => 'コア（デフォルト）',
    'emailProviderHelp'         => '送信メールの配信を担当するプラグインを選択してください。',
    'emailSaveBtn'              => 'メール設定を保存',
    'emailSettingsSaved'        => 'メール設定が保存されました。',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'ソーシャルログイン（OAuth）',
    'socialLoginHelp'           => '認証情報は.envファイルに保存されます。GoogleとFacebookでアプリを登録してクライアントIDとシークレットを取得してください。',
    'socialLoginGoogleId'       => 'クライアントID',
    'socialLoginGoogleSecret'   => 'クライアントシークレット',
    'socialLoginFbAppId'        => 'アプリID',
    'socialLoginFbAppSecret'    => 'アプリシークレット',
    'socialLoginPlaceholderSecret' => '（既存の値を維持する場合は空白）',
    'socialLoginSaveBtn'        => 'ソーシャルログイン設定を保存',
    'socialLoginSettingsSaved'  => 'ソーシャルログイン設定が保存されました。',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => '公開時のソーシャル自動シェア',
    'socialSharingHelp'         => '「公開時にシェア」にチェックを入れて記事を公開すると、Pubvanaは設定されたソーシャルアカウントに自動投稿します。',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'developer.twitter.com → アプリ → キーとトークンでキーを取得してください。',
    'socialSharingApiKey'       => 'APIキー',
    'socialSharingApiSecret'    => 'APIシークレット',
    'socialSharingAccessToken'  => 'アクセストークン',
    'socialSharingAccessSecret' => 'アクセスシークレット',
    'socialSharingFbPage'       => 'Facebookページ',
    'socialSharingFbPageHelp'   => 'pages_manage_posts権限を持つページアクセストークンが必要です。',
    'socialSharingFbPageId'     => 'ページID',
    'socialSharingFbPageToken'  => 'ページアクセストークン',
    'socialSharingSaveBtn'      => 'シェア設定を保存',
    'socialSharingSettingsSaved'=> 'ソーシャルシェア設定が保存されました。',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'スパム対策（hCaptcha）',
    'spamHcaptchaIntro'         => 'Pubvanaはスパムボットからコメントフォームとお問い合わせフォームを保護するためhCaptcha（プライバシー重視、非Google）を使用しています。',
    'spamHcaptchaFree'          => 'hCaptchaはほとんどのサイトで無料です。hcaptcha.comに登録した後、サイトキーは Account → Sites → Add Site から、シークレットキーは Account → Settings → Secret Key → Generate から取得できます。両方を以下に入力してください。',
    'spamHcaptchaSiteKey'       => 'サイトキー',
    'spamHcaptchaSecretKey'     => 'シークレットキー',
    'spamHcaptchaNote'          => 'これらのキーが設定されていない場合、hCaptchaはサイレントにスキップされます — ローカル開発に安全です。保存後、ウィジェットはコメントフォームとお問い合わせページに自動的に表示されます。',
    'spamSettingsSaved'         => 'スパム対策設定が保存されました。',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => '言語',
    'languageCode'              => 'コード',
    'languageName'              => '名前',
    'languageDefault'           => 'デフォルト',
    'languageEnabled'           => '有効',
    'languageMakeDefault'       => 'デフォルトに設定',
    'languageSetAsDefault'      => '{0} がデフォルト言語に設定されました。',
    'languageEnabled_msg'       => '{0} が有効になりました。',
    'languageDisabled_msg'      => '{0} が無効になりました。',
    'languageNotFound'          => '言語が見つかりません。',
    'languageCannotDisable'     => 'デフォルト言語は無効にできません。',
    'languageDirection'         => '方向',
    'languageNativeName'        => 'ネイティブ名',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'アナリティクス',
    'analyticsTotalViews'       => '総閲覧数',
    'analyticsTopPosts'         => '人気記事',
    'analyticsReferrers'        => '主要リファラー',
    'analyticsLast7'            => '過去7日間',
    'analyticsLast30'           => '過去30日間',
    'analyticsLast90'           => '過去90日間',
    'analyticsChartTitle'       => 'ページビュー',
    'analyticsNoData'           => 'この期間のアナリティクスデータがありません。',
    'analyticsDomain'           => 'ドメイン',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'アフィリエイトリンク',
    'newAffiliateLinkTitle'     => '新規アフィリエイトリンク',
    'editAffiliateLinkTitle'    => 'アフィリエイトリンクを編集',
    'affiliateName'             => '名前',
    'affiliateSlug'             => 'スラッグ',
    'affiliateDestination'      => '転送先URL',
    'affiliateActive'           => '有効',
    'affiliateClicks'           => 'クリック数',
    'affiliateClicksTitle'      => 'クリック数 - {0}',
    'affiliateTotal'            => '合計',
    'affiliateViewClicks'       => 'クリック数を見る',

    // Affiliate flash messages
    'affiliateCreated'          => 'アフィリエイトリンクが作成されました。',
    'affiliateUpdated'          => 'アフィリエイトリンクが更新されました。',
    'affiliateDeleted'          => 'アフィリエイトリンクが削除されました。',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'リンク切れ',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTPステータス',
    'brokenLinkError'           => 'エラー',
    'brokenLinkSource'          => 'ソース',
    'brokenLinkShowDismissed'   => '非表示を表示',
    'brokenLinkHideDismissed'   => '非表示を隠す',
    'brokenLinkTimeout'         => 'タイムアウト',
    'brokenLinkBroken'          => 'リンク切れ',
    'brokenLinkNone'            => 'リンク切れは検出されませんでした。',
    'brokenLinkNowReachable'    => 'リンクが到達可能になりました - 結果から削除されました。',
    'brokenLinkStillBroken'     => 'リンクはまだ切れています（{0}）。',
    'brokenLinkDismissed'       => 'リンクを非表示にしました。',
    'brokenLinksCliHint'        => 'このレポートに入力するにはコマンドラインからフルスキャンを実行してください：<code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} 件の問題が見つかりました',
    'brokenLinksCount'          => '{0} 件のリンク切れ',
    'brokenLinksRecheck'        => 'このURLを再確認',
    'brokenLinksDismiss'        => '非表示（結果から隠す）',
    'brokenLinksRunScan'        => 'スキャン実行',
    'brokenLinksScanComplete'   => 'スキャン完了：{0} 件のリンクを確認、{1} 件のリンク切れ。',
    'timeout'                   => 'タイムアウト',
    'typePost'                  => '記事',
    'typePage'                  => 'ページ',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'アクティビティログ',
    'activityLogType'           => '種類',
    'activityLogAction'         => 'アクション',
    'activityLogUser'           => 'ユーザー',
    'activityLogDate'           => '日付',
    'activityLogNote'           => 'メモ',
    'activityLogFilterAll'      => 'すべての種類',
    'activityLogEmpty'          => 'まだアクティビティが記録されていません。',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'バックアップとエクスポート',
    'backupDownload'            => 'バックアップを作成してダウンロード',
    'backupFiles'               => '利用可能なバックアップ',
    'backupFilename'            => 'ファイル名',
    'backupSize'                => 'サイズ',
    'backupDate'                => '作成日',
    'backupGenerating'          => 'バックアップを生成中…',
    'backupNoFiles'             => '保存されたバックアップがありません。',
    'backupFailed'              => 'バックアップに失敗しました：{0}',
    'backupDeleted'             => 'バックアップが削除されました。',
    'backupCannotDelete'        => 'バックアップを削除できませんでした。',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IPはSHA-256ハッシュとして保存されます — 生の個人情報は記録されません。',
    'colTime'                   => '時刻',
    'colIpHash'                 => 'IPハッシュ',
    'colReferrer'               => 'リファラー',
    'affiliateDirectReferrer'   => 'ダイレクト',
    'affiliateNameHint'         => '内部ラベル — 訪問者には表示されません。',
    'affiliateSlugHint'         => '英字、数字、ハイフン、アンダースコアのみ。リンクを共有した後は変更できません。',
    'affiliateDestHint'         => 'https://を含める必要があります。訪問者は301リダイレクトでここに転送されます。',
    'affiliateInactiveHint'     => '無効なリンクは404を返します。',
    'affiliateLinkCount'        => '{0} 件のリンク',
    'colDomain'                 => 'ドメイン',
    'commentAll'                => 'すべて',
    'commentPending'            => '承認待ち',
    'commentTrash'              => 'ゴミ箱',
    'commentsNone'              => '{0} コメントはありません。',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'バックアップを作成',
    'backupStarting'            => 'バックアップを開始中...',
    'backupNoneYet'             => 'まだバックアップがありません。「バックアップを作成」をクリックして最初のバックアップを作成してください。',
    'backupsTitle'              => 'バックアップ',
    'backupRetentionNote'       => '最大15件のバックアップを保持 — 古いものは自動的に削除されます。',
    'backupRestoreConfirm'      => 'このバックアップを復元しますか？現在の状態のバックアップが先に作成されます。',
    'backupDeleteConfirm'       => 'このバックアップを削除しますか？',
    'colFilename'               => 'ファイル名',
    'colVersion'                => 'バージョン',
    'colTrigger'                => 'トリガー',
    'colSize'                   => 'サイズ',
    'colDate'                   => '日付',
    'colActions'                => 'アクション',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'インポート',
    'importWpHeading'           => 'WordPressからインポート',
    'importWpHelp'              => 'ツール → エクスポートでWordPressサイトをエクスポートし、以下に.xmlファイルをアップロードしてください。',
    'importChooseFile'          => 'WXRファイル（.xml）を選択',
    'importDryRun'              => 'ドライラン（プレビューのみ - 何も保存されません）',
    'importRunBtn'              => 'インポートを実行',
    'importNoValidFile'         => '有効なWordPress WXRエクスポートファイルをアップロードしてください。',
    'importOnlyXml'             => '.xmlファイルのみ受け付けています。',
    'importFileTooLarge'        => 'インポートファイルが大きすぎます。最大サイズは50MBです。',
    'importResultsHeading'      => 'インポート結果',
    'importDryRunNote'          => 'ドライラン - データは保存されませんでした。',
    'importDryRunLabel'         => '（ドライラン — データは書き込まれていません）',
    'importComplete'            => 'インポート完了',
    'importCreated'             => '作成済み',
    'importSkipped'             => 'スキップ済み',
    'importErrors'              => 'エラー：',
    'importInstructions'        => '<strong>ツール → エクスポート → すべてのコンテンツ</strong>からWordPressコンテンツをエクスポートし、<code>.xml</code>ファイルをここにアップロードしてください。Pubvanaは記事、ページ、カテゴリ、タグ、著者、コメントをインポートします。',
    'importCliTitle'            => 'CLIインポート',
    'importCliHint'             => 'コマンドラインからインポーターを実行することもできます：',
    'importCliDryRunHint'       => '<code>--dry-run</code>フラグはデータベースに書き込まずにインポートされる内容を表示します。',
    'importWhatTitle'           => 'インポートされるもの',
    'importItemPosts'           => '記事（タイトル、コンテンツ、抜粋、スラッグ、ステータス）',
    'importItemPages'           => 'ページ',
    'importItemCategories'      => 'カテゴリ（階層付き）',
    'importItemTags'            => 'タグ',
    'importItemAuthors'         => '著者（サブスクライバーアカウントとして作成）',
    'importItemComments'        => 'コメント',
    'importItemMedia'           => 'メディアファイル（コンテンツ内のURLを保持）',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'アップデート',
    'updatesCurrentVersion'     => '現在のバージョン',
    'updatesLatestVersion'      => '最新バージョン',
    'updatesUpToDate'           => 'Pubvanaは最新版です。',
    'updatesAvailable'          => 'アップデートあり：{0}',
    'updatesCheckBtn'           => 'アップデートを確認',
    'updatesReleaseNotes'       => 'リリースノート',
    'updatesHowToApply'         => 'アップデートの適用方法',
    'updatesCacheCleared'       => 'アップデートキャッシュをクリアしました - 再確認中。',
    'updatesExtCapped'          => 'アップデートあり：{0}（アドオン対応）',
    'updatesNewerAvailable'     => 'Pubvana {0} が利用可能です - 以下のアドオンを更新してロック解除してください。',

    // Addon Updates
    'updatesExtTitle'               => 'アドオン',
    'updatesExtCheckAll'            => 'すべて確認',
    'updatesExtUpdateAll'           => 'すべて更新',
    'updatesExtCheckAllType'        => 'すべての{0}を確認',
    'updatesExtUpdateAllType'       => 'すべての{0}を更新',
    'updatesExtNoInstalled'         => '{0} がインストールされていません。',
    'updatesExtColName'             => '名前',
    'updatesExtColVersion'          => 'バージョン',
    'updatesExtColLatest'           => '最新',
    'updatesExtColAutoUpdate'       => '自動更新',
    'updatesExtColStatus'           => 'ステータス',
    'updatesExtColActions'          => 'アクション',
    'updatesExtBundled'             => 'コア同梱',
    'updatesExtNoSource'            => '更新ソースなし',
    'updatesExtFailed'              => '失敗',
    'updatesExtUpdatedAt'           => '{0} に更新済み',
    'updatesExtAvailable'           => 'アップデートあり',
    'updatesExtUpToDate'            => '最新版',
    'updatesExtUpdate'              => '更新',
    'updatesExtChecking'            => '確認中...',
    'updatesExtUpdating'            => '更新中...',
    'updatesExtUpdated'             => '更新済み',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'アップデートの確認',
    'updatesConfirmBody'            => 'サイトをバックアップし、アップデートをダウンロードして適用します。',
    'updatesConfirmSafe'            => '<code>.env</code>、<code>App.php</code>、<code>Database.php</code>は上書きされません。',
    'updatesConfirmBtn'             => '今すぐ更新',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'すべてのアドオンを更新',
    'updatesExtAllBody'             => '保留中のアップデートがあるすべてのアドオンを更新します。',
    'updatesExtAllNote'             => '自動更新が無効なアドオンも更新されます。',
    'updatesExtAllBtn'              => 'すべて更新',

    'updatesExtBadge'               => '更新：v{0}',
    'updatesExtGoToUpdates'         => 'アップデート',

    // Update Settings
    'updatesSettingsTitle'          => 'アップデート設定',
    'updatesAutoUpdateLabel'        => 'Pubvana自動更新',
    'updatesAutoUpdateManual'       => '手動',
    'updatesAutoUpdateAuto'         => '自動',
    'updatesAutoUpdateHelp'         => '有効にすると、破壊的変更のないPubvanaのアップデートが自動的に適用されます。',
    'updatesCheckMethodLabel'       => 'アップデート確認方法',
    'updatesCheckMethodPageload'    => 'ページ読み込み',
    'updatesCheckMethodCron'        => 'Cronジョブ',
    'updatesCheckMethodHelp'        => 'ページ読み込みはすべてのリクエストで確認します（24時間キャッシュ）。Cronはサーバーのcronジョブが必要です。',
    'updatesCronCommand'            => 'Cronコマンド',
    'updatesCronHelp'               => '毎日アップデート確認を実行するにはこれをサーバーのcrontabに追加してください：',
    'updatesSettingsSaved'          => 'アップデート設定が保存されました。',

    // Compatibility
    'compatWarningTitle'            => '互換性の警告',
    'compatNotCompatible'           => 'インストール済みのアドオンの一部がこのバージョンと互換性がありません。',
    'compatRequiresUpdate'          => 'しかし、次のアドオンを先に更新する必要があります：',
    'compatSupportsUpTo'            => '{0} まで対応',
    'compatRequiresMin'             => 'Pubvana {0}以上が必要',
    'compatNotDeclared'             => '次のアドオンはPubvana {0}との互換性を宣言していません。アップデート後に動作しなくなる可能性があります：',
    'compatColType'                 => '種類',
    'compatColName'                 => '名前',
    'compatColVersion'              => '互換性',
    'compatRemoveHint'              => '問題が発生した場合は互換性のないアドオンを削除するかデフォルトテーマに切り替えることができます。すべてのアップデート前にバックアップが作成されます。',
    'compatMaxVersion'              => '最大互換バージョン：{0}',
    'compatMinVersion'              => 'Pubvana {0}以上が必要',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => '記事スケジュール',
    'scheduleNoScheduled'       => '予約済みの記事はありません。',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'リビジョン - {0}',
    'revisionPageTitle'         => 'リビジョン - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => '管理パネルにアクセスするにはログインが必要です。',
    'dirNotWritable'            => 'ディレクトリが書き込み不可です：{0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} が正しく設定されていません。エンドユーザーの場合は開発者に連絡してください。開発者の場合はドキュメントを参照してください。',
    'addonMisconfiguredLink'    => '{0} が正しく設定されていません。エンドユーザーの場合は<a href="{1}">開発者に連絡</a>してください。開発者の場合は<a href="https://github.com/enlivenapp/pubvana">ドキュメントを参照</a>してください。',
    'licenseExpiringSoon'       => '{0} のライセンスは {1} に期限切れになります。ライセンスが期限切れになると {0} は無効化されます。',
    'licenseExpiredDeactivated' => 'ライセンスが期限切れのため {0} が無効化されました。',
    'addonDeactivated'          => '{0} が無効化されました。理由：{1}。',
    'widgetValidationFailed'    => "ウィジェット ''{0}'' を検証できませんでした。開発者に連絡するかアドオンを削除してください。",
    'widgetValidationFailedLink' => "ウィジェット ''{0}'' を検証できませんでした。<a href=\"{1}\">開発者に連絡する</a>かアドオンを削除してください。",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => '無効化済み：ライセンス期限切れ',
    'addonDeactivatedTampered'  => '無効化済み：設定エラー',
    'addonDeactivatedNoLicense' => '無効化済み：有効なライセンスなし',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => '無効',
    'addonDisabledInvalidJson'  => 'システム：{0} の {1} が無効または読み取り不可です。',
    'addonDisabledMissingFields' => 'システム：{0} に必須フィールドがありません：{1}。',
    'addonDisabledPhpFiles'     => 'システム：{0} にPHPファイルが含まれています。ウィジェットはJSONとテンプレートのみで構成されている必要があります。',

    // Flash messages (on activation attempt)
    'licenseRequired'           => '{0} を有効化するには有効なライセンスが必要です。',
    'licenseInvalidActivation'  => '{0} のライセンス検証に失敗しました。ライセンスキーをご確認ください。',
    'licenseExpiredActivation'  => '{0} のライセンスが期限切れです。有効化するには更新してください。',
    'licenseCheckUnreachable'   => '{0} のライセンスを確認できませんでした。ライセンスサーバーに接続できません。後でもう一度お試しください。',
    'activationBlockedTampered' => '{0} は設定エラーのため有効化できません。',
    'activationBlockedBundled'  => '{0} は有効化できません：Pubvanaアドオンのみバンドル済みとしてマークできます。',
    'activationBlockedNoUrls'   => '{0} は有効化できません：有料アドオンにはライセンス認証URLが必要です。',
    'activationBlockedFreeFlag' => '{0} は有効化できません：Pubvanaアドオンは無料とマークできません。',
    'activationBlockedDisabled' => '{0} は設定エラーのため有効化できません。情報ファイルをご確認ください。',

    // Third-party license
    'licenseThirdPartyLabel'    => 'サードパーティ',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'アップデートを開始中...',
    'updateCheckLabel'           => 'アップデート確認：',
    'updateAvailable'            => 'Pubvana {0} が利用可能です！',
    'updateRunning'              => '現在 {0} を実行中です。',
    'updateBreakingChanges'      => '破壊的変更',
    'updateMigrationNotes'       => 'マイグレーションノート',
    'updateNotices'              => '通知',
    'updatePreflightTitle'       => '事前確認',
    'updateToVersion'            => 'Pubvana {0} に更新',
    'updatePreflightFailed'      => '必要な事前確認が1つ以上失敗しました。更新前に解決してください。',
    'updateUpToDate'             => 'Pubvanaは最新版です。バージョン {0} を実行中です。',
    'updateAnyway'               => 'それでも更新',
    'updateAvailableTooltip'     => 'Pubvana {0} 利用可能',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '（あなた）',
    'usersNone'                  => 'ユーザーが見つかりません。',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'アカウント有効',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'プロフィール詳細',
    'profileDisplayNameHint'     => '公開記事でユーザー名の代わりに表示されます。',
    'profileAvatarHint'          => 'JPEG、PNG、WebPまたはGIF。最大10MB。',
    'profileSocialHandles'       => 'ソーシャルハンドル',
    'preview'                    => 'プレビュー',
    'website'                    => 'ウェブサイト',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => '二要素認証',
    'totpActiveDesc'             => 'TOTP二要素認証がアカウントで有効です。ログインのたびに認証アプリから6桁のコードを求められます。',
    'totpCurrentCode'            => '現在のコード',
    'totpInactiveDesc'           => 'アカウントにセキュリティの追加レイヤーを追加します。有効にすると、各ログイン時に認証アプリからコードを入力する必要があります。',
    'totpEnable'                 => '二要素認証を有効化',
    'totpScanInstructions'       => '認証アプリ（Google Authenticator、Authy、1Passwordなど）を開き、このQRコードをスキャンしてください。',
    'totpManualEntry'            => 'スキャンできませんか？このコードを手動で入力してください：',
    'totpConfirmInstructions'    => 'スキャン後、設定を確認するためにアプリに表示される6桁のコードを入力してください。',
    'totpRecoveryWarning'        => 'リカバリーコードを保管してください。認証アプリへのアクセスを失った場合、ログインできなくなります。サイト管理者に連絡して2FAをリセットしてください。',

];
