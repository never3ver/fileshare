<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../config.php';

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

        if (move_uploaded_file($_FILES['uploadfile']['tmp_name'], Helper::getFilePath($file->getTmpName()))) {
            $dataGateway->addFile($file);
            $url = $this->router->pathFor('list');
            $response = $response->withStatus(302)->withHeader('Location', $url);
            return $response;
        } else {
            $url = $this->router->pathFor('error');
            $response = $response->withStatus(302)->withHeader('Location', $url);
            return $response;
        }
    }
});

$app->get('/error', function (Request $request, Response $response) {
    $response = $this->view->render($response, "error.html.twig", ["error" => "Something has gone wrong."]);
    return $response;
})->setName('error');

$app->get('/file/{id}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    $dataGateway = new FileDataGateway($this->db);
    $file = $dataGateway->getFile($id);
    $getId3 = new getID3();
    $fileInfo = $getId3->Analyze(Helper::getFilePath($file->getTmpName()));
    if ($file->isImage()) {
        $imagePath = Helper::getImagePath($file->getTmpName());
        $response = $this->view->render($response, "file.html.twig", ["file" => $file, "fileInfo" => $fileInfo, "imagePath" => $imagePath]);
        return $response;
    }
    $response = $this->view->render($response, "file.html.twig", ["file" => $file, "fileInfo" => $fileInfo]);
    return $response;
})->setName('file');

$app->get('/download/{id}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    $dataGateway = new FileDataGateway($this->db);
    $file = $dataGateway->getFile($id);
    $path = Helper::getFilePath($file->getTmpName());
    if (is_readable($path)) {
        $fh = fopen($path, "rb");
        $stream = new \Slim\Http\Stream($fh); // create a new stream instance for the response body
        $response = $response->withHeader('Content-Type', $file->getType());
        $response = $response->withHeader('Content-Description', 'File Transfer');
        $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $file->getName());
        $response = $response->withHeader('Content-Transfer-Encoding', 'binary');
        $response = $response->withHeader('Expires', '0');
        $response = $response->withHeader('Cache-Control', 'must-revalidate');
        $response = $response->withHeader('Pragma', 'public');
        $response = $response->withHeader('Content-Length', $file->getSize());
        $response = $response->withBody($stream);
        return $response;
    } else {
        $response = $this->view->render($response, "error.html.twig", ["error" => "No file found"]);
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
