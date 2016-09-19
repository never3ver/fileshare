<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$config['displayErrorDetails'] = TRUE;
$config['db']['host'] = "localhost";
$config['db']['user'] = "root";
$config['db']['pass'] = "";
$config['db']['dbname'] = "test";


$app = new \Slim\App(["settings" => $config]);
$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig("../templates");
    $view->addExtension(new \Slim\Views\TwigExtension(
            $container['router'], $container['request']->getUri()
    ));

    return $view;
};

// Render Twig template in route
$app->get('/', function (Request $request, Response $response) {
    $response = $this->view->render($response, "upload.html.twig");
    return $response;
})->setName('upload');

$app->post('/', function (Request $request, Response $response) {
    if ($_FILES) {
        $file = new File();
        $dataGateway = new FileDataGateway($this->db);
        $file->setName($_FILES['uploadfile']['name']);
        $file->setSize($_FILES['uploadfile']['size']);
        $file->setType($_FILES['uploadfile']['type']);
        do {
            $tmpName = Helper::generateTmpName();
        } while ($dataGateway->isTmpNameExisting($tmpName));
        $file->setTmpName($tmpName);
        $dataGateway->addFile($file);
        move_uploaded_file($_FILES['uploadfile']['tmp_name'], "files/$tmpName");
    }
    $response = $this->view->render($response, "upload.html.twig");
    return $response;
});

$app->get('/file/{id}', function (Request $request, Response $response, $args) {
    require_once('../app/getid3/getid3.php');
    $id = (int) $args['id'];
    $dataGateway = new FileDataGateway($this->db);
    $file = $dataGateway->getFile($id);
    $getId3 = new getID3();
    $fileInfo = $getId3->Analyze("files/{$file->getTmpName()}");
    $response = $this->view->render($response, "file.html.twig", ["file" => $file, "fileInfo" => $fileInfo]);
    return $response;
})->setName('file');

$app->get('/download/{id}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    $dataGateway = new FileDataGateway($this->db);
    $file = $dataGateway->getFile($id);
    $path = "files/{$file->getTmpName()}";
    if (file_exists($path)) {
        $response = $response->withHeader('Content-Type', $file->getType());
        $response = $response->withHeader('Content-Description', 'File Transfer');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $file->getName());
        $response = $response->withHeader('Content-Transfer-Encoding', 'binary');
        $response = $response->withHeader('Expires', '0');
        $response = $response->withHeader('Cache-Control', 'must-revalidate');
        $response = $response->withHeader('Pragma', 'public');
        $response = $response->withHeader('Content-Length', $file->getSize());
        readfile($path);
        return $response;
    }
})->setName('download');

$app->get('/list', function (Request $request, Response $response) {
    $dataGateway = new FileDataGateway($this->db);
    //getting latest 100 files
    $files = $dataGateway->getAllFiles(100, 0);
    $response = $this->view->render($response, "list.html.twig", ["files" => $files]);
    return $response;
})->setName('list');

$app->run();
