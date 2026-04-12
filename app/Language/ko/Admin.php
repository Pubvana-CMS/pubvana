<?php

/**
 * Pubvana CMS - Admin language strings (Korean)
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
    'save'              => '저장',
    'saveChanges'       => '변경사항 저장',
    'cancel'            => '취소',
    'edit'              => '편집',
    'delete'            => '삭제',
    'create'            => '만들기',
    'add'               => '추가',
    'back'              => '뒤로',
    'view'              => '보기',
    'apply'             => '적용',
    'install'           => '설치',
    'update'            => '업데이트',
    'refresh'           => '새로고침',
    'activate'          => '활성화',
    'deactivate'        => '비활성화',
    'enable'            => '사용',
    'disable'           => '사용 안 함',
    'disabled'          => '비활성',
    'approve'           => '승인',
    'spam'              => '스팸',
    'trash'             => '휴지통',
    'restore'           => '복원',
    'dismiss'           => '닫기',
    'recheck'           => '재확인',
    'clickToCopy'       => '클릭하여 복사',
    'download'          => '다운로드',
    'upload'            => '업로드',
    'import'            => '가져오기',
    'export'            => '내보내기',
    'publish'           => '게시',
    'unpublish'         => '게시 취소',
    'logout'            => '로그아웃',
    'viewSite'          => '사이트 보기',
    'newPost'           => '새 게시글',
    'buyNow'            => '지금 구매',
    'visitStore'        => '스토어 방문',
    'loadMore'          => '더 보기',

    // Table headers / labels
    'title'             => '제목',
    'name'              => '이름',
    'slug'              => '슬러그',
    'status'            => '상태',
    'date'              => '날짜',
    'actions'           => '작업',
    'author'            => '저자',
    'views'             => '조회수',
    'type'              => '유형',
    'url'               => 'URL',
    'description'       => '설명',
    'role'              => '역할',
    'email'             => '이메일',
    'username'          => '사용자 이름',
    'active'            => '활성',
    'version'           => '버전',
    'size'              => '크기',
    'clicks'            => '클릭수',
    'total'             => '합계',
    'platform'          => '플랫폼',
    'label'             => '레이블',
    'order'             => '순서',
    'source'            => '소스',
    'content'           => '내용',
    'excerpt'           => '발췌',
    'details'           => '세부 정보',
    'contentType'       => '콘텐츠 유형',
    'seo'               => 'SEO',
    'metaTitle'         => '메타 제목',
    'metaDescription'   => '메타 설명',

    // Status badges
    'published'         => '게시됨',
    'draft'             => '초안',
    'scheduled'         => '예약됨',
    'pending'           => '대기 중',
    'safe'              => '안전',
    'notSafe'           => '안전하지 않음',
    'malicious'         => '악성',
    'safetyUnknown'     => '알 수 없음',
    'inactive'          => '비활성',
    'installed'         => '설치됨',
    'free'              => '무료',
    'premium'           => '프리미엄',
    'all'               => '전체',

    // Confirmations
    'confirmDelete'         => '이 항목을 삭제하시겠습니까?',
    'confirmDeletePost'     => '이 게시글을 삭제하시겠습니까?',
    'confirmDeletePage'     => '이 페이지를 삭제하시겠습니까?',
    'confirmDeleteComment'  => '이 댓글을 영구 삭제하시겠습니까?',
    'confirmDeleteUser'     => '이 사용자를 삭제하시겠습니까?',
    'confirmDeleteMedia'    => '삭제하시겠습니까?',
    'confirmDeleteBackup'   => '이 백업 파일을 삭제하시겠습니까?',
    'confirmBulkAction'     => '선택한 게시글에 일괄 작업을 적용하시겠습니까?',

    // Empty states
    'noPostsYet'        => '아직 게시글이 없습니다. {0}',
    'noResultsFound'    => '결과를 찾을 수 없습니다.',
    'noCommentsYet'     => '대기 중인 댓글이 없습니다.',
    'noMediaYet'        => '아직 미디어가 없습니다.',
    'noItemsFound'      => '마켓플레이스에서 항목을 찾을 수 없습니다.',
    'noCategoriesYet'   => '아직 카테고리가 없습니다.',
    'noTagsYet'         => '아직 태그가 없습니다.',
    'noRevisionsYet'    => '수정 내역이 없습니다.',

    // Misc common
    'permissionDenied'  => '권한이 없습니다.',
    'notFound'          => '레코드를 찾을 수 없습니다.',
    'commasSeparated'   => '쉼표로 구분',
    'optional'          => '선택 사항',
    'required'          => '필수',
    'enabled'           => '활성화됨',
    'selected'          => '{0}개의 게시글이 선택됨',
    'published_count'   => '{0}개 게시됨',
    'pending_count'     => '{0}개 대기 중',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => '대시보드',
    'navContent'        => '콘텐츠',
    'navAppearance'     => '외관',
    'navUsersAndSite'   => '사용자 및 사이트',
    'navTools'          => '도구',
    'navMarketplace'    => '마켓플레이스',
    'navPlugins'        => '플러그인',
    'navPosts'          => '게시글',
    'navSchedule'       => '일정',
    'navPages'          => '페이지',
    'navCategories'     => '카테고리',
    'navTags'           => '태그',
    'navComments'       => '댓글',
    'navMedia'          => '미디어',
    'navImport'         => '가져오기',
    'navThemes'         => '테마',
    'navWidgets'        => '위젯',
    'navNavigation'     => '내비게이션',
    'navUsers'          => '사용자',
    'navSocialLinks'    => '소셜 링크',
    'navRedirects'      => '리디렉션',
    'navLanguages'      => '언어',
    'navSettings'       => '설정',
    'navAnalytics'      => '분석',
    'navAffiliates'     => '제휴 링크',
    'navBrokenLinks'    => '깨진 링크',
    'navActivityLog'    => '활동 로그',
    'navBackup'         => '백업 및 내보내기',
    'navUpdates'        => '업데이트',
    'navBrowse'         => '탐색',
    'navLicenses'       => '라이선스',
    'navPubvanaStore'   => 'Pubvana 스토어',
    'navUpdateAvailable'=> '업데이트 있음',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => '나가시겠습니까?',
    'logoutModalBody'   => '아래 "로그아웃"을 선택하여 세션을 종료하세요.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => '대시보드',
    'dashStats'             => '통계',
    'dashPosts'             => '게시글',
    'dashPages'             => '페이지',
    'dashComments'          => '댓글',
    'dashUsers'             => '사용자',
    'dashRecentPosts'       => '최근 게시글',
    'dashPendingComments'   => '대기 중인 댓글',
    'dashViewAll'           => '전체 보기',
    'dashCreateOne'         => '만들어 보세요!',
    'dashNoPosts'           => '아직 게시글이 없습니다.',
    'dashNoPendingComments' => '대기 중인 댓글이 없습니다.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => '게시글',
    'newPostTitle'          => '새 게시글',
    'editPostTitle'         => '게시글 편집: {0}',
    'copyPreviewLink'       => '미리보기 링크 복사',
    'backToPosts'           => '게시글 목록으로',
    'postTitleField'        => '제목 *',
    'postEditor'            => '편집기',
    'postHtmlEditor'        => 'HTML 편집기',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => '발췌',
    'postExcerptPlaceholder'=> '선택적 짧은 요약...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => '메타 제목',
    'postMetaDescription'   => '메타 설명',
    'postPublishSection'    => '게시',
    'postStatus'            => '상태',
    'postStatusDraft'       => '초안',
    'postStatusPublished'   => '게시됨',
    'postStatusScheduled'   => '예약됨',
    'postScheduledAt'       => '예약 날짜 및 시간',
    'postFeatured'          => '추천 게시글',
    'postMembersOnly'       => '회원 전용',
    'postShareOnPublish'    => '게시 시 소셜에 공유',
    'postSaveBtn'           => '게시글 저장',
    'postFeaturedImage'     => '대표 이미지',
    'postFeaturedImagePlaceholder' => 'URL 또는 업로드 경로…',
    'postCategories'        => '카테고리',
    'postTags'              => '태그',
    'postTagsPlaceholder'   => '태그1, 태그2, 태그3',
    'postRevisions'         => '수정 내역',
    'postRevisionCount'     => '{0}개의 수정 내역',
    'postPreview'           => '미리보기',
    'postBulkAction'        => '- 작업 선택 -',
    'postBulkPublish'       => '게시',
    'postBulkUnpublish'     => '게시 취소 (초안으로 변경)',
    'postBulkDelete'        => '삭제',

    // Post flash messages
    'postCreated'           => '게시글이 성공적으로 생성되었습니다.',
    'postUpdated'           => '게시글이 업데이트되었습니다.',
    'scheduledDateMustBeFuture' => '예약 날짜는 미래여야 합니다.',
    'postDeleted'           => '게시글이 삭제되었습니다.',
    'postBulkUpdated'       => '{0}개의 게시글이 업데이트되었습니다.',
    'postBulkInvalid'       => '잘못된 일괄 작업입니다.',
    'postPermission'        => '본인의 게시글만 편집할 수 있습니다.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => '수정 내역: {0}',
    'revisionTitle'         => '수정 내역 — {0}',
    'revisionShowTitle'     => '수정 내역',
    'revisionsBackToPost'   => '게시글로 돌아가기',
    'revisionsBackToList'   => '수정 내역 목록으로',
    'revisionRestored'      => '{0}의 수정 내역으로 게시글이 복원되었습니다.',
    'revisionRestoreBtn'    => '이 수정 내역으로 복원',
    'revisionSaved'         => '저장됨',
    'revisionBy'            => '작성자:',
    'revisionOn'            => '날짜:',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => '페이지',
    'newPageTitle'          => '새 페이지',
    'editPageTitle'         => '페이지 편집',
    'pageSlugInUse'         => "슬러그 '{0}'는 이미 사용 중입니다.",
    'pageCannotDelete'      => '이 페이지는 삭제할 수 없습니다.',
    'slugAutoGenHint'       => '비워 두면 제목에서 자동으로 생성됩니다',
    'slugCannotChange'      => '변경 불가',
    'colSystem'             => '시스템',
    'system'                => '시스템',

    // Page flash messages
    'pageCreated'           => '페이지가 생성되었습니다.',
    'pageUpdated'           => '페이지가 업데이트되었습니다.',
    'pageDeleted'           => '페이지가 삭제되었습니다.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => '카테고리',
    'newCategoryTitle'      => '새 카테고리',
    'editCategoryTitle'     => '카테고리 편집',
    'categoryName'          => '이름',
    'categoryDescription'   => '설명',
    'categoryPostCount'     => '게시글 수',

    // Category flash messages
    'categoryCreated'       => '카테고리가 생성되었습니다.',
    'categoryUpdated'       => '카테고리가 업데이트되었습니다.',
    'categoryDeleted'       => '카테고리가 삭제되었습니다.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => '태그',
    'tagPostCount'          => '게시글 수',

    // Tag flash messages
    'tagDeleted'            => '태그가 삭제되었습니다.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => '댓글',
    'commentAuthor'         => '저자',
    'commentContent'        => '댓글',
    'commentPost'           => '게시글',
    'commentDate'           => '날짜',
    'commentStatusFilter'   => '상태로 필터',

    // Comment flash messages
    'commentApproved'       => '댓글이 승인되었습니다.',
    'commentSpam'           => '스팸으로 표시되었습니다.',
    'commentTrashed'        => '댓글이 휴지통으로 이동되었습니다.',
    'commentDeleted'        => '댓글이 영구 삭제되었습니다.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => '미디어 라이브러리',
    'mediaTitle'            => '제목',
    'mediaAltText'          => '대체 텍스트',
    'mediaAltPlaceholder'   => '접근성을 위해 이미지를 설명하세요',
    'mediaTitlePlaceholder' => '선택적 이미지 제목',
    'mediaImageDetails'     => '이미지 세부 정보',
    'mediaSaved'            => '저장되었습니다!',
    'mediaNoSelection'      => '선택된 이미지 없음',
    'mediaBrowse'           => '미디어 탐색',
    'mediaRemove'           => '제거',
    'mediaUseImage'         => '이 이미지 사용',
    'mediaDropzone'         => '이미지를 여기에 끌어다 놓거나 클릭하여 탐색',
    'mediaLoading'          => '미디어 로딩 중…',
    'mediaEmpty'            => '아직 업로드된 미디어가 없습니다.',
    'mediaUpload'           => '미디어 업로드',
    'mediaDragDrop'         => '파일을 여기에 끌어다 놓거나,',
    'mediaChooseFiles'      => '파일 선택',
    'mediaUploading'        => '업로드 중…',
    'mediaFilename'         => '파일 이름',
    'mediaSize'             => '크기',
    'mediaUploadFailed'     => '업로드 실패: {0}',
    'mediaUploadError'      => '업로드 오류: {0}',

    // Media flash messages
    'mediaDeleted'          => '미디어가 삭제되었습니다.',
    'mediaNoValidFile'      => '유효한 파일이 업로드되지 않았습니다.',
    'mediaUploadSuccess'    => '파일이 성공적으로 업로드되었습니다.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => '내비게이션',
    'navQuickAdd'           => '빠른 추가',
    'navQuickAddPlaceholder' => '페이지, 카테고리, 플러그인 검색...',
    'navItemLabel'          => '레이블',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => '대상',
    'navItemOrder'          => '정렬 순서',
    'navGroupPrimary'       => '기본',
    'navGroupFooter'        => '푸터',
    'navSelectGroup'        => '내비게이션 그룹 선택:',
    'navParent'             => '상위',
    'navTopLevel'           => '— 최상위 —',
    'navSameWindow'         => '같은 창',
    'navNewWindow'          => '새 창',
    'navMenuItems'          => '메뉴 항목',
    'navNoItems'            => '이 메뉴에 항목이 없습니다.',
    'dragToReorder'         => '끌어서 순서 변경',

    // Navigation flash messages
    'navItemAdded'          => '내비게이션 항목이 추가되었습니다.',
    'navItemRemoved'        => '내비게이션 항목이 삭제되었습니다.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => '테마',
    'themeOptions'          => '테마 옵션',
    'themeActivate'         => '활성화',
    'themeOptionsBtn'       => '옵션',
    'themeActive'           => '활성',
    'themeBy'               => '제작자:',
    'themeSupport'          => '지원',
    'themeVersion'          => '버전',
    'themeSaveOptions'      => '옵션 저장',
    'themeInvalidLicense'   => '테마를 활성화할 수 없습니다 - 라이선스가 유효하지 않습니다. 재설치하거나 지원팀에 문의하세요.',
    'themeValidationFailed' => '테마에 PHP 코드가 포함되어 있어 활성화할 수 없습니다.',
    'noThemesInstalled'     => '설치된 테마가 없습니다. 마켓플레이스에서 테마를 구하세요.',
    'themeUnapprovedTitle'  => '미승인 테마를 활성화하시겠습니까?',
    'themeNotApproved'      => '이 테마는 Pubvana에서 승인되지 않았습니다.',
    'themeUnapprovedRisk'   => '미승인 테마를 활성화하면 보안 위험이나 호환성 문제가 발생할 수 있습니다.',
    'themeActivateConfirm'  => '정말로 활성화하시겠습니까?',
    'themeActivateAnyway'   => '그래도 활성화',
    'themeNoOptions'        => '이 테마에는 설정 가능한 옵션이 없습니다.',
    'themeCustomize'        => '테마 사용자 정의',

    // Theme flash messages
    'themeActivated'        => '테마가 활성화되었습니다.',
    'themeOptionsSaved'     => '옵션이 저장되었습니다.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => '라이선스 보유',
    'licenseCheckNow'        => '지금 확인',
    'licenseExpired'         => '만료됨',
    'licenseEnterKey'        => '키 입력',
    'licenseChangeKey'       => '변경',
    'licenseRenew'           => '갱신',
    'licenseThirdParty'      => '서드파티',
    'unchecked'              => '미확인',
    'safetyLabel'            => '안전:',
    'recheckBtn'             => '재확인',
    'recheckSuccess'         => '안전 확인이 업데이트되었습니다.',
    'recheckFailed'          => '검증 서버에 연결할 수 없습니다. 나중에 다시 시도하세요.',
    'recheckNotFound'        => '항목을 찾을 수 없습니다.',
    'widgetBlockedMalicious' => '{0}은(는) 악성으로 표시되어 추가할 수 없습니다.',
    'licenseNoStoreProduct'  => '이 항목은 스토어 제품에 연결되어 있지 않습니다. 이 항목을 구매한 경우 마켓플레이스에서 다시 설치하여 라이선스를 활성화하세요.',
    'securityWarning'        => '보안 경고:',
    'licenseModalTitle'      => '라이선스 키 입력',
    'licenseModalBody'       => '아래에 라이선스 키를 붙여넣으세요.',
    'licenseModalSave'       => '저장',
    'licenseSaved'           => '라이선스 키가 저장되고 검증되었습니다.',
    'licenseInvalid'         => '라이선스 키가 유효하지 않습니다.',
    'licenseKeyRequired'     => '라이선스 키와 제품이 필요합니다.',
    'licenseCheckFailed'     => '라이선스 서버에 연결할 수 없습니다. 나중에 다시 시도해 주세요.',
    'licenseProductNotFound' => '스토어에서 이 항목을 찾을 수 없습니다.',
    'btnCancel'              => '취소',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => '위젯',
    'widgetConfigureTitle'  => '위젯 구성',
    'widgetAreas'           => '위젯 영역',
    'widgetAvailable'       => '사용 가능한 위젯',
    'widgetAddToArea'       => '영역에 추가',
    'widgetArea'            => '영역',
    'widgetNoOptions'       => '옵션 없음.',
    'widgetSaveConfig'      => '구성 저장',
    'widgetConfigure'       => '구성',
    'widgetNoAreas'         => '위젯 영역을 찾을 수 없습니다. 테마를 활성화하여 위젯 영역을 사용하세요.',
    'widgetAreaEmpty'       => '이 영역에 위젯이 없습니다. 오른쪽 목록에서 추가하세요 →',

    // Widget flash messages
    'widgetAdded'           => '위젯이 추가되었습니다.',
    'widgetRemoved'         => '위젯이 제거되었습니다.',
    'widgetConfigured'      => '위젯이 구성되었습니다.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => '마켓플레이스',
    'marketplaceRefresh'    => '새로고침',
    'marketplaceVisitStore' => '스토어 방문',
    'marketplaceAll'        => '전체',
    'marketplaceThemes'     => '테마',
    'marketplaceWidgets'    => '위젯',
    'marketplacePlugins'    => '플러그인',
    'marketplaceUpdatesAvailable' => '{0}개의 업데이트가 있습니다.',
    'marketplaceBy'         => '제작자:',
    'marketplaceFree'       => '무료',
    'marketplaceInstalled'  => '설치됨',
    'marketplaceInstall'    => '설치',
    'marketplaceBuyNow'     => '지금 구매',
    'marketplaceNoItems'    => '마켓플레이스에서 항목을 찾을 수 없습니다.',
    'marketplaceInstalledVersion' => 'v{0} 설치됨',
    'marketplaceLoadError'  => '스토어에서 제품을 불러올 수 없습니다. 나중에 다시 확인하세요.',
    'byAuthor'              => '{0} 제작',
    'unknown'               => '알 수 없음',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0}이(가) 성공적으로 설치되었습니다.',
    'marketplaceInstallFail'    => '설치에 실패했습니다. 로그를 확인하세요.',
    'marketplaceUpdateSuccess'  => '성공적으로 업데이트되었습니다.',
    'marketplaceUpdateFail'     => '업데이트에 실패했습니다.',
    'marketplaceCacheRefreshed' => '마켓플레이스 캐시가 새로고침되었습니다.',
    'marketplaceInvalidRequest' => '잘못된 설치 요청입니다.',
    'marketplaceCannotUpdate'   => '이 항목을 업데이트할 수 없습니다.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => '라이선스',
    'licensesNone'                => '라이선스 없음',
    'licensesProduct'             => '제품',
    'licensesKey'                 => '라이선스 키',
    'licensesStatus'              => '상태',
    'licensesType'                => '유형',
    'licensesExpires'             => '만료일',
    'licensesDomain'              => '도메인',
    'licensesInstalled'           => '설치됨',
    'licensesLastChecked'         => '마지막 확인',
    'licensesActions'             => '작업',
    'licensesStatusValid'         => '유효',
    'licensesStatusInvalid'       => '유효하지 않음',
    'licensesStatusExpired'       => '만료됨',
    'licensesStatusSubExpired'    => '구독 만료됨',
    'licensesStatusUnchecked'     => '미확인',
    'licensesSubscription'        => '구독',
    'licensesOneTime'             => '일회 구매',
    'licensesPerpetual'           => '영구',
    'licensesNotInstalled'        => '설치되지 않음',
    'licensesNever'               => '없음',
    'licensesRevalidate'          => '재검증',
    'licenseKeyPlaceholder'       => '라이선스 키를 입력하세요...',
    'marketplaceLicensesEmpty'    => '설치 후 라이선스 제품이 여기에 표시됩니다.',
    'typeTheme'                   => '테마',
    'typeWidget'                  => '위젯',
    'typePlugin'                  => '플러그인',

    // License revalidation flash messages
    'licenseRevalidateValid'       => '라이선스가 성공적으로 검증되었습니다.',
    'licenseRevalidateInvalid'     => '라이선스가 유효하지 않거나 만료되었습니다.',
    'licenseRevalidateUnreachable' => '라이선스 서버에 연결할 수 없습니다. 나중에 다시 시도해 주세요.',
    'licenseRevalidateSkipped'     => '라이선스 확인이 건너뛰어졌습니다(개발 모드).',
    'licenseRevalidateNotFound'    => '라이선스를 찾을 수 없습니다.',

    // License warning banners
    'licenseWarningTitle'   => '라이선스 문제',
    'licenseWarningInvalid' => '라이선스가 유효하지 않거나 만료됨',
    'licenseWarningManage'  => '라이선스 관리',

    // Plugin license
    'pluginInvalidLicense' => '이 플러그인의 라이선스가 유효하지 않거나 만료되어 활성화할 수 없습니다.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => '라이선스 키',
    'storeBrowseFull'       => '전체 스토어 탐색',
    'storeBackToMarketplace'=> '마켓플레이스로 돌아가기',
    'storeNoProducts'       => '이용 가능한 제품이 없습니다.',
    'storeViewInStore'      => '스토어에서 보기',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => '사용자',
    'editUserTitle'         => '사용자 편집',
    'createUserTitle'       => '사용자 만들기',
    'authorProfileTitle'    => '저자 프로필',
    'userRoleLabel'         => '역할',
    'userActiveLabel'       => '활성',
    'userPasswordLabel'     => '비밀번호',
    'userPasswordOptional'  => '현재 비밀번호를 유지하려면 비워 두세요',
    'userDisplayName'       => '표시 이름',
    'userBio'               => '소개',
    'userWebsite'           => '웹사이트',
    'userTwitter'           => 'Twitter / X 핸들',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => '아바타',
    'userSaveProfile'       => '프로필 저장',
    'userSaveChanges'       => '변경사항 저장',
    'userCannotDeleteSelf'  => '본인을 삭제할 수 없습니다.',
    'userCannotDeleteOwner' => '사이트 소유자 계정은 삭제할 수 없습니다.',
    'userOwnerCannotModify' => '사이트 소유자 계정은 수정할 수 없습니다.',

    // User flash messages
    'userCreated'           => '사용자가 생성되었습니다.',
    'userUpdated'           => '사용자가 업데이트되었습니다.',
    'userDeleted'           => '사용자가 삭제되었습니다.',
    'userBanned'            => '사용자가 차단되었습니다.',
    'userUnbanned'          => '사용자 차단이 해제되었습니다.',
    'userCannotBanSelf'     => '본인 또는 사이트 소유자를 차단할 수 없습니다.',
    'banStatus'             => '차단 상태',
    'banned'                => '차단됨',
    'ban'                   => '사용자 차단',
    'unban'                 => '차단 해제',
    'banReasonRequired'     => '차단 이유가 필요합니다.',
    'banReasonPlaceholder'  => '차단 이유...',
    'confirmBanUser'        => '이 사용자를 차단하시겠습니까?',
    'userProfileSaved'      => '프로필이 저장되었습니다.',
    'userAvatarUploadFail'  => '아바타 업로드 실패: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA 설정',
    'tfaSetupHeading'       => '이중 인증 설정',
    'tfaScanQr'             => '인증 앱(예: Google Authenticator, Authy)으로 아래 QR 코드를 스캔하세요.',
    'tfaManualEntry'        => '또는 비밀 키를 수동으로 입력:',
    'tfaEnterCode'          => '확인을 위해 앱의 6자리 코드를 입력하세요:',
    'tfaCodeLabel'          => '인증 코드',
    'tfaConfirmBtn'         => '확인 및 2FA 활성화',
    'tfaDisableBtn'         => '2FA 비활성화',
    'tfaDisableConfirm'     => '비활성화하려면 현재 2FA 코드를 입력하세요:',
    'tfaEnabled'            => '이중 인증이 활성화되었습니다.',
    'tfaDisabled'           => '이중 인증이 비활성화되었습니다.',
    'tfaInvalidCode'        => '잘못된 코드입니다 - QR 코드를 다시 스캔하고 시도해 주세요.',
    'tfaInvalidDisable'     => '잘못된 코드입니다 - 2FA가 비활성화되지 않았습니다.',
    'tfaSessionExpired'     => '설정 세션이 만료되었습니다 - 다시 시작해 주세요.',
    'tfaNotEnabled'         => '2FA가 현재 활성화되어 있지 않습니다.',
    'tfaCantScan'           => '스캔할 수 없나요? 이 코드를 수동으로 입력하세요:',
    'tfaWarning'            => '이 비밀 키를 안전한 곳에 보관하세요. 인증 기기를 잃어버린 경우 접근 복구에 필요합니다.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => '소셜 링크',
    'socialPlatform'           => '플랫폼',
    'socialUrl'                => 'URL',
    'socialIcon'               => '아이콘',
    'socialSortOrder'          => '정렬 순서',
    'socialIconPackInfo'       => '현재 테마 <strong>{0}</strong>은 아이콘에 <strong>{1}</strong>(v{2})을 사용합니다. 아래에서 이 사이트의 소셜 링크 기능에 표시될 아이콘을 선택할 수 있습니다.',
    'socialSearchPlaceholder'  => '플랫폼 검색...',
    'socialIconDisclaimer'     => '이 아이콘들은 사용될 아이콘의 표현일 뿐입니다. 실제 아이콘은 활성화된 테마의 아이콘 팩에 따라 다를 수 있습니다.',

    // Social flash messages
    'socialLinkAdded'       => '소셜 링크가 추가되었습니다.',
    'socialLinkUpdated'     => '링크가 업데이트되었습니다.',
    'socialLinkDeleted'     => '링크가 삭제되었습니다.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => '리디렉션',
    'redirectFrom'          => '원본 URL',
    'redirectTo'            => '대상 URL',
    'redirectType'          => '유형',
    'redirectAdd'           => '리디렉션 추가',
    'redirectFromHint'      => '(상대 경로, 예: /old-page)',
    'redirect301'           => '301 영구',
    'redirect302'           => '302 임시',
    'redirectInvalidDest'   => '잘못된 리디렉션 대상 URL입니다.',

    // Redirect flash messages
    'redirectAdded'         => '리디렉션이 추가되었습니다.',
    'redirectDeleted'       => '리디렉션이 삭제되었습니다.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => '설정',
    'settingsGeneral'       => '일반',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => '이메일',
    'settingsSocialLogin'   => '소셜 로그인',
    'settingsSocialSharing' => '소셜 공유',
    'settingsSpam'          => '스팸 방지',

    'generalSettingsHeading'    => '일반 설정',
    'generalSiteName'           => '사이트 이름',
    'generalTagline'            => '태그라인',
    'generalAdminEmail'         => '관리자 이메일',
    'generalPostsPerPage'       => '페이지당 게시글 수',
    'generalComments'           => '댓글',
    'generalCommentsEnable'     => '댓글 허용',
    'generalCommentModeration'  => '게시 전 검토 필요',
    'generalMaintenanceMode'    => '점검 모드',
    'generalMaintenanceEnable'  => '점검 모드 활성화',
    'generalMaintenanceHelp'    => '방문자에게는 "곧 돌아오겠습니다" 페이지가 표시됩니다. 관리자는 여전히 사이트에 접근할 수 있습니다.',
    'generalFrontPage'          => '메인 페이지',
    'generalFrontPageBlog'      => '블로그 인덱스 (최신 게시글)',
    'generalFrontPageStatic'    => '정적 페이지:',
    'generalFrontPagePlugin'    => '플러그인 페이지:',
    'generalSelectPage'         => '- 페이지 선택 -',
    'generalSelectRoute'        => '- 경로 선택 -',
    'generalFrontPageNoPlugins' => '플러그인 경로 없음',
    'generalPageCacheTtl'       => '페이지 캐시 TTL',
    'settingsCacheTtlHint'      => '초. 0 = 비활성화.',
    'generalSaveBtn'            => '일반 설정 저장',

    // General flash messages
    'generalSettingsSaved'      => '일반 설정이 저장되었습니다.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO 설정',
    'seoMetaDescription'        => '메타 설명',
    'seoGoogleAnalytics'        => 'Google Analytics ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => '사이트맵',
    'seoSitemapEnable'          => 'sitemap.xml 활성화',
    'seoSitemapHelp'            => '모든 게시된 게시글 및 페이지에 대한 표준 사이트맵.',
    'seoNewsSitemap'            => 'news-sitemap.xml 활성화',
    'seoNewsSitemapHelp'        => 'Google News 사이트맵 - 지난 48시간 내에 게시된 게시글을 나열합니다.',
    'seoSaveBtn'                => 'SEO 설정 저장',
    'seoSettingsSaved'          => 'SEO 설정이 저장되었습니다.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => '이메일 설정',
    'emailFromName'             => '발신자 이름',
    'emailFromAddress'          => '발신자 주소',
    'emailProtocol'             => '프로토콜',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP 호스트',
    'emailSmtpPort'             => 'SMTP 포트',
    'emailSmtpEncryption'       => '암호화',
    'emailSmtpEncryptionNone'   => '없음',
    'emailSmtpUsername'         => 'SMTP 사용자 이름',
    'emailSmtpPassword'         => 'SMTP 비밀번호',
    'emailSaveBtn'              => '이메일 설정 저장',
    'emailSettingsSaved'        => '이메일 설정이 저장되었습니다.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => '소셜 로그인 (OAuth)',
    'socialLoginHelp'           => '자격 증명은 .env 파일에 저장됩니다. Google 및 Facebook에서 앱을 등록하여 클라이언트 ID와 시크릿을 받으세요.',
    'socialLoginGoogleId'       => '클라이언트 ID',
    'socialLoginGoogleSecret'   => '클라이언트 시크릿',
    'socialLoginFbAppId'        => '앱 ID',
    'socialLoginFbAppSecret'    => '앱 시크릿',
    'socialLoginPlaceholderSecret' => '(기존 값을 유지하려면 비워 두세요)',
    'socialLoginSaveBtn'        => '소셜 로그인 설정 저장',
    'socialLoginSettingsSaved'  => '소셜 로그인 설정이 저장되었습니다.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => '게시 시 소셜 자동 공유',
    'socialSharingHelp'         => '"게시 시 공유"가 체크된 상태로 게시글이 게시되면, Pubvana는 설정된 소셜 계정에 자동으로 게시합니다.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'developer.twitter.com → 앱 → 키와 토큰에서 키를 받으세요.',
    'socialSharingApiKey'       => 'API 키',
    'socialSharingApiSecret'    => 'API 시크릿',
    'socialSharingAccessToken'  => '액세스 토큰',
    'socialSharingAccessSecret' => '액세스 시크릿',
    'socialSharingFbPage'       => 'Facebook 페이지',
    'socialSharingFbPageHelp'   => 'pages_manage_posts 권한이 있는 페이지 액세스 토큰이 필요합니다.',
    'socialSharingFbPageId'     => '페이지 ID',
    'socialSharingFbPageToken'  => '페이지 액세스 토큰',
    'socialSharingSaveBtn'      => '공유 설정 저장',
    'socialSharingSettingsSaved'=> '소셜 공유 설정이 저장되었습니다.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => '스팸 방지 (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana는 댓글 양식과 문의 양식을 스팸 봇으로부터 보호하기 위해 hCaptcha(개인 정보 보호, 비Google)를 사용합니다.',
    'spamHcaptchaFree'          => 'hCaptcha는 대부분의 사이트에서 무료입니다. hcaptcha.com에 가입한 후: 사이트 키를 얻으려면 Account → Sites → Add Site, 시크릿 키를 얻으려면 Account → Settings → Secret Key → Generate 를 따르세요. 아래에 두 키를 모두 입력하세요.',
    'spamHcaptchaSiteKey'       => '사이트 키',
    'spamHcaptchaSecretKey'     => '시크릿 키',
    'spamHcaptchaNote'          => '이 키가 설정되지 않은 경우 hCaptcha는 자동으로 건너뜁니다 — 로컬 개발에 안전합니다. 저장 후 위젯은 댓글 양식과 문의 페이지에 자동으로 나타납니다.',
    'spamSettingsSaved'         => '스팸 방지 설정이 저장되었습니다.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => '언어',
    'languageCode'              => '코드',
    'languageName'              => '이름',
    'languageDefault'           => '기본',
    'languageEnabled'           => '활성화됨',
    'languageMakeDefault'       => '기본으로 설정',
    'languageSetAsDefault'      => '{0}이(가) 기본 언어로 설정되었습니다.',
    'languageEnabled_msg'       => '{0}이(가) 활성화되었습니다.',
    'languageDisabled_msg'      => '{0}이(가) 비활성화되었습니다.',
    'languageNotFound'          => '언어를 찾을 수 없습니다.',
    'languageCannotDisable'     => '기본 언어는 비활성화할 수 없습니다.',
    'languageDirection'         => '방향',
    'languageNativeName'        => '현지 이름',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => '분석',
    'analyticsTotalViews'       => '총 조회수',
    'analyticsTopPosts'         => '인기 게시글',
    'analyticsReferrers'        => '주요 추천인',
    'analyticsLast7'            => '최근 7일',
    'analyticsLast30'           => '최근 30일',
    'analyticsLast90'           => '최근 90일',
    'analyticsChartTitle'       => '페이지뷰',
    'analyticsNoData'           => '이 기간의 분석 데이터가 없습니다.',
    'analyticsDomain'           => '도메인',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => '제휴 링크',
    'newAffiliateLinkTitle'     => '새 제휴 링크',
    'editAffiliateLinkTitle'    => '제휴 링크 편집',
    'affiliateName'             => '이름',
    'affiliateSlug'             => '슬러그',
    'affiliateDestination'      => '대상 URL',
    'affiliateActive'           => '활성',
    'affiliateClicks'           => '클릭수',
    'affiliateClicksTitle'      => '클릭수 - {0}',
    'affiliateTotal'            => '합계',
    'affiliateViewClicks'       => '클릭수 보기',

    // Affiliate flash messages
    'affiliateCreated'          => '제휴 링크가 생성되었습니다.',
    'affiliateUpdated'          => '제휴 링크가 업데이트되었습니다.',
    'affiliateDeleted'          => '제휴 링크가 삭제되었습니다.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => '깨진 링크',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP 상태',
    'brokenLinkError'           => '오류',
    'brokenLinkSource'          => '소스',
    'brokenLinkShowDismissed'   => '무시됨 표시',
    'brokenLinkHideDismissed'   => '무시됨 숨기기',
    'brokenLinkTimeout'         => '시간 초과',
    'brokenLinkBroken'          => '깨진 링크',
    'brokenLinkNone'            => '깨진 링크가 감지되지 않았습니다.',
    'brokenLinkNowReachable'    => '링크에 접근 가능해졌습니다 - 결과에서 제거되었습니다.',
    'brokenLinkStillBroken'     => '링크가 여전히 깨져 있습니다({0}).',
    'brokenLinkDismissed'       => '링크가 무시되었습니다.',
    'brokenLinksCliHint'        => '이 보고서를 채우려면 명령줄에서 전체 스캔을 실행하세요: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0}개의 문제 발견',
    'brokenLinksCount'          => '{0}개의 깨진 링크',
    'brokenLinksRecheck'        => '이 URL 재확인',
    'brokenLinksDismiss'        => '무시 (결과에서 숨기기)',
    'brokenLinksRunScan'        => '스캔 실행',
    'brokenLinksScanComplete'   => '스캔 완료: {0}개의 링크 확인, {1}개의 깨진 링크.',
    'timeout'                   => '시간 초과',
    'typePost'                  => '게시글',
    'typePage'                  => '페이지',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => '활동 로그',
    'activityLogType'           => '유형',
    'activityLogAction'         => '작업',
    'activityLogUser'           => '사용자',
    'activityLogDate'           => '날짜',
    'activityLogNote'           => '메모',
    'activityLogFilterAll'      => '모든 유형',
    'activityLogEmpty'          => '아직 기록된 활동이 없습니다.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => '백업 및 내보내기',
    'backupDownload'            => '백업 만들기 및 다운로드',
    'backupFiles'               => '사용 가능한 백업',
    'backupFilename'            => '파일 이름',
    'backupSize'                => '크기',
    'backupDate'                => '생성일',
    'backupGenerating'          => '백업 생성 중…',
    'backupNoFiles'             => '저장된 백업이 없습니다.',
    'backupFailed'              => '백업 실패: {0}',
    'backupDeleted'             => '백업이 삭제되었습니다.',
    'backupCannotDelete'        => '백업을 삭제할 수 없습니다.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IP는 SHA-256 해시로 저장됩니다 — 원시 개인 정보는 기록되지 않습니다.',
    'colTime'                   => '시간',
    'colIpHash'                 => 'IP 해시',
    'colReferrer'               => '추천인',
    'affiliateDirectReferrer'   => '직접',
    'affiliateNameHint'         => '내부 레이블 — 방문자에게 표시되지 않습니다.',
    'affiliateSlugHint'         => '영문자, 숫자, 하이픈, 밑줄만 사용 가능. 링크를 공유한 후에는 변경할 수 없습니다.',
    'affiliateDestHint'         => 'https://를 포함해야 합니다. 방문자는 여기로 301 리디렉션됩니다.',
    'affiliateInactiveHint'     => '비활성 링크는 404를 반환합니다.',
    'affiliateLinkCount'        => '{0}개의 링크',
    'colDomain'                 => '도메인',
    'commentAll'                => '전체',
    'commentPending'            => '대기 중',
    'commentTrash'              => '휴지통',
    'commentsNone'              => '{0} 댓글이 없습니다.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => '백업 만들기',
    'backupStarting'            => '백업 시작 중...',
    'backupNoneYet'             => '아직 백업이 없습니다. "백업 만들기"를 클릭하여 첫 번째 백업을 만드세요.',
    'backupsTitle'              => '백업',
    'backupRetentionNote'       => '최대 15개의 백업 보관 — 오래된 백업은 자동으로 삭제됩니다.',
    'backupRestoreConfirm'      => '이 백업을 복원하시겠습니까? 현재 상태의 백업이 먼저 생성됩니다.',
    'backupDeleteConfirm'       => '이 백업을 삭제하시겠습니까?',
    'colFilename'               => '파일 이름',
    'colVersion'                => '버전',
    'colTrigger'                => '트리거',
    'colSize'                   => '크기',
    'colDate'                   => '날짜',
    'colActions'                => '작업',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => '가져오기',
    'importWpHeading'           => 'WordPress에서 가져오기',
    'importWpHelp'              => '도구 → 내보내기를 통해 WordPress 사이트를 내보내고 아래에 .xml 파일을 업로드하세요.',
    'importChooseFile'          => 'WXR 파일(.xml) 선택',
    'importDryRun'              => '드라이런(미리보기만 - 저장되지 않음)',
    'importRunBtn'              => '가져오기 실행',
    'importNoValidFile'         => '유효한 WordPress WXR 내보내기 파일을 업로드하세요.',
    'importOnlyXml'             => '.xml 파일만 허용됩니다.',
    'importFileTooLarge'        => '가져오기 파일이 너무 큽니다. 최대 크기는 50MB입니다.',
    'importResultsHeading'      => '가져오기 결과',
    'importDryRunNote'          => '드라이런 - 데이터가 저장되지 않았습니다.',
    'importDryRunLabel'         => '(드라이런 — 데이터 작성 없음)',
    'importComplete'            => '가져오기 완료',
    'importCreated'             => '생성됨',
    'importSkipped'             => '건너뜀',
    'importErrors'              => '오류:',
    'importInstructions'        => '<strong>도구 → 내보내기 → 모든 콘텐츠</strong>에서 WordPress 콘텐츠를 내보내고 여기에 <code>.xml</code> 파일을 업로드하세요. Pubvana는 게시글, 페이지, 카테고리, 태그, 저자, 댓글을 가져옵니다.',
    'importCliTitle'            => 'CLI 가져오기',
    'importCliHint'             => '명령줄에서 가져오기 도구를 실행할 수도 있습니다:',
    'importCliDryRunHint'       => '<code>--dry-run</code> 플래그는 데이터베이스에 쓰지 않고 가져올 내용을 표시합니다.',
    'importWhatTitle'           => '가져오는 항목',
    'importItemPosts'           => '게시글(제목, 내용, 발췌, 슬러그, 상태)',
    'importItemPages'           => '페이지',
    'importItemCategories'      => '카테고리(계층 포함)',
    'importItemTags'            => '태그',
    'importItemAuthors'         => '저자(구독자 계정으로 생성)',
    'importItemComments'        => '댓글',
    'importItemMedia'           => '미디어 파일(콘텐츠의 URL 유지)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => '업데이트',
    'updatesCurrentVersion'     => '현재 버전',
    'updatesLatestVersion'      => '최신 버전',
    'updatesUpToDate'           => 'Pubvana가 최신 상태입니다.',
    'updatesAvailable'          => '업데이트 있음: {0}',
    'updatesCheckBtn'           => '업데이트 확인',
    'updatesReleaseNotes'       => '릴리스 노트',
    'updatesHowToApply'         => '업데이트 적용 방법',
    'updatesCacheCleared'       => '업데이트 캐시가 지워졌습니다 - 다시 확인 중.',
    'updatesExtCapped'          => '업데이트 있음: {0}(애드온 안전)',
    'updatesNewerAvailable'     => 'Pubvana {0}를 사용할 수 있습니다 - 아래 나열된 애드온을 업데이트하여 잠금을 해제하세요.',

    // Addon Updates
    'updatesExtTitle'               => '애드온',
    'updatesExtCheckAll'            => '모두 확인',
    'updatesExtUpdateAll'           => '모두 업데이트',
    'updatesExtCheckAllType'        => '모든 {0} 확인',
    'updatesExtUpdateAllType'       => '모든 {0} 업데이트',
    'updatesExtNoInstalled'         => '{0}이(가) 설치되지 않았습니다.',
    'updatesExtColName'             => '이름',
    'updatesExtColVersion'          => '버전',
    'updatesExtColLatest'           => '최신',
    'updatesExtColAutoUpdate'       => '자동 업데이트',
    'updatesExtColStatus'           => '상태',
    'updatesExtColActions'          => '작업',
    'updatesExtBundled'             => '코어 번들',
    'updatesExtNoSource'            => '업데이트 소스 없음',
    'updatesExtFailed'              => '실패',
    'updatesExtUpdatedAt'           => '{0}에 업데이트됨',
    'updatesExtAvailable'           => '업데이트 있음',
    'updatesExtUpToDate'            => '최신 상태',
    'updatesExtUpdate'              => '업데이트',
    'updatesExtChecking'            => '확인 중...',
    'updatesExtUpdating'            => '업데이트 중...',
    'updatesExtUpdated'             => '업데이트됨',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => '업데이트 확인',
    'updatesConfirmBody'            => '사이트를 백업하고 업데이트를 다운로드하여 적용합니다.',
    'updatesConfirmSafe'            => '<code>.env</code>, <code>App.php</code>, <code>Database.php</code>는 절대 덮어쓰이지 않습니다.',
    'updatesConfirmBtn'             => '지금 업데이트',

    // Addon Update All Modal
    'updatesExtAllTitle'            => '모든 애드온 업데이트',
    'updatesExtAllBody'             => '업데이트가 보류 중인 모든 애드온을 업데이트합니다.',
    'updatesExtAllNote'             => '자동 업데이트가 비활성화된 애드온도 업데이트됩니다.',
    'updatesExtAllBtn'              => '모두 업데이트',

    'updatesExtBadge'               => '업데이트: v{0}',
    'updatesExtGoToUpdates'         => '업데이트',

    // Update Settings
    'updatesSettingsTitle'          => '업데이트 설정',
    'updatesAutoUpdateLabel'        => 'Pubvana 자동 업데이트',
    'updatesAutoUpdateManual'       => '수동',
    'updatesAutoUpdateAuto'         => '자동',
    'updatesAutoUpdateHelp'         => '활성화하면 호환성을 깨는 변경 사항 없는 Pubvana 업데이트가 자동으로 적용됩니다.',
    'updatesCheckMethodLabel'       => '업데이트 확인 방법',
    'updatesCheckMethodPageload'    => '페이지 로드',
    'updatesCheckMethodCron'        => 'Cron 작업',
    'updatesCheckMethodHelp'        => '페이지 로드는 모든 요청에서 확인합니다(24시간 캐시). Cron은 서버 cron 작업이 필요합니다.',
    'updatesCronCommand'            => 'Cron 명령',
    'updatesCronHelp'               => '업데이트 확인을 매일 실행하려면 서버의 crontab에 다음을 추가하세요:',
    'updatesSettingsSaved'          => '업데이트 설정이 저장되었습니다.',

    // Compatibility
    'compatWarningTitle'            => '호환성 경고',
    'compatNotCompatible'           => '일부 설치된 애드온이 이 버전과 호환되지 않습니다.',
    'compatRequiresUpdate'          => '하지만 다음 애드온을 먼저 업데이트해야 합니다:',
    'compatSupportsUpTo'            => '{0}까지 지원',
    'compatRequiresMin'             => 'Pubvana {0}+ 필요',
    'compatNotDeclared'             => '다음 애드온은 Pubvana {0}와의 호환성을 선언하지 않았습니다. 업데이트 후 작동하지 않을 수 있습니다:',
    'compatColType'                 => '유형',
    'compatColName'                 => '이름',
    'compatColVersion'              => '호환성',
    'compatRemoveHint'              => '문제 발생 시 호환되지 않는 애드온을 제거하거나 기본 테마로 전환할 수 있습니다. 모든 업데이트 전에 백업이 생성됩니다.',
    'compatMaxVersion'              => '최대 호환 버전: {0}',
    'compatMinVersion'              => 'Pubvana {0}+ 필요',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => '게시글 일정',
    'scheduleNoScheduled'       => '예약된 게시글이 없습니다.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => '수정 내역 - {0}',
    'revisionPageTitle'         => '수정 내역 - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => '관리 패널에 접근하려면 로그인해야 합니다.',
    'dirNotWritable'            => '디렉토리에 쓰기 권한이 없습니다: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0}이(가) 잘못 구성되었습니다. 최종 사용자라면 개발자에게 문의하세요. 개발자라면 문서를 참조하세요.',
    'addonMisconfiguredLink'    => '{0}이(가) 잘못 구성되었습니다. 최종 사용자라면 <a href="{1}">개발자에게 문의</a>하세요. 개발자라면 <a href="https://github.com/enlivenapp/pubvana">문서를 참조</a>하세요.',
    'licenseExpiringSoon'       => '{0}의 라이선스가 {1}에 만료됩니다. 라이선스가 만료되면 {0}이(가) 비활성화됩니다.',
    'licenseExpiredDeactivated' => '라이선스가 만료되어 {0}이(가) 비활성화되었습니다.',
    'addonDeactivated'          => '{0}이(가) 비활성화되었습니다. 이유: {1}.',
    'widgetValidationFailed'    => "위젯 ''{0}''을(를) 검증할 수 없습니다. 개발자에게 문의하거나 애드온을 제거하세요.",
    'widgetValidationFailedLink' => "위젯 ''{0}''을(를) 검증할 수 없습니다. <a href=\"{1}\">개발자에게 문의</a>하거나 애드온을 제거하세요.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => '비활성화됨: 라이선스 만료',
    'addonDeactivatedTampered'  => '비활성화됨: 잘못 구성됨',
    'addonDeactivatedNoLicense' => '비활성화됨: 유효한 라이선스 없음',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => '비활성화됨',
    'addonDisabledInvalidJson'  => '시스템: {0}의 {1}이(가) 유효하지 않거나 읽을 수 없습니다.',
    'addonDisabledMissingFields' => '시스템: {0}에 필수 필드가 없습니다: {1}.',
    'addonDisabledPhpFiles'     => '시스템: {0}에 PHP 파일이 포함되어 있습니다. 위젯은 JSON + 템플릿만 가능합니다.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => '{0}을(를) 활성화하려면 유효한 라이선스가 필요합니다.',
    'licenseInvalidActivation'  => '{0}의 라이선스 검증에 실패했습니다. 라이선스 키를 확인하세요.',
    'licenseExpiredActivation'  => '{0}의 라이선스가 만료되었습니다. 활성화하려면 갱신하세요.',
    'licenseCheckUnreachable'   => '{0}의 라이선스를 확인할 수 없습니다. 라이선스 서버에 연결할 수 없습니다. 나중에 다시 시도해 주세요.',
    'activationBlockedTampered' => '{0}이(가) 잘못 구성되어 활성화할 수 없습니다.',
    'activationBlockedBundled'  => '{0}을(를) 활성화할 수 없습니다: Pubvana 애드온만 번들로 표시할 수 있습니다.',
    'activationBlockedNoUrls'   => '{0}을(를) 활성화할 수 없습니다: 유료 애드온에는 라이선스 확인 URL이 포함되어야 합니다.',
    'activationBlockedFreeFlag' => '{0}을(를) 활성화할 수 없습니다: Pubvana 애드온은 무료로 표시할 수 없습니다.',
    'activationBlockedDisabled' => '{0}이(가) 구성 오류로 인해 활성화할 수 없습니다. 정보 파일을 확인하세요.',

    // Third-party license
    'licenseThirdPartyLabel'    => '3rd 파티',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => '업데이트 시작 중...',
    'updateCheckLabel'           => '업데이트 확인:',
    'updateAvailable'            => 'Pubvana {0}을(를) 사용할 수 있습니다!',
    'updateRunning'              => '현재 {0}을(를) 실행 중입니다.',
    'updateBreakingChanges'      => '호환성을 깨는 변경 사항',
    'updateMigrationNotes'       => '마이그레이션 노트',
    'updateNotices'              => '알림',
    'updatePreflightTitle'       => '사전 확인',
    'updateToVersion'            => 'Pubvana {0}으로 업데이트',
    'updatePreflightFailed'      => '필수 사전 확인 중 하나 이상이 실패했습니다. 업데이트 전에 해결해 주세요.',
    'updateUpToDate'             => 'Pubvana가 최신 상태입니다. 버전 {0}을(를) 실행 중입니다.',
    'updateAnyway'               => '그래도 업데이트',
    'updateAvailableTooltip'     => 'Pubvana {0} 사용 가능',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(본인)',
    'usersNone'                  => '사용자를 찾을 수 없습니다.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => '계정 활성화',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => '프로필 세부 정보',
    'profileDisplayNameHint'     => '게시된 게시글에서 사용자 이름 대신 표시됩니다.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP 또는 GIF. 최대 10MB.',
    'profileSocialHandles'       => '소셜 핸들',
    'preview'                    => '미리보기',
    'website'                    => '웹사이트',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => '이중 인증',
    'totpActiveDesc'             => 'TOTP 이중 인증이 계정에서 활성화되어 있습니다. 로그인할 때마다 인증 앱의 6자리 코드를 입력해야 합니다.',
    'totpCurrentCode'            => '현재 코드',
    'totpInactiveDesc'           => '계정에 추가 보안 레이어를 추가하세요. 활성화하면 각 로그인 시 인증 앱의 코드를 입력해야 합니다.',
    'totpEnable'                 => '이중 인증 활성화',
    'totpScanInstructions'       => '인증 앱(Google Authenticator, Authy, 1Password 등)을 열고 이 QR 코드를 스캔하세요.',
    'totpManualEntry'            => '스캔할 수 없나요? 이 코드를 수동으로 입력하세요:',
    'totpConfirmInstructions'    => '스캔 후 설정을 확인하기 위해 앱에 표시된 6자리 코드를 입력하세요.',
    'totpRecoveryWarning'        => '복구 코드를 보관하세요. 인증 앱에 접근할 수 없게 되면 로그인할 수 없습니다. 사이트 관리자에게 연락하여 2FA를 재설정하세요.',

];
