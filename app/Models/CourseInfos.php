<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseInfos extends Model
{
    use HasFactory;
    protected $table='course_infos';
    protected $fillable=[
        'name',
        'subject',
        'student_limit',
        'minutes_lesson',
        'min_teaching_day',
        'double_time',
        'course_price_per_lesson',
        'course_status',
        'school_id',
        'school_year_id'
    ];
    public $timestamps = false;

    public function status():BelongsTo
    {
        return $this->BelongsTo(Statuses::class);
    }

    public function school():BelongsTo
    {
        return $this->BelongsTo(Schools::class);
    }
    public function label():BelongsToMany
    {
        return $this->BelongsToMany(Labels::class, 'course_labels', 'course_id','label_id');
    }
}
