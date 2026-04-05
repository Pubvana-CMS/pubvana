<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (English)
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

    'home'              => 'Home',
    'blog'              => 'Blog',
    'readMore'          => 'Read More',
    'viewAll'           => 'View All',
    'noPostsYet'        => 'No posts yet. Check back soon!',
    'search'            => 'Search',
    'searchPlaceholder' => 'Search…',
    'searchPostsPlaceholder' => 'Search posts…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Sitemap',
    'allRightsReserved' => 'All rights reserved.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Latest Posts',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Posted on',
    'views'             => '{0} views',
    'readingTime'       => '{0} min read',
    'publishedBy'       => 'By',
    'inCategory'        => 'in',
    'tags'              => 'Tags',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Preview Mode - This post is not publicly visible',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Members Only',
    'paywallMessage'        => 'This post is available to registered members. Sign in or create a free account to continue reading.',
    'paywallSignIn'         => 'Sign In',
    'paywallCreateAccount'  => 'Create Account',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => 'About the Author',
    'unknownAuthor'     => 'Unknown Author',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Category: {0}',
    'noPostsInCategory' => 'No posts in this category yet.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Tag: {0}',
    'noPostsWithTag'    => 'No posts with this tag.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archive: {0}',
    'noPostsInPeriod'   => 'No posts in this period.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Search Results',
    'searchShowingFor'      => 'Showing results for: {0}',
    'searchNoResults'       => 'No posts found for "{0}".',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Comments ({0})',
    'commentFormTitle'      => 'Leave a Comment',
    'commentLabel'          => 'Comment *',
    'commentPostBtn'        => 'Post Comment',
    'commentModerated'      => 'Comments are moderated before appearing.',
    'commentLoginRequired'  => 'to leave a comment.',
    'commentLoginLink'      => 'Log in',
    'commentAwaitModeration'=> 'Your comment is awaiting moderation.',
    'commentPosted'         => 'Your comment has been posted.',
    'commentLoginToComment' => 'You must be logged in to comment.',
    'commentTooFast'        => 'You are commenting too quickly. Please wait a few minutes before trying again.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Contact',
    'contactName'           => 'Name',
    'contactEmail'          => 'Email',
    'contactMessage'        => 'Message',
    'contactSendBtn'        => 'Send Message',
    'contactSent'           => 'Your message has been sent!',
    'contactCaptchaFail'    => 'Captcha verification failed. Please try again.',
    'contactSubject'        => 'Contact Form: {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Page navigation',
    'prevPage'          => 'Previous',
    'nextPage'          => 'Next',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Page not found.',
    'pageNotFoundTitle' => '404 - Page Not Found',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'Under Maintenance',
    'maintenanceBody'   => "We're performing scheduled maintenance. We'll be back soon - thanks for your patience!",

    // Language
    'language'          => 'Language',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'My Profile',
    'profileBasicInfo'        => 'Basic Information',
    'profileUsername'          => 'Username',
    'profileEmail'            => 'Email',
    'profilePassword'         => 'Password',
    'profilePasswordConfirm'  => 'Confirm Password',
    'profilePasswordHelp'     => 'Leave blank to keep current password.',
    'profileSave'             => 'Save Changes',
    'profileUpdated'          => 'Profile updated successfully.',
    'profileUsernameRequired' => 'Username is required.',
    'profileUsernameTaken'    => 'That username is already taken.',
    'profileEmailRequired'    => 'Email is required.',
    'profileEmailTaken'       => 'That email is already in use.',
    'profilePasswordMismatch' => 'Passwords do not match.',
    'profilePasswordTooShort' => 'Password must be at least 8 characters.',

    'profileAuthorInfo'       => 'Author Profile',
    'profileDisplayName'      => 'Display Name',
    'profileBio'              => 'Bio',
    'profileAvatar'           => 'Avatar',
    'profileAvatarChange'     => 'Change Avatar',
    'profileAvatarUpload'     => 'Upload',
    'profileWebsite'          => 'Website',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avatar updated successfully.',
    'profileAvatarInvalid'    => 'Invalid file upload.',
    'profileAvatarTypeError'  => 'Only JPEG, PNG, WebP, and GIF images are accepted.',
    'profileAvatarTooLarge'   => 'Avatar must be 2 MB or smaller.',
    'profileAvatarNotAllowed' => 'Avatar uploads are available for authors and above.',

    'login'                         => 'Log In',
    'adminPanel'                    => 'Admin Panel',

    'profileUpdatedRelogin'         => 'Profile updated. Please log in again.',
    'profileUsernameChangedSubject' => 'Your username has been changed',
    'profileUsernameChangedBody'    => 'Your username was changed from "{0}" to "{1}". If you did not make this change, please contact the site administrator immediately.',
    'profileEmailChangedSubject'    => 'Your email address has been changed',
    'profileEmailChangedBody'       => 'Your email address was changed from "{0}" to "{1}". If you did not make this change, please contact the site administrator immediately.',
    'profilePasswordChangedSubject' => 'Your password has been changed',
    'profilePasswordChangedBody'    => 'Your password was recently changed. If you did not make this change, please contact the site administrator immediately.',

];
