<?php

/**
 * Pubvana CMS - Blog / Public-facing language strings (French)
 *
 * AI Translated: verification needed from native speaker
 *
 * Usage: lang('Blog.keyName')
 */

return [

    // =========================================================================
    // Common public UI
    // =========================================================================

    'home'              => 'Accueil',
    'blog'              => 'Blog',
    'readMore'          => 'Lire la suite',
    'viewAll'           => 'Voir tout',
    'noPostsYet'        => 'Aucun article pour l\'instant. Revenez bientôt !',
    'search'            => 'Recherche',
    'searchPlaceholder' => 'Rechercher…',
    'searchPostsPlaceholder' => 'Rechercher des articles…',

    // RSS / feeds
    'rssFeed'           => 'RSS',
    'sitemap'           => 'Plan du site',
    'allRightsReserved' => 'Tous droits réservés.',

    // =========================================================================
    // Home / listing
    // =========================================================================

    'latestPosts'       => 'Derniers articles',

    // =========================================================================
    // Post detail
    // =========================================================================

    'postedOn'          => 'Publié le',
    'views'             => '{0} vues',
    'readingTime'       => '{0} min de lecture',
    'publishedBy'       => 'Par',
    'inCategory'        => 'dans',
    'tags'              => 'Tags',

    // =========================================================================
    // Preview mode banner
    // =========================================================================

    'previewModeBanner' => 'Mode aperçu - Cet article n\'est pas visible publiquement',

    // =========================================================================
    // Premium paywall
    // =========================================================================

    'paywallTitle'          => 'Membres uniquement',
    'paywallMessage'        => 'Cet article est disponible pour les membres inscrits. Connectez-vous ou créez un compte gratuit pour continuer la lecture.',
    'paywallSignIn'         => 'Se connecter',
    'paywallCreateAccount'  => 'Créer un compte',

    // =========================================================================
    // Author card
    // =========================================================================

    'authorCardLabel'   => "À propos de l'auteur",
    'unknownAuthor'     => 'Auteur inconnu',

    // =========================================================================
    // Category page
    // =========================================================================

    'categoryHeading'   => 'Catégorie : {0}',
    'noPostsInCategory' => 'Aucun article dans cette catégorie pour l\'instant.',

    // =========================================================================
    // Tag page
    // =========================================================================

    'tagHeading'        => 'Tag : {0}',
    'noPostsWithTag'    => 'Aucun article avec ce tag.',

    // =========================================================================
    // Archive page
    // =========================================================================

    'archiveHeading'    => 'Archive : {0}',
    'noPostsInPeriod'   => 'Aucun article dans cette période.',

    // =========================================================================
    // Search results
    // =========================================================================

    'searchResultsHeading'  => 'Résultats de recherche',
    'searchShowingFor'      => 'Résultats pour : {0}',
    'searchNoResults'       => 'Aucun article trouvé pour « {0} ».',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsHeading'       => 'Commentaires ({0})',
    'commentFormTitle'      => 'Laisser un commentaire',
    'commentLabel'          => 'Commentaire *',
    'commentPostBtn'        => 'Publier le commentaire',
    'commentModerated'      => 'Les commentaires sont modérés avant d\'apparaître.',
    'commentLoginRequired'  => 'pour laisser un commentaire.',
    'commentLoginLink'      => 'Connectez-vous',
    'commentAwaitModeration'=> 'Votre commentaire est en attente de modération.',
    'commentPosted'         => 'Votre commentaire a été publié.',
    'commentLoginToComment' => 'Vous devez être connecté pour commenter.',
    'commentTooFast'        => 'Vous commentez trop rapidement. Veuillez attendre quelques minutes avant de réessayer.',

    // =========================================================================
    // Contact form
    // =========================================================================

    'contactTitle'          => 'Contact',
    'contactName'           => 'Nom',
    'contactEmail'          => 'E-mail',
    'contactMessage'        => 'Message',
    'contactSendBtn'        => 'Envoyer le message',
    'contactSent'           => 'Votre message a été envoyé !',
    'contactCaptchaFail'    => 'Échec de la vérification du captcha. Veuillez réessayer.',
    'contactSubject'        => 'Formulaire de contact : {0}',

    // =========================================================================
    // Pagination
    // =========================================================================

    'pageNavLabel'      => 'Navigation de page',
    'prevPage'          => 'Précédent',
    'nextPage'          => 'Suivant',

    // =========================================================================
    // Errors / 404
    // =========================================================================

    'pageNotFound'      => 'Page introuvable.',
    'pageNotFoundTitle' => '404 - Page introuvable',

    // =========================================================================
    // Maintenance mode
    // =========================================================================

    'maintenanceTitle'  => 'En maintenance',
    'maintenanceBody'   => 'Nous effectuons une maintenance planifiée. Nous serons de retour bientôt - merci de votre patience !',

    // Language
    'language'          => 'Langue',

    // =========================================================================
    // Account / Profile
    // =========================================================================

    'profileTitle'            => 'Mon profil',
    'profileBasicInfo'        => 'Informations de base',
    'profileUsername'          => 'Nom d\'utilisateur',
    'profileEmail'            => 'E-mail',
    'profilePassword'         => 'Mot de passe',
    'profilePasswordConfirm'  => 'Confirmer le mot de passe',
    'profilePasswordHelp'     => 'Laissez vide pour conserver le mot de passe actuel.',
    'profileSave'             => 'Enregistrer les modifications',
    'profileUpdated'          => 'Profil mis à jour avec succès.',
    'profileUsernameRequired' => 'Le nom d\'utilisateur est obligatoire.',
    'profileUsernameTaken'    => 'Ce nom d\'utilisateur est déjà pris.',
    'profileEmailRequired'    => 'L\'adresse e-mail est obligatoire.',
    'profileEmailTaken'       => 'Cette adresse e-mail est déjà utilisée.',
    'profilePasswordMismatch' => 'Les mots de passe ne correspondent pas.',
    'profilePasswordTooShort' => 'Le mot de passe doit comporter au moins 8 caractères.',

    'profileAuthorInfo'       => 'Profil d\'auteur',
    'profileDisplayName'      => 'Nom affiché',
    'profileBio'              => 'Biographie',
    'profileAvatar'           => 'Avatar',
    'profileAvatarChange'     => 'Changer l\'avatar',
    'profileAvatarUpload'     => 'Téléverser',
    'profileWebsite'          => 'Site web',
    'profileTwitter'          => 'Twitter',
    'profileFacebook'         => 'Facebook',
    'profileLinkedin'         => 'LinkedIn',
    'profileAvatarUpdated'    => 'Avatar mis à jour avec succès.',
    'profileAvatarInvalid'    => 'Fichier invalide.',
    'profileAvatarTypeError'  => 'Seules les images JPEG, PNG, WebP et GIF sont acceptées.',
    'profileAvatarTooLarge'   => 'L\'avatar doit peser 2 Mo ou moins.',
    'profileAvatarNotAllowed' => 'Le téléversement d\'avatars est disponible pour les auteurs et au-delà.',

    'login'                         => 'Se connecter',
    'adminPanel'                    => 'Panneau d\'administration',

    'profileUpdatedRelogin'         => 'Profil mis à jour. Veuillez vous reconnecter.',
    'profileUsernameChangedSubject' => 'Votre nom d\'utilisateur a été modifié',
    'profileUsernameChangedBody'    => 'Votre nom d\'utilisateur a été changé de « {0} » à « {1} ». Si vous n\'êtes pas à l\'origine de ce changement, veuillez contacter immédiatement l\'administrateur du site.',
    'profileEmailChangedSubject'    => 'Votre adresse e-mail a été modifiée',
    'profileEmailChangedBody'       => 'Votre adresse e-mail a été changée de « {0} » à « {1} ». Si vous n\'êtes pas à l\'origine de ce changement, veuillez contacter immédiatement l\'administrateur du site.',
    'profilePasswordChangedSubject' => 'Votre mot de passe a été modifié',
    'profilePasswordChangedBody'    => 'Votre mot de passe a été modifié récemment. Si vous n\'êtes pas à l\'origine de ce changement, veuillez contacter immédiatement l\'administrateur du site.',

];
