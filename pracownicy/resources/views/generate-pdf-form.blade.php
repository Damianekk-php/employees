@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Wygeneruj PDF dla pracownika</h2>

        <form method="GET" action="{{ route('generate.pdf') }}" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="first_name" class="form-control" placeholder="Imię" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="last_name" class="form-control" placeholder="Nazwisko" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Generuj PDF</button>
                </div>
            </div>
        </form>
    </div>
@endsection
