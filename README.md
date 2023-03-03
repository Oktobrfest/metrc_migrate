# metrc- ﻿Metrc Track-and-Trace API Integration Module

Use the metrc_migrate sub-module instead of the views module.

### metrc migrate module

This module is geared for use by cultivators and not stores. It can still be used by other facility types, but will need to be modified/expanded upon. The customer migration requires you to get a list of all the facilities in the state(s) you operate in and import that into your application. This isn't strictly required, but lets everything come full circle when packages are sold by associating the transfers with a facility(node). This information is typically available with the state regulatory agency and may have to be re-arranged some to work with the existing configuration as every state has this information available in slightly different formats. 

1. Make sure you get your API key from Metrc.
2. Install the pre-requisites: Migrate, Migrate Tools, and Migrate Plus. If you will use the revision imports you'll need to get the entity revisions modules as well. 
3. Go into the config files and make those same data types and fields so you can have something to migrate the data into.
4. Enter the API keys as well as facility license number into the config page.
5. Start with the simplest imports such as units or lab_test_type to test if it works for you. If you have issues with it, try importing only a single field first. And make sure you can get data with postman.
6. Proceed with the other migrations in the order specified by the labels.
7. For more than the last few days of data. You will need to adjust the import date in the source modules (MetrcPackageHarvUrl.php...) files if you want to get data from the system for items that use a "LastModified" field.
8. Install cron migration and run all the migrations in order several times a day to keep everything up to date. 

This module gets the lab test data as paragraphs then runs a seperate migration to link those lab results to the harvests using the lab_allocation migration. That can then be used to link the packages to those lab tests in a view. Migrations that use word "detail" are for the purpose of having identical data types for the user to have additional fields which can be modified without directly altering the nodes/taxonomies that mirror metrc. Packages are also set to inactive/unpublished when they are sold.

### metrc migrate module (not the recommended route to take)

To use views to show connected users' metrc data, enable the metrc views (metrc_views) sub-module.

1. Install the metrc views (metrc_views) module.
2. Create a new view.
3. Under View Settings, choose one of the metrc data types for the 'Show' dropdown.
4. Continue as usual to create a view of metrc data.
