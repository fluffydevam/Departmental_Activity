# Plant Nursery Inventory Management System (PNIMS)

Step-by-step build notes for the Departmental Practice Activity (SET D).

Laravel 12 MVC app: Breeze authentication, plant CRUD, filtered reports, PDF export.
MySQL database `PNIMS`.

Every code block below is the code that is in the project now (final working version).

---

## 1. Create the project and install Breeze

```bash
composer create-project laravel/laravel PNIMS
cd PNIMS
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
```

`.env` database settings:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=PNIMS
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in phpMyAdmin (`CREATE DATABASE PNIMS;`), then:

```bash
php artisan migrate
```

Breeze gives you `users`, password reset and session tables plus the login, register,
logout and profile pages. No custom auth code was written.

> **Known gap:** the activity asks for login by email **or** username. What is built is
> Breeze's standard email + password login. The `users` table has no `username` column.

---

## 2. Plants table

```bash
php artisan make:migration create_plants_table
```

```php
Schema::create('plants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('plant_name');
    $table->string('category')->index();
    $table->decimal('unit_price', 10, 2);
    $table->text('description');
    $table->string('stock_status')->default('in_stock')->index();
    $table->timestamps();
});
```

```bash
php artisan migrate
```

Design notes:

- `foreignId('user_id')->constrained()` creates the FK to `users.id`.
  `cascadeOnDelete()` deletes a user's plants when the user is deleted.
- `decimal(10,2)` not `float` — money must be exact.
- `description` is `text` and **not nullable**, matching the required validation.
- `category` / `stock_status` are indexed because reports filter on them.

Check the generated table:

```sql
SHOW CREATE TABLE plants;
```

Expected FK:

```sql
CONSTRAINT `plants_user_id_foreign` FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`) ON DELETE CASCADE
```

---

## 3. Models and relationships

`app/Models/Plant.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
        'plant_name',
        'category',
        'unit_price',
        'description',
        'stock_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

`app/Models/User.php` — add at the end of the class:

```php
public function plants()
{
    return $this->hasMany(Plant::class);
}
```

`user_id` is deliberately **not** fillable. It is set through the relationship in the
controller, so it can never be supplied by a form.

---

## 4. Routes

`routes/web.php`

```php
use App\Http\Controllers\PlantController;
use App\Http\Controllers\ReportController;

Route::middleware('auth')->group(function () {
    // profile routes from Breeze ...

    Route::resource('plants', PlantController::class);

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
});
```

`Route::resource` creates the 7 CRUD routes. The whole group is behind `auth`, so
guests are redirected to `/login` (protects the admin pages).

```bash
php artisan route:list --name=plants
php artisan route:list --name=reports
```

---

## 5. Validation

```bash
php artisan make:request PlantRequest
```

`app/Http/Requests/PlantRequest.php`

```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [
        'plant_name'   => ['required', 'string', 'max:255'],
        'category'     => ['required', 'in:ornamental,flowering,succulent,herb'],
        'unit_price'   => ['required', 'numeric', 'min:0'],
        'description'  => ['required', 'string'],
        'stock_status' => ['required', 'in:in_stock,out_of_stock'],
    ];
}
```

- `authorize()` must return `true`, otherwise every request gets a 403.
- Type-hinting `PlantRequest` in the controller runs these rules automatically.
- `numeric|min:0` = the price must be numeric and nonnegative **on the server**;
  the HTML `min="0"` is only a browser hint.
- `$request->validated()` returns only these fields — no mass assignment risk.

---

## 6. Plant controller

```bash
php artisan make:controller PlantController --resource --model=Plant
```

`app/Http/Controllers/PlantController.php`

