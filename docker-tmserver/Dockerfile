FROM debian:buster-slim 

COPY tmserver/ /opt/
COPY entrypoint-tmserver.sh /opt/tmserver/

RUN apt update \
	&& apt install -y pwgen gettext-base

RUN groupadd trackmania
RUN useradd -M -g trackmania trackmania
RUN chown -R trackmania:trackmania /opt/tmserver

USER trackmania
WORKDIR /opt/tmserver

ENTRYPOINT ./entrypoint-tmserver.sh
