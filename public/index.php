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

$container['FileDataGateway'] = function ($c) {
    $dataGateway = new FileDataGateway($c['db']);
    return $dataGateway;
};

$container['sphinx'] = function ($c) {
    $sphinx = new Sphinx($c['sphinxdb']);
    return $sphinx;
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
    if ($_FILES) {
        $file = new File();
        $files = $request->getUploadedFiles();
        $uploadedFile = $files['uploadfile'];
        $file->setName($uploadedFile->getClientFilename());
        $file->setSize($uploadedFile->getSize());
        $file->setType($uploadedFile->getClientMediaType());

        $tmpName = $this->FileDataGateway->createTmpName();

        if ($this->FileDataGateway->isTmpNameExisting($tmpName)) {
            $response = $this->view->render($response, 'error.html.twig');
            return $response;
        }

        $datePath = date('Y-m-d') . '/';
        $tmpName = $datePath . $tmpName;
        $file->setTmpName($tmpName);

        if (!is_dir(Helper::getFilePath('') . $datePath)) {
            mkdir(Helper::getFilePath('') . $datePath);
        }

        $uploadedFile->moveTo(Helper::getFilePath($file->getTmpName()));

        if (is_readable(Helper::getFilePath($file->getTmpName()))) {
            if ($file->isMedia()) {
                $fileInfo = new FileInfo($file);
                $json = $fileInfo->getJson();
                $file->setJson($json);
            }
            $this->FileDataGateway->addFile($file);
            $url = $this->router->pathFor('list');
            $response = $response->withStatus(302)->withHeader('Location', $url);
            return $response;
        } else {
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }
});

$app->get('/file/{id}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    $file = $this->FileDataGateway->getFile($id);

    if (file_exists(Helper::getFilePath($file->getTmpName()))) {
        $fileInfo = new FileInfo($file);
        $response = $this->view->render($response, 'file.html.twig', ['file' => $file, 'fileInfo' => $fileInfo]);
        return $response;
    } else {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }
})->setName('file');

$app->get('/download/{id}/{name}', function (Request $request, Response $response, $args) {
    $id = (int) $args['id'];
    $file = $this->FileDataGateway->getFile($id);
    $path = Helper::getFilePath($file->getTmpName());

    if (is_readable($path)) {
        if (in_array('mod_xsendfile', apache_get_modules())) {
            //download using xsendfile apache module:
            $response = $response->withHeader('X-SendFile', $path);
            $response = $response->withHeader('Content-Description', 'File Transfer');
            $response = $response->withHeader('Content-Disposition', 'attachment');
            return $response;
        } else {
            //universal way to download using php:
            $fh = fopen($path, 'rb');
            $stream = new \Slim\Http\Stream($fh); // create a new stream instance for the response body
            $response = $response->withHeader('Content-Type', 'application/octet-stream');
            $response = $response->withHeader('Content-Description', 'File Transfer');
            $response = $response->withHeader('Content-Disposition', 'attachment');
            $response = $response->withHeader('Content-Transfer-Encoding', 'binary');
            $response = $response->withHeader('Expires', '0');
            $response = $response->withHeader('Cache-Control', 'must-revalidate');
            $response = $response->withHeader('Pragma', 'public');
            $response = $response->withHeader('Content-Length', $file->getSize());
            $response = $response->withBody($stream);
            return $response;
        }
    } else {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }
})->setName('download');

$app->get('/search', function (Request $request, Response $response, $args) {
    $query = $request->getQueryParam('query');
    $files = $this->sphinx->searchBySphinx($query);
    $response = $this->view->render($response, 'search.html.twig', ['files' => $files, 'query' => $query]);
    return $response;
})->setName('search');

$app->get('/list', function (Request $request, Response $response) {
//getting latest 100 files
    $files = $this->FileDataGateway->getAllFiles(100, 0);
    $response = $this->view->render($response, 'list.html.twig', ['files' => $files]);
    return $response;
})->setName('list');

$app->run();
