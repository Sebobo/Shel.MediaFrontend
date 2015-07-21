Shel.MediaFrontend
==================

This is a extension package for Neos CMS 2.* and the TYPO3/Media package.

It allows you to import files from a folder into the Media management and contains some
Neos content element for browsing media in the frontend.

Warning: This package is not stable yet. So use at your own risk!

## Installation

### Add composer package

Run

    composer require shel/mediafrontend:dev-master
    
Add dependency also to your site package.
    
### Dependencies

See `composer.json`. Additionally the frontend uses `FontAwesome` by default. 
But you can change this by overriding the TypoScript in your own package.
    
### Add route

If you want to use the content elements add the following to `Configuration/Routes.yaml`:

    -
      name: 'Shel MediaFrontend'
      uriPattern: '<ShelMediaFrontendSubroutes>'
      subRoutes:
        'ShelMediaFrontendSubroutes':
          package: 'Shel.MediaFrontend'

## Import files

This command will import all files recursively from a folder and puts them into a
asset collection called "Imported".

Running the import command several times will not import the files again.
Each files sha1 hash is checked if it already exists as resource.

    ./flow import:files --path=... [--simulate] 
    
## Undo import

Removes all assets which are still part of the "Imported" asset collection.
You can use this command to cleanup your assets after you assigned your files to the
collections you want.

    ./flow import:purge [--simulate]

## Use the content elements

After installation you can add the new element `Asset directory` as content element to any page.

It allows you to configure a main asset collection to initially filter which assets to show.
You can also define more collections by which the user can filter.

Another filterbox shows the available tags of the selected main asset collection.
