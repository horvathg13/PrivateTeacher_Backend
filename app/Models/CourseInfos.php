<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class CourseInfos extends Model
{
    use HasFactory;
    protected $table='course_infos';
    protected $fillable=[
        'student_limit',
        'minutes_lesson',
        'min_teaching_day',
        'course_price_per_lesson',
        'course_status',
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
            CourseLocations::class,
            'course_id',
            'id',
            'id',
            'location_id'
        );
    }
    public function locations():HasManyThrough
    {
        return $this->hasManyThrough(Locations::class, CourseLocations::class, 'location_id', 'id', 'id');
    }
}
