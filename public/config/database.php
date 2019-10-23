<?php
class Database
{
    function __construct()
    {
        # code...
    }

    private function connection()
    {
        $connection = new StdClass();
        $connection->dbhost = 'localhost';
        $connection->dbuser = 'root';
        $connection->dbpass = 'root';
        $connection->dbname = 'db_nakertrans';

        return $connection;
    }

    public function connect()
    {
        $connect = $this->connection();
        $dbconnect = new PDO("mysql:dbname=$connect->dbname;host=$connect->dbhost", "$connect->dbuser", "$connect->dbpass");
        $dbconnect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbconnect->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $dbconnect;
    }
}