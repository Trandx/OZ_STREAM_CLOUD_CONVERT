<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenDriver extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        "pivot",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at",
    ];

}
