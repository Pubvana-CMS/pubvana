<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class WelcomePostSeeder extends Seeder
{
    public function run()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $db->table('posts')->ignore(true)->insert([
            'title'            => 'Welcome to Pubvana CMS',
            'slug'             => 'welcome-to-pubvana-cms',
            'content'          => '<p>Congratulations, your new Pubvana CMS site is up and running! Pubvana CMS is a modern, lightweight content management system built on CodeIgniter 4. It comes with a clean admin dashboard, theme, plugin, and widget support, a marketplace for extensions, and everything you need to start publishing right away.</p>'
                                . '<p>This is your first post. You can edit or delete it from the admin panel under Posts. From there you can create new posts, manage categories and tags, upload media, and customize your site\'s appearance under Themes. Take a look around the admin area to get familiar with all the tools at your disposal. Happy publishing!</p>',
            'content_type'     => 'html',
            'excerpt'          => 'Your new Pubvana CMS site is up and running. Edit or delete this post from the admin panel to get started.',
            'status'           => 'published',
            'featured_image'   => 'https://cdn.pubvana.net/pubvana-banner.png',
            'author_id'        => 1,
            'published_at'     => $now,
            'share_on_publish' => 0,
            'lang'             => 'en',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $postId = $db->insertID();

        // Tags
        $tags = ['cms', 'blogging', 'open-source', 'getting-started'];
        foreach ($tags as $tagName) {
            $slug = url_title($tagName, '-', true);
            $db->table('tags')->ignore(true)->insert([
                'name' => $tagName,
                'slug' => $slug,
            ]);
            $tagId = $db->insertID();
            if (! $tagId) {
                $tagId = (int) $db->table('tags')->where('slug', $slug)->get()->getRowObject()->id;
            }
            $db->table('tags_to_posts')->ignore(true)->insert([
                'tag_id'  => $tagId,
                'post_id' => $postId,
            ]);
        }

        // Welcome page
        $db->table('pages')->ignore(true)->insert([
            'title'        => 'Welcome to Pubvana CMS',
            'slug'         => 'welcome-to-pubvana-cms',
            'content'      => '<p>Congratulations, your new Pubvana CMS site is up and running! Pubvana CMS is a modern, lightweight content management system built on CodeIgniter 4. It comes with a clean admin dashboard, theme, plugin, and widget support, a marketplace for extensions, and everything you need to start publishing right away.</p>'
                            . '<p>This is your first page. You can edit or delete it from the admin panel under Pages. From there you can create new pages, organize them with parent pages, and build out your site\'s structure. Take a look around the admin area to get familiar with all the tools at your disposal. Happy publishing!</p>',
            'content_type' => 'html',
            'status'       => 'published',
            'sort_order'   => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }
}
