<?php

/**
 * Pubvana CMS - Plugins language strings (Czech)
 *
 * Usage: lang('Plugins.keyName')
 */

return [
    'title'              => 'Pluginy',
    'scanBtn'            => 'Vyhledat pluginy',
    'noPlugins'          => 'Žádné pluginy nejsou nainstalovány. Navštivte <a href="/admin/marketplace">Tržiště</a> a prohlédněte si dostupné pluginy.',
    'colPlugin'          => 'Plugin',
    'colVersion'         => 'Verze',
    'colStatus'          => 'Stav',
    'colSafe'            => 'Bezpečný',
    'colActions'         => 'Akce',
    'statusActive'       => 'Aktivní',
    'statusInactive'     => 'Neaktivní',
    'safeYes'            => 'Bezpečný',
    'safeNo'             => 'Nebezpečný',
    'safeMalicious'      => 'Škodlivý',
    'safeUnchecked'      => 'Neověřený',
    'safeUnknown'        => 'Neznámý',
    'btnActivate'        => 'Aktivovat',
    'btnDeactivate'      => 'Deaktivovat',
    'btnActivateAnyway'  => 'Přesto aktivovat',
    'btnCancel'          => 'Zrušit',
    'modalTitle'         => 'Aktivovat neověřený plugin',
    'modalSecurityWarn'  => 'Bezpečnostní varování:',
    'modalNotSafe'       => 'Tento plugin nebyl Pubvana ověřen jako bezpečný.',
    'modalRiskWarning'   => 'Aktivace neověřených pluginů může zavést bezpečnostní rizika nebo nestabilitu.',
    'modalConfirm'       => 'Opravdu ho chcete aktivovat?',
    'discovered'         => 'Nalezen/y nový/é plugin(y).',
    'noneFound'          => 'Žádné nové pluginy nenalezeny.',
    'activated'          => 'Plugin byl aktivován.',
    'deactivated'        => 'Plugin byl deaktivován.',
    'notFound'           => 'Plugin nenalezen.',
    'alreadyActive'      => 'Plugin je již aktivní.',
    'migrationFailed'    => 'Plugin se nepodařilo spustit databázové migrace. Podrobnosti naleznete v protokolech.',
    'installFailed'      => 'Instalace pluginu selhala. Případná částečná instalace byla vrácena. Podrobnosti naleznete v protokolech.',

    // License column
    'support'            => 'Podpora',
    'colLicense'         => 'Licence',
    'licenseLicensed'    => 'Licencováno',
    'licenseCheckNow'    => 'Zkontrolovat nyní',
    'licenseExpired'     => 'Vypršela',
    'licenseEnterKey'    => 'Zadat klíč',
    'licenseChangeKey'   => 'Změnit',
    'licenseRenew'       => 'Obnovit',
    'licenseSaved'       => 'Licenční klíč byl uložen a ověřen.',
    'licenseInvalid'     => 'Licenční klíč není platný.',
    'licenseKeyRequired' => 'Licenční klíč a produkt jsou povinné.',
    'licenseProductNotFound' => 'Tento plugin se v obchodě nepodařilo najít.',
    'licenseCheckFailed' => 'Nelze se připojit k licenčnímu serveru. Zkuste to prosím později.',
    'licenseModalTitle'  => 'Zadat licenční klíč',
    'licenseModalBody'   => 'Vložte svůj licenční klíč níže.',
    'licenseModalSave'   => 'Uložit',
    'licenseThirdParty'  => 'Třetí strana',

    // Addon Licensing
    'licenseRequiredActivation' => 'K aktivaci tohoto pluginu je vyžadována platná licence.',

    // Email provider
    'emailProviderModalTitle'  => 'Doručování e-mailů',
    'emailProviderModalBody'   => 'Tento plugin dokáže zpracovávat základní systémové e-maily (kontaktní formuláře, obnovení hesla atd.) i vlastní. Chcete, aby převzal základní doručování e-mailů?',
    'emailProviderModalLabel'  => 'Odesílat základní e-maily prostřednictvím',
    'emailProviderCore'        => 'Základní (výchozí)',
    'emailProviderModalSave'   => 'Uložit',
    'emailProviderSaved'       => 'Poskytovatel e-mailu uložen.',
];
