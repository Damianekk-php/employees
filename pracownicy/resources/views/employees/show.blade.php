@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Szczegóły pracownika</h2>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">{{ $employee->first_name }} {{ $employee->last_name }}</h4>
                <p><strong>Obecny departament:</strong> {{ $currentDepartment->dept_name ?? 'Brak' }}</p>
                <p><strong>Obecny tytuł:</strong> {{ $currentTitle->title ?? 'Brak' }}</p>
                <p><strong>Obecna pensja:</strong> {{ $currentSalary->salary ?? 'Brak' }} zł</p>
            </div>
        </div>

        <h3>Historia tytułów i pensji</h3>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Tytuł</th>
                <th>Pensja</th>
                <th>Od</th>
                <th>Do</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($history as $record)
                <tr>
                    <td>{{ $record['title'] }}</td>
                    <td>{{ $record['salary'] }} zł</td>
                    <td>{{ $record['from_date'] }}</td>
                    <td>{{ $record['to_date'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <a href="{{ route('generate.pdf', ['first_name' => $employee->first_name, 'last_name' => $employee->last_name]) }}" class="btn btn-success mt-3">Pobierz PDF</a>
        <a href="{{ route('employees.index') }}" class="btn btn-primary mt-3">Powrót do listy</a>
    </div>
@endsection