```php
namespace App\Http\Controllers;

use App\Http\Requests\PlantRequest;
use App\Models\Plant;

class PlantController extends Controller
{
    public function index()
    {
        $plants = Plant::with('user')->latest()->paginate(10);

        return view('plants.index', compact('plants'));
    }

    public function create()
    {
        return view('plants.create');
    }

    // The plant is owned by the logged-in user. Creating through the
    // relationship sets user_id automatically, so it cannot come from the form.
    public function store(PlantRequest $request)
    {
        auth()->user()->plants()->create($request->validated());

        return redirect()->route('plants.index')->with('success', 'Plant added successfully.');
    }

    public function show(Plant $plant)
    {
        return view('plants.show', compact('plant'));
    }

    public function edit(Plant $plant)
    {
        return view('plants.edit', compact('plant'));
    }

    public function update(PlantRequest $request, Plant $plant)
    {
        $plant->update($request->validated());

        return redirect()->route('plants.index')->with('success', 'Plant updated successfully.');
    }

    public function destroy(Plant $plant)
    {
        $plant->delete();

        return redirect()->route('plants.index')->with('success', 'Plant deleted successfully.');
    }
}
```

Key points:

- `Plant $plant` is route-model binding; Laravel looks up the record from the URL and
  returns 404 if it does not exist.
- `update()` **must** take `PlantRequest`, because `validated()` only exists on a
  FormRequest. Using the plain `Illuminate\Http\Request` throws.
- `with('user')` eager loads the owner in `index` and avoids one query per row.

---

## 7. Admin layout

`resources/views/layouts/admin.blade.php`

Breeze's layout is component-based (`{{ $slot }}`), so a standard `@extends` layout was
added for the plant and report pages.

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Plant Nursery</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav>
        <a href="{{ route('plants.index') }}">Plants</a> |
        <a href="{{ route('dashboard') }}">Dashboard</a> |
        <a href="{{ route('reports.index') }}">Reports</a> |
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </nav>

    <hr>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    @yield('content')
</body>
</html>
```

- `@yield('content')` is filled by each page's `@section('content')`.
- The success flash message from the controller is displayed here, once.
- Logout is a POST form with `@csrf` (Breeze's logout route requires it).

---

## 8. Plant views

### `resources/views/plants/_form.blade.php`

Shared partial, used by both create and edit.

```blade
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
    <input type="number" step="0.01" min="0" name="unit_price"
           value="{{ old('unit_price', $plant->unit_price ?? '') }}">
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
```

`$plant->... ?? ''` is what lets the same partial work on create (no `$plant`) and edit.
`old()` keeps the submitted values after a validation failure.

### `create.blade.php`

```blade
@extends('layouts.admin')

@section('content')
    <h1>Add Plant</h1>

    <form method="POST" action="{{ route('plants.store') }}">
        @csrf
        @include('plants._form')
        <button type="submit">Save</button>
    </form>
@endsection
```

### `edit.blade.php`

```blade
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
```

HTML forms only send GET/POST, so `@method('PUT')` spoofs the PUT the resource route
expects.

### `index.blade.php`

```blade
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
```

Delete is a POST form with `@method('DELETE')`, not a link — destructive actions should
never be GET requests.

### `show.blade.php`

```blade
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
```

---

## 9. Report page with filters

```bash
php artisan make:controller ReportController
```

`app/Http/Controllers/ReportController.php`

```php
namespace App\Http\Controllers;

