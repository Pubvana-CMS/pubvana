<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (Korean)
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

    'home'              => '홈',
    'blog'              => '블로그',
    'readMore'          => '더 읽기',
    'viewAll'           => '전체 보기',
    'noPostsYet'        => '아직 게시글이 없습니다. 나중에 다시 확인해 주세요!',
    'search'            => '검색',
    'searchPlaceholder' => '검색…',
    'searchPostsPlaceholder' => '게시글 검색…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => '사이트맵',
    'allRightsReserved' => '모든 권리 보유.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => '최신 게시글',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => '게시일',
    'views'             => '{0}회 조회',
    'readingTime'       => '{0}분 읽기',
    'publishedBy'       => '작성자:',
    'inCategory'        => '카테고리:',
    'tags'              => '태그',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => '미리보기 모드 - 이 게시글은 공개되지 않습니다',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => '프리미엄 콘텐츠',
    'paywallMessage'        => '이 콘텐츠는 프리미엄 구독자에게 제공됩니다.',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => '저자 소개',
    'unknownAuthor'     => '알 수 없는 저자',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => '카테고리: {0}',
    'noPostsInCategory' => '이 카테고리에는 아직 게시글이 없습니다.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => '태그: {0}',
    'noPostsWithTag'    => '이 태그의 게시글이 없습니다.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => '아카이브: {0}',
    'noPostsInPeriod'   => '이 기간에 게시글이 없습니다.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => '검색 결과',
    'searchShowingFor'      => '"{0}" 검색 결과',
    'searchNoResults'       => '"{0}"에 대한 게시글을 찾을 수 없습니다.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => '댓글 ({0})',
    'commentsClosed'        => '댓글이 마감되었습니다.',
    'commentFormTitle'      => '댓글 남기기',
    'commentLabel'          => '댓글 *',
    'commentPostBtn'        => '댓글 게시',
    'commentModerated'      => '댓글은 게시 전에 검토됩니다.',
    'commentLoginRequired'  => '댓글을 남기려면 로그인하세요.',
    'commentLoginLink'      => '로그인',
    'commentAwaitModeration'=> '댓글이 승인 대기 중입니다.',
    'commentPosted'         => '댓글이 게시되었습니다.',
    'commentLoginToComment' => '댓글을 남기려면 로그인해야 합니다.',
    'commentTooFast'        => '댓글을 너무 빨리 작성하고 있습니다. 몇 분 후에 다시 시도해 주세요.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => '문의하기',
    'contactName'           => '이름',
    'contactEmail'          => '이메일',
    'contactMessage'        => '메시지',
    'contactSendBtn'        => '메시지 보내기',
    'contactSent'           => '메시지가 전송되었습니다!',
    'contactCaptchaFail'    => '캡차 인증에 실패했습니다. 다시 시도해 주세요.',
    'contactSubject'        => '문의 양식: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => '페이지 탐색',
    'prevPage'          => '이전',
    'nextPage'          => '다음',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => '페이지를 찾을 수 없습니다.',
    'pageNotFoundTitle' => '404 - 페이지를 찾을 수 없습니다',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => '점검 중',
    'maintenanceBody'   => '정기 점검을 진행 중입니다. 곧 돌아오겠습니다 - 기다려 주셔서 감사합니다!',

    // Language
    'language'          => '언어',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => '내 프로필',
    'profileBasicInfo'        => '기본 정보',
    'profileUsername'          => '사용자 이름',
    'profileEmail'            => '이메일',
    'profilePassword'         => '비밀번호',
    'profilePasswordConfirm'  => '비밀번호 확인',
    'profilePasswordHelp'     => '현재 비밀번호를 유지하려면 비워 두세요.',
    'profileSave'             => '변경사항 저장',
    'profileUpdated'          => '프로필이 성공적으로 업데이트되었습니다.',
    'profileUsernameRequired' => '사용자 이름은 필수입니다.',
    'profileUsernameTaken'    => '해당 사용자 이름은 이미 사용 중입니다.',
    'profileEmailRequired'    => '이메일은 필수입니다.',
    'profileEmailTaken'       => '해당 이메일은 이미 사용 중입니다.',
    'profilePasswordMismatch' => '비밀번호가 일치하지 않습니다.',
    'profilePasswordTooShort' => '비밀번호는 최소 8자 이상이어야 합니다.',

    'profileAuthorInfo'       => '저자 프로필',
    'profileDisplayName'      => '표시 이름',
    'profileBio'              => '소개',
    'profileAvatar'           => '아바타',
    'profileAvatarChange'     => '아바타 변경',
    'profileAvatarUpload'     => '업로드',
    'profileWebsite'          => '웹사이트',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => '아바타가 성공적으로 업데이트되었습니다.',
    'profileAvatarInvalid'    => '잘못된 파일 업로드입니다.',
    'profileAvatarTypeError'  => 'JPEG, PNG, WebP, GIF 형식의 이미지만 허용됩니다.',
    'profileAvatarTooLarge'   => '아바타는 2MB 이하여야 합니다.',
    'profileAvatarNotAllowed' => '아바타 업로드는 저자 이상의 역할에서 사용 가능합니다.',

    'login'                         => '로그인',
    'adminPanel'                    => '관리 패널',

    'profileUpdatedRelogin'         => '프로필이 업데이트되었습니다. 다시 로그인해 주세요.',
    'profileUsernameChangedSubject' => '사용자 이름이 변경되었습니다',
    'profileUsernameChangedBody'    => '사용자 이름이 "{0}"에서 "{1}"으로 변경되었습니다. 본인이 변경하지 않은 경우 즉시 사이트 관리자에게 문의하세요.',
    'profileEmailChangedSubject'    => '이메일 주소가 변경되었습니다',
    'profileEmailChangedBody'       => '이메일 주소가 "{0}"에서 "{1}"으로 변경되었습니다. 본인이 변경하지 않은 경우 즉시 사이트 관리자에게 문의하세요.',
    'profilePasswordChangedSubject' => '비밀번호가 변경되었습니다',
    'profilePasswordChangedBody'    => '최근 비밀번호가 변경되었습니다. 본인이 변경하지 않은 경우 즉시 사이트 관리자에게 문의하세요.',

];
