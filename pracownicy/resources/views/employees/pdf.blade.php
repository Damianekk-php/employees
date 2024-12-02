<!DOCTYPE html>
<html>
<head>
    <title>Export Employees</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
<h1>Lista pracowników</h1>
<table>
    <thead>
    <tr>
        <th>Imię</th>
        <th>Nazwisko</th>
        <th>Departament</th>
        <th>Tytuł</th>
        <th>Pensja</th>
    </tr>
    </thead>
    <tbody>
    @foreach($employees as $employee)
        <tr>
            <td>{{ $employee->first_name }}</td>
            <td>{{ $employee->last_name }}</td>
            <td>{{ $employee->department->dept_name }}</td>
            <td>{{ $employee->titles->last()->title ?? '' }}</td>
            <td>{{ $employee->salaries->first()->salary ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
