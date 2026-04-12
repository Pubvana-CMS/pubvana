<?php

/**
 * Pubvana CMS - Plugins language strings (Mandarin Chinese - Simplified)
 *
 * Usage: lang('Plugins.keyName')
 */

return [
    'title'              => '插件',
    'scanBtn'            => '扫描插件',
    'noPlugins'          => '未安装任何插件。请访问<a href="/admin/marketplace">应用市场</a>浏览可用插件。',
    'colPlugin'          => '插件',
    'colVersion'         => '版本',
    'colStatus'          => '状态',
    'colSafe'            => '安全',
    'colActions'         => '操作',
    'statusActive'       => '已启用',
    'statusInactive'     => '已禁用',
    'safeYes'            => '安全',
    'safeNo'             => '不安全',
    'safeMalicious'      => '恶意',
    'safeUnchecked'      => '未检查',
    'safeUnknown'        => '未知',
    'btnActivate'        => '启用',
    'btnDeactivate'      => '禁用',
    'btnActivateAnyway'  => '仍然启用',
    'btnCancel'          => '取消',
    'modalTitle'         => '启用未经审核的插件',
    'modalSecurityWarn'  => '安全警告：',
    'modalNotSafe'       => '此插件尚未经 Pubvana 验证为安全。',
    'modalRiskWarning'   => '启用未经审核的插件可能引入安全风险或不稳定性。',
    'modalConfirm'       => '您确定要启用它吗？',
    'discovered'         => '发现新插件。',
    'noneFound'          => '未发现新插件。',
    'activated'          => '插件已启用。',
    'deactivated'        => '插件已禁用。',
    'notFound'           => '未找到插件。',
    'alreadyActive'      => '插件已处于启用状态。',
    'migrationFailed'    => '插件运行数据库迁移失败，请检查日志获取详情。',
    'installFailed'      => '插件安装程序失败，所有部分设置已回滚，请检查日志获取详情。',

    // License column
    'support'            => '支持',
    'colLicense'         => '许可证',
    'licenseLicensed'    => '已授权',
    'licenseCheckNow'    => '立即检查',
    'licenseExpired'     => '已过期',
    'licenseEnterKey'    => '输入密钥',
    'licenseChangeKey'   => '更改',
    'licenseRenew'       => '续期',
    'licenseSaved'       => '许可证密钥已保存并验证。',
    'licenseInvalid'     => '许可证密钥无效。',
    'licenseKeyRequired' => '许可证密钥和产品为必填项。',
    'licenseProductNotFound' => '在商店中找不到此插件。',
    'licenseCheckFailed' => '无法连接到许可证服务器，请稍后重试。',
    'licenseModalTitle'  => '输入许可证密钥',
    'licenseModalBody'   => '请在下方粘贴您的许可证密钥。',
    'licenseModalSave'   => '保存',
    'licenseThirdParty'  => '第三方',

    // Addon Licensing
    'licenseRequiredActivation' => '启用此插件需要有效的许可证。',

    // Email provider
    'emailProviderModalTitle'  => '邮件投递',
    'emailProviderModalBody'   => '此插件可以处理核心系统邮件（联系表单、密码重置等）以及自己的邮件。您希望它接管核心邮件投递吗？',
    'emailProviderModalLabel'  => '通过以下方式发送核心邮件',
    'emailProviderCore'        => '核心（默认）',
    'emailProviderModalSave'   => '保存',
    'emailProviderSaved'       => '邮件提供商已保存。',
];
