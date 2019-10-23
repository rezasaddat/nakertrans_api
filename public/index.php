<?php
header("Access-Control-Allow-Origin: *");
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use \Firebase\JWT\JWT;

require '../vendor/autoload.php';
require './config/database.php';
require './config/helper.php';

$app = new \Slim\App;
// index api
$app->get('/', function() {
    echo "API TESTING";
});

$app->get('/status_token', function($request, $response) {
    $helper     = new Helper();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    return $response->withJson($decoded, 200);
});

$app->post('/authentication/signin', function ($request, $response) {
    // database connection
    $db         = new Database();
    $helper     = new Helper();
    $dbconnect     = $db->connect();
    // --------------------------
    $body       = $request->getParsedBody();
    $query      = "
                    SELECT
                        *
                    FROM 
                        users 
                    WHERE 
                        username=:username";
    $statement  = $dbconnect->prepare($query);
    $param = [
        ":username" => $body['username'],
        // ":password" => md5($body['password'])
    ];
    if($statement->execute($param)){
        $user = $statement->fetch(PDO::FETCH_OBJ);
        if (md5($body['password']) == $user->password) {
            $token = $helper->encodeToken($user);
            return $response->withJson($token, 200);    
        }else{
            return $response->withJson(["status" => "failed", "message" => "password salah!"], 200);
        }

    }else{
        return $response->withJson(["status" => "failed", "message" => "user tidak ditemukan!"], 200);
    }
});

