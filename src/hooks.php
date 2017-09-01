<?php

namespace Combi\Web;

use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

// Request 类添加 body parser
core::hook()->attach(\Combi\HOOK_READY, function() {

    Request::registerBodyParser(function (string $body): ?arrays {
        $result = json_decode($body, true);
        if (!is_array($result)) {
            return null;
        }
        return $result;
    }, 'application/json');

    Request::registerBodyParser(function (string $body): ?array {
        $backup = libxml_disable_entity_loader(true);
        $backup_errors = libxml_use_internal_errors(true);
        $result = simplexml_load_string($body);
        libxml_disable_entity_loader($backup);
        libxml_clear_errors();
        libxml_use_internal_errors($backup_errors);
        if ($result === false) {
            return null;
        }
        return $result;
    }, 'application/xml', 'text/xml');

    Request::registerBodyParser(function (string $body): ?array {
        parse_str($body, $data);
        return $data;
    }, 'application/x-www-form-urlencoded', 'multipart/form-data');

});
