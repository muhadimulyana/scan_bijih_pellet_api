<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Info Aplikasi
$router->group(['prefix' => 'appInfo'], function () use ($router) {
    $router->get('/getAppInfo/{packageName}', 'AppInfoController@show');
    $router->post('/checkUpdate', 'AppInfoController@checkUpdate');
    $router->post('/insert', 'AppInfoController@store');
});

// Login
// $router->pattern('uName', '(?i)uName(?-i)');
// $router->pattern('uPass', '(?i)uPass(?-i)');
$router->post('/login', 'LoginController@login');

// Cabang
$router->group(['prefix' => 'cabang'], function () use ($router) {
    $router->get('/', 'CabangController@index');
    $router->get('/getPt', 'CabangController@getPt');
    $router->get('/getGudang/{kaCab}', 'CabangController@getGudang');
});

// Info Device
$router->group(['prefix' => 'deviceInfo'], function () use ($router) {
    $router->get('/getDeviceInfo/{uDeviceId}/{uAppId}/{uName}', 'DeviceInfoController@show');
    $router->post('/getDevice', 'DeviceInfoController@getDevice');
    $router->post('/insert', 'DeviceInfoController@store');
});

// Rule Aplikasi
$router->group(['prefix' => 'appRule'], function () use ($router) {
    $router->get('/getRule/{uName}/{packageName}/{pageName}/{ruleName}', 'AppRuleController@show');
    $router->post('/getRule', 'AppRuleController@getRule');
    $router->post('/insert', 'AppRuleController@store');
    $router->patch('/update', 'AppRuleController@update');
});

// Scan Bijih
$router->group(['prefix' => 'barcodePelletDet'], function () use ($router) {
    $router->get('/', 'BarcodePelletDetController@index');
    $router->post('/insert', 'BarcodePelletDetController@store');
    //get list barcode
    $router->get('/getlistBarcode/{status}/{notrans:[A-Za-z0-9/]+}', 'BarcodePelletDetController@getlistBarcode');
    $router->get('/getPellet', 'BarcodePelletDetController@getkodePellet');
    $router->post('/checkBarcode', 'BarcodePelletDetController@checkBarcode');
});

//BST 
$router->group(['prefix' => 'bst'], function () use ($router) {
    $router->get('/getBst/{pt}/{gudang}/{dept}', 'BstController@bstKirim');
    $router->get('/getbstUser/{pt}/{dept}', 'BstController@getbstUser');
    $router->get('/getDraf/{pt}/{gudang}/{dept}', 'BstController@getdrafBst');
    $router->post('/terima', 'BstController@terimaBst');
    $router->post('/update', 'BstController@update');
    $router->post('/kirim', 'BstController@store');
    $router->get('/cekList/{notrans:[A-Za-z0-9/]+}', 'BstController@checklistBst2');
    $router->get('/getTotal/{notrans:[A-Za-z0-9/]+}', 'BstController@gettotalBarcode');
    $router->post('/checkBarcode', 'BstController@checkBarcode');
    $router->post('/checkBarcodeKirim', 'BstController@checkBarcodeKirim');
    $router->post('/checkbarcodeTerima', 'BstController@checkbarcodeTerima');
    $router->post('/checkBarcodeKirimUpdate', 'BstController@checkbarcodeDraf');
    $router->post('/ceklistArea', 'BstController@ceklistArea');
    $router->delete('/delete/{notrans:[A-Za-z0-9/]+}', 'BstController@delete');
    $router->get('/checkFinal/{notrans:[A-Za-z0-9/]+}', 'BstController@checkFinalize');
    //$router->post('/submit', 'BstController@store');
});
// $router->get('/getBst', 'BstController@bstKirim');
// $router->get('/generateBst/{pt}/{dept}/{tgl}', 'BstController@bstGenerate');

//Departemen
$router->group(['prefix' => 'dept'], function () use ($router) {
    $router->get('/', 'DepartemenController@index');
    $router->get('/deptScan', 'DepartemenController@getdeptScan');
    $router->get('/deptPengirim/{user}/{dept}', 'DepartemenController@getdeptPengirim');
});

//Area
$router->group(['prefix' => 'area'], function () use ($router) {
    $router->get('/show/{dept}', 'StrukturJabatanController@show');
    $router->get('/getArea/{user}', 'StrukturJabatanController@getArea');
    //$router->get('/deptScan', 'DepartemenController@getdeptScan');
});

//Router DO
$router->group(['prefix' => 'doi'], function() use ($router){
    $router->get('/', 'DoiController@test');
    //Terima
    $router->get('/getlistdoiUser/{pt}/{gudang}', 'DoiController@getlistdoiUser');
    $router->get('/getlistDoi/{pt}/{gudang}', 'DoiController@getlistDoi');
    $router->get('/getlistBarcode/{IdDo:[A-Za-z0-9/]+}', 'DoiController@getlistBarcode');
    $router->get('/getTotal/{IdDo:[A-Za-z0-9/]+}', 'DoiController@gettotalBarcode');
    $router->post('/terima', 'DoiController@terimaDoi');
    // Kirim
    $router->get('/getSoi/{pt}/{gudang}', 'DoiController@getSo');
    $router->get('/getJadwal/{pt}/{gudang}', 'DoiController@getJadwal');
    $router->get('/getDraf/{pt}/{gudang}', 'DoiController@getdrafDoi');
    $router->post('/checkBarcode', 'DoiController@checkBarcode');
    $router->post('/checkBarcodeKirim', 'DoiController@checkBarcodeKirim');
    $router->post('/checkBarcodeTerima', 'DoiController@checkBarcodeTerima');
    $router->get('/checkItem/{idSo:[A-Za-z0-9/]+}/{kode}/{total}', 'DoiController@checktotalItem');
    $router->post('/kirim', 'DoiController@store');
    $router->post('/update', 'DoiController@update');
    $router->get('/checkFinal/{notrans:[A-Za-z0-9/]+}', 'DoiController@checkFinalize');
});

//User Routes
$router->group(['prefix' => 'users'], function () use ($router) {
    $router->get('/checkPin/{username}/{pin}', 'UsersController@checkPin');
    //$router->get('/deptScan', 'DepartemenController@getdeptScan');
});


//Tutor Routes
$router->group(['prefix' => 'appTut'], function () use ($router) {
    $router->post('/getTutor', 'AppTutController@getTutor');
    //$router->get('/deptScan', 'DepartemenController@getdeptScan');
});





