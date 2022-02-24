<?php
class ApiServer
{
    // шаблон ответа
    private $response = ['notice'=>[]];

    private $db = null;

    public function __construct(){
        // результат в формате JSON
        header('Content-Type: application/json; utf-8');

        try {
            
            switch($_SERVER['REQUEST_METHOD'])
            {
                case 'DELETE':
                    $this->processDelete($_SERVER['PATH_INFO']);
                    break;
                case 'GET': 
                    $this->processGet($_SERVER['PATH_INFO']);
                    break;
                 case 'POST':
                     $this->processPost($_SERVER['PATH_INFO']);
                     break;
            }
        } catch (\Throwable $th) {
            $this->response['notice']['answer'] = $th->getMessage();
        }

        // выводим в stdout JSON-строку
        echo json_encode($this->response, JSON_UNESCAPED_UNICODE);
    }

    private function processGet($path)
    {
        switch($path)
        {
            case '/ProductSale':
                $this->auth();
                
                // получаем данные
                $this->response['notice']['data'] = $this->db
                    ->query("SELECT ps.*, a.Title, p.Title
                     FROM ProductSale ps, Agent a, Product p 
                     WHERE  ps.AgentID = a.ID and
                      ps.ProductID = p.ID")
                    ->fetchAll(PDO::FETCH_ASSOC);
                break;
                default:
                header("HTTP/1.1 404 Not Found");

                case '/Product':
                $this->auth();
                $this->response['notice']['data'] = $this->db
                ->query("SELECT * FROM Product")
                ->fetchAll(PDO::FETCH_ASSOC);
                break;
            
        }
    }
    private function processDelete($path)
    {
        switch($path)
        {
            case '/ProductSale':
                $this-> auth();
                $id = $_GET['id'] ? :0;
                if($id)
                $this->db->query("DELETE FROM ProductSale WHERE id = $id")
                ->execute();
                $this ->response['status'] = 0;
                break;
                default:
                header("HTTP/1.1 404 Not Found");
        }
    }
    private function processPost($path)
    {
        $rawData = file_get_contents('php://input');
        $json = json_decode($rawData);
        switch($path)
        {
            case '/ProductSale':
                $this->auth();
                $query = $this->db->
                prepare("INSERT INTO ProductSale (AgentID,ProductID,ProductCount,SaleDate) 
                VALUES (:AgentID,:ProductID,:ProductCount, now() )");
                $query -> execute([':AgentID'=> $json->AgentID, ':ProductID' => $json->ProductID,':ProductCount'=>$json->ProductCount
                ]);
                $this->response['status']=0;
                break;
                default:
                header("HTTP/1.1 404 Not Found");
        }
    }

    private function auth()
    {
        if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
            throw new Exception('Не задан логин/пароль');

        // пытаемся подключиться к MySQL серверу
        $this->db = new PDO(
            "mysql:host=kolei.ru;port=3306;dbname={$_SERVER['PHP_AUTH_USER']};charset=UTF8", 
            $_SERVER['PHP_AUTH_USER'], 
            $_SERVER['PHP_AUTH_PW']);
    }
}
new ApiServer();
?>