<?php

namespace App\Services;

use App\Models\PluginModel;
use App\Models\ThemeModel;
use App\Models\WidgetModel;

class VettingService
{
    /**
     * Normalize an author string: lowercase, spaces to underscores.
     */
    public static function normalizeAuthor(string $author): string
    {
        return strtolower(str_replace(' ', '_', trim($author)));
    }

    /**
     * Check approval status for all unchecked plugins, themes, and widgets.
     *
     * Calls the pubvana.net vetting API with unchecked items.
     * Updates pv_safe and pv_warning_note in the appropriate table.
     * On network failure, leaves as NULL (retry on next discover/sync).
     */
    public function checkApproval(): void
    {
        $pluginModel = model(PluginModel::class);
        $themeModel  = model(ThemeModel::class);
        $widgetModel = model(WidgetModel::class);

        $uncheckedPlugins = $pluginModel->where('pv_safe', null)->findAll();
        $uncheckedThemes  = $themeModel->where('pv_safe', null)->findAll();
        $uncheckedWidgets = $widgetModel->where('pv_safe', null)->findAll();

        if (empty($uncheckedPlugins) && empty($uncheckedThemes) && empty($uncheckedWidgets)) {
            return;
        }

        $items = [];

        foreach ($uncheckedPlugins as $p) {
            $items[] = [
                'type'    => 'plugin',
                'slug'    => $p->folder,
                'version' => $p->version,
                'author'  => $p->author ?? '',
            ];
        }

        foreach ($uncheckedThemes as $t) {
            $items[] = [
                'type'    => 'theme',
                'slug'    => $t->folder,
                'version' => $t->version ?? '',
                'author'  => $t->author ?? '',
            ];
        }

        foreach ($uncheckedWidgets as $w) {
            $items[] = [
                'type'    => 'widget',
                'slug'    => $w->folder,
                'version' => $w->version ?? '',
                'author'  => $w->author ?? '',
            ];
        }

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->post(PUBVANA_API_BASE . 'vetted/v1/check', [
                'json'        => [
                    'pv_version' => APP_VERSION,
                    'base_url'   => base_url(),
                    'items'      => $items,
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return;
            }

            $body = json_decode($response->getBody(), true);

            if (! is_array($body) || ! isset($body['results'])) {
                return;
            }

            $resultMap = [];
            foreach ($body['results'] as $r) {
                $key = $r['type'] . '|' . $r['slug'] . '|' . $r['version'];
                $resultMap[$key] = $r;
            }

            foreach ($uncheckedPlugins as $p) {
                $key = 'plugin|' . $p->folder . '|' . $p->version;
                $this->applyResult($pluginModel, $p->id, $resultMap[$key] ?? null);
            }

            foreach ($uncheckedThemes as $t) {
                $key = 'theme|' . $t->folder . '|' . ($t->version ?? '');
                $this->applyResult($themeModel, $t->id, $resultMap[$key] ?? null);
            }

            foreach ($uncheckedWidgets as $w) {
                $key = 'widget|' . $w->folder . '|' . ($w->version ?? '');
                $this->applyResult($widgetModel, $w->id, $resultMap[$key] ?? null);
            }
        } catch (\Throwable $e) {
            log_message('debug', 'VettingService::checkApproval unreachable: ' . $e->getMessage());
        }
    }

    private function applyResult($model, int $id, ?array $result): void
    {
        if (! $result) {
            return;
        }

        $status = $result['status'] ?? 'unknown';
        $warning = $result['warning'] ?? null;

        switch ($status) {
            case 'approved':
                $model->update($id, [
                    'pv_safe'     => 1,
                    'pv_warning_note' => null,
                ]);
                break;
            case 'known':
                $model->update($id, [
                    'pv_safe'     => 1,
                    'pv_warning_note' => $warning,
                ]);
                break;
            case 'malicious':
                $model->update($id, [
                    'pv_safe'     => 0,
                    'pv_warning_note' => $warning,
                ]);
                break;
            default:
                break;
        }
    }
}
