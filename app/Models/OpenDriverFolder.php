<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenDriverFolder extends Model
{
    use HasFactory;

    protected $hidden = [
        "pivot",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at",
    ];

}
