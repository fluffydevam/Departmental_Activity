<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h2>Plant Inventory Report</h2>
    <p>Generated: {{ now()->format('Y-m-d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Plant Name</th>
                <th>Category</th>
                <th>Unit Price</th>
                <th>Stock Status</th>
                <th>Added By</th>
            </tr>
        </thead>
        <tbody>
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
        </tbody>
    </table>
</body>
</html>
