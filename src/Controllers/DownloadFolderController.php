<?php

namespace Sunnysideup\DownloadFolder\Controllers;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use Sunnysideup\Download\Control\DownloadFile;
use Sunnysideup\Download\Control\Model\CachedDownload;
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
                urlencode($folder->Name),
                $folder->ID
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

    protected $hashableString = '';

    public function index()
    {
        $id = (int) $this->getRequest()->param('OtherID');
        if ($id !== 0) {
            $this->folder = Folder::get()->byID($id);
            if ($this->folder) {
                if($this->folder->AllowFullFolderDownload && $this->folder->canView()) {
                    $files = File::get()
                        ->filter('ParentID', $this->folder->ID)
                        ->exclude(['ClassName' => Folder::class]);
                    $this->setFilesToCollate($files);
                    return CachedDownload::inst(
                        $this->getFilename(),
                        $this->getTitle(),
                        $this->getMaxAgeInMinutes(),
                        $this->getDeleteOnFlush(),
                    )
                        ->getData($this->getCallbackToCreateDownloadFile());
                } else {
                    return $this->httpError(403, 'You do not have permission to download this folder.');
                }
            } else {
                return $this->httpError(404, 'Folder '.$this->getRequest()->param('ID').' can not be found');
            }
        } else {
            return $this->httpError(404);
        }
    }

    protected function setFilesToCollate(DataList $files)
    {
        $skippedExtensions = (array) $this->Config()->get('skipped_extensions');
        foreach ($files as $file) {
            if (! $file->canView()) {
                continue;
            }
            if(in_array($file->getExtension(), $skippedExtensions, true)) {
                continue;
            }
            $path = Controller::join_links(ASSETS_PATH, $file->getFilename());
            if(! file_exists($path)) {
                $path = Controller::join_links(PUBLIC_PATH, $file->getSourceURL(true));
                if(! file_exists($path)) {
                    $path = str_replace('public/assets/', 'public/assets/.protected/', $path);
                }
            }
            if(file_exists($path)) {
                if(! $file->Hash) {
                    user_error('No hash for file!'.$path);
                }
                $this->hashableStringArray[$path] = $file->Hash.'_'.strtotime($file->LastEdited);
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

    protected function getCallbackToCreateDownloadFile()
    {
        return function () {
            $zipFilePath = tempnam(sys_get_temp_dir(), 'folder') . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                foreach($this->filesToCollate as $path => $file) {
                    $zip->addFile($path, $file->Name);
                }
                $zip->close();

            } else {
                return user_error('could not create zip file!');
            }

        };
    }

    protected function getMaxAgeInMinutes(): ?int
    {
        return 99999999999999999; // set to null to use default
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
        return urlencode($this->folder->name);
    }

    protected function getTitle(): string
    {
        return 'Download of folder '.$this->folder;
    }

}
