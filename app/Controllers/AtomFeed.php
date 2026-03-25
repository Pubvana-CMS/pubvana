<?php

namespace App\Controllers;

use App\Models\PostModel;

class AtomFeed extends BaseController
{
    public function index(): \CodeIgniter\HTTP\Response
    {
        $postModel = new PostModel();
        $posts     = $postModel->published()
            ->select('title, slug, excerpt, content, content_type, published_at, author_id')
            ->orderBy('published_at', 'DESC')
            ->limit(20)
            ->findAll();

        $siteName = site_name();
        $baseUrl  = rtrim(base_url(), '/') . '/';
        $atomUrl  = base_url('atom');

        // Feed-level updated = most recent post, or now if no posts
        $feedUpdated = !empty($posts)
            ? gmdate('Y-m-d\TH:i:s\Z', strtotime($posts[0]->published_at))
            : gmdate('Y-m-d\TH:i:s\Z');

        $xml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <title>' . esc($siteName) . "</title>\n";
        $xml .= '  <link href="' . esc($baseUrl) . "\" />\n";
        $xml .= '  <link rel="self" href="' . esc($atomUrl) . "\" />\n";
        $xml .= '  <id>' . esc($baseUrl) . "</id>\n";
        $xml .= '  <updated>' . $feedUpdated . "</updated>\n";
        $xml .= "  <author>\n";
        $xml .= '    <name>' . esc($siteName) . "</name>\n";
        $xml .= "  </author>\n";

        foreach ($posts as $post) {
            $content   = render_content($post);
            $postUrl   = post_url($post->slug);
            $updated   = gmdate('Y-m-d\TH:i:s\Z', strtotime($post->published_at));
            $published = $updated;

            // Escape ]]> so it cannot break out of the CDATA section
            $summary = str_replace(']]>', ']]]]><![CDATA[>', $post->excerpt ?? excerpt($content));
            $body    = str_replace(']]>', ']]]]><![CDATA[>', $content);

            $xml .= "  <entry>\n";
            $xml .= '    <title>' . esc($post->title) . "</title>\n";
            $xml .= '    <link href="' . esc($postUrl) . "\" />\n";
            $xml .= '    <id>' . esc($postUrl) . "</id>\n";
            $xml .= '    <updated>' . $updated . "</updated>\n";
            $xml .= '    <published>' . $published . "</published>\n";
            $xml .= '    <summary><![CDATA[' . $summary . "]]></summary>\n";
            $xml .= '    <content type="html"><![CDATA[' . $body . "]]></content>\n";
            $xml .= "  </entry>\n";
        }

        $xml .= '</feed>';

        return $this->response
            ->setContentType('application/atom+xml')
            ->setBody($xml);
    }
}
