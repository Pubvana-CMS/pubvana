<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameWidgetFolders extends Migration
{
    private array $map = [
        'ad_unit'           => 'AdUnit',
        'archive_list'      => 'ArchiveList',
        'author_bio'        => 'AuthorBio',
        'categories_list'   => 'CategoriesList',
        'recent_comments'   => 'RecentComments',
        'recent_posts'      => 'RecentPosts',
        'related_posts'     => 'RelatedPosts',
        'search_form'       => 'SearchForm',
        'social_links'      => 'SocialLinks',
        'table_of_contents' => 'TableOfContents',
        'tag_cloud'         => 'TagCloud',
        'text_block'        => 'TextBlock',
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            $this->db->table('widgets')->where('folder', $old)->update(['folder' => $new]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $old => $new) {
            $this->db->table('widgets')->where('folder', $new)->update(['folder' => $old]);
        }
    }
}
