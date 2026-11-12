@extends('layouts.admin')

@section('content')
    <h1>Plants</h1>
    <a href="{{ route('plants.create') }}">+ Add Plant</a>

    <table border="1" cellpadding="6">
        <tr>
            <th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Added By</th><th>Actions</th>
        </tr>
        @forelse ($plants as $plant)
            <tr>
                <td>{{ $plant->plant_name }}</td>
                <td>{{ $plant->category }}</td>
                <td>{{ number_format($plant->unit_price, 2) }}</td>
                <td>{{ $plant->stock_status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}</td>
                <td>{{ $plant->user->name }}</td>
                <td>
                    <a href="{{ route('plants.show', $plant) }}">View</a>
                    <a href="{{ route('plants.edit', $plant) }}">Edit</a>
                    <form method="POST" action="{{ route('plants.destroy', $plant) }}" style="display:inline"
                          onsubmit="return confirm('Delete this plant?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No plants yet.</td></tr>
        @endforelse
    </table>

    {{ $plants->links() }}
@endsection
