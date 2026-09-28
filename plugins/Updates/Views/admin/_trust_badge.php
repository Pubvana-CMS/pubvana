<?php
/**
 * Trust badge partial for this plugin's own screens.
 *
 * Same markup and same statuses as the core admin partial, kept here so the
 * plugin renders its tables without reaching into app/Views. The badgeHtml()
 * helper in the page script below mirrors it for ajax rechecks.
 *
 * @var string      $badgeStatus  'trusted'|'known'|'malicious'|'unknown'|'none'
 * @var string|null $badgeWarning Warning text for the badge tooltip
 */

$badgeWarningAttr = $badgeWarning !== null && $badgeWarning !== ''
    ? ' title="' . htmlspecialchars($badgeWarning, ENT_QUOTES, 'UTF-8') . '"'
    : '';

echo match ($badgeStatus) {
    'trusted'   => '<span class="badge bg-green-lt text-success"><i class="ti ti-shield-check me-1"></i>Trusted</span>',
    'known'     => '<span class="badge bg-azure-lt"' . $badgeWarningAttr . '><i class="ti ti-shield me-1"></i>Known</span>',
    'malicious' => '<span class="badge bg-red-lt text-danger"' . $badgeWarningAttr . '><i class="ti ti-alert-triangle me-1"></i>Malicious</span>',
    'unknown'   => '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-help me-1"></i>Unknown</span>',
    default     => '<span class="badge bg-secondary-lt"><i class="ti ti-circle-dashed me-1"></i>Not checked</span>',
};
