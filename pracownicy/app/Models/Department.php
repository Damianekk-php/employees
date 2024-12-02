<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $primaryKey = 'dept_no';
    public $incrementing = false;

    public function employees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'dept_emp', 'dept_no', 'emp_no')
            ->withPivot('from_date', 'to_date');
    }
}
