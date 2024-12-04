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

        <div class="mb-4 text-center">
            <strong>Liczba wyświetlanych pracowników: {{ $totalEmployees }}</strong>
        </div>

        <form method="POST" action="{{ route('employees.generate_pdfs') }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all"></th>
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
                            <td><input type="checkbox" name="employees[]" value="{{ $employee->emp_no }}"></td>
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
                                <a href="{{ route('employees.show', ['id' => $employee->emp_no]) }}" class="btn btn-info">Szczegóły</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-3">
                <button type="submit" class="btn btn-success">Generuj PDF-y dla zaznaczonych</button>
            </div>
        </form>

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

                @foreach ($employees->links() as $page)
                    <li class="page-item {{ $page->active ? 'active' : '' }}">
                        <a class="page-link" href="{{ $page->url }}">{{ $page->label }}</a>
                    </li>
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
