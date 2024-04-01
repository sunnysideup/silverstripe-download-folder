<?php

namespace Sunnysideup\DownloadFolder\Controllers;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Sunnysideup\Download\Api\CreateProtectedDownloadAsset;
use Sunnysideup\Download\Api\FilePathCalculator;
use Sunnysideup\Download\Control\DownloadFile;
use ZipArchive;

class DownloadFolderController extends DownloadFile
{
    private static $url_segment = 'download-folder';

    private static $skipped_extensions = [
        'zip',
    ];

    public static function get_download_link($folder): ?string
    {
        return Director::absoluteURL(
            Controller::join_links(
                Config::inst()->get(self::class, 'url_segment'),
                'download',
                $folder->ID,
                urlencode($folder->Name),
            )
        );
    }

    private static $allowed_actions = [
        'download',
    ];

    /**
     *
     *
     * @var null|Folder
     */
    protected $folder = null;
    protected $filesToCollate = [];

    protected $hashableStringArray = [];

    public function download()
    {
        if(!empty($this->filesToCollate)) {
            return parent::index();
        }
        return $this->httpError(404, 'No files to download');
    }

    protected function init()
    {
        parent::init();
        $id = (int) $this->getRequest()->param('ID');
        if ($id !== 0) {
            $this->folder = Folder::get()->byID($id);
            if ($this->folder) {
                if($this->folder->AllowFullFolderDownload && $this->folder->canView()) {
                    $this->setFilesToCollate();
                } else {
                    return $this->httpError(403, 'You do not have permission to download this folder.');
                }
            } else {
                return $this->httpError(404, 'Folder '.$this->getRequest()->param('ID').' can not be found');
            }
        } else {
            return $this->httpError(404, 'No folder ID provided');
        }
    }

    protected function setFilesToCollate()
    {
        $files = File::get()
        ->filter(
            [
                'ParentID' => $this->folder->ID,
                'ClassName:not' => Folder::class
            ]
        );
        $skippedExtensions = (array) $this->Config()->get('skipped_extensions');
        foreach ($files as $file) {
            if (! $file->canView()) {
                continue;
            }
            if(in_array($file->getExtension(), $skippedExtensions, true)) {
                continue;
            }
            $path = FilePathCalculator::get_path($file);
            if(file_exists($path)) {
                if(! $file->Hash) {
                    user_error('No hash for file!'.$path);
                }
                $this->hashableStringArray[$path] = $file->Hash.'_'.filesize($path).'_'.filemtime($path);
                $this->filesToCollate[$path] = $file;
            } else {
                user_error('Could not find file with ID '.$file->ID);
            }
        }
    }

    protected function calculateHashFromFiles(): string
    {
        return md5(implode('_', $this->hashableStringArray));
    }

    protected function getCallbackToCreateDownloadFile(): callable
    {
        return function () {
            $zipFilePath = tempnam(sys_get_temp_dir(), 'folder') . '.zip';
            $zip = new ZipArchive();
            $openZip = $zip->open($zipFilePath, ZipArchive::CREATE);
            if ($openZip === true) {
                foreach($this->filesToCollate as $path => $file) {
                    $zip->addFile($path, $file->Name);
                }
                $zip->close();
                CreateProtectedDownloadAsset::register_download_asset_from_local_path($zipFilePath, $this->getFileName());
                return file_get_contents($zipFilePath);
            } else {
                return user_error('could not create zip file!');
            }
        };
    }

    protected function getMaxAgeInMinutes(): ?int
    {
        return 365 * 24 * 60; // set to one year as it changes automatically if the hash of files changes.
    }

    protected function getDeleteOnFlush(): ?bool
    {
        return false; // set to null to use default
    }

    protected function getContentType(): string
    {
        return 'application/zip';
    }

    protected function getFileName(): string
    {
        return urlencode((string) $this->folder->Name). '_'.$this->calculateHashFromFiles().'.zip';
    }

    protected function getTitle(): string
    {
        return 'Download of folder '.$this->folder;
    }

    protected function getHasControlledAccess(): ?bool
    {
        return true; // set to null to use default
    }


}
