<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\nfseController;

// rota para o formulário de NFSE via controller
Route::get('/nfse', [nfseController::class, 'index'])->name('nfse.form');

// rota para testar conexão com API da Prefeitura
Route::get('/nfse/testar-conexao', [nfseController::class, 'testarConexao']);


Route::get('/', function () {
    return view('welcome');
});




// rota com parâmetro opcional
Route::get('/nfses_teste/{id?}', function ($id = null) {
    return view('nfse-forms', ['id' => $id]  );
});

// rota com parâmetro obrigatório
Route::get('/nfses_teste01/{id}', function ($id) {
    return view('nfse-forms01', ['id' => $id]  );
});

//rota com query params
Route::get('/nfses_teste02', function () {

    $busca = request('search');

    return view('nfse-forms02', ['busca' => $busca]  );
});


