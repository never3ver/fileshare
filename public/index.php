<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../config.php';

$app = new \Slim\App(['settings' => $config]);
$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ';dbname=' . $db['dbname'] . ';charset=UTF8', $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_mode='STRICT_ALL_TABLES'");
    return $pdo;
};

$container['sphinxdb'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['sphinx_host']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
};

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../templates');
    $view->addExtension(new \Slim\Views\TwigExtension(
            $container['router'], $container['request']->getUri()
    ));
    return $view;
};

$container['gateway'] = function ($c) {
    return new FileDataGateway($c['db']);
};

$container['sphinx'] = function ($c) {
    return new Sphinx($c['sphinxdb']);
};

$container['helper'] = function() {
    return new Helper();
};

/* $container['notFoundHandler'] = function ($c) {
  return function ($request, $response) use ($c) {
  return $c->view->render($response, 'error.html.twig')
  ->withStatus(404)
  ->withHeader('Content-Type', 'text/html');
  };
  }; */

// Render Twig template in route
$app->get('/', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'upload.html.twig');
    return $response;
})->setName('upload');

$app->post('/', function (Request $request, Response $response) {
    if (is_array($request->getUploadedFiles())) {
        $file = new File();
        $files = $request->getUploadedFiles();
        $uploadedFile = $files['uploadfile'];

        $tmpName = $this->helper->createTmpName($this->gateway);
        $file->setTmpName($tmpName);

        $file->setName($uploadedFile->getClientFilename());
        $file->setSize($uploadedFile->getSize());
        $file->setType($uploadedFile->getClientMediaType());

        $uploadedFile->moveTo($this->helper->getFilePath($file->getTmpName()));

        if (is_readable($this->helper->getFilePath($file->getTmpName()))) {
            if ($file->isMedia()) {
                $metadata = $fileInfo->getMetadata();
                $file->setMetadata($metadata);
            }
            $this->gateway->addFile($file);
            $id = $this->db->lastInsertId();
            $this->sphinx->addRtIndex($id, $file->getName());
            $url = $this->router->pathFor('file', ['id' => $id]);
            $response = $response->withStatus(302)->withHeader('Location', $url);
            return $response;
        } else {
//            throw new \Slim\Exception\NotFoundException($request, $response);
            $response = $response->withStatus(500)->withHeader('Content-Type', 'text/html');
            $response = $this->view->render($response, 'error.html.twig', ['message' => '500 Internal Server Error']);
        }
    }
});

$app->get('/file/{id}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    $file = $this->gateway->getFile($id);
    if (file_exists($this->helper->getFilePath($file->getTmpName()))) {
        $fileInfo = new FileInfo($file, $this);
        $helper = $this->helper;
        $response = $this->view->render($response, 'file.html.twig', ['file' => $file,
            'fileInfo' => $fileInfo,
            'helper' => $helper]);
        return $response;
    } else {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }
})->setName('file');

$app->get('/download/{id}/{name}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    if (!$this->gateway->isIdInDB($id)) {
        throw new Exception("There is no file with id=$id in database");
    }
    $file = $this->gateway->getFile($id);
    $path = $this->helper->getFilePath($file->getTmpName());
    $response = $this->helper->downloadFile($request, $response, $path, $file);
    return $response;
})->setName('download');

$app->get('/search', function (Request $request, Response $response, $args) {
    $query = $request->getQueryParam('query');
    $result = $this->sphinx->searchBySphinx($query);
    $files = [];
    foreach ($result as $value) {
        $files[] = $this->gateway->getFile($value->getId());
    }
    $response = $this->view->render($response, 'list.html.twig', ['files' => $files,
        'query' => $query,
        'helper' => $this->helper]);
    return $response;
})->setName('search');

$app->get('/list', function (Request $request, Response $response) {
//getting latest 100 files
    $response = $this->view->render($response, 'list.html.twig', ['files' => $this->gateway->getAllFiles(100, 0),
        'helper' => $this->helper]);
    return $response;
})->setName('list');

$app->run();
