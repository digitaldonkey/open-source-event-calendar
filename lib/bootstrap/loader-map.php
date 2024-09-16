<?php return [
  '0registered' =>
    [AI1EC_PATH . 'lib/bootstrap/loader-map.php' => TRUE],
  '1class_map' =>
    [
//      'bootstrap.registry.application' =>
//        [
//          'f' => AI1EC_PATH . 'lib/bootstrap/registry/application.php', // File
//          'c' => 'Ai1ec_Registry_Application', // Class
//          'i' => 'g',  // INSTANTIATE  factory, // Instantiator (
//                      // NEWINST = 'n'  Used to specify new instances every time.
//                      // GLOBALINST = 'g' Used to specify to treat as singleton.
//                      //                  To have only one instance of this object
//                      //                   in the application that will handle all calls.
//                      //    @see https://github.com/DesignPatternsPHP/DesignPatternsPHP/tree/main/Creational/Singleton
//          'r' => 'y', // Registry when the class.
//                      // isset($class_data['r']): Adds Registry to args at Registry (?)
//
//        ],
//      'Ai1ecIcsConnectorPlugin' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar-feed/ics.php',
//          'c' => 'Ai1ecIcsConnectorPlugin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Acl_Aco' =>
//        [
//          'f' => AI1EC_PATH . 'lib/acl/aco.php',
//          'c' => 'Ai1ec_Acl_Aco',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Base_Extension_Controller' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/extension.php',
//          'c' => 'Ai1ec_Base_Extension_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Base_License_Controller' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/extension-license.php',
//          'c' => 'Ai1ec_Base_License_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Bootstrap_Modal' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/legacy/bootstrap/modal.php',
//          'c' => 'Ai1ec_Bootstrap_Modal',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Cache_Interface' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/interface.php',
//          'c' => 'Ai1ec_Cache_Interface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Cache_Memory' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/memory.php',
//          'c' => 'Ai1ec_Cache_Memory',
//          'i' => 'n', // INSTANTIATE  new
//        ],
//      'Ai1ec_Cache_Not_Set_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/exception/not-set.php',
//          'c' => 'Ai1ec_Cache_Not_Set_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Cache_Strategy' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/abstract.php',
//          'c' => 'Ai1ec_Cache_Strategy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Cache_Strategy_Apc' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/apc.php',
//          'c' => 'Ai1ec_Cache_Strategy_Apc',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Cache_Strategy_Db' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/db.php',
//          'c' => 'Ai1ec_Cache_Strategy_Db',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Cache_Strategy_File' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/file.php',
//          'c' => 'Ai1ec_Cache_Strategy_File',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Cache_Strategy_Void' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/void.php',
//          'c' => 'Ai1ec_Cache_Strategy_Void',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Cache_Write_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/exception/write.php',
//          'c' => 'Ai1ec_Cache_Write_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Calendar_Avatar_Fallbacks' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/fallbacks.php',
//          'c' => 'Ai1ec_Calendar_Avatar_Fallbacks',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_Page' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/page.php',
//          'c' => 'Ai1ec_Calendar_Page',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_State' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar/state.php',
//          'c' => 'Ai1ec_Calendar_State',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_Updates' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar/updates.php',
//          'c' => 'Ai1ec_Calendar_Updates',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_View_Abstract' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/abstract.php',
//          'c' => 'Ai1ec_Calendar_View_Abstract',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_View_Agenda' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/agenda.php',
//          'c' => 'Ai1ec_Calendar_View_Agenda',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_View_Month' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/month.php',
//          'c' => 'Ai1ec_Calendar_View_Month',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_View_Oneday' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/oneday.php',
//          'c' => 'Ai1ec_Calendar_View_Oneday',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Calendar_View_Week' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/week.php',
//          'c' => 'Ai1ec_Calendar_View_Week',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Captcha_Nocaptcha_Provider' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/provider/nocaptcha.php',
//          'c' => 'Ai1ec_Captcha_Nocaptcha_Provider',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Captcha_Provider' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/provider.php',
//          'c' => 'Ai1ec_Captcha_Provider',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Captcha_Providers' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/providers.php',
//          'c' => 'Ai1ec_Captcha_Providers',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Captcha_Recaptcha_Provider' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/provider/recaptcha.php',
//          'c' => 'Ai1ec_Captcha_Recaptcha_Provider',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Clone_Renderer_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/clone/renderer-helper.php',
//          'c' => 'Ai1ec_Clone_Renderer_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Compatibility_Check' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/check.php',
//          'c' => 'Ai1ec_Compatibility_Check',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Compatibility_Cli' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/cli.php',
//          'c' => 'Ai1ec_Compatibility_Cli',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Compatibility_Memory' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/memory.php',
//          'c' => 'Ai1ec_Compatibility_Memory',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Compatibility_OutputBuffer' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/ob.php',
//          'c' => 'Ai1ec_Compatibility_OutputBuffer',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Compatibility_Xguard' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/xguard.php',
//          'c' => 'Ai1ec_Compatibility_Xguard',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Connector_Plugin' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar-feed/abstract.php',
//          'c' => 'Ai1ec_Connector_Plugin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Content_Filters' =>
//        [
//          'f' => AI1EC_PATH . 'lib/content/filter.php',
//          'c' => 'Ai1ec_Content_Filters',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Controller_Calendar_Feeds' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/calendar-feeds.php',
//          'c' => 'Ai1ec_Controller_Calendar_Feeds',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Controller_Content_Filter' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/content-filter.php',
//          'c' => 'Ai1ec_Controller_Content_Filter',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Cookie_Present_Dto' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cookie/dto.php',
//          'c' => 'Ai1ec_Cookie_Present_Dto',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Cookie_Utility' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cookie/utility.php',
//          'c' => 'Ai1ec_Cookie_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Css_Admin' =>
//        [
//          'f' => AI1EC_PATH . 'lib/css/admin.php',
//          'c' => 'Ai1ec_Css_Admin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Database_Applicator' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/applicator.php',
//          'c' => 'Ai1ec_Database_Applicator',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Database_Error' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/exception/database.php',
//          'c' => 'Ai1ec_Database_Error',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Database_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/helper.php',
//          'c' => 'Ai1ec_Database_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Database_Schema_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/exception/schema.php',
//          'c' => 'Ai1ec_Database_Schema_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Database_Update_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/exception/update.php',
//          'c' => 'Ai1ec_Database_Update_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Date_Converter' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/converter.php',
//          'c' => 'Ai1ec_Date_Converter',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Date_Date_Time_Zone' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/date-time-zone.php',
//          'c' => 'Ai1ec_Date_Date_Time_Zone',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Date_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/exception/date.php',
//          'c' => 'Ai1ec_Date_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Date_System' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/system.php',
//          'c' => 'Ai1ec_Date_System',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Date_Time' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/time.php',
//          'c' => 'Ai1ec_Date_Time',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Date_Timezone' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/timezone.php',
//          'c' => 'Ai1ec_Date_Timezone',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Date_Timezone_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/exception/timezone.php',
//          'c' => 'Ai1ec_Date_Timezone_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Dbi' =>
//        [
//          'f' => AI1EC_PATH . 'lib/dbi/dbi.php',
//          'c' => 'Ai1ec_Dbi',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Dbi_Utils' =>
//        [
//          'f' => AI1EC_PATH . 'lib/dbi/dbi-utils.php',
//          'c' => 'Ai1ec_Dbi_Utils',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Email_Notification' =>
//        [
//          'f' => AI1EC_PATH . 'lib/notification/email.php',
//          'c' => 'Ai1ec_Email_Notification',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Embeddable' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/embeddable.php',
//          'c' => 'Ai1ec_Embeddable',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Engine_Not_Set_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/exception/engine-not-set.php',
//          'c' => 'Ai1ec_Engine_Not_Set_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Event' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event.php',
//          'c' => 'Ai1ec_Event',
//          'i' => 'Ai1ec_Factory_Event.create_event_instance',
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Compatibility' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event-compatibility.php',
//          'c' => 'Ai1ec_Event_Compatibility',
//          'i' => 'Ai1ec_Factory_Event.create_event_instance',
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Create_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/event-create-exception.php',
//          'c' => 'Ai1ec_Event_Create_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Event_Creating' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/creating.php',
//          'c' => 'Ai1ec_Event_Creating',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Entity' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/entity.php',
//          'c' => 'Ai1ec_Event_Entity',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Instance' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/instance.php',
//          'c' => 'Ai1ec_Event_Instance',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Legacy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/legacy.php',
//          'c' => 'Ai1ec_Event_Legacy',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Not_Found_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/not-found-exception.php',
//          'c' => 'Ai1ec_Event_Not_Found_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Event_Parent' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/parent.php',
//          'c' => 'Ai1ec_Event_Parent',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Search' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/search.php',
//          'c' => 'Ai1ec_Event_Search',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Event_Taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/taxonomy.php',
//          'c' => 'Ai1ec_Event_Taxonomy',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Factory_Event' =>
//        [
//          'f' => AI1EC_PATH . 'lib/factory/event.php',
//          'c' => 'Ai1ec_Factory_Event',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Factory_Html' =>
//        [
//          'f' => AI1EC_PATH . 'lib/factory/html.php',
//          'c' => 'Ai1ec_Factory_Html',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Factory_Strategy' =>
//        [
//          'f' => AI1EC_PATH . 'lib/factory/strategy.php',
//          'c' => 'Ai1ec_Factory_Strategy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_File_Abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/abstract.php',
//          'c' => 'Ai1ec_File_Abstract',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_File_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/exception.php',
//          'c' => 'Ai1ec_File_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_File_Image' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/image.php',
//          'c' => 'Ai1ec_File_Image',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_File_Less' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/less.php',
//          'c' => 'Ai1ec_File_Less$this->lessphp_controller',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_File_Not_Found_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/exception/file-not-found.php',
//          'c' => 'Ai1ec_File_Not_Found_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_File_Php' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/php.php',
//          'c' => 'Ai1ec_File_Php',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_File_Twig' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/twig.php',
//          'c' => 'Ai1ec_File_Twig',
//          'i' => 'n', // INSTANTIATE  new
//        ],
//      'Ai1ec_Filesystem_Checker' =>
//        [
//          'f' => AI1EC_PATH . 'lib/filesystem/checker.php',
//          'c' => 'Ai1ec_Filesystem_Checker',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Filesystem_Misc' =>
//        [
//          'f' => AI1EC_PATH . 'lib/filesystem/misc.php',
//          'c' => 'Ai1ec_Filesystem_Misc',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Authors' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/auth_ids.php',
//          'c' => 'Ai1ec_Filter_Authors',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Categories' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/cat_ids.php',
//          'c' => 'Ai1ec_Filter_Categories',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Int' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/int.php',
//          'c' => 'Ai1ec_Filter_Int',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Interface' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/interface.php',
//          'c' => 'Ai1ec_Filter_Interface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Filter_Posts' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/post_ids.php',
//          'c' => 'Ai1ec_Filter_Posts',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Posts_By_Instance' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/instance_ids.php',
//          'c' => 'Ai1ec_Filter_Posts_By_Instance',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Tags' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/tag_ids.php',
//          'c' => 'Ai1ec_Filter_Tags',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Filter_Taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/taxonomy.php',
//          'c' => 'Ai1ec_Filter_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Frequency_Utility' =>
//        [
//          'f' => AI1EC_PATH . 'lib/parser/frequency.php',
//          'c' => 'Ai1ec_Frequency_Utility',
//          'i' => 'n', // INSTANTIATE  new
//        ],
//      'Ai1ec_Html_Element' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/legacy/abstract/html-element.php',
//          'c' => 'Ai1ec_Html_Element',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Element_Calendar_Page_Selector' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/calendar-page-selector.php',
//          'c' => 'Ai1ec_Html_Element_Calendar_Page_Selector',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Element_Enabled_Views' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/enabled-views.php',
//          'c' => 'Ai1ec_Html_Element_Enabled_Views',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Element_Href' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/href.php',
//          'c' => 'Ai1ec_Html_Element_Href',
//          'i' => 'Ai1ec_Factory_Html.create_href_helper_instance',
//        ],
//      'Ai1ec_Html_Element_Interface' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/interface.php',
//          'c' => 'Ai1ec_Html_Element_Interface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Html_Element_Settings' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/abstract.php',
//          'c' => 'Ai1ec_Html_Element_Settings',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/exception.php',
//          'c' => 'Ai1ec_Html_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Html_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/helper.php',
//          'c' => 'Ai1ec_Html_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Html_Setting_Cache' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/cache.php',
//          'c' => 'Ai1ec_Html_Setting_Cache',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Setting_Html' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/html.php',
//          'c' => 'Ai1ec_Html_Setting_Html',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Setting_Input' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/input.php',
//          'c' => 'Ai1ec_Html_Setting_Input',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Setting_Renderer' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting-renderer.php',
//          'c' => 'Ai1ec_Html_Setting_Renderer',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Setting_Select' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/select.php',
//          'c' => 'Ai1ec_Html_Setting_Select',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Setting_Tags_Categories' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/tags-categories.php',
//          'c' => 'Ai1ec_Html_Setting_Tags_Categories',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Setting_Textarea' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/textarea.php',
//          'c' => 'Ai1ec_Html_Setting_Textarea',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Html_Settings_Checkbox' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/checkbox.php',
//          'c' => 'Ai1ec_Html_Settings_Checkbox',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_I18n' =>
//        [
//          'f' => AI1EC_PATH . 'lib/p28n/i18n.php',
//          'c' => 'Ai1ec_I18n',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Ics_Import_Export_Engine' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/ics.php',
//          'c' => 'Ai1ec_Ics_Import_Export_Engine',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Import_Export_Controller' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/import-export.php',
//          'c' => 'Ai1ec_Import_Export_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Import_Export_Engine' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/interface/import-export-engine.php',
//          'c' => 'Ai1ec_Import_Export_Engine',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Import_Export_Service_Engine' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/interface/import-export-service-engine.php',
//          'c' => 'Ai1ec_Import_Export_Service_Engine',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Invalid_Argument_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/invalid-argument-exception.php',
//          'c' => 'Ai1ec_Invalid_Argument_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Less_Lessphp' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/lessphp.php',
//          'c' => 'Ai1ec_Less_Lessphp',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Less_Variable' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/abstract.php',
//          'c' => 'Ai1ec_Less_Variable',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Less_Variable_Color' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/color.php',
//          'c' => 'Ai1ec_Less_Variable_Color',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Less_Variable_Font' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/font.php',
//          'c' => 'Ai1ec_Less_Variable_Font',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Less_Variable_Size' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/size.php',
//          'c' => 'Ai1ec_Less_Variable_Size',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Localization_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/p28n/wpml.php',
//          'c' => 'Ai1ec_Localization_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Meta' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/meta.php',
//          'c' => 'Ai1ec_Meta',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Meta_Post' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/meta-post.php',
//          'c' => 'Ai1ec_Meta_Post',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Meta_User' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/meta-user.php',
//          'c' => 'Ai1ec_Meta_User',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Notification' =>
//        [
//          'f' => AI1EC_PATH . 'lib/notification/abstract.php',
//          'c' => 'Ai1ec_Notification',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Notification_Admin' =>
//        [
//          'f' => AI1EC_PATH . 'lib/notification/admin.php',
//          'c' => 'Ai1ec_Notification_Admin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Parse_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/exception.php',
//          'c' => 'Ai1ec_Parse_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Parser_Date' =>
//        [
//          'f' => AI1EC_PATH . 'lib/parser/date.php',
//          'c' => 'Ai1ec_Parser_Date',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Persistence_Context' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/persistence-context.php',
//          'c' => 'Ai1ec_Persistence_Context',
//          'i' => 'Ai1ec_Factory_Strategy.create_persistence_context',
//        ],
//      'Ai1ec_Post_Content_Check' =>
//        [
//          'f' => AI1EC_PATH . 'lib/post/content.php',
//          'c' => 'Ai1ec_Post_Content_Check',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Post_Custom_Type' =>
//        [
//          'f' => AI1EC_PATH . 'lib/post/custom-type.php',
//          'c' => 'Ai1ec_Post_Custom_Type',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Primitive_Array' =>
//        [
//          'f' => AI1EC_PATH . 'lib/primitive/array.php',
//          'c' => 'Ai1ec_Primitive_Array',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Primitive_Int' =>
//        [
//          'f' => AI1EC_PATH . 'lib/primitive/int.php',
//          'c' => 'Ai1ec_Primitive_Int',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Query_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/query/helper.php',
//          'c' => 'Ai1ec_Query_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Recurrence_Rule' =>
//        [
//          'f' => AI1EC_PATH . 'lib/recurrence/rule.php',
//          'c' => 'Ai1ec_Recurrence_Rule',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Renderable' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/legacy/abstract/interface.php',
//          'c' => 'Ai1ec_Renderable',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Rewrite_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/rewrite/helper.php',
//          'c' => 'Ai1ec_Rewrite_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Robots_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/robots/helper.php',
//          'c' => 'Ai1ec_Robots_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Router' =>
//        [
//          'f' => AI1EC_PATH . 'lib/routing/router.php',
//          'c' => 'Ai1ec_Router',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Scheduling_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/scheduling/exception.php',
//          'c' => 'Ai1ec_Scheduling_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Script_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/script/helper.php',
//          'c' => 'Ai1ec_Script_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Settings' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/settings.php',
//          'c' => 'Ai1ec_Settings',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Settings_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/settings/exception.php',
//          'c' => 'Ai1ec_Settings_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Settings_View' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/settings-view.php',
//          'c' => 'Ai1ec_Settings_View',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Shutdown_Controller' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/shutdown.php',
//          'c' => 'Ai1ec_Shutdown_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/taxonomy.php',
//          'c' => 'Ai1ec_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Template_Link_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/template/link/helper.php',
//          'c' => 'Ai1ec_Template_Link_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Theme_Compiler' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/compiler.php',
//          'c' => 'Ai1ec_Theme_Compiler',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Theme_List' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/list.php',
//          'c' => 'Ai1ec_Theme_List',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Theme_Loader' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/loader.php',
//          'c' => 'Ai1ec_Theme_Loader',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Theme_Search' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/search.php',
//          'c' => 'Ai1ec_Theme_Search',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Time_I18n_Utility' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/time-i18n.php',
//          'c' => 'Ai1ec_Time_I18n_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Time_Utility' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/legacy.php',
//          'c' => 'Ai1ec_Time_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Twig_Ai1ec_Extension' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/ai1ec-extension.php',
//          'c' => 'Ai1ec_Twig_Ai1ec_Extension',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Twig_Cache' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/cache.php',
//          'c' => 'Ai1ec_Twig_Cache',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Twig_Environment' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/environment.php',
//          'c' => 'Ai1ec_Twig_Environment',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Twig_Loader_Filesystem' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/loader.php',
//          'c' => 'Ai1ec_Twig_Loader_Filesystem',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Uri' =>
//        [
//          'f' => AI1EC_PATH . 'lib/routing/uri.php',
//          'c' => 'Ai1ec_Uri',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Validation_Utility' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/validator.php',
//          'c' => 'Ai1ec_Validation_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_Validator' =>
//        [
//          'f' => AI1EC_PATH . 'lib/validator/abstract.php',
//          'c' => 'Ai1ec_Validator',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Validator_Numeric_Or_Default' =>
//        [
//          'f' => AI1EC_PATH . 'lib/validator/numeric.php',
//          'c' => 'Ai1ec_Validator_Numeric_Or_Default',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'Ai1ec_Value_Not_Valid_Exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/validator/exception.php',
//          'c' => 'Ai1ec_Value_Not_Valid_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_View_Admin_EventCategory' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/admin/event-category.php',
//          'c' => 'Ai1ec_View_Admin_EventCategory',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Admin_Widget' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/widget.php',
//          'c' => 'Ai1ec_View_Admin_Widget',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_View_Calendar_Shortcode' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/shortcode.php',
//          'c' => 'Ai1ec_View_Calendar_Shortcode',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Calendar_SubscribeButton' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/subscribe-button.php',
//          'c' => 'Ai1ec_View_Calendar_SubscribeButton',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_View_Calendar_Taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/taxonomy.php',
//          'c' => 'Ai1ec_View_Calendar_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Avatar' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/avatar.php',
//          'c' => 'Ai1ec_View_Event_Avatar',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Color' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/color.php',
//          'c' => 'Ai1ec_View_Event_Color',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Content' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/content.php',
//          'c' => 'Ai1ec_View_Event_Content',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Location' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/location.php',
//          'c' => 'Ai1ec_View_Event_Location',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Post' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/post.php',
//          'c' => 'Ai1ec_View_Event_Post',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Single' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/single.php',
//          'c' => 'Ai1ec_View_Event_Single',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/taxonomy.php',
//          'c' => 'Ai1ec_View_Event_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_View_Event_Ticket' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/ticket.php',
//          'c' => 'Ai1ec_View_Event_Ticket',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_View_Event_Time' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/time.php',
//          'c' => 'Ai1ec_View_Event_Time',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'Ai1ec_Wp_Uri_Helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/routing/uri-helper.php',
//          'c' => 'Ai1ec_Wp_Uri_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ec_XML_Builder' =>
//        [
//          'f' => AI1EC_PATH . 'lib/xml/builder.php',
//          'c' => 'Ai1ec_XML_Builder',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'Ai1ecdm_Datetime_Migration' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/datetime-migration.php',
//          'c' => 'Ai1ecdm_Datetime_Migration',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'acl.aco' =>
//        [
//          'f' => AI1EC_PATH . 'lib/acl/aco.php',
//          'c' => 'Ai1ec_Acl_Aco',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'cache.exception.not-set' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/exception/not-set.php',
//          'c' => 'Ai1ec_Cache_Not_Set_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'cache.exception.write' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/exception/write.php',
//          'c' => 'Ai1ec_Cache_Write_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'cache.interface' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/interface.php',
//          'c' => 'Ai1ec_Cache_Interface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'cache.memory' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/memory.php',
//          'c' => 'Ai1ec_Cache_Memory',
//          'i' => 'n', // INSTANTIATE  new
//        ],
//      'cache.strategy.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/abstract.php',
//          'c' => 'Ai1ec_Cache_Strategy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'cache.strategy.apc' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/apc.php',
//          'c' => 'Ai1ec_Cache_Strategy_Apc',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'cache.strategy.db' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/db.php',
//          'c' => 'Ai1ec_Cache_Strategy_Db',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'cache.strategy.file' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/file.php',
//          'c' => 'Ai1ec_Cache_Strategy_File',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'cache.strategy.persistence-context' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/persistence-context.php',
//          'c' => 'Ai1ec_Persistence_Context',
//          'i' => 'Ai1ec_Factory_Strategy.create_persistence_context',
//        ],
//      'cache.strategy.void' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cache/strategy/void.php',
//          'c' => 'Ai1ec_Cache_Strategy_Void',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'calendar-feed.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar-feed/abstract.php',
//          'c' => 'Ai1ec_Connector_Plugin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'calendar-feed.ics' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar-feed/ics.php',
//          'c' => 'Ai1ecIcsConnectorPlugin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'calendar.state' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar/state.php',
//          'c' => 'Ai1ec_Calendar_State',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'calendar.updates' =>
//        [
//          'f' => AI1EC_PATH . 'lib/calendar/updates.php',
//          'c' => 'Ai1ec_Calendar_Updates',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'calendarComponent' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'calendarComponent',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'captcha.provider' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/provider.php',
//          'c' => 'Ai1ec_Captcha_Provider',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'captcha.provider.nocaptcha' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/provider/nocaptcha.php',
//          'c' => 'Ai1ec_Captcha_Nocaptcha_Provider',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'captcha.provider.recaptcha' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/provider/recaptcha.php',
//          'c' => 'Ai1ec_Captcha_Recaptcha_Provider',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'captcha.providers' =>
//        [
//          'f' => AI1EC_PATH . 'lib/captcha/providers.php',
//          'c' => 'Ai1ec_Captcha_Providers',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'clone.renderer-helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/clone/renderer-helper.php',
//          'c' => 'Ai1ec_Clone_Renderer_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'compatibility.check' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/check.php',
//          'c' => 'Ai1ec_Compatibility_Check',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'compatibility.cli' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/cli.php',
//          'c' => 'Ai1ec_Compatibility_Cli',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'compatibility.memory' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/memory.php',
//          'c' => 'Ai1ec_Compatibility_Memory',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'compatibility.ob' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/ob.php',
//          'c' => 'Ai1ec_Compatibility_OutputBuffer',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'compatibility.xguard' =>
//        [
//          'f' => AI1EC_PATH . 'lib/compatibility/xguard.php',
//          'c' => 'Ai1ec_Compatibility_Xguard',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'content.filter' =>
//        [
//          'f' => AI1EC_PATH . 'lib/content/filter.php',
//          'c' => 'Ai1ec_Content_Filters',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'controller.calendar-feeds' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/calendar-feeds.php',
//          'c' => 'Ai1ec_Controller_Calendar_Feeds',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'controller.content-filter' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/content-filter.php',
//          'c' => 'Ai1ec_Controller_Content_Filter',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'controller.exception.engine-not-set' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/exception/engine-not-set.php',
//          'c' => 'Ai1ec_Engine_Not_Set_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'controller.exception.file-not-found' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/exception/file-not-found.php',
//          'c' => 'Ai1ec_File_Not_Found_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'controller.extension' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/extension.php',
//          'c' => 'Ai1ec_Base_Extension_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'controller.extension-license' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/extension-license.php',
//          'c' => 'Ai1ec_Base_License_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'controller.import-export' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/import-export.php',
//          'c' => 'Ai1ec_Import_Export_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'controller.shutdown' =>
//        [
//          'f' => AI1EC_PATH . 'app/controller/shutdown.php',
//          'c' => 'Ai1ec_Shutdown_Controller',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'cookie.dto' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cookie/dto.php',
//          'c' => 'Ai1ec_Cookie_Present_Dto',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'cookie.utility' =>
//        [
//          'f' => AI1EC_PATH . 'lib/cookie/utility.php',
//          'c' => 'Ai1ec_Cookie_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'css.admin' =>
//        [
//          'f' => AI1EC_PATH . 'lib/css/admin.php',
//          'c' => 'Ai1ec_Css_Admin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'database.applicator' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/applicator.php',
//          'c' => 'Ai1ec_Database_Applicator',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'database.datetime-migration' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/datetime-migration.php',
//          'c' => 'Ai1ecdm_Datetime_Migration',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'database.exception.database' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/exception/database.php',
//          'c' => 'Ai1ec_Database_Error',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'database.exception.schema' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/exception/schema.php',
//          'c' => 'Ai1ec_Database_Schema_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'database.exception.update' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/exception/update.php',
//          'c' => 'Ai1ec_Database_Update_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'database.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/database/helper.php',
//          'c' => 'Ai1ec_Database_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'date.converter' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/converter.php',
//          'c' => 'Ai1ec_Date_Converter',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'date.date-time-zone' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/date-time-zone.php',
//          'c' => 'Ai1ec_Date_Date_Time_Zone',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'date.exception.date' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/exception/date.php',
//          'c' => 'Ai1ec_Date_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'date.exception.timezone' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/exception/timezone.php',
//          'c' => 'Ai1ec_Date_Timezone_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'date.legacy' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/legacy.php',
//          'c' => 'Ai1ec_Time_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'date.system' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/system.php',
//          'c' => 'Ai1ec_Date_System',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'date.time' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/time.php',
//          'c' => 'Ai1ec_Date_Time',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'date.time-i18n' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/time-i18n.php',
//          'c' => 'Ai1ec_Time_I18n_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'date.timezone' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/timezone.php',
//          'c' => 'Ai1ec_Date_Timezone',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'date.validator' =>
//        [
//          'f' => AI1EC_PATH . 'lib/date/validator.php',
//          'c' => 'Ai1ec_Validation_Utility',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'dbi.dbi' =>
//        [
//          'f' => AI1EC_PATH . 'lib/dbi/dbi.php',
//          'c' => 'Ai1ec_Dbi',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'dbi.dbi-utils' =>
//        [
//          'f' => AI1EC_PATH . 'lib/dbi/dbi-utils.php',
//          'c' => 'Ai1ec_Dbi_Utils',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'factory.event' =>
//        [
//          'f' => AI1EC_PATH . 'lib/factory/event.php',
//          'c' => 'Ai1ec_Factory_Event',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'factory.html' =>
//        [
//          'f' => AI1EC_PATH . 'lib/factory/html.php',
//          'c' => 'Ai1ec_Factory_Html',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'factory.strategy' =>
//        [
//          'f' => AI1EC_PATH . 'lib/factory/strategy.php',
//          'c' => 'Ai1ec_Factory_Strategy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'filesystem.checker' =>
//        [
//          'f' => AI1EC_PATH . 'lib/filesystem/checker.php',
//          'c' => 'Ai1ec_Filesystem_Checker',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'filesystem.misc' =>
//        [
//          'f' => AI1EC_PATH . 'lib/filesystem/misc.php',
//          'c' => 'Ai1ec_Filesystem_Misc',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'html.element.href' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/href.php',
//          'c' => 'Ai1ec_Html_Element_Href',
//          'i' => 'Osec\Html\HtmlFactory.create_href_helper_instance',
//        ],
//      'html.element.interface' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/interface.php',
//          'c' => 'Ai1ec_Html_Element_Interface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'html.element.legacy.abstract.html-element' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/legacy/abstract/html-element.php',
//          'c' => 'Ai1ec_Html_Element',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'html.element.legacy.abstract.interface' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/legacy/abstract/interface.php',
//          'c' => 'Ai1ec_Renderable',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'html.element.legacy.bootstrap.modal' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/legacy/bootstrap/modal.php',
//          'c' => 'Ai1ec_Bootstrap_Modal',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting-renderer' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting-renderer.php',
//          'c' => 'Ai1ec_Html_Setting_Renderer',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'html.element.setting.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/abstract.php',
//          'c' => 'Ai1ec_Html_Element_Settings',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'html.element.setting.cache' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/cache.php',
//          'c' => 'Ai1ec_Html_Setting_Cache',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting.calendar-page-selector' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/calendar-page-selector.php',
//          'c' => 'Ai1ec_Html_Element_Calendar_Page_Selector',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'html.element.setting.checkbox' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/checkbox.php',
//          'c' => 'Ai1ec_Html_Settings_Checkbox',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting.enabled-views' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/enabled-views.php',
//          'c' => 'Ai1ec_Html_Element_Enabled_Views',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'html.element.setting.html' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/html.php',
//          'c' => 'Ai1ec_Html_Setting_Html',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting.input' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/input.php',
//          'c' => 'Ai1ec_Html_Setting_Input',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting.select' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/select.php',
//          'c' => 'Ai1ec_Html_Setting_Select',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting.tags-categories' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/tags-categories.php',
//          'c' => 'Ai1ec_Html_Setting_Tags_Categories',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.element.setting.textarea' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/element/setting/textarea.php',
//          'c' => 'Ai1ec_Html_Setting_Textarea',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'html.exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/exception.php',
//          'c' => 'Ai1ec_Html_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'html.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/html/helper.php',
//          'c' => 'Ai1ec_Html_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//  iCalcnv seems not to be in use at all.
//  Also availabe at https://sourceforge.net/projects/icalcreator/files/iCalcnv/iCalcnv-3.0/ (Current is 3.2)
//
//
//      'iCalcnv' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcnv-3.0/iCalcnv.class.php',
//          'c' => 'iCalcnv',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'import-export.exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/exception.php',
//          'c' => 'Ai1ec_Parse_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'import-export.ics' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/ics.php',
//          'c' => 'Ai1ec_Ics_Import_Export_Engine',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'import-export.interface.import-export-engine' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/interface/import-export-engine.php',
//          'c' => 'Ai1ec_Import_Export_Engine',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'import-export.interface.import-export-service-engine' =>
//        [
//          'f' => AI1EC_PATH . 'lib/import-export/interface/import-export-service-engine.php',
//          'c' => 'Ai1ec_Import_Export_Service_Engine',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'less.lessphp' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/lessphp.php',
//          'c' => 'Ai1ec_Less_Lessphp',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'less.variable.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/abstract.php',
//          'c' => 'Ai1ec_Less_Variable',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'less.variable.color' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/color.php',
//          'c' => 'Ai1ec_Less_Variable_Color',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'less.variable.font' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/font.php',
//          'c' => 'Ai1ec_Less_Variable_Font',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'less.variable.size' =>
//        [
//          'f' => AI1EC_PATH . 'lib/less/variable/size.php',
//          'c' => 'Ai1ec_Less_Variable_Size',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.event' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event.php',
//          'c' => 'Ai1ec_Event',
//          'i' => 'Ai1ec_Factory_Event.create_event_instance',
//          'r' => 'y',
//        ],
//      'model.event-compatibility' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event-compatibility.php',
//          'c' => 'Ai1ec_Event_Compatibility',
//          'i' => 'Ai1ec_Factory_Event.create_event_instance',
//          'r' => 'y',
//        ],
//      'model.event.creating' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/creating.php',
//          'c' => 'Ai1ec_Event_Creating',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.event.entity' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/entity.php',
//          'c' => 'Ai1ec_Event_Entity',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.event.event-create-exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/event-create-exception.php',
//          'c' => 'Ai1ec_Event_Create_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'model.event.instance' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/instance.php',
//          'c' => 'Ai1ec_Event_Instance',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.event.invalid-argument-exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/invalid-argument-exception.php',
//          'c' => 'Ai1ec_Invalid_Argument_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'model.event.legacy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/legacy.php',
//          'c' => 'Ai1ec_Event_Legacy',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.event.not-found-exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/not-found-exception.php',
//          'c' => 'Ai1ec_Event_Not_Found_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'model.event.parent' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/parent.php',
//          'c' => 'Ai1ec_Event_Parent',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.event.taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/event/taxonomy.php',
//          'c' => 'Ai1ec_Event_Taxonomy',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.filter.auth_ids' =>
//      [
//        'f' => AI1EC_PATH . 'app/model/filter/auth_ids.php',
//        'c' => 'Ai1ec_Filter_Authors',
//        'i' => 'n', // INSTANTIATE  new
//        'r' => 'y',
//      ],
//      'model.filter.cat_ids' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/cat_ids.php',
//          'c' => 'Ai1ec_Filter_Categories',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.filter.instance_ids' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/instance_ids.php',
//          'c' => 'Ai1ec_Filter_Posts_By_Instance',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.filter.int' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/int.php',
//          'c' => 'Ai1ec_Filter_Int',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.filter.interface' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/interface.php',
//          'c' => 'Ai1ec_Filter_Interface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'model.filter.post_ids' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/post_ids.php',
//          'c' => 'Ai1ec_Filter_Posts',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.filter.tag_ids' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/tag_ids.php',
//          'c' => 'Ai1ec_Filter_Tags',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'model.filter.taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/filter/taxonomy.php',
//          'c' => 'Ai1ec_Filter_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.meta' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/meta.php',
//          'c' => 'Ai1ec_Meta',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.meta-post' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/meta-post.php',
//          'c' => 'Ai1ec_Meta_Post',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.meta-user' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/meta-user.php',
//          'c' => 'Ai1ec_Meta_User',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.search' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/search.php',
//          'c' => 'Ai1ec_Event_Search',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.settings' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/settings.php',
//          'c' => 'Ai1ec_Settings',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.settings-view' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/settings-view.php',
//          'c' => 'Ai1ec_Settings_View',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'model.settings.exception' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/settings/exception.php',
//          'c' => 'Ai1ec_Settings_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'model.taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/model/taxonomy.php',
//          'c' => 'Ai1ec_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'news.feed' =>
//        [
//          'f' => AI1EC_PATH . 'lib/news/feed.php',
//          'c' => 'Ai1ec_News_Feed',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'notification.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/notification/abstract.php',
//          'c' => 'Ai1ec_Notification',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'notification.admin' =>
//        [
//          'f' => AI1EC_PATH . 'lib/notification/admin.php',
//          'c' => 'Ai1ec_Notification_Admin',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'notification.email' =>
//        [
//          'f' => AI1EC_PATH . 'lib/notification/email.php',
//          'c' => 'Ai1ec_Email_Notification',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'p28n.i18n' =>
//        [
//          'f' => AI1EC_PATH . 'lib/p28n/i18n.php',
//          'c' => 'Ai1ec_I18n',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'p28n.wpml' =>
//        [
//          'f' => AI1EC_PATH . 'lib/p28n/wpml.php',
//          'c' => 'Ai1ec_Localization_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'parser.date' =>
//        [
//          'f' => AI1EC_PATH . 'lib/parser/date.php',
//          'c' => 'Ai1ec_Parser_Date',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'parser.frequency' =>
//        [
//          'f' => AI1EC_PATH . 'lib/parser/frequency.php',
//          'c' => 'Ai1ec_Frequency_Utility',
//          'i' => 'n', // INSTANTIATE  new
//        ],
//      'post.content' =>
//        [
//          'f' => AI1EC_PATH . 'lib/post/content.php',
//          'c' => 'Ai1ec_Post_Content_Check',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'post.custom-type' =>
//        [
//          'f' => AI1EC_PATH . 'lib/post/custom-type.php',
//          'c' => 'Ai1ec_Post_Custom_Type',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'primitive.array' =>
//        [
//          'f' => AI1EC_PATH . 'lib/primitive/array.php',
//          'c' => 'Ai1ec_Primitive_Array',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'primitive.int' =>
//        [
//          'f' => AI1EC_PATH . 'lib/primitive/int.php',
//          'c' => 'Ai1ec_Primitive_Int',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'query.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/query/helper.php',
//          'c' => 'Ai1ec_Query_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'recurrence.rule' =>
//        [
//          'f' => AI1EC_PATH . 'lib/recurrence/rule.php',
//          'c' => 'Ai1ec_Recurrence_Rule',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'rewrite.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/rewrite/helper.php',
//          'c' => 'Ai1ec_Rewrite_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'robots.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/robots/helper.php',
//          'c' => 'Ai1ec_Robots_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'routing.router' =>
//        [
//          'f' => AI1EC_PATH . 'lib/routing/router.php',
//          'c' => 'Ai1ec_Router',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'routing.uri' =>
//        [
//          'f' => AI1EC_PATH . 'lib/routing/uri.php',
//          'c' => 'Ai1ec_Uri',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'routing.uri-helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/routing/uri-helper.php',
//          'c' => 'Ai1ec_Wp_Uri_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'scheduling.exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/scheduling/exception.php',
//          'c' => 'Ai1ec_Scheduling_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'script.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/script/helper.php',
//          'c' => 'Ai1ec_Script_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'template.link.helper' =>
//        [
//          'f' => AI1EC_PATH . 'lib/template/link/helper.php',
//          'c' => 'Ai1ec_Template_Link_Helper',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'theme.compiler' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/compiler.php',
//          'c' => 'Ai1ec_Theme_Compiler',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'theme.file.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/abstract.php',
//          'c' => 'Ai1ec_File_Abstract',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'theme.file.exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/exception.php',
//          'c' => 'Ai1ec_File_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'theme.file.image' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/image.php',
//          'c' => 'Ai1ec_File_Image',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'theme.file.less' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/less.php',
//          'c' => 'Ai1ec_File_Less',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'theme.file.php' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/php.php',
//          'c' => 'Ai1ec_File_Php',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'theme.file.twig' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/file/twig.php',
//          'c' => 'Ai1ec_File_Twig',
//          'i' => 'n', // INSTANTIATE  new
//        ],
//      'theme.list' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/list.php',
//          'c' => 'Ai1ec_Theme_List',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'theme.loader' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/loader.php',
//          'c' => 'Ai1ec_Theme_Loader',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'theme.search' =>
//        [
//          'f' => AI1EC_PATH . 'lib/theme/search.php',
//          'c' => 'Ai1ec_Theme_Search',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'twig.Bootstrap.String' =>
//      // deprecated see https://github.com/twigphp/Twig/pull/1641
//      //  ['f' => AI1EC_PATH . 'see https://github.com/twigphp/Twig/pull/1641/String.php', 'c' => 'Twig_Loader_String', 'i' => 'n', // INSTANTIATE  factory], 'twig.LoaderInterface' =>
//        [
//          'f' => AI1EC_PATH . 'vendor/twig/LoaderInterface.php',
//          'c' => 'Twig_LoaderInterface',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'twig.ai1ec-extension' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/ai1ec-extension.php',
//          'c' => 'Ai1ec_Twig_Ai1ec_Extension',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'twig.cache' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/cache.php',
//          'c' => 'Ai1ec_Twig_Cache',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'twig.environment' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/environment.php',
//          'c' => 'Ai1ec_Twig_Environment',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'twig.loader' =>
//        [
//          'f' => AI1EC_PATH . 'lib/twig/loader.php',
//          'c' => 'Ai1ec_Twig_Loader_Filesystem',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'valarm' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'valarm',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'validator.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'lib/validator/abstract.php',
//          'c' => 'Ai1ec_Validator',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'validator.exception' =>
//        [
//          'f' => AI1EC_PATH . 'lib/validator/exception.php',
//          'c' => 'Ai1ec_Value_Not_Valid_Exception',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'validator.numeric' =>
//        [
//          'f' => AI1EC_PATH . 'lib/validator/numeric.php',
//          'c' => 'Ai1ec_Validator_Numeric_Or_Default',
//          'i' => 'n', // INSTANTIATE  new
//          'r' => 'y',
//        ],
//      'vevent' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'vevent',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'vfreebusy' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'vfreebusy',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//
//      'view.admin.add-ons' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/admin/add-ons.php',
//          'c' => 'Ai1ec_View_Add_Ons',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.admin.event-category' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/admin/event-category.php',
//          'c' => 'Ai1ec_View_Admin_EventCategory',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.fallbacks' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/fallbacks.php',
//          'c' => 'Ai1ec_Calendar_Avatar_Fallbacks',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.page' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/page.php',
//          'c' => 'Ai1ec_Calendar_Page',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.shortcode' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/shortcode.php',
//          'c' => 'Ai1ec_View_Calendar_Shortcode',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.subscribe-button' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/subscribe-button.php',
//          'c' => 'Ai1ec_View_Calendar_SubscribeButton',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'view.calendar.taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/taxonomy.php',
//          'c' => 'Ai1ec_View_Calendar_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.view.abstract' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/abstract.php',
//          'c' => 'Ai1ec_Calendar_View_Abstract',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.view.agenda' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/agenda.php',
//          'c' => 'Ai1ec_Calendar_View_Agenda',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.view.month' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/month.php',
//          'c' => 'Ai1ec_Calendar_View_Month',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.view.oneday' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/oneday.php',
//          'c' => 'Ai1ec_Calendar_View_Oneday',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.view.week' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/view/week.php',
//          'c' => 'Ai1ec_Calendar_View_Week',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.calendar.widget' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/calendar/widget.php',
//          'c' => 'Ai1ec_View_Admin_Widget',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'view.embeddable' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/embeddable.php',
//          'c' => 'Ai1ec_Embeddable',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'view.event.avatar' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/avatar.php',
//          'c' => 'Ai1ec_View_Event_Avatar',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.color' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/color.php',
//          'c' => 'Ai1ec_View_Event_Color',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.content' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/content.php',
//          'c' => 'Ai1ec_View_Event_Content',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.location' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/location.php',
//          'c' => 'Ai1ec_View_Event_Location',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.post' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/post.php',
//          'c' => 'Ai1ec_View_Event_Post',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.single' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/single.php',
//          'c' => 'Ai1ec_View_Event_Single',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.taxonomy' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/taxonomy.php',
//          'c' => 'Ai1ec_View_Event_Taxonomy',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'view.event.ticket' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/ticket.php',
//          'c' => 'Ai1ec_View_Event_Ticket',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'view.event.time' =>
//        [
//          'f' => AI1EC_PATH . 'app/view/event/time.php',
//          'c' => 'Ai1ec_View_Event_Time',
//          'i' => 'g',  // INSTANTIATE  factory,
//          'r' => 'y',
//        ],
//      'vjournal' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'vjournal',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'vtimezone' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'vtimezone',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'vtodo' =>
//        [
//          'f' => AI1EC_PATH . 'lib/iCal/iCalcreator-2.20/iCalcreator.class.php',
//          'c' => 'vtodo',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
//      'xml.builder' =>
//        [
//          'f' => AI1EC_PATH . 'lib/xml/builder.php',
//          'c' => 'Ai1ec_XML_Builder',
//          'i' => 'g',  // INSTANTIATE  factory,
//        ],
    ],
];
