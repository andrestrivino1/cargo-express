@extends('layouts.guest')

@section('content')
<div class="container py-5" style="max-width:480px">
    <h1 class="h3 mb-3">Cambia tu contraseña</h1>
    <p class="text-muted">Tu cuenta fue creada con una contraseña temporal por la importación de inventario histórico. Establece tu nueva contraseña para continuar.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('primer-login.password.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Contraseña temporal actual</label>
            <input type="password" name="password_actual" class="form-control @error('password_actual') is-invalid @enderror" required autofocus>
            @error('password_actual') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-muted">Mínimo 8 caracteres, mayúsculas, minúsculas, número y símbolo.</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirma la nueva contraseña</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Actualizar contraseña</button>
    </form>
</div>
@endsection
