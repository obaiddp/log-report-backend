<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class IssueType extends Model
{
    protected $fillable = [
        'name'
    ];

    public function supportLogs(): BelongsToMany
    {
        return $this->BelongsToMany(SupportLog::class, 'support_log_issue_types');        
    }    

}