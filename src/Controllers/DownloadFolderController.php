<?php

namespace Sunnysideup\DownloadFolder\Controllers;

use SilverStripe\Forms\FieldList;
use DNADesign\Elemental\TopPage\DataExtension;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use ZipArchive;

class DownloadFolderController extends ContentController
{
    private static $url_segment = 'download-folder';

    public static function get_download_link($folder)
    {
        return Controller::join_links(
            Config::inst()->get(self::class, 'url_segment'),
            'download',
            urlencode($folder->Name),
            $folder->ID
        );
    }

    private static $allowed_actions = [
        'download'
    ];

    public function download($request)
    {
        $id = (int) $request->param('OtherID');
        if ($id) {
            $folder = Folder::get()->byID($id);
            if ($folder && $folder->AllowFullFolderDownload && $folder->canView()) {
                $files = File::get()
                    ->filter('ParentID', $folder->ID)
                    ->exclude(['ClassName' => Folder::class]);
                $zipFilePath = tempnam(sys_get_temp_dir(), 'folder') . '.zip';
                $zip = new ZipArchive();
                if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                    foreach ($files as $file) {
                        if (!$file->canView()) {
                            return $this->httpError(403);
                        }
                        $path = Controller::join_links(ASSETS_PATH, $file->getFilename());
                        $zip->addFile($path, $file->Name);
                    }
                    $zip->close();
                    // Set headers to force download
                    return $this->getResponse()
                        ->addHeader('Content-Disposition', 'attachment; filename="'.urlencode($folder->Name).'.zip"')
                        ->addHeader('Content-Type', 'application/zip')
                        ->addHeader('Content-Length', filesize($zipFilePath))
                        ->setBody(file_get_contents($zipFilePath))
                        ->output();
                } else {
                    return $this->httpError(500, 'Could not create zip file.');
                }
            } else {
                return $this->httpError(403);
            }
        } else {
            return $this->httpError(404);
        }
    }

}
