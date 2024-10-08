<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Str;
class CategoriesController extends BaseCrudController
{

    public function __construct(){
        $this->model= Categories::class;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories=Categories::all();
        return Inertia::render('Categories/Index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $validated = $this->validateRequest($request);
        $data=$validated;
        $data['slug']=Str::slug($validated['name']);
        $this->model::create($data);
        return response()->json(['check'=>true,'data'=>$this->model::all()]);
    }
    /**
     * Display the specified resource.
     */
    public function show(Categories $categories)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categories $categories)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'unqiue:categories,name|max:255|string',
           'position'=>'numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['check'=>false,'msg'=>$validator->errors()->first()]);
        }
        $resource = $this->model::findOrFail($id);
        $data=$request->all();
        $resource->update($data);
        $result = $this->model::all();
        return response()->json([
           'check'=>true,
           'data'=>$result,
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    protected function validateRequest(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:categories,name',
            'position'=>'numeric',
        ];
    
        $validator = \Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'check' => false,
                'msg' => $validator->errors()->first(),
            ], 200));
        }
    
        return $validator->validated();
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category  = Categories::find($id);
        if(!$category){
            return response()->json(['check'=>false,'msg'=>'Không tìm thấy mã loại sản phẩm']);
        }
        Categories::where('id',$id)->delete();
        // Categories::find($id)->delete();
        return response()->json(['check'=>true,'data'=>$this->model::all()]);
    }
}
