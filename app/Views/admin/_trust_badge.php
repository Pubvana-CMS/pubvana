<?php
/**
 * Trust badge partial, included by the core admin trust tables.
 *
 * One definition of the badge markup for the themes and plugins screens.
 * Statuses come from the Pubvana trust service cache: trusted, known,
 * malicious, unknown, and 'none' for anything not checked yet. A warning
 * from the trust service reads as the badge tooltip: it is an answer to
 * look at, not just a verdict to trust.
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
