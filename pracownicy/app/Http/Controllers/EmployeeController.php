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

        $titleHistory = $employee->titles->map(function ($title) {
            return [
                'title' => $title->title,
                'from_date' => $title->from_date,
                'to_date' => $title->to_date !== '9999-01-01' ? $title->to_date : 'obecnie'
            ];
        });

        $salaryHistory = $employee->salaries->map(function ($salary) {
            return [
                'salary' => $salary->salary,
                'from_date' => $salary->from_date,
                'to_date' => $salary->to_date !== '9999-01-01' ? $salary->to_date : 'obecnie'
            ];
        });

        $pdfData = [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'department' => $department,
            'title' => $title,
            'salary' => $salary,
            'totalSalary' => $totalSalary,
            'titleHistory' => $titleHistory,
            'salaryHistory' => $salaryHistory,
        ];

        $pdf = Pdf::loadView('pdf.employee', $pdfData);


        return $pdf->download("{$first_name}_{$last_name}.pdf"); // Pobieranie PDF
    }


    public function show($id)
    {
        $employee = Employee::with([
            'departments' => function ($query) {
                $query->orderBy('dept_emp.from_date');
            },
            'titles' => function ($query) {
                $query->orderBy('from_date');
            },
            'salaries' => function ($query) {
                $query->orderBy('from_date');
            }
        ])->findOrFail($id);

        $currentDepartment = $employee->departments->where('to_date', '9999-01-01')->first()
            ?? $employee->departments->last();

        $currentTitle = $employee->titles->where('to_date', '9999-01-01')->first()
            ?? $employee->titles->last();

        $currentSalary = $employee->salaries->where('to_date', '9999-01-01')->first()
            ?? $employee->salaries->last();

        $titleHistory = $employee->titles->map(function ($title) {
            return [
                'title' => $title->title,
                'from_date' => $title->from_date,
                'to_date' => $title->to_date !== '9999-01-01' ? $title->to_date : 'obecnie'
            ];
        });

        $salaryHistory = $employee->salaries->map(function ($salary) {
            return [
                'salary' => $salary->salary,
                'from_date' => $salary->from_date,
                'to_date' => $salary->to_date !== '9999-01-01' ? $salary->to_date : 'obecnie'
            ];
        });

        return view('employees.show', compact('employee', 'currentDepartment', 'currentTitle', 'currentSalary', 'titleHistory', 'salaryHistory'));
    }

    public function generatePdfs(Request $request)
    {
        $employeeIds = $request->input('employees', []);

        if (empty($employeeIds)) {
            return redirect()->route('employees.index')->with('error', 'Nie wybrano żadnego pracownika.');
        }

        $employees = Employee::whereIn('emp_no', $employeeIds)->get();

        $pdfPaths = [];

        foreach ($employees as $employee) {
            $department = $employee->departments->first()->dept_name ?? 'Brak departamentu';
            $title = $employee->titles->last()->title ?? 'Brak tytułu';
            $salary = $employee->salaries->first()->salary ?? 0;
            $totalSalary = $employee->salaries->sum('salary');

            $titleHistory = $employee->titles->map(function ($title) {
                return [
                    'title' => $title->title,
                    'from_date' => $title->from_date,
                    'to_date' => $title->to_date !== '9999-01-01' ? $title->to_date : 'obecnie'
                ];
            });

            $salaryHistory = $employee->salaries->map(function ($salary) {
                return [
                    'salary' => $salary->salary,
                    'from_date' => $salary->from_date,
                    'to_date' => $salary->to_date !== '9999-01-01' ? $salary->to_date : 'obecnie'
                ];
            });

            $pdfData = [
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'department' => $department,
                'title' => $title,
                'salary' => $salary,
                'totalSalary' => $totalSalary,
                'titleHistory' => $titleHistory,
                'salaryHistory' => $salaryHistory,
            ];

            $pdf = Pdf::loadView('pdf.employee', $pdfData);
            $fileName = "{$employee->first_name}_{$employee->last_name}_pdf.pdf";
            $pdfPath = storage_path("app/pdfs/{$fileName}");

            $pdf->save($pdfPath);

            $pdfPaths[] = $pdfPath;
        }

        return redirect()->route('employees.index');
    }







}


