# docker-tmserver

Docker image for simple or customizable Trackmania Nations/United Forever server
bundled with XAseco.

## How to use this image

### docker run

`docker run --env-file <path/to/env-file> -p 2350/2350:udp -p 3450/3450:udp [-v {volumes}] fanyx/tmserver`

### docker-compose

Check the default [`docker-compose.yml`](./docker-compose.yml) to familiarize yourself
with a possible setup. Adjust it to your needs and according to the documentation below,
then run

`docker-compose up -d`

## Configuration - Trackmania

### Mandatory

```
  - SERVER_LOGIN                | Server account login
  - SERVER_LOGIN_PASSWORD       | Server account password
```

### Optional

```
  - SERVER_SA_PASSWORD          | Password for SuperAdmin credential
                                  -> when left empty will be randomly generated
  - SERVER_ADM_PASSWORD         | Password for Admin credential
                                  -> when left empty will be randomly generated
  - SERVER_PORT                 | Port for server communications -> Default : 2350
  - SERVER_P2P_PORT             | Port for peer2peer communication -> Default : 3450
  - SERVER_NAME                 | Server name in ingame browser -> Default : "Trackmania Server"
  - SERVER_COMMENT              | Server description -> Default : "This is a Trackmania Server"
  - SERVER_PASSWORD             | If you want to secure your server against
                                  unwanted logins, set a server password
  - HIDE_SERVER                 | Whether you want your server public or not -> Default : 0 (public)
  - MAX_PLAYERS                 | Max player count -> Default : 32
  - PACKMASK                    | Leave empty to change server mode to United -> Default : stadium
                                  (Nations)
```

### Gamemodes

```
  - GAMEMODE                    | 0 (Rounds), 1 (TimeAttack), 2 (Team), 3 (Laps), 4 (Stunts) -> Default : 1
  - CHATTIME                    | Chat time value in milliseconds -> Default : 10000
  - FINISHTIMEOUT               | Finish timeout value in milliseconds.
                                  0 = classic, 1 = adaptive -> Default : 1
  - DISABLERESPAWN              | 0 (respawns enabled), 1 (respawns disabled) -> Default : 0
```

#### Gamemode : Rounds

```
  - ROUNDS_POINTSLIMIT          | Points limit for rounds mode -> Default : 30
```

#### Gamemode : TimeAttack

```
  - TIMEATTACK_LIMIT            | Time limit in milliseconds for time attack mode -> Default : 180000
```

#### Gamemode : Team

```
  - TEAM_POINTSLIMIT            | Points limit for team mode -> Default : 50
  - TEAM_MAXPOINTS              | Number of maximum points per round for team mode -> Default : 6
```

#### Gamemode : Laps

```
  - LAPS_NBLAPS                 | Number of laps for laps mode -> Default : 5
  - LAPS_TIMELIMIT              | Time limit in milliseconds for laps mode -> Default : 0
```

#### Gamemode : Cup

```
  - CUP_POINTSLIMIT             | Points limit for cup mode -> Default : 100
  - CUP_ROUNDSPERCHALLENGE      | Rounds per challenge -> Default : 5
  - CUP_NBWINNERS               | Number of Winners -> Default : 3
  - CUP_WARMUPDURATION          | Warmup duration -> Default : 2
```

## Configuration - XAseco

I've taken the freedom to ease the MySQL configuration a bit.
Other plugins still need to be configured fully.
Acquire the necessary files and follow the guide to custom configurations below.

### Mandatory

```
  - MASTERADMIN_LOGIN           | Login name of the player to assume MasterAdmin role for XAseco
```

### MySQL

```
  - MYSQL_HOST                  | Host of MySQL database -> Default : db
  - MYSQL_LOGIN                 | Username of MySQL database -> Default : trackmania
  - MYSQL_PASSWORD (Mandatory)  | Password of MySQL user
  - MYSQL_DATABASE              | Name of MySQL database -> Default : trackmania
```

## Customization

Apart from the configuration possibilities, I've included some scripts to add custom tracks, tracklist, configuration files, plugins and a blacklist to disable unwanted default plugins.

