<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    //
    protected $fillable = [
        'user_id',
        'id_number',
        'department_id',
        'academic_year',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function classes()
    {
        return $this->belongsToMany(ClassRoom::class, "class_students", "student_id", "class_id");
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class, "test_student", "student_id", "test_id");
    }
}

