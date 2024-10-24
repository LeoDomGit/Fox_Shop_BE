<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Products;
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
    public function UploadImages(Request $request){
        if (!request()->has('images')) {
             $images = $request->file('images');
             $imageName = $images->getClientOriginalName();

             $extractTo = storage_path('app/public/categories/');
             $images->move($extractTo, $imageName);
             $result[] = Storage::url('categories/' . $imageName);
              Categories::create([
                'images' => $imageName
            ]);
             return response()->json(['check' => true, 'result' => $result]);

        }
        $images = $request->images;
        $extractTo = storage_path('app/public/categories/');
        $images->move($extractTo, $imageName);

        $result[] = Storage::url('categories/' . $imageName);
        Categories::create([
            'images' => $imageName
        ]);
        return response()->json(['check' => true, 'result' => $result]);

    }
    

    public function store(Request $request){
        $validated = $this->validateRequest($request);
        $data=$validated;
        $data['slug']=Str::slug($validated['name']);
        if ($request->hasFile('images')) {
        $images = [];
        foreach ($request->file('images') as $image) {
            // Kiểm tra xem file có hợp lệ hay không
            if ($image->isValid()) {
                // Lưu ảnh vào thư mục public/categories
                $path =  $image->store('public/categories');
                $images[] = basename($path); 
            }
        }
        $data['images'] = json_encode($images);
    }
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
    public function api_categories(Request $request)
    {
        $categories = Categories::
        where('status', 1)->get();
        return response()->json($categories);
    }
    public function api_categories_with_products(Request $request)
{
    $categories = Categories::where('status', 1)->get();
    $categories_with_products = [];
    foreach ($categories as $category) {
        $products = Products::join('product_categories', 'products.id', '=', 'product_categories.id_product')
            ->where('product_categories.id_categories', $category->id)
            ->where('products.status', 1)
            ->select('products.*')
            ->get();
        $categories_with_products[] = [
            'category' => $category,
            'products' => $products,
        ];
    }

    // Trả về kết quả dưới dạng JSON
    return response()->json($categories_with_products);
}

    public function api_paginate_products_by_category($id, Request $request)
{
    
    $limit = $request->has('limit') ? $request->limit : 10;
    $products = Products::join('product_categories', 'products.id', '=', 'product_categories.id_product')
        ->join('gallery', 'products.id', '=', 'gallery.id_parent')
        ->where('product_categories.id_categories', $id) 
        ->where('products.status', 1) 
        ->where('gallery.status', 1) 
        ->select('products.*', 'gallery.image as image') 
        ->paginate($limit);
    if ($products->isEmpty()) {
        return response()->json(['message' => 'No products found in this category'], 404);
    }

    // Trả về kết quả danh sách sản phẩm được phân trang
    return response()->json($products);
}


}
