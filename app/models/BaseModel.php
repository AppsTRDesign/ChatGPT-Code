<?php
abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        global $container;
        $this->db = $container['db'];
    }
}
