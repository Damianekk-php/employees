<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipStream\ZipStream;
use ZipStream\OperationMode;
use Illuminate\Support\Facades\Storage;
use ZipStream\Option\Archive;


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

            $query->whereHas('titles', function ($q) use ($status) {
                $q->whereIn('titles.to_date', function ($subQuery) {
                    $subQuery->selectRaw('MAX(to_date)')
                        ->from('titles')
                        ->whereColumn('titles.emp_no', 'employees.emp_no');
                });

                if ($status === 'current') {
                    $q->where(function ($q) {
                        $q->whereNull('to_date')
                            ->orWhere('to_date', '9999-01-01');
                    });
                } elseif ($status === 'past') {
                    $q->where('to_date', '<', now());
                }
            });
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

        $totalEmployees = $query->count();

        $employees = $query->paginate(10);

        return view('employees.index', compact('employees', 'departments', 'totalEmployees'));
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

        $history = [];
        $titleHistory = $employee->titles;
        $salaryHistory = $employee->salaries;

        foreach ($titleHistory as $titleRecord) {
            foreach ($salaryHistory as $salaryRecord) {
                if (
                    $titleRecord->from_date <= $salaryRecord->to_date &&
                    $salaryRecord->from_date <= $titleRecord->to_date
                ) {
                    $history[] = [
                        'title' => $titleRecord->title,
                        'salary' => $salaryRecord->salary,
                        'from_date' => max($titleRecord->from_date, $salaryRecord->from_date),
                        'to_date' => min(
                            $titleRecord->to_date !== '9999-01-01' ? $titleRecord->to_date : 'obecnie',
                            $salaryRecord->to_date !== '9999-01-01' ? $salaryRecord->to_date : 'obecnie'
                        ),
                    ];
                }
            }
        }

        $pdfData = [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'department' => $department,
            'title' => $title,
            'salary' => $salary,
            'totalSalary' => $totalSalary,
            'history' => $history,
        ];

        $pdf = Pdf::loadView('pdf.employee', $pdfData);

        return $pdf->download("{$first_name}_{$last_name}.pdf");
    }



    public function show($id)
    {
        $employee = Employee::with(['titles', 'salaries', 'departments'])->findOrFail($id);

        $currentDepartment = $employee->departments->last();
        $currentTitle = $employee->titles->last();
        $currentSalary = $employee->salaries->last();

        $titleHistory = $employee->titles->toArray();
        $salaryHistory = $employee->salaries->toArray();

        $history = [];
        foreach ($titleHistory as $title) {
            foreach ($salaryHistory as $salary) {
                if (
                    $title['from_date'] <= $salary['to_date'] &&
                    $salary['from_date'] <= $title['to_date']
                ) {
                    $history[] = [
                        'title' => $title['title'],
                        'salary' => $salary['salary'],
                        'from_date' => max($title['from_date'], $salary['from_date']),
                        'to_date' => min($title['to_date'], $salary['to_date']),
                    ];
                }
            }
        }

        return view('employees.show', compact(
            'employee',
            'currentDepartment',
            'currentTitle',
            'currentSalary',
            'history'
        ));
    }


    public function generatePdfs(Request $request)
    {
        $employeeIds = $request->input('employees', []);

        if (empty($employeeIds)) {
            return redirect()->route('employees.index')->with('error', 'Nie wybrano żadnego pracownika.');
        }

        $employees = Employee::whereIn('emp_no', $employeeIds)->get();

        if ($employees->isEmpty()) {
            return redirect()->route('employees.index')->with('error', 'Nie znaleziono wybranych pracowników.');
        }

        $pdfData = $employees->map(function ($employee) {
            $department = $employee->departments->first()->dept_name ?? 'Brak departamentu';
            $title = $employee->titles->last()->title ?? 'Brak tytułu';
            $salary = $employee->salaries->last()->salary ?? 0;

            return [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'department' => $department,
                'title' => $title,
                'salary' => $salary,
            ];
        });

        $pdf = Pdf::loadView('pdf.employees', ['employees' => $pdfData]);

        return $pdf->download('Pracownicy.pdf');
    }







}


