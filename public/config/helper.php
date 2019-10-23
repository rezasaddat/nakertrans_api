<?php
    use \Firebase\JWT\JWT;
    class Helper
    {
        function __construct()
        {
            # code...
        }
        public $key = 'nakertrans';
        public function encodeToken($param)
        {
            $date = new DateTime();
            $payload['id']          = $param->id;
            $payload['nama']        = $param->nama;
            $payload['tipe']        = $param->tipe;
            $payload['role']        = $param->role;
            $payload['program']     = $param->program;
            $payload['username']    = $param->username;
            $payload['iat']         = $date->getTimestamp(); //waktu di buat
            $payload['exp']         = $date->getTimestamp() + 864000; //satu jam
            $r = [
                'status'        => 'success',
                'token'         => JWT::encode($payload, $this->key),
                'id'            => $param->id,
                'nama'          => $param->nama,
                'tipe'          => $param->tipe,
                'role'          => $param->role,
                'program'       => $param->program,
                'username'      => $param->username,
            ];
            return $r;
        }
        public function decodeToken($token)
        {
            $decoded = JWT::decode($token, $this->key, array('HS256'));
            if ($decoded) {
                $r = [
                    "status"=> true,
                    "response"=> [
                        "classname"=> "Firebase\\JWT\\ExpiredException",
                        "message"=> "Token valid"
                    ]
                ];
            } else {
                $r = [
                    "status"=> false,
                    "response"=> [
                        "classname"=> "Firebase\\JWT\\ExpiredException",
                        "message"=> "Expired token"
                    ]
                ];
            }
            return $r;
        }
    }
    