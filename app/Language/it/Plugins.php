<?php

/**
 * Pubvana CMS - Plugins language strings (Italian)
 *
 * Usage: lang('Plugins.keyName')
 */

return [
    'title'              => 'Plugin',
    'scanBtn'            => 'Cerca plugin',
    'noPlugins'          => 'Nessun plugin installato. Visita il <a href="/admin/marketplace">Marketplace</a> per sfogliare i plugin disponibili.',
    'colPlugin'          => 'Plugin',
    'colVersion'         => 'Versione',
    'colStatus'          => 'Stato',
    'colSafe'            => 'Sicuro',
    'colActions'         => 'Azioni',
    'statusActive'       => 'Attivo',
    'statusInactive'     => 'Inattivo',
    'safeYes'            => 'Sicuro',
    'safeNo'             => 'Non sicuro',
    'safeMalicious'      => 'Dannoso',
    'safeUnchecked'      => 'Non verificato',
    'safeUnknown'        => 'Sconosciuto',
    'btnActivate'        => 'Attiva',
    'btnDeactivate'      => 'Disattiva',
    'btnActivateAnyway'  => 'Attiva comunque',
    'btnCancel'          => 'Annulla',
    'modalTitle'         => 'Attiva plugin non verificato',
    'modalSecurityWarn'  => 'Avviso di sicurezza:',
    'modalNotSafe'       => 'Questo plugin non è stato verificato come sicuro da Pubvana.',
    'modalRiskWarning'   => "L'attivazione di plugin non verificati può introdurre rischi di sicurezza o instabilità.",
    'modalConfirm'       => 'Sei sicuro di volerlo attivare?',
    'discovered'         => 'Nuovo/i plugin trovato/i.',
    'noneFound'          => 'Nessun nuovo plugin trovato.',
    'activated'          => 'Plugin attivato.',
    'deactivated'        => 'Plugin disattivato.',
    'notFound'           => 'Plugin non trovato.',
    'alreadyActive'      => 'Il plugin è già attivo.',
    'migrationFailed'    => 'Il plugin non è riuscito ad eseguire le migrazioni del database. Controlla i log per i dettagli.',
    'installFailed'      => "L'installazione del plugin è fallita. Qualsiasi configurazione parziale è stata annullata. Controlla i log per i dettagli.",

    // License column
    'support'            => 'Supporto',
    'colLicense'         => 'Licenza',
    'licenseLicensed'    => 'Licenziato',
    'licenseCheckNow'    => 'Verifica ora',
    'licenseExpired'     => 'Scaduta',
    'licenseEnterKey'    => 'Inserisci chiave',
    'licenseChangeKey'   => 'Cambia',
    'licenseRenew'       => 'Rinnova',
    'licenseSaved'       => 'Chiave di licenza salvata e validata.',
    'licenseInvalid'     => 'La chiave di licenza non è valida.',
    'licenseKeyRequired' => 'La chiave di licenza e il prodotto sono obbligatori.',
    'licenseProductNotFound' => 'Impossibile trovare questo plugin nel negozio.',
    'licenseCheckFailed' => 'Impossibile raggiungere il server di licenza. Riprova più tardi.',
    'licenseModalTitle'  => 'Inserisci chiave di licenza',
    'licenseModalBody'   => 'Incolla la tua chiave di licenza qui sotto.',
    'licenseModalSave'   => 'Salva',
    'licenseThirdParty'  => 'Terze parti',

    // Addon Licensing
    'licenseRequiredActivation' => 'È necessaria una licenza valida per attivare questo plugin.',

    // Email provider
    'emailProviderModalTitle'  => 'Consegna e-mail',
    'emailProviderModalBody'   => 'Questo plugin può gestire le e-mail di sistema principali (moduli di contatto, reimpostazione password, ecc.) oltre alle proprie. Vuoi che si occupi della consegna principale delle e-mail?',
    'emailProviderModalLabel'  => 'Invia e-mail principali tramite',
    'emailProviderCore'        => 'Principale (predefinito)',
    'emailProviderModalSave'   => 'Salva',
    'emailProviderSaved'       => 'Fornitore e-mail salvato.',
];
