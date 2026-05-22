@props(['estado'])

<span class="badge bg-{{ $estado->color() }}">{{ $estado->label() }}</span>
