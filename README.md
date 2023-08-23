
Radio Thermstat Web Interface
Radio Thermostat CT-50, CT80, 3M50 CT-30

- JW Smythe https://jwsmythe.com  jwsmythe[at]jwsmythe.com

v1.0 2023.08.23

This is shared under the non-commercial Creative Commons CC BY-SA.  You may 
use and share this code for non-commercial purposes.  If you're going to roll
it into a product, please contact me for licensing.

===== About =================================================================
   This project is to provide a web interface for the Radio Thermostat CT-50, CT80, 3M50 CT-30 thermostats.
   
   As of May 15, 2023, the official Radio Thermostat web app is being 
   discontinued.  With this, we all lost the web interface, history logging, 
   etc, that their site provided.   This project is to give you that same 
   functionality.    I did ask them for the site code, so I could match 
   their old look and feel.  They never responded.   So this is all new code.

   It project provides:

   * Web interface that you can place on a local server. 
     * Ability to set the temp
     * Ability to set the schedule
     * Ability to change HVAC mode and fan mode
     * Display of current information.
   * MRTG graphing of the current temperature and target temperature
   * MQTT publish current temperature for other MQTT nodes to use.

===== Requirements ==========================================================

   1) A web server that supports PHP, that is on the same network.  
      A RPi or similar would be more than sufficient.
      
   2) phpMQTT for the MQTT publish function if so desired.  
      https://github.com/bluerhinos/phpMQTT
      
   3) MRTG installed on host, if you want graphing.

   4) Cron or similar scheduler, if you want MQTT and/or MRTG to work.

===== Files ================================================================= 
   * mrtg.thermostat.conf - MRTG configuration file, for temp/target graph
   * phpMQTT.php          - MQTT library from https://github.com/bluerhinos/phpMQTT
   * tstat_api_gw.php     - PHP to send API requests directly to the thermostat
   * tstat_gloabls.php    - Global variables used by the PHP files.
   * tstat_info.php       - A dump of all the API calls supported by the thermostat.  
                          This is slow, but is only used for developing new features.
   * tstat_main.html      - The main page.  You should copy this to index.html
   * tstat_poll_mnqtt.php - This is run by the cron to send out current mqtt data.
   * tstat_poll_mrtg.php  - This is run by the cron, to update the graphs.
   * tstat_scheduler.php  - Editor for the scheduler.
   * whoami.php           - Simple page to show what $_SERVER $_GET, $_POST, $_REQUEST, and $_COOKIE are present.  
                          You can include this into other php pages, while developing.

   * ./docs/              - This directory contains the user manual for the 
                          Radio Thermostat CT-50, CT80, 3M50, and CT-30, as 
                          well as the API doc that I found a while back.  
                          If there is a more recent version of the API doc, 
                          I would appreciate getting a copy.
   * ./images/            - Some images for the web interface
   * ./data/              - MRTG data
===== Installation ==========================================================
   1) Copy the files for this package to a directory on your local web server.  
   The web server *must* be on the same network segment.  These thermostats
   do not have *ANY* security to them.  There is no authentication for API 
   calls.  Do *NOT* ever put this thermostat directly facing the Internet. 
   
   I installed it on my custom firewall (Linux server), on the web interface
   that is only accessible by the LAN. (bound to 192.168.1.0:80 :443, not 
   0.0.0.0:80 :433)
                          
   2) Modify the tstat_globals.php file to match your installation. 
   
   3) Browse to it, and see that it works.
   
   4) Set the crons (below) as necessary for automated tasks.  The scheduler
   itself runs automatically on the thermostat.  There is nothing more to do for that.
   
===== Cron ==================================================================

   I have opted to run both the MRTG and MQTT crons once per minute.  That gives 
   more datapoints than the typical 5+ minute interval.  This is in the standard 
   Linux/Unix cron format.  Fix your paths for your machine's paths.
   
   */1 * * * * /usr/bin/mrtg /var/www/htdocs/thermostat/mrtg.thermostat.conf
   */1 * * * * /usr/local/bin/php /var/www/htdocs/thermostat/tstat_poll_mqtt.php
