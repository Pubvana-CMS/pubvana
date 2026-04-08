<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Mandarin Chinese - Simplified)
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

    'home'              => '首页',
    'blog'              => '博客',
    'readMore'          => '阅读更多',
    'viewAll'           => '查看全部',
    'noPostsYet'        => '暂无文章，请稍后再来！',
    'search'            => '搜索',
    'searchPlaceholder' => '搜索…',
    'searchPostsPlaceholder' => '搜索文章…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => '站点地图',
    'allRightsReserved' => '版权所有。',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => '最新文章',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => '发布于',
    'views'             => '{0} 次浏览',
    'readingTime'       => '{0} 分钟阅读',
    'publishedBy'       => '作者',
    'inCategory'        => '分类',
    'tags'              => '标签',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => '预览模式 - 此文章未公开显示',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => '付费内容',
    'paywallMessage'        => '此内容仅对付费会员开放。',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => '关于作者',
    'unknownAuthor'     => '未知作者',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => '分类：{0}',
    'noPostsInCategory' => '该分类暂无文章。',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => '标签：{0}',
    'noPostsWithTag'    => '暂无此标签的文章。',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => '归档：{0}',
    'noPostsInPeriod'   => '该时间段暂无文章。',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => '搜索结果',
    'searchShowingFor'      => '正在显示以下内容的结果：{0}',
    'searchNoResults'       => '未找到"{0}"的相关文章。',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => '评论（{0}）',
    'commentsClosed'        => '评论已关闭。',
    'commentFormTitle'      => '发表评论',
    'commentLabel'          => '评论 *',
    'commentPostBtn'        => '提交评论',
    'commentModerated'      => '评论在显示之前需要审核。',
    'commentLoginRequired'  => '以发表评论。',
    'commentLoginLink'      => '登录',
    'commentAwaitModeration'=> '您的评论正在等待审核。',
    'commentPosted'         => '您的评论已发布。',
    'commentLoginToComment' => '您必须登录才能发表评论。',
    'commentTooFast'        => '您评论太频繁了，请等待几分钟后再试。',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => '联系我们',
    'contactName'           => '姓名',
    'contactEmail'          => '邮箱',
    'contactMessage'        => '消息',
    'contactSendBtn'        => '发送消息',
    'contactSent'           => '您的消息已发送！',
    'contactCaptchaFail'    => '验证码验证失败，请重试。',
    'contactSubject'        => '联系表单：{0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => '页面导航',
    'prevPage'          => '上一页',
    'nextPage'          => '下一页',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => '页面未找到。',
    'pageNotFoundTitle' => '404 - 页面未找到',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => '维护中',
    'maintenanceBody'   => "我们正在进行计划维护，即将回归——感谢您的耐心等待！",

    // Language
    'language'          => '语言',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => '我的个人资料',
    'profileBasicInfo'        => '基本信息',
    'profileUsername'          => '用户名',
    'profileEmail'            => '邮箱',
    'profilePassword'         => '密码',
    'profilePasswordConfirm'  => '确认密码',
    'profilePasswordHelp'     => '留空则保留当前密码。',
    'profileSave'             => '保存更改',
    'profileUpdated'          => '个人资料已成功更新。',
    'profileUsernameRequired' => '用户名为必填项。',
    'profileUsernameTaken'    => '该用户名已被占用。',
    'profileEmailRequired'    => '邮箱为必填项。',
    'profileEmailTaken'       => '该邮箱已被使用。',
    'profilePasswordMismatch' => '两次输入的密码不一致。',
    'profilePasswordTooShort' => '密码长度至少为8个字符。',

    'profileAuthorInfo'       => '作者资料',
    'profileDisplayName'      => '显示名称',
    'profileBio'              => '简介',
    'profileAvatar'           => '头像',
    'profileAvatarChange'     => '更换头像',
    'profileAvatarUpload'     => '上传',
    'profileWebsite'          => '网站',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => '头像已成功更新。',
    'profileAvatarInvalid'    => '无效的文件上传。',
    'profileAvatarTypeError'  => '仅接受 JPEG、PNG、WebP 和 GIF 格式的图片。',
    'profileAvatarTooLarge'   => '头像大小不得超过 2 MB。',
    'profileAvatarNotAllowed' => '头像上传仅对作者及以上级别开放。',

    'login'                         => '登录',
    'adminPanel'                    => '管理面板',

    'profileUpdatedRelogin'         => '个人资料已更新，请重新登录。',
    'profileUsernameChangedSubject' => '您的用户名已更改',
    'profileUsernameChangedBody'    => '您的用户名已从"{0}"更改为"{1}"。如果您未进行此更改，请立即联系网站管理员。',
    'profileEmailChangedSubject'    => '您的电子邮件地址已更改',
    'profileEmailChangedBody'       => '您的电子邮件地址已从"{0}"更改为"{1}"。如果您未进行此更改，请立即联系网站管理员。',
    'profilePasswordChangedSubject' => '您的密码已更改',
    'profilePasswordChangedBody'    => '您的密码最近已被更改。如果您未进行此更改，请立即联系网站管理员。',

];
