@extends('layouts.admin')

@section('content')
    <h1>Edit Plant</h1>

    <form method="POST" action="{{ route('plants.update', $plant) }}">
        @csrf
        @method('PUT')
        @include('plants._form')
        <button type="submit">Update</button>
    </form>
@endsection
