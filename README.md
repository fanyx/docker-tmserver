# docker-tmserver
Docker images for simple or customizable Trackmania Nations Forever server.

## How to use this repo:
Run the images in this repo with `docker-compose`.  
I've included a template [`docker-compose.yml`](https://github.com/ryluth/docker-tmserver/blob/master/docker-compose.yml) in this repo to get you going.

### Required:
  #### tmserver:
    - `$SERVER_LOGIN`               | Server account login
    - `$SERVER_LOGIN_PASSWORD`      | Server account password
    - `$SERVER_SA_PASSWORD`         | Password for SuperAdmin credential
    - `$SERVER_ADM_PASSWORD`        | Password for Admin credential
  #### xaseco:
    - `$SERVER_SA_PASSWORD`         | Password for SuperAdmin credential
    - `$DB_HOST`                    | -> subject to deletion since i'm gonna lock it down 
    - `$DB_LOGIN`                   | ^
    - `$DB_LOGIN_PASSWORD`          | ^
    - `$DB_NAME`                    | ^
  
### Optional environment variables are:
  #### tmserver:
    - `$SERVER_PORT`                | Port for server communications -> Default : 2350
    - `$SERVER_P2P_PORT`            | Port for peer2peer communication -> Default : 3450
    - `$SERVER_NAME`                | Server name in ingame browser -> Default : "Trackmania Server"
    - `$SERVER_COMMENT`             | Server description -> Default : "This is a Trackmania Server"
    - `$SERVER_PASSWORD`            | If you want to secure your server against unwanted logins, set a server password
  
## Configuring the Trackmania server
Without manual configuration the server launches with Nadeo's default config.  
To configure the server on your own demands edit the `config.txt` file in `tmserver/GameData/Config`.  
To configure the tracklist edit the `playlist.txt` in `tmserver/GameData/Tracks/MatchSettings/`.  
**IMPORTANT!!**  
After making changes to the server configuration run `docker-compose build` to rebuild the container images, otherwise the changes won't be in effect.  
