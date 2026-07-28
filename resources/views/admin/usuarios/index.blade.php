@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2>
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->name }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->phone ?? '—' }}</td>
                            <td>
                                @foreach($usuario->roles as $role)
                                    @if (in_array($role->name, $rolesRetirados, true))
                                        {{-- Rol retirado: conserva sus permisos, pero conviene reasignarlo --}}
                                        <span class="badge bg-warning text-dark"
                                              title="Rol retirado de circulación. El usuario conserva sus permisos actuales; reasígnalo a un rol vigente.">
                                            <i class="bi bi-exclamation-triangle"></i> {{ ucfirst($role->name) }}
                                        </span>
                                    @else
                                        <span class="badge bg-info text-dark">{{ ucfirst($role->name) }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                @unless($usuario->hasRole('administrador'))
                                    <form action="{{ route('admin.usuarios.destroy', $usuario) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Está seguro de eliminar este usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $usuarios->links() }}
        </div>
    </div>
</div>
@endsection
