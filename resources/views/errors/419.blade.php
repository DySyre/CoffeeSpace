@extends('errors.layout')

@section('code', '419')
@section('title', 'Page expired')
@section('message', 'Your session has expired. Sign in again, then return to what you were doing.')
@section('supporting', 'If you were completing a form, you may need to enter its details again.')

@section('actions')
    <a href="{{ url('/login') }}" class="button">Sign in again</a>
    <a href="{{ url('/') }}" class="button button-secondary">Back to Discover</a>
@endsection
