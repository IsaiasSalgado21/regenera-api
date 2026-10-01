use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API Regenera BCS conectada y funcionando',
        'timestamp' => now()
    ], 200);
});