# UM User Role History
Extension to Ultimate Member for display of User Role History of Role Changes and User Registration Date and Last Login Date.

## UM Settings Appearance -> Profile Menu
1. "User History Tab" - Enable/Disable
2. "Who can see User History Tab?" - UM Selections

## UM Settings General -> Users
1. "User Role History - Date/Time format" - Default is the WP date format "%s %s". Both PHP date and time local formats can be used.
2. "User Role History - Max number of Role changes saved" - Default is 100 saved Role changes per user.
3. "User Role History - Source type Translations" - Enter Source type translations comma separated like A:UM,B:Backend,C:Registration

## UM Profile Page
1. User Profile page menu Tab: User History
2. List headings: Date and Time,	Role Change,	Performed by,	Source

## Usermeta
1. Meta_key for each User ID <code>user_role_history</code>
2. Array with Date, Role ID, Admin and Type for each change.

## Translations or Text changes
1. Use the "Say What?" plugin with text domain ultimate-member
2. https://wordpress.org/plugins/say-what/

## Updates
1. Version 3.0.0 This plugin documentation
2. Version 3.1.0 Tested with UM 2.8.0
3. Version 3.2.0 Updated for UM 2.8.3

## Installation
1. Install by downloading the plugin ZIP file and install as a new Plugin, which you upload in WordPress -> Plugins -> Add New -> Upload Plugin.
2. Activate the Plugin: Ultimate Member - User Role History
