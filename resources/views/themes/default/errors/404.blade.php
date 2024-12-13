@extends('themes::partials.layout')

@section('title', 'Page Not Found')

@section('content')
    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for could not be found.</p>
    <a href="{{ url('/') }}">Go Back Home</a>
@endsection
