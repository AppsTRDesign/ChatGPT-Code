<?php
abstract class BaseController
{
    protected array $request;

    public function __construct()
    {
        $this->request = Security::sanitize($_REQUEST);
    }

    protected function inputJson(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            return [];
        }
        return Security::sanitize($data);
    }
}
