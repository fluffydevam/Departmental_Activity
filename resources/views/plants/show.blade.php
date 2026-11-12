@extends('layouts.admin')

@section('content')
    <h1>{{ $plant->plant_name }}</h1>

    <p><strong>Category:</strong> {{ $plant->category }}</p>
    <p><strong>Price:</strong> {{ number_format($plant->unit_price, 2) }}</p>
    <p><strong>Status:</strong> {{ $plant->stock_status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}</p>
    <p><strong>Description:</strong> {{ $plant->description }}</p>
    <p><strong>Added By:</strong> {{ $plant->user->name }}</p>

    <a href="{{ route('plants.edit', $plant) }}">Edit</a> |
    <a href="{{ route('plants.index') }}">Back to list</a>
@endsection
