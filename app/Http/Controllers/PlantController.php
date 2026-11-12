<?php

namespace App\Http\Controllers;


use App\Http\Requests\PlantRequest;
use App\Models\Plant;


class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
          $plants = Plant::with('user')->latest()->paginate(10);
         return view('plants.index', compact('plants'));
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('plants.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlantRequest $request)
    {
        auth()->user()->plants()->create($request->validated());

    return redirect()->route('plants.index')->with('success', 'Plant added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Plant $plant)
    {
         return view('plants.show', compact('plant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plant $plant)
    {
       return view('plants.edit', compact('plant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlantRequest $request, Plant $plant)
    {
       $plant->update($request->validated());

    return redirect()->route('plants.index')->with('success', 'Plant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plant $plant)
    {
         $plant->delete();

    return redirect()->route('plants.index')->with('success', 'Plant deleted successfully.');
        }
}

