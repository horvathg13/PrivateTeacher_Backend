<?php

namespace App\Jobs;

use App\Models\CourseInfos;
use App\Models\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheckCourseExpireDate
{
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function __invoke(): void
    {
        $getCourses = CourseInfos::all();

        if ($getCourses->isNotEmpty()) {
            foreach ($getCourses as $course) {
                if (Carbon::parse($course->end_date)->subDay(5) <= now() && Carbon::parse($course->end_date) > now()) {
                    $checkNotificationExists= Notifications::where(["receiver_id" => $course->teacher_id, "message" => "messages.notification.courseExpire"])->exists();
                    if (!$checkNotificationExists) {
                        DB::transaction(function () use ($course) {
                            Notifications::create([
                                "receiver_id" => $course->teacher_id,
                                "message" => "messages.notification.courseExpire",
                                "url" => "/course/".$course->id
                            ]);
                        });
                    }
                }
                if (Carbon::parse($course->end_date) < now()) {
                    if($course->course_status !== "FINISHED"){
                        DB::transaction(function () use (&$course) {
                            $course->update([
                                "course_status" => "FINISHED"
                            ]);
                        });
                    }

                    $checkNotificationExists = Notifications::where(["receiver_id" => $course->teacher_id, "message" => "messages.notification.courseExpired"])->exists();
                    if (!$checkNotificationExists) {
                        DB::transaction(function () use (&$course) {
                            Notifications::create([
                                "receiver_id" => $course->teacher_id,
                                "message" => "messages.notification.courseExpired",
                                "url" => "/course/".$course->id
                            ]);
                        });
                    }
                }
            }
        }else{
            \Log::info('Course not found');
        }
    }
}
