FROM debian:buster-slim

RUN mkdir /opt/tmserver 

COPY tmserver/ /opt/tmserver
COPY entrypoint-tmserver.sh /opt/tmserver

RUN apt update \
	&& apt install pwgen gettext-base

RUN groupadd trackmania
RUN useradd -M -g trackmania trackmania
RUN chown -R trackmania:trackmania /opt/tmserver
RUN chown trackmania:trackmania /entrypoint-tmserver.sh

USER trackmania
WORKDIR /opt/tmserver

ENTRYPOINT ["./entrypoint-tmserver.sh"]
