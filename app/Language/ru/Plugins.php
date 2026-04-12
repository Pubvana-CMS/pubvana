<?php

/**
 * Pubvana CMS - Plugins language strings (Russian)
 *
 * Usage: lang('Plugins.keyName')
 */

return [
    'title'              => 'Плагины',
    'scanBtn'            => 'Найти плагины',
    'noPlugins'          => 'Плагины не установлены. Посетите <a href="/admin/marketplace">Магазин</a>, чтобы просмотреть доступные плагины.',
    'colPlugin'          => 'Плагин',
    'colVersion'         => 'Версия',
    'colStatus'          => 'Статус',
    'colSafe'            => 'Безопасность',
    'colActions'         => 'Действия',
    'statusActive'       => 'Активен',
    'statusInactive'     => 'Неактивен',
    'safeYes'            => 'Безопасный',
    'safeNo'             => 'Небезопасный',
    'safeMalicious'      => 'Вредоносный',
    'safeUnchecked'      => 'Не проверен',
    'safeUnknown'        => 'Неизвестно',
    'btnActivate'        => 'Активировать',
    'btnDeactivate'      => 'Деактивировать',
    'btnActivateAnyway'  => 'Активировать в любом случае',
    'btnCancel'          => 'Отмена',
    'modalTitle'         => 'Активация непроверенного плагина',
    'modalSecurityWarn'  => 'Предупреждение безопасности:',
    'modalNotSafe'       => 'Этот плагин не был проверен на безопасность компанией Pubvana.',
    'modalRiskWarning'   => 'Активация непроверенных плагинов может создать угрозы безопасности или нестабильность.',
    'modalConfirm'       => 'Вы уверены, что хотите активировать его?',
    'discovered'         => 'Обнаружен(ы) новый(е) плагин(ы).',
    'noneFound'          => 'Новые плагины не найдены.',
    'activated'          => 'Плагин активирован.',
    'deactivated'        => 'Плагин деактивирован.',
    'notFound'           => 'Плагин не найден.',
    'alreadyActive'      => 'Плагин уже активен.',
    'migrationFailed'    => 'Плагину не удалось выполнить миграции базы данных. Подробности в журнале.',
    'installFailed'      => 'Установщик плагина завершился с ошибкой. Частичная установка была откатана. Подробности в журнале.',

    // License column
    'support'            => 'Поддержка',
    'colLicense'         => 'Лицензия',
    'licenseLicensed'    => 'Лицензирован',
    'licenseCheckNow'    => 'Проверить сейчас',
    'licenseExpired'     => 'Истекла',
    'licenseEnterKey'    => 'Ввести ключ',
    'licenseChangeKey'   => 'Изменить',
    'licenseRenew'       => 'Продлить',
    'licenseSaved'       => 'Лицензионный ключ сохранён и подтверждён.',
    'licenseInvalid'     => 'Лицензионный ключ недействителен.',
    'licenseKeyRequired' => 'Лицензионный ключ и продукт обязательны.',
    'licenseProductNotFound' => 'Не удалось найти этот плагин в магазине.',
    'licenseCheckFailed' => 'Не удалось связаться с сервером лицензий. Попробуйте позже.',
    'licenseModalTitle'  => 'Введите лицензионный ключ',
    'licenseModalBody'   => 'Вставьте ваш лицензионный ключ ниже.',
    'licenseModalSave'   => 'Сохранить',
    'licenseThirdParty'  => 'Сторонний',

    // Addon Licensing
    'licenseRequiredActivation' => 'Для активации этого плагина требуется действующая лицензия.',

    // Email provider
    'emailProviderModalTitle'  => 'Доставка электронной почты',
    'emailProviderModalBody'   => 'Этот плагин может обрабатывать основные системные письма (контактные формы, сброс пароля и т.д.), а также собственные. Хотите, чтобы он взял на себя основную доставку электронной почты?',
    'emailProviderModalLabel'  => 'Отправлять основные письма через',
    'emailProviderCore'        => 'Основной (по умолчанию)',
    'emailProviderModalSave'   => 'Сохранить',
    'emailProviderSaved'       => 'Провайдер электронной почты сохранён.',
];
