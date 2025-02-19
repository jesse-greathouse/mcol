<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model;

/**
 * Class FileFirstAppearance
 *
 * Represents the first appearance of a file in the system.
 *
 * @package App\Models
 */
class FileFirstAppearance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
}
