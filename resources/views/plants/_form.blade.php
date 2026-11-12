<p>
    <label>Plant Name</label><br>
    <input type="text" name="plant_name" value="{{ old('plant_name', $plant->plant_name ?? '') }}">
    @error('plant_name') <span style="color:red">{{ $message }}</span> @enderror
</p>

<p>
    <label>Category</label><br>
    <select name="category">
        @foreach (['ornamental', 'flowering', 'succulent', 'herb'] as $category)
            <option value="{{ $category }}" @selected(old('category', $plant->category ?? '') === $category)>
                {{ ucfirst($category) }}
            </option>
        @endforeach
    </select>
    @error('category') <span style="color:red">{{ $message }}</span> @enderror
</p>

<p>
    <label>Unit Price</label><br>
    <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', $plant->unit_price ?? '') }}">
    @error('unit_price') <span style="color:red">{{ $message }}</span> @enderror
</p>

<p>
    <label>Description</label><br>
    <textarea name="description">{{ old('description', $plant->description ?? '') }}</textarea>
    @error('description') <span style="color:red">{{ $message }}</span> @enderror
</p>

<p>
    <label>Stock Status</label><br>
    <select name="stock_status">
        <option value="in_stock" @selected(old('stock_status', $plant->stock_status ?? 'in_stock') === 'in_stock')>In Stock</option>
        <option value="out_of_stock" @selected(old('stock_status', $plant->stock_status ?? '') === 'out_of_stock')>Out of Stock</option>
    </select>
    @error('stock_status') <span style="color:red">{{ $message }}</span> @enderror
</p>
