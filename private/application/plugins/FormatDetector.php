<?php

class BBBManager_Plugin_FormatDetector extends Zend_Controller_Plugin_Abstract {

    public function routeShutdown(\Zend_Controller_Request_Abstract $request) {
        $requestUri = $request->getPathInfo();

        if (strpos($requestUri, '.') !== false) {
            $rUriPieces = explode('/', $requestUri);
            $uriLastPiece = $rUriPieces[(count($rUriPieces) - 1)];
            $rUriFormat = explode('.', $uriLastPiece);

            $uriLastPieceData = $rUriFormat[0];
            $uriLastPieceFormat = $rUriFormat[1];

            if ($request->getParam('id', null) != null) {
                $request->setParam('id', $uriLastPieceData);
            } else {
                $request->setControllerName($uriLastPieceData);
            }
            $request->setParam('format', $uriLastPieceFormat);
        } elseif ($request->getModuleName() == 'api') {
            $request->setParam('format', 'json');
        }
    }

}
