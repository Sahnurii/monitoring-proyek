@extends('layouts.app')

@php
    $role = optional($user->role)->role_name ?? 'operator';
@endphp

@section('content')
    @includeWhen($role === 'admin', 'dashboard.partials.admin-overview', ['user' => $user])
    @includeWhen($role === 'manager', 'dashboard.partials.manager-overview', ['user' => $user])
    @includeWhen($role === 'operator', 'dashboard.partials.operator-overview', ['user' => $user])
    @unless (in_array($role, ['admin', 'manager', 'operator'], true))
        @include('dashboard.partials.operator-overview', ['user' => $user])
    @endunless
@endsection
