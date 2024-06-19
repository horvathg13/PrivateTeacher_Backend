<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class CourseInfos extends Model
{
    use HasFactory;
    protected $table='course_infos';
    protected $fillable=[
        'name',
        'student_limit',
        'minutes_lesson',
        'min_teaching_day',
        'double_time',
        'course_price_per_lesson',
        'course_status',
        'school_location_id',
        'school_year_id',
        'lang',
        'teacher_id',
        'payment_period'
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
        return $this->BelongsToMany(Labels::class, 'course_labels', 'course_id','label_id', 'id', 'id');
    }

    public function courseNamesAndLangs():HasMany
    {
        return $this->hasMany(CourseLangsNames::class, 'course_id', 'id');
    }

    public function teacher():HasOne
    {
        return $this->hasOne(User::class, 'id', 'teacher_id' );
    }
    public function location():HasOneThrough
    {
        return $this->hasOneThrough(
            Locations::class,
            SchoolLocations::class,
            'id',
            'id',
            'school_location_id',
            'location_id'
        );
    }
}
