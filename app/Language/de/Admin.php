<?php

/**
 * Pubvana CMS - Admin language strings (German)
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
    'save'              => 'Speichern',
    'saveChanges'       => 'Änderungen speichern',
    'cancel'            => 'Abbrechen',
    'edit'              => 'Bearbeiten',
    'delete'            => 'Löschen',
    'create'            => 'Erstellen',
    'add'               => 'Hinzufügen',
    'back'              => 'Zurück',
    'view'              => 'Anzeigen',
    'apply'             => 'Anwenden',
    'install'           => 'Installieren',
    'update'            => 'Aktualisieren',
    'refresh'           => 'Aktualisieren',
    'activate'          => 'Aktivieren',
    'deactivate'        => 'Deaktivieren',
    'enable'            => 'Aktivieren',
    'disable'           => 'Deaktivieren',
    'disabled'          => 'Deaktiviert',
    'approve'           => 'Genehmigen',
    'spam'              => 'Spam',
    'trash'             => 'Papierkorb',
    'restore'           => 'Wiederherstellen',
    'dismiss'           => 'Schließen',
    'recheck'           => 'Erneut prüfen',
    'clickToCopy'       => 'Zum Kopieren klicken',
    'download'          => 'Herunterladen',
    'upload'            => 'Hochladen',
    'import'            => 'Importieren',
    'export'            => 'Exportieren',
    'publish'           => 'Veröffentlichen',
    'unpublish'         => 'Veröffentlichung zurückziehen',
    'logout'            => 'Abmelden',
    'viewSite'          => 'Website anzeigen',
    'newPost'           => 'Neuer Beitrag',
    'buyNow'            => 'Jetzt kaufen',
    'visitStore'        => 'Shop besuchen',
    'loadMore'          => 'Mehr laden',

    // Table headers / labels
    'title'             => 'Titel',
    'name'              => 'Name',
    'slug'              => 'Slug',
    'status'            => 'Status',
    'date'              => 'Datum',
    'actions'           => 'Aktionen',
    'author'            => 'Autor',
    'views'             => 'Aufrufe',
    'type'              => 'Typ',
    'url'               => 'URL',
    'description'       => 'Beschreibung',
    'role'              => 'Rolle',
    'email'             => 'E-Mail',
    'username'          => 'Benutzername',
    'active'            => 'Aktiv',
    'version'           => 'Version',
    'size'              => 'Größe',
    'clicks'            => 'Klicks',
    'total'             => 'Gesamt',
    'platform'          => 'Plattform',
    'label'             => 'Bezeichnung',
    'order'             => 'Reihenfolge',
    'source'            => 'Quelle',
    'content'           => 'Inhalt',
    'excerpt'           => 'Auszug',
    'details'           => 'Details',
    'contentType'       => 'Inhaltstyp',
    'seo'               => 'SEO',
    'metaTitle'         => 'Meta-Titel',
    'metaDescription'   => 'Meta-Beschreibung',

    // Status badges
    'published'         => 'Veröffentlicht',
    'draft'             => 'Entwurf',
    'scheduled'         => 'Geplant',
    'pending'           => 'Ausstehend',
    'safe'              => 'Sicher',
    'notSafe'           => 'Nicht sicher',
    'malicious'         => 'Bösartig',
    'safetyUnknown'     => 'Unbekannt',
    'inactive'          => 'Inaktiv',
    'installed'         => 'Installiert',
    'free'              => 'Kostenlos',
    'premium'           => 'Premium',
    'all'               => 'Alle',

    // Confirmations
    'confirmDelete'         => 'Sind Sie sicher, dass Sie dieses Element löschen möchten?',
    'confirmDeletePost'     => 'Diesen Beitrag löschen?',
    'confirmDeletePage'     => 'Diese Seite löschen?',
    'confirmDeleteComment'  => 'Diesen Kommentar dauerhaft löschen?',
    'confirmDeleteUser'     => 'Diesen Benutzer löschen?',
    'confirmDeleteMedia'    => 'Löschen?',
    'confirmDeleteBackup'   => 'Diese Sicherungsdatei löschen?',
    'confirmBulkAction'     => 'Massenaktion auf ausgewählte Beiträge anwenden?',

    // Empty states
    'noPostsYet'        => 'Noch keine Beiträge. {0}',
    'noResultsFound'    => 'Keine Ergebnisse gefunden.',
    'noCommentsYet'     => 'Keine ausstehenden Kommentare.',
    'noMediaYet'        => 'Noch keine Medien.',
    'noItemsFound'      => 'Keine Elemente im Marktplatz gefunden.',
    'noCategoriesYet'   => 'Noch keine Kategorien.',
    'noTagsYet'         => 'Noch keine Tags.',
    'noRevisionsYet'    => 'Keine Revisionen gefunden.',

    // Misc common
    'permissionDenied'  => 'Zugriff verweigert.',
    'notFound'          => 'Datensatz nicht gefunden.',
    'commasSeparated'   => 'Kommagetrennt',
    'optional'          => 'Optional',
    'required'          => 'Erforderlich',
    'enabled'           => 'Aktiviert',
    'selected'          => '{0} Beitrag/Beiträge ausgewählt',
    'published_count'   => '{0} veröffentlicht',
    'pending_count'     => '{0} ausstehend',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Dashboard',
    'navContent'        => 'Inhalt',
    'navAppearance'     => 'Darstellung',
    'navUsersAndSite'   => 'Benutzer & Website',
    'navTools'          => 'Werkzeuge',
    'navMarketplace'    => 'Marktplatz',
    'navPlugins'        => 'Plugins',
    'navPosts'          => 'Beiträge',
    'navSchedule'       => 'Zeitplan',
    'navPages'          => 'Seiten',
    'navCategories'     => 'Kategorien',
    'navTags'           => 'Tags',
    'navComments'       => 'Kommentare',
    'navMedia'          => 'Medien',
    'navImport'         => 'Importieren',
    'navThemes'         => 'Themes',
    'navWidgets'        => 'Widgets',
    'navNavigation'     => 'Navigation',
    'navUsers'          => 'Benutzer',
    'navSocialLinks'    => 'Social-Links',
    'navRedirects'      => 'Weiterleitungen',
    'navLanguages'      => 'Sprachen',
    'navSettings'       => 'Einstellungen',
    'navAnalytics'      => 'Analytik',
    'navAffiliates'     => 'Affiliate-Links',
    'navBrokenLinks'    => 'Defekte Links',
    'navActivityLog'    => 'Aktivitätsprotokoll',
    'navBackup'         => 'Sicherung & Export',
    'navUpdates'        => 'Updates',
    'navBrowse'         => 'Durchsuchen',
    'navLicenses'       => 'Lizenzen',
    'navPubvanaStore'   => 'Pubvana-Shop',
    'navUpdateAvailable'=> 'Update verfügbar',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Abmelden?',
    'logoutModalBody'   => 'Klicken Sie unten auf „Abmelden", um Ihre Sitzung zu beenden.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Dashboard',
    'dashStats'             => 'Statistiken',
    'dashPosts'             => 'Beiträge',
    'dashPages'             => 'Seiten',
    'dashComments'          => 'Kommentare',
    'dashUsers'             => 'Benutzer',
    'dashRecentPosts'       => 'Aktuelle Beiträge',
    'dashPendingComments'   => 'Ausstehende Kommentare',
    'dashViewAll'           => 'Alle anzeigen',
    'dashCreateOne'         => 'Jetzt erstellen!',
    'dashNoPosts'           => 'Noch keine Beiträge.',
    'dashNoPendingComments' => 'Keine ausstehenden Kommentare.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Beiträge',
    'newPostTitle'          => 'Neuer Beitrag',
    'editPostTitle'         => 'Beitrag bearbeiten: {0}',
    'copyPreviewLink'       => 'Vorschaulink kopieren',
    'backToPosts'           => 'Zurück zu Beiträgen',
    'postTitleField'        => 'Titel *',
    'postEditor'            => 'Editor',
    'postHtmlEditor'        => 'HTML-Editor',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Auszug',
    'postExcerptPlaceholder'=> 'Optionale Kurzzusammenfassung...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Meta-Titel',
    'postMetaDescription'   => 'Meta-Beschreibung',
    'postPublishSection'    => 'Veröffentlichen',
    'postStatus'            => 'Status',
    'postStatusDraft'       => 'Entwurf',
    'postStatusPublished'   => 'Veröffentlicht',
    'postStatusScheduled'   => 'Geplant',
    'postScheduledAt'       => 'Geplantes Datum & Uhrzeit',
    'postFeatured'          => 'Hervorgehobener Beitrag',
    'postMembersOnly'       => 'Nur für Mitglieder',
    'postShareOnPublish'    => 'Bei Veröffentlichung in sozialen Netzwerken teilen',
    'postSaveBtn'           => 'Beitrag speichern',
    'postFeaturedImage'     => 'Vorschaubild',
    'postFeaturedImagePlaceholder' => 'URL oder Upload-Pfad…',
    'postCategories'        => 'Kategorien',
    'postTags'              => 'Tags',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'Revisionen',
    'postRevisionCount'     => '{0} Revision(en)',
    'postPreview'           => 'Vorschau',
    'postBulkAction'        => '- Aktion auswählen -',
    'postBulkPublish'       => 'Veröffentlichen',
    'postBulkUnpublish'     => 'Zurückziehen (als Entwurf setzen)',
    'postBulkDelete'        => 'Löschen',

    // Post flash messages
    'postCreated'           => 'Beitrag erfolgreich erstellt.',
    'postUpdated'           => 'Beitrag aktualisiert.',
    'scheduledDateMustBeFuture' => 'Das geplante Datum muss in der Zukunft liegen.',
    'postDeleted'           => 'Beitrag gelöscht.',
    'postBulkUpdated'       => '{0} Beitrag/Beiträge aktualisiert.',
    'postBulkInvalid'       => 'Ungültige Massenaktion.',
    'postPermission'        => 'Sie können nur Ihre eigenen Beiträge bearbeiten.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revisionen: {0}',
    'revisionTitle'         => 'Revision — {0}',
    'revisionShowTitle'     => 'Revision',
    'revisionsBackToPost'   => 'Zurück zum Beitrag',
    'revisionsBackToList'   => 'Zurück zur Revisionsliste',
    'revisionRestored'      => 'Beitrag auf Revision vom {0} wiederhergestellt.',
    'revisionRestoreBtn'    => 'Diese Revision wiederherstellen',
    'revisionSaved'         => 'Gespeichert',
    'revisionBy'            => 'Von',
    'revisionOn'            => 'Am',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Seiten',
    'newPageTitle'          => 'Neue Seite',
    'editPageTitle'         => 'Seite bearbeiten',
    'pageSlugInUse'         => "Slug '{0}' wird bereits verwendet.",
    'pageCannotDelete'      => 'Diese Seite kann nicht gelöscht werden.',
    'slugAutoGenHint'       => 'wird automatisch aus dem Titel generiert, wenn leer gelassen',
    'slugCannotChange'      => 'kann nicht geändert werden',
    'colSystem'             => 'System',
    'system'                => 'System',

    // Page flash messages
    'pageCreated'           => 'Seite erstellt.',
    'pageUpdated'           => 'Seite aktualisiert.',
    'pageDeleted'           => 'Seite gelöscht.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Kategorien',
    'newCategoryTitle'      => 'Neue Kategorie',
    'editCategoryTitle'     => 'Kategorie bearbeiten',
    'categoryName'          => 'Name',
    'categoryDescription'   => 'Beschreibung',
    'categoryPostCount'     => 'Beitragsanzahl',

    // Category flash messages
    'categoryCreated'       => 'Kategorie erstellt.',
    'categoryUpdated'       => 'Kategorie aktualisiert.',
    'categoryDeleted'       => 'Kategorie gelöscht.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Tags',
    'tagPostCount'          => 'Beitragsanzahl',

    // Tag flash messages
    'tagDeleted'            => 'Tag gelöscht.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Kommentare',
    'commentAuthor'         => 'Autor',
    'commentContent'        => 'Kommentar',
    'commentPost'           => 'Beitrag',
    'commentDate'           => 'Datum',
    'commentStatusFilter'   => 'Nach Status filtern',

    // Comment flash messages
    'commentApproved'       => 'Kommentar genehmigt.',
    'commentSpam'           => 'Als Spam markiert.',
    'commentTrashed'        => 'Kommentar in Papierkorb verschoben.',
    'commentDeleted'        => 'Kommentar dauerhaft gelöscht.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Mediathek',
    'mediaTitle'            => 'Titel',
    'mediaAltText'          => 'Alt-Text',
    'mediaAltPlaceholder'   => 'Beschreiben Sie das Bild für die Barrierefreiheit',
    'mediaTitlePlaceholder' => 'Optionaler Bildtitel',
    'mediaImageDetails'     => 'Bilddetails',
    'mediaSaved'            => 'Gespeichert!',
    'mediaNoSelection'      => 'Kein Bild ausgewählt',
    'mediaBrowse'           => 'Medien durchsuchen',
    'mediaRemove'           => 'Entfernen',
    'mediaUseImage'         => 'Dieses Bild verwenden',
    'mediaDropzone'         => 'Bild hier hineinziehen oder klicken zum Durchsuchen',
    'mediaLoading'          => 'Medien werden geladen…',
    'mediaEmpty'            => 'Noch keine Medien hochgeladen.',
    'mediaUpload'           => 'Medien hochladen',
    'mediaDragDrop'         => 'Dateien hier hineinziehen oder',
    'mediaChooseFiles'      => 'Dateien auswählen',
    'mediaUploading'        => 'Wird hochgeladen…',
    'mediaFilename'         => 'Dateiname',
    'mediaSize'             => 'Größe',
    'mediaUploadFailed'     => 'Upload fehlgeschlagen: {0}',
    'mediaUploadError'      => 'Upload-Fehler: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Medien gelöscht.',
    'mediaNoValidFile'      => 'Keine gültige Datei hochgeladen.',
    'mediaUploadSuccess'    => 'Datei erfolgreich hochgeladen.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navigation',
    'navQuickAdd'           => 'Schnell hinzufügen',
    'navQuickAddPlaceholder' => 'Seiten, Kategorien, Plugins suchen...',
    'navItemLabel'          => 'Bezeichnung',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Ziel',
    'navItemOrder'          => 'Sortierreihenfolge',
    'navGroupPrimary'       => 'Primär',
    'navGroupFooter'        => 'Fußzeile',
    'navSelectGroup'        => 'Navigationsgruppe auswählen:',
    'navParent'             => 'Übergeordnet',
    'navTopLevel'           => '— Oberste Ebene —',
    'navSameWindow'         => 'Gleiches Fenster',
    'navNewWindow'          => 'Neues Fenster',
    'navMenuItems'          => 'Menüelemente',
    'navNoItems'            => 'Keine Einträge in diesem Menü.',
    'dragToReorder'         => 'Zum Umsortieren ziehen',

    // Navigation flash messages
    'navItemAdded'          => 'Navigationselement hinzugefügt.',
    'navItemRemoved'        => 'Navigationselement entfernt.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Themes',
    'themeOptions'          => 'Theme-Optionen',
    'themeActivate'         => 'Aktivieren',
    'themeOptionsBtn'       => 'Optionen',
    'themeActive'           => 'Aktiv',
    'themeBy'               => 'Von',
    'themeSupport'          => 'Support',
    'themeVersion'          => 'Version',
    'themeSaveOptions'      => 'Optionen speichern',
    'themeInvalidLicense'   => 'Theme kann nicht aktiviert werden – Lizenz ist ungültig. Neu installieren oder Support kontaktieren.',
    'themeValidationFailed' => 'Theme enthält PHP-Code und kann nicht aktiviert werden.',
    'noThemesInstalled'     => 'Keine Themes installiert. Besuchen Sie den Marktplatz, um Themes zu erhalten.',
    'themeUnapprovedTitle'  => 'Nicht genehmigtes Theme aktivieren?',
    'themeNotApproved'      => 'Dieses Theme wurde von Pubvana nicht genehmigt.',
    'themeUnapprovedRisk'   => 'Das Aktivieren nicht genehmigter Themes kann Sicherheitsrisiken oder Kompatibilitätsprobleme verursachen.',
    'themeActivateConfirm'  => 'Sind Sie sicher, dass Sie es trotzdem aktivieren möchten?',
    'themeActivateAnyway'   => 'Trotzdem aktivieren',
    'themeNoOptions'        => 'Dieses Theme hat keine konfigurierbaren Optionen.',
    'themeCustomize'        => 'Theme anpassen',

    // Theme flash messages
    'themeActivated'        => 'Theme aktiviert.',
    'themeOptionsSaved'     => 'Optionen gespeichert.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Lizenziert',
    'licenseCheckNow'        => 'Jetzt prüfen',
    'licenseExpired'         => 'Abgelaufen',
    'licenseEnterKey'        => 'Schlüssel eingeben',
    'licenseChangeKey'       => 'Ändern',
    'licenseRenew'           => 'Erneuern',
    'licenseThirdParty'      => 'Drittanbieter',
    'unchecked'              => 'Ungeprüft',
    'safetyLabel'            => 'Sicherheit:',
    'recheckBtn'             => 'Erneut prüfen',
    'recheckSuccess'         => 'Sicherheitsprüfung aktualisiert.',
    'recheckFailed'          => 'Der Überprüfungsserver konnte nicht erreicht werden. Bitte versuchen Sie es später erneut.',
    'recheckNotFound'        => 'Element nicht gefunden.',
    'widgetBlockedMalicious' => '{0} wurde als bösartig markiert und kann nicht hinzugefügt werden.',
    'licenseNoStoreProduct'  => 'Dieses Element ist nicht mit einem Store-Produkt verknüpft. Wenn Sie dieses Element gekauft haben, installieren Sie es bitte über den Marketplace neu, um die Lizenzierung zu aktivieren.',
    'securityWarning'        => 'Sicherheitswarnung:',
    'licenseModalTitle'      => 'Lizenzschlüssel eingeben',
    'licenseModalBody'       => 'Fügen Sie Ihren Lizenzschlüssel unten ein.',
    'licenseModalSave'       => 'Speichern',
    'licenseSaved'           => 'Lizenzschlüssel gespeichert und validiert.',
    'licenseInvalid'         => 'Lizenzschlüssel ist nicht gültig.',
    'licenseKeyRequired'     => 'Lizenzschlüssel und Produkt sind erforderlich.',
    'licenseCheckFailed'     => 'Der Lizenzserver konnte nicht erreicht werden. Bitte versuchen Sie es später erneut.',
    'licenseProductNotFound' => 'Dieses Element konnte im Store nicht gefunden werden.',
    'btnCancel'              => 'Abbrechen',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widgets',
    'widgetConfigureTitle'  => 'Widget konfigurieren',
    'widgetAreas'           => 'Widget-Bereiche',
    'widgetAvailable'       => 'Verfügbare Widgets',
    'widgetAddToArea'       => 'Zum Bereich hinzufügen',
    'widgetArea'            => 'Bereich',
    'widgetNoOptions'       => 'Keine Optionen.',
    'widgetSaveConfig'      => 'Konfiguration speichern',
    'widgetConfigure'       => 'Konfigurieren',
    'widgetNoAreas'         => 'Keine Widget-Bereiche gefunden. Aktivieren Sie ein Theme, um Widget-Bereiche zu aktivieren.',
    'widgetAreaEmpty'       => 'Keine Widgets in diesem Bereich. Fügen Sie eines aus der Liste hinzu →',

    // Widget flash messages
    'widgetAdded'           => 'Widget hinzugefügt.',
    'widgetRemoved'         => 'Widget entfernt.',
    'widgetConfigured'      => 'Widget konfiguriert.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Marktplatz',
    'marketplaceRefresh'    => 'Aktualisieren',
    'marketplaceVisitStore' => 'Shop besuchen',
    'marketplaceAll'        => 'Alle',
    'marketplaceThemes'     => 'Themes',
    'marketplaceWidgets'    => 'Widgets',
    'marketplacePlugins'    => 'Plugins',
    'marketplaceUpdatesAvailable' => '{0} Update(s) verfügbar.',
    'marketplaceBy'         => 'Von',
    'marketplaceFree'       => 'Kostenlos',
    'marketplaceInstalled'  => 'Installiert',
    'marketplaceInstall'    => 'Installieren',
    'marketplaceBuyNow'     => 'Jetzt kaufen',
    'marketplaceNoItems'    => 'Keine Elemente im Marktplatz gefunden.',
    'marketplaceInstalledVersion' => 'v{0} installiert',
    'marketplaceLoadError'  => 'Produkte konnten nicht aus dem Store geladen werden. Bitte versuchen Sie es später erneut.',
    'byAuthor'              => 'Von {0}',
    'unknown'               => 'Unbekannt',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} erfolgreich installiert.',
    'marketplaceInstallFail'    => 'Installation fehlgeschlagen. Protokolle prüfen.',
    'marketplaceUpdateSuccess'  => 'Erfolgreich aktualisiert.',
    'marketplaceUpdateFail'     => 'Aktualisierung fehlgeschlagen.',
    'marketplaceCacheRefreshed' => 'Marktplatz-Cache aktualisiert.',
    'marketplaceInvalidRequest' => 'Ungültige Installationsanfrage.',
    'marketplaceCannotUpdate'   => 'Dieses Element kann nicht aktualisiert werden.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Lizenzen',
    'licensesNone'                => 'Keine Lizenzen',
    'licensesProduct'             => 'Produkt',
    'licensesKey'                 => 'Lizenzschlüssel',
    'licensesStatus'              => 'Status',
    'licensesType'                => 'Typ',
    'licensesExpires'             => 'Läuft ab',
    'licensesDomain'              => 'Domain',
    'licensesInstalled'           => 'Installiert',
    'licensesLastChecked'         => 'Zuletzt geprüft',
    'licensesActions'             => 'Aktionen',
    'licensesStatusValid'         => 'Gültig',
    'licensesStatusInvalid'       => 'Ungültig',
    'licensesStatusExpired'       => 'Abgelaufen',
    'licensesStatusSubExpired'    => 'Abonnement abgelaufen',
    'licensesStatusUnchecked'     => 'Ungeprüft',
    'licensesSubscription'        => 'Abonnement',
    'licensesOneTime'             => 'Einmalig',
    'licensesPerpetual'           => 'Dauerhaft',
    'licensesNotInstalled'        => 'Nicht installiert',
    'licensesNever'               => 'Nie',
    'licensesRevalidate'          => 'Neu validieren',
    'licenseKeyPlaceholder'       => 'Lizenzschlüssel eingeben...',
    'marketplaceLicensesEmpty'    => 'Lizenzierte Produkte erscheinen hier nach der Installation.',
    'typeTheme'                   => 'Theme',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Plugin',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Lizenz erfolgreich validiert.',
    'licenseRevalidateInvalid'     => 'Lizenz ist ungültig oder abgelaufen.',
    'licenseRevalidateUnreachable' => 'Der Lizenzserver konnte nicht erreicht werden. Bitte versuchen Sie es später erneut.',
    'licenseRevalidateSkipped'     => 'Lizenzprüfung übersprungen (Entwicklungsmodus).',
    'licenseRevalidateNotFound'    => 'Lizenz nicht gefunden.',

    // License warning banners
    'licenseWarningTitle'   => 'Lizenzprobleme',
    'licenseWarningInvalid' => 'Lizenz ist ungültig oder abgelaufen',
    'licenseWarningManage'  => 'Lizenzen verwalten',

    // Plugin license
    'pluginInvalidLicense' => 'Dieses Plugin hat eine ungültige oder abgelaufene Lizenz und kann nicht aktiviert werden.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Lizenzschlüssel',
    'storeBrowseFull'       => 'Gesamten Shop durchsuchen',
    'storeBackToMarketplace'=> 'Zurück zum Marktplatz',
    'storeNoProducts'       => 'Keine Produkte verfügbar.',
    'storeViewInStore'      => 'Im Shop ansehen',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Benutzer',
    'editUserTitle'         => 'Benutzer bearbeiten',
    'createUserTitle'       => 'Benutzer erstellen',
    'authorProfileTitle'    => 'Autorenprofil',
    'userRoleLabel'         => 'Rolle',
    'userActiveLabel'       => 'Aktiv',
    'userPasswordLabel'     => 'Passwort',
    'userPasswordOptional'  => 'Leer lassen, um das aktuelle Passwort beizubehalten',
    'userDisplayName'       => 'Anzeigename',
    'userBio'               => 'Biografie',
    'userWebsite'           => 'Website',
    'userTwitter'           => 'Twitter / X-Handle',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Profil speichern',
    'userSaveChanges'       => 'Änderungen speichern',
    'userCannotDeleteSelf'  => 'Sie können sich nicht selbst löschen.',
    'userCannotDeleteOwner' => 'Das Konto des Website-Inhabers kann nicht gelöscht werden.',
    'userOwnerCannotModify' => 'Das Konto des Website-Inhabers kann nicht geändert werden.',

    // User flash messages
    'userCreated'           => 'Benutzer erstellt.',
    'userUpdated'           => 'Benutzer aktualisiert.',
    'userDeleted'           => 'Benutzer gelöscht.',
    'userBanned'            => 'Benutzer wurde gesperrt.',
    'userUnbanned'          => 'Benutzer wurde entsperrt.',
    'userCannotBanSelf'     => 'Sie können sich selbst oder den Website-Inhaber nicht sperren.',
    'banStatus'             => 'Sperrstatus',
    'banned'                => 'Gesperrt',
    'ban'                   => 'Benutzer sperren',
    'unban'                 => 'Entsperren',
    'banReasonRequired'     => 'Ein Grund für die Sperre ist erforderlich.',
    'banReasonPlaceholder'  => 'Grund für die Sperre...',
    'confirmBanUser'        => 'Sind Sie sicher, dass Sie diesen Benutzer sperren möchten?',
    'userProfileSaved'      => 'Profil gespeichert.',
    'userAvatarUploadFail'  => 'Avatar-Upload fehlgeschlagen: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => '2FA-Einrichtung',
    'tfaSetupHeading'       => 'Zwei-Faktor-Authentifizierung einrichten',
    'tfaScanQr'             => 'Scannen Sie den QR-Code unten mit Ihrer Authentifizierungs-App (z. B. Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Oder geben Sie den geheimen Schlüssel manuell ein:',
    'tfaEnterCode'          => 'Geben Sie den 6-stelligen Code aus Ihrer App zur Bestätigung ein:',
    'tfaCodeLabel'          => 'Authentifizierungscode',
    'tfaConfirmBtn'         => 'Bestätigen & 2FA aktivieren',
    'tfaDisableBtn'         => '2FA deaktivieren',
    'tfaDisableConfirm'     => 'Geben Sie Ihren aktuellen 2FA-Code zum Deaktivieren ein:',
    'tfaEnabled'            => 'Zwei-Faktor-Authentifizierung aktiviert.',
    'tfaDisabled'           => 'Zwei-Faktor-Authentifizierung deaktiviert.',
    'tfaInvalidCode'        => 'Ungültiger Code – bitte scannen Sie den QR-Code erneut und versuchen Sie es nochmal.',
    'tfaInvalidDisable'     => 'Ungültiger Code – 2FA wurde nicht deaktiviert.',
    'tfaSessionExpired'     => 'Einrichtungssitzung abgelaufen – bitte beginnen Sie erneut.',
    'tfaNotEnabled'         => '2FA ist derzeit nicht aktiviert.',
    'tfaCantScan'           => 'Können Sie nicht scannen? Geben Sie diesen Code manuell ein:',
    'tfaWarning'            => 'Bewahren Sie diesen geheimen Schlüssel an einem sicheren Ort auf. Sie benötigen ihn, um den Zugang wiederherzustellen, falls Sie Ihr Authentifizierungsgerät verlieren.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Social-Links',
    'socialPlatform'           => 'Plattform',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Symbol',
    'socialSortOrder'          => 'Sortierreihenfolge',
    'socialIconPackInfo'       => 'Das aktuelle Theme <strong>{0}</strong> verwendet <strong>{1}</strong> (v{2}) für Symbole. Unten können Sie die verfügbaren Symbole auswählen, die für die Social-Links-Funktion dieser Website angezeigt werden.',
    'socialSearchPlaceholder'  => 'Plattformen suchen...',
    'socialIconDisclaimer'     => "Diese Symbole sind nur eine Darstellung des verwendeten Symbols. Das tatsächliche Symbol kann je nach aktivem Theme-Symbolpaket variieren.",

    // Social flash messages
    'socialLinkAdded'       => 'Social-Link hinzugefügt.',
    'socialLinkUpdated'     => 'Link aktualisiert.',
    'socialLinkDeleted'     => 'Link gelöscht.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Weiterleitungen',
    'redirectFrom'          => 'Von URL',
    'redirectTo'            => 'Zu URL',
    'redirectType'          => 'Typ',
    'redirectAdd'           => 'Weiterleitung hinzufügen',
    'redirectFromHint'      => '(relativ, z. B. /alte-seite)',
    'redirect301'           => '301 Permanent',
    'redirect302'           => '302 Temporär',
    'redirectInvalidDest'   => 'Ungültige Weiterleitungs-Ziel-URL.',

    // Redirect flash messages
    'redirectAdded'         => 'Weiterleitung hinzugefügt.',
    'redirectDeleted'       => 'Weiterleitung gelöscht.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Einstellungen',
    'settingsGeneral'       => 'Allgemein',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'E-Mail',
    'settingsSocialLogin'   => 'Social-Login',
    'settingsSocialSharing' => 'Social-Sharing',
    'settingsSpam'          => 'Spam-Schutz',

    'generalSettingsHeading'    => 'Allgemeine Einstellungen',
    'generalSiteName'           => 'Website-Name',
    'generalTagline'            => 'Slogan',
    'generalAdminEmail'         => 'Admin-E-Mail',
    'generalPostsPerPage'       => 'Beiträge pro Seite',
    'generalComments'           => 'Kommentare',
    'generalCommentsEnable'     => 'Kommentare aktivieren',
    'generalCommentModeration'  => 'Moderation vor Veröffentlichung verlangen',
    'generalMaintenanceMode'    => 'Wartungsmodus',
    'generalMaintenanceEnable'  => 'Wartungsmodus aktivieren',
    'generalMaintenanceHelp'    => 'Besucher sehen eine „Wir sind bald zurück"-Seite. Administratoren können die Website weiterhin aufrufen.',
    'generalFrontPage'          => 'Startseite',
    'generalFrontPageBlog'      => 'Blog-Index (neueste Beiträge)',
    'generalFrontPageStatic'    => 'Statische Seite:',
    'generalFrontPagePlugin'    => 'Plugin-Seite:',
    'generalSelectPage'         => '- Seite auswählen -',
    'generalSelectRoute'        => '- Route auswählen -',
    'generalFrontPageNoPlugins' => 'Keine Plugin-Routen verfügbar',
    'generalPageCacheTtl'       => 'Seiten-Cache-TTL',
    'settingsCacheTtlHint'      => 'Sekunden. 0 = deaktiviert.',
    'generalSaveBtn'            => 'Allgemeine Einstellungen speichern',

    // General flash messages
    'generalSettingsSaved'      => 'Allgemeine Einstellungen gespeichert.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'SEO-Einstellungen',
    'seoMetaDescription'        => 'Meta-Beschreibung',
    'seoGoogleAnalytics'        => 'Google Analytics-ID',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Sitemap',
    'seoSitemapEnable'          => 'sitemap.xml aktivieren',
    'seoSitemapHelp'            => 'Standard-Sitemap für alle veröffentlichten Beiträge und Seiten.',
    'seoNewsSitemap'            => 'news-sitemap.xml aktivieren',
    'seoNewsSitemapHelp'        => 'Google News-Sitemap – listet in den letzten 48 Stunden veröffentlichte Beiträge auf.',
    'seoSaveBtn'                => 'SEO-Einstellungen speichern',
    'seoSettingsSaved'          => 'SEO-Einstellungen gespeichert.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'E-Mail-Einstellungen',
    'emailFromName'             => 'Absendername',
    'emailFromAddress'          => 'Absenderadresse',
    'emailProtocol'             => 'Protokoll',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'SMTP-Host',
    'emailSmtpPort'             => 'SMTP-Port',
    'emailSmtpEncryption'       => 'Verschlüsselung',
    'emailSmtpEncryptionNone'   => 'Keine',
    'emailSmtpUsername'         => 'SMTP-Benutzername',
    'emailSmtpPassword'         => 'SMTP-Passwort',
    'emailSaveBtn'              => 'E-Mail-Einstellungen speichern',
    'emailSettingsSaved'        => 'E-Mail-Einstellungen gespeichert.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Social-Login (OAuth)',
    'socialLoginHelp'           => 'Anmeldedaten werden in Ihrer .env-Datei gespeichert. Registrieren Sie Ihre App bei Google und Facebook, um Client-IDs und Geheimnisse zu erhalten.',
    'socialLoginGoogleId'       => 'Client-ID',
    'socialLoginGoogleSecret'   => 'Client-Geheimnis',
    'socialLoginFbAppId'        => 'App-ID',
    'socialLoginFbAppSecret'    => 'App-Geheimnis',
    'socialLoginPlaceholderSecret' => '(leer lassen, um bestehende beizubehalten)',
    'socialLoginSaveBtn'        => 'Social-Login-Einstellungen speichern',
    'socialLoginSettingsSaved'  => 'Social-Login-Einstellungen gespeichert.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Automatisches Social-Sharing bei Veröffentlichung',
    'socialSharingHelp'         => 'Wenn ein Beitrag mit aktiviertem „Bei Veröffentlichung teilen" veröffentlicht wird, postet Pubvana automatisch auf konfigurierten Social-Media-Konten.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Schlüssel erhalten unter developer.twitter.com → Ihre App → Schlüssel und Tokens.',
    'socialSharingApiKey'       => 'API-Schlüssel',
    'socialSharingApiSecret'    => 'API-Geheimnis',
    'socialSharingAccessToken'  => 'Zugriffstoken',
    'socialSharingAccessSecret' => 'Zugriffs-Geheimnis',
    'socialSharingFbPage'       => 'Facebook-Seite',
    'socialSharingFbPageHelp'   => 'Erfordert ein Seiten-Zugriffstoken mit der Berechtigung pages_manage_posts.',
    'socialSharingFbPageId'     => 'Seiten-ID',
    'socialSharingFbPageToken'  => 'Seiten-Zugriffstoken',
    'socialSharingSaveBtn'      => 'Sharing-Einstellungen speichern',
    'socialSharingSettingsSaved'=> 'Social-Sharing-Einstellungen gespeichert.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Spam-Schutz (hCaptcha)',
    'spamHcaptchaIntro'         => 'Pubvana verwendet hCaptcha (datenschutzfreundlich, kein Google), um Kommentarformulare und das Kontaktformular vor Spam-Bots zu schützen.',
    'spamHcaptchaFree'          => 'hCaptcha ist für die meisten Websites kostenlos. Registrieren Sie sich unter hcaptcha.com, erstellen Sie eine Website und geben Sie Ihre Schlüssel unten ein.',
    'spamHcaptchaSiteKey'       => 'Website-Schlüssel',
    'spamHcaptchaSecretKey'     => 'Geheimer Schlüssel',
    'spamHcaptchaNote'          => 'Wenn diese Schlüssel nicht gesetzt sind, wird hCaptcha stillschweigend übersprungen – sicher für lokale Entwicklung. Nach dem Speichern erscheint das Widget automatisch im Kommentarformular und auf der Kontaktseite.',
    'spamSettingsSaved'         => 'Spam-Schutz-Einstellungen gespeichert.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Sprachen',
    'languageCode'              => 'Code',
    'languageName'              => 'Name',
    'languageDefault'           => 'Standard',
    'languageEnabled'           => 'Aktiviert',
    'languageMakeDefault'       => 'Als Standard setzen',
    'languageSetAsDefault'      => '{0} als Standardsprache festgelegt.',
    'languageEnabled_msg'       => '{0} aktiviert.',
    'languageDisabled_msg'      => '{0} deaktiviert.',
    'languageNotFound'          => 'Sprache nicht gefunden.',
    'languageCannotDisable'     => 'Die Standardsprache kann nicht deaktiviert werden.',
    'languageDirection'         => 'Richtung',
    'languageNativeName'        => 'Nativer Name',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Analytik',
    'analyticsTotalViews'       => 'Gesamtaufrufe',
    'analyticsTopPosts'         => 'Top-Beiträge',
    'analyticsReferrers'        => 'Top-Verweise',
    'analyticsLast7'            => 'Letzte 7 Tage',
    'analyticsLast30'           => 'Letzte 30 Tage',
    'analyticsLast90'           => 'Letzte 90 Tage',
    'analyticsChartTitle'       => 'Seitenaufrufe',
    'analyticsNoData'           => 'Keine Analysedaten für diesen Zeitraum.',
    'analyticsDomain'           => 'Domain',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Affiliate-Links',
    'newAffiliateLinkTitle'     => 'Neuer Affiliate-Link',
    'editAffiliateLinkTitle'    => 'Affiliate-Link bearbeiten',
    'affiliateName'             => 'Name',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'Ziel-URL',
    'affiliateActive'           => 'Aktiv',
    'affiliateClicks'           => 'Klicks',
    'affiliateClicksTitle'      => 'Klicks - {0}',
    'affiliateTotal'            => 'Gesamt',
    'affiliateViewClicks'       => 'Klicks anzeigen',

    // Affiliate flash messages
    'affiliateCreated'          => 'Affiliate-Link erstellt.',
    'affiliateUpdated'          => 'Affiliate-Link aktualisiert.',
    'affiliateDeleted'          => 'Affiliate-Link gelöscht.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Defekte Links',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'HTTP-Status',
    'brokenLinkError'           => 'Fehler',
    'brokenLinkSource'          => 'Quelle',
    'brokenLinkShowDismissed'   => 'Ausgeblendete anzeigen',
    'brokenLinkHideDismissed'   => 'Ausgeblendete verbergen',
    'brokenLinkTimeout'         => 'Zeitüberschreitung',
    'brokenLinkBroken'          => 'defekt',
    'brokenLinkNone'            => 'Keine defekten Links gefunden.',
    'brokenLinkNowReachable'    => 'Link ist jetzt erreichbar – aus den Ergebnissen entfernt.',
    'brokenLinkStillBroken'     => 'Link ist noch defekt ({0}).',
    'brokenLinkDismissed'       => 'Link ausgeblendet.',
    'brokenLinksCliHint'        => 'Führen Sie einen vollständigen Scan über die Befehlszeile durch, um diesen Bericht zu befüllen: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} Problem(e) gefunden',
    'brokenLinksCount'          => '{0} defekt',
    'brokenLinksRecheck'        => 'Diese URL erneut prüfen',
    'brokenLinksDismiss'        => 'Ausblenden (aus Ergebnissen verbergen)',
    'brokenLinksRunScan'        => 'Scan starten',
    'brokenLinksScanComplete'   => 'Scan abgeschlossen: {0} Links geprüft, {1} defekt.',
    'timeout'                   => 'Zeitüberschreitung',
    'typePost'                  => 'Beitrag',
    'typePage'                  => 'Seite',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Aktivitätsprotokoll',
    'activityLogType'           => 'Typ',
    'activityLogAction'         => 'Aktion',
    'activityLogUser'           => 'Benutzer',
    'activityLogDate'           => 'Datum',
    'activityLogNote'           => 'Anmerkung',
    'activityLogFilterAll'      => 'Alle Typen',
    'activityLogEmpty'          => 'Noch keine Aktivitäten aufgezeichnet.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Sicherung & Export',
    'backupDownload'            => 'Sicherung erstellen & herunterladen',
    'backupFiles'               => 'Verfügbare Sicherungen',
    'backupFilename'            => 'Dateiname',
    'backupSize'                => 'Größe',
    'backupDate'                => 'Erstellt',
    'backupGenerating'          => 'Sicherung wird erstellt…',
    'backupNoFiles'             => 'Keine gespeicherten Sicherungen.',
    'backupFailed'              => 'Sicherung fehlgeschlagen: {0}',
    'backupDeleted'             => 'Sicherung gelöscht.',
    'backupCannotDelete'        => 'Sicherung konnte nicht gelöscht werden.',
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'IPs werden als SHA-256-Hashes gespeichert – keine Rohdaten werden aufgezeichnet.',
    'colTime'                   => 'Uhrzeit',
    'colIpHash'                 => 'IP-Hash',
    'colReferrer'               => 'Verweise',
    'affiliateDirectReferrer'   => 'Direkt',
    'affiliateNameHint'         => 'Interne Bezeichnung – nicht für Besucher sichtbar.',
    'affiliateSlugHint'         => 'Nur Buchstaben, Zahlen, Bindestriche und Unterstriche. Kann nach dem Teilen von Links nicht mehr geändert werden.',
    'affiliateDestHint'         => 'Muss https:// enthalten. Besucher werden per 301 hierhin weitergeleitet.',
    'affiliateInactiveHint'     => 'Inaktive Links geben einen 404 zurück.',
    'affiliateLinkCount'        => '{0} Links',
    'colDomain'                 => 'Domain',
    'commentAll'                => 'Alle',
    'commentPending'            => 'Ausstehend',
    'commentTrash'              => 'Papierkorb',
    'commentsNone'              => 'Keine {0}-Kommentare.',

    'backupCreate'              => 'Sicherung erstellen',
    'backupStarting'            => 'Sicherung wird gestartet...',
    'backupNoneYet'             => 'Noch keine Sicherungen. Klicken Sie auf „Sicherung erstellen", um Ihre erste zu erstellen.',
    'backupsTitle'              => 'Sicherungen',
    'backupRetentionNote'       => 'Maximal 15 Sicherungen werden aufbewahrt – älteste werden automatisch gelöscht.',
    'backupRestoreConfirm'      => 'Diese Sicherung wiederherstellen? Zuvor wird eine Sicherung des aktuellen Zustands erstellt.',
    'backupDeleteConfirm'       => 'Diese Sicherung löschen?',
    'colFilename'               => 'Dateiname',
    'colVersion'                => 'Version',
    'colTrigger'                => 'Auslöser',
    'colSize'                   => 'Größe',
    'colDate'                   => 'Datum',
    'colActions'                => 'Aktionen',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Importieren',
    'importWpHeading'           => 'Von WordPress importieren',
    'importWpHelp'              => 'Exportieren Sie Ihre WordPress-Website über Werkzeuge → Export und laden Sie die .xml-Datei unten hoch.',
    'importChooseFile'          => 'WXR-Datei auswählen (.xml)',
    'importDryRun'              => 'Testlauf (nur Vorschau – es wird nichts gespeichert)',
    'importRunBtn'              => 'Import starten',
    'importNoValidFile'         => 'Bitte laden Sie eine gültige WordPress-WXR-Exportdatei hoch.',
    'importOnlyXml'             => 'Nur .xml-Dateien werden akzeptiert.',
    'importFileTooLarge'        => 'Importdatei zu groß. Maximale Größe ist 50 MB.',
    'importResultsHeading'      => 'Importergebnisse',
    'importDryRunNote'          => 'Testlauf – keine Daten wurden gespeichert.',
    'importDryRunLabel'         => '(Testlauf – keine Daten geschrieben)',
    'importComplete'            => 'Import abgeschlossen',
    'importCreated'             => 'erstellt',
    'importSkipped'             => 'übersprungen',
    'importErrors'              => 'Fehler:',
    'importInstructions'        => 'Exportieren Sie Ihren WordPress-Inhalt unter <strong>Werkzeuge → Export → Alle Inhalte</strong> und laden Sie die <code>.xml</code>-Datei hier hoch. Pubvana importiert Beiträge, Seiten, Kategorien, Tags, Autoren und Kommentare.',
    'importCliTitle'            => 'CLI-Import',
    'importCliHint'             => 'Sie können den Importer auch über die Befehlszeile ausführen:',
    'importCliDryRunHint'       => 'Das Flag <code>--dry-run</code> zeigt an, was importiert werden würde, ohne in die Datenbank zu schreiben.',
    'importWhatTitle'           => 'Was wird importiert',
    'importItemPosts'           => 'Beiträge (Titel, Inhalt, Auszug, Slug, Status)',
    'importItemPages'           => 'Seiten',
    'importItemCategories'      => 'Kategorien (mit Hierarchie)',
    'importItemTags'            => 'Tags',
    'importItemAuthors'         => 'Autoren (werden als Abonnentenkonten erstellt)',
    'importItemComments'        => 'Kommentare',
    'importItemMedia'           => 'Mediendateien (URLs im Inhalt erhalten)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Updates',
    'updatesCurrentVersion'     => 'Aktuelle Version',
    'updatesLatestVersion'      => 'Neueste Version',
    'updatesUpToDate'           => 'Pubvana ist aktuell.',
    'updatesAvailable'          => 'Update verfügbar: {0}',
    'updatesCheckBtn'           => 'Auf Updates prüfen',
    'updatesReleaseNotes'       => 'Versionshinweise',
    'updatesHowToApply'         => 'So wenden Sie ein Update an',
    'updatesCacheCleared'       => 'Update-Cache geleert – wird jetzt neu geprüft.',
    'updatesExtCapped'          => 'Update verfügbar: {0} (addon-sicher)',
    'updatesNewerAvailable'     => 'Pubvana {0} ist ebenfalls verfügbar – aktualisieren Sie die unten aufgeführten Addons, um es freizuschalten.',

    // Addon Updates
    'updatesExtTitle'               => 'Addons',
    'updatesExtCheckAll'            => 'Alle prüfen',
    'updatesExtUpdateAll'           => 'Alle aktualisieren',
    'updatesExtCheckAllType'        => 'Alle {0} prüfen',
    'updatesExtUpdateAllType'       => 'Alle {0} aktualisieren',
    'updatesExtNoInstalled'         => 'Keine {0} installiert.',
    'updatesExtColName'             => 'Name',
    'updatesExtColVersion'          => 'Version',
    'updatesExtColLatest'           => 'Neueste',
    'updatesExtColAutoUpdate'       => 'Auto-Update',
    'updatesExtColStatus'           => 'Status',
    'updatesExtColActions'          => 'Aktionen',
    'updatesExtBundled'             => 'Im Kern enthalten',
    'updatesExtNoSource'            => 'Keine Update-Quelle',
    'updatesExtFailed'              => 'Fehlgeschlagen',
    'updatesExtUpdatedAt'           => 'Aktualisiert {0}',
    'updatesExtAvailable'           => 'Update verfügbar',
    'updatesExtUpToDate'            => 'Aktuell',
    'updatesExtUpdate'              => 'Aktualisieren',
    'updatesExtChecking'            => 'Wird geprüft...',
    'updatesExtUpdating'            => 'Wird aktualisiert...',
    'updatesExtUpdated'             => 'Aktualisiert',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Update bestätigen',
    'updatesConfirmBody'            => 'Dies erstellt eine Sicherung Ihrer Website, lädt das Update herunter und wendet es an.',
    'updatesConfirmSafe'            => 'Ihre <code>.env</code>, <code>App.php</code> und <code>Database.php</code> werden niemals überschrieben.',
    'updatesConfirmBtn'             => 'Jetzt aktualisieren',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Alle Addons aktualisieren',
    'updatesExtAllBody'             => 'Dies aktualisiert alle Addons mit ausstehenden Updates.',
    'updatesExtAllNote'             => 'Addons mit deaktiviertem Auto-Update werden ebenfalls aktualisiert.',
    'updatesExtAllBtn'              => 'Alle aktualisieren',

    'updatesExtBadge'               => 'Update: v{0}',
    'updatesExtGoToUpdates'         => 'Updates',

    // Update Settings
    'updatesSettingsTitle'          => 'Update-Einstellungen',
    'updatesAutoUpdateLabel'        => 'Pubvana Auto-Update',
    'updatesAutoUpdateManual'       => 'Manuell',
    'updatesAutoUpdateAuto'         => 'Automatisch',
    'updatesAutoUpdateHelp'         => 'Wenn aktiviert, werden Pubvana-Updates ohne bahnbrechende Änderungen automatisch angewendet.',
    'updatesCheckMethodLabel'       => 'Update-Prüfmethode',
    'updatesCheckMethodPageload'    => 'Seitenaufruf',
    'updatesCheckMethodCron'        => 'Cron-Job',
    'updatesCheckMethodHelp'        => 'Seitenaufruf prüft bei jeder Anfrage (24h zwischengespeichert). Cron erfordert einen Server-Cron-Job.',
    'updatesCronCommand'            => 'Cron-Befehl',
    'updatesCronHelp'               => 'Fügen Sie dies zur crontab Ihres Servers hinzu, um die Update-Prüfung täglich auszuführen:',
    'updatesSettingsSaved'          => 'Update-Einstellungen gespeichert.',

    // Compatibility
    'compatWarningTitle'            => 'Kompatibilitätswarnung',
    'compatNotCompatible'           => 'Einige installierte Addons sind mit dieser Version nicht kompatibel.',
    'compatRequiresUpdate'          => 'erfordert jedoch, dass die folgenden Addons zuerst aktualisiert werden:',
    'compatSupportsUpTo'            => 'unterstützt bis zu {0}',
    'compatRequiresMin'             => 'erfordert Pubvana {0}+',
    'compatNotDeclared'             => 'Die folgenden Addons haben keine Kompatibilität mit Pubvana {0} deklariert. Sie könnten nach dem Update aufhören zu funktionieren:',
    'compatColType'                 => 'Typ',
    'compatColName'                 => 'Name',
    'compatColVersion'              => 'Kompatibilität',
    'compatRemoveHint'              => 'Sie können inkompatible Addons entfernen oder zum Standard-Theme wechseln, falls Probleme auftreten. Vor jedem Update wird eine Sicherung erstellt.',
    'compatMaxVersion'              => 'Maximale kompatible Version: {0}',
    'compatMinVersion'              => 'Erfordert Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Beitragsplan',
    'scheduleNoScheduled'       => 'Keine geplanten Beiträge.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revisionen - {0}',
    'revisionPageTitle'         => 'Revision - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Sie müssen angemeldet sein, um auf das Admin-Panel zuzugreifen.',
    'dirNotWritable'            => 'Verzeichnis ist nicht beschreibbar: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    'addonMisconfigured'        => '{0} ist nicht ordnungsgemäß konfiguriert. Wenn Sie der Endbenutzer sind, kontaktieren Sie den Entwickler. Wenn Sie der Entwickler sind, lesen Sie die Dokumentation.',
    'addonMisconfiguredLink'    => '{0} ist nicht ordnungsgemäß konfiguriert. Wenn Sie der Endbenutzer sind, <a href="{1}">kontaktieren Sie den Entwickler</a>. Wenn Sie der Entwickler sind, <a href="https://github.com/Pubvana-CMS/pubvana">lesen Sie die Dokumentation</a>.',
    'licenseExpiringSoon'       => 'Lizenz für {0} läuft am {1} ab. {0} wird deaktiviert, wenn die Lizenz abläuft.',
    'licenseExpiredDeactivated' => '{0} wurde deaktiviert, weil die Lizenz abgelaufen ist.',
    'addonDeactivated'          => '{0} wurde deaktiviert. Grund: {1}.',
    'widgetValidationFailed'    => "Widget ''{0}'' konnte nicht validiert werden. Kontaktieren Sie den Entwickler oder entfernen Sie das Addon.",
    'widgetValidationFailedLink' => "Widget ''{0}'' konnte nicht validiert werden. <a href=\"{1}\">Kontaktieren Sie den Entwickler</a> oder entfernen Sie das Addon.",

    'addonDeactivatedExpired'   => 'Deaktiviert: Lizenz abgelaufen',
    'addonDeactivatedTampered'  => 'Deaktiviert: nicht ordnungsgemäß konfiguriert',
    'addonDeactivatedNoLicense' => 'Deaktiviert: keine gültige Lizenz',

    'addonDisabled'             => 'Deaktiviert',
    'addonDisabledInvalidJson'  => 'System: {0} hat eine ungültige oder unleserliche {1}.',
    'addonDisabledMissingFields' => 'System: {0} fehlen erforderliche Felder: {1}.',
    'addonDisabledPhpFiles'     => 'System: {0} enthält PHP-Dateien. Widgets dürfen nur JSON + Templates enthalten.',

    'licenseRequired'           => 'Zur Aktivierung von {0} ist eine gültige Lizenz erforderlich.',
    'licenseInvalidActivation'  => 'Lizenzvalidierung für {0} fehlgeschlagen. Bitte prüfen Sie Ihren Lizenzschlüssel.',
    'licenseExpiredActivation'  => 'Die Lizenz für {0} ist abgelaufen. Bitte erneuern Sie sie, um zu aktivieren.',
    'licenseCheckUnreachable'   => 'Die Lizenz für {0} konnte nicht verifiziert werden. Der Lizenzserver ist nicht erreichbar. Bitte versuchen Sie es später erneut.',
    'activationBlockedTampered' => '{0} kann nicht aktiviert werden, da es nicht ordnungsgemäß konfiguriert ist.',
    'activationBlockedBundled'  => '{0} kann nicht aktiviert werden: Nur Pubvana-Addons können als im Kern enthalten markiert werden.',
    'activationBlockedNoUrls'   => '{0} kann nicht aktiviert werden: Kostenpflichtige Addons müssen Lizenzverifizierungs-URLs enthalten.',
    'activationBlockedFreeFlag' => '{0} kann nicht aktiviert werden: Pubvana-Addons können nicht als kostenlos markiert werden.',
    'activationBlockedDisabled' => '{0} kann nicht aktiviert werden, da es Konfigurationsfehler hat. Prüfen Sie die Info-Datei.',

    'licenseThirdPartyLabel'    => 'Drittanbieter',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Update wird gestartet...',
    'updateCheckLabel'           => 'Update-Prüfung:',
    'updateAvailable'            => 'Pubvana {0} ist verfügbar!',
    'updateRunning'              => 'Sie verwenden {0}.',
    'updateBreakingChanges'      => 'Bahnbrechende Änderungen',
    'updateMigrationNotes'       => 'Migrationshinweise',
    'updateNotices'              => 'Hinweise',
    'updatePreflightTitle'       => 'Vorflugprüfungen',
    'updateToVersion'            => 'Auf Pubvana {0} aktualisieren',
    'updatePreflightFailed'      => 'Eine oder mehrere erforderliche Vorflugprüfungen sind fehlgeschlagen. Bitte beheben Sie diese, bevor Sie aktualisieren.',
    'updateUpToDate'             => 'Pubvana ist aktuell. Sie verwenden Version {0}.',
    'updateAnyway'               => 'Trotzdem aktualisieren',
    'updateAvailableTooltip'     => 'Pubvana {0} verfügbar',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(Sie)',
    'usersNone'                  => 'Keine Benutzer gefunden.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Konto aktiv',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Profildetails',
    'profileDisplayNameHint'     => 'Wird auf veröffentlichten Beiträgen anstelle des Benutzernamens angezeigt.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP oder GIF. Max 10 MB.',
    'profileSocialHandles'       => 'Social-Handles',
    'preview'                    => 'Vorschau',
    'website'                    => 'Website',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Zwei-Faktor-Authentifizierung',
    'totpActiveDesc'             => 'TOTP-Zwei-Faktor-Authentifizierung ist auf Ihrem Konto aktiv. Bei jeder Anmeldung werden Sie nach einem 6-stelligen Code aus Ihrer Authentifizierungs-App gefragt.',
    'totpCurrentCode'            => 'Aktueller Code',
    'totpInactiveDesc'           => 'Fügen Sie Ihrem Konto eine zusätzliche Sicherheitsebene hinzu. Nach der Aktivierung müssen Sie bei jeder Anmeldung einen Code aus Ihrer Authentifizierungs-App eingeben.',
    'totpEnable'                 => 'Zwei-Faktor-Authentifizierung aktivieren',
    'totpScanInstructions'       => 'Öffnen Sie Ihre Authentifizierungs-App (Google Authenticator, Authy, 1Password usw.) und scannen Sie diesen QR-Code.',
    'totpManualEntry'            => 'Können Sie nicht scannen? Geben Sie diesen Code manuell ein:',
    'totpConfirmInstructions'    => 'Geben Sie nach dem Scannen den 6-stelligen Code in Ihrer App ein, um die Einrichtung zu bestätigen.',
    'totpRecoveryWarning'        => 'Bewahren Sie Ihre Wiederherstellungscodes auf. Wenn Sie den Zugang zu Ihrer Authentifizierungs-App verlieren, können Sie sich nicht anmelden. Kontaktieren Sie Ihren Website-Administrator, um 2FA zurückzusetzen.',

];
