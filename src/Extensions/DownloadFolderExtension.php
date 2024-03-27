<?php

namespace Sunnysideup\DownloadFolder\Extensions;

use SilverStripe\Forms\FieldList;
use DNADesign\Elemental\TopPage\DataExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\DownloadFolder\Controllers\DownloadFolderController;

class DownloadFolderExtension extends DataExtension
{
    private static $db = [
        'AllowFullFolderDownload' => 'Boolean',
    ];

    public function AllowFullFolderDownloadLink()
    {
        return DownloadFolderController::get_download_link($this->getOwner());
    }
}
