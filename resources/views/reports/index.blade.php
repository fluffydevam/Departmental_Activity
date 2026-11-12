@extends('layouts.admin')

@section('content')
    <h1>Plant Report</h1>

    <form method="GET" action="{{ route('reports.index') }}">
        <select name="category">
            <option value="">All Categories</option>
            @foreach (['ornamental', 'flowering', 'succulent', 'herb'] as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>
                    {{ ucfirst($category) }}
                </option>
            @endforeach
        </select>

        <select name="stock_status">
            <option value="">All Statuses</option>
            <option value="in_stock" @selected(request('stock_status') === 'in_stock')>In Stock</option>
            <option value="out_of_stock" @selected(request('stock_status') === 'out_of_stock')>Out of Stock</option>
        </select>

        <button type="submit">Filter</button>
        <a href="{{ route('reports.index') }}">Reset</a>
        <a href="{{ route('reports.pdf', request()->query()) }}">Export PDF</a>
    </form>

    <p>
        Quick:
        <a href="{{ route('reports.index', ['stock_status' => 'in_stock']) }}">In Stock</a> |
        <a href="{{ route('reports.index', ['stock_status' => 'out_of_stock']) }}">Out of Stock</a>
    </p>

    <table border="1" cellpadding="6">
        <tr>
            <th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Added By</th>
        </tr>
        @forelse ($plants as $plant)
            <tr>
                <td>{{ $plant->plant_name }}</td>
                <td>{{ $plant->category }}</td>
                <td>{{ number_format($plant->unit_price, 2) }}</td>
                <td>{{ $plant->stock_status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}</td>
                <td>{{ $plant->user->name }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No plants match these filters.</td></tr>
        @endforelse
    </table>
@endsection
