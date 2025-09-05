<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = ['name', 'content'];

    protected $casts = [
        'content' => 'array'
    ];

    public function variables()
    {
        return $this->hasMany(DocumentTemplateVariable::class);
    }
}
