<?php

namespace App\Models;

use CodeIgniter\Model;

class PostRevisionModel extends Model
{
    protected $table      = 'post_revisions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $updatedField = '';

    protected $allowedFields = [
        'post_id',
        'author_id',
        'title',
        'content',
        'content_type',
        'excerpt',
        'status',
        'meta_title',
        'meta_description',
    ];
}
