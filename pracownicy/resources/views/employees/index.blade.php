@extends('layouts.app')

@section('content')
    <div class="container">
        <form method="GET" action="{{ route('employees.index') }}" class="mb-4">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <select name="status" class="form-control">
                        <option value="">Wszyscy</option>
                        <option value="current" {{ request('status') === 'current' ? 'selected' : '' }}>Obecni</option>
                        <option value="past" {{ request('status') === 'past' ? 'selected' : '' }}>Byli</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <select name="gender" class="form-control">
                        <option value="">Dowolna płeć</option>
                        <option value="M" {{ request('gender') === 'M' ? 'selected' : '' }}>Mężczyzna</option>
                        <option value="F" {{ request('gender') === 'F' ? 'selected' : '' }}>Kobieta</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <input type="number" name="salary_min" class="form-control" placeholder="Pensja od" value="{{ request('salary_min') }}">
                </div>
                <div class="col-md-2 mb-3">
                    <input type="number" name="salary_max" class="form-control" placeholder="Pensja do" value="{{ request('salary_max') }}">
                </div>
                <div class="col-md-2 mb-3">
                    <select name="department" class="form-control">
                        <option value="">Wybierz dział</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->dept_no }}" {{ request('department') === $department->dept_no ? 'selected' : '' }}>{{ $department->dept_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 text-center mt-3">
                    <button type="submit" class="btn btn-primary">Filtruj</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary ml-2">Resetuj filtry</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Imię</th>
                    <th>Nazwisko</th>
                    <th>Departamenty</th>
                    <th>Tytuł</th>
                    <th>Pensja</th>
                    <th>Akcje</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($employees as $employee)
                    <tr>
                        <td>{{ $employee->first_name }}</td>
                        <td>{{ $employee->last_name }}</td>
                        <td>
                            @foreach ($employee->departments as $department)
                                {{ $department->dept_name }}<br>
                            @endforeach
                        </td>
                        <td>{{ $employee->titles->last()->title ?? '' }}</td>
                        <td>{{ $employee->salaries->first()->salary ?? '' }}</td>
                        <td>
                            <form action="{{ route('generate.pdf', ['first_name' => $employee->first_name, 'last_name' => $employee->last_name]) }}" method="GET">
                                <button type="submit" class="btn btn-success">Wygeneruj PDF</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                @if ($employees->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link" aria-label="Previous">&laquo; Poprzednia</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $employees->previousPageUrl() . '&' . http_build_query(request()->except('page')) }}" aria-label="Previous">&laquo; Poprzednia</a>
                    </li>
                @endif

                @php
                    $currentPage = $employees->currentPage();
                    $lastPage = $employees->lastPage();
                    $pagesToShow = 5;
                    $pageRange = [];

                    if ($lastPage <= $pagesToShow) {
                        $pageRange = range(1, $lastPage);
                    } else {
                        $pageRange = range(max(1, $currentPage - 2), min($lastPage, $currentPage + 2));

                        if ($currentPage > 3) {
                            array_unshift($pageRange, '...');
                        }

                        if ($currentPage < $lastPage - 2) {
                            array_push($pageRange, '...');
                        }
                    }
                @endphp

                @foreach ($pageRange as $page)
                    @if ($page === '...')
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @else
                        <li class="page-item {{ $page == $employees->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $employees->url($page) . '&' . http_build_query(request()->except('page')) }}">
                                {{ $page }}
                            </a>
                        </li>
                    @endif
                @endforeach

                @if ($employees->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $employees->nextPageUrl() . '&' . http_build_query(request()->except('page')) }}" aria-label="Next">Następna &raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link" aria-label="Next">Następna &raquo;</span>
                    </li>
                @endif
            </ul>
        </nav>

    </div>
@endsection

@push('styles')
    <style>
        .table-responsive {
            max-width: 100%;
            overflow-x: auto;
        }

        .table th, .table td {
            text-align: center;
            padding: 10px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            font-size: 16px;
        }

        .pagination li {
            margin: 0 5px;
        }

        .pagination li a {
            display: inline-block;
            padding: 6px 12px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .pagination li a:hover {
            background-color: #0056b3;
        }

        .pagination .active a {
            background-color: #004085;
        }

        .pagination .disabled a {
            background-color: #e9ecef;
            color: #6c757d;
            pointer-events: none;
        }

        .pagination-lg .page-link {
            padding: 10px 20px;
            font-size: 18px;
        }

        .pagination-sm .page-link {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-primary {
            width: 100%;
        }

        .btn-secondary {
            width: auto;
        }

        @media (max-width: 768px) {
            .table th, .table td {
                padding: 8px;
            }

            .pagination {
                flex-direction: column;
                align-items: center;
            }

            .pagination li {
                margin: 5px 0;
            }
        }
    </style>
@endpush
