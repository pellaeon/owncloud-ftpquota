<?php
/**
 * ownCloud - ftpquota
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pellaeon Lin <pellaeon@cnmc.tw>
 * @copyright Pellaeon Lin 2015
 */

namespace OCA\FtpQuota\AppInfo;

use OCP\AppFramework\App;

class Application extends App {

	private $servercontainer;
	private $usermanager;

	public function callback(\OCP\Files\Node $node) {
		$nodepath = $node->getPath();
		$username = explode('/', $nodepath)[1];
		try {
			$user = $this->usermanager->get($username);
		} catch (\Exception $e) {
			\OCP\Util::writeLog('ftpquota', 'get user failed: '. $e->getMessage(), \OCP\Util::ERROR);
			return;
		}
		try {
			$homedir = $user->getHome();
		} catch (\Exception $e) {
			\OCP\Util::writeLog('ftpquota', 'get user home failed: '. $e->getMessage(), \OCP\Util::ERROR);
			return;
		}
		exec("/usr/local/sbin/pure-quotacheck -d ".escapeshellarg($homedir.'/files'), $output, $return_value);
		if ( $return_value !== 0 ) {
			\OCP\Util::writeLog('ftpquota', 'pure-quotacheck returned '.$return_value.' '.implode("\n", $output), \OCP\Util::ERROR);
		}
	}

	public function __construct(array $urlParams=array()){
		parent::__construct('ftpquota', $urlParams);

		$container = $this->getContainer();

		$this->servercontainer = $container->query('ServerContainer');

		$this->usermanager = $this->servercontainer->getUserManager();
		// following hooks won't be invoked since file upload or delete through web UI
		// won't trigger newFile() in  lib/private/files/node/folder.php,
		// so there is no event emitted
		$this->servercontainer->getRootFolder()->listen('\OC\Files', 'postWrite', [$this, 'callback']);
		$this->servercontainer->getRootFolder()->listen('\OC\Files', 'postDelete', [$this, 'callback']);
		/*$sc->getUserFolder($sc->getUserSession()->getUser()->getUID())->listen('\OC\Files', 'postDelete', function($node) {
			throw new \Exception($node->getPath());
			\OCP\Util::writeLog('ftpquota', $node->getPath(), \OCP\Util::WARN);

		});*/
	}
}
