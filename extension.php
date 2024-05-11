<?php

class FlareSolverrExtension extends Minz_Extension
{
    public function init()
    {
        $this->registerTranslates();
        $this->registerHook('post_update', array($this, 'postUpdateHook'));
    }

    public function install()
    {
        $filename = 'cloudsolver.php';
        $file_source = join_path($this->getPath(), $filename);
        $path_destination = join_path(PUBLIC_PATH, 'api');
        $file_destination = join_path($path_destination, $filename);

        if (!is_writable($path_destination)) {
            return 'server cannot write in ' . $path_destination;
        }

        if (file_exists($file_destination)) {
            if (!unlink($file_destination)) {
                return 'API file seems already existing but cannot be removed';
            }
        }

        if (!file_exists($file_source)) {
            return 'API file seems not existing in this extension. Try to download it again.';
        }

        if (!copy($file_source, $file_destination)) {
            return 'the API file has failed during installation.';
        }

        return true;
    }

    public function uninstall()
    {
        $filename = 'cloudsolver.php';
        $file_destination = join_path(PUBLIC_PATH, 'api', $filename);

        if (file_exists($file_destination) && !unlink($file_destination)) {
            return 'API file cannot be removed';
        }

        return true;
    }

    public function postUpdateHook()
    {
        $res = $this->install();

        if ($res !== true) {
            Minz_Log::warning('Problem during Flaresolverr API extension post update: ' . $res);
        }
    }

    public function getPluginEndpoint()
    {
    	return Minz_Url::display('/api/cloudsolver.php', 'html', true) . '?feed=';
    }

    public function handleConfigureAction()
    {
        if (Minz_Request::isPost()) {
            FreshRSS_Context::$system_conf->flaresolver_url = Minz_Request::param('flaresolver_url', "");
            FreshRSS_Context::$system_conf->save();
        }
    }

    public function getFlaresolverUrl()
    {
        if (FreshRSS_Context::$system_conf->flaresolver_url !== null)
            return FreshRSS_Context::$system_conf->flaresolver_url;

        return true;
    }
}
