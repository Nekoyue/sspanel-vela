<?php

namespace App\Utils;

use App\Models\Models;
use App\Services\Config;
use Ozdemir\Datatables\DB\DatabaseInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;

class DatatablesHelper implements DatabaseInterface
{
    protected array $escape = [];
    protected \Illuminate\Database\Connection $connection;

    public function __construct($config = null)
    {
        $capsule = new Capsule();
        $capsule->addConnection(Config::getDbConfig(), 'default');
        $this->connection = $capsule->getConnection('default');
        try {
            $this->connection->select("set session sql_mode='';");
        } catch (QueryException $e) {
        }
    }

    public function connect(): static
    {
        return $this;
    }

    public function query($query): array
    {
        $data = $this->connection->select($query, $this->escape);
        $row = [];
        foreach ($data as $item) {
            $row[] = (array)$item;
        }
        return $row;
    }

    public function count($query)
    {
        $query = 'Select count(*) as rowcount,' . substr($query, 6);
        $data = $this->connection->select($query, $this->escape);
        return $data[0]->rowcount;
    }

    public function escape($string): string
    {
        $this->escape[':escape' . (count($this->escape) + 1)] = '%' . $string . '%';
        return ':escape' . count($this->escape);
    }
}
