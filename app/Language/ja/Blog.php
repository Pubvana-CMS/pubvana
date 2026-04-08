<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Japanese)
 *
 * Covers: blog listing, post detail, category, tag, archive, search,
 *         contact form, comments, pagination, paywall, preview mode,
 *         author card, and shared public UI elements.
 *
 * Also incorporates the single key from the old pages_lang.php (readMore).
 *
 * Usage: lang('Blog.keyName')
 */

return [

    // =========================================================================
    // Common public UI
    // =========================================================================

    'home'              => 'ホーム',
    'blog'              => 'ブログ',
    'readMore'          => '続きを読む',
    'viewAll'           => 'すべて見る',
    'noPostsYet'        => 'まだ記事がありません。またご確認ください！',
    'search'            => '検索',
    'searchPlaceholder' => '検索…',
    'searchPostsPlaceholder' => '記事を検索…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'サイトマップ',
    'allRightsReserved' => '全著作権所有。',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => '最新の記事',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => '投稿日',
    'views'             => '{0} 回閲覧',
    'readingTime'       => '{0} 分で読めます',
    'publishedBy'       => '著者：',
    'inCategory'        => 'カテゴリ：',
    'tags'              => 'タグ',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'プレビューモード - この記事は公開されていません',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'プレミアムコンテンツ',
    'paywallMessage'        => 'このコンテンツはプレミアム会員向けです。',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => '著者について',
    'unknownAuthor'     => '不明な著者',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'カテゴリ：{0}',
    'noPostsInCategory' => 'このカテゴリにはまだ記事がありません。',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'タグ：{0}',
    'noPostsWithTag'    => 'このタグの記事はありません。',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'アーカイブ：{0}',
    'noPostsInPeriod'   => 'この期間の記事はありません。',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => '検索結果',
    'searchShowingFor'      => '「{0}」の検索結果',
    'searchNoResults'       => '「{0}」に一致する記事は見つかりませんでした。',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'コメント（{0}）',
    'commentsClosed'        => 'コメントは締め切られています。',
    'commentFormTitle'      => 'コメントを投稿',
    'commentLabel'          => 'コメント *',
    'commentPostBtn'        => 'コメントを送信',
    'commentModerated'      => 'コメントは公開前に承認が必要です。',
    'commentLoginRequired'  => 'コメントするにはログインしてください。',
    'commentLoginLink'      => 'ログイン',
    'commentAwaitModeration'=> 'コメントは承認待ちです。',
    'commentPosted'         => 'コメントが投稿されました。',
    'commentLoginToComment' => 'コメントするにはログインが必要です。',
    'commentTooFast'        => 'コメントが速すぎます。数分待ってからもう一度お試しください。',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'お問い合わせ',
    'contactName'           => 'お名前',
    'contactEmail'          => 'メールアドレス',
    'contactMessage'        => 'メッセージ',
    'contactSendBtn'        => 'メッセージを送信',
    'contactSent'           => 'メッセージが送信されました！',
    'contactCaptchaFail'    => 'キャプチャの確認に失敗しました。もう一度お試しください。',
    'contactSubject'        => 'お問い合わせフォーム：{0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'ページナビゲーション',
    'prevPage'          => '前へ',
    'nextPage'          => '次へ',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'ページが見つかりません。',
    'pageNotFoundTitle' => '404 - ページが見つかりません',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'メンテナンス中',
    'maintenanceBody'   => '定期メンテナンスを実施中です。まもなく復旧します - ご理解ありがとうございます！',

    // Language
    'language'          => '言語',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'マイプロフィール',
    'profileBasicInfo'        => '基本情報',
    'profileUsername'          => 'ユーザー名',
    'profileEmail'            => 'メールアドレス',
    'profilePassword'         => 'パスワード',
    'profilePasswordConfirm'  => 'パスワードの確認',
    'profilePasswordHelp'     => '現在のパスワードを維持する場合は空白のままにしてください。',
    'profileSave'             => '変更を保存',
    'profileUpdated'          => 'プロフィールが正常に更新されました。',
    'profileUsernameRequired' => 'ユーザー名は必須です。',
    'profileUsernameTaken'    => 'そのユーザー名はすでに使用されています。',
    'profileEmailRequired'    => 'メールアドレスは必須です。',
    'profileEmailTaken'       => 'そのメールアドレスはすでに使用されています。',
    'profilePasswordMismatch' => 'パスワードが一致しません。',
    'profilePasswordTooShort' => 'パスワードは8文字以上である必要があります。',

    'profileAuthorInfo'       => '著者プロフィール',
    'profileDisplayName'      => '表示名',
    'profileBio'              => '自己紹介',
    'profileAvatar'           => 'アバター',
    'profileAvatarChange'     => 'アバターを変更',
    'profileAvatarUpload'     => 'アップロード',
    'profileWebsite'          => 'ウェブサイト',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'アバターが正常に更新されました。',
    'profileAvatarInvalid'    => '無効なファイルアップロードです。',
    'profileAvatarTypeError'  => 'JPEG、PNG、WebP、GIF形式の画像のみ受け付けています。',
    'profileAvatarTooLarge'   => 'アバターは2MB以下である必要があります。',
    'profileAvatarNotAllowed' => 'アバターのアップロードは著者以上のロールで利用可能です。',

    'login'                         => 'ログイン',
    'adminPanel'                    => '管理パネル',

    'profileUpdatedRelogin'         => 'プロフィールが更新されました。再度ログインしてください。',
    'profileUsernameChangedSubject' => 'ユーザー名が変更されました',
    'profileUsernameChangedBody'    => 'ユーザー名が「{0}」から「{1}」に変更されました。この変更に心当たりがない場合は、直ちにサイト管理者にご連絡ください。',
    'profileEmailChangedSubject'    => 'メールアドレスが変更されました',
    'profileEmailChangedBody'       => 'メールアドレスが「{0}」から「{1}」に変更されました。この変更に心当たりがない場合は、直ちにサイト管理者にご連絡ください。',
    'profilePasswordChangedSubject' => 'パスワードが変更されました',
    'profilePasswordChangedBody'    => '最近パスワードが変更されました。この変更に心当たりがない場合は、直ちにサイト管理者にご連絡ください。',

];
