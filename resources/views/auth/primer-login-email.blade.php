@extends('layouts.guest')

@section('content')
<div class="container py-5" style="max-width:480px">
    <h1 class="h3 mb-3">Actualiza tu email</h1>
    <p class="text-muted">Tu cuenta fue creada con un email temporal por la importación de inventario histórico ({{ auth()->user()->email }}). Indica tu email real para recibir notificaciones.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('primer-login.email.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email real</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required autofocus value="{{ old('email') }}">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">Actualizar email</button>
    </form>
</div>
@endsection
