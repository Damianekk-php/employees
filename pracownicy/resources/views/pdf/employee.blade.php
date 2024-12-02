<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Pracownika</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin-bottom: 0;
        }

        .details {
            margin-top: 20px;
        }

        .details table {
            width: 100%;
            border-collapse: collapse;
        }

        .details table th, .details table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .details table th {
            background-color: #f4f4f4;
        }

        .total-salary {
            margin-top: 20px;
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Raport dla Pracownika</h1>
    </div>

    <div class="details">
        <table>
            <tr>
                <th>Imie i Nazwisko</th>
                <td>{{ $first_name }} {{ $last_name }}</td>
            </tr>
            <tr>
                <th>Departament</th>
                <td>{{ $department }}</td>
            </tr>
            <tr>
                <th>Tytul</th>
                <td>{{ $title }}</td>
            </tr>
            <tr>
                <th>Obecna pensja</th>
                <td>{{ $salary }}</td>
            </tr>
        </table>

        <div class="total-salary">
            <p><strong>Calkowita suma wyplat: {{ $totalSalary }}</strong></p>
        </div>
    </div>
</div>

</body>
</html>
