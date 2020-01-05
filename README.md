# docker-tmserver
Docker image for simple or customizable Trackmania Nations Forever server

## How to use this image
`docker run -e {required environment variables} -p {selected ports} fanyx/tmserver`

### There are several required environment variables that you need to set:
```
  - $SERVER_LOGIN               | Server account login
  - $SERVER_LOGIN_PASSWORD      | Server account password
```


### Optional environment variables are:
```
  - $SERVER_PORT                | Port for server communications -> Default : 2350
  - $SERVER_P2P_PORT            | Port for peer2peer communication -> Default : 3450
  - $SERVER_SA_PASSWORD         | Password for SuperAdmin credential -> when left empty will be randomly generated
  - $SERVER_ADM_PASSWORD        | Password for Admin credential -> when left empty will be randomly generated
  - $SERVER_NAME                | Server name in ingame browser -> Default : "Trackmania Server"
  - $SERVER_COMMENT             | Server description -> Default : "This is a Trackmania Server"
  - $SERVER_PASSWORD            | If you want to secure your server against unwanted logins, set a server password
```


## Running this image with `docker-compose`
I have a default [`docker-compose.yml`](https://github.com/ryluth/docker-tmserver/blob/master/docker-compose.yml) included in this repository.
You can adjust this file to your needs but running with docker-compose is more comfortable in general.

## Configuring the Trackmania server
Without manual configuration the server launches with Nadeo's default config. 
This means round based driving.

Other configuration options are listed below:
```
  - $GAMEMODE | 0 (Rounds), 1 (TimeAttack), 2 (Team), 3 (Laps), 4 (Stunts) -> Default : 1
  - $CHATTIME | chat time value in milliseconds -> Default : 10000
  - $FINISHTIMEOUT | finish timeout value in milliseconds. 0 means classic, 1 means adaptive -> Default : 1
  - $DISABLERESPAWN | 0 (respawns enabled), 1 (respawns disabled) -> Default : 0
```


#### Gamemode : Rounds
```
  - $ROUNDS_POINTSLIMIT | points limit for rounds mode -> Default : 30
```


#### Gamemode : TimeAttack
```
  - $TIMEATTACK_LIMIT | time limit in milliseconds for time attack mode -> Default : 180000
```


#### Gamemode : Team
```
  - $TEAM_POINTSLIMIT | points limit for team mode -> Default : 50
  - $TEAM_MAXPOINTS | number of maximum points per round for team mode -> Default : 6
```


#### Gamemode : Laps
```
  - $LAPS_NBLAPS | number of laps for laps mode -> Default : 5
  - $LAPS_TIMELIMIT | time limit in milliseconds for laps mode -> Default : 0
```


### Running custom tracks
While the Nadeo tracks are available in this repository and accessible under `GameData/Tracks/Challenges/Nadeo/` you can also run custom tracks following the instructions below.

You can run custom tracks by mounting a volume from where your tracks are stored to `/opt/tmserver/GameData/Tracks/Custom`.

In this example i am storing my tracks in `./tracks` relative to the docker-compose file.

```
[...]
  tmserver:
    image: fanyx/tmserver:latest
    [...]
    volumes:
     - ./tracks:/opt/tmserver/GameData/Tracks/Custom
[...]
```


### Running a custom playlist

You can add tracks to a playlist in a simple way. Just provide a `playlist.txt` that contains every track in a certain format. Create a folder next to your `docker-compose.yml`, mount it as a volume to `/opt/tmserver/playlist` and put the `playlist.txt` in there.

The tracks for the server are stored relative to `/opt/tmserver/GameData/Tracks`. Creating your own playlist is as easy as specifying each track on a separate line in the `playlist.txt` adressed by its relative path to the `Tracks` folder.

#### Example:
Folder structure:
```
|--> docker-compose.yml
|--> ./tracks
|--> ./db-data
`--> ./playlist
   `--> playlist.txt
```

playlist.txt :
```
Challenges/Nadeo/C01-Race.Challenge.Gbx
Custom/mini01.Challenge.Gbx
Custom/SpeedxZxZ.Challenge.Gbx
```
