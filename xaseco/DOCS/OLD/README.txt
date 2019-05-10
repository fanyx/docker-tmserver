BEFORE ATTEMPTING TO INSTALL RASP, YOU MUST HAVE A FULLY WORKING VERSION OF ASECO 0.6.1b RUNNING ON A LOCAL DATABASE


Introduction
------------
Rasp is a plugin pack for the aseco server script. The core rasp plugin is the ranks and stats system which heavily relies on aseco's records system. The other plugins are extra features which you can add/remove as you please and are completely independent of the core plugin.

Features
--------
Here is a list of the features:

- In-built rank system which assigns a rank based on all records a player has made.
- Stats calculation which shows a player their personal best and average time on a track.
- Jukebox system which allows a player to select their favorite tracks to be played.
- Direct adding of tracks from http://nations.tm-exchange.com (Nations only)
- Karma system which works as a simple but effective replacement of the voting system.
- Built in IRC bot which links your server to a channel on an IRC server.
- /nextmap command which shows the track that will be played next on the server.
- /hi, /bye, /gg, /lol commands from FAST.
- /msg command which allows players to communicate privately via private message.
- Team match scoring & output
- New admin commands


Minimum Requirements
--------------------
-Php5 or above (haven't tested with php4 but feel free to try)
-MySQL 5.1 or above
-Stable installation of Aseco 0.6.1b


Installation
------------

--CORE PLUGIN--

Before installing the core plugin you MUST ensure Aseco 0.6.1b is running stable on your server. Resolve any issues with aseco before attempting to install rasp.

1. BACK UP your working ASECO files!!!!!!!!! All of them, including files in subdirectories!!!!!

2. Un-rar/un-zip the rasp files and upload the following to the appropriate directory on your server. All files must be within the aseco root folder and in their correct directories.
   - all but five of the files are in their correct directories in the current zip file; use winzip, or similar, and extract with folder/subdirectory names to the main aseco folder
   - overwrite existing files with all of the ones that are in the zip file
   - newinstall/rasp.settings.php has default values in it for a new install. Move it to the includes folder only if this is a brand new install of RASP and you don't yet have an includes/rasp.settings.php file.
   - newinstall/*.xml goes in the main ASECO directory. If you are already running ASECO/RASP, you can leave these alone.

3. Run the rasp.sql file on your aseco database, either with PhpMyAdmin or with console access to mysql.

4, 5, & 6 apply to new installs only. Existing installs can skip these steps.

4. Edit includes/rasp.settings.php as required.

5. Edit plugins.xml to display the line <plugin>plugin.rasp.php</plugin> or copy the one out of newinstall and edit as needed.

6. Edit rasp.xml, localdatabase.xml, publicdatabase.xml in the main ASECO directory as needed.

7. If you had an existing RASP installation (prior to 1.1), open newinstall/localdatabase.xml and copy over the <messages> section to your existing localdatabase.xml. Do the same for publicdatabase.xml.

8. If you had an existing RASP installation prior to 1.1, and are going to use the matchsave feature, edit matchsave.xml and make appropriate changes as necessary. If you don't want a continuous output file (append, instead of overwrite), you should be able to use nul: for Windows, and /dev/null for linux. If you don't want matchsave, don't add it to plugins.xml.

9. Restart Aseco!

Any problems go to http://www.tm-forum.com/viewtopic.php?t=3356


--ADDITIONAL PLUGINS--

The extra plugins can be used independently or alongside the core plugin. If
used independently you must ensure rasp.settings.php has been copied over to
the includes directory.

1. Place the plugin file inside the plugins folder for your aseco installation.

2. Insert the line <plugin>plugin.rasp_*plugin-name*.php</plugin> in plugins.xml

3. Modify includes/rasp.settings.php as required.

3. Restart aseco.


plugin.rasp_jukebox.php
-----------------------
This plugin adds the jukebox function to your server, as well as the jukebox extension for requesting tracks directly from TM-Exchange. There are several variables inside rasp.settings.php which correspond to this plugin.

To add a track to the jukebox type:
/jukebox <track_id>

To check what a track's ID is use /list. If you do /list xxx, you'll get a list of all tracks with "xxx" in their name, or author's name.

To find out the best & worst rated tracks, use /list karma +# or -#. You'll get back a list of all tracks with # or higher value for +, and all tracks with # or lower for -.

If an admin wants to clear the jukebox use /admin clearjukebox (requires the rasp version of chat.admin.php)

To add a track from TM-Exchange use:
/add <tmx_id>
The tmx_id is the number on the end of the external link which is at the top of a track's page in TMX.
Using the /add command successfuly will start a vote. You can specify the pass ratio in rasp.settings.php
If a player wants to vote for the track they must use /y.
If the vote is successful the track will be added to the jukebox. Once the track has been played it is removed from the map rotation. To add a track permenantly use /admin add <tmx_id> (requires the rasp version of chat.admin.php)


plugin.rasp_chat.php
--------------------
This plugin adds various chat commands to your server which were originally seen in FAST. These commands include /hi, /bye, /gg, /lol, /lool, /msg

To use the hi/bye/gg commands you must use the following syntax (with /hi used as an example)

/hi --displays the message "Hello all !"
/hi player_name --displays "Hello player_name !"

The /msg command is used for sending private messages.
/msg player_login *some text* --will send *some text* to the player whose login name is player_login.

/lol & /lool don't require any arguments.

plugin.rasp_karma.php
---------------------
This plugin adds a replacement to the built-in voting system for aseco. It is much easier to use and seems to get more usage than the original /vote command. Players type "/++" if they like a track and "/--" if they don't. The /karma command shows the track's current karma.

plugin.rasp_irc.php
-------------------
If this plugin is added it will link your server to an IRC channel. You MUST specify the IRC variables in rasp.settings.php before adding this plugin. If successful you will see a bot in your chosen channel which will relay all chat from your server. It also parses the messages typed by other users in the channel and relays them to the TM-server. It is recommended you use a seperate channel for this bot, as putting it in an existing channel will make it virtually un-usable for anything else if you have a busy server.

plugin.rasp_nextmap.php
-----------------------
This plugin is fairly simple. It adds one command to your server, /nextmap which shows the map to be played next on the server.

---------------------------------------------
Released under the GNU General Public License
Copyright (C) 2006 Iain Surgey

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
