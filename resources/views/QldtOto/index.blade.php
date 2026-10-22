@extends('PMGPLX.layouts.quan-ly')

@section('title', 'QLĐT Ô TÔ')

@section('content')
    @foreach ($tabs as $tab)
        <div class="card card-panel">
            <div class="card-header">{{ $tab['title'] }} — các bước của một khoá</div>
            <div class="card-body">
                @include('QldtOto.partials.cay-menu', ['items' => $tab['children'] ?? [], 'cap' => 0])
            </div>
        </div>
    @endforeach
@endsection
