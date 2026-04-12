<?php

/**
 * Pubvana CMS - Plugins language strings (German)
 *
 * Usage: lang('Plugins.keyName')
 */

return [
    'title'              => 'Plugins',
    'scanBtn'            => 'Nach Plugins suchen',
    'noPlugins'          => 'Keine Plugins installiert. Besuchen Sie den <a href="/admin/marketplace">Marktplatz</a>, um verfügbare Plugins zu durchsuchen.',
    'colPlugin'          => 'Plugin',
    'colVersion'         => 'Version',
    'colStatus'          => 'Status',
    'colSafe'            => 'Sicher',
    'colActions'         => 'Aktionen',
    'statusActive'       => 'Aktiv',
    'statusInactive'     => 'Inaktiv',
    'safeYes'            => 'Sicher',
    'safeNo'             => 'Nicht sicher',
    'safeMalicious'      => 'Bösartig',
    'safeUnchecked'      => 'Ungeprüft',
    'safeUnknown'        => 'Unbekannt',
    'btnActivate'        => 'Aktivieren',
    'btnDeactivate'      => 'Deaktivieren',
    'btnActivateAnyway'  => 'Trotzdem aktivieren',
    'btnCancel'          => 'Abbrechen',
    'modalTitle'         => 'Ungeprüftes Plugin aktivieren',
    'modalSecurityWarn'  => 'Sicherheitswarnung:',
    'modalNotSafe'       => 'Dieses Plugin wurde von Pubvana nicht als sicher verifiziert.',
    'modalRiskWarning'   => 'Das Aktivieren ungeprüfter Plugins kann Sicherheitsrisiken oder Instabilität verursachen.',
    'modalConfirm'       => 'Sind Sie sicher, dass Sie es aktivieren möchten?',
    'discovered'         => 'Neue Plugin(s) entdeckt.',
    'noneFound'          => 'Keine neuen Plugins gefunden.',
    'activated'          => 'Plugin aktiviert.',
    'deactivated'        => 'Plugin deaktiviert.',
    'notFound'           => 'Plugin nicht gefunden.',
    'alreadyActive'      => 'Plugin ist bereits aktiv.',
    'migrationFailed'    => 'Plugin konnte Datenbankmigrationen nicht ausführen. Prüfen Sie die Protokolle für Details.',
    'installFailed'      => 'Plugin-Installation fehlgeschlagen. Ein teilweises Setup wurde zurückgerollt. Prüfen Sie die Protokolle für Details.',

    // License column
    'support'            => 'Support',
    'colLicense'         => 'Lizenz',
    'licenseLicensed'    => 'Lizenziert',
    'licenseCheckNow'    => 'Jetzt prüfen',
    'licenseExpired'     => 'Abgelaufen',
    'licenseEnterKey'    => 'Schlüssel eingeben',
    'licenseChangeKey'   => 'Ändern',
    'licenseRenew'       => 'Erneuern',
    'licenseSaved'       => 'Lizenzschlüssel gespeichert und validiert.',
    'licenseInvalid'     => 'Lizenzschlüssel ist nicht gültig.',
    'licenseKeyRequired' => 'Lizenzschlüssel und Produkt sind erforderlich.',
    'licenseProductNotFound' => 'Dieses Plugin konnte im Store nicht gefunden werden.',
    'licenseCheckFailed' => 'Der Lizenzserver konnte nicht erreicht werden. Bitte versuchen Sie es später erneut.',
    'licenseModalTitle'  => 'Lizenzschlüssel eingeben',
    'licenseModalBody'   => 'Fügen Sie Ihren Lizenzschlüssel unten ein.',
    'licenseModalSave'   => 'Speichern',
    'licenseThirdParty'  => 'Drittanbieter',

    // Addon Licensing
    'licenseRequiredActivation' => 'Zur Aktivierung dieses Plugins ist eine gültige Lizenz erforderlich.',

    // Email provider
    'emailProviderModalTitle'  => 'E-Mail-Zustellung',
    'emailProviderModalBody'   => 'Dieses Plugin kann grundlegende System-E-Mails (Kontaktformulare, Passwortzurücksetzungen usw.) sowie eigene E-Mails verarbeiten. Möchten Sie, dass es die grundlegende E-Mail-Zustellung übernimmt?',
    'emailProviderModalLabel'  => 'Kern-E-Mails senden über',
    'emailProviderCore'        => 'Kern (Standard)',
    'emailProviderModalSave'   => 'Speichern',
    'emailProviderSaved'       => 'E-Mail-Anbieter gespeichert.',
];