$app->get('/sumberdata/list', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $page  = (($param['page'])? $param['page'] : 1) - 1;
        $start = ceil($page * 20);

        $query = "SELECT 
                        t.TABLE_NAME as sumber_data,
                        t.TABLE_ROWS as total_record
                    FROM 
                        INFORMATION_SCHEMA.TABLES AS t 
                    WHERE 
                        t.TABLE_SCHEMA = 'db_nakertrans' 
                    AND 
                        t.TABLE_NAME LIKE 'data%'
                    LIMIT 20 OFFSET $start";
        $statement = $dbconnect->query($query);
        $result = $statement->fetchAll(PDO::FETCH_OBJ);

        if($statement->rowCount() > 0){
            return $response->withJson([
                'currentPage'   => $param['page'] + 1,
                'from'          => $start,
                'to'            => $start + $statement->rowCount(),
                'perPage'       => 20,
                'data'          => $result
            ], 200);
        }else{
            return $response->withJson([
                'status' => 400,
                'message' => 'tidak ada data.'
            ], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->get('/sumberdata/detail', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $page  = (($param['page'])? $param['page'] : 1) - 1;
        $start = ceil($page * 20);
        
        $query ="SELECT * 
                FROM ".$param['sumberdata']."
                LIMIT 20 OFFSET $start";

        $statement = $dbconnect->query($query);
        $result = $statement->fetchAll(PDO::FETCH_OBJ);
        
        if($statement->rowCount() > 0){
            return $response->withJson([
                'currentPage'   => $param['page'] + 1,
                'from'          => $start,
                'to'            => $start + $statement->rowCount(),
                'perPage'       => 20,
                'data'          => $result
            ], 200);
        }else{
            return $response->withJson([
                'status' => 400,
                'message' => 'tidak ada data.'
            ], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->post('/sumberdata/single_record', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $body       = $request->getParsedBody();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $query = "INSERT INTO 
                    data_master_sumber_data
                VALUE (
                    :nik,
                    :nama,
                    :tempat_lahir,
                    :tanggal_lahir,
                    :jenis_kelamin,
                    :alamat,
                    :rtrw,
                    :keldesa,
                    :kecamatan,
                    :agama,
                    :status_perkawinan,
                    :pekerjaan,
                    :kewarganegaraan,
                    :golongan_darah,
                    NULL,
                    NULL,
                    NULL
                )";
        $statement = $dbconnect->prepare($query);
        $insert_value = [
            ":nik"                  => $body['nik'],
            ":nama"                 => $body['nama'],
            ":tempat_lahir"         => $body['tempat_lahir'],
            ":tanggal_lahir"        => $body['tanggal_lahir'],
            ":jenis_kelamin"        => $body['jenis_kelamin'],
            ":alamat"               => $body['alamat'],
            ":rtrw"                 => $body['rtrw'],
            ":keldesa"              => $body['keldesa'],
            ":kecamatan"            => $body['kecamatan'],
            ":agama"                => $body['agama'],
            ":status_perkawinan"    => $body['status_perkawinan'],
            ":pekerjaan"            => $body['pekerjaan'],
            ":kewarganegaraan"      => $body['kewarganegaraan'],
            ":golongan_darah"       => $body['golongan_darah']
        ];
        
        $checker = "SELECT * FROM data_master_sumber_data WHERE nik = ".$body['nik'];
        $statement_checker = $dbconnect->query($checker)->rowCount();
        if($statement_checker == 0){
            $statement->execute($insert_value);
            return $response->withJson(["status" => "success", "data" => "1"], 200);
        }else{
            return $response->withJson(["status" => "failed", "data" => "0"], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->get('/sumberdata/detail_record', function ($request, $response, $args) {
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $query = "SELECT * FROM data_master_sumber_data WHERE nik = ".$param['nik'];
        $statement = $dbconnect->query($query);
        $result = $statement->fetch(PDO::FETCH_OBJ);

        if($statement->rowCount() > 0){
            return $response->withJson([
                'data'          => $result
            ], 200);
        }else{
            return $response->withJson([
                'status' => 400,
                'message' => 'tidak ada data.'
            ], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->put('/sumberdata/update_record/{nik}', function ($request, $response, $args) {
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $body       = $request->getParsedBody();
    $decoded    = $helper->decodeToken($param['token']);

    if ($decoded['status'] == true) {

        $query = "UPDATE `data_master_sumber_data`
                SET 
                    `nama` = :nama,
                    `tempat_lahir` = :tempat_lahir,
                    `tanggal_lahir` = :tanggal_lahir,
                    `jenis_kelamin` = :jenis_kelamin,
                    `alamat` = :alamat,
                    `rt/rw` = :rtrw,
                    `kel/desa` = :keldesa,
                    `kecamatan` = :kecamatan,
                    `agama` = :agama,
                    `status_perkawinan` = :status_perkawin,
                    `pekerjaan` = :pekerjaan,
                    `kewarganegaraan` = :kewarganegaraan,
                    `golongan_darah` = :golongan_darah
                WHERE
                    `nik` = :nik";

        $value = [
            ":nik" => $args['nik'],
            ":nama" => $body['nama'],
            ":tempat_lahir" => $body['tempat_lahir'],
            ":tanggal_lahir" => $body['tanggal_lahir'],
            ":jenis_kelamin" => $body['jenis_kelamin'],
            ":alamat" => $body['alamat'],
            ":rtrw" => $body['rtrw'],
            ":keldesa" => $body['keldesa'],
            ":kecamatan" => $body['kecamatan'],
            ":agama" => $body['agama'],
            ":status_perkawin" => $body['status_perkawinan'],
            ":pekerjaan" => $body['pekerjaan'],
            ":kewarganegaraan" => $body['kewarganegaraan'],
            ":golongan_darah" => $body['golongan_darah']
        ];

        $statement = $dbconnect->prepare($query);
        if($statement->execute($value)){
            return $response->withJson(["status" => "success", "data" => "1"], 200);
        }else{
            return $response->withJson(["status" => "failed", "data" => "0"], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->get('/program/list', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $page  = (($param['page'])? $param['page'] : 1) - 1;
        $start = ceil($page * 20);

        $query = "SELECT * FROM master_program ORDER BY id ASC LIMIT 20 OFFSET $start";
        $statement = $dbconnect->query($query);
        $result = $statement->fetchAll(PDO::FETCH_OBJ);
        
        if($statement->rowCount() > 0){
            return $response->withJson([
                'currentPage'   => $param['page'],
                'from'          => $start,
                'to'            => $start + $statement->rowCount(),
                'perPage'       => 20,
                'data'          => $result
            ], 200);
        }else{
            return $response->withJson([
                'status' => 400,
                'message' => 'tidak ada data.'
            ], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->get('/program/detail', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $page  = (($param['page'])? $param['page'] : 1) - 1;
        $start = ceil($page * 20);

        $query = "SELECT * FROM ".$param['sumberdata']." LIMIT 20 OFFSET $start";
        $statement = $dbconnect->query($query);
        $result = $statement->fetchAll(PDO::FETCH_OBJ);
        
        if($statement->rowCount() > 0){
            return $response->withJson([
                'currentPage'   => $param['page'],
                'from'          => $start,
                'to'            => $start + $statement->rowCount(),
                'perPage'       => 20,
                'data'          => $result
            ], 200);
        }else{
            return $response->withJson([
                'status' => 400,
                'message' => 'tidak ada data.'
            ], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->get('/berita/list', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {
        $page  = (($param['page'])? $param['page'] : 1) - 1;
        $start = ceil($page * 20);

        $query = "SELECT * FROM master_berita ORDER BY id ASC LIMIT 20 OFFSET $start";
        $statement = $dbconnect->query($query);
        $result = $statement->fetchAll(PDO::FETCH_OBJ);
        
        if($statement->rowCount() > 0){
            return $response->withJson([
                'currentPage'   => $param['page'],
                'from'          => $start,
                'to'            => $start + $statement->rowCount(),
                'perPage'       => 20,
                'data'          => $result
            ], 200);
        }else{
            return $response->withJson([
                'status' => 400,
                'message' => 'tidak ada data.'
            ], 200);
        }
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->get('/berita/detail', function($request, $response){
    $helper     = new Helper();
    $db         = new Database();
    $dbconnect  = $db->connect();
    $param      = $request->getQueryParams();
    $decoded    = $helper->decodeToken($param['token']);
    if ($decoded['status'] == true) {

        $query = "SELECT * FROM master_berita WHERE id = ".$param['id'];
        $statement = $dbconnect->query($query);
        $result = $statement->fetch(PDO::FETCH_OBJ);

        $query_detail = "SELECT isi_berita, tipe FROM detail_berita WHERE id_berita = ".$param['id']." ORDER BY id ASC";
        $statement_detail = $dbconnect->query($query_detail);
        $result_detail = $statement_detail->fetchAll(PDO::FETCH_OBJ);
        
        $data = [
            "id"=> $result->id,
            "judul_berita"=> $result->judul_berita,
            "gambar_utama"=> $result->gambar_utama,
            "tanggal"=> $result->tanggal,
            "detail" => $result_detail
        ];
        return $response->withJson($data, 200);
    }else{
        return $response->withJson($decoded, 200);
    }
});

$app->run();