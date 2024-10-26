<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Gallery;
use App\Models\ProductCategory;
use App\Models\Products;
use App\Models\Attribute;
use App\Models\ProductsAttribute;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Traits\HasCrud;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Leo\Categories\Models\Categories;
use Illuminate\Support\Facades\Auth;
class ProductController extends Controller
{
    use HasCrud;
    protected $model;
    public function __construct()
    {
        $this->model = Products::class;
    }

    public function index()
    {
        $result = $this->model::with('categories', 'brands')->get();
        $categories = \App\Models\Categories::active()->get();
        $brands = Brand::active()->get();
        $colors = Attribute::where('name', 'color')->get()->toArray();
        $sizes = Attribute::where('name', 'size')->get()->toArray();
        return Inertia::render('Products/Index', ['dataproducts' => $result, 'databrands' => $brands,
         'datacategories' => $categories, 'datacolor' => $colors, 'datasize' => $sizes]);
    }
    public function Active()
    {
        $result = $this->model::with('categories', 'brands', 'gallery')
            ->where('status', 1)
            ->where('gallery.status', 1)
            ->paginate(3);
        return response()->json($result);
    }
    public function Single_Active($id)
    {
        $product = $this->model::with('categories', 'brands')
            ->where('status', 1)
            ->where('id', $id)
            ->first();
        $result = Gallery::where('id_parent', $id)->pluck('image')->toArray();
        $gallery = [];
        foreach ($result as  $value) {
            $gallery[] = Storage::url('products/' . $id . '/' . $value);
        }
        return response()->json(['result' => $product, 'gallery' => $gallery]);
    }

    public function UploadImages(Request $request, $id)
    {
        if (!request()->has('files')) {
            return response()->json(['check' => false, 'msg' => 'Files is required   ']);
        }
        $result = [];
        foreach ($request->file('files') as $file) {

            $imageName = $file->getClientOriginalName();

            $extractTo = storage_path('app/public/products/');

            $file->move($extractTo, $imageName);

            Gallery::create([

                'id_parent' => $id,

                'image' => $imageName,

                'status' => 0

            ]);

            $result[] = Storage::url('products/' . $imageName);
        }

        $oldImages = Gallery::where('id_parent', $id)->pluck('image')->toArray();

        if (count($oldImages) > 0) {

            foreach ($oldImages as  $value) {

                $result[] = Storage::url('products/' . $id . '/' . $value);
            }
        }

        $result = array_merge($oldImages, $result);

        Products::where('id', $id)->update(['status' => 0]);

        return response()->json(['check' => true, 'result' => $result]);
    }



    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|unique:products,name',
        'price' => 'required|numeric',
        'idBrand' => 'required|exists:brands,id',
        'content' => 'required',
        'files' => 'required|array',
        'files.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'categories' => 'required|array',
        'quantity' => 'nullable|numeric',
        'discount' => 'nullable|numeric'
    ]);

    if ($validator->fails()) {
        return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
    }

    $data = [];
    $data['name'] = $request->name;
    $data['slug'] = Str::slug($data['name']);
    $data['price'] = $request->price;
    $data['discount'] = $request->discount;
    $data['id_brand'] = $request->idBrand;
    $data['content'] = $request->content;
    $data['in_stock'] = $request->quantity;
    $data['created_at'] = now();

    // Thêm sản phẩm mới và lấy id
    $id = $this->model::insertGetId($data);
    // Lưu danh mục cho sản phẩm
    foreach ($request->categories as $value) {
        ProductCategory::create(['id_product' => $id, 'id_categories' => $value, 'created_at' => now()]);
    }

    // Xử lý hình ảnh
    foreach ($request->file('files') as $file) {
        $imageName = $file->getClientOriginalName();
        $extractTo = storage_path('app/public/products/');
        $file->move($extractTo, $imageName);

        Gallery::create([
            'id_parent' => $id,
            'image' => $imageName,
            'status' => 0
        ]);
    }
    foreach ($request->input('colors')   as $colorId) {
            ProductsAttribute::create([
                'product_id' => $id,
                'attribute_id' => $colorId, // Lưu color vào attribute_id
            ]);
    }

    foreach ($request->input('sizes') as $sizeId) {
            // Lưu size vào attribute_id
            ProductsAttribute::create([
                'product_id' => $id,
                'attribute_id' => $sizeId, // Lưu size vào attribute_id
            ]);
    }



    $result = $this->model::with('categories', 'brands')->find($id);

    return response()->json(['check' => true, 'data' => $result]);
}





    public function show($identifier)
    {
        $result = $this->model::with('brands','categories','attributes')->find($identifier);
        $oldImages = Gallery::where('id_parent', $identifier)->pluck('image')->toArray();
        $allColors = Attribute::where('name', 'color')->get();
        $allSizes = Attribute::where('name', 'size')->get();

        $selectedAttributes = ProductsAttribute::where('product_id', $identifier)->pluck('attribute_id')->toArray();
            $gallery = [];
        $selectedCategories = ProductCategory::where('id_product', $identifier)->pluck('id_categories')->toArray();
        foreach ($oldImages as  $value) {
            $gallery[] = Storage::url('products/' .  $value);
        }
        $categories = \App\Models\Categories::active()->get();
        $brands = Brand::active()->get();

        $image = Gallery::where('id_parent', $identifier)->where('status', 1)->value("image");

        if ($image) {
            return Inertia::render('Products/Edit', ['dataId' => $identifier, 'dataBrand' => $brands,'cate'=>$selectedCategories, 'dataCate' => $categories, 'dataproduct' => $result,  'dataColor' => $allColors, 'dataSize' => $allSizes, 'selectedAttributes' => $selectedAttributes, 'datagallery' => $gallery, 'dataimage' => Storage::url('products/' . $image)]);
        } else {
            return Inertia::render('Products/Edit', ['dataId' => $identifier, 'dataBrand' => $brands,'cate'=>$selectedCategories, 'dataCate' => $categories, 'dataproduct' => $result,  'dataColor' => $allColors, 'dataSize' => $allSizes,'selectedAttributes' => $selectedAttributes, 'datagallery' => $gallery, 'dataimage' => Storage::url('products/' . $image)]);
        }
    }



    public function setImage($id, $imageName)

    {

        Gallery::where('id_parent', $id)->update(['status' => 0]);

        Gallery::where('id_parent', $id)

            ->where('image', $imageName)

            ->update(['status' => 1]);

        $result = Storage::url('products/' . $imageName);

        return response()->json(['check' => true, 'result' => $result]);
    }



    public function removeImage($id, $imageName)

    {

        $filePath = "public/products/{$imageName}";

        Storage::delete($filePath);

        Gallery::where('id_parent', $id)

            ->where('image', $imageName)

            ->delete();

        $oldImages = Gallery::where('id_parent', $id)->pluck('image')->toArray();

        $gallery = [];

        foreach ($oldImages as  $value) {

            $gallery[] = Storage::url('products/' . $value);
        }

        return response()->json(['check' => true, 'gallery' => $gallery]);
    }



    public function importImages(Request $request, $identifier)

    {

        $extractTo = storage_path('app/public/products/' . $identifier);

        $zip = new ZipArchive;

        if (request()->has('file')) {

            $zipFile = $request->file;

            if ($zip->open($zipFile) == true) {

                $zip->extractTo($extractTo);

                $zip->close();

                $files = File::files($extractTo);

                foreach ($files as $file) {

                    Gallery::create(['image' => $file->getFilename(), 'id_parent' => $identifier]);
                }



                Products::where('id', $identifier)->update(['status' => 0]);

                return response()->json(['check' => true]);
            } else {

                echo 'Failed to extract files.';
            }
        } else {

            return response()->json(['check' => false, 'msg' => 'file is required']);
        }
    }



    public function switchProduct(Request $request, $identifier)
    {
        $result = Products::findOrFail($identifier);
        if (!$result) {
            return response()->json(['check' => false, 'msg' => 'Not exists']);
        }
        $old = $result->status;
        if ($old == 0) {
            Products::where('id', $identifier)->update(['status' => 1]);
        } else {
            Products::where('id', $identifier)->update(['status' => 0]);
        }
        $result = $this->model::with('categories', 'brands')->get();
        return response()->json(['check' => true, 'data' => $result]);
    }

  public function update(Request $request, $identifier)
{
    // Xác thực dữ liệu đầu vào
    $validator = Validator::make($request->all(), [
        'name' => 'string|max:255|unique:categories,name,' . $identifier,
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
    }

    // Tìm danh mục theo ID
    $category = Category::find($identifier);
    if (!$category) {
        return response()->json(['check' => false, 'msg' => 'Category not found']);
    }

    // Cập nhật dữ liệu cho danh mục
    $category->name = $request->name ?? $category->name;
    $category->slug = Str::slug($request->name) ?? $category->slug;

    // Xử lý hình ảnh nếu có file mới
    if ($request->hasFile('image')) {
        // Xóa ảnh cũ nếu có
        if ($category->image && Storage::exists('public/categories/' . $category->image)) {
            Storage::delete('public/categories/' . $category->image);
        }

        // Lưu ảnh mới và cập nhật đường dẫn trong cơ sở dữ liệu
        $image = $request->file('image');
        $imageName = $image->getClientOriginalName();
        $image->move(storage_path('app/public/categories'), $imageName);
        $category->image = $imageName;
    }

    $category->save();

    // Trả về thông tin danh mục đã cập nhật
    $updatedCategory = Category::with('relatedModel')->find($identifier); // Nếu có liên kết với bảng khác
    return response()->json(['check' => true, 'data' => $updatedCategory]);
}




    public function destroy($identifier)
    {
        $product = Products::where('id', $identifier)->first();
        if (!$product) {
            return response()->json(['check' => false, 'msg' => 'Không tìm thấy sản phẩm']);
        }
        $images = Gallery::where('id_parent', $identifier)->select('image')->get();
        foreach ($images as $image) {
            $filePath = "public/products/{$image->image}";
            Storage::delete($filePath);
        }
        Gallery::where('id_parent', $identifier)->delete();
        Products::where('id', $identifier)->delete();
        $result = $this->model::with('categories', 'brands')->get();
        if (count($result) > 0) {
            return response()->json(['check' => true, 'result' => $result]);
        }
        return response()->json(['check' => true]);
    }
    public function import(Request $request)
    {
        if ($request->has('file')) {
            $file = $request->file('file');
            Excel::import(new ProductImport(), $file);
            return response()->json(['check' => true]);
        } else {
            return response()->json(['check' => false, 'msg' => 'File is required']);
        }
    }

    public function api_product(Request $request)
    {
        if ($request->has('limit')) {
            $result = Products::join('gallery', 'products.id', '=', 'gallery.id_parent')
                ->where('products.status', 1)
                ->where('gallery.status', 1)->select('products.*', 'gallery.image as image')
                ->take($request->limit)->get();
            return response()->json($result);
        } else {
            $result = Products::join('gallery', 'products.id', '=', 'gallery.id_parent')
                ->where('products.status', 1)
                ->where('gallery.status', 1)->select('products.*', 'gallery.image as image')
                ->paginate(16);
            return response()->json($result);
        }
    }
    public function api_product_cate(Request $request)
    {
        // Khởi tạo query ban đầu với điều kiện mặc định
        $query = Products::join('product_categories', 'products.id', '=', 'product_categories.id_product')
        ->join('categories', 'product_categories.id_categories', '=', 'categories.id')
        ->where('products.status', 1)
        ->where('categories.status', 1)
        ->select('products.*');

    // Nếu có yêu cầu lọc theo loại (category)
    if ($request->has('id_categories')) {
        $query->where('categories.id', $request->category_id);
    }

    // Nếu có yêu cầu giới hạn số lượng sản phẩm
    if ($request->has('limit')) {
        $result = $query->take($request->limit)->get();
    } else {
        // Nếu không có yêu cầu limit, sử dụng phân trang
        $result = $query->paginate(16);
    }

    // Trả về kết quả dưới dạng JSON
    return response()->json($result);
    }
    // --------------------------------------
    public function api_search_product($slug)
    {
        $result = Products::join('gallery', 'products.id', '=', 'gallery.id_parent')
            ->where('products.status', 1)
            ->where('gallery.status', 1)
            ->where(function ($query) use ($slug) {
                $query->where('products.name', 'like', '%' . $slug . '%')
                    ->orWhere('products.slug', 'like', '%' . $slug . '%');
            })
            ->select('products.*', 'gallery.image as image')
            ->get();
        if (count($result) == 0) {
            return response()->json(['product' => []]);
        }
        return response()->json(['products' => $result]);
    }
    // --------------------------------------
public function api_single_product($slug)
{
    $result = Products::with(['brands', 'categories'])
        ->where('products.slug', $slug)
        ->where('products.status', 1)
        ->select('products.*')
        ->first();
    if (!$result) {
        return response()->json([]);
    }

    $medias = Gallery::where('id_parent', $result->id)
        ->pluck('image');

    // Lấy thông tin danh mục của sản phẩm
    $categoryData = ProductCategory::where('id_product', $result->id)->first();
    $id_cate = $categoryData->id_categories;

    // Lấy sản phẩm cùng danh mục
    $cate_products = Products::join('product_categories', 'products.id', '=', 'product_categories.id_product')
        ->join('gallery', 'products.id', '=', 'gallery.id_parent')
        ->where('products.status', 1)
        ->where('product_categories.id_categories', $id_cate)
        ->where('gallery.status', 1)
        ->select('products.*', 'gallery.image as image')
        ->take(4);

    // Lấy sản phẩm cùng brand
    $brand_products = Products::join('gallery', 'products.id', '=', 'gallery.id_parent')
        ->where('products.status', 1)
        ->where('products.id_brand', $result->idBrand)
        ->where('gallery.status', 1)
        ->select('products.*', 'gallery.image as image')
        ->take(4);

    // Xử lý để lấy sản phẩm liên quan
    if ($brand_products->exists() && $cate_products->exists()) {
        $links = $cate_products->union($brand_products)->get();
    } elseif (!$brand_products->exists()) {
        $links = $cate_products->get();
    } else {
        $links = $brand_products->get();
    }

    // Lấy màu và kích thước của sản phẩm
    $attributes = ProductsAttribute::where('product_id', $result->id)
        ->join('attribute', 'products_attribute.attribute_id', '=', 'attribute.id')
        ->select('attribute.type', 'attribute.name')
        ->get()
        ->groupBy('name'); // Nhóm theo 'name' để tách color và size

    // Phân loại thuộc tính color và size
    $colors = $attributes->get('color', []);
    $sizes = $attributes->get('size', []);

    // Trả về dữ liệu dưới dạng JSON
    return response()->json([
        'product' => $result,
        'medias' => $medias,
        'links' => $links,
        'colors' => $colors,
        'sizes' => $sizes
    ]);
}


    public function api_gallery_by_product_id(Request $request, $productId)
    {
        $product = Products::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $images = Gallery::where('id_parent', $productId)
            ->where('status', 1)
            ->pluck('image');
        return response()->json(['images' => $images]);
    }
    public function api_product_details(Request $request, $productId)
{
    // Lấy sản phẩm cùng với thông tin liên kết (brand, category)
    $product = Products::where('id', $productId)
        ->where('status', 1)
        ->first();

    // Kiểm tra nếu không tìm thấy sản phẩm
    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }

    // Lấy thuộc tính (color, size) của sản phẩm
    $attributes = ProductsAttribute::where('product_id', $productId)
        ->join('attribute', 'products_attribute.attribute_id', '=', 'attribute.id')
        ->select('attribute.type', 'attribute.name')
        ->get()
        ->groupBy('name'); // Nhóm theo 'name' để tách color và size

    // Phân loại thuộc tính color và size
    $colors = $attributes->get('color', []);
    $sizes = $attributes->get('size', []);

    // Lấy danh sách hình ảnh liên quan
    $images = Gallery::where('id_parent', $productId)->get();

    // Trả về dữ liệu dưới dạng JSON
    return response()->json([
        'product' => $product,
        'color' => $colors,
        'size' => $sizes,
        'images' => $images,
    ]);
}


    public function api_load_cart_product(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $arr = [];
        foreach ($request->cart as $item) {
            $product = Products::join('gallery', 'products.id', '=', 'gallery.id_parent')->where('gallery.status', 1)->where('products.id', $item[0])->select('products.id', 'gallery.image', 'slug', 'name', 'price', 'discount')->get();
            foreach ($product as $item1) {
                $item2 = [
                    'id' => $item1->id,
                    'name' => $item1->name,
                    'slug' => $item1->slug,
                    'quantity' => $item[1],
                    'discount' => (int)$item1->discount,
                    'price' => (int)$item1->price,
                    'image' => $item1->image,
                    'total' => (int)$item1->discount * $item[1],
                ];
                array_push($arr, $item2);
            }
        }
        return response()->json($arr);
    }}
