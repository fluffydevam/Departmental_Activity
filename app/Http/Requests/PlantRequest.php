<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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
}
