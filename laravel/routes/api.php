use App\Http\Controllers\AICustomBouquetController;

Route::post('/ai/recommend', [AICustomBouquetController::class, 'recommend']);
Route::post('/ai/generate-message', [AICustomBouquetController::class, 'generateMessage']);