# Plant Nursery Inventory Management System (PNIMS)

A Laravel 12 MVC application for managing plant inventory records. Built for the
Departmental Practice Activity (SET D). Sales and customer transactions are out of scope.

## Features

- Session authentication (Laravel Breeze): register, login, logout
- Authenticated-only admin area (`auth` middleware)
- Plant CRUD: list, add, view, edit, delete
- Server-side validation (required fields, numeric nonnegative price, fixed categories)
- Reports filtered by category and stock status
- PDF export that respects the active filters
- Every plant is linked to the user who created it

## Tech stack

- PHP 8.2, Laravel 12
- MySQL (database: `PNIMS`)
- Blade templates with `@extends` / `@section`
- Laravel Breeze for authentication
- barryvdh/laravel-dompdf for PDF export

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=PNIMS
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate
npm install
npm run dev        # or: npm run build
php artisan serve
```

Open `/register` to create the first admin account, then log in.

## Database schema

`plants`

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned | primary key |
| user_id | bigint unsigned | FK → users.id, ON DELETE CASCADE |
| plant_name | varchar(255) | required |
| category | varchar(255) | indexed; ornamental, flowering, succulent, herb |
| unit_price | decimal(10,2) | numeric, nonnegative |
| description | text | required |
| stock_status | varchar(255) | indexed; in_stock / out_of_stock, default in_stock |
| created_at / updated_at | timestamp | |

`decimal(10,2)` is used instead of `float` so prices stay exact.

## Models and relationships

- `User` **hasMany** `Plant`
- `Plant` **belongsTo** `User`

`user_id` is intentionally **not** in `$fillable`. Ownership is assigned through the
relationship so it can never come from form input:

```php
auth()->user()->plants()->create($request->validated());
```

## Routes

```php
Route::middleware('auth')->group(function () {
    Route::resource('plants', PlantController::class);
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
});
```

`Route::resource` provides the 7 CRUD routes (`plants.index`, `.create`, `.store`,
`.show`, `.edit`, `.update`, `.destroy`). The whole group requires login.

## Project structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── PlantController.php      # resource controller (7 methods)
│   │   └── ReportController.php     # index + pdf, shared filteredPlants()
│   └── Requests/
│       └── PlantRequest.php         # validation rules
├── Models/
│   ├── Plant.php                    # belongsTo User, fillable fields
│   └── User.php                     # hasMany Plant
database/migrations/
└── ..._create_plants_table.php      # schema + foreign key
resources/views/
├── layouts/admin.blade.php          # @yield('content'), nav, flash, logout
├── plants/                          # index, create, edit, show, _form
└── reports/                         # index (filters), pdf (standalone HTML)
```

## Validation

`app/Http/Requests/PlantRequest.php`

```php
'plant_name'   => ['required', 'string', 'max:255'],
'category'     => ['required', 'in:ornamental,flowering,succulent,herb'],
'unit_price'   => ['required', 'numeric', 'min:0'],
'description'  => ['required', 'string'],
'stock_status' => ['required', 'in:in_stock,out_of_stock'],
```

- Type-hinting `PlantRequest` runs validation before the controller method.
- `$request->validated()` returns only the listed fields, preventing mass assignment.
- `numeric|min:0` enforces a nonnegative price on the server; HTML `min="0"` is only a hint.
- Failed validation redirects back with `$errors` and the old input.

## Reports and exports

One filter method is shared by the report page and the export:

```php
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
```

The export link forwards the current query string, so the PDF always matches the
filters visible on screen:

```blade
<a href="{{ route('reports.pdf', request()->query()) }}">Export PDF</a>
```

The PDF template (`reports/pdf.blade.php`) is plain HTML with inline CSS — it does not
extend the admin layout, because dompdf does not load Vite assets.

Reports supported by the filter page:

- In-stock plants (`?stock_status=in_stock`)
- Out-of-stock plants (`?stock_status=out_of_stock`)
- Plants by category (category dropdown)
- Plant price list (unit price column in the same table)

## Test checklist

1. Logged out, visiting `/plants` redirects to `/login`.
2. Login works; logout ends the session.
3. Submitting an empty plant form shows validation errors and keeps old input.
4. `unit_price` rejects `abc` and `-5`.
5. Create, view, edit and delete a plant; success messages appear.
6. Deleting a user's account removes their plants (FK cascade).
7. Reports filter by category and stock status; the URL carries both filters.
8. PDF export contains only the filtered rows.

## Known gaps

- Login accepts email only; the activity also requires login by username.
- Excel export is not implemented (PDF only).
- Categories and stock statuses are hardcoded in validation and Blade instead of model constants.
- No admin seeder; create the first account through `/register`.
