<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RedirectFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = '/' . ltrim($request->getUri()->getPath(), '/');

        if (str_starts_with($path, '/admin') || str_starts_with($path, '/api/')) {
            return;
        }

        $redirects = cache('redirects_map');
        if ($redirects === null) {
            $rows = model(\App\Models\RedirectModel::class)->findAll();
            $redirects = [];
            foreach ($rows as $row) {
                $redirects[$row->from_url] = [
                    'to'   => $row->to_url,
                    'type' => (int) ($row->type ?? 301),
                ];
            }
            cache()->save('redirects_map', $redirects, 3600);
        }

        if (isset($redirects[$path])) {
            return redirect()->to($redirects[$path]['to'], $redirects[$path]['type']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
