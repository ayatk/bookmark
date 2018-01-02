<?php
/**
 * Copyright (c) 2018 ayatk. licensed under the MIT License.
 *
 * Created by PhpStorm.
 * User: ayatk
 * Date: 2018/01/03
 * Time: 1:27
 */
require 'database.php';
require 'http.php';

preg_match('|' . dirname($_SERVER['SCRIPT_NAME']) . '/([\w%/]*)|', $_SERVER['REQUEST_URI'], $matches);
$paths = explode('/', $matches[1]);
$raw_id = isset($paths[1]) ? htmlspecialchars($paths[1]) : null;

// bookmark id バリデーション
$id = filter_var($raw_id, FILTER_VALIDATE_INT);
if ($raw_id != null && !$id) {
    http_response_code(StatusCodes::HTTP_BAD_REQUEST);
    return;
}

switch (strtolower($_SERVER['REQUEST_METHOD']) . ':' . $paths[0]) {
    case 'get:bookmark':
        if ($id) {
            json_response(execSQL("SELECT bookmark.id, bookmark.name, bookmark.url, tag.name AS tag FROM bookmark JOIN tag ON bookmark.id = tag.bid WHERE bookmark.id = $id"), StatusCodes::HTTP_OK);
        } else {
            json_response(execSQL('SELECT bookmark.id, bookmark.name, bookmark.url, tag.name AS tag FROM bookmark JOIN tag ON bookmark.id = tag.bid'), StatusCodes::HTTP_OK);
        }
        break;
    case 'post:bookmark':
        // Request Body 取得
        $body = json_decode(file_get_contents('php://input'));
        insertBookmark($body->name, $body->url, $body->tags);

        break;
    case 'put:bookmark':
        $body = json_decode(file_get_contents('php://input'));
        updateBookmark($id, $body->name, $body->url, $body->tags);

        break;
    case 'delete:bookmark':
        if (empty(execSQL("SELECT * FROM bookmark WHERE id=$id"))) {
            $code = StatusCodes::HTTP_NOT_FOUND;
            json_response(array("message" => StatusCodes::getMessageForCode($code)), $code);
        } else {
            execSQL("DELETE FROM bookmark WHERE id=$id");
            $code = StatusCodes::HTTP_OK;
            json_response(array("message" => "Delete successful"), $code);
        }
        break;
}

function json_response($obj, $code)
{
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    header("X-Content-Type-Options: nosniff");
    echo json_encode($obj);
}
