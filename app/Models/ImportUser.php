<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportUser extends Model
{
    use HasFactory;
    
    protected $table = 'import_users';
    protected $guarded = [];
}
