<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Pracowników</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Raport Pracownikow</h1>
</div>

<table>
    <thead>
    <tr>
        <th>Imie i Nazwisko</th>
        <th>Departament</th>
        <th>Tytul</th>
        <th>Aktualna Pensja</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($employees as $employee)
        <tr>
            <td>{{ $employee['first_name'] }} {{ $employee['last_name'] }}</td>
            <td>{{ $employee['department'] }}</td>
            <td>{{ $employee['title'] }}</td>
            <td>{{ $employee['salary'] }} PLN</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
