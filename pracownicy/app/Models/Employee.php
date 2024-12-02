<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public function titles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Title::class, 'emp_no', 'emp_no');
    }

    public function salaries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Salary::class, 'emp_no', 'emp_no');
    }

    public function departments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'dept_emp', 'emp_no', 'dept_no')
            ->withPivot('from_date', 'to_date');
    }

    protected $primaryKey = 'emp_no';
    public $incrementing = false;
}
