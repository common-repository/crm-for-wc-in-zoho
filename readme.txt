=== Integration of WooCommerce and Zoho CRM ===
Contributors: formsintegrations
Tags: Zoho with WooCommerce,  WooCommerce with Zoho, Zoho and WooCommerce, WooCommerce and Zoho, Zoho Integration, WooCommerce Integration, Zoho CRM, Integration, WooCommerce, CRM
Requires at least: 4.9.0
Tested up to: 6.6.1
Requires PHP: 5.6
Stable tag: 2.4
License: GPLv2 or later

== Description ==

Visit plugin's [website](https://formsintegrations.com/woocommerce-integration-with-zoho-crm)

This is an advanced integration plugin for WooCommerce and Zoho CRM. After authenticating your site with Zoho CRM, you can sync all for WooCommerce orders to Zoho CRM (Customers, Products, Sales Order) in a matter of few minutes. You just have to map the WooCommerce fields with the CRM fields once, and you are ready to go! This plugin allows you to create **custom value** to add data.

**Check out our step-by-step tutorial on Zoho CRM Integration with WooCommerce**

https://youtu.be/oLxz7WrbUvQ

When and order is processed through WooCommerce checkout page the data will be immediately send to Zoho CRM. The plugin also import old WooCommerce sales order and customer data in Zoho CRM in just one click and rest of the work will be done automatically as per your preference.


# How to Connect your Zoho CRM account with WooCommerce?

1. Install & activate this plugin for WooCommerce and Zoho CRM integration.
2. Click the  **Home**  button.
3. From  **Zoho CRM settings**  enter  **Integration Name**,  **Data Center**,**Homepage URL**  ,  **Authorized Redirect URL**.
4. To get client Id & secret information go to  **ZOHO API CONSOLE**  =>  **Add Client**=>  **Server-based application**  =>  **Create New Client**. Register all the information and get your client ID and secret information.
5. Authorize your Zoho CRM account for integrations with WordPress website.

# Organization select for WooCommerce and Zoho CRM integration:

For mapping the fields first enter your  **‘organization name‘**  to which your Zoho CRM account belongs. Now you can sync order details of your WooCommerce site with Zoho CRM by mapping the fields regarding your choice. You just have to map those fields only once, after that it will be executed by this WordPress plugin automatically.

# [Field Mapping Between Zoho CRM and WooCommerce Customer:](https://formsintegrations.com/woocommerce-integration-with-zoho-crm)

**1. Module :**  After integration with Zoho CRM users have to select modules in which all the customer data will be stored. Users can select  **Accounts**,  **Contacts**,  **Leads**, and  **Custom modules**.
**2. New Record :**  From this option, the user can create multiple related lists regarding the customer’s details.
**3. Field Map :** Users have to sync their WooCommerce checkout field with their CRM field. Suppose you want to set your customer’s first name as a contact name in Zoho CRM. Then select  **‘First Name‘**  in your WooCommerce checkout field & select  **‘Contact_Name‘**  in the Zoho CRM field. You can map multiple fields with CRM fields to organize your customer information. You can set  **‘Custom value‘**  from where you can send order notes according to the placed order.

# Field Mapping Between WooCommerce and Zoho CRM Customer Sales Order:

Sales order mapping is available for only pro version. When you are mapping your sales order one thing you have to keep in mind that you have to set a look up field on your Zoho CRM account. Through your selected look up field you could able to fetch the order records you need. While using the pro version, at first you have to select your desired  **lookup field**. Then based on look up field you can set Customer Module. As like the Customer option you have to set the module of sales order and also can create a related list on  **New Record field**. 

After mapping all your desired WooCommerce field with Zoho CRM field, when customer place any order from your WooCommerce site a corresponding sales order will be created automatically in Zoho CRM. You can customize your sales order area according to your need that how you want to show your customer’s sales order on your Zoho CRM account.

# Product Mapping Between Zoho CRM and WooCommerce:

From product settings user have to set the product on the module section where all the product details of respective customer will be store from user’s WooCommerce site. If any item of your WooCommerce site is not enlisted in Zoho CRM account then you have to entry that particular item or product. 