use App\Models\Plant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $plants = $this->filteredPlants($request);

        return view('reports.index', compact('plants'));
    }

    // One filter method shared by the page and the PDF export,
    // so the export always matches what is on screen.
    public function filteredPlants(Request $request)
    {
        $query = Plant::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        return $query->get();
    }
}
```

`resources/views/reports/index.blade.php`

```blade
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
```

The filter form is `GET`, so the filters appear in the URL
(`?category=flowering&stock_status=in_stock`). `request('category')` keeps the selected
option after submitting. `request()->query()` on the export link forwards those same
filters to the PDF route.

Reports covered:

| Report | How |
|---|---|
| In-stock plants | quick link `?stock_status=in_stock` |
| Out-of-stock plants | quick link `?stock_status=out_of_stock` |
| Plants by category | category dropdown |
| Plant price list | unit price column, filter/sort as needed |

---

## 10. PDF export

```bash
composer require barryvdh/laravel-dompdf
```

Controller method:

```php
public function pdf(Request $request)
{
    $plants = $this->filteredPlants($request);

    return Pdf::loadView('reports.pdf', compact('plants'))->download('plant-report.pdf');
}
```

`resources/views/reports/pdf.blade.php` — plain HTML, no layout, no `@vite`:

```blade
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
```

dompdf does not load Vite assets, so extending the admin layout would produce an
unstyled or broken PDF. Use a standalone HTML template.

> **Known gap:** Excel export is not implemented (PDF only). The activity requires both.

---

## 11. Problems hit during the build

These are useful to know for the exam because they are common.

**1. `syntax error, unexpected token "public"` / `Unclosed '{'`**
An extra or missing `}` in `PlantController`. PHP closes the class early, so every method
after the mistake is "unexpected". Check with:

```bash
php -l app/Http/Controllers/PlantController.php
```

**2. `Call to undefined method Illuminate\Http\Request::validated()`**
`validated()` exists only on `FormRequest`. Fix: type-hint `PlantRequest` in `update()`
as well as `store()`.

**3. Composer blocked by antivirus (McAfee)**
"Failed to open stream: Permission denied" when writing `vendor/composer/tmp-*.zip`.
Fix: exclude `C:\PNIMS` (and the temp folder) from real-time scanning, close
`php artisan serve` / VS Code, and retry. A reboot clears file locks.

**4. `maatwebsite/excel` install failures**
- version 4.0.6 requires PHP ^8.3, this machine has 8.2 → pin `3.1.70`;
- version 3.1.55 is blocked by a security advisory;
- 3.1.x needs `ext-gd`, which was disabled in `C:\xampp\php\php.ini` → remove the `;`
  from `;extension=gd`, verify with `php -m | findstr gd`.
The package was eventually dropped, so the project uses dompdf only.

**5. VS Code shows red under `auth()->user()`**
"Undefined method 'user'" is static-analysis noise: `auth()` is typed as
`AuthFactory|Guard` and only `Guard` has `user()`. It works at runtime. To silence it:

```php
/** @var \App\Models\User $user */
$user = auth()->user();
$user->plants()->create($request->validated());
```

**6. `php artisan view:clear`** is handy after editing Blade files if changes seem not to
apply.

---

## 12. Test checklist

1. Logged out, `/plants` redirects to `/login`.
2. Register/login works; logout ends the session.
3. Empty plant form → validation errors, old input kept.
4. `unit_price` rejects `abc` and `-5`.
5. Create, view, edit, delete a plant; success messages appear.
6. Reports filter by category and stock status; URL carries both filters.
7. PDF export contains only the filtered rows.
8. Delete a user in the DB → their plants are removed (FK cascade).

---

## 13. Command cheat sheet

```bash
php artisan make:migration create_plants_table
php artisan migrate
php artisan make:controller PlantController --resource --model=Plant
php artisan make:request PlantRequest
php artisan make:controller ReportController
php artisan route:list --name=plants
php artisan route:list --name=reports
php artisan view:clear
php artisan serve
npm run dev
composer require barryvdh/laravel-dompdf
```

---

## Requirement status

| Requirement | Status |
|---|---|
| Login page + logout | Done (Breeze) |
| Login by email **or** username | **Missing** — email only |
| Protect admin pages | Done (`auth` middleware) |
| Plant CRUD (list/add/view/edit/delete) | Done |
| Required fields + validation | Done (`PlantRequest`) |
| Numeric, nonnegative price | Done (`numeric`, `min:0`) |
| User has many Plants / Plant belongs to User | Done |
| `user_id` FK on plants | Done (cascade delete) |
| Resource routes + named routes | Done |
| Reusable layout (`@extends` / `@section`) | Done (`layouts/admin.blade.php`) |
| Reports: in/out of stock, by category, price list | Done (filters) |
| PDF export matching filters | Done |
| Excel export matching filters | **Missing** |
#   D e p a r t m e n t a l _ A c t i v i t y  
 