### Custom Tracks

While the Nadeo tracks are available in this repository and accessible under `GameData/Tracks/Challenges/Nadeo/`,
adding custom tracks from e.g. [Trackmania Exchange](https://tmuf.exchange/) is as simple as placing the files
in the `tracks/` folder and mounting it to `/var/lib/tmserver/GameData/Tracks/Custom/`.

```
[...]
  tmserver:
    image: fanyx/tmserver
    [...]
    volumes:
     - ./tracks:/var/lib/tmserver/GameData/Tracks/Custom
[...]
```

### Custom Playlist

You can add tracks to a playlist in a simple way.
Create a plaintext file like in the example below and mount it to `/var/lib/tmserver/playlist.txt`.
To enable parsing of this file set `CUSTOM_PLAYLIST` in your env-file to anything but an empty string.

The tracks for the server are stored relative to `/var/lib/tmserver/GameData/Tracks`.
Creating your own playlist is as easy as specifying each track on a separate line in the `playlist.txt`
by its relative path to the `Tracks` folder.

#### Example:
Folder structure:
```
|--> docker-compose.yml
|--> ./tracks
  |--> mini01.Challenge.Gbx
  `--> SpeedxZxZ.Challenge.Gbx
|--> ./db-data
`--> ./playlist.txt
```

playlist.txt :
```
Challenges/Nadeo/C01-Race.Challenge.Gbx
Custom/mini01.Challenge.Gbx
Custom/SpeedxZxZ.Challenge.Gbx
```

---
**NOTE**

When mounting your own `playlist.xml` file to the container you overwrite a lot of
customization options that usually would be parsed from environment variables.

```xml
	<gameinfos>
		<game_mode>@GAMEMODE@</game_mode>
		<chat_time>@CHATTIME@</chat_time>
		<finishtimeout>@FINISHTIMEOUT@</finishtimeout>
		<disablerespawn>@DISABLERESPAWN@</disablerespawn>
		<rounds_pointslimit>@ROUNDS_POINTSLIMIT@</rounds_pointslimit>
		<team_pointslimit>@TEAM_POINTSLIMIT@</team_pointslimit>
		<team_maxpoints>@TEAM_MAXPOINTS@</team_maxpoints>
		<timeattack_limit>@TIMEATTACK_LIMIT@</timeattack_limit>
		<timeattack_synchstartperiod>0</timeattack_synchstartperiod>
		<laps_nblaps>@LAPS_NBLAPS@</laps_nblaps>
		<laps_timelimit>@LAPS_TIMELIMIT@</laps_timelimit>
		<cup_pointslimit>@CUP_POINTSLIMIT@</cup_pointslimit>
		<cup_roundsperchallenge>@CUP_ROUNDSPERCHALLENGE@</cup_roundsperchallenge>
		<cup_nbwinners>@CUP_NBWINNERS@</cup_nbwinners>
		<cup_warmupduration>@CUP_WARMUPDURATION@</cup_warmupduration>
  </gameinfos>
```

If you're familiar with these options anyway, you can mount `playlist.xml` to
`/var/lib/tmserver/GameData/MatchSettings/playlist.xml` and use it as a complete
and persistent configuration file that can be written to by plugins or external tools
like RemoteCP.

---

### Custom configuration files

Most plugins need you to provide valid configuration files to function in the first place.
Place these in a folder e.g. `config/` and mount it to `/var/lib/xaseco/config/`.
All files will be linked to XAseco's root folder.

Careful, this will overwrite exisiting default files and `localdatabase.xml` as well.

### Custom plugins

Custom plugins work similar to configuration files.
Create a folder like `plugins/` and mount it to `/var/lib/xaseco/plugins/custom/`.
They will be linked down to the plugins folder.

### Plugin blacklist

Create a file called `blacklist.txt` and list plugins by filename that you want ignored on
XAseco's boot.
Mount this file at `/var/lib/xaseco/blacklist.txt`.

blacklist.txt:
```
jfreu.chat.php
jfreu.plugin.php
```

would disable jfreu's plugins but leave all others enabled.
