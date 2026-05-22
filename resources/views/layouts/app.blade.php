<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Cargo Express') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --sidebar-width: 260px;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .wrapper {
            display: flex;
            flex: 1;
        }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: calc(100vh - 56px);
            background-color: #212529;
            transition: margin-left 0.3s ease;
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        #sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            border-radius: 0;
            transition: all 0.2s;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        #sidebar .nav-link .bi {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        #sidebar .sidebar-heading {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1rem 0.4rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 1.5rem;
            margin-top: 56px;
        }

        footer {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }

        /* Sidebar collapsed */
        #sidebar.collapsed {
            margin-left: calc(var(--sidebar-width) * -1);
        }

        #sidebar.collapsed ~ .main-content,
        #sidebar.collapsed ~ footer {
            margin-left: 0;
        }

        /* Mobile */
        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: calc(var(--sidebar-width) * -1);
            }

            #sidebar.show {
                margin-left: 0;
            }

            .main-content,
            footer {
                margin-left: 0 !important;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm" style="z-index: 1100;">
        <div class="container-fluid">
            <!-- Sidebar Toggle -->
            @auth
            <button class="btn btn-dark me-2" id="sidebarToggle" type="button">
                <i class="bi bi-list fs-5"></i>
            </button>
            @endauth

            <!-- Brand -->
            <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                <i class="bi bi-box-seam-fill me-1"></i> Cargo Express
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person me-1"></i> Mi Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-left me-1"></i> Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Iniciar Sesión</a>
                    </li>
                    @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Registrarse</a>
                    </li>
                    @endif
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    @auth
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="pt-3">
            <!-- Dashboard (todos los usuarios) -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
            </ul>

            {{-- Secciones exclusivas para Cliente --}}
            @role('cliente')
            <div class="sidebar-heading">Mi Cuenta</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}" href="{{ route('inventario.index') }}">
                        <i class="bi bi-archive"></i> Mi Inventario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('entregas.create') ? 'active' : '' }}" href="{{ route('entregas.create') }}">
                        <i class="bi bi-plus-circle"></i> Orden de Cargue
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('entregas.index') || request()->routeIs('entregas.show') ? 'active' : '' }}" href="{{ route('entregas.index') }}">
                        <i class="bi bi-truck"></i> Mis Entregas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trazabilidad.*') ? 'active' : '' }}" href="{{ route('trazabilidad.index') }}">
                        <i class="bi bi-search"></i> Trazabilidad
                    </a>
                </li>
            </ul>
            @endrole

            {{-- Secciones para personal operativo (NO cliente) --}}
            @unlessrole('cliente')
            <!-- Operaciones -->
            <div class="sidebar-heading">Operaciones</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}" href="{{ route('solicitudes.index') }}">
                        <i class="bi bi-file-earmark-text"></i> Solicitudes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('gate-in.*') ? 'active' : '' }}" href="{{ route('gate-in.index') }}">
                        <i class="bi bi-box-arrow-in-right"></i> Ingreso
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('vaciado.*') ? 'active' : '' }}" href="{{ route('vaciado.index') }}">
                        <i class="bi bi-box-seam"></i> Vaciado
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}" href="{{ route('inventario.index') }}">
                        <i class="bi bi-archive"></i> Almacenamiento
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('gate-out.*') ? 'active' : '' }}" href="{{ route('gate-out.index') }}">
                        <i class="bi bi-box-arrow-right"></i> Salida
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                        <i class="bi bi-box"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('transferencias.*') ? 'active' : '' }}" href="{{ route('transferencias.index') }}">
                        <i class="bi bi-arrow-left-right"></i> Transferencias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('entregas.*') ? 'active' : '' }}" href="{{ route('entregas.index') }}">
                        <i class="bi bi-truck"></i> Entregas
                    </a>
                </li>
            </ul>

            <!-- Consultas -->
            <div class="sidebar-heading">Consultas</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('trazabilidad.*') ? 'active' : '' }}" href="{{ route('trazabilidad.index') }}">
                        <i class="bi bi-search"></i> Trazabilidad
                    </a>
                </li>
            </ul>

            <!-- Reportes (supervisor, gerente, administrador) -->
            @role('supervisor|gerente|administrador')
            <div class="sidebar-heading">Reportes</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                        <i class="bi bi-bar-chart"></i> Reportes
                    </a>
                </li>
            </ul>
            @endrole

            <!-- Administración (solo administrador) -->
            @role('administrador')
            <div class="sidebar-heading">Administración</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">
                        <i class="bi bi-people"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.ubicaciones.*') ? 'active' : '' }}" href="{{ route('admin.ubicaciones.index') }}">
                        <i class="bi bi-geo-alt"></i> Ubicaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('importaciones.*') ? 'active' : '' }}" href="{{ route('importaciones.index') }}">
                        <i class="bi bi-cloud-upload"></i> Importar inventario
                    </a>
                </li>
            </ul>
            @endrole

            @hasanyrole('administrador|coordinador|despachador|portero|operador|supervisor')
            <div class="sidebar-heading">Importación histórica</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    @php $totalPendientes = \App\Models\ImportPendingRecord::whereNull('completado_at')->count(); @endphp
                    <a class="nav-link {{ request()->routeIs('pendientes.*') ? 'active' : '' }}" href="{{ route('pendientes.index') }}">
                        <i class="bi bi-list-check"></i> Pendientes por completar
                        @if ($totalPendientes > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $totalPendientes }}</span>
                        @endif
                    </a>
                </li>
            </ul>
            @endhasanyrole
            @endunlessrole
        </div>
    </nav>
    @endauth

    <!-- Main Content -->
    <div class="main-content @guest mt-5 pt-3 @endguest">
        <!-- Flash Messages -->
        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        @endif

        @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        @endif

        @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-1"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 border-top @guest ms-0 @endguest">
        <small>&copy; {{ date('Y') }} Cargo Express. Todos los derechos reservados.</small>
    </footer>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            if (toggle) {
                toggle.addEventListener('click', function () {
                    if (window.innerWidth < 992) {
                        sidebar.classList.toggle('show');
                        overlay.classList.toggle('show');
                    } else {
                        sidebar.classList.toggle('collapsed');
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>