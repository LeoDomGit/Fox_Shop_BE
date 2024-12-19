<?php

use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrdersMngController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WishlistController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Laravel\Socialite\Facades\Socialite;

Route::get('/',[UserController::class, 'loginForm']);

Route::get('/verify', [UserController::class, 'loginForm']);
Route::prefix('admin')->group(function () {
Route::resource('/roles', RolesController::class);
Route::resource('/permissions', PermissionController::class);
Route::resource('/users', UserController::class);
Route::post('/loginad', [UserController::class, 'loginAdmin']);
Route::put('/users/switch/{id}', [UserController::class, 'switchUser']);
Route::resource('/categories', CategoriesController::class);
Route::post('/categories/switch/{id}', [CategoriesController::class, 'switchCategories']);
Route::resource('/brands', BrandController::class);
Route::post('/brands/switch/{id}', [BrandController::class, 'switchBrand']);
Route::resource('/products', ProductController::class);
Route::post('categories/imgaes/{id}', [CategoriesController::class, 'updateCate']);
Route::post('post/update/{id}', [PostController::class, 'updatePost']);
Route::post('voucher/update/{id}', [VoucherController::class, 'updateVoucher']);
Route::post('method/update/{id}', [MethodController::class, 'updateMethod']);
Route::put('/products/switch/{id}', [ProductController::class, 'switchProduct']);
Route::post('/products/switch_qty/{id}', [ProductController::class, 'switchProductQty']);
Route::delete('/products/drop-image/{id}/{imageName}', [ProductController::class, 'removeImage']);
Route::post('/products/set-image/{id}/{imageName}', [ProductController::class, 'setImage']);
Route::post('/products/set-image/{id}/{imageName}', [ProductController::class, 'setImage']);
Route::post('/products/upload-images/{id}', [ProductController::class, 'UploadImages']);
Route::resource('/attributes', AttributeController::class);
Route::resource('/posts', PostController::class);
Route::post('/posts/switch/{id}', [PostController::class, 'switchPost']);
Route::resource('/vouchers', VoucherController::class);
Route::post('/vouchers/status/{id}', [VoucherController::class, 'UploadStatus']);
Route::post('/receive-voucher/{id}', [VoucherController::class, 'receiveVoucher']);
Route::resource('/methods', MethodController::class);
Route::resource('/cart',CartController::class);
Route::post('/dashboard/date',[DashboardController::class, 'searchDate']);
Route::resource('/dashboard', DashboardController::class);   
Route::resource('/ordermng', OrdersMngController::class);
Route::get('/products/detail/{slug}', [ProductController::class, 'ProDetail']);
Route::resource('/wishlist', WishlistController::class);
Route::resource('/review', ReviewController::class);
Route::post('/review/switch/{id}', [ReviewController::class, 'switchReview']);

});