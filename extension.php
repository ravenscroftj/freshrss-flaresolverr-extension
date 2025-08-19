<?php

require_once(__DIR__.'/lib.php');


class FlareSolverrExtension extends Minz_Extension
{
    public function init()
    {
        $this->registerTranslates();
        $this->registerHook('post_update', array($this, 'postUpdateHook'));
		$this->registerHook('api_misc', array($this, 'callApi'));
    }

    public function install()
    {

		// for old versions of freshrss we need to inject the cloudsolver.php file

		if(version_compare(FRESHRSS_VERSION, "1.26.4","<")){
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

	public function callApi( ) {
		run_flaresolverr_extension();
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

		if(version_compare(FRESHRSS_VERSION, "1.26.3",">")){
			return Minz_Url::display('/api/misc.php/'.rawurlencode($this->getName()).'?feed=', 'html', true);
		}else{
			return Minz_Url::display('/api/cloudsolver.php?feed=', 'html', true);

		}

    }

    public function handleConfigureAction()
    {

		$conf = FreshRSS_Context::systemConf();


        if (Minz_Request::isPost()) {
			$conf = FreshRSS_Context::systemConf();
            $conf->flaresolver_url = Minz_Request::paramString('flaresolver_url', "");
            $conf->flaresolver_maxTimeout = Minz_Request::paramInt("flaresolver_maxTimeout", "");
            $conf->save();
        }
    }

    public function getFlaresolverUrl()
    {

		if (FreshRSS_Context::systemConf()->hasParam('flaresolver_url')) {
			return FreshRSS_Context::systemConf()->flaresolver_url;
		}
        return "";
    }



    public function getMaxTimeout() {

		if ( FreshRSS_Context::systemConf()->hasParam('flaresolver_maxTimeout') ){
			return intval(FreshRSS_Context::systemConf()->flaresolver_maxTimeout);
		}
		return 60000;
    }
}
