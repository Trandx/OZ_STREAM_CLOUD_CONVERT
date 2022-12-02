<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Medias extends Model
{
    use HasFactory;

    //protected $table = "medias";

    public $incrementing = false;

    /**
    *The attributes that are not mass assignable.
    *
    * @var Array $guarded <int, string>
    */

    protected $guarded = ["id"];

    // In Laravel 6.0+ make sure to also set $keyType
    protected $keyType = 'string';

        /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'details' => 'array',
        'converted_format' => 'array',
    ];

    /**
     * custom boot user ids
     */

    public static function boot()
    {

        parent::boot();

        self::creating(function ($model) {

            $model->id = Uuid::uuid4()->toString();

        });

    }
}