1.  Connect to Zoho CRM Account
2.  Field Mapping
3.  Whenever a order is created in WooCommerce, Customer & (Sales Order (pro)) will be created in Zoho CRM.
4.  Import Contact & (Customer and+ Sales Order option. You have to map which field of your WooCommerce site will sync with Zoho CRM field. One point to be noted, there will not arise any problem of duplicate data. Once the product is enlisted when multiple customers will order that particular item, all the details of the customers will be sorted regarding that product.

# [Zoho CRM Actions For Integration with WordPress (PRO)](https://formsintegrations.com/woocommerce-integration-with-zoho-crm):

WooCommerce integration with Zoho CRM has 8 additional action which helps user to control the data submission as well as to track their leads/Contacts more preciously. On CRM account. The actions available are:

1.  **Workflow :** Select this option to trigger the Zoho CRM workflow to relevant the selected module. If you didn’t select this option Zoho CRM workflow to relevant the selected module doesn’t work.
2. **Approval list :** To Select this option you can send file upload field data to Zoho CRM related list Attachments. You can also send file upload field data to Zoho CRM via normal field mapping. Please select the CRM file upload field from the drop-down which you want to send data.
3. **BluePrint :** Select this option to trigger the Zoho CRM blueprint to relevant the selected module. If you didn’t select this option Zoho CRM blueprint to relevant the selected module doesn’t work.
4. **GCLID :** Sends the click id of Google ads to Zoho CRM.
5. **Upsert Record :** Upon selecting the option YES, if a record with the identical value exists in Zoho CRM, it will be updated with the new values. If you select NO, a new record will be created in Zoho CRM. You can arrange fields in the order in which upsert should happen.  

#### Here’s how upsert works:

For example, you arrange the Email field before the Company field. When a form is submitted with the Company as formsintegrations and Email as  formsintegrations@xxx.com.

**a.**  First, it’s checked if the email  formsintegrations@xxx.com  exists in the CRM. If it does, then the mapped fields get updated to the record associated with that email address.
**b.**  In case of the email address  formsintegrations@xxx.com  doesn’t exist in your CRM, it’s checked if Bitcode exists under Company. If it does, then the mapped fields get updated to the record associated with that company name.
**c.**  If none of the field values exists, then a new record with all the mapped details is created under the mapped CRM module

6.  **Assignment Rules :** When you choose this option, Assignment Rules defined in Zoho CRM can be triggered when form entries are added to a CRM module. Please select the assignment rule from the drop-down which you want to trigger.
7. **Tag records :** When you choose this option, you can add a tag to the records that are pushed to Zoho CRM. It helps you to search for records in CRM by tag. There has a list of tag records in the dropdown. You can use Zoho CRM by default tag, form fields also can add a custom tag.
8.  **Record owner :** Select this option you can set a record owner of the sent record.


# How to old import data from WooCommerce to Zoho CRM?

In this area, you can set the duration, within which date you can import all data to zoho crm. For example, you are running a woocoomerce site since 2019 but you have been using this plugin since 2020. Then if you set Start Date – 1/01/19, End Date – 1/01/2020, Order Status – Confirmed & also set Import Type, after clicking  **‘Import Data‘**  only mentioned data will be imported to Zoho CRM. If you want to import all the confirmed data between 2019 to 2021 then just click the ‘Import Data’ button. You can select multiple order status to import orders.


# Integration of Zoho CRM and WooCommerce data Logs

1. All logs allow admin to see the newly created record in Zoho CRM from WordPress.
2. If user import any old data from your WordPress site, these data will also be displayed in the log.
3. Users can sort the columns according to their preference.
4. Users can copy all the API response of Zoho CRM sales order to clipboard.

Visit plugin's [website](https://formsintegrations.com/woocommerce-integration-with-zoho-crm)

== Installation ==

1. Download the plugin.
2. From the WordPress Admin Panel, click on Plugins => Add New.
3. Click on Upload, so you can directly upload your plugin zip file.
4. Use the browse button to select the plugin zip file that was downloaded, and then click on Install Now.
5. Once installed, click “Activate”.

== Changelog ==

= 2.4 =
* Tested with WordPress version 6.6.1

= 2.3 =
* Authorization redirect issue fixed

= 2.2 =
* Pro feature issue fixed

= 2.1 =
*Release Date - 20 December 2022*
* Fixed Authorization

= 2.0 =
* Fixed import data issue
* Fixed zoho crm sales order customer issue
* Fixed db log issue
* Added new toggle for integration enabled/disabled
* Improved the ux of the plugin

= 1.0.2 =
* plugin name changed from 'crm for woocommerce in zoho' to WC-2-ZCRM
* product & sales order feature added
= 1.0.1 =
* icon/logo updated
= 1.0.0 =
* Initial release of bit_wc_zoho_crm