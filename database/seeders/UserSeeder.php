<?php

namespace Database\Seeders;

use App\Models\Roles;
use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
            DB::transaction(function (){
                $teacher=User::insertGetId([
                    "first_name" => "Teacher",
                    "last_name" => "Test",
                    "email"=>"teacher@privateteacher.com",
                    "password" => bcrypt('teacher'),
                    "created_at" => now(),
                    "updated_at" => now(),
                    "user_status" => "ACTIVE"
                ]);
                $admin=User::insertGetId([
                    "first_name" => "Admin",
                    "last_name" => "Test",
                    "email"=>"admin@privateteacher.com",
                    "password" => bcrypt('admin'),
                    "created_at" => now(),
                    "updated_at" => now(),
                    "user_status" => "ACTIVE"
                ]);
                $parent=User::insertGetId([
                    "first_name" => "Parent",
                    "last_name" => "Test",
                    "email"=>"parent@privateteacher.com",
                    "password" => bcrypt('parent'),
                    "created_at" => now(),
                    "updated_at" => now(),
                    "user_status" => "ACTIVE"
                ]);

                UserRoles::insert([
                    "user_id" => $teacher,
                    "role_id" => 2
                ]);
                UserRoles::insert([
                    "user_id" => $admin,
                    "role_id" => 1
                ]);
                UserRoles::insert([
                    "user_id" => $parent,
                    "role_id" => 3
                ]);
            });


    }
}
