@extends('errors.layout')

@section('code', '500')
@section('title', 'Server error')
@section('message', 'Something went wrong on our side. We could not finish your request.')
@section('supporting', 'Please try again in a moment. If this keeps happening, come back a little later.')

@section('actions')
    <a href="{{ url('/') }}" class="button">Back to Discover</a>
    <a href="{{ url('/login') }}" class="button button-secondary">Go to sign in</a>
@endsection
