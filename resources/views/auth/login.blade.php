@extends('templates.auth')
@section('page-title', 'Masuk')
@section('content')
    <div class="auth-layout">
        @include('auth.partials.intro')
        @include('auth.partials.login-form')
    </div>
@endsection
