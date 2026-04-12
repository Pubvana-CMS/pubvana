<?php

/**
 * Pubvana CMS - Plugins language strings (Japanese)
 *
 * Usage: lang('Plugins.keyName')
 */

return [
    'title'              => 'プラグイン',
    'scanBtn'            => 'プラグインを検索',
    'noPlugins'          => 'プラグインがインストールされていません。<a href="/admin/marketplace">マーケットプレイス</a>で利用可能なプラグインを検索してください。',
    'colPlugin'          => 'プラグイン',
    'colVersion'         => 'バージョン',
    'colStatus'          => 'ステータス',
    'colSafe'            => '安全性',
    'colActions'         => 'アクション',
    'statusActive'       => '有効',
    'statusInactive'     => '無効',
    'safeYes'            => '安全',
    'safeNo'             => '安全でない',
    'safeMalicious'      => '悪意あり',
    'safeUnchecked'      => '未確認',
    'safeUnknown'        => '不明',
    'btnActivate'        => '有効化',
    'btnDeactivate'      => '無効化',
    'btnActivateAnyway'  => 'それでも有効化',
    'btnCancel'          => 'キャンセル',
    'modalTitle'         => '未確認プラグインの有効化',
    'modalSecurityWarn'  => 'セキュリティ警告：',
    'modalNotSafe'       => 'このプラグインはPubvanaによって安全と確認されていません。',
    'modalRiskWarning'   => '未確認のプラグインを有効化すると、セキュリティリスクや不安定性が生じる可能性があります。',
    'modalConfirm'       => '本当に有効化しますか？',
    'discovered'         => '新しいプラグインが見つかりました。',
    'noneFound'          => '新しいプラグインは見つかりませんでした。',
    'activated'          => 'プラグインが有効化されました。',
    'deactivated'        => 'プラグインが無効化されました。',
    'notFound'           => 'プラグインが見つかりません。',
    'alreadyActive'      => 'プラグインはすでに有効です。',
    'migrationFailed'    => 'プラグインのデータベースマイグレーションに失敗しました。詳細はログをご確認ください。',
    'installFailed'      => 'プラグインのインストールに失敗しました。部分的なセットアップはロールバックされました。詳細はログをご確認ください。',

    // License column
    'support'            => 'サポート',
    'colLicense'         => 'ライセンス',
    'licenseLicensed'    => 'ライセンス済み',
    'licenseCheckNow'    => '今すぐ確認',
    'licenseExpired'     => '期限切れ',
    'licenseEnterKey'    => 'キーを入力',
    'licenseChangeKey'   => '変更',
    'licenseRenew'       => '更新',
    'licenseSaved'       => 'ライセンスキーが保存・検証されました。',
    'licenseInvalid'     => 'ライセンスキーが無効です。',
    'licenseKeyRequired' => 'ライセンスキーと製品が必要です。',
    'licenseProductNotFound' => 'ストアでこのプラグインが見つかりませんでした。',
    'licenseCheckFailed' => 'ライセンスサーバーに接続できません。後でもう一度お試しください。',
    'licenseModalTitle'  => 'ライセンスキーを入力',
    'licenseModalBody'   => '以下にライセンスキーを貼り付けてください。',
    'licenseModalSave'   => '保存',
    'licenseThirdParty'  => 'サードパーティ',

    // Addon Licensing
    'licenseRequiredActivation' => 'このプラグインを有効化するには有効なライセンスが必要です。',

    // Email provider
    'emailProviderModalTitle'  => 'メール配信',
    'emailProviderModalBody'   => 'このプラグインは、コアシステムメール（お問い合わせフォーム、パスワードリセットなど）および独自のメールを処理できます。コアのメール配信を引き継ぎますか？',
    'emailProviderModalLabel'  => 'コアメールの送信先',
    'emailProviderCore'        => 'コア（デフォルト）',
    'emailProviderModalSave'   => '保存',
    'emailProviderSaved'       => 'メールプロバイダーが保存されました。',
];
