# metrc views

The views module is incomplete. Use the metrc_migrate module instead.
The metrc_views module wraps the metrc API and exposes metrc data to views. The 
module is made up of two modules. The first is a base module which serves to 
wrap the metrc API. To that end, the following is provided:

* Global settings form for setting a metrc application.
* Per-user settings form to enable users of your site to connect their metrc
accounts. Users can revoke access at anytime. 
* Services for getting metrc data and stored access tokens per user.

The second module is metrc Views. It provides a views query plugin to expose 
metrc data of all connected users on your site to views. This allows you to 
build views as you're used to, but with data straight from the metrc API.

### metrc views module

1. Install the metrc (metrc) module
2. Assign the 'Authorize metrc account' permission to any user roles that 
should be allowed to connect their metrc accounts. 
3. Configure a metrc application by visiting /admin/config/services/metrc 
and following the directions there.
4. Have your users connect their metrc accounts on their metrc user settings 
page, eg. /user/1/metrc.

If you indend on using this module as a library for your own modules/themes 
metrc integration, your done. You can now make use of the services provided by
the base module (mainly metrc.user_key_manager and metrc.client). See the 
code documentation in those services for more details. If you'd like to use 
views to easily build lists of your users' metrc data, read on.

To use views to show connected users' metrc data, enable the metrc views 
(metrc_views) sub-module.

1. Install the metrc views (metrc_views) module.
2. Create a new view.
3. Under View Settings, choose one of the metrc data types for the 'Show' 
dropdown.
4. Continue as usual to create a view of metrc data.
5. Note that you can combine data from different metrc data types (
corresponding to the endpoints exposed by metrc APIs) by adding a Relationship.
Add a relationship in the usual way to be able to add more data per user.
6. If you know of a metrc endpoint that is not covered by the views 
integration, log an issue https://www.drupal.org/project/issues/metrc.
