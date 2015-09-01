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

	private function updateFTPQuota($dir) {
		exec("/usr/local/sbin/pure-quotacheck -d ".escapeshellarg($dir), $output, $return_value);
		\OCP\Util::writeLog('ftpquota', $dir);
		return $return_value;
	}

	public function callback(\OCP\Files\Node $node) {
		throw new \Exception($node->getPath());
		\OCP\Util::writeLog('ftpquota', $node->getPath(), \OCP\Util::WARN);
	}

	public function __construct(array $urlParams=array()){
		parent::__construct('ftpquota', $urlParams);

		$container = $this->getContainer();

		$sc = $container->query('ServerContainer');
		// following hooks won't be invoked since file upload or delete through web UI
		// won't trigger newFile() in  lib/private/files/node/folder.php,
		// so there is no event emitted
		$sc->getRootFolder()->listen('\OC\Files', 'postWrite', [$this, 'callback']);
		$sc->getRootFolder()->listen('\OC\Files', 'postDelete', [$this, 'callback']);
		/*$sc->getUserFolder($sc->getUserSession()->getUser()->getUID())->listen('\OC\Files', 'postDelete', function($node) {
			throw new \Exception($node->getPath());
			\OCP\Util::writeLog('ftpquota', $node->getPath(), \OCP\Util::WARN);

		});*/
	}
}
