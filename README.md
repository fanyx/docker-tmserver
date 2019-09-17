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
