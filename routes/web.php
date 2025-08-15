<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/nfse', function () {
    return view('nfse-form');
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


