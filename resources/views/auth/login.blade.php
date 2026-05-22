<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - Cargo Express</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .login-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .login-header i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .login-header p {
            margin: 0.25rem 0 0;
            opacity: 0.85;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-floating > .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            height: 56px;
        }
        .form-floating > .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .btn-login {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="login-card card">
        <div class="login-header">
            <i class="bi bi-box-seam-fill d-block"></i>
            <h3>CARGO EXPRESS</h3>
            <p>Sistema de Trazabilidad de Carga</p>
        </div>
        <div class="login-body">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    @foreach ($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-floating mb-3">
                    <input type="email"
                           class="form-control"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="correo@ejemplo.com"
                           required
                           autofocus>
                    <label for="email"><i class="bi bi-envelope me-1"></i> Correo electrónico</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Contraseña"
                           required>
                    <label for="password"><i class="bi bi-lock me-1"></i> Contraseña</label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Recordarme</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-decoration-none">
                            ¿Olvidó su contraseña?
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                </button>
            </form>
        </div>
        <div class="card-footer text-center py-3 bg-light" style="border-radius: 0 0 16px 16px;">
            <small class="text-muted">&copy; {{ date('Y') }} Cargo Express</small>
        </div>
    </div>
</body>
</html>
