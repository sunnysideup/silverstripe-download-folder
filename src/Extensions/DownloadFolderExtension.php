<?php

namespace Sunnysideup\DownloadFolder\Extensions;

use SilverStripe\ORM\DataExtension;
use Sunnysideup\DownloadFolder\Controllers\DownloadFolderController;

/**
 * Class \Sunnysideup\DownloadFolder\Extensions\DownloadFolderExtension
 *
 * @property Folder|DownloadFolderExtension $owner
 * @property bool $AllowFullFolderDownload
 */
class DownloadFolderExtension extends DataExtension
{
    private static $db = [
        'AllowFullFolderDownload' => 'Boolean',
    ];

    public function AllowFullFolderDownloadLink(): ?string
    {
        return DownloadFolderController::get_download_link($this->getOwner());
    }
}
