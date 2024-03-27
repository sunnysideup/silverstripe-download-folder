<?php

namespace Sunnysideup\DownloadFolder\Extensions;

use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;

class DownloadFolderFileFormFactoryExtension extends Extension
{
    /**
     * Update Fields
     * @return FieldList
     */
    public function updateForm($form, $controller, $name, $context)
    {
        if (isset($context['Record'])) {
            /** @var File $record */
            $record = $context['Record'];
            if ($record && $record instanceof Folder) {
                $desc = 'If checked, all files in this folder can be downloaded as a single zip file. Please use with care.';
                $fields = $form->Fields();
                if ($record->AllowFullFolderDownload) {
                    $desc .= ' Try download now: <a href="' . $record->AllowFullFolderDownloadLink() . '">' . $record->AllowFullFolderDownloadLink() . '</a>';
                }
                $fields->push(
                    (CheckboxField::create('AllowFullFolderDownload', 'Allow Full Folder Download'))
                        ->setDescription($desc)
                );
            }
        }
    }
}
