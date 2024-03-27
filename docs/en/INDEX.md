# How it works

In the cms, you can set for a folder to be download-able. Browse to any folder in the assets section and click the edit button. 

If you do this, you can visit a link that goes like this: `/download-folder/download/MyPictures/ID`

The ID is the ID of the folder. MyPictures is the name of the folder. 

This will download a zip file of the folder.

It respects canView for the folder and all the files. 

# Under the bonnet

There is a controller with a method that is called `download`. This controller checks for the files in the folder and calculates the
size and creation time for each of the files, bypassing any resized images. This results in a hash.

If the hash is the same as the last time the folder was downloaded, the old zip file is returned.
Otherwise a new zip file is created and returned.

# Download Link for a folder.

To get the download link for a folder, you can use this method: `AllowFullFolderDownloadLink`.  
This method returns the link to the folder.  This is an absolute link so it is easy to add to emails, etc... 

