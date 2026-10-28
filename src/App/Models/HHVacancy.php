<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HHVacancy extends Model
{
    public $incrementing = false;

    protected $table = 'hh_vacancies';

    protected $primaryKey = 'vacancy_id';

    protected $fillable = ['vacancy_id', 'hash', 'title', 'company', 'address', 'schedule', 'link', 'salary', 'description', 'published'];

    protected $casts = ['vacancy_id' => 'integer', 'published' => 'boolean'];
}
