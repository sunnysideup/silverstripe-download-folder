# How it works

In the cms, you can set for a folder to be download-able.

If you do this, you can visit a link that goes like this: `/download-folder/download/Path/To/The/Folder/From/Assets`

The ID is the ID of the folder.

This will download a zip file of the folder.

# under the bonnet

There is a controller with a method that is called `download`. This controller checks for the files in the folder and calculates the
size and creation time for each of the files, bypassing any resized images. This results in a hash.

If the hash is the same as the last time the folder was downloaded, the old zip file is returned.
Otherwise a new zip file is created and returned.
