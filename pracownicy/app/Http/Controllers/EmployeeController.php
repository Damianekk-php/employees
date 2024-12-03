<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::all();

        $query = Employee::query()
            ->with(['departments', 'titles', 'salaries' => function($q) {
                $q->orderBy('to_date', 'desc');
            }]);

        if ($request->filled('status')) {
            $status = $request->input('status');

            if ($status === 'current') {
                $query->whereHas('titles', function ($q) {
                    $q->whereNull('to_date')
                        ->orWhere('to_date', '>', now());
                });
            } elseif ($status === 'past') {
                $query->whereHas('titles', function ($q) {
                    $q->where('to_date', '<', now());
                });
            }
        }


        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('salary_min') || $request->filled('salary_max')) {
            $salary_min = $request->input('salary_min', 0);
            $salary_max = $request->input('salary_max', PHP_INT_MAX);
            $query->whereHas('salaries', function($q) use ($salary_min, $salary_max) {
                $q->whereBetween('salary', [$salary_min, $salary_max]);
            });
        }

        if ($request->filled('department')) {
            $query->whereHas('departments', function($q) use ($request) {
                $q->where('departments.dept_no', $request->input('department'));
            });
        }

        $employees = $query->paginate(10);

        return view('employees.index', compact('employees', 'departments'));
    }


    public function export(Request $request)
    {
        $employees = Employee::query()
            ->with(['departments', 'titles', 'salaries' => function($q) {
                $q->orderBy('to_date', 'desc');
            }])
            ->when($request->has('status'), function ($query) use ($request) {
                $status = $request->input('status') === 'current' ? '9999-01-01' : '<9999-01-01';
                $query->whereHas('titles', function ($q) use ($status) {
                    $q->where('to_date', $status);
                });
            })
            ->when($request->has('gender'), function ($query) use ($request) {
                $query->where('gender', $request->input('gender'));
            })
            ->when($request->has('salary_min') || $request->has('salary_max'), function ($query) use ($request) {
                $query->whereHas('salaries', function ($q) use ($request) {
                    $q->whereBetween('salary', [
                        $request->input('salary_min', 0),
                        $request->input('salary_max', PHP_INT_MAX)
                    ]);
                });
            })
            ->when($request->has('department'), function ($query) use ($request) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('dept_no', $request->input('department'));
                });
            })
            ->get();

        $pdf = Pdf::loadView('employees.pdf', compact('employees'));
        return $pdf->download('employees.pdf');
    }


    public function showPdfForm()
    {
        return view('generate-pdf-form');
    }

    public function generatePdf($first_name, $last_name)
    {
        $employee = Employee::where('first_name', $first_name)
            ->where('last_name', $last_name)
            ->first();

        if (!$employee) {
            return redirect()->route('employees.index')->with('error', 'Pracownik nie znaleziony.');
        }

        $department = $employee->departments->first()->dept_name ?? 'Brak departamentu';
        $title = $employee->titles->last()->title ?? 'Brak tytułu';
        $salary = $employee->salaries->last()->salary ?? 0;
        $totalSalary = $employee->salaries->sum('salary');

        $pdfData = [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'department' => $department,
            'title' => $title,
            'salary' => $salary,
            'totalSalary' => $totalSalary
        ];

        $pdf = Pdf::loadView('pdf.employee', $pdfData);

        return $pdf->download("{$first_name}_{$last_name}_pdf.pdf");
    }
}


