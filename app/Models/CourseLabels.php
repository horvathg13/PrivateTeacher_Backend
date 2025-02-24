<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseLabels extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table='course_labels';
    protected $fillable=['course_id', 'label_id', 'language_id'];


    public function getCourseLabels():HasOne
    {
        return $this->hasOne(Labels::class, "id", "label_id");
    }

}
