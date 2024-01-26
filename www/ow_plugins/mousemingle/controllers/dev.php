<?php

class MOUSE_CTRL_Dev extends OW_ActionController
{
    public function staticUrl($params)
    {
        if( OW::getRequest()->isAjax() )
        {
            return;
        }

        $type = isset($params['type']) ? $params['type'] : '';
        $file = isset($params['file']) ? $params['file'] : '';
        $plugin = OW::getPluginManager()->getPlugin('mouse');

        $path = [
            'js'  => $plugin->getStaticJsDir() . $file,
            'css' => $plugin->getStaticDir() . "css/{$file}",
            'img' => $plugin->getStaticDir() . "img/{$file}",
        ];

        $content = '';

        if (isset($path[$type])) {
            // Set header application type based on file type
            switch ($type) {
                case 'js':
                    header('Content-Type: application/javascript');
                    break;
                case 'css':
                    header('Content-Type: text/css');
                    break;
                case 'img':
                    // Determine the image type and set the appropriate content type
                    $imageType = exif_imagetype($path['img']);
                    $allowedTypes = [
                        IMAGETYPE_JPEG => 'image/jpeg',
                        IMAGETYPE_PNG  => 'image/png',
                        IMAGETYPE_GIF  => 'image/gif',
                    ];

                    if (isset($allowedTypes[$imageType])) {
                        header("Content-Type: {$allowedTypes[$imageType]}");
                    } else {
                        // Handle unsupported image type or set a default content type
                        header('Content-Type: image/jpeg');
                    }
                    break;
            }

            // Get content from the file
            $content = file_get_contents($path[$type]);
        }

        exit($content);
    }

    /* public function login( $params )
    {
        $id = $params['id'] | null;

        if( $user = BOL_UserService::getInstance()->findUserById($id) )
        {
            OW::getUser()->login($user->id);

            $this->redirect(OW::getRouter()->getBaseUrl());
        }

        throw new Redirect404Exception();
    } */
}
