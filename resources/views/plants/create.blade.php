@extends('layouts.admin')

@section('content')
    <h1>Add Plant</h1>

    <form method="POST" action="{{ route('plants.store') }}">
        @csrf
        @include('plants._form')
        <button type="submit">Save</button>
    </form>
@endsection
