{{-- resources/views/docs/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link tab-link{{ $selected === 'readme' ? ' active' : '' }}" href="{{ url('/docs/readme') }}">README</a>
        </li>
        <li class="nav-item">
            <a class="nav-link tab-link{{ $selected === 'api' ? ' active' : '' }}" href="{{ url('/docs/api') }}">API Docs</a>
        </li>
    </ul>
    <div class="prose">
        {!! $html !!}
    </div>
@endsection