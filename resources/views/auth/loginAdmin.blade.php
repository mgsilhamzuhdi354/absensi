@extends('templates.auth')
@section('page-title', 'Masuk Admin')
@section('content')
    <div class="auth-layout">
        @include('auth.partials.intro')
        @include('auth.partials.login-form', ['adminLogin' => true])
    </div>
@endsection